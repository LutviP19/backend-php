<?php

namespace App\Core\Support;

/**
 * Config values from config directory.
 * @package Backend-PHP
 * @author Lutvi <lutvip19@gmail.com>
 */
class Config
{
    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key     Key konfigurasi (contoh: 'redis.cache.host')
     * @param mixed  $default Nilai fallback jika key tidak ditemukan
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $config = App::get('config');

        // Jika data config global kosong atau bukan array/ArrayAccess, langsung return default
        if (empty($config) || (!is_array($config) && !($config instanceof \ArrayAccess))) {
            return $default;
        }

        // Jalur Cepat (Fast-track): Jika key tidak menggunakan dot notation, langsung return nilainya
        if (strpos($key, '.') === false) {
            return isset($config[$key]) ? $config[$key] : $default;
        }

        // Jalur Dot Notation
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            // Periksa apakah segment ada di dalam array saat ini
            if (is_array($config) && isset($config[$segment])) {
                $config = $config[$segment];
            } elseif ($config instanceof \ArrayAccess && isset($config[$segment])) {
                $config = $config[$segment];
            } else {
                return $default; // Kembalikan nilai default/fallback jika key terputus
            }
        }

        return $config;
    }
}
