<?php
/**
 * AJAX/REST handlers for asynchronous operations.
 *
 * @package OHTheme
 */

declare(strict_types=1);

namespace OHTheme;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Ajax
{
    public static function init(): void
    {
        add_action('rest_api_init', [static::class, 'register_routes']);
    }

    public static function register_routes(): void
    {
        register_rest_route(
            'oh-digital/v1',
            '/wallet/balance',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [static::class, 'get_wallet_balance'],
                'permission_callback' => static fn () => is_user_logged_in(),
            ]
        );

        register_rest_route(
            'oh-digital/v1',
            '/orders/instant-delivery',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [static::class, 'trigger_instant_delivery'],
                'permission_callback' => [static::class, 'can_trigger_delivery'],
                'args'                => [
                    'order_id' => [
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    public static function get_wallet_balance(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = get_current_user_id();

        if (! $user_id) {
            return new WP_REST_Response(['balance' => 0], 200);
        }

        $balance = apply_filters('oh_theme_wallet_balance', 0, $user_id);

        return new WP_REST_Response([
            'balance' => (float) $balance,
        ]);
    }

    public static function trigger_instant_delivery(WP_REST_Request $request): WP_REST_Response
    {
        $order_id = (int) $request->get_param('order_id');

        do_action('oh_theme_trigger_delivery', $order_id, get_current_user_id());

        return new WP_REST_Response([
            'status'  => 'queued',
            'message' => __('Teslimat işleme alındı.', THEME_TEXT_DOMAIN),
        ]);
    }

    public static function can_trigger_delivery(): bool
    {
        return current_user_can('manage_woocommerce') || current_user_can('edit_shop_orders');
    }
}
