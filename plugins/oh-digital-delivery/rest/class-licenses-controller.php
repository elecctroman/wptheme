<?php
/**
 * REST controller that exposes license delivery operations.
 *
 * @package OH\DigitalDelivery\REST
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\REST;

use OH\DigitalDelivery\Service\Delivery_Service;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function __;
use function wc_get_order;
use function wc_get_product;

class Licenses_Controller extends WP_REST_Controller
{
    private Delivery_Service $delivery_service;

    public function __construct(Delivery_Service $delivery_service)
    {
        $this->delivery_service = $delivery_service;
        $this->namespace        = 'oh/v1';
        $this->rest_base        = 'licenses';
    }

    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_items'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<order_id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_item'],
                    'permission_callback' => [$this, 'permissions_check_order'],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/revoke',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'revoke_code'],
                    'permission_callback' => [$this, 'permissions_manage'],
                    'args'                => [
                        'code_id' => [
                            'type'     => 'integer',
                            'required' => true,
                        ],
                        'status'  => [
                            'type'    => 'string',
                            'default' => 'revoked',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/upload',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'upload_codes'],
                    'permission_callback' => [$this, 'permissions_manage'],
                    'args'                => [
                        'product_id' => [
                            'type'     => 'integer',
                            'required' => true,
                        ],
                        'csv'        => [
                            'type'     => 'string',
                            'required' => true,
                        ],
                        'note'       => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]
        );
    }

    public function permissions_check(): bool
    {
        return is_user_logged_in();
    }

    public function permissions_check_order(WP_REST_Request $request)
    {
        $order_id = (int) $request['order_id'];
        $order    = wc_get_order($order_id);

        if (! $order) {
            return new WP_Error('oh_license_not_found', __('Sipariş bulunamadı.', 'oh-digital-delivery'), ['status' => 404]);
        }

        if (current_user_can('manage_woocommerce')) {
            return true;
        }

        if (! is_user_logged_in()) {
            return new WP_Error('oh_license_forbidden', __('Giriş yapmanız gerekiyor.', 'oh-digital-delivery'), ['status' => 401]);
        }

        $user_id = (int) $order->get_user_id();
        if (0 === $user_id) {
            return new WP_Error('oh_license_guest', __('Misafir siparişlerine yalnızca yöneticiler erişebilir.', 'oh-digital-delivery'), ['status' => 403]);
        }

        return $user_id === get_current_user_id();
    }

    public function permissions_manage(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        $licenses = $this->delivery_service->get_licenses_for_user(get_current_user_id());

        $data = array_map(
            function ($license) {
                $product = wc_get_product($license['product_id']);
                $order   = wc_get_order($license['order_id']);

                return [
                    'id'            => $license['id'],
                    'order_id'      => $license['order_id'],
                    'product_id'    => $license['product_id'],
                    'product_name'  => $product ? $product->get_name() : __('Silinmiş ürün', 'oh-digital-delivery'),
                    'product_image' => $product ? $product->get_image('thumbnail') : '',
                    'masked_code'   => $this->delivery_service->mask_code($license['code']),
                    'status'        => $license['status'],
                    'used_at'       => $license['used_at'],
                    'purchased_at'  => $order ? $order->get_date_created()->date_i18n('Y-m-d H:i') : null,
                    'order_status'  => $order ? $order->get_status() : null,
                ];
            },
            $licenses
        );

        return new WP_REST_Response(['data' => $data]);
    }

    public function get_item(WP_REST_Request $request): WP_REST_Response
    {
        $order_id = (int) $request['order_id'];
        $licenses = $this->delivery_service->get_licenses_for_order($order_id);

        $data = array_map(
            function ($license) {
                $product = wc_get_product($license['product_id']);

                return [
                    'id'           => $license['id'],
                    'order_id'     => $license['order_id'],
                    'product_name' => $product ? $product->get_name() : __('Silinmiş ürün', 'oh-digital-delivery'),
                    'code'         => $license['code'],
                    'masked_code'  => $this->delivery_service->mask_code($license['code']),
                    'status'       => $license['status'],
                    'used_at'      => $license['used_at'],
                    'product_id'   => $license['product_id'],
                ];
            },
            $licenses
        );

        return new WP_REST_Response(['data' => $data]);
    }

    public function revoke_code(WP_REST_Request $request)
    {
        $code_id = (int) $request['code_id'];
        $status  = $request['status'] ?: 'revoked';

        try {
            $this->delivery_service->update_code_status($code_id, $status);
        } catch (\Throwable $exception) {
            return new WP_Error('oh_license_status', $exception->getMessage(), ['status' => 400]);
        }

        return new WP_REST_Response([
            'status'  => 'ok',
            'message' => __('Kod durumu güncellendi.', 'oh-digital-delivery'),
        ]);
    }

    public function upload_codes(WP_REST_Request $request)
    {
        $product_id = (int) $request['product_id'];
        $csv        = $request['csv'];
        $note       = $request['note'] ?? null;

        $count = $this->delivery_service->import_codes_from_csv($csv, $product_id, $note);

        return new WP_REST_Response([
            'status'  => 'ok',
            'imported'=> $count,
        ], 201);
    }
}
