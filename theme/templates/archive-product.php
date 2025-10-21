<?php
/**
 * Template for WooCommerce product archives.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require get_template_directory() . '/templates/parts/header.php';
?>
<section class="oh-archive">
    <div class="oh-container">
        <header class="oh-archive__header">
            <h1 class="oh-archive__title"><?php woocommerce_page_title(); ?></h1>
            <div class="oh-archive__filters">
                <?php woocommerce_catalog_ordering(); ?>
                <?php woocommerce_result_count(); ?>
            </div>
        </header>
        <div class="oh-product-grid">
            <?php if (woocommerce_product_loop()) : ?>
                <?php woocommerce_product_loop_start(); ?>

                <?php while (have_posts()) : ?>
                    <?php the_post(); ?>
                    <?php wc_get_template_part('content', 'product-card'); ?>
                <?php endwhile; ?>

                <?php woocommerce_product_loop_end(); ?>
            <?php else : ?>
                <?php wc_get_template('loop/no-products-found.php'); ?>
            <?php endif; ?>
        </div>
        <div class="oh-archive__pagination">
            <?php woocommerce_pagination(); ?>
        </div>
    </div>
</section>
<?php
require get_template_directory() . '/templates/parts/footer.php';
