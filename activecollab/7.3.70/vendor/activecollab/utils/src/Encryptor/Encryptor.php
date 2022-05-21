<?php

/*
 * This file is part of the Active Collab Utils project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

namespace ActiveCollab\Encryptor;

use InvalidArgumentException;

/**
 * Encrypt and decrypt values.
 *
 * Inspired by Nelmio Security Bundle encryptor:
 * https://github.com/nelmio/NelmioSecurityBundle/blob/master/Encrypter.php
 *
 * and refactored to use OpenSSL based on this article:
 * https://paragonie.com/blog/2015/05/if-you-re-typing-word-mcrypt-into-your-code-you-re-doing-it-wrong
 *
 * @package ActiveCollab\Encryptor
 */
class Encryptor implements EncryptorInterface
{
    const METHOD = 'aes-256-cbc';

    private string $key;
    private int $iv_size;

    public function __construct(string $key)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Key needs to be a non-empty string value.');
        }

        $this->key = $key;
        $this->iv_size = openssl_cipher_iv_length(self::METHOD);
    }

    public function encrypt($value): string
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }

        $iv = openssl_random_pseudo_bytes($this->iv_size);

        return sprintf(
            '%s:%s',
            base64_encode(
                openssl_encrypt(
                    $value,
                    self::METHOD,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv
                )
            ),
            base64_encode($iv)
        );
    }

    public function decrypt(string $value)
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Value is required.');
        }

        $separated_data = explode(':', $value);

        if (count($separated_data) != 2) {
            throw new InvalidArgumentException('Separator not found in the encrypted data.');
        }

        return openssl_decrypt(
            base64_decode($separated_data[0], true),
            self::METHOD, $this->key,
            OPENSSL_RAW_DATA,
            base64_decode($separated_data[1], true)
        );
    }
}
