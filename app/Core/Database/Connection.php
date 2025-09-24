<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\Support\Config;

/**
 * Connection Class
 *  
 */
class Connection
{    
    /**
     * Method make
     *
     * @return void
     */
    public static function make()
    {
        try {
            $driver = Config::get('default_db');


            if ($driver !== 'sqlite') {
                $name     = Config::get("database.{$driver}.dbname");
                $host     = Config::get("database.{$driver}.host");
                $port     = Config::get("database.{$driver}.port");
                $username = Config::get("database.{$driver}.username");
                $password = Config::get("database.{$driver}.password");
                $options  = Config::get("database.{$driver}.options");
                // dd("{$driver}:host={$host};port={$port};dbname={$name}");

                $pdo = new PDO(
                    "{$driver}:host={$host};port={$port};dbname={$name}",
                    $username,
                    $password,
                    $options
                );

            } else {
                $databaseFile = Config::get("database.{$driver}.dbname");
                // dd("sqlite:{$databaseFile}");

                $pdo = new PDO("sqlite:{$databaseFile}");
                // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode for better error handling
            }
            // dd($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));


            return $pdo;

        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

}
