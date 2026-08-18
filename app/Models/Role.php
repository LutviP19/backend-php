<?php

namespace App\Models;

use App\Core\Database\BaseModel;
use PDO; // new PDO object

class Role extends BaseModel
{
    protected static string $table = "roles";

    public function __construct(?PDO $pdo = null)
    {
        // // Custom connection
        // $driver = '';
        // $name = '';
        // $host = '';
        // $port = '';
        // $username = '';
        // $password = '';
        // $options = [];
        // $conn = $pdo ?: Connection::custom($driver, $name, $host, $port, $username, $password, $options);
        // parent::__construct($conn);

        // Default connection
        parent::__construct($pdo);
    }

    public static function getRoleById($id, $cols = null)
    {
        $selectCols = $cols ?? '*';

        return static::select($selectCols)->where('id', '=', $id)->first();
    }

}
