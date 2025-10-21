<?php
/**
 * Wallet endpoint placeholder referencing Terra Wallet integration.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="oh-account-wallet">
    <h2><?php esc_html_e('Cüzdan Bakiyesi', 'oh-digital'); ?></h2>
    <p><?php esc_html_e('Terra Wallet ile bakiye yükleyin ve alışverişlerinizde kullanın.', 'oh-digital'); ?></p>
    <div class="oh-account-wallet__balance" data-wallet-balance>0.00</div>
    <form class="oh-account-wallet__topup" method="post">
        <label for="oh-wallet-amount"><?php esc_html_e('Yükleme Tutarı', 'oh-digital'); ?></label>
        <input id="oh-wallet-amount" type="number" name="wallet_amount" min="50" step="10" />
        <button type="submit" class="oh-button oh-button--primary"><?php esc_html_e('Bakiye Yükle', 'oh-digital'); ?></button>
    </form>
    <div class="oh-account-wallet__history">
        <h3><?php esc_html_e('İşlem Geçmişi', 'oh-digital'); ?></h3>
        <p><?php esc_html_e('Terra Wallet API entegrasyonu sonrasında işlem geçmişi burada listelenecek.', 'oh-digital'); ?></p>
    </div>
</div>
