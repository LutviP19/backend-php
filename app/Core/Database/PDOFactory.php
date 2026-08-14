<?php 

namespace App\Core\Database;

use PDO;

class PDOFactory
{
    /**
     * Default active connection name used by Factory
     */
    private static string $activeConnection = 'mysql';

    /**
     * Set active connection name statically (Optional for multi-pool)
     */
    public static function setConnectionName(string $connectionName): void
    {
        self::$activeConnection = $connectionName;
    }

    /**
     * Creates a new PDO instance according to the target connection name.
     * Called internally by OpenSwoole ClientPool /DatabasePoolManager.
     * 
     * OpenSwoole ClientPool sends an integer (pool index position) to $connectionName,
     * so the parameter must accept string|int|null.
     * 
     * @param string|int|null $connectionName
     * @return PDO
     */
    public static function make(string|int|null $connectionName = 'mysql'): PDO
    {
        // If called by OpenSwoole ClientPool (an integer) or null,
        // use saved active connection name or fallback to default ('mysql').
        if (is_int($connectionName) || $connectionName === null) {
            $connectionName = self::$activeConnection;
        }

        // 1. If the Connection::fromConfig() method is available
        if (method_exists(Connection::class, 'fromConfig')) {
            return Connection::fromConfig($connectionName);
        }

        // 2. Fallback ke Connection::make()
        return Connection::make($connectionName);
    }
}