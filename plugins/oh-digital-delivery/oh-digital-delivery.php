<?php
/**
 * Plugin Name: OH Digital Delivery
 * Description: Kod havuzu ve dijital teslimat yönetimi için temel eklenti iskeleti.
 * Version: 0.1.0
 * Author: OH Digital
 * Text Domain: oh-digital-delivery
 */

declare(strict_types=1);

namespace OH\DigitalDelivery;

use function add_action;
use function esc_html__;

if (! defined('ABSPATH')) {
    exit;
}

define('OH_DIGITAL_DELIVERY_PATH', \plugin_dir_path(__FILE__));
define('OH_DIGITAL_DELIVERY_VERSION', '0.1.0');

if (! class_exists('WooCommerce')) {
    add_action(
        'admin_notices',
        static function (): void {
            echo '<div class="notice notice-error"><p>' .
                esc_html__(
                    'OH Digital Delivery eklentisi WooCommerce olmadan çalışamaz. Lütfen eklentiyi etkinleştirmeden önce WooCommerce\'i aktif edin.',
                    'oh-digital-delivery'
                ) .
                '</p></div>';
        }
    );

    return;
}

require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-plugin.php';

Plugin::instance();

\register_activation_hook(__FILE__, [Plugin::class, 'activate']);
\register_deactivation_hook(__FILE__, [Plugin::class, 'deactivate']);
