<?php
/**
 * Notifies customers when licenses tied to an order are revoked.
 *
 * @package OH\DigitalDelivery\Emails
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Emails;

use WC_Email;

class License_Revoked_Email extends WC_Email
{
    protected array $codes = [];

    public function __construct()
    {
        $this->id             = 'oh_license_revoked';
        $this->title          = __('Lisans İptal Bildirimi', 'oh-digital-delivery');
        $this->description    = __('Sipariş iade edildiğinde müşteriye gönderilen lisans iptal e-postası.', 'oh-digital-delivery');
        $this->customer_email = true;

        $this->template_html  = 'emails/license-revoked.php';
        $this->template_plain = 'emails/plain/license-revoked.php';
        $this->placeholders   = [
            '{order_number}' => '',
        ];

        parent::__construct();
    }

    public function trigger($order_id, $codes = []): void
    {
        $this->setup_locale();

        $this->object = $order_id ? wc_get_order($order_id) : null;
        $this->codes  = is_array($codes) ? $codes : [];

        if (! $this->object || ! $this->is_enabled()) {
            $this->restore_locale();

            return;
        }

        $this->recipient = $this->object->get_billing_email();
        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        $this->restore_locale();
    }

    public function get_default_subject(): string
    {
        return __('{order_number} numaralı siparişte lisans iptali', 'oh-digital-delivery');
    }

    public function get_default_heading(): string
    {
        return __('Lisansınız iptal edildi', 'oh-digital-delivery');
    }

    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'order' => $this->object,
                'email' => $this,
                'codes' => $this->codes,
            ],
            '',
            OH_DIGITAL_DELIVERY_PATH . 'templates/'
        );
    }

    public function get_content_plain(): string
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'order' => $this->object,
                'email' => $this,
                'codes' => $this->codes,
            ],
            '',
            OH_DIGITAL_DELIVERY_PATH . 'templates/'
        );
    }
}
