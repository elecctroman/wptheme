<?php
/**
 * Outputs the customer ticket inbox inside My Account.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use function esc_url_raw;
use function get_current_user_id;
use function rest_url;
use function wc_get_template;
use function wp_create_nonce;

class Account_Tickets_Renderer
{
    private Ticket_Service $service;

    public function __construct(Ticket_Service $service)
    {
        $this->service = $service;
    }

    public function render(): void
    {
        $context = [
            'list_endpoint' => esc_url_raw(rest_url('oh/v1/tickets')),
            'nonce'         => wp_create_nonce('wp_rest'),
            'current_user'  => get_current_user_id(),
        ];

        wc_get_template('myaccount/tickets.php', $context, '', OH_DIGITAL_DELIVERY_PATH . 'templates/');
    }
}
