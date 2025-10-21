<?php
/**
 * License listing endpoint template.
 *
 * @var string $list_endpoint
 * @var string $detail_endpoint
 * @var string $pdf_endpoint
 * @var string $nonce
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
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
            <h2><?php esc_html_e('Lisanslarım & E-PIN’lerim', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('Satın aldığınız tüm dijital kodları güvenle görüntüleyin, kopyalayın ve PDF olarak indirin.', 'oh-digital'); ?></p>
        </div>
        <div class="oh-account-section__actions">
            <button type="button" class="oh-button" data-action="download-all">
                <?php esc_html_e('Tümünü PDF İndir', 'oh-digital'); ?>
            </button>
        </div>
    </header>

    <div class="oh-account-section__filters">
        <div class="oh-form__row">
            <label class="oh-form__label" for="oh-license-search"><?php esc_html_e('Arama', 'oh-digital'); ?></label>
            <input id="oh-license-search" type="search" class="oh-form__input" data-filter="query" placeholder="<?php echo esc_attr__('Ürün, sipariş veya kod ara...', 'oh-digital'); ?>" />
        </div>
        <div class="oh-chip-group" data-filter="status">
            <button type="button" class="oh-chip is-active" data-value="all"><?php esc_html_e('Tümü', 'oh-digital'); ?></button>
            <button type="button" class="oh-chip" data-value="used"><?php esc_html_e('Tamamlandı', 'oh-digital'); ?></button>
            <button type="button" class="oh-chip" data-value="reserved"><?php esc_html_e('Bekliyor', 'oh-digital'); ?></button>
            <button type="button" class="oh-chip" data-value="revoked"><?php esc_html_e('İptal', 'oh-digital'); ?></button>
        </div>
    </div>

    <div class="oh-account-cards" data-role="list"></div>
    <div class="oh-account-empty" data-role="empty" hidden>
        <?php esc_html_e('Henüz görüntüleyebileceğiniz bir lisans yok. İlk dijital satın almanızı gerçekleştirin!', 'oh-digital'); ?>
    </div>
</div>
