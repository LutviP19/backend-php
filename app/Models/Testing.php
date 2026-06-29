<?php

namespace App\Models;

use App\Core\Database\Model;
// use App\Core\Database\QueryBuilder; // import the class.
use App\Core\Database\Connection; // Uncomment to build new Custom connection.
use PDO; // new PDO object

class Testing extends Model
{
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected $table = "employees";
    protected static $tableM = "employees";

    protected ?\PDO $pdo = null;

    public function __construct(?PDO $pdo = null)
    {
        // Custom connection
        $driver = 'mysql';
        $dbname = 'employees';
        $host = '127.0.0.1';
        $port = '3306';
        $username = 'root';
        $password = '';
        $options = [];
        $conn = $pdo ?: Connection::custom($driver, $dbname, $host, $port, $username, $password, $options);
        parent::__construct($conn);

        // // Default connection
        // parent::__construct($pdo);
        
        // Set default table
        // $this->table = self::$tableM;
    }

    public static function getAllEmployees($cols = false, $limit = '10')
    {
        $selectCols = $cols ?: '*';
        $sql = 'SELECT '.$selectCols.' FROM '.self::$tableM.' LIMIT ' . $limit;
        $result = self::table(self::$tableM)->execQuery($sql, [], false, false, true);

        return $result;
    }

}
