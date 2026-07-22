<?php

namespace App\Service;

final class TotpEncryptionService
{
    private const CIPHER = 'aes-256-gcm';
    private const NONCE_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private string $key;

    public function __construct(string $appSecret)
    {
        $this->key = hash('sha256', $appSecret . ':totp-pepper-v1', true);
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($ciphertext === false) {
            throw new \RuntimeException('TOTP encryption failed');
        }
        return base64_encode($nonce . $tag . $ciphertext);
    }

    public function decrypt(string $encoded): string
    {
        $data = base64_decode($encoded, true);
        if ($data === false || strlen($data) < self::NONCE_LENGTH + self::TAG_LENGTH) {
            throw new \RuntimeException('Invalid encrypted TOTP secret');
        }
        $nonce = substr($data, 0, self::NONCE_LENGTH);
        $tag = substr($data, self::NONCE_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($data, self::NONCE_LENGTH + self::TAG_LENGTH);
        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('TOTP decryption failed');
        }
        return $plaintext;
    }
}
