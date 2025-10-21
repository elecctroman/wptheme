<?php
/**
 * Ticket center fallback template.
 *
 * @var string $list_endpoint
 * @var string $nonce
 */
?>
<div
    class="oh-account-section oh-account-section--tickets"
    data-tickets-endpoint="<?php echo esc_attr($list_endpoint); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-current-user="<?php echo isset($current_user) ? esc_attr((string) $current_user) : '0'; ?>"
>
    <header class="oh-account-section__header">
        <div>
            <h2><?php esc_html_e('Destek Taleplerim', 'oh-digital-delivery'); ?></h2>
            <p><?php esc_html_e('Sorularınızı iletmek için yeni talep oluşturun veya mevcut talepleri inceleyin.', 'oh-digital-delivery'); ?></p>
        </div>
    </header>
    <section class="oh-account-tickets__create">
        <h3><?php esc_html_e('Yeni Talep', 'oh-digital-delivery'); ?></h3>
        <form data-role="ticket-create">
            <div class="oh-form-control">
                <label for="oh-ticket-subject"><?php esc_html_e('Konu', 'oh-digital-delivery'); ?></label>
                <input id="oh-ticket-subject" name="subject" type="text" required />
            </div>
            <div class="oh-form-control">
                <label for="oh-ticket-order"><?php esc_html_e('Sipariş Referansı (opsiyonel)', 'oh-digital-delivery'); ?></label>
                <input id="oh-ticket-order" name="order_id" type="number" min="1" />
            </div>
            <div class="oh-form-control">
                <label for="oh-ticket-message"><?php esc_html_e('Mesajınız', 'oh-digital-delivery'); ?></label>
                <textarea id="oh-ticket-message" name="message" rows="4" required></textarea>
            </div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Talep Oluştur', 'oh-digital-delivery'); ?></button>
        </form>
    </section>
    <section class="oh-account-tickets__list">
        <h3><?php esc_html_e('Aktif Talepler', 'oh-digital-delivery'); ?></h3>
        <div class="oh-ticket-list" data-role="ticket-list"></div>
    </section>
</div>
