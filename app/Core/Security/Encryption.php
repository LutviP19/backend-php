<?php

namespace App\Core\Security;

use Exception;

class Encryption
{
    protected $encryptionKey;
    protected $cipher;

    public function __construct()
    {
        $this->encryptionKey = getenv('ENCRYPTION_KEY');
        $this->cipher = "AES-256-CBC";

        if (!$this->encryptionKey) {
            throw new Exception("Encryption key is not set in environment variables.");
        }
    }

    /**
     * Encrypts data.
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(strtolower($this->cipher)));

        $value = \openssl_encrypt(
            $data,
            strtolower($this->cipher), $this->encryptionKey, 0, $iv, $tag
        );

        if ($value === false) {
            throw new EncryptException('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ?? '');
        $mac = $this->hash($iv, $value, $this->encryptionKey);

        $json = json_encode(compact('iv', 'value', 'mac', 'tag'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Decrypts encrypted data.
     */
    public function decrypt(string $encryptedData): string
    {
        $payload = $this->getJsonPayload($encryptedData);
        // \App\Core\Support\Log::info($payload);

        $iv = base64_decode($payload['iv']);
        $tag = empty($payload['tag']) ? null : base64_decode($payload['tag']);

        $decrypted = \openssl_decrypt(
            $payload['value'], strtolower($this->cipher), $this->encryptionKey, 0, $iv, $tag ?? ''
        );

        if (($decrypted ?? false) === false) {
            throw new Exception('Could not decrypt the data.');
        }

        return $decrypted;
    }

    /**
     * Match encrypted data.
     */
    public function match(string $data, string $encryptedData): bool
    {
        $encryptedData = $this->decrypt($encryptedData);

        return $data === $encryptedData;
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  int  $length (32-16)
     * @return string
     */
    public static function generateKey($length = 32)
    {
        return base64_encode(random_bytes($length));
    }

    /**
     * Create a MAC for the given value.
     *
     * @param  string  $iv
     * @param  mixed  $value
     * @param  string  $key
     * @return string
     */
    protected function hash(#[\SensitiveParameter] $iv, #[\SensitiveParameter] $value, #[\SensitiveParameter] $key)
    {
        return hash_hmac('sha256', $iv.$value, $key);
    }

     /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     *
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function getJsonPayload($payload)
    {
        if (! is_string($payload)) {
            throw new Exception('The payload is invalid.');
        }

        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (! $this->validPayload($payload)) {
            throw new Exception('The payload is invalid.');
        }

        return $payload;
    }

    /**
     * Determine if the MAC is valid for the given payload and key.
     *
     * @param  array  $payload
     * @param  string  $key
     * @return bool
     */
    protected function validMacForKey(#[\SensitiveParameter] $payload, $key)
    {
        return hash_equals(
            $this->hash($payload['iv'], $payload['value'], $key), $payload['mac']
        );
    }

    /**
     * Verify that the encryption payload is valid.
     *
     * @param  mixed  $payload
     * @return bool
     */
    protected function validPayload($payload)
    {
        if (! is_array($payload)) {
            return false;
        }

        foreach (['iv', 'value', 'mac'] as $item) {
            if (! isset($payload[$item]) || ! is_string($payload[$item])) {
                return false;
            }
        }

        if (isset($payload['tag']) && ! is_string($payload['tag'])) {
            return false;
        }

        return strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length(strtolower($this->cipher));
    }
}
