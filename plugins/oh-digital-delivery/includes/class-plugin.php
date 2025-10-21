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
use OH\DigitalDelivery\REST\Admin_Controller;
use OH\DigitalDelivery\REST\Customer_Controller;
use OH\DigitalDelivery\REST\Licenses_Controller;
use OH\DigitalDelivery\Service\Account_Tickets_Renderer;
use OH\DigitalDelivery\Service\Account_Wallet_Renderer;
use OH\DigitalDelivery\Service\Analytics_Service;
use OH\DigitalDelivery\Service\Delivery_Service;
use OH\DigitalDelivery\Service\Licenses_Renderer;
use OH\DigitalDelivery\Service\PDF_Generator;
use OH\DigitalDelivery\Service\Ticket_Service;
use OH\DigitalDelivery\Service\Wallet_Service;
use OH\DigitalDelivery\Storage\Codes_Repository;
use function esc_attr;
use function esc_html__;
use function esc_url;
use function get_current_user_id;
use function is_admin;
use function is_checkout;
use function number_format_i18n;
use function wc_get_account_endpoint_url;
use function wc_get_page_permalink;
use function wc_price;
use function wp_kses_post;

final class Plugin
{
    private static ?Plugin $instance = null;

    private Delivery_Service $delivery_service;

    private Analytics_Service $analytics_service;

    private Codes_Repository $repository;

    private Wallet_Service $wallet_service;

    private Ticket_Service $ticket_service;

    private PDF_Generator $pdf_generator;

    private bool $wallet_notice_required = false;

    private function __construct()
    {
        $this->include_files();

        add_action('init', [$this, 'register_tables']);
        add_action('init', [$this, 'register_account_endpoints']);
        add_action('plugins_loaded', [$this, 'init_services']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('woocommerce_order_status_completed', [$this, 'maybe_fulfil_order'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'handle_refund'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'handle_refund'], 10, 1);
        add_filter('woocommerce_email_classes', [$this, 'register_emails']);
        add_filter('woocommerce_my_account_menu_items', [$this, 'register_account_menu']);
        add_action('woocommerce_account_licenses_endpoint', [$this, 'render_licenses_endpoint']);
        add_action('woocommerce_account_wallet_endpoint', [$this, 'render_wallet_endpoint']);
        add_action('woocommerce_account_tickets_endpoint', [$this, 'render_tickets_endpoint']);
        add_action('oh_digital_check_stock_levels', [$this, 'run_stock_audit']);
        add_filter('woocommerce_available_payment_gateways', [$this, 'guard_wallet_gateway'], 20);
        add_action('woocommerce_review_order_before_payment', [$this, 'render_wallet_shortcut'], 5);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_wallet_payment'], 10, 2);

        if (! wp_next_scheduled('oh_digital_check_stock_levels')) {
            wp_schedule_event(time(), 'daily', 'oh_digital_check_stock_levels');
        }

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
        self::instance()->register_account_endpoints();
        flush_rewrite_rules();

        if (! wp_next_scheduled('oh_digital_check_stock_levels')) {
            wp_schedule_event(time(), 'daily', 'oh_digital_check_stock_levels');
        }
    }

    private function include_files(): void
    {
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-encryption.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-order-notifier.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-delivery-service.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-licenses-renderer.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-wallet-renderer.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-wallet-service.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-ticket-service.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-tickets-renderer.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-pdf-generator.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-codes-repository.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'includes/class-analytics-service.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'emails/class-license-delivered-email.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'emails/class-stock-low-email.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'emails/class-license-revoked-email.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'rest/class-licenses-controller.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'rest/class-admin-controller.php';
        require_once OH_DIGITAL_DELIVERY_PATH . 'rest/class-customer-controller.php';
    }

    public function register_tables(): void
    {
        global $wpdb;

        $wpdb->oh_licence_pool = $wpdb->prefix . 'oh_licence_pool';
    }

    public function init_services(): void
    {
        $this->repository        = new Codes_Repository();
        $order_notifier          = new Order_Notifier();
        $this->delivery_service  = new Delivery_Service($this->repository, $order_notifier);
        $this->analytics_service = new Analytics_Service($this->repository);
        $this->wallet_service    = new Wallet_Service();
        $this->ticket_service    = new Ticket_Service();
        $this->pdf_generator     = new PDF_Generator($this->delivery_service);

        add_action('init', [$this->ticket_service, 'register_post_type']);
    }

    public function register_rest_routes(): void
    {
        $controller = new Licenses_Controller($this->get_delivery_service());
        $controller->register_routes();

        $admin_controller = new Admin_Controller($this->get_analytics_service(), $this->get_delivery_service());
        $admin_controller->register_routes();

        $customer_controller = new Customer_Controller(
            $this->get_delivery_service(),
            $this->get_wallet_service(),
            $this->get_ticket_service(),
            $this->get_pdf_generator()
        );
        $customer_controller->register_routes();
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

    public function register_account_endpoints(): void
    {
        add_rewrite_endpoint('licenses', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('tickets', EP_ROOT | EP_PAGES);
    }

    public function register_account_menu(array $items): array
    {
        unset($items['dashboard']);

        $ordered = [];
        $ordered['edit-account'] = __('Profil', 'oh-digital-delivery');
        $ordered['orders']       = __('Siparişlerim', 'oh-digital-delivery');
        $ordered['licenses']     = __('Lisanslarım', 'oh-digital-delivery');
        $ordered['wallet']       = __('Bakiye', 'oh-digital-delivery');

        if (post_type_exists(Ticket_Service::POST_TYPE)) {
            $ordered['tickets'] = __('Destek Talepleri', 'oh-digital-delivery');
        }

        if (isset($items['downloads'])) {
            $ordered['downloads'] = $items['downloads'];
        }

        if (isset($items['subscriptions'])) {
            $ordered['subscriptions'] = $items['subscriptions'];
        }

        $ordered['customer-logout'] = $items['customer-logout'] ?? __('Çıkış', 'oh-digital-delivery');

        return $ordered;
    }

    public function guard_wallet_gateway(array $gateways): array
    {
        if (is_admin() || ! function_exists('is_checkout') || ! is_checkout()) {
            return $gateways;
        }

        if (! isset($gateways['terra_wallet'])) {
            return $gateways;
        }

        if (! function_exists('WC') || ! \WC()->cart) {
            return $gateways;
        }

        $total   = (float) \WC()->cart->total;
        $balance = $this->get_wallet_service()->get_balance(get_current_user_id());

        if ($total > 0 && $balance + 0.01 < $total) {
            unset($gateways['terra_wallet']);
            $this->wallet_notice_required = true;
        }

        return $gateways;
    }

    public function render_wallet_shortcut(): void
    {
        if (! function_exists('is_checkout') || ! is_checkout() || ! function_exists('WC') || ! \WC()->cart) {
            return;
        }

        $total   = (float) \WC()->cart->total;
        $balance = $this->get_wallet_service()->get_balance(get_current_user_id());

        if ($total <= 0) {
            return;
        }

        if (! $this->wallet_notice_required && $balance + 0.01 >= $total) {
            return;
        }

        $difference = max(0, $total - $balance);
        $wallet_url = function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('wallet') : wc_get_page_permalink('myaccount');

        echo '<div class="oh-checkout-wallet-notice" data-wallet-balance="' . esc_attr(number_format_i18n($balance, 2)) . '" data-order-total="' . esc_attr(number_format_i18n($total, 2)) . '">';
        echo '<strong>' . esc_html__('Terra Wallet bakiyeniz yetersiz görünüyor.', 'oh-digital-delivery') . '</strong>';
        echo '<p>' . sprintf(esc_html__('Siparişi tamamlamak için %s daha yükleyin.', 'oh-digital-delivery'), wp_kses_post(wc_price($difference))) . '</p>';
        echo '<a class="button" href="' . esc_url($wallet_url) . '">' . esc_html__('Bakiye Yükle', 'oh-digital-delivery') . '</a>';
        echo '</div>';
    }

    public function validate_wallet_payment(array $data, \WP_Error $errors): void
    {
        if (empty($data['payment_method']) || 'terra_wallet' !== $data['payment_method']) {
            return;
        }

        if (! function_exists('WC') || ! \WC()->cart) {
            return;
        }

        $total   = (float) \WC()->cart->total;
        $balance = $this->get_wallet_service()->get_balance(get_current_user_id());

        if ($balance + 0.01 < $total) {
            $errors->add('terra_wallet_insufficient', esc_html__('Terra Wallet bakiyeniz bu siparişi karşılamıyor.', 'oh-digital-delivery'));
        }
    }

    public function render_licenses_endpoint(): void
    {
        $renderer = new Licenses_Renderer($this->get_delivery_service());
        $renderer->render_account_view();
    }

    public function render_wallet_endpoint(): void
    {
        $renderer = new Account_Wallet_Renderer($this->get_wallet_service());
        $renderer->render();
    }

    public function render_tickets_endpoint(): void
    {
        $renderer = new Account_Tickets_Renderer($this->get_ticket_service());
        $renderer->render();
    }

    public function get_delivery_service(): Delivery_Service
    {
        if (! isset($this->delivery_service)) {
            $this->init_services();
        }

        return $this->delivery_service;
    }

    public function get_analytics_service(): Analytics_Service
    {
        if (! isset($this->analytics_service)) {
            $this->init_services();
        }

        return $this->analytics_service;
    }

    public function get_repository(): Codes_Repository
    {
        if (! isset($this->repository)) {
            $this->init_services();
        }

        return $this->repository;
    }

    public function get_wallet_service(): Wallet_Service
    {
        if (! isset($this->wallet_service)) {
            $this->init_services();
        }

        return $this->wallet_service;
    }

    public function get_ticket_service(): Ticket_Service
    {
        if (! isset($this->ticket_service)) {
            $this->init_services();
        }

        return $this->ticket_service;
    }

    public function get_pdf_generator(): PDF_Generator
    {
        if (! isset($this->pdf_generator)) {
            $this->init_services();
        }

        return $this->pdf_generator;
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

    public function run_stock_audit(): void
    {
        $this->get_delivery_service()->check_stock_levels();
    }

    public static function deactivate(): void
    {
        $timestamp = wp_next_scheduled('oh_digital_check_stock_levels');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'oh_digital_check_stock_levels');
        }
    }
}
