<?php
/**
 * Custom front-page template scaffold.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require get_template_directory() . '/templates/parts/header.php';
?>
<section class="oh-hero">
    <div class="oh-container">
        <div class="oh-hero__content">
            <span class="oh-hero__eyebrow"><?php esc_html_e('Dijital kod & hesap mağazası', 'oh-digital'); ?></span>
            <h1 class="oh-hero__title"><?php esc_html_e('PUBG UC, Valorant VP ve lisanslarda ışık hızında teslimat', 'oh-digital'); ?></h1>
            <p class="oh-hero__subtitle"><?php esc_html_e('7/24 otomatik teslimat ve güvenilir ödeme altyapısı ile ihtiyacınız olan tüm kodlar tek yerde.', 'oh-digital'); ?></p>
            <div class="oh-hero__actions">
                <?php
                $shop_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
                $shop_url     = $shop_page_id ? get_permalink($shop_page_id) : home_url('/shop');
                ?>
                <a class="oh-button oh-button--primary" href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Ürünleri keşfet', 'oh-digital'); ?></a>
                <a class="oh-button oh-button--ghost" href="#featured"><?php esc_html_e('Popüler kategoriler', 'oh-digital'); ?></a>
            </div>
        </div>
        <div class="oh-hero__media">
            <span class="oh-hero__badge"><?php esc_html_e('Dakikalar içinde teslim', 'oh-digital'); ?></span>
        </div>
    </div>
</section>
<section class="oh-section" id="featured">
    <div class="oh-container">
        <header class="oh-section__header">
            <h2><?php esc_html_e('Öne çıkan ürünler', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('En çok satan dijital ürünlerimiz', 'oh-digital'); ?></p>
        </header>
        <div class="oh-product-grid">
            <?php
            echo do_shortcode('[products limit="4" columns="4" visibility="featured"]');
            ?>
        </div>
    </div>
</section>
<section class="oh-section">
    <div class="oh-container">
        <header class="oh-section__header">
            <h2><?php esc_html_e('Bakiye ile ödeme', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('Terra Wallet entegrasyonu ile saniyeler içinde bakiye yükleyin.', 'oh-digital'); ?></p>
        </header>
        <div class="oh-wallet" data-wallet-balance>
            0.00
        </div>
    </div>
</section>
<?php
require get_template_directory() . '/templates/parts/footer.php';
