<?php
/**
 * Terra Wallet account endpoint styling.
 *
 * @var array  $gateways
 * @var string $balance_endpoint
 * @var string $transactions_endpoint
 * @var string $nonce
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$default_gateway = ! empty($gateways) ? array_key_first($gateways) : '';
?>
<div
    class="oh-account-section oh-account-section--wallet"
    data-balance-endpoint="<?php echo esc_attr($balance_endpoint); ?>"
    data-transactions-endpoint="<?php echo esc_attr($transactions_endpoint); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
>
    <header class="oh-account-section__header">
        <div>
            <h2><?php esc_html_e('Bakiye', 'oh-digital'); ?></h2>
            <p><?php esc_html_e('Terra Wallet bakiyenizi yönetin, hızlıca bakiye yükleyin ve hareketlerinizi takip edin.', 'oh-digital'); ?></p>
        </div>
        <div class="oh-account-wallet__balance">
            <span><?php esc_html_e('Güncel Bakiye', 'oh-digital'); ?></span>
            <strong data-role="wallet-balance">0.00</strong>
        </div>
    </header>

    <section class="oh-account-wallet__topup">
        <h3><?php esc_html_e('Bakiye Yükle', 'oh-digital'); ?></h3>
        <form class="oh-form" data-role="wallet-topup">
            <div class="oh-form__row">
                <label for="oh-wallet-amount" class="oh-form__label"><?php esc_html_e('Tutar', 'oh-digital'); ?></label>
                <input id="oh-wallet-amount" name="amount" type="number" min="25" step="5" required class="oh-form__input" placeholder="100" />
            </div>
            <div class="oh-form__row">
                <label for="oh-wallet-gateway" class="oh-form__label"><?php esc_html_e('Ödeme Yöntemi', 'oh-digital'); ?></label>
                <div class="oh-pill-select">
                    <?php foreach ($gateways as $key => $label) : ?>
                        <label class="oh-pill-select__option">
                            <input type="radio" name="gateway" value="<?php echo esc_attr($key); ?>" <?php checked($key, $default_gateway); ?> />
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="oh-form__actions">
                <button type="submit" class="oh-button oh-button--primary">
                    <?php esc_html_e('Ödeme Adımına Geç', 'oh-digital'); ?>
                </button>
                <p class="oh-form__hint"><?php esc_html_e('Ödeme sağlayıcısında işlemi tamamladıktan sonra bakiye otomatik olarak hesabınıza işlenir.', 'oh-digital'); ?></p>
            </div>
        </form>
    </section>

    <section class="oh-account-wallet__history">
        <div class="oh-account-section__header oh-account-section__header--sub">
            <div>
                <h3><?php esc_html_e('Hareket Geçmişi', 'oh-digital'); ?></h3>
                <p><?php esc_html_e('Bakiye yüklemeleri ve harcamalarınız kronolojik olarak listelenir.', 'oh-digital'); ?></p>
            </div>
        </div>
        <div class="oh-table" data-role="wallet-history"></div>
    </section>
</div>
