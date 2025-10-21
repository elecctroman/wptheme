<?php
/**
 * Provides AES-256 encryption helpers for license codes.
 *
 * @package OH\DigitalDelivery
 */

declare(strict_types=1);

namespace OH\DigitalDelivery;

use RuntimeException;

class Encryption
{
    private const CIPHER = 'aes-256-gcm';

    public static function encrypt(string $value): string
    {
        $key = self::get_key();
        $iv  = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';

        $encrypted = openssl_encrypt($value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (false === $encrypted) {
            throw new RuntimeException('Failed to encrypt license code.');
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    public static function decrypt(string $payload): string
    {
        $key = self::get_key();
        $data = base64_decode($payload, true);
        if (false === $data) {
            throw new RuntimeException('Encrypted payload cannot be decoded.');
        }

        $iv_length = openssl_cipher_iv_length(self::CIPHER);
        $iv        = substr($data, 0, $iv_length);
        $tag       = substr($data, $iv_length, 16);
        $cipher    = substr($data, $iv_length + 16);

        $decrypted = openssl_decrypt($cipher, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if (false === $decrypted) {
            throw new RuntimeException('Failed to decrypt license code.');
        }

        return $decrypted;
    }

    private static function get_key(): string
    {
        $salt = wp_salt('auth');

        return hash('sha256', $salt . AUTH_KEY, true);
    }
}
