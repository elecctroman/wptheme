<?php
/**
 * My Account dashboard override.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="oh-account-dashboard">
    <h2><?php esc_html_e('Hoş geldiniz', 'oh-digital'); ?>, <?php echo esc_html(wp_get_current_user()->display_name); ?></h2>
    <p><?php esc_html_e('Siparişleriniz, cüzdan bakiyeniz ve abonelikleriniz bu panelde.', 'oh-digital'); ?></p>
    <div class="oh-account-dashboard__grid">
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('orders')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Siparişlerim', 'oh-digital'); ?></span>
        </a>
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('wallet')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Cüzdanım', 'oh-digital'); ?></span>
            <span class="oh-account-card__value" data-wallet-balance>0.00</span>
        </a>
        <a class="oh-account-card" href="<?php echo esc_url(wc_get_endpoint_url('subscriptions')); ?>">
            <span class="oh-account-card__label"><?php esc_html_e('Aboneliklerim', 'oh-digital'); ?></span>
        </a>
    </div>
</div>
