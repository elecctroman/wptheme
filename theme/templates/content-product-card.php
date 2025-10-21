<?php
/**
 * Custom product card for grid listings.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

global $product;

?>
<li <?php wc_product_class('oh-product-card'); ?>>
    <a href="<?php the_permalink(); ?>" class="oh-product-card__link">
        <div class="oh-product-card__thumbnail">
            <?php echo woocommerce_get_product_thumbnail('woocommerce_thumbnail'); ?>
            <span class="oh-product-card__badge"><?php esc_html_e('Popüler', 'oh-digital'); ?></span>
        </div>
        <div class="oh-product-card__content">
            <h2 class="oh-product-card__title"><?php the_title(); ?></h2>
            <p class="oh-product-card__excerpt"><?php echo wp_trim_words(get_the_excerpt(), 12); ?></p>
            <div class="oh-product-card__meta">
                <span class="oh-product-card__price"><?php echo $product->get_price_html(); ?></span>
                <span class="oh-product-card__delivery"><?php esc_html_e('Anında Teslim', 'oh-digital'); ?></span>
            </div>
        </div>
    </a>
    <div class="oh-product-card__actions">
        <?php woocommerce_template_loop_add_to_cart(); ?>
    </div>
</li>
