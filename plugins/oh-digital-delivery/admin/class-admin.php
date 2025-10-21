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

class Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [static::class, 'register_menu']);
        add_action('admin_post_oh_digital_import_codes', [static::class, 'handle_import']);
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

        $table       = $repository->get_table_name();
        $where       = ['1=1'];
        $parameters  = [];

        if ($product_id) {
            $where[]     = 'product_id = %d';
            $parameters[] = $product_id;
        }

        if ($status && in_array($status, ['free', 'reserved', 'used', 'revoked'], true)) {
            $where[]     = 'status = %s';
            $parameters[] = $status;
        }

        if ($order_id) {
            $where[]     = 'order_id = %d';
            $parameters[] = $order_id;
        }

        $where_clause = implode(' AND ', $where);

        if ($parameters) {
            $count_sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}",
                ...$parameters
            );
        } else {
            $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        }
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

        $imported = isset($_GET['imported']) ? absint($_GET['imported']) : 0;
        $error    = isset($_GET['error']) ? sanitize_text_field(wp_unslash((string) $_GET['error'])) : '';
        ?>
        <div class="wrap oh-digital-admin">
            <h1><?php esc_html_e('Dijital Kod Havuzu', 'oh-digital-delivery'); ?></h1>

            <?php if ($imported) : ?>
                <div class="notice notice-success"><p><?php printf(esc_html__('%d kod başarıyla içe aktarıldı.', 'oh-digital-delivery'), $imported); ?></p></div>
            <?php endif; ?>

            <?php if ($error) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <form method="get" class="oh-digital-admin__filters">
                <input type="hidden" name="page" value="oh-digital-delivery" />
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

            <h2><?php esc_html_e('Kod Havuzu', 'oh-digital-delivery'); ?></h2>
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
                                <td><code><?php echo esc_html($service->mask_code($code)); ?></code></td>
                                <td><?php echo esc_html($row->status); ?></td>
                                <td><?php echo $row->order_id ? sprintf('#%d', $row->order_id) : '—'; ?></td>
                                <td><?php echo $row->note ? esc_html($row->note) : '—'; ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(rest_url('oh/v1/licenses/revoke')); ?>" class="oh-digital-admin__status-form">
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
        </div>
        <script>
            (function () {
                const forms = document.querySelectorAll('.oh-digital-admin__status-form');
                forms.forEach((form) => {
                    form.addEventListener('submit', (event) => {
                        event.preventDefault();
                        const formData = new FormData(form);
                        const endpoint = form.getAttribute('action');
                        const headers = new Headers({ 'X-WP-Nonce': form.querySelector('input[name="_wpnonce"]').value });

                        fetch(endpoint, {
                            method: 'POST',
                            headers,
                            body: formData,
                        })
                            .then((response) => response.json())
                            .then(() => {
                                window.location.reload();
                            });
                    });
                });
            })();
        </script>
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

        $service = Plugin::instance()->get_delivery_service();
        $imported = $service->import_codes_from_csv($contents, $product_id, $note);

        wp_safe_redirect(add_query_arg('imported', $imported, admin_url('admin.php?page=oh-digital-delivery')));
        exit;
    }
}
