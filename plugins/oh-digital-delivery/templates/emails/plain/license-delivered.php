<?php
/** @var WC_Email $email */

defined('ABSPATH') || exit;

echo wp_strip_all_tags($email->get_heading()) . "\n\n";

echo esc_html__('Satın aldığınız dijital ürün(ler) aşağıda listelenmiştir.', 'oh-digital-delivery') . "\n\n";

if (! empty($payload['codes'])) {
    echo esc_html__('Kod Teslimatı', 'oh-digital-delivery') . "\n";
    foreach ($payload['codes'] as $item) {
        echo sprintf('%s: %s', $item['product_name'], $item['code']) . "\n";
    }
    echo "\n";
}

if (! empty($payload['accounts'])) {
    echo esc_html__('Hesap Bilgileri', 'oh-digital-delivery') . "\n";
    foreach ($payload['accounts'] as $item) {
        echo sprintf('%s: %s', $item['product_name'], $item['code']) . "\n";
    }
    echo "\n";
}

echo esc_html__('Kodlarınıza Hesabım > Lisanslarım alanından dilediğiniz zaman erişebilirsiniz.', 'oh-digital-delivery') . "\n\n";

if (isset($order)) {
    echo "----------\n";
    echo esc_html__('Sipariş Özeti', 'oh-digital-delivery') . "\n";
    echo wc_get_email_order_items($order, ['plain_text' => true]);
}
