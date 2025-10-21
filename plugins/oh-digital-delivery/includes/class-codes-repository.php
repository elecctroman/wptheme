<?php
/**
 * Handles persistence for license pool records.
 *
 * @package OH\DigitalDelivery\Storage
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Storage;

use DateTimeImmutable;
use InvalidArgumentException;
use OH\DigitalDelivery\Encryption;
use wpdb;

class Codes_Repository
{
    private wpdb $db;

    private string $table;

    public function __construct(?wpdb $db = null)
    {
        global $wpdb;

        $this->db    = $db ?: $wpdb;
        $this->table = $this->db->prefix . 'oh_licence_pool';
    }

    public function get_table_name(): string
    {
        return $this->table;
    }

    public function reserve_codes(int $product_id, int $quantity, int $order_id): array
    {
        if ($quantity <= 0) {
            return [];
        }

        $this->db->query('START TRANSACTION');

        $codes = $this->db->get_results(
            $this->db->prepare(
                "SELECT id, code FROM {$this->table} WHERE product_id = %d AND status = 'free' ORDER BY id ASC LIMIT %d FOR UPDATE",
                $product_id,
                $quantity
            )
        );

        if (empty($codes)) {
            $this->db->query('ROLLBACK');

            return [];
        }

        $assigned = [];
        $now      = (new DateTimeImmutable('now', wp_timezone()))->format('Y-m-d H:i:s');

        foreach ($codes as $row) {
            $this->db->update(
                $this->table,
                [
                    'status'   => 'used',
                    'order_id' => $order_id,
                    'used_at'  => $now,
                ],
                ['id' => (int) $row->id],
                ['%s', '%d', '%s'],
                ['%d']
            );

            $assigned[] = [
                'id'    => (int) $row->id,
                'code'  => Encryption::decrypt($row->code),
                'raw'   => $row->code,
            ];
        }

        $this->db->query('COMMIT');

        return $assigned;
    }

    public function release_codes_for_order(int $order_id): void
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table} SET status = 'free', used_at = NULL, order_id = NULL WHERE order_id = %d AND status = 'used'",
                $order_id
            )
        );
    }

    public function revoke_codes_for_order(int $order_id): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT id, code FROM {$this->table} WHERE order_id = %d",
                $order_id
            )
        );

        if (empty($rows)) {
            return [];
        }

        $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table} SET status = 'revoked', used_at = NULL WHERE order_id = %d",
                $order_id
            )
        );

        return array_map(
            static fn($row) => [
                'id'   => (int) $row->id,
                'code' => Encryption::decrypt($row->code),
            ],
            $rows
        );
    }

    public function count_free_codes(int $product_id): int
    {
        return (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE product_id = %d AND status = 'free'",
                $product_id
            )
        );
    }

    public function count_by_status(string $status): int
    {
        return (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE status = %s",
                $status
            )
        );
    }

    public function get_codes_for_order(int $order_id): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT id, product_id, status, code, used_at FROM {$this->table} WHERE order_id = %d",
                $order_id
            )
        );

        if (! $rows) {
            return [];
        }

        return array_map(
            static fn($row) => [
                'id'         => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'status'     => $row->status,
                'code'       => Encryption::decrypt($row->code),
                'used_at'    => $row->used_at,
            ],
            $rows
        );
    }

    public function get_codes_by_ids(array $ids): array
    {
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $query        = $this->db->prepare(
            "SELECT id, product_id, status, code FROM {$this->table} WHERE id IN ({$placeholders})",
            ...$ids
        );

        $rows = $this->db->get_results($query);

        return array_map(
            static fn($row) => [
                'id'         => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'status'     => $row->status,
                'code'       => Encryption::decrypt($row->code),
            ],
            $rows ?: []
        );
    }

    public function get_codes_for_user(int $user_id): array
    {
        $rows = $this->db->get_results(
            $this->db->prepare(
                "SELECT c.id, c.product_id, c.status, c.code, c.used_at, c.order_id
                FROM {$this->table} AS c
                INNER JOIN {$this->db->postmeta} AS meta ON meta.post_id = c.order_id AND meta.meta_key = '_customer_user'
                WHERE CAST(meta.meta_value AS UNSIGNED) = %d",
                $user_id
            )
        );

        if (! $rows) {
            return [];
        }

        return array_map(
            static fn($row) => [
                'id'         => (int) $row->id,
                'product_id' => (int) $row->product_id,
                'status'     => $row->status,
                'code'       => Encryption::decrypt($row->code),
                'order_id'   => (int) $row->order_id,
                'used_at'    => $row->used_at,
            ],
            $rows
        );
    }

    public function import_codes_from_csv(string $csv, int $product_id, ?string $note = null): int
    {
        $lines = array_filter(array_map('trim', explode("\n", $csv)));
        $inserted = 0;

        foreach ($lines as $line) {
            $encrypted = Encryption::encrypt($line);
            $result = $this->db->insert(
                $this->table,
                [
                    'product_id' => $product_id,
                    'code'       => $encrypted,
                    'status'     => 'free',
                    'note'       => $note,
                ],
                ['%d', '%s', '%s', '%s']
            );

            if (false !== $result) {
                $inserted++;
            }
        }

        return $inserted;
    }

    public function mark_revoked(int $code_id): void
    {
        $this->db->update(
            $this->table,
            ['status' => 'revoked'],
            ['id' => $code_id],
            ['%s'],
            ['%d']
        );
    }

    public function set_status(int $code_id, string $status): void
    {
        $allowed = ['free', 'reserved', 'used', 'revoked'];
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid license status supplied.');
        }

        $set     = ['status = %s'];
        $params  = [$status];

        if (in_array($status, ['free', 'revoked'], true)) {
            $set[] = 'order_id = NULL';
            $set[] = 'used_at = NULL';
        }

        $params[] = $code_id;

        $sql = $this->db->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $set) . ' WHERE id = %d',
            ...$params
        );

        $this->db->query($sql);
    }

    public function get_stock_overview(): array
    {
        $rows = $this->db->get_results(
            "SELECT product_id, status, COUNT(*) AS total FROM {$this->table} GROUP BY product_id, status",
            ARRAY_A
        );

        if (! $rows) {
            return [];
        }

        $overview = [];
        foreach ($rows as $row) {
            $product_id = (int) $row['product_id'];
            if (! isset($overview[$product_id])) {
                $overview[$product_id] = [
                    'product_id' => $product_id,
                ];
            }

            $overview[$product_id][$row['status']] = (int) $row['total'];
        }

        return array_values($overview);
    }

    public function get_low_stock_products(int $threshold = 10): array
    {
        $query = $this->db->prepare(
            "SELECT product_id, COUNT(*) AS remaining
             FROM {$this->table}
             WHERE status = 'free'
             GROUP BY product_id
             HAVING remaining < %d",
            $threshold
        );

        $rows = $this->db->get_results($query, ARRAY_A);

        return array_map(
            static fn($row) => [
                'product_id' => (int) $row['product_id'],
                'remaining'  => (int) $row['remaining'],
            ],
            $rows ?: []
        );
    }
}
