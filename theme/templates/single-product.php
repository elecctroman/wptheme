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
        <?php woocommerce_breadcrumb(['delimiter' => '<span>/</span>', 'wrap_before' => '<nav class="oh-breadcrumbs" aria-label="Breadcrumb">', 'wrap_after' => '</nav>']); ?>
        <?php while (have_posts()) : ?>
            <?php the_post(); ?>
            <?php $product = wc_get_product(get_the_ID()); ?>
            <article id="product-<?php the_ID(); ?>" <?php wc_product_class('oh-product-detail', get_the_ID()); ?> data-product-type="<?php echo esc_attr($product ? $product->get_type() : 'simple'); ?>">
                <div class="oh-product-detail__media">
                    <?php woocommerce_show_product_images(); ?>
                </div>
                <div class="oh-product-detail__summary" data-role="product-summary">
                    <h1 class="oh-product-detail__title"><?php the_title(); ?></h1>
                    <div class="oh-product-detail__rating">
                        <?php woocommerce_template_single_rating(); ?>
                    </div>
                    <div class="oh-product-detail__price" data-role="product-price">
                        <?php woocommerce_template_single_price(); ?>
                    </div>
                    <div class="oh-product-detail__badges">
                        <span class="oh-badge oh-badge--instant"><?php esc_html_e('Anında Teslim', 'oh-digital'); ?></span>
                        <span class="oh-badge oh-badge--auto"><?php esc_html_e('Otomatik Teslim', 'oh-digital'); ?></span>
                        <span class="oh-badge oh-badge--secure"><?php esc_html_e('Güvenli Ödeme', 'oh-digital'); ?></span>
                    </div>
                    <?php woocommerce_template_single_excerpt(); ?>
                    <?php if ($product && $product->is_type('variable')) : ?>
                        <div class="oh-pill-select" data-role="variant-select">
                            <?php
                            $variations = $product->get_available_variations();
                            foreach ($variations as $variation) {
                                $attributes = []; // Build readable label
                                foreach ($variation['attributes'] as $key => $value) {
                                    $attributes[] = wc_attribute_label(str_replace('attribute_', '', $key)) . ': ' . wc_attribute_label($value);
                                }
                                $label = implode(' / ', $attributes);
                                ?>
                                <label class="oh-pill-select__option">
                                    <input type="radio" name="oh-variation" value="<?php echo esc_attr((string) $variation['variation_id']); ?>" data-price="<?php echo esc_attr((string) ($variation['display_price'] ?? 0)); ?>" />
                                    <span><?php echo esc_html($label); ?></span>
                                </label>
                                <?php
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    <section class="oh-product-detail__delivery">
                        <h2><?php esc_html_e('Teslimat Bilgileri', 'oh-digital'); ?></h2>
                        <ul>
                            <li><?php esc_html_e('Ödeme tamamlandığında kod havuzundan otomatik çekim yapılır.', 'oh-digital'); ?></li>
                            <li><?php esc_html_e('Kodlar e-posta ve Hesabım > Lisanslarım sekmesinde görüntülenir.', 'oh-digital'); ?></li>
                            <li><?php esc_html_e('Terra Wallet ile bakiye kullanımında anlık düşüş gerçekleşir.', 'oh-digital'); ?></li>
                        </ul>
                    </section>
                    <div class="oh-product-detail__meta">
                        <?php woocommerce_template_single_add_to_cart(); ?>
                        <?php woocommerce_template_single_meta(); ?>
                    </div>
                    <div class="oh-product-detail__support">
                        <a class="oh-button oh-button--outline" href="https://wa.me/905551112233" target="_blank" rel="noopener">
                            <?php esc_html_e('WhatsApp ile İletişim', 'oh-digital'); ?>
                        </a>
                        <div class="oh-gateway-stack"><?php echo wp_kses_post(\OHTheme\render_gateway_badges()); ?></div>
                    </div>
                </div>
            </article>
            <?php woocommerce_output_product_data_tabs(); ?>
            <section class="oh-product-detail__related">
                <header>
                    <h2><?php esc_html_e('İlginizi Çekebilir', 'oh-digital'); ?></h2>
                </header>
                <?php woocommerce_upsell_display(); ?>
            </section>
            <?php woocommerce_output_related_products(); ?>
        <?php endwhile; ?>
    </div>
</section>
<div class="oh-mini-cart" data-role="mini-cart" hidden>
    <div class="oh-mini-cart__content">
        <div class="oh-mini-cart__header">
            <h3><?php esc_html_e('Sepete Eklendi', 'oh-digital'); ?></h3>
            <button type="button" class="oh-mini-cart__close" data-action="close">&times;</button>
        </div>
        <div class="oh-mini-cart__body" data-role="mini-cart-body"></div>
        <div class="oh-mini-cart__actions">
            <a class="oh-button oh-button--ghost" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart')); ?>"><?php esc_html_e('Sepete Git', 'oh-digital'); ?></a>
            <a class="oh-button oh-button--primary" href="<?php echo esc_url(function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout')); ?>"><?php esc_html_e('Ödeme Adımına Geç', 'oh-digital'); ?></a>
        </div>
    </div>
</div>
<?php
require get_template_directory() . '/templates/parts/footer.php';
