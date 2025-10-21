<?php
/**
 * REST controller for customer facing operations (wallet, tickets, pdf).
 *
 * @package OH\DigitalDelivery\REST
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\REST;

use OH\DigitalDelivery\Service\Delivery_Service;
use OH\DigitalDelivery\Service\PDF_Generator;
use OH\DigitalDelivery\Service\Ticket_Service;
use OH\DigitalDelivery\Service\Wallet_Service;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function __;
use function get_current_user_id;
use function is_user_logged_in;

class Customer_Controller extends WP_REST_Controller
{
    private Delivery_Service $delivery_service;

    private Wallet_Service $wallet_service;

    private Ticket_Service $ticket_service;

    private PDF_Generator $pdf_generator;

    public function __construct(
        Delivery_Service $delivery_service,
        Wallet_Service $wallet_service,
        Ticket_Service $ticket_service,
        PDF_Generator $pdf_generator
    ) {
        $this->delivery_service = $delivery_service;
        $this->wallet_service   = $wallet_service;
        $this->ticket_service   = $ticket_service;
        $this->pdf_generator    = $pdf_generator;
        $this->namespace        = 'oh/v1';
        $this->rest_base        = 'customer';
    }

    public function register_routes(): void
    {
        register_rest_route(
            $this->namespace,
            '/wallet/balance',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_wallet_balance'],
                    'permission_callback' => [$this, 'ensure_logged_in'],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/wallet/transactions',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_wallet_transactions'],
                    'permission_callback' => [$this, 'ensure_logged_in'],
                    'args'                => [
                        'page' => [
                            'type'    => 'integer',
                            'default' => 1,
                        ],
                        'per_page' => [
                            'type'    => 'integer',
                            'default' => 20,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/licenses/pdf',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'generate_license_pdf'],
                    'permission_callback' => [$this, 'ensure_logged_in'],
                    'args'                => [
                        'order_id' => [
                            'type'     => 'integer',
                            'required' => true,
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/tickets',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_tickets'],
                    'permission_callback' => [$this, 'ensure_logged_in'],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'create_ticket'],
                    'permission_callback' => [$this, 'ensure_logged_in'],
                    'args'                => [
                        'subject' => [
                            'type'     => 'string',
                            'required' => false,
                        ],
                        'message' => [
                            'type'     => 'string',
                            'required' => true,
                        ],
                        'order_id' => [
                            'type' => 'integer',
                        ],
                        'ticket_id' => [
                            'type' => 'integer',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/tickets/(?P<id>\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'get_ticket'],
                    'permission_callback' => [$this, 'ensure_logged_in'],
                ],
            ]
        );
    }

    public function ensure_logged_in(): bool
    {
        return is_user_logged_in();
    }

    public function get_wallet_balance(): WP_REST_Response
    {
        $user_id = get_current_user_id();

        return new WP_REST_Response([
            'balance' => $this->wallet_service->get_balance($user_id),
        ]);
    }

    public function get_wallet_transactions(WP_REST_Request $request): WP_REST_Response
    {
        $user_id    = get_current_user_id();
        $page       = max(1, (int) $request->get_param('page'));
        $per_page   = max(1, (int) $request->get_param('per_page'));
        $ledger     = $this->wallet_service->get_transactions($user_id, $page, $per_page);

        return new WP_REST_Response($ledger);
    }

    public function generate_license_pdf(WP_REST_Request $request)
    {
        $user_id  = get_current_user_id();
        $order_id = (int) $request->get_param('order_id');

        try {
            $result = $this->pdf_generator->generate_for_order($order_id, $user_id);
        } catch (\Throwable $throwable) {
            return new WP_Error('oh_pdf_error', $throwable->getMessage(), ['status' => 400]);
        }

        return new WP_REST_Response($result);
    }

    public function get_tickets(): WP_REST_Response
    {
        $user_id = get_current_user_id();
        $tickets = $this->ticket_service->get_tickets_for_user($user_id);

        return new WP_REST_Response([
            'items' => $tickets,
        ]);
    }

    public function get_ticket(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $ticket  = $this->ticket_service->get_ticket((int) $request['id'], $user_id);

        if (! $ticket) {
            return new WP_Error('oh_ticket_not_found', __('Talep bulunamadı.', 'oh-digital-delivery'), ['status' => 404]);
        }

        return new WP_REST_Response($ticket);
    }

    public function create_ticket(WP_REST_Request $request)
    {
        $user_id   = get_current_user_id();
        $ticket_id = (int) $request->get_param('ticket_id');
        $message   = (string) $request->get_param('message');

        if ($ticket_id) {
            $ticket = $this->ticket_service->get_ticket($ticket_id, $user_id);
            if (! $ticket) {
                return new WP_Error('oh_ticket_forbidden', __('Bu talebi güncelleme yetkiniz yok.', 'oh-digital-delivery'), ['status' => 403]);
            }

            $this->ticket_service->append_message($ticket_id, $user_id, $message);

            return new WP_REST_Response(['status' => 'updated']);
        }

        $subject = (string) $request->get_param('subject');
        if ('' === trim($subject)) {
            return new WP_Error('oh_ticket_subject', __('Lütfen bir konu başlığı girin.', 'oh-digital-delivery'), ['status' => 400]);
        }

        $order_id = $request->get_param('order_id') ? (int) $request->get_param('order_id') : null;
        $created  = $this->ticket_service->create_ticket($user_id, $subject, $message, $order_id);

        return new WP_REST_Response([
            'status'    => 'created',
            'ticket_id' => $created,
        ], 201);
    }
}
