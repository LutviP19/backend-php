<?php

define('BASE_PATH', str_replace('config', '' , __DIR__));

/**
 * Config values for our application.
 * 
 * @return array
 */
return [

    /**
     * Application config details.
     */
    'app' => [
        'name' => env('APP_NAME', 'Backend PHP'),
        'key' => env('ENCRYPTION_KEY'),
        'hash_key' => env('HASH_KEY'),
        'token' => env('HEADER_TOKEN'),
        'url' => env('APP_URL', 'http://localhost'),
        'env' => env('APP_ENV', 'production'),
        'debug' => (bool) env('APP_DEBUG', false),
        'logdir' => __DIR__.'/../storage/logs/',
    ],

    'trusted_ips' => [
        '::1',
        '127.0.0.1',
    ],

    'valid_headers' => [
        // 'Accept',
        'Content-Type',
        'X-Api-Token',
    ],

    /**
     * Database Credentials.
     */
    'default_db' => env('DB_CONNECTION', 'sqlite'),

    'database' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'dbname' =>  database_path(env('DB_DATABASE','database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'dbname'   => env('DB_DATABASE', 'backend_php'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]) : []
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'dbname' => env('DB_DATABASE', 'backend_php'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'dbname' => env('DB_DATABASE', 'backend_php'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'dbname' => env('DB_DATABASE', 'backend_php'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],
    ],

    /**
     * Message Broker Credentials.
     */
    'default_mb' => env('MB_CONNECTION', 'rabbitmq'),

    'broker' => [
        'rabbitmq'  => [
            'host' => env('MB_HOST', '127.0.0.1'),
            'port' => env('MB_PORT', '5672'),
            'username' => env('MB_USERNAME', 'guest'),
            'password' => env('MB_PASSWORD', 'guest'),
            'queue_name' => env('MB_QUEUE_NAME', 'backend_php-queue'),
        ],
    ],

    /**
     * Cookies
     */
    'cookie' => [
        'csrf_token' => 'token'
    ],

    /**
     * Session
     */
    'session' => [
        'csrf_token' => 'csrf_token',
    ],

];