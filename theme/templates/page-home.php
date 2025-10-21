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

get_header();

$shop_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
$shop_url     = $shop_page_id ? get_permalink($shop_page_id) : home_url('/shop');

$slides = [
    [
        'title'    => __('PUBG UC paketlerinde anında teslimat', 'oh-digital'),
        'subtitle' => __('ID doğrulamasıyla saniyeler içinde kod teslimi alın.', 'oh-digital'),
        'cta'      => $shop_url,
        'tag'      => __('Oyun İçin En Hızlı Mağaza', 'oh-digital'),
    ],
    [
        'title'    => __('Valorant VP & Riot Pin stokları hep hazır', 'oh-digital'),
        'subtitle' => __('Güvenli ödeme seçenekleri ve otomatik teslimat.', 'oh-digital'),
        'cta'      => $shop_url,
        'tag'      => __('Dakikalar içinde hesabında', 'oh-digital'),
    ],
    [
        'title'    => __('Windows, Office ve Adobe lisansları', 'oh-digital'),
        'subtitle' => __('Kurumsal lisanslar ve profesyonel yazılım çözümleri tek adımda.', 'oh-digital'),
        'cta'      => $shop_url,
        'tag'      => __('%100 Orijinal Lisans', 'oh-digital'),
    ],
];

$featured_categories = [
    'pubg',
    'valorant',
    'windows',
    'adobe',
    'canva',
    'freepik',
    'shutterstock',
    'elementor',
];

$blog_query = new WP_Query([
    'post_type'      => 'post',
    'posts_per_page' => 3,
    'ignore_sticky_posts' => true,
]);
?>
<section class="oh-hero" data-role="hero">
    <div class="oh-hero__glow"></div>
    <div class="oh-container">
        <div class="oh-hero__slider" data-role="hero-slider">
            <?php foreach ($slides as $index => $slide) : ?>
                <article class="oh-hero__slide" data-active="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                    <span class="oh-hero__badge"><?php echo esc_html($slide['tag']); ?></span>
                    <h1 class="oh-hero__title"><?php echo esc_html($slide['title']); ?></h1>
                    <p class="oh-hero__subtitle"><?php echo esc_html($slide['subtitle']); ?></p>
                    <div class="oh-hero__actions">
                        <a class="oh-button oh-button--primary" href="<?php echo esc_url($slide['cta']); ?>"><?php esc_html_e('Hemen İncele', 'oh-digital'); ?></a>
                        <a class="oh-button oh-button--ghost" href="#featured"><?php esc_html_e('Koleksiyonlar', 'oh-digital'); ?></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="oh-hero__progress" data-role="hero-progress">
            <?php foreach ($slides as $index => $slide) : ?>
                <button type="button" data-index="<?php echo esc_attr((string) $index); ?>" aria-label="<?php esc_attr_e('Slide', 'oh-digital'); ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="oh-section" id="featured">
    <div class="oh-container">
        <header class="oh-section__header">
            <h2><?php esc_html_e('Popüler kategoriler', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('En çok tercih edilen oyun ve yazılım lisanslarına hızlı erişim.', 'oh-digital'); ?></p>
        </header>
        <div class="oh-category-chips" data-role="category-chips">
            <?php foreach ($featured_categories as $slug) :
                $term = get_term_by('slug', $slug, 'product_cat');
                if (! $term || is_wp_error($term)) {
                    continue;
                }
                ?>
                <button class="oh-category-chip" type="button" data-category="<?php echo esc_attr((string) $term->term_id); ?>">
                    <span><?php echo esc_html($term->name); ?></span>
                    <span class="oh-category-chip__count"><?php echo esc_html((string) $term->count); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="oh-product-grid" data-role="category-grid" data-endpoint="<?php echo esc_url(rest_url('oh-digital/v1/catalog')); ?>">
            <?php echo do_shortcode('[products limit="8" columns="4" visibility="featured"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
</section>
<section class="oh-section">
    <div class="oh-container">
        <header class="oh-section__header">
            <h2><?php esc_html_e('Anında teslim rozetli ürünler', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('PUBG, Valorant ve Steam için hazır kod paketleri.', 'oh-digital'); ?></p>
        </header>
        <div class="oh-product-grid">
            <?php echo do_shortcode('[products limit="4" columns="4" tag="instant-delivery"]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
</section>
<section class="oh-section oh-section--split">
    <div class="oh-container">
        <div class="oh-split">
            <div class="oh-split__card">
                <h3><?php esc_html_e('Terra Wallet ile bakiye yükle', 'oh-digital'); ?></h3>
                <p><?php esc_html_e('PayTR, İyzico, Shopier, Paywant ve kripto dahil çoklu ödeme entegrasyonu.', 'oh-digital'); ?></p>
                <ul class="oh-feature-list">
                    <li><?php esc_html_e('Minimum 25₺ yükleme', 'oh-digital'); ?></li>
                    <li><?php esc_html_e('İşlem sonrası otomatik bakiye güncellemesi', 'oh-digital'); ?></li>
                    <li><?php esc_html_e('Bakiye ile ödeme adımında tek tıkla kullanın', 'oh-digital'); ?></li>
                </ul>
                <?php if (function_exists('wc_get_account_endpoint_url')) : ?>
                    <a class="oh-button oh-button--secondary" href="<?php echo esc_url(wc_get_account_endpoint_url('wallet')); ?>"><?php esc_html_e('Bakiye Paneline Git', 'oh-digital'); ?></a>
                <?php endif; ?>
            </div>
            <div class="oh-split__card oh-split__card--accent" data-role="wallet-preview">
                <header>
                    <span><?php esc_html_e('Bakiye Durumu', 'oh-digital'); ?></span>
                    <strong data-role="wallet-balance">0.00</strong>
                </header>
                <p><?php esc_html_e('Gerçek zamanlı bakiye ve hareket takibi.', 'oh-digital'); ?></p>
                <div class="oh-gateway-stack"><?php echo wp_kses_post(\OHTheme\render_gateway_badges()); ?></div>
            </div>
        </div>
    </div>
</section>
<section class="oh-section oh-section--blog">
    <div class="oh-container">
        <header class="oh-section__header">
            <h2><?php esc_html_e('Blog & Rehberler', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('Oyun içi ipuçları, lisans rehberleri ve güncel kampanyalar.', 'oh-digital'); ?></p>
        </header>
        <div class="oh-blog-grid">
            <?php if ($blog_query->have_posts()) : ?>
                <?php while ($blog_query->have_posts()) : $blog_query->the_post(); ?>
                    <article class="oh-blog-card">
                        <a href="<?php the_permalink(); ?>" class="oh-blog-card__link">
                            <div class="oh-blog-card__thumb"><?php the_post_thumbnail('medium_large'); ?></div>
                            <div class="oh-blog-card__content">
                                <h3><?php the_title(); ?></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                                <span class="oh-blog-card__meta"><?php echo esc_html(get_the_date()); ?></span>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="oh-empty"><?php esc_html_e('Henüz blog yazısı eklenmedi.', 'oh-digital'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="oh-section oh-section--seo">
    <div class="oh-container">
        <h2><?php esc_html_e('E-PİN Dijital Mağaza – Neden Biz?', 'oh-digital'); ?></h2>
        <p><?php esc_html_e('E-PİN, PUBG UC, Valorant VP, Steam cüzdan kodları ve kurumsal lisans satışında %100 otomasyon sunar. PayTR, İyzico, Shopier, Paywant, Terra Wallet ve kripto ödeme çözümleri ile tüm siparişleriniz güvenle tamamlanır.', 'oh-digital'); ?></p>
        <p><?php esc_html_e('Anında teslimat altyapımız kod havuzundan çekilen lisansları hem e-posta hem de Hesabım > Lisanslarım sekmesinde gösterir. Çoklu dil desteği, gelişmiş SEO altyapısı ve performans optimizasyonları sayesinde mağazanız arama motorlarında öne çıkar.', 'oh-digital'); ?></p>
    </div>
</section>
<?php
wp_reset_postdata();
get_footer();
