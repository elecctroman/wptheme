<?php
/**
 * Generates lightweight PDF exports for delivered licenses.
 *
 * @package OH\DigitalDelivery\Service
 */

declare(strict_types=1);

namespace OH\DigitalDelivery\Service;

use RuntimeException;
use WC_Order;
use function __;
use function base64_encode;
use function sprintf;
use function wc_get_order;
use function wc_get_product;

class PDF_Generator
{
    private Delivery_Service $delivery_service;

    public function __construct(Delivery_Service $delivery_service)
    {
        $this->delivery_service = $delivery_service;
    }

    /**
     * Returns a base64 encoded inline PDF for a given order.
     */
    public function generate_for_order(int $order_id, int $user_id): array
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            throw new RuntimeException(__('Sipariş bulunamadı.', 'oh-digital-delivery'));
        }

        if ((int) $order->get_user_id() !== $user_id && ! current_user_can('manage_woocommerce')) {
            throw new RuntimeException(__('Bu siparişe erişim yetkiniz yok.', 'oh-digital-delivery'));
        }

        $items    = $this->delivery_service->get_licenses_for_order($order_id);
        $document = $this->build_pdf_markup($order, $items);
        $encoded  = base64_encode($document);

        return [
            'filename' => sprintf('oh-license-%d.pdf', $order_id),
            'data_url' => 'data:application/pdf;base64,' . $encoded,
        ];
    }

    private function build_pdf_markup(WC_Order $order, array $items): string
    {
        $lines   = [];
        $lines[] = __('OH Digital Lisans Özeti', 'oh-digital-delivery');
        $lines[] = sprintf(__('Sipariş #%d', 'oh-digital-delivery'), $order->get_id());
        $lines[] = sprintf(__('Tarih: %s', 'oh-digital-delivery'), $order->get_date_created()->date_i18n('Y-m-d H:i'));
        $lines[] = '';

        foreach ($items as $item) {
            $product_name = __('Ürün', 'oh-digital-delivery');
            $product      = wc_get_product($item['product_id']);
            if ($product) {
                $product_name = $product->get_name();
            }

            $lines[] = $product_name;
            $lines[] = sprintf(__('Kod: %s', 'oh-digital-delivery'), $item['code']);
            $lines[] = '';
        }

        $content_stream = 'BT /F1 12 Tf 60 760 Td ';
        $first          = true;
        foreach ($lines as $line) {
            if (! $first) {
                $content_stream .= 'T* ';
            }

            $first         = false;
            $content_stream .= '(' . $this->escape_pdf_text($line) . ') Tj ';
        }

        $content_stream .= 'ET';

        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj',
            sprintf('4 0 obj << /Length %d >> stream\n%s\nendstream\nendobj', strlen($content_stream), $content_stream),
            '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
        ];

        $pdf     = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf      .= $object . "\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref\n0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }

        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xref . "\n";
        $pdf .= '%%EOF';

        return $pdf;
    }

    private function escape_pdf_text(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);

        return $text;
    }
}
