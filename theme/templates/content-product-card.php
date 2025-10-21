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

$title      = get_the_title();
$slug       = sanitize_title($title);
$match_text = $title;
if (preg_match('/(\d+[\s]*(UC|VP|TL|USD|₺))/iu', $title, $matches)) {
    $match_text = $matches[0];
}

$badges = [
    __('Anında Teslim', 'oh-digital'),
    __('Otomatik Teslim', 'oh-digital'),
    __('Güvenli Ödeme', 'oh-digital'),
];

?>
<li <?php wc_product_class('oh-product-card oh-product-card--' . esc_attr($slug)); ?> data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>">
    <a href="<?php the_permalink(); ?>" class="oh-product-card__link">
        <div class="oh-product-card__thumbnail">
            <?php echo woocommerce_get_product_thumbnail('woocommerce_thumbnail'); ?>
            <div class="oh-product-card__badge-stack">
                <?php foreach ($badges as $badge) : ?>
                    <span class="oh-product-card__badge"><?php echo esc_html($badge); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="oh-product-card__content">
            <h2 class="oh-product-card__title"><?php echo esc_html($title); ?></h2>
            <span class="oh-product-card__amount"><?php echo esc_html($match_text); ?></span>
            <p class="oh-product-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 16)); ?></p>
        </div>
    </a>
    <div class="oh-product-card__footer">
        <span class="oh-product-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
        <div class="oh-product-card__actions">
            <?php woocommerce_template_loop_add_to_cart(['class' => 'oh-button oh-button--primary']); ?>
        </div>
    </div>
</li>
