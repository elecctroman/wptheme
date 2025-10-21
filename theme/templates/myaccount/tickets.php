<?php
/**
 * Customer ticket center template.
 *
 * @var string $list_endpoint
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
    class="oh-account-section oh-account-section--tickets"
    data-tickets-endpoint="<?php echo esc_attr($list_endpoint); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-current-user="<?php echo isset($current_user) ? esc_attr((string) $current_user) : '0'; ?>"
>
    <header class="oh-account-section__header">
        <div>
            <h2><?php esc_html_e('Destek Taleplerim', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('Siparişlerinizle ilgili sorunları bu alandan iletin ve yanıtları takip edin.', 'oh-digital'); ?></p>
        </div>
    </header>

    <section class="oh-account-tickets__create">
        <h3><?php esc_html_e('Yeni Destek Talebi', 'oh-digital'); ?></h3>
        <form class="oh-form" data-role="ticket-create">
            <div class="oh-form__row">
                <label class="oh-form__label" for="oh-ticket-subject"><?php esc_html_e('Konu', 'oh-digital'); ?></label>
                <input id="oh-ticket-subject" name="subject" type="text" class="oh-form__input" required />
            </div>
            <div class="oh-form__row">
                <label class="oh-form__label" for="oh-ticket-order"><?php esc_html_e('Sipariş Numarası (Opsiyonel)', 'oh-digital'); ?></label>
                <input id="oh-ticket-order" name="order_id" type="number" class="oh-form__input" min="1" />
            </div>
            <div class="oh-form__row">
                <label class="oh-form__label" for="oh-ticket-message"><?php esc_html_e('Mesajınız', 'oh-digital'); ?></label>
                <textarea id="oh-ticket-message" name="message" rows="4" class="oh-form__textarea" required></textarea>
            </div>
            <div class="oh-form__actions">
                <button type="submit" class="oh-button oh-button--primary"><?php esc_html_e('Talep Oluştur', 'oh-digital'); ?></button>
            </div>
        </form>
    </section>

    <section class="oh-account-tickets__list">
        <div class="oh-account-section__header oh-account-section__header--sub">
            <div>
                <h3><?php esc_html_e('Taleplerim', 'oh-digital'); ?></h3>
                <p><?php esc_html_e('Son güncellenen destek talepleriniz aşağıda listelenir.', 'oh-digital'); ?></p>
            </div>
        </div>
        <div class="oh-ticket-list" data-role="ticket-list"></div>
    </section>
</div>
