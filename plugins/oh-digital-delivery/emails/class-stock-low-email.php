<?php
/**
 * Admin alert when license stock is running low.
 *
 * @package OH\DigitalDelivery\Emails
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Emails;

use WC_Email;

class Stock_Low_Email extends WC_Email
{
    protected int $product_id = 0;

    protected int $remaining = 0;

    public function __construct()
    {
        $this->id          = 'oh_license_stock_low';
        $this->title       = __('Lisans Stoğu Kritik Seviyede', 'oh-digital-delivery');
        $this->description = __('Lisans havuzunda stok azaldığında yöneticiyi bilgilendirir.', 'oh-digital-delivery');

        $this->template_html  = 'emails/stock-low.php';
        $this->template_plain = 'emails/plain/stock-low.php';
        $this->placeholders   = [
            '{product_name}' => '',
        ];

        parent::__construct();

        $this->recipient = get_option('admin_email');
    }

    public function trigger($product_id, $remaining = 0): void
    {
        $this->setup_locale();

        $this->product_id = (int) $product_id;
        $this->remaining  = (int) $remaining;
        $this->placeholders['{product_name}'] = $this->get_product_name();

        if (! $this->is_enabled()) {
            $this->restore_locale();

            return;
        }

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        $this->restore_locale();
    }

    public function get_default_subject(): string
    {
        return __('{product_name} lisans stoğu kritik seviyede', 'oh-digital-delivery');
    }

    public function get_default_heading(): string
    {
        return __('Lisans havuzunda düşük stok uyarısı', 'oh-digital-delivery');
    }

    public function get_content_html(): string
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'email'      => $this,
                'product_id' => $this->product_id,
                'remaining'  => $this->remaining,
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
                'email'      => $this,
                'product_id' => $this->product_id,
                'remaining'  => $this->remaining,
            ],
            '',
            OH_DIGITAL_DELIVERY_PATH . 'templates/'
        );
    }

    public function get_product_name(): string
    {
        $product = wc_get_product($this->product_id);

        return $product ? $product->get_name() : __('Bilinmeyen ürün', 'oh-digital-delivery');
    }
}
