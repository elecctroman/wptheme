<?php
/**
 * Wallet service integrating Terra Wallet and fallback ledgers.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use DateTimeImmutable;
use function __;
class Wallet_Service
{
    public const META_BALANCE      = '_oh_wallet_balance';
    public const META_TRANSACTIONS = '_oh_wallet_transactions';

    public function get_balance(int $user_id): float
    {
        if ($user_id <= 0) {
            return 0.0;
        }

        if (function_exists('woo_wallet')) {
            $wallet = woo_wallet();
            if ($wallet && method_exists($wallet, 'get_wallet_balance')) {
                return (float) $wallet->get_wallet_balance($user_id);
            }
        }

        $balance = apply_filters('oh_wallet_balance', null, $user_id);
        if (null !== $balance) {
            return (float) $balance;
        }

        return (float) get_user_meta($user_id, self::META_BALANCE, true);
    }

    public function adjust_balance(int $user_id, float $amount, string $gateway = 'manual', string $note = ''): float
    {
        if ($user_id <= 0) {
            return 0.0;
        }

        if (function_exists('woo_wallet')) {
            $wallet = woo_wallet();
            if ($wallet && method_exists($wallet, 'credit')) {
                $wallet->credit($user_id, $amount, $note ?: __('Bakiye hareketi', 'oh-digital-delivery'));

                return (float) $wallet->get_wallet_balance($user_id);
            }
        }

        $current = $this->get_balance($user_id);
        $new     = $current + $amount;
        update_user_meta($user_id, self::META_BALANCE, $new);

        $this->log_transaction($user_id, $amount, $gateway, $note, $new);

        return $new;
    }

    public function get_transactions(int $user_id, int $page = 1, int $per_page = 20): array
    {
        if ($user_id <= 0) {
            return ['items' => [], 'total' => 0];
        }

        $items = [];
        if (function_exists('woo_wallet')) {
            $wallet = woo_wallet();
            if ($wallet && method_exists($wallet, 'wallet')) {
                $transactions = $wallet->wallet->get_transactions($user_id, $page, $per_page);
                if (is_array($transactions)) {
                    foreach ($transactions as $transaction) {
                        $items[] = [
                            'id'        => (int) ($transaction['transaction_id'] ?? 0),
                            'amount'    => (float) ($transaction['amount'] ?? 0),
                            'balance'   => isset($transaction['balance']) ? (float) $transaction['balance'] : null,
                            'type'      => $transaction['type'] ?? '',
                            'created'   => $transaction['date'] ?? '',
                            'reference' => $transaction['details'] ?? '',
                        ];
                    }

                    return [
                        'items' => $items,
                        'total' => (int) ($wallet->wallet->get_transactions_count($user_id) ?? count($items)),
                    ];
                }
            }
        }

        $ledger = get_user_meta($user_id, self::META_TRANSACTIONS, true);
        if (! is_array($ledger)) {
            $ledger = [];
        }

        $ledger = array_reverse($ledger);
        $offset = max(0, ($page - 1) * $per_page);
        $slice  = array_slice($ledger, $offset, $per_page);

        return [
            'items' => array_values($slice),
            'total' => count($ledger),
        ];
    }

    public function get_gateway_options(): array
    {
        $options = [
            'paytr'    => 'PayTR',
            'iyzico'   => 'İyzico',
            'shopier'  => 'Shopier',
            'paywant'  => 'Paywant',
            'crypto'   => 'CoinPayments / NOWPayments',
        ];

        return apply_filters('oh_wallet_gateways', $options);
    }

    private function log_transaction(int $user_id, float $amount, string $gateway, string $note, float $balance): void
    {
        $ledger = get_user_meta($user_id, self::META_TRANSACTIONS, true);
        if (! is_array($ledger)) {
            $ledger = [];
        }

        $ledger[] = [
            'id'        => uniqid('txn_', true),
            'amount'    => $amount,
            'balance'   => $balance,
            'gateway'   => $gateway,
            'note'      => $note,
            'created'   => (new DateTimeImmutable('now', wp_timezone()))->format('Y-m-d H:i:s'),
        ];

        update_user_meta($user_id, self::META_TRANSACTIONS, $ledger);
    }
}
