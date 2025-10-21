<?php
/**
 * Template for single product view.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require get_template_directory() . '/templates/parts/header.php';
?>
<section class="oh-single-product">
    <div class="oh-container">
        <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <article id="product-<?php the_ID(); ?>" <?php wc_product_class('oh-product-detail', get_the_ID()); ?>>
                <div class="oh-product-detail__media">
                    <?php woocommerce_show_product_images(); ?>
                </div>
                <div class="oh-product-detail__summary">
                    <h1 class="oh-product-detail__title"><?php the_title(); ?></h1>
                    <?php woocommerce_template_single_rating(); ?>
                    <?php woocommerce_template_single_price(); ?>
                    <div class="oh-product-detail__badges">
                        <span class="oh-badge oh-badge--instant"><?php esc_html_e('Anında Teslim', 'oh-digital'); ?></span>
                        <span class="oh-badge oh-badge--secure"><?php esc_html_e('Güvenli Ödeme', 'oh-digital'); ?></span>
                    </div>
                    <?php woocommerce_template_single_excerpt(); ?>
                    <div class="oh-product-detail__meta">
                        <?php woocommerce_template_single_add_to_cart(); ?>
                        <?php woocommerce_template_single_meta(); ?>
                    </div>
                    <div class="oh-product-detail__support">
                        <a class="oh-button oh-button--outline" href="https://wa.me/905551112233" target="_blank" rel="noopener">
                            <?php esc_html_e('WhatsApp ile İletişim', 'oh-digital'); ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php woocommerce_output_product_data_tabs(); ?>
            <?php woocommerce_upsell_display(); ?>
            <?php woocommerce_output_related_products(); ?>
        <?php endwhile; ?>
    </div>
</section>
<?php
require get_template_directory() . '/templates/parts/footer.php';
