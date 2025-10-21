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
            <?php woocommerce_breadcrumb(['delimiter' => '<span>/</span>', 'wrap_before' => '<nav class="oh-breadcrumbs" aria-label="Breadcrumb">', 'wrap_after' => '</nav>']); ?>
            <h1 class="oh-archive__title"><?php woocommerce_page_title(); ?></h1>
            <div class="oh-archive__filters">
                <?php woocommerce_catalog_ordering(); ?>
                <?php woocommerce_result_count(); ?>
            </div>
        </header>
        <?php
        $current_category = get_queried_object();
        if ($current_category instanceof WP_Term) {
            $children = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'parent'     => $current_category->term_id,
            ]);
            if (! empty($children) && ! is_wp_error($children)) {
                echo '<div class="oh-subcategory-chips" data-role="subcategory-chips" data-endpoint="' . esc_url(rest_url('oh-digital/v1/catalog')) . '">';
                foreach ($children as $child) {
                    echo '<button type="button" class="oh-category-chip" data-category="' . esc_attr((string) $child->term_id) . '">';
                    echo esc_html($child->name);
                    echo '</button>';
                }
                echo '</div>';
            }
        }
        ?>
        <div class="oh-filter-panel" data-role="filter-panel" data-endpoint="<?php echo esc_url(rest_url('oh-digital/v1/catalog')); ?>">
            <form class="oh-filter-panel__form" data-role="filter-form">
                <div class="oh-filter-panel__group">
                    <label for="oh-filter-min"><?php esc_html_e('Min Fiyat', 'oh-digital'); ?></label>
                    <input type="number" name="min_price" id="oh-filter-min" min="0" step="5" />
                </div>
                <div class="oh-filter-panel__group">
                    <label for="oh-filter-max"><?php esc_html_e('Max Fiyat', 'oh-digital'); ?></label>
                    <input type="number" name="max_price" id="oh-filter-max" min="0" step="5" />
                </div>
                <div class="oh-filter-panel__group">
                    <label for="oh-filter-stock"><?php esc_html_e('Stok Durumu', 'oh-digital'); ?></label>
                    <select id="oh-filter-stock" name="stock">
                        <option value=""><?php esc_html_e('Tümü', 'oh-digital'); ?></option>
                        <option value="instock"><?php esc_html_e('Stokta', 'oh-digital'); ?></option>
                    </select>
                </div>
                <button type="submit" class="oh-button oh-button--primary"><?php esc_html_e('Filtrele', 'oh-digital'); ?></button>
            </form>
        </div>
        <div class="oh-product-grid" data-role="archive-grid">
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
        <?php if ($current_category instanceof WP_Term) : ?>
            <div class="oh-archive__seo">
                <h2><?php echo esc_html(sprintf(__('Hakkında: %s', 'oh-digital'), $current_category->name)); ?></h2>
                <p><?php echo wp_kses_post(term_description($current_category)); ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
require get_template_directory() . '/templates/parts/footer.php';
