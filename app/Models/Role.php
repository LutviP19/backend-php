<?php

namespace App\Models;

use App\Core\Database\Model;
use App\Core\Database\QueryBuilder; // import the class.

class Role extends Model
{
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM = "roles";


    public static function getRoleById($id, $cols = false) {
        $selectCols = $cols ?: '*';
        $sql = 'SELECT '.$selectCols.' FROM '.self::$tableM.' WHERE id = ? LIMIT 1';
        $result = QueryBuilder::table(self::$tableM)->execQuery($sql, [$id], false, true, false);

        return $result;
    }

}