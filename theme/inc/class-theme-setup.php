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
        add_action('wp_head', [static::class, 'render_meta_tags'], 5);
        add_action('wp_head', [static::class, 'render_structured_data'], 20);
        add_filter('wp_get_attachment_image_attributes', [static::class, 'ensure_lazy_loaded_images'], 10, 3);
        add_filter('script_loader_tag', [static::class, 'defer_theme_scripts'], 10, 3);
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
                'siteName' => get_bloginfo('name'),
            ]
        );

        $critical = '.oh-main{min-height:60vh}.oh-button{display:inline-flex;align-items:center;justify-content:center;font-weight:600;border-radius:999px;transition:transform .2s ease,box-shadow .2s ease}.oh-product-card{position:relative;overflow:hidden;border-radius:1.5rem;background:rgba(18,18,18,.75);display:flex;flex-direction:column;min-height:100%;transition:transform .25s ease,box-shadow .25s ease}.oh-product-card:hover{transform:translateY(-6px);box-shadow:0 30px 60px rgba(0,0,0,.45);}';
        wp_add_inline_style('oh-theme-style', $critical);
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
        $product->update_meta_data('_oh_required_player_id_label', __('PUBG ID', THEME_TEXT_DOMAIN));

        $product_id = $product->save();

        if ($product_id && ! is_wp_error($product_id)) {
            update_post_meta($product_id, '_stock_status', 'instock');
        }

        flush_rewrite_rules();
    }

    public static function render_meta_tags(): void
    {
        if (is_admin()) {
            return;
        }

        $title       = wp_get_document_title();
        $description = get_bloginfo('description');
        $image       = get_template_directory_uri() . '/assets/img/social-share-default.jpg';

        if (is_singular()) {
            $post = get_queried_object();
            if ($post instanceof \WP_Post) {
                $custom_description = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
                $description        = $custom_description ?: wp_trim_words(wp_strip_all_tags(get_the_excerpt($post)), 40);
                $maybe_thumb        = get_the_post_thumbnail_url($post, 'large');
                if ($maybe_thumb) {
                    $image = $maybe_thumb;
                }
            }
        }

        if (function_exists('is_shop') && (is_shop() || is_product_taxonomy()) && function_exists('wc_get_page_id')) {
            $shop_page_id = wc_get_page_id('shop');
            if ($shop_page_id > 0) {
                $description = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $shop_page_id)), 40);
            }
        }

        $description = $description ?: __('PUBG UC, Valorant VP ve kurumsal lisanslarda anında teslimat.', THEME_TEXT_DOMAIN);

        echo '<meta name="description" content="' . esc_attr($description) . '" />';
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />';
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />';
        echo '<meta property="og:image" content="' . esc_url($image) . '" />';
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />';
        echo '<meta property="twitter:card" content="summary_large_image" />';
        echo '<meta property="twitter:title" content="' . esc_attr($title) . '" />';
        echo '<meta property="twitter:description" content="' . esc_attr($description) . '" />';
    }

    public static function render_structured_data(): void
    {
        if (! function_exists('is_product') || ! is_product() || ! function_exists('wc_get_product')) {
            return;
        }

        $product = wc_get_product(get_the_ID());
        if (! $product) {
            return;
        }

        $data = [
            '@context'    => 'https://schema.org/',
            '@type'       => 'Product',
            'name'        => wp_strip_all_tags(get_the_title()),
            'description' => wp_strip_all_tags(get_the_excerpt()),
            'sku'         => $product->get_sku() ?: (string) $product->get_id(),
            'image'       => array_filter($product->get_gallery_image_ids() ? array_map('wp_get_attachment_url', $product->get_gallery_image_ids()) : [get_the_post_thumbnail_url($product->get_id(), 'large')]),
            'brand'       => get_bloginfo('name'),
        ];

        $price = (float) $product->get_price();
        if ($price > 0) {
            $data['offers'] = [
                '@type'           => 'Offer',
                'priceCurrency'   => get_woocommerce_currency(),
                'price'           => wc_format_decimal($price, wc_get_price_decimals()),
                'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url'             => get_permalink($product->get_id()),
                'seller'          => [
                    '@type' => 'Organization',
                    'name'  => get_bloginfo('name'),
                ],
            ];
        }

        if ($product->get_average_rating()) {
            $data['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_rating_count(),
            ];
        }

        echo '<script type="application/ld+json">' . wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    public static function ensure_lazy_loaded_images(array $attr, $attachment, $size): array
    {
        if (empty($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }

        if (! empty($attr['class'])) {
            $attr['class'] .= ' oh-lazy-image';
        } else {
            $attr['class'] = 'oh-lazy-image';
        }

        return $attr;
    }

    public static function defer_theme_scripts(string $tag, string $handle, string $src): string
    {
        if ('oh-theme-app' === $handle) {
            return '<script src="' . esc_url($src) . '" defer></script>';
        }

        return $tag;
    }
}
