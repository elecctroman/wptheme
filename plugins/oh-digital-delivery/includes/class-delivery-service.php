<?php
/**
 * Encapsulates business rules for fulfilling and revoking license deliveries.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use OH\DigitalDelivery\Order_Notifier;
use OH\DigitalDelivery\Storage\Codes_Repository;
use WC_Order;
use WC_Order_Item_Product;
use function __;
use function wc_get_product;

class Delivery_Service
{
    private Codes_Repository $repository;

    private Order_Notifier $notifier;

    public function __construct(Codes_Repository $repository, Order_Notifier $notifier)
    {
        $this->repository = $repository;
        $this->notifier   = $notifier;
    }

    public function process_order(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            return;
        }

        $assigned_codes    = [];
        $assigned_accounts = [];
        $delivery_status   = 'completed';

        foreach ($order->get_items() as $item) {
            if (! $item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            if (! $product) {
                continue;
            }

            $delivery_type = $product->get_meta('delivery_type');
            if (! in_array($delivery_type, ['code_pool', 'account_creds'], true)) {
                continue;
            }

            $quantity = (int) $item->get_quantity();
            $codes    = $this->repository->reserve_codes((int) $product->get_id(), $quantity, $order_id);

            if (empty($codes)) {
                $delivery_status = 'failed';
                continue;
            }

            if (count($codes) < $quantity && 'failed' !== $delivery_status) {
                $delivery_status = 'partial';
            }

            foreach ($codes as $code) {
                $record = [
                    'code_id'      => $code['id'],
                    'product_id'   => (int) $product->get_id(),
                    'product_name' => $product->get_name(),
                    'code'         => $code['code'],
                    'masked'       => $this->mask_code($code['code']),
                ];

                if ('account_creds' === $delivery_type) {
                    $assigned_accounts[] = $record;
                } else {
                    $assigned_codes[] = $record;
                }
            }

            $remaining = $this->repository->count_free_codes((int) $product->get_id());
            if ($remaining < 10) {
                $this->notifier->notify_stock_low((int) $product->get_id(), $remaining);
            }
        }

        if (empty($assigned_codes) && empty($assigned_accounts)) {
            $delivery_status = 'pending';
        }

        $order->update_meta_data('oh_assigned_codes', array_map(
            static fn($code) => [
                'code_id'    => $code['code_id'],
                'product_id' => $code['product_id'],
            ],
            $assigned_codes
        ));

        $order->update_meta_data('oh_assigned_accounts', array_map(
            static fn($code) => [
                'code_id'    => $code['code_id'],
                'product_id' => $code['product_id'],
            ],
            $assigned_accounts
        ));

        $order->update_meta_data('oh_delivery_status', $delivery_status);
        $order->save();

        if ('pending' !== $delivery_status) {
            $this->notifier->send_delivery_email($order, [
                'codes'    => $assigned_codes,
                'accounts' => $assigned_accounts,
                'status'   => $delivery_status,
            ]);
        }
    }

    public function handle_refund(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            return;
        }

        $revoked = $this->repository->revoke_codes_for_order($order_id);
        $order->update_meta_data('oh_delivery_status', 'failed');
        $order->save();

        if (! empty($revoked)) {
            $this->notifier->send_revoked_email($order, $revoked);
        }
    }

    public function release_codes(int $order_id): void
    {
        $this->repository->release_codes_for_order($order_id);
    }

    public function mask_code(string $code): string
    {
        $normalized = preg_replace('/\s+/', '', $code) ?: $code;
        $length     = strlen($normalized);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        $chunk = max(4, (int) floor($length / 4));
        $segments = str_split($normalized, $chunk);

        foreach ($segments as $index => $segment) {
            if (0 === $index || (count($segments) - 1) === $index) {
                continue;
            }

            $segments[$index] = str_repeat('*', strlen($segment));
        }

        return implode('-', $segments);
    }

    public function get_licenses_for_user(int $user_id): array
    {
        return $this->repository->get_codes_for_user($user_id);
    }

    public function get_licenses_for_order(int $order_id): array
    {
        return $this->repository->get_codes_for_order($order_id);
    }

    public function import_codes_from_csv(string $csv, int $product_id, ?string $note = null): int
    {
        return $this->repository->import_codes_from_csv($csv, $product_id, $note);
    }

    public function mark_code_revoked(int $code_id): void
    {
        $this->repository->mark_revoked($code_id);
    }

    public function update_code_status(int $code_id, string $status): void
    {
        $this->repository->set_status($code_id, $status);
    }

    public function check_stock_levels(int $threshold = 10): void
    {
        $low_stock = $this->repository->get_low_stock_products($threshold);

        foreach ($low_stock as $row) {
            $this->notifier->notify_stock_low((int) $row['product_id'], (int) $row['remaining']);
        }
    }

    public function get_low_stock_products(int $threshold = 10): array
    {
        return $this->repository->get_low_stock_products($threshold);
    }

    public function cancel_delivery(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            return;
        }

        $this->repository->release_codes_for_order($order_id);
        $order->update_meta_data('oh_assigned_codes', []);
        $order->update_meta_data('oh_assigned_accounts', []);
        $order->update_meta_data('oh_delivery_status', 'pending');
        $order->save();
    }

    public function resend_delivery(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            return;
        }

        $assigned_codes    = (array) $order->get_meta('oh_assigned_codes');
        $assigned_accounts = (array) $order->get_meta('oh_assigned_accounts');

        $code_ids     = array_map(static fn($entry) => (int) ($entry['code_id'] ?? 0), $assigned_codes);
        $account_ids  = array_map(static fn($entry) => (int) ($entry['code_id'] ?? 0), $assigned_accounts);
        $codes        = $this->repository->get_codes_by_ids(array_filter($code_ids));
        $accounts     = $this->repository->get_codes_by_ids(array_filter($account_ids));

        $payload = [
            'codes'    => array_map(
                function ($code) {
                    $product = wc_get_product($code['product_id']);

                    return [
                        'code_id'      => $code['id'],
                        'product_id'   => $code['product_id'],
                        'product_name' => $product ? $product->get_name() : __('Silinmiş ürün', 'oh-digital-delivery'),
                        'code'         => $code['code'],
                        'masked'       => $this->mask_code($code['code']),
                    ];
                },
                $codes
            ),
            'accounts' => array_map(
                function ($code) {
                    $product = wc_get_product($code['product_id']);

                    return [
                        'code_id'      => $code['id'],
                        'product_id'   => $code['product_id'],
                        'product_name' => $product ? $product->get_name() : __('Silinmiş ürün', 'oh-digital-delivery'),
                        'code'         => $code['code'],
                        'masked'       => $this->mask_code($code['code']),
                    ];
                },
                $accounts
            ),
            'status'   => $order->get_meta('oh_delivery_status') ?: 'pending',
        ];

        $this->notifier->send_delivery_email($order, $payload);
    }
}
