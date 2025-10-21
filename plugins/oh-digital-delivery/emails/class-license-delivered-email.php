<?php
/**
 * Customer email sent after digital licenses are assigned.
 *
 * @package OH\DigitalDelivery\Emails
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Emails;

use WC_Email;

class License_Delivered_Email extends WC_Email
{
    protected array $payload = [];

    public function __construct()
    {
        $this->id             = 'oh_license_delivered';
        $this->title          = __('Dijital Lisans Teslim Edildi', 'oh-digital-delivery');
        $this->description    = __('Sipariş tamamlandığında müşteriye gönderilen lisans teslim e-postası.', 'oh-digital-delivery');
        $this->customer_email = true;

        $this->template_html = 'emails/license-delivered.php';
        $this->template_plain = 'emails/plain/license-delivered.php';
        $this->placeholders = [
            '{order_date}'   => '',
            '{order_number}' => '',
        ];

        parent::__construct();
    }

    public function trigger($order_id, $payload = []): void
    {
        $this->setup_locale();

        $this->object  = $order_id ? wc_get_order($order_id) : null;
        $this->payload = is_array($payload) ? $payload : [];

        if (! $this->object || ! $this->is_enabled()) {
            $this->restore_locale();

            return;
        }

        $this->recipient = $this->object->get_billing_email();
        $this->placeholders['{order_date}']   = wc_format_datetime($this->object->get_date_created());
        $this->placeholders['{order_number}'] = $this->object->get_order_number();

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        $this->restore_locale();
    }

    public function get_default_subject(): string
    {
        return __('Dijital ürününüz hazır: {order_date}', 'oh-digital-delivery');
    }

    public function get_default_heading(): string
    {
        return __('Teslimat başarıyla tamamlandı', 'oh-digital-delivery');
    }

    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'order'    => $this->object,
                'email'    => $this,
                'payload'  => $this->payload,
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
                'order'   => $this->object,
                'email'   => $this,
                'payload' => $this->payload,
            ],
            '',
            OH_DIGITAL_DELIVERY_PATH . 'templates/'
        );
    }
}
