<?php
/**
 * Admin UI bootstrap for OH Digital Delivery.
 *
 * @package OH\DigitalDelivery\Admin
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Admin;

use OH\DigitalDelivery\Encryption;
use OH\DigitalDelivery\Plugin;
use OH\DigitalDelivery\Storage\Codes_Repository;
use function WC;
use function __;
use function absint;
use function add_query_arg;
use function admin_url;
use function current_user_can;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_url;
use function get_current_screen;
use function get_locale;
use function get_woocommerce_currency;
use function paginate_links;
use function plugins_url;
use function rest_url;
use function sanitize_text_field;
use function selected;
use function wc_get_product;
use function wc_get_products;
use function wp_create_nonce;
use function wp_die;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_get_referer;
use function wp_localize_script;
use function wp_nonce_field;
use function wp_safe_redirect;
use function wp_unslash;

class Admin
{
    private static array $page_hooks = [];

    public static function init(): void
    {
        add_action('admin_menu', [static::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [static::class, 'enqueue_assets']);
        add_action('admin_post_oh_digital_import_codes', [static::class, 'handle_import']);
        add_action('admin_notices', [static::class, 'maybe_render_notices']);
    }

    public static function register_menu(): void
    {
        $parent = add_menu_page(
            __('Dijital Satış', 'oh-digital-delivery'),
            __('Dijital Satış', 'oh-digital-delivery'),
            'manage_woocommerce',
            'oh-digital-sales',
            [static::class, 'render_dashboard'],
            'dashicons-chart-line',
            56
        );

        self::$page_hooks[] = $parent;

        $dashboard = add_submenu_page(
            'oh-digital-sales',
            __('Gösterge Paneli', 'oh-digital-delivery'),
            __('Gösterge Paneli', 'oh-digital-delivery'),
            'manage_woocommerce',
            'oh-digital-sales',
            [static::class, 'render_dashboard']
        );
        self::$page_hooks[] = $dashboard;

        $orders = add_submenu_page(
            'oh-digital-sales',
            __('Siparişler', 'oh-digital-delivery'),
            __('Siparişler', 'oh-digital-delivery'),
            'manage_woocommerce',
            'oh-digital-sales-orders',
            [static::class, 'render_orders']
        );
        self::$page_hooks[] = $orders;

        $codes = add_submenu_page(
            'oh-digital-sales',
            __('Kod Havuzu', 'oh-digital-delivery'),
            __('Kod Havuzu', 'oh-digital-delivery'),
            'manage_woocommerce',
            'oh-digital-sales-codes',
            [static::class, 'render_codes']
        );
        self::$page_hooks[] = $codes;

        $reports = add_submenu_page(
            'oh-digital-sales',
            __('Raporlar', 'oh-digital-delivery'),
            __('Raporlar', 'oh-digital-delivery'),
            'manage_woocommerce',
            'oh-digital-sales-reports',
            [static::class, 'render_reports']
        );
        self::$page_hooks[] = $reports;
    }

    public static function enqueue_assets(string $hook): void
    {
        if (! in_array($hook, self::$page_hooks, true)) {
            return;
        }

        $base_url = plugins_url('/', OH_DIGITAL_DELIVERY_PATH . 'oh-digital-delivery.php');

        wp_enqueue_style(
            'oh-digital-admin',
            $base_url . 'admin/assets/css/admin.css',
            [],
            OH_DIGITAL_DELIVERY_VERSION
        );

        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        wp_enqueue_script(
            'oh-digital-admin',
            $base_url . 'admin/assets/js/dashboard.js',
            ['chartjs'],
            OH_DIGITAL_DELIVERY_VERSION,
            true
        );

        wp_localize_script(
            'oh-digital-admin',
            'OHDigitalAdmin',
            [
                'root'     => esc_url(rest_url('oh/v1/admin/')),
                'nonce'    => wp_create_nonce('wp_rest'),
                'currency' => get_woocommerce_currency(),
                'locale'   => get_locale(),
                'i18n'     => [
                    'loading'        => __('Yükleniyor…', 'oh-digital-delivery'),
                    'noResults'      => __('Sonuç bulunamadı.', 'oh-digital-delivery'),
                    'orders'         => __('Siparişler', 'oh-digital-delivery'),
                    'revenue'        => __('Ciro', 'oh-digital-delivery'),
                    'items'          => __('Adet', 'oh-digital-delivery'),
                    'resend'         => __('Yeniden Gönder', 'oh-digital-delivery'),
                    'cancel'         => __('İptal Et', 'oh-digital-delivery'),
                    'actionSuccess'  => __('İşlem tamamlandı.', 'oh-digital-delivery'),
                    'actionError'    => __('İşlem başarısız.', 'oh-digital-delivery'),
                    'exportFile'     => __('orders.csv', 'oh-digital-delivery'),
                    'exportHeaders'  => [
                        __('Sipariş', 'oh-digital-delivery'),
                        __('Müşteri', 'oh-digital-delivery'),
                        __('E-posta', 'oh-digital-delivery'),
                        __('Ürünler', 'oh-digital-delivery'),
                        __('Durum', 'oh-digital-delivery'),
                        __('Kod Adedi', 'oh-digital-delivery'),
                        __('Toplam', 'oh-digital-delivery'),
                        __('Tarih', 'oh-digital-delivery'),
                    ],
                    'statusLabels'   => [
                        'pending'   => __('Bekliyor', 'oh-digital-delivery'),
                        'completed' => __('Tamamlandı', 'oh-digital-delivery'),
                        'partial'   => __('Stok Bekliyor', 'oh-digital-delivery'),
                        'failed'    => __('Başarısız', 'oh-digital-delivery'),
                    ],
                ],
            ]
        );
    }

    public static function render_dashboard(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(__('Bu alana erişim yetkiniz yok.', 'oh-digital-delivery'));
        }
        ?>
        <div class="wrap oh-digital-admin oh-digital-admin--dashboard" data-oh-dashboard>
            <h1><?php esc_html_e('Gösterge Paneli', 'oh-digital-delivery'); ?></h1>

            <div class="oh-dashboard__toolbar">
                <div class="oh-dashboard__range" data-range-picker>
                    <button type="button" class="button button-secondary is-active" data-range="30"><?php esc_html_e('Son 30 Gün', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button button-secondary" data-range="7"><?php esc_html_e('Son 7 Gün', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button button-secondary" data-range="custom"><?php esc_html_e('Özel Tarih', 'oh-digital-delivery'); ?></button>
                    <label class="screen-reader-text" for="oh-dashboard-start"><?php esc_html_e('Başlangıç tarihi', 'oh-digital-delivery'); ?></label>
                    <input type="date" id="oh-dashboard-start" data-range-start />
                    <label class="screen-reader-text" for="oh-dashboard-end"><?php esc_html_e('Bitiş tarihi', 'oh-digital-delivery'); ?></label>
                    <input type="date" id="oh-dashboard-end" data-range-end />
                    <button type="button" class="button button-primary" data-range-apply><?php esc_html_e('Uygula', 'oh-digital-delivery'); ?></button>
                </div>
            </div>

            <section class="oh-dashboard__cards">
                <article class="oh-card" data-stat="today-orders">
                    <h2><?php esc_html_e('Bugün - Sipariş', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                    <span data-delta>—</span>
                </article>
                <article class="oh-card" data-stat="today-revenue">
                    <h2><?php esc_html_e('Bugün - Ciro', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                    <span data-delta>—</span>
                </article>
                <article class="oh-card" data-stat="today-deliveries">
                    <h2><?php esc_html_e('Bugün - Teslimatlar', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                    <span data-delta>—</span>
                </article>
                <article class="oh-card" data-stat="seven-days">
                    <h2><?php esc_html_e('Son 7 Gün', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                    <span data-delta>—</span>
                </article>
                <article class="oh-card" data-stat="thirty-days">
                    <h2><?php esc_html_e('Son 30 Gün', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                    <span data-delta>—</span>
                </article>
                <article class="oh-card" data-stat="pending-orders">
                    <h2><?php esc_html_e('Bekleyen Siparişler', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                </article>
                <article class="oh-card" data-stat="total-revenue">
                    <h2><?php esc_html_e('Toplam Ciro', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                </article>
                <article class="oh-card" data-stat="stock-waiting">
                    <h2><?php esc_html_e('Bekleyen Stok', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                </article>
                <article class="oh-card" data-stat="completed-deliveries">
                    <h2><?php esc_html_e('Tamamlanan Teslimatlar', 'oh-digital-delivery'); ?></h2>
                    <strong data-value>—</strong>
                </article>
            </section>

            <section class="oh-dashboard__charts">
                <article class="oh-card oh-card--chart">
                    <h2><?php esc_html_e('Son 12 Ay Sipariş ve Ciro Trendleri', 'oh-digital-delivery'); ?></h2>
                    <canvas id="oh-dashboard-trend"></canvas>
                </article>
                <article class="oh-card oh-card--chart">
                    <h2><?php esc_html_e('Teslimat Durum Dağılımı', 'oh-digital-delivery'); ?></h2>
                    <canvas id="oh-dashboard-status"></canvas>
                </article>
                <article class="oh-card oh-card--chart">
                    <h2><?php esc_html_e('Günlük Satış Performansı', 'oh-digital-delivery'); ?></h2>
                    <canvas id="oh-dashboard-daily"></canvas>
                </article>
            </section>

            <div class="oh-dashboard__grid">
                <article class="oh-card oh-card--list">
                    <h2><?php esc_html_e('En Çok Satan Ürünler', 'oh-digital-delivery'); ?></h2>
                    <ul class="oh-list" data-top-products>
                        <li><?php esc_html_e('Veri yükleniyor…', 'oh-digital-delivery'); ?></li>
                    </ul>
                </article>
                <article class="oh-card oh-card--list">
                    <h2><?php esc_html_e('Ödeme Metodu Dağılımı', 'oh-digital-delivery'); ?></h2>
                    <ul class="oh-list" data-payment-breakdown>
                        <li><?php esc_html_e('Veri yükleniyor…', 'oh-digital-delivery'); ?></li>
                    </ul>
                </article>
                <article class="oh-card oh-card--list">
                    <h2><?php esc_html_e('Müşteri Analitiği', 'oh-digital-delivery'); ?></h2>
                    <dl class="oh-metrics" data-customer-metrics>
                        <div><dt><?php esc_html_e('Toplam Müşteri', 'oh-digital-delivery'); ?></dt><dd>—</dd></div>
                        <div><dt><?php esc_html_e('Tekrar Sipariş Oranı', 'oh-digital-delivery'); ?></dt><dd>—</dd></div>
                    </dl>
                </article>
            </div>

            <section class="oh-card oh-card--table">
                <h2><?php esc_html_e('Son 5 Sipariş', 'oh-digital-delivery'); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Sipariş', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Ürünler', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Tutar', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Durum', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Tarih', 'oh-digital-delivery'); ?></th>
                        </tr>
                    </thead>
                    <tbody data-recent-orders>
                        <tr><td colspan="5"><?php esc_html_e('Veri yükleniyor…', 'oh-digital-delivery'); ?></td></tr>
                    </tbody>
                </table>
            </section>
        </div>
        <?php
    }

    public static function render_orders(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(__('Bu alana erişim yetkiniz yok.', 'oh-digital-delivery'));
        }

        $products = wc_get_products([
            'limit'  => 200,
            'status' => ['publish', 'private'],
            'orderby'=> 'title',
            'order'  => 'ASC',
        ]);

        $gateways = [];
        if (function_exists('WC')) {
            $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : [];
        }
        ?>
        <div class="wrap oh-digital-admin" data-oh-orders>
            <h1><?php esc_html_e('Sipariş Yönetimi', 'oh-digital-delivery'); ?></h1>

            <form class="oh-orders__filters" data-orders-filters>
                <div class="oh-field">
                    <label for="oh-filter-status"><?php esc_html_e('Teslim Durumu', 'oh-digital-delivery'); ?></label>
                    <select id="oh-filter-status" name="status">
                        <option value="">— <?php esc_html_e('Tümü', 'oh-digital-delivery'); ?> —</option>
                        <option value="pending"><?php esc_html_e('Bekliyor', 'oh-digital-delivery'); ?></option>
                        <option value="completed"><?php esc_html_e('Tamamlandı', 'oh-digital-delivery'); ?></option>
                        <option value="partial"><?php esc_html_e('Stok Bekliyor', 'oh-digital-delivery'); ?></option>
                        <option value="failed"><?php esc_html_e('Başarısız', 'oh-digital-delivery'); ?></option>
                    </select>
                </div>
                <div class="oh-field">
                    <label for="oh-filter-product"><?php esc_html_e('Ürün', 'oh-digital-delivery'); ?></label>
                    <select id="oh-filter-product" name="product_id">
                        <option value="">— <?php esc_html_e('Tümü', 'oh-digital-delivery'); ?> —</option>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>"><?php echo esc_html($product->get_formatted_name()); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="oh-field">
                    <label for="oh-filter-payment"><?php esc_html_e('Ödeme Metodu', 'oh-digital-delivery'); ?></label>
                    <select id="oh-filter-payment" name="payment_method">
                        <option value="">— <?php esc_html_e('Tümü', 'oh-digital-delivery'); ?> —</option>
                        <?php foreach ($gateways as $id => $gateway) : ?>
                            <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($gateway->get_title()); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="oh-field">
                    <label for="oh-filter-start"><?php esc_html_e('Başlangıç', 'oh-digital-delivery'); ?></label>
                    <input type="date" id="oh-filter-start" name="date_start" />
                </div>
                <div class="oh-field">
                    <label for="oh-filter-end"><?php esc_html_e('Bitiş', 'oh-digital-delivery'); ?></label>
                    <input type="date" id="oh-filter-end" name="date_end" />
                </div>
                <div class="oh-field oh-field--actions">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filtrele', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button" data-reset-filters><?php esc_html_e('Sıfırla', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button" data-export-orders><?php esc_html_e('CSV Dışa Aktar', 'oh-digital-delivery'); ?></button>
                </div>
            </form>

            <div class="oh-orders__table">
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Sipariş', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Müşteri', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Ürünler', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Teslim Durumu', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Kod Adedi', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Toplam', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Tarih', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('İşlemler', 'oh-digital-delivery'); ?></th>
                        </tr>
                    </thead>
                    <tbody data-orders-body>
                        <tr><td colspan="8"><?php esc_html_e('Veri yükleniyor…', 'oh-digital-delivery'); ?></td></tr>
                    </tbody>
                </table>
                <div class="tablenav">
                    <div class="tablenav-pages" data-orders-pagination></div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_codes(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(__('Bu alana erişim yetkiniz yok.', 'oh-digital-delivery'));
        }

        $repository = new Codes_Repository();
        $service    = Plugin::instance()->get_delivery_service();

        $product_id = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
        $status     = isset($_GET['status']) ? sanitize_text_field(wp_unslash((string) $_GET['status'])) : '';
        $order_id   = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $page       = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $per_page   = 20;
        $offset     = ($page - 1) * $per_page;

        global $wpdb;

        $table      = $repository->get_table_name();
        $where      = ['1=1'];
        $parameters = [];

        if ($product_id) {
            $where[]      = 'product_id = %d';
            $parameters[] = $product_id;
        }

        if ($status && in_array($status, ['free', 'reserved', 'used', 'revoked'], true)) {
            $where[]      = 'status = %s';
            $parameters[] = $status;
        }

        if ($order_id) {
            $where[]      = 'order_id = %d';
            $parameters[] = $order_id;
        }

        $where_clause = implode(' AND ', $where);

        $count_sql = $parameters
            ? $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", ...$parameters)
            : "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

        $total = (int) $wpdb->get_var($count_sql);

        $list_params = array_merge($parameters, [$per_page, $offset]);
        $list_sql    = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY id DESC LIMIT %d OFFSET %d",
            ...$list_params
        );
        $rows = $wpdb->get_results($list_sql);

        $products = wc_get_products([
            'limit'  => 200,
            'status' => ['publish', 'private'],
            'orderby'=> 'title',
            'order'  => 'ASC',
        ]);

        $statuses = [
            ''         => __('Tümü', 'oh-digital-delivery'),
            'free'     => __('Boşta', 'oh-digital-delivery'),
            'reserved' => __('Rezerve', 'oh-digital-delivery'),
            'used'     => __('Teslim edildi', 'oh-digital-delivery'),
            'revoked'  => __('İptal edildi', 'oh-digital-delivery'),
        ];

        $stock_overview = $repository->get_stock_overview();
        $imported       = isset($_GET['imported']) ? absint($_GET['imported']) : 0;
        $error          = isset($_GET['error']) ? sanitize_text_field(wp_unslash((string) $_GET['error'])) : '';
        ?>
        <div class="wrap oh-digital-admin oh-digital-admin--codes">
            <h1><?php esc_html_e('Kod Havuzu', 'oh-digital-delivery'); ?></h1>

            <?php if ($imported) : ?>
                <div class="notice notice-success"><p><?php printf(esc_html__('%d kod başarıyla içe aktarıldı.', 'oh-digital-delivery'), $imported); ?></p></div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <section class="oh-card oh-card--table">
                <h2><?php esc_html_e('Stok Durumu', 'oh-digital-delivery'); ?></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Ürün', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Boşta', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Rezerve', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Teslim', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('İptal', 'oh-digital-delivery'); ?></th>
                        </tr>
                    </thead>
                    <tbody data-stock-overview>
                        <?php if ($stock_overview) : ?>
                            <?php foreach ($stock_overview as $stock_row) :
                                $product = wc_get_product((int) $stock_row['product_id']);
                                ?>
                                <tr>
                                    <td><?php echo esc_html($product ? $product->get_formatted_name() : __('Silinmiş ürün', 'oh-digital-delivery')); ?></td>
                                    <td><?php echo esc_html($stock_row['free'] ?? 0); ?></td>
                                    <td><?php echo esc_html($stock_row['reserved'] ?? 0); ?></td>
                                    <td><?php echo esc_html($stock_row['used'] ?? 0); ?></td>
                                    <td><?php echo esc_html($stock_row['revoked'] ?? 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="5"><?php esc_html_e('Stok verisi bulunamadı.', 'oh-digital-delivery'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <form method="get" class="oh-digital-admin__filters">
                <input type="hidden" name="page" value="oh-digital-sales-codes" />
                <label>
                    <?php esc_html_e('Ürün', 'oh-digital-delivery'); ?>
                    <select name="product_id">
                        <option value="0">— <?php esc_html_e('Tümü', 'oh-digital-delivery'); ?> —</option>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>" <?php selected($product_id, $product->get_id()); ?>>
                                <?php echo esc_html($product->get_formatted_name()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <?php esc_html_e('Durum', 'oh-digital-delivery'); ?>
                    <select name="status">
                        <?php foreach ($statuses as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <?php esc_html_e('Sipariş ID', 'oh-digital-delivery'); ?>
                    <input type="number" name="order_id" value="<?php echo esc_attr($order_id); ?>" min="0" />
                </label>
                <button type="submit" class="button button-primary"><?php esc_html_e('Filtrele', 'oh-digital-delivery'); ?></button>
            </form>

            <section class="oh-card oh-card--table">
                <h2><?php esc_html_e('Kod Listesi', 'oh-digital-delivery'); ?></h2>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Ürün', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Kod', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Durum', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Sipariş', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Not', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Güncelle', 'oh-digital-delivery'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows) : ?>
                            <?php foreach ($rows as $row) :
                                $product     = wc_get_product((int) $row->product_id);
                                $productName = $product ? $product->get_formatted_name() : __('Silinmiş ürün', 'oh-digital-delivery');
                                $code        = '';
                                try {
                                    $code = Encryption::decrypt($row->code);
                                } catch (\Throwable $exception) {
                                    $code = __('Çözülemedi', 'oh-digital-delivery');
                                }
                                ?>
                                <tr>
                                    <td><?php echo esc_html($row->id); ?></td>
                                    <td><?php echo esc_html($productName); ?></td>
                                    <td><code><?php echo esc_html($service->mask_code((string) $code)); ?></code></td>
                                    <td><?php echo esc_html($row->status); ?></td>
                                    <td><?php echo $row->order_id ? sprintf('#%d', $row->order_id) : '—'; ?></td>
                                    <td><?php echo $row->note ? esc_html($row->note) : '—'; ?></td>
                                    <td>
                                        <form method="post" action="<?php echo esc_url(rest_url('oh/v1/licenses/revoke')); ?>" class="oh-digital-admin__status-form" data-code-update>
                                            <?php wp_nonce_field('wp_rest'); ?>
                                            <input type="hidden" name="code_id" value="<?php echo esc_attr($row->id); ?>" />
                                            <select name="status">
                                                <option value="revoked" <?php selected('revoked', $row->status); ?>><?php esc_html_e('İptal', 'oh-digital-delivery'); ?></option>
                                                <option value="free" <?php selected('free', $row->status); ?>><?php esc_html_e('Boşta', 'oh-digital-delivery'); ?></option>
                                                <option value="used" <?php selected('used', $row->status); ?>><?php esc_html_e('Teslim', 'oh-digital-delivery'); ?></option>
                                            </select>
                                            <button type="submit" class="button button-secondary"><?php esc_html_e('Kaydet', 'oh-digital-delivery'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7"><?php esc_html_e('Kayıt bulunamadı.', 'oh-digital-delivery'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total > $per_page) :
                    $total_pages = (int) ceil($total / $per_page);
                    ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php echo paginate_links([
                                'total'   => $total_pages,
                                'current' => $page,
                                'base'    => add_query_arg('paged', '%#%'),
                                'format'  => '',
                            ]); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="oh-card oh-card--form">
                <h2><?php esc_html_e('CSV ile Kod Yükle', 'oh-digital-delivery'); ?></h2>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('oh_digital_import_codes'); ?>
                    <input type="hidden" name="action" value="oh_digital_import_codes" />
                    <p>
                        <label for="oh-import-product"><?php esc_html_e('Hedef ürün', 'oh-digital-delivery'); ?></label>
                        <select id="oh-import-product" name="product_id" required>
                            <option value="">— <?php esc_html_e('Ürün seçin', 'oh-digital-delivery'); ?> —</option>
                            <?php foreach ($products as $product) : ?>
                                <option value="<?php echo esc_attr($product->get_id()); ?>"><?php echo esc_html($product->get_formatted_name()); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="oh-import-note"><?php esc_html_e('Not (isteğe bağlı)', 'oh-digital-delivery'); ?></label>
                        <input type="text" id="oh-import-note" name="note" />
                    </p>
                    <p>
                        <label for="oh-import-file"><?php esc_html_e('CSV Dosyası', 'oh-digital-delivery'); ?></label>
                        <input type="file" id="oh-import-file" name="csv_file" accept=".csv,text/plain" required />
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e('İçe aktar', 'oh-digital-delivery'); ?></button>
                    </p>
                </form>
            </section>
        </div>
        <?php
    }

    public static function render_reports(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(__('Bu alana erişim yetkiniz yok.', 'oh-digital-delivery'));
        }
        ?>
        <div class="wrap oh-digital-admin oh-digital-admin--reports" data-oh-reports>
            <h1><?php esc_html_e('Raporlar', 'oh-digital-delivery'); ?></h1>

            <div class="oh-dashboard__toolbar">
                <div class="oh-dashboard__range" data-report-range>
                    <button type="button" class="button button-secondary is-active" data-range="30"><?php esc_html_e('Son 30 Gün', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button button-secondary" data-range="90"><?php esc_html_e('Son 90 Gün', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button button-secondary" data-range="365"><?php esc_html_e('Son 12 Ay', 'oh-digital-delivery'); ?></button>
                    <button type="button" class="button button-secondary" data-range="custom"><?php esc_html_e('Özel', 'oh-digital-delivery'); ?></button>
                    <input type="date" data-range-start />
                    <input type="date" data-range-end />
                    <button type="button" class="button button-primary" data-range-apply><?php esc_html_e('Filtrele', 'oh-digital-delivery'); ?></button>
                </div>
            </div>

            <section class="oh-dashboard__charts">
                <article class="oh-card oh-card--chart">
                    <h2><?php esc_html_e('Satış Grafiği', 'oh-digital-delivery'); ?></h2>
                    <canvas id="oh-reports-sales"></canvas>
                </article>
                <article class="oh-card oh-card--chart">
                    <h2><?php esc_html_e('Ürün Performansı', 'oh-digital-delivery'); ?></h2>
                    <canvas id="oh-reports-products"></canvas>
                </article>
                <article class="oh-card oh-card--chart">
                    <h2><?php esc_html_e('Ödeme Metodu Raporu', 'oh-digital-delivery'); ?></h2>
                    <canvas id="oh-reports-payments"></canvas>
                </article>
            </section>

            <section class="oh-card oh-card--table">
                <h2><?php esc_html_e('Ürün Bazlı Satış', 'oh-digital-delivery'); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Ürün', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Adet', 'oh-digital-delivery'); ?></th>
                            <th><?php esc_html_e('Ciro', 'oh-digital-delivery'); ?></th>
                        </tr>
                    </thead>
                    <tbody data-report-products>
                        <tr><td colspan="3"><?php esc_html_e('Veri yükleniyor…', 'oh-digital-delivery'); ?></td></tr>
                    </tbody>
                </table>
            </section>
        </div>
        <?php
    }

    public static function handle_import(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            wp_die(__('Bu işlemi gerçekleştirme yetkiniz yok.', 'oh-digital-delivery'));
        }

        check_admin_referer('oh_digital_import_codes');

        $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $note       = isset($_POST['note']) ? sanitize_text_field(wp_unslash((string) $_POST['note'])) : null;

        if (! $product_id) {
            wp_safe_redirect(add_query_arg('error', rawurlencode(__('Ürün seçmelisiniz.', 'oh-digital-delivery')), wp_get_referer()));
            exit;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('error', rawurlencode(__('CSV dosyası bulunamadı.', 'oh-digital-delivery')), wp_get_referer()));
            exit;
        }

        $contents = file_get_contents($_FILES['csv_file']['tmp_name']);
        if (false === $contents) {
            wp_safe_redirect(add_query_arg('error', rawurlencode(__('Dosya okunamadı.', 'oh-digital-delivery')), wp_get_referer()));
            exit;
        }

        $service  = Plugin::instance()->get_delivery_service();
        $imported = $service->import_codes_from_csv($contents, $product_id, $note);

        wp_safe_redirect(add_query_arg('imported', $imported, admin_url('admin.php?page=oh-digital-sales-codes')));
        exit;
    }

    public static function maybe_render_notices(): void
    {
        $screen = get_current_screen();
        if (! $screen || false === strpos($screen->id, 'oh-digital-sales')) {
            return;
        }

        $service    = Plugin::instance()->get_delivery_service();
        $low_stock  = $service->get_low_stock_products();
        $analytics  = Plugin::instance()->get_analytics_service();
        $dashboard  = $analytics->get_dashboard_payload();
        $pending    = $dashboard['totals']['pending_orders'] ?? 0;
        $stock_wait = $dashboard['totals']['stock_waiting'] ?? 0;

        if (! empty($low_stock)) {
            echo '<div class="notice notice-warning"><p>';
            esc_html_e('Bazı ürünlerde kod stoğu kritik seviyede.', 'oh-digital-delivery');
            echo '</p><ul class="oh-notice__list">';
            foreach ($low_stock as $item) {
                $product = wc_get_product($item['product_id']);
                $label   = sprintf('%s — %d', $product ? $product->get_name() : __('Silinmiş ürün', 'oh-digital-delivery'), (int) $item['remaining']);
                echo '<li>' . esc_html($label) . '</li>';
            }
            echo '</ul></div>';
        }

        if ($pending > 0) {
            echo '<div class="notice notice-info"><p>';
            printf(esc_html__('%d adet bekleyen teslimat bulunuyor.', 'oh-digital-delivery'), (int) $pending);
            echo '</p></div>';
        }

        if ($stock_wait > 0) {
            echo '<div class="notice notice-error"><p>';
            printf(esc_html__('%d adet sipariş stok bekliyor.', 'oh-digital-delivery'), (int) $stock_wait);
            echo '</p></div>';
        }
    }
}
