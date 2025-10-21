<?php
/** @var WC_Email $email */

defined('ABSPATH') || exit;

echo wp_strip_all_tags($email->get_heading()) . "\n\n";

echo sprintf(
    '"%1$s" ürünü için lisans havuzunda yalnızca %2$d kayıt kaldı.',
    $email->get_product_name(),
    (int) $remaining
) . "\n\n";

echo esc_html__('Yeni kodlar yüklemek için yönetim panelindeki Kod Havuzu sayfasını ziyaret edin.', 'oh-digital-delivery') . "\n";
