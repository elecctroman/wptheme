<?php
/**
 * Wallet template fallback.
 *
 * @var array  $gateways
 * @var string $balance_endpoint
 * @var string $transactions_endpoint
 * @var string $nonce
 */
?>
<div
    class="oh-account-section oh-account-section--wallet"
    data-balance-endpoint="<?php echo esc_attr($balance_endpoint); ?>"
    data-transactions-endpoint="<?php echo esc_attr($transactions_endpoint); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
>
    <header class="oh-account-section__header">
        <div>
            <h2><?php esc_html_e('Bakiye', 'oh-digital-delivery'); ?></h2>
            <p><?php esc_html_e('Terra Wallet bakiyenizi görüntüleyin ve yönetin.', 'oh-digital-delivery'); ?></p>
        </div>
        <div class="oh-account-wallet__balance">
            <span><?php esc_html_e('Güncel Bakiye', 'oh-digital-delivery'); ?></span>
            <strong data-role="wallet-balance">0.00</strong>
        </div>
    </header>
    <section class="oh-account-wallet__topup">
        <h3><?php esc_html_e('Bakiye Yükle', 'oh-digital-delivery'); ?></h3>
        <form data-role="wallet-topup">
            <div class="oh-form-control">
                <label for="oh-wallet-amount"><?php esc_html_e('Tutar', 'oh-digital-delivery'); ?></label>
                <input id="oh-wallet-amount" name="amount" type="number" min="25" step="5" required />
            </div>
            <div class="oh-form-control">
                <label for="oh-wallet-gateway"><?php esc_html_e('Ödeme Yöntemi', 'oh-digital-delivery'); ?></label>
                <select id="oh-wallet-gateway" name="gateway" required>
                    <?php $default_gateway = ! empty($gateways) ? array_key_first($gateways) : ''; ?>
                    <?php foreach ($gateways as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $default_gateway); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Devam Et', 'oh-digital-delivery'); ?></button>
            <p class="oh-form-hint"><?php esc_html_e('Ödeme onaylandığında bakiye otomatik olarak hesabınıza eklenir.', 'oh-digital-delivery'); ?></p>
        </form>
    </section>
    <section class="oh-account-wallet__history">
        <h3><?php esc_html_e('Hareket Geçmişi', 'oh-digital-delivery'); ?></h3>
        <div class="oh-table" data-role="wallet-history"></div>
    </section>
</div>
