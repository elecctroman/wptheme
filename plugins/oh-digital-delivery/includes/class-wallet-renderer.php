<?php
/**
 * Renders the Terra Wallet account endpoint.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use function esc_url_raw;
use function rest_url;
use function wc_get_template;
use function wp_create_nonce;

class Account_Wallet_Renderer
{
    private Wallet_Service $wallet_service;

    public function __construct(Wallet_Service $wallet_service)
    {
        $this->wallet_service = $wallet_service;
    }

    public function render(): void
    {
        $context = [
            'gateways'              => $this->wallet_service->get_gateway_options(),
            'balance_endpoint'      => esc_url_raw(rest_url('oh/v1/wallet/balance')),
            'transactions_endpoint' => esc_url_raw(rest_url('oh/v1/wallet/transactions')),
            'nonce'                 => wp_create_nonce('wp_rest'),
        ];

        wc_get_template('myaccount/wallet.php', $context, '', OH_DIGITAL_DELIVERY_PATH . 'templates/');
    }
}
