<?php
/** @var WC_Email $email */

defined('ABSPATH') || exit;

echo wp_strip_all_tags($email->get_heading()) . "\n\n";

echo esc_html__('İade talebiniz işleme alındı ve aşağıdaki lisanslar iptal edildi.', 'oh-digital-delivery') . "\n";

if (! empty($codes)) {
    foreach ($codes as $code) {
        echo sprintf('%s', $code['code']) . "\n";
    }
}

echo "\n" . esc_html__('Yeni bir teslimat gerektiğinde destek ekibimizle iletişime geçebilirsiniz.', 'oh-digital-delivery') . "\n";
