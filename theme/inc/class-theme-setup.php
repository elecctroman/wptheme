<?php
/**
 * Core theme setup logic.
 *
 * @package OHTheme
 */

declare(strict_types=1);

namespace OHTheme;

use WP_Post;

class ThemeSetup
{
    public static function init(): void
    {
        add_action('after_setup_theme', [static::class, 'setup']);
        add_action('wp_enqueue_scripts', [static::class, 'enqueue_assets']);
        add_action('after_switch_theme', [static::class, 'create_demo_content']);
        add_action('init', [static::class, 'register_theme_settings']);
    }

    public static function setup(): void
    {
        load_theme_textdomain(THEME_TEXT_DOMAIN, get_template_directory() . '/languages');

        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('woocommerce');
        add_theme_support(
            'html5',
            ['comment-form', 'comment-list', 'gallery', 'caption', 'search-form']
        );

        register_nav_menus(
            [
                'primary'   => __('Primary Menu', THEME_TEXT_DOMAIN),
                'secondary' => __('Secondary Menu', THEME_TEXT_DOMAIN),
                'footer'    => __('Footer Menu', THEME_TEXT_DOMAIN),
            ]
        );
    }

    public static function enqueue_assets(): void
    {
        $theme_uri = get_template_directory_uri();

        wp_enqueue_style(
            'oh-theme-style',
            $theme_uri . '/assets/css/theme.css',
            [],
            THEME_VERSION
        );

        wp_enqueue_script(
            'oh-theme-app',
            $theme_uri . '/assets/js/app.js',
            ['jquery'],
            THEME_VERSION,
            true
        );

        wp_localize_script(
            'oh-theme-app',
            'ohTheme',
            [
                'restUrl' => esc_url_raw(rest_url()),
                'nonce'   => wp_create_nonce('wp_rest'),
            ]
        );
    }

    public static function register_theme_settings(): void
    {
        register_setting(
            'oh_theme_options',
            'oh_theme_palette',
            [
                'type'              => 'array',
                'sanitize_callback' => [static::class, 'sanitize_palette'],
                'default'           => [
                    'primary'   => '#0f172a',
                    'secondary' => '#22d3ee',
                    'accent'    => '#fbbf24',
                    'muted'     => '#1e293b',
                ],
            ]
        );
    }

    public static function sanitize_palette($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $sanitized = [];
        foreach ($value as $key => $color) {
            $sanitized[$key] = sanitize_hex_color($color) ?: '#000000';
        }

        return $sanitized;
    }

    public static function create_demo_content(): void
    {
        if (! class_exists('WC_Product_Simple')) {
            return;
        }

        $existing = get_page_by_path('pubg-uc-demo', OBJECT, 'product');
        if ($existing instanceof WP_Post) {
            return;
        }

        $product = new \WC_Product_Simple();
        $product->set_name('PUBG UC Demo Paketi');
        $product->set_slug('pubg-uc-demo');
        $product->set_regular_price('99.00');
        $product->set_description(__('PUBG Mobile için hızlı teslimat dijital UC paketi.', THEME_TEXT_DOMAIN));
        $product->set_short_description(__('Anında teslim dijital ürün demosu.', THEME_TEXT_DOMAIN));
        $product->set_catalog_visibility('visible');
        $product->update_meta_data('_virtual', 'yes');
        $product->update_meta_data('_downloadable', 'no');

        $product_id = $product->save();

        if ($product_id && ! is_wp_error($product_id)) {
            update_post_meta($product_id, '_stock_status', 'instock');
        }

        flush_rewrite_rules();
    }
}
