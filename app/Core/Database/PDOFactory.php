<?php

namespace App\Core\Database;

use PDO;

class PDOFactory
{
    /**
     * Method 'make' ini wajib ada karena dipanggil secara internal oleh ClientPool OpenSwoole
     */
    public static function make(): PDO
    {
        return Connection::make();
    }
}