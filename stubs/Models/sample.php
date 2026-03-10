<?php 
/**
 *  @package Backend-PHP
 */

namespace App\Models;


use App\Core\Database\Model;
use App\Core\Database\QueryBuilder; // import the class.
// use PDO; // Uncomment to build new PDO object


class MyModel extends Model
{
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM = "users";

    public function index(?array $request = [])
    {
        // $data = self::table(self::$tableM)->select([ '*'])
        //         ->where('email', '=', $email)
        //         ->whereAnd('status', '=', 1)
        //         ->first();

        // if($data) return $data;

        return null;
    }

    public function store(?array $params = [], $columnId = 'id')
    {
        // self::table(self::$tableM)->primaryKey($columnId);
        // $store = self::table(self::$tableM)->createOrUpdate($params, $columnId);
        // return $store;

        // $sqlQuery = "INSERT INTO " . self::$tableM . " (" . array_keys($params) . ") VALUES (". str_repeat('?', count($params)).")";
        // $lastId = QueryBuilder::table(self::$tableM)->execQuery($sqlQuery, array_values($params), true);
        // return $lastId;

        return;
    }

    public function edit($id, $columnId = 'id')
    {
        // self::table(self::$tableM)->primaryKey($columnId);
        // $data = self::table(self::$tableM)->select(['ulid'])->where('id', '=', $id)->first();
        // // \App\Core\Support\Log::debug($data, 'UserModel.getUlid');

        // if ($data) {
        //     return $data->ulid;
        // }

        return false;
    }

    public function update($id, $columnId = 'id')
    {
        // $token = generateRandomString();

        // self::table(self::$tableM)->primaryKey($columnId);
        // $update = self::table(self::$tableM)->updateWhere(['client_token' => $token], $columnId, $id);

        // if (true === $update) {
        //     return $token;
        // } else {
        //     return null;
        // }

        return;
    }

    public function destroy($id, $columnId = 'id')
    {
        // self::table(self::$tableM)->primaryKey($columnId);
        // $delete = self::table(self::$tableM)->delete($id);

        // return $delete;

        return;
    }
}

