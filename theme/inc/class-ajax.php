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
use function absint;
use function esc_html__;
use function get_post;
use function get_term;
use function is_wp_error;
use function setup_postdata;
use function wc_get_products;
use function wc_get_template_part;
use function wp_reset_postdata;

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

        register_rest_route(
            'oh-digital/v1',
            '/catalog',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [static::class, 'get_catalog_products'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'category'  => [
                        'type'              => 'integer',
                        'required'          => false,
                        'sanitize_callback' => 'absint',
                    ],
                    'min_price' => [
                        'type'              => 'number',
                        'required'          => false,
                    ],
                    'max_price' => [
                        'type'              => 'number',
                        'required'          => false,
                    ],
                    'stock'     => [
                        'type'              => 'string',
                        'required'          => false,
                    ],
                    'page'      => [
                        'type'              => 'integer',
                        'required'          => false,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
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

    public static function get_catalog_products(WP_REST_Request $request): WP_REST_Response
    {
        if (! function_exists('wc_get_products')) {
            return new WP_REST_Response([
                'html'      => '',
                'total'     => 0,
                'max_pages' => 0,
            ]);
        }

        $args = [
            'status'   => 'publish',
            'limit'    => 12,
            'paginate' => true,
            'page'     => max(1, (int) $request->get_param('page')),
        ];

        $category = absint($request->get_param('category'));
        if ($category) {
            $term = get_term($category, 'product_cat');
            if ($term && ! is_wp_error($term)) {
                $args['category'] = [$term->slug];
            }
        }

        $min_price = $request->get_param('min_price');
        $max_price = $request->get_param('max_price');

        if (null !== $min_price && '' !== $min_price) {
            $args['min_price'] = max(0, (float) $min_price);
        }

        if (null !== $max_price && '' !== $max_price) {
            $args['max_price'] = max(0, (float) $max_price);
        }

        $stock = $request->get_param('stock');
        if ('instock' === $stock) {
            $args['stock_status'] = 'instock';
        }

        $results  = wc_get_products($args);
        $products = $results['products'] ?? [];
        $html     = '';

        if (! empty($products)) {
            ob_start();
            foreach ($products as $product) {
                $post = get_post($product->get_id());
                if ($post) {
                    setup_postdata($post);
                    wc_get_template_part('content', 'product-card');
                }
            }
            $html = ob_get_clean();
            wp_reset_postdata();
        }

        if ('' === $html) {
            $html = '<p class="oh-empty">' . esc_html__('Seçilen filtrelere ait ürün bulunamadı.', THEME_TEXT_DOMAIN) . '</p>';
        }

        return new WP_REST_Response([
            'html'      => $html,
            'total'     => (int) ($results['total'] ?? count($products)),
            'max_pages' => (int) ($results['max_num_pages'] ?? 1),
        ]);
    }
}
