<?php

namespace App\Models;

use App\Core\Database\Model;
use App\Core\Database\QueryBuilder; // import the class.
// use App\Core\Database\Connection; // Uncomment to build new Custom connection.
use PDO; // new PDO object

class Role extends Model
{
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM = "roles";

    public function __construct(PDO $pdo = null)
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
        
        $this->table = self::$tableM;
    }


    public static function getRoleById($id, $cols = false) {
        $selectCols = $cols ?: '*';
        $sql = 'SELECT '.$selectCols.' FROM '.self::$tableM.' WHERE id = ? LIMIT 1';
        $result = QueryBuilder::table(self::$tableM)->execQuery($sql, [$id], false, true, false);

        return $result;
    }

}