<?php
/**
 * REST endpoints for admin level analytics and order tooling.
 *
 * @package OH\DigitalDelivery\REST
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\REST;

use DateTimeImmutable;
use OH\DigitalDelivery\Service\Analytics_Service;
use OH\DigitalDelivery\Service\Delivery_Service;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function __;
use function current_user_can;
use function wp_timezone;

class Admin_Controller extends WP_REST_Controller
{
    private Analytics_Service $analytics_service;

    private Delivery_Service $delivery_service;

    public function __construct(Analytics_Service $analytics_service, Delivery_Service $delivery_service)
    {
        $this->analytics_service = $analytics_service;
        $this->delivery_service  = $delivery_service;
        $this->namespace         = 'oh/v1';
        $this->rest_base         = 'admin';
    }

    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/stats',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_stats'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => [
                        'range' => [
                            'type'    => 'integer',
                            'minimum' => 1,
                        ],
                        'start' => [
                            'type' => 'string',
                        ],
                        'end'   => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/orders',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_orders'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => [
                        'page'           => ['type' => 'integer', 'minimum' => 1],
                        'per_page'       => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                        'status'         => ['type' => 'string'],
                        'product_id'     => ['type' => 'integer'],
                        'payment_method' => ['type' => 'string'],
                        'date_start'     => ['type' => 'string'],
                        'date_end'       => ['type' => 'string'],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/top-products',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_top_products'],
                    'permission_callback' => [$this, 'permissions_check'],
                    'args'                => [
                        'days' => ['type' => 'integer', 'minimum' => 1],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/orders/(?P<order_id>\d+)/resend',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'resend_delivery'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/orders/(?P<order_id>\d+)/cancel',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'cancel_delivery'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
            ]
        );
    }

    public function permissions_check(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function get_stats(WP_REST_Request $request): WP_REST_Response
    {
        $start = $request->get_param('start');
        $end   = $request->get_param('end');
        $range = (int) $request->get_param('range');

        $range_start = null;
        $range_end   = null;

        if ($start && $end) {
            try {
                $timezone    = wp_timezone();
                $range_start = new DateTimeImmutable($start, $timezone);
                $range_end   = (new DateTimeImmutable($end, $timezone))->setTime(23, 59, 59);
            } catch (\Exception $exception) {
                return new WP_REST_Response(
                    new WP_Error('oh_invalid_range', __('Geçersiz tarih aralığı.', 'oh-digital-delivery')),
                    400
                );
            }
        } elseif ($range > 0) {
            $timezone    = wp_timezone();
            $now         = new DateTimeImmutable('now', $timezone);
            $range_end   = $now->setTime(23, 59, 59);
            $range_start = $range_end->modify(sprintf('-%d days', $range - 1))->setTime(0, 0);
        }

        $payload = $this->analytics_service->get_dashboard_payload($range_start, $range_end);

        return new WP_REST_Response(['data' => $payload]);
    }

    public function get_orders(WP_REST_Request $request): WP_REST_Response
    {
        $orders = $this->analytics_service->get_orders($request->get_params());

        return new WP_REST_Response(['data' => $orders]);
    }

    public function get_top_products(WP_REST_Request $request): WP_REST_Response
    {
        $days = (int) $request->get_param('days') ?: 30;
        $data = $this->analytics_service->get_top_products($days);

        return new WP_REST_Response(['data' => $data]);
    }

    public function resend_delivery(WP_REST_Request $request): WP_REST_Response
    {
        $order_id = (int) $request->get_param('order_id');
        if (! $order_id) {
            return new WP_REST_Response(new WP_Error('oh_invalid_order', __('Geçersiz sipariş.', 'oh-digital-delivery')), 400);
        }

        $this->delivery_service->resend_delivery($order_id);

        return new WP_REST_Response(['status' => 'ok']);
    }

    public function cancel_delivery(WP_REST_Request $request): WP_REST_Response
    {
        $order_id = (int) $request->get_param('order_id');
        if (! $order_id) {
            return new WP_REST_Response(new WP_Error('oh_invalid_order', __('Geçersiz sipariş.', 'oh-digital-delivery')), 400);
        }

        $this->delivery_service->cancel_delivery($order_id);

        return new WP_REST_Response(['status' => 'ok']);
    }
}
