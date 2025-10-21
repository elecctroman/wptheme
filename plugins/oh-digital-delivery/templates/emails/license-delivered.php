<?php
/** @var WC_Email $email */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email->get_heading(), $email);
?>
<p><?php esc_html_e('Satın aldığınız dijital ürün(ler) aşağıda listelenmiştir.', 'oh-digital-delivery'); ?></p>

<?php if (! empty($payload['codes'])) : ?>
    <h3><?php esc_html_e('Kod Teslimatı', 'oh-digital-delivery'); ?></h3>
    <ul>
        <?php foreach ($payload['codes'] as $item) : ?>
            <li>
                <strong><?php echo esc_html($item['product_name']); ?>:</strong>
                <code><?php echo esc_html($item['code']); ?></code>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (! empty($payload['accounts'])) : ?>
    <h3><?php esc_html_e('Hesap Bilgileri', 'oh-digital-delivery'); ?></h3>
    <ul>
        <?php foreach ($payload['accounts'] as $item) : ?>
            <li>
                <strong><?php echo esc_html($item['product_name']); ?>:</strong>
                <code><?php echo esc_html($item['code']); ?></code>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p><?php esc_html_e('Kodlarınıza Hesabım > Lisanslarım alanından dilediğiniz zaman erişebilirsiniz.', 'oh-digital-delivery'); ?></p>

<?php
if (isset($order)) {
    do_action('woocommerce_email_order_details', $order, $email);
    do_action('woocommerce_email_order_meta', $order, $email);
    do_action('woocommerce_email_customer_details', $order, $email);
}

do_action('woocommerce_email_footer', $email);
