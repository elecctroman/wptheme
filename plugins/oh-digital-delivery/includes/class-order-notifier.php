<?php
/**
 * Dispatches WooCommerce emails for delivery events.
 *
 * @package OH\DigitalDelivery
 */

declare(strict_types=1);

namespace OH\DigitalDelivery;

use OH\DigitalDelivery\Emails\License_Delivered_Email;
use OH\DigitalDelivery\Emails\License_Revoked_Email;
use OH\DigitalDelivery\Emails\Stock_Low_Email;
use WC_Order;

class Order_Notifier
{
    public function send_delivery_email(WC_Order $order, array $payload): void
    {
        $email = $this->get_email(License_Delivered_Email::class);
        if ($email) {
            $email->trigger($order->get_id(), $payload);
        }
    }

    public function notify_stock_low(int $product_id, int $remaining): void
    {
        $email = $this->get_email(Stock_Low_Email::class);
        if ($email) {
            $email->trigger($product_id, $remaining);
        }
    }

    public function send_revoked_email(WC_Order $order, array $codes): void
    {
        $email = $this->get_email(License_Revoked_Email::class);
        if ($email) {
            $email->trigger($order->get_id(), $codes);
        }
    }

    private function get_email(string $class)
    {
        if (! function_exists('WC')) {
            return null;
        }

        $mailer = WC()->mailer();
        if (! $mailer) {
            return null;
        }

        $emails = $mailer->get_emails();

        return $emails[$class] ?? null;
    }
}
