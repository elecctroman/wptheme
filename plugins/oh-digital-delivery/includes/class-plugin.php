<?php
/**
 * Main plugin controller for OH Digital Delivery.
 *
 * @package OH\DigitalDelivery
 */

declare(strict_types=1);

namespace OH\DigitalDelivery;

use OH\DigitalDelivery\Admin\Admin;
use wpdb;

final class Plugin
{
    private static ?Plugin $instance = null;

    private function __construct()
    {
        require_once OH_DIGITAL_DELIVERY_PATH . 'admin/class-admin.php';
        add_action('init', [$this, 'register_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('woocommerce_thankyou', [$this, 'handle_order_delivery']);
        Admin::init();
    }

    public static function instance(): Plugin
    {
        if (! static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public static function activate(): void
    {
        self::create_codes_table();
    }

    public function register_tables(): void
    {
        global $wpdb;

        $wpdb->oh_digital_codes = $wpdb->prefix . 'oh_digital_codes';
    }

    public function register_rest_routes(): void
    {
        require_once OH_DIGITAL_DELIVERY_PATH . 'api/class-codes-controller.php';
        $controller = new API\Codes_Controller();
        $controller->register_routes();
    }

    public function handle_order_delivery(int $order_id): void
    {
        do_action('oh_digital_delivery_queue', $order_id);
    }

    public static function create_codes_table(): void
    {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'oh_digital_codes';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            code_value TEXT NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'available',
            delivered_to BIGINT UNSIGNED DEFAULT NULL,
            delivered_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
