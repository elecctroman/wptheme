<?php
/**
 * Outputs the "My Licenses" account endpoint with AJAX powered UI.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use function esc_html__;
use function esc_url_raw;
use function is_user_logged_in;
use function rest_url;
use function wc_get_template;
use function wp_create_nonce;

class Licenses_Renderer
{
    private Delivery_Service $delivery_service;

    public function __construct(Delivery_Service $delivery_service)
    {
        $this->delivery_service = $delivery_service;
    }

    public function render_account_view(): void
    {
        if (! is_user_logged_in()) {
            echo '<p>' . esc_html__('Bu alanı görmek için giriş yapmalısınız.', 'oh-digital-delivery') . '</p>';

            return;
        }

        $context = [
            'list_endpoint'   => esc_url_raw(rest_url('oh/v1/licenses')),
            'detail_endpoint' => esc_url_raw(rest_url('oh/v1/licenses')),
            'pdf_endpoint'    => esc_url_raw(rest_url('oh/v1/licenses/pdf')),
            'nonce'           => wp_create_nonce('wp_rest'),
        ];

        wc_get_template('myaccount/licenses.php', $context, '', OH_DIGITAL_DELIVERY_PATH . 'templates/');
    }
}
