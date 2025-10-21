<?php
/**
 * Default licenses template used as a fallback when the theme does not override.
 *
 * @var string $list_endpoint
 * @var string $detail_endpoint
 * @var string $pdf_endpoint
 * @var string $nonce
 */
?>
<div
    class="oh-account-section oh-account-section--licenses"
    data-list-endpoint="<?php echo esc_attr($list_endpoint); ?>"
    data-detail-endpoint="<?php echo esc_attr($detail_endpoint); ?>"
    data-pdf-endpoint="<?php echo esc_attr($pdf_endpoint); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
>
    <header class="oh-account-section__header">
        <div>
            <h2><?php esc_html_e('Lisanslarım', 'oh-digital-delivery'); ?></h2>
            <p><?php esc_html_e('Satın aldığınız dijital kodları bu alandan yönetebilirsiniz.', 'oh-digital-delivery'); ?></p>
        </div>
        <div class="oh-account-section__actions">
            <button type="button" class="button" data-action="download-all">
                <?php esc_html_e('PDF Olarak İndir', 'oh-digital-delivery'); ?>
            </button>
        </div>
    </header>
    <div class="oh-account-section__filters">
        <label>
            <span class="screen-reader-text"><?php esc_html_e('Ara', 'oh-digital-delivery'); ?></span>
            <input type="search" placeholder="<?php echo esc_attr__('Ürün veya sipariş ara...', 'oh-digital-delivery'); ?>" data-filter="query" />
        </label>
    </div>
    <div class="oh-account-cards" data-role="list"></div>
    <div class="oh-account-empty" data-role="empty" hidden>
        <?php esc_html_e('Henüz teslim edilmiş lisansınız bulunmuyor.', 'oh-digital-delivery'); ?>
    </div>
</div>
