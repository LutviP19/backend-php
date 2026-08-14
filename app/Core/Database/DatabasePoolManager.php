<?php

namespace App\Core\Database;

use OpenSwoole\Core\Coroutine\Pool\ClientPool;
use PDO;
use RuntimeException;
use Throwable;

class DatabasePoolManager
{
    /**
     * Map of multiple ClientPools based on connection name
     * @var array<string, ClientPool>
     */
    private static array $pools = [];

    /**
     * Custom factory callback option per connection name (optional)
     * @var array<string, callable>
     */
    private static array $factories = [];

    /**
     * Initialize pool for specific connection (default: 'mysql').
     */
    public static function init(string $connectionName = 'mysql', int $capacity = 10, ?callable $factory = null): void
    {
        if (isset(self::$pools[$connectionName])) {
            return;
        }

        if ($factory !== null) {
            self::$factories[$connectionName] = $factory;
        }
        
        self::$pools[$connectionName] = new ClientPool(PDOFactory::class, $capacity);
    }

    /**
     * Borrows PDO connections directly from the pool (for fast queries)
     */
    public static function getConnection(string $connectionName = 'mysql'): PDO
    {
        return self::getPool($connectionName)->get();
    }

    /**
     * Create a new PDO instance according to the connection name
     */
    public static function createConnection(string $connectionName = 'mysql'): PDO
    {
        if (isset(self::$factories[$connectionName])) {
            return (self::$factories[$connectionName])();
        }

        if (class_exists(\App\Core\Database\Connection::class)) {
            return Connection::fromConfig($connectionName);
        }

        if (class_exists(\App\Core\Database\PDOFactory::class)) {
            return PDOFactory::make($connectionName);
        }

        throw new RuntimeException("Tidak ada pembuat koneksi (Connection/PDOFactory) yang terdeteksi.");
    }

    /**
     * Retrieve ClientPool instances based on connection name
     */
    public static function getPool(string $connectionName = 'mysql'): ClientPool
    {
        if (!isset(self::$pools[$connectionName])) {
            self::init($connectionName);
        }

        return self::$pools[$connectionName];
    }

    /**
     * Close and clean up specific ClientPool resources or the entire pool.
     * 
     * @param string|null $connectionName Connection name (e.g. 'mysql'). If null, it will close ALL registered pools.
     */
    public static function close(?string $connectionName = null): void
    {
        // 1. If the parameter is null, close all stored pools
        if ($connectionName === null) {
            foreach (array_keys(self::$pools) as $name) {
                self::close($name);
            }
            return;
        }

        // 2. If the connection name is listed, perform a ClientPool cleanup
        if (isset(self::$pools[$connectionName])) {
            $pool = self::$pools[$connectionName];

            try {
                // Call the close() method on the OpenSwoole ClientPool if available
                if (method_exists($pool, 'close')) {
                    $pool->close();
                }
            } catch (Throwable $e) {
                if (function_exists('write_log')) {
                    write_log('error', "[POOL:{$connectionName}] Error closing pool: " . $e->getMessage(), '/Core/Database/DatabasePoolManager.close', false);
                }
            } finally {
                // Remove reference pool & factory from static memory
                unset(self::$pools[$connectionName]);
                unset(self::$factories[$connectionName]);
            }
        }
    }

    /**
     * IMPORTANT: Use SHORT TIME connection with Auto-Release (Prevents Deadlock)
     * Never use raw $pool->get() without try...finally!
     */
    public static function useConnection(callable $callback, string $connectionName = 'mysql'): mixed
    {
        $pool = self::getPool($connectionName);
        
        // Take the connection from the pool
        $pdo = $pool->get();

        if (!$pdo instanceof PDO) {
            throw new RuntimeException("Gagal mengambil koneksi dari Database Pool (Timeout/Exhausted).");
        }

        try {
            return $callback($pdo);
        } finally {
            // Ensure it is ALWAYS returned to the pool no matter what happens (Error/Abort/Success)
            $pool->put($pdo);
        }
    }

    /**
     * Ultimate execution helper with Auto-Ping, Auto-Retry, Multi-Pool, & Safe Pool Return
     */
    public static function run(callable $callback, string $connectionName = 'mysql', int $maxRetries = 2): mixed
    {
        $pool = self::getPool($connectionName);
        $attempts = 0;

        while (true) {
            $attempts++;
            /** @var PDO $pdo */
            $pdo = $pool->get();

            // PING CHECK: If the connection is dead, replace with a new PDO
            try {
                $pdo->query('SELECT 1');
            } catch (Throwable $e) {
                // Create a new instance if the one popped from the pool is dead
                $pdo = self::createConnection($connectionName);
            }

            try {
                $result = $callback($pdo);
                // Return healthy connections to the pool
                $pool->put($pdo);
                return $result;

            } catch (Throwable $e) {
                // Handle Rollback if in transaction condition
                try {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                } catch (Throwable $t) {
                    // Ignore
                }

                if (self::isConnectionLost($e)) {
                    $freshPdo = self::createConnection($connectionName);

                    if ($attempts > $maxRetries) {
                        // Keep returning fresh instances so that the quota pool doesn't hang/leak
                        $pool->put($freshPdo);

                        if (function_exists('write_log')) {
                            write_log('error', "[POOL:{$connectionName}] Connection lost. Failed after {$attempts} attempts.", '/Core/Database/DatabasePoolManager.run', false);
                        }

                        throw new RuntimeException("Koneksi database terputus setelah {$maxRetries} kali percobaan.", 503, $e);
                    }

                    // Return fresh PDO then try again in the next iteration
                    $pool->put($freshPdo);

                    $jitter = rand(10, 50) / 1000;
                    $sleepDuration = (0.1 * $attempts) + $jitter;

                    if (class_exists(\OpenSwoole\Coroutine::class)) {
                        \OpenSwoole\Coroutine::sleep($sleepDuration);
                    }

                    continue;
                }

                // Non-connection error (Syntax/Validation/etc), return PDO to pool & throw exception
                $pool->put($pdo);
                throw $e;
            }
        }
    }

    /**
     * Securely wrap DB Transaction execution in OpenSwoole Pool.
     * If the connection is lost in the middle of a transaction, it WILL NOT be retried per-query, 
     * instead the connection is discarded, cleared, and throws an Exception.
     * 
     * @param callable $callback fn(PDO $pdo)
     * @param string $connectionName
     * @return mixed
     * @throws Throwable
     */
    public static function transaction(callable $callback, string $connectionName = 'mysql'): mixed
    {
        $pool = self::getPool($connectionName);
        
        /** @var PDO $pdo */
        $pdo = $pool->get();

        // Ping check before initiating a transaction
        try {
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            $pdo = self::createConnection($connectionName);
        }

        try {
            // 1. Start a PDO Transaction
            $pdo->beginTransaction();

            // 2. Execute the business logic inside the closure
            $result = $callback($pdo);

            // 3. Commit if all queries are successful
            $pdo->commit();

            // 4. Restore a healthy connection to the pool
            $pool->put($pdo);

            return $result;

        } catch (Throwable $e) {
            // A. Check whether PDO is still connected and is in transaction status
            try {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (Throwable $rollbackException) {
                if (function_exists('write_log')) {
                    write_log('error', "[POOL:{$connectionName}] Failed to rollback transaction: " . $rollbackException->getMessage(), '/Core/Database/DatabasePoolManager.transaction', false);
                }
            }

            // B. If the error is caused by a CONNECTION BREAK
            if (self::isConnectionLost($e)) {
                if (function_exists('write_log')) {
                    write_log('error', "[POOL:{$connectionName}] Connection dropped during TRANSACTION! Destroying stale PDO instance. Error: " . $e->getMessage(), '/Core/Database/DatabasePoolManager.transaction', false);
                }

                // Return new fresh PDO to the pool to maintain pool quota/capacity
                $freshPdo = self::createConnection($connectionName);
                $pool->put($freshPdo);

                throw new RuntimeException("Transaksi dibatalkan karena koneksi database terputus. Silakan coba kembali request Anda.", 503, $e);
            }

            // C. If the error is normal (Not a broken connection, for example: Constraint Violation /Validation)
            $pool->put($pdo);
            throw $e;
        }
    }

    /**
     * Detect whether Throwable is caused by a dropped connection
     */
    private static function isConnectionLost(Throwable $e): bool
    {
        $message = $e->getMessage();

        // List of broken error messages/queries for MySQL & PostgreSQL
        $connectionLostMessages = [
            // --- Common / Network / General PDO Errors ---
            'Connection refused',
            'Connection reset by peer',
            'Error while sending',
            'HY000',
            'decryption failed or bad record mac',
            'SSL connection has been closed unexpectedly',

            // --- MySQL Specific Errors ---
            '2006', // MySQL server has gone away
            '2013', // Lost connection to MySQL server during query
            '2002', // Can't connect to local MySQL server
            'MySQL server has gone away',
            'Server shutdown in progress',
            'packets out of order',

            // --- PostgreSQL Specific Errors ---
            '57P01', // admin_shutdown (terminasi paksa oleh DBA/server shutdown)
            '57P02', // crash_shutdown
            '57P03', // cannot_connect_now (server dalam fase startup/recovery)
            '08000', // connection_exception
            '08003', // connection_does_not_exist
            '08006', // connection_failure
            '08001', // sqlclient_unable_to_establish_sqlconnection
            '08004', // sqlserver_rejected_establishment_of_sqlconnection
            'server closed the connection unexpectedly',
            'terminating connection due to administrator command',
            'terminating connection due to unexpected postmaster exit',
            'could not connect to server',
            'no connection to the server',
            'FATAL: the database system is shutting down',
            'FATAL: the database system is starting up',
            'FATAL: 57P01',
        ];

        foreach ($connectionLostMessages as $lostMessage) {
            if (stripos($message, $lostMessage) !== false) {
                return true;
            }
        }

        return false;
    }
}