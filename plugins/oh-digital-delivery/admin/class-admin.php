<?php
/**
 * Admin UI bootstrap for OH Digital Delivery.
 *
 * @package OH\DigitalDelivery\Admin
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Admin;

class Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [static::class, 'register_menu']);
    }

    public static function register_menu(): void
    {
        add_menu_page(
            __('Dijital Teslimat', 'oh-digital-delivery'),
            __('Dijital Teslimat', 'oh-digital-delivery'),
            'manage_woocommerce',
            'oh-digital-delivery',
            [static::class, 'render_page'],
            'dashicons-cloud-upload'
        );
    }

    public static function render_page(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('Kod Havuzu', 'oh-digital-delivery') . '</h1>';
        echo '<p>' . esc_html__('Kod importu ve teslimat kuyruk yönetimi burada yapılandırılacak.', 'oh-digital-delivery') . '</p></div>';
    }
}
