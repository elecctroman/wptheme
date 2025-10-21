<?php
/**
 * Checkout form override with payment tabs.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

wc_print_notices();

do_action('woocommerce_before_checkout_form', $checkout);

if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('Siparişi tamamlamak için giriş yapmalısınız.', 'oh-digital')));
    return;
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout oh-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
    <div class="oh-checkout__grid">
        <div class="oh-checkout__column oh-checkout__column--details">
            <?php if ($checkout->get_checkout_fields()) : ?>
                <?php do_action('woocommerce_checkout_before_customer_details'); ?>
                <div id="customer_details" class="oh-checkout__customer">
                    <div class="oh-checkout__section">
                        <h3><?php esc_html_e('Fatura Bilgileri', 'oh-digital'); ?></h3>
                        <?php do_action('woocommerce_checkout_billing'); ?>
                    </div>
                    <div class="oh-checkout__section">
                        <h3><?php esc_html_e('Teslimat Notu', 'oh-digital'); ?></h3>
                        <?php do_action('woocommerce_checkout_shipping'); ?>
                    </div>
                </div>
                <?php do_action('woocommerce_checkout_after_customer_details'); ?>
            <?php endif; ?>
        </div>
        <div class="oh-checkout__column oh-checkout__column--summary">
            <h3><?php esc_html_e('Sipariş Özeti', 'oh-digital'); ?></h3>
            <div class="oh-checkout__review">
                <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
                <div id="order_review" class="woocommerce-checkout-review-order" data-role="order-review">
                    <?php do_action('woocommerce_checkout_order_review'); ?>
                </div>
            </div>
        </div>
    </div>
    <?php do_action('woocommerce_checkout_before_submit'); ?>
    <div class="oh-checkout__submit">
        <?php do_action('woocommerce_review_order_before_submit'); ?>
        <?php echo apply_filters('woocommerce_order_button_html', '<button type="submit" class="oh-button oh-button--primary" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr__('Siparişi Tamamla', 'oh-digital') . '">' . esc_html__('Siparişi Tamamla', 'oh-digital') . '</button>'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php do_action('woocommerce_review_order_after_submit'); ?>
    </div>
    <?php do_action('woocommerce_checkout_after_submit'); ?>
</form>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
