<?php
/**
 * My Account dashboard override with quick shortcuts.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$user = wp_get_current_user();
?>
<div class="oh-account-dashboard">
    <h2><?php printf(esc_html__('Merhaba %s 👋', 'oh-digital'), esc_html($user->display_name)); ?></h2>
    <p><?php esc_html_e('Siparişlerinizi, lisans teslimatlarınızı ve bakiye hareketlerinizi buradan kontrol edebilirsiniz.', 'oh-digital'); ?></p>

    <div class="oh-account-dashboard__grid">
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('orders')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Siparişlerim', 'oh-digital'); ?></span>
            <span class="oh-account-card__icon" aria-hidden="true">📦</span>
        </a>
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('licenses')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Lisanslarım', 'oh-digital'); ?></span>
            <span class="oh-account-card__icon" aria-hidden="true">🔐</span>
        </a>
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('wallet')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Bakiye', 'oh-digital'); ?></span>
            <span class="oh-account-card__value" data-role="wallet-balance">0.00</span>
        </a>
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('tickets')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Destek Taleplerim', 'oh-digital'); ?></span>
            <span class="oh-account-card__icon" aria-hidden="true">💬</span>
        </a>
    </div>
</div>
