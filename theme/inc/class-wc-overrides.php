<?php
/**
 * WooCommerce specific overrides and template helpers.
 *
 * @package OHTheme
 */

declare(strict_types=1);

namespace OHTheme;

class WCOverrides
{
    public static function init(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        add_action('after_setup_theme', [static::class, 'declare_support']);
        add_action('init', [static::class, 'register_wallet_endpoint']);
        add_filter('woocommerce_locate_template', [static::class, 'override_templates'], 10, 3);
        add_filter('woocommerce_product_get_rating_html', [static::class, 'render_badge_rating'], 10, 3);
        add_filter('woocommerce_account_menu_items', [static::class, 'filter_account_menu']);
        add_action('woocommerce_account_wallet_endpoint', [static::class, 'render_wallet_endpoint']);
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

    public static function register_wallet_endpoint(): void
    {
        add_rewrite_endpoint('wallet', EP_ROOT | EP_PAGES);
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

    public static function filter_account_menu(array $items): array
    {
        $items['wallet'] = __('Cüzdanım', THEME_TEXT_DOMAIN);
        return $items;
    }

    public static function render_wallet_endpoint(): void
    {
        wc_get_template('myaccount/wallet.php');
    }
}
