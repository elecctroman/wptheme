<?php
/**
 * WooCommerce specific overrides and template helpers.
 *
 * @package OHTheme
 */

declare(strict_types=1);

namespace OHTheme;

use function esc_attr;
use function esc_html__;
use function esc_html_e;
use function get_current_user_id;
use function get_user_meta;
use function sanitize_text_field;
use function wc_add_notice;
use function update_user_meta;
use function wp_kses_post;
use function wp_unslash;

class WCOverrides
{
    public static function init(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        add_action('after_setup_theme', [static::class, 'declare_support']);
        add_filter('woocommerce_locate_template', [static::class, 'override_templates'], 10, 3);
        add_filter('woocommerce_product_get_rating_html', [static::class, 'render_badge_rating'], 10, 3);
        add_action('woocommerce_edit_account_form', [static::class, 'render_phone_field']);
        add_action('woocommerce_save_account_details', [static::class, 'save_phone_field']);
        add_filter('woocommerce_my_account_my_orders_actions', [static::class, 'decorate_order_actions']);
        add_action('woocommerce_before_add_to_cart_button', [static::class, 'render_player_id_field']);
        add_filter('woocommerce_add_to_cart_validation', [static::class, 'validate_player_id'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [static::class, 'store_player_id'], 10, 3);
        add_filter('woocommerce_get_item_data', [static::class, 'display_player_id'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [static::class, 'persist_player_id'], 10, 4);
    }

    public static function declare_support(): void
    {
        add_theme_support(
            'woocommerce',
            [
                'thumbnail_image_width' => 420,
                'single_image_width'    => 720,
                'product_grid'          => [
                    'default_rows'    => 3,
                    'min_rows'        => 1,
                    'max_rows'        => 6,
                    'default_columns' => 3,
                    'min_columns'     => 2,
                    'max_columns'     => 4,
                ],
            ]
        );
    }

    public static function override_templates(string $template, string $template_name, string $template_path): string
    {
        $theme_template = locate_template('templates/' . $template_name);

        if ($theme_template) {
            return $theme_template;
        }

        return $template;
    }

    public static function render_badge_rating($html, $rating, $count)
    {
        if (! $rating) {
            return $html;
        }

        $badge = sprintf(
            '<span class="oh-product-badge" aria-hidden="true">★ %s</span>',
            esc_html(number_format_i18n((float) $rating, 1))
        );

        return $badge . $html;
    }

    public static function render_phone_field(): void
    {
        $user_id = get_current_user_id();
        $phone   = get_user_meta($user_id, 'billing_phone', true);
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_phone"><?php esc_html_e('Telefon Numarası', THEME_TEXT_DOMAIN); ?></label>
            <input type="tel" class="woocommerce-Input input-text" name="account_phone" id="account_phone" value="<?php echo esc_attr($phone); ?>" />
        </p>
        <?php
    }

    public static function save_phone_field(int $user_id): void
    {
        if (isset($_POST['account_phone'])) { // phpcs:ignore WordPress.Security.NonceVerification
            update_user_meta($user_id, 'billing_phone', sanitize_text_field(wp_unslash($_POST['account_phone']))); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        }
    }

    public static function decorate_order_actions(array $actions): array
    {
        foreach ($actions as $key => &$action) {
            $action['name'] = sprintf('<span class="oh-order-action oh-order-action--%1$s">%2$s</span>', esc_attr($key), wp_kses_post($action['name']));
        }

        return $actions;
    }

    public static function render_player_id_field(): void
    {
        global $product;

        if (! $product) {
            return;
        }

        $label = $product->get_meta('_oh_required_player_id_label');
        if (! $label) {
            $label = __('Oyuncu ID', THEME_TEXT_DOMAIN);
        }

        ?>
        <div class="oh-player-id">
            <label class="oh-player-id__label" for="oh-player-id"><?php echo esc_html($label); ?> <span>*</span></label>
            <input
                class="oh-player-id__input"
                id="oh-player-id"
                name="oh_player_id"
                type="text"
                required
                maxlength="60"
                placeholder="<?php echo esc_attr(sprintf(__('Örn: %s', THEME_TEXT_DOMAIN), '1234567890')); ?>"
            />
            <small class="oh-player-id__hint"><?php esc_html_e('Teslimatın doğru yapılabilmesi için oyun içi ID\'nizi giriniz.', THEME_TEXT_DOMAIN); ?></small>
        </div>
        <?php
    }

    public static function validate_player_id(bool $passed, int $product_id, int $quantity): bool
    {
        if (isset($_POST['oh_player_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $value = trim((string) wp_unslash($_POST['oh_player_id'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            if ($value === '') {
                wc_add_notice(__('Oyuncu ID alanı zorunludur.', THEME_TEXT_DOMAIN), 'error');
                return false;
            }
        } else {
            wc_add_notice(__('Oyuncu ID alanı zorunludur.', THEME_TEXT_DOMAIN), 'error');
            return false;
        }

        return $passed;
    }

    public static function store_player_id(array $cart_item_data, int $product_id, int $variation_id): array
    {
        if (isset($_POST['oh_player_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $cart_item_data['oh_player_id'] = sanitize_text_field(wp_unslash($_POST['oh_player_id'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        }

        return $cart_item_data;
    }

    public static function display_player_id(array $item_data, array $cart_item): array
    {
        if (isset($cart_item['oh_player_id'])) {
            $item_data[] = [
                'name'  => __('Oyuncu ID', THEME_TEXT_DOMAIN),
                'value' => esc_html($cart_item['oh_player_id']),
            ];
        }

        return $item_data;
    }

    public static function persist_player_id($item, $cart_item_key, $values, $order): void
    {
        if (isset($values['oh_player_id'])) {
            $item->add_meta_data('Oyuncu ID', sanitize_text_field($values['oh_player_id']), true);
        }
    }
}
