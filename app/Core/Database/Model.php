<?php

namespace App\Core\Database;


use Exception;
use PDOException;

/**
 * Model class
 * @author Lutvi <lutvip19@gmail.com>
 */
class Model extends QueryBuilder
{
    /**
     * This class extends QueryBuilder that
     * has the functionality for database
     * manipulation.
     *
     * You can add any code related to
     * models...
     */

    
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM;

    public function __construct(PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
    
}
