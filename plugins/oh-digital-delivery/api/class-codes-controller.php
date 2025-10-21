<?php
/**
 * REST controller placeholder for managing digital codes.
 *
 * @package OH\DigitalDelivery\API
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Codes_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'oh-digital/v1';
        $this->rest_base = 'codes';
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
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create_item'],
                    'permission_callback' => [$this, 'permissions_check'],
                ],
            ]
        );
    }

    public function permissions_check(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public function get_items(WP_REST_Request $request): WP_REST_Response
    {
        $codes = apply_filters('oh_digital_delivery_list_codes', []);

        return new WP_REST_Response([
            'data' => $codes,
        ]);
    }

    public function create_item(WP_REST_Request $request): WP_REST_Response
    {
        do_action('oh_digital_delivery_import_codes', $request->get_params());

        return new WP_REST_Response([
            'status'  => 'accepted',
            'message' => __('Kodlar işlenmek üzere kuyruğa alındı.', 'oh-digital-delivery'),
        ], 202);
    }
}
