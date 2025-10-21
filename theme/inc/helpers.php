<?php
/**
 * Helper functions shared across the theme.
 *
 * @package OHTheme
 */

declare(strict_types=1);

namespace OHTheme;

if (! defined('ABSPATH')) {
    exit;
}

function get_theme_palette(): array
{
    $palette = get_option('oh_theme_palette');
    if (! is_array($palette)) {
        return [
            'primary'   => '#0f172a',
            'secondary' => '#22d3ee',
            'accent'    => '#fbbf24',
            'muted'     => '#1e293b',
        ];
    }

    return $palette;
}

function get_payment_gateways(): array
{
    return apply_filters(
        'oh_theme_payment_gateways',
        [
            'paytr'         => __('PayTR', THEME_TEXT_DOMAIN),
            'iyzico'        => __('İyzico', THEME_TEXT_DOMAIN),
            'shopier'       => __('Shopier', THEME_TEXT_DOMAIN),
            'paywant'       => __('Paywant', THEME_TEXT_DOMAIN),
            'coinpayments'  => __('CoinPayments', THEME_TEXT_DOMAIN),
            'nowpayments'   => __('NOWPayments', THEME_TEXT_DOMAIN),
            'terra_wallet'  => __('Terra Wallet', THEME_TEXT_DOMAIN),
        ]
    );
}

function render_gateway_badges(): string
{
    $gateways = get_payment_gateways();

    $badges = array_map(
        static fn ($label) => sprintf('<span class="oh-gateway-badge">%s</span>', esc_html($label)),
        $gateways
    );

    return implode('', $badges);
}
