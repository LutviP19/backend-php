<?php

namespace App\Core\Security;

use App\Core\Support\Config;
use Exception;
use RuntimeException;

class Encryption
{
    protected $encryptionKey;
    protected $cipher;

    /**
     * The previous / legacy encryption keys.
     *
     * @var array
     */
    protected $previousKeys = [];

    /**
     * The supported cipher algorithms and their properties.
     *
     * @var array
     */
    private static $supportedCiphers = [
        'aes-128-cbc' => ['size' => 16, 'aead' => false],
        'aes-256-cbc' => ['size' => 32, 'aead' => false],
        'aes-128-gcm' => ['size' => 16, 'aead' => true],
        'aes-256-gcm' => ['size' => 32, 'aead' => true],
    ];

    public function __construct(#[\SensitiveParameter] $key = null, $cipher = 'aes-256-cbc')
    {
        $key = str_replace('base64:', '', (string) $key ?: Config::get('app.key'));
        $key = base64_decode($key);
        $this->encryptionKey = $key;

        if (!$this->encryptionKey) {
            throw new Exception("Encryption key is not set in environment variables.");
        }

        if (! static::supported($key, $cipher)) {
            $ciphers = implode(', ', array_keys(self::$supportedCiphers));

            throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
        }

        $this->cipher = $cipher;
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @param  int  $length (32-16)
     * @return string
     */
    public static function generateKey($cipher = 'aes-256-cbc')
    {
        return 'base64:' . base64_encode(random_bytes(self::$supportedCiphers[strtolower($cipher)]['size'] ?? 32));
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        if (! isset(self::$supportedCiphers[strtolower($cipher)])) {
            return false;
        }

        return mb_strlen($key, '8bit') === self::$supportedCiphers[strtolower($cipher)]['size'];
    }

    /**
     * Encrypts data.
     */
    public function encrypt(#[\SensitiveParameter] string $data): string
    {
        $iv = random_bytes(openssl_cipher_iv_length(strtolower($this->cipher)));

        $value = \openssl_encrypt(
            $data,
            strtolower($this->cipher),
            $this->encryptionKey,
            0,
            $iv,
            $tag
        );

        if ($value === false) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ?? '');
        $mac = self::$supportedCiphers[strtolower($this->cipher)]['aead']
            ? '' // For AEAD-algorithms, the tag / MAC is returned by openssl_encrypt...
            : $this->hash($iv, $value, $this->encryptionKey);

        $json = json_encode(compact('iv', 'value', 'mac', 'tag'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Could not encrypt the data.');
        }

        return base64_encode($json);
    }

    /**
     * Decrypts encrypted data.
     */
    public function decrypt(#[\SensitiveParameter] string $encryptedData): string
    {
        $payload = $this->getJsonPayload($encryptedData);

        $iv = base64_decode($payload['iv']);

        $this->ensureTagIsValid(
            $tag = empty($payload['tag']) ? null : base64_decode($payload['tag'])
        );

        $foundValidMac = false;

        foreach ($this->getAllKeys() as $key) {
            if (
                $this->shouldValidateMac() &&
                ! ($foundValidMac = $foundValidMac || $this->validMacForKey($payload, $key))
            ) {
                continue;
            }

            $decrypted = \openssl_decrypt(
                $payload['value'],
                strtolower($this->cipher),
                $key,
                0,
                $iv,
                $tag ?? ''
            );

            if ($decrypted !== false) {
                break;
            }
        }

        if ($this->shouldValidateMac() && ! $foundValidMac) {
            throw new RuntimeException('The MAC is invalid.');
        }

        if (($decrypted ?? false) === false) {
            throw new RuntimeException('Could not decrypt the data.');
        }

        return $decrypted;
    }

    /**
     * Match encrypted data.
     */
    public function match(#[\SensitiveParameter] $value, $encryptedData): bool
    {
        if (empty($value) || empty($encryptedData))
            return false;

        $encryptedData = $this->decrypt($encryptedData);

        return $value === $encryptedData;
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
        return hash_hmac('sha256', $iv . $value, $key);
    }

    /**
     * Determine if we should validate the MAC while decrypting.
     *
     * @return bool
     */
    protected function shouldValidateMac()
    {
        return ! self::$supportedCiphers[strtolower($this->cipher)]['aead'];
    }

    /**
     * Get the JSON array from the given payload.
     *
     * @param  string  $payload
     * @return array
     *
     * @throws Exception
     */
    protected function getJsonPayload($payload)
    {
        if (! is_string($payload)) {
            throw new RuntimeException('The payload is invalid.');
        }

        $payload = json_decode(base64_decode($payload), true);

        // If the payload is not valid JSON or does not have the proper keys set we will
        // assume it is invalid and bail out of the routine since we will not be able
        // to decrypt the given value. We'll also check the MAC for this encryption.
        if (! $this->validPayload($payload)) {
            throw new RuntimeException('The payload is invalid.');
        }

        return $payload;
    }

    /**
     * Determine if the MAC for the given payload is valid for the primary key.
     *
     * @param  array  $payload
     * @return bool
     */
    protected function validMac(#[\SensitiveParameter] array $payload)
    {
        return $this->validMacForKey($payload, $this->encryptionKey);
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
            $this->hash($payload['iv'], $payload['value'], $key),
            $payload['mac']
        );
    }

    /**
     * Ensure the given tag is a valid tag given the selected cipher.
     *
     * @param  string  $tag
     * @return void
     */
    protected function ensureTagIsValid($tag)
    {
        if (self::$supportedCiphers[strtolower($this->cipher)]['aead'] && strlen($tag) !== 16) {
            throw new RuntimeException('Could not decrypt the data.');
        }

        if (! self::$supportedCiphers[strtolower($this->cipher)]['aead'] && is_string($tag)) {
            throw new RuntimeException('Unable to use tag because the cipher algorithm does not support AEAD.');
        }
    }

    /**
     * Get the current encryption key and all previous encryption keys.
     *
     * @return array
     */
    public function getAllKeys()
    {
        return [$this->encryptionKey, ...$this->previousKeys];
    }

    /**
     * Get the previous encryption keys.
     *
     * @return array
     */
    public function getPreviousKeys()
    {
        return $this->previousKeys;
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

    /**
     * Set the previous / legacy encryption keys that should be utilized if decryption fails.
     *
     * @param  array  $keys
     * @return $this
     */
    public function previousKeys(array $keys)
    {
        foreach ($keys as $key) {
            if (! static::supported($key, $this->cipher)) {
                $ciphers = implode(', ', array_keys(self::$supportedCiphers));

                throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
            }
        }

        $this->previousKeys = $keys;

        return $this;
    }
}
