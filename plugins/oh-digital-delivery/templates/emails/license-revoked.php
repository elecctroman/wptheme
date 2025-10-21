<?php
/** @var WC_Email $email */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email->get_heading(), $email);
?>
<p><?php esc_html_e('İade talebiniz işleme alındı ve aşağıdaki lisanslar iptal edildi.', 'oh-digital-delivery'); ?></p>

<?php if (! empty($codes)) : ?>
    <ul>
        <?php foreach ($codes as $code) : ?>
            <li><code><?php echo esc_html($code['code']); ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<p><?php esc_html_e('Yeni bir teslimat gerektiğinde destek ekibimizle iletişime geçebilirsiniz.', 'oh-digital-delivery'); ?></p>

<?php
do_action('woocommerce_email_footer', $email);
