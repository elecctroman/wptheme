<?php
/** @var WC_Email $email */

defined('ABSPATH') || exit;

do_action('woocommerce_email_header', $email->get_heading(), $email);
?>
<p>
    <?php
    printf(
        esc_html__('"%1$s" ürünü için lisans havuzunda yalnızca %2$d kayıt kaldı.', 'oh-digital-delivery'),
        esc_html($email->get_product_name()),
        (int) $remaining
    );
    ?>
</p>
<p><?php esc_html_e('Yeni kodlar yüklemek için yönetim panelindeki Kod Havuzu sayfasını ziyaret edin.', 'oh-digital-delivery'); ?></p>
<?php
do_action('woocommerce_email_footer', $email);
