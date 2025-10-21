<?php
/**
 * Main plugin bootstrap for OH Digital Delivery.
 *
 * @package OH\DigitalDelivery
 */

declare(strict_types=1);

namespace OH\DigitalDelivery;

use OH\DigitalDelivery\Admin\Admin;
use OH\DigitalDelivery\Emails\License_Delivered_Email;
use OH\DigitalDelivery\Emails\License_Revoked_Email;
use OH\DigitalDelivery\Emails\Stock_Low_Email;
use OH\DigitalDelivery\Order_Notifier;
use OH\DigitalDelivery\REST\Licenses_Controller;
use OH\DigitalDelivery\Service\Delivery_Service;
use OH\DigitalDelivery\Service\Licenses_Renderer;
use OH\DigitalDelivery\Storage\Codes_Repository;

final class Plugin
{
    private static ?Plugin $instance = null;

    private Delivery_Service $delivery_service;

    private function __construct()
    {
        $this->include_files();

        add_action('init', [$this, 'register_tables']);
        add_action('init', [$this, 'add_account_endpoint']);
        add_action('plugins_loaded', [$this, 'init_services']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('woocommerce_order_status_completed', [$this, 'maybe_fulfil_order'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'handle_refund'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'handle_refund'], 10, 1);
        add_filter('woocommerce_email_classes', [$this, 'register_emails']);
        add_filter('woocommerce_my_account_menu_items', [$this, 'register_account_menu']);
        add_action('woocommerce_account_licenses_endpoint', [$this, 'render_account_endpoint']);

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
        flush_rewrite_rules();
    }

    private function include_files(): void
    {
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-encryption.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-order-notifier.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-delivery-service.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-licenses-renderer.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-codes-repository.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'emails/class-license-delivered-email.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'emails/class-stock-low-email.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'emails/class-license-revoked-email.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'rest/class-licenses-controller.php';
    }

    public function register_tables(): void
    {
        global $wpdb;

        $wpdb->oh_licence_pool = $wpdb->prefix . 'oh_licence_pool';
    }

    public function init_services(): void
    {
        $repository       = new Codes_Repository();
        $order_notifier   = new Order_Notifier();
        $this->delivery_service = new Delivery_Service($repository, $order_notifier);
    }

    public function register_rest_routes(): void
    {
        $controller = new Licenses_Controller($this->get_delivery_service());
        $controller->register_routes();
    }

    public function maybe_fulfil_order(int $order_id): void
    {
        $this->get_delivery_service()->process_order($order_id);
    }

    public function handle_refund(int $order_id): void
    {
        $this->get_delivery_service()->handle_refund($order_id);
    }

    public function register_emails(array $emails): array
    {
        $emails[License_Delivered_Email::class] = new License_Delivered_Email();
        $emails[Stock_Low_Email::class]         = new Stock_Low_Email();
        $emails[License_Revoked_Email::class]   = new License_Revoked_Email();

        return $emails;
    }

    public function add_account_endpoint(): void
    {
        add_rewrite_endpoint('licenses', EP_ROOT | EP_PAGES);
    }

    public function register_account_menu(array $items): array
    {
        $new = [];
        foreach ($items as $key => $label) {
            $new[$key] = $label;
            if ('orders' === $key) {
                $new['licenses'] = __('Lisanslarım', 'oh-digital-delivery');
            }
        }

        if (! isset($new['licenses'])) {
            $new['licenses'] = __('Lisanslarım', 'oh-digital-delivery');
        }

        return $new;
    }

    public function render_account_endpoint(): void
    {
        $renderer = new Licenses_Renderer($this->get_delivery_service());
        $renderer->render_account_view();
    }

    public function get_delivery_service(): Delivery_Service
    {
        if (! isset($this->delivery_service)) {
            $this->init_services();
        }

        return $this->delivery_service;
    }

    public static function create_codes_table(): void
    {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'oh_licence_pool';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            code LONGTEXT NOT NULL,
            status ENUM('free','reserved','used','revoked') NOT NULL DEFAULT 'free',
            used_at DATETIME DEFAULT NULL,
            order_id BIGINT UNSIGNED DEFAULT NULL,
            note TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX product_status (product_id, status),
            INDEX order_lookup (order_id)
        ) ENGINE=InnoDB {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
