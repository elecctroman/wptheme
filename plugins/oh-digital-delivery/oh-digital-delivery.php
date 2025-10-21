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

if (! defined('ABSPATH')) {
    exit;
}

define('OH_DIGITAL_DELIVERY_PATH', \plugin_dir_path(__FILE__));
define('OH_DIGITAL_DELIVERY_VERSION', '0.1.0');

require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-plugin.php';

Plugin::instance();

\register_activation_hook(__FILE__, [Plugin::class, 'activate']);
\register_deactivation_hook(__FILE__, [Plugin::class, 'deactivate']);
