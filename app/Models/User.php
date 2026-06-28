<?php

namespace App\Models;

use App\Core\Database\Model;
use App\Core\Database\QueryBuilder; // import the class.
// use App\Core\Database\Connection; // Uncomment to build new Custom connection.
use PDO; // new PDO object

class User extends Model
{
    /**
     * static table name for this model.
     *
     * @var string
     */
    protected static $tableM = "users";

    public function __construct(?PDO $pdo = null)
    {
        // Default connection
        parent::__construct($pdo);
        
        $this->table = self::$tableM;
    }


    //user model code....
    public static function getAllUser()
    {
        $data = self::table(self::$tableM)->select()->get();
        if($data) return $data;

        return null;
    }

    public static function getUserByEmail($email)
    {
        $data = self::table(self::$tableM)->select([
                        'ulid',
                        'name',
                        'email',
                        'password',
                        'client_token',
                        'current_team_id',
                        'profile_photo_path',
                        'first_name',
                        'last_name',
                        'default_url'
                    ])
                    ->where('email', '=', $email)
                    ->whereAnd('status', '=', 1)
                    ->first();

        if($data) return $data;

        return null;
    }

    public static function getClientId($id, $columnId = 'id')
    {
        $data = self::table(self::$tableM)->select(['client_token'])
                ->where($columnId, '=', $id)
                ->whereAnd('status', '=', 1)
                ->first();
        // \App\Core\Support\Log::debug($data, 'UserModel.getClientId');

        if ($data) {
            return $data->client_token;
        }

        return false;
    }

    public static function getUlid($id)
    {
        $data = self::table(self::$tableM)->select(['ulid'])->where('id', '=', $id)->first();
        // \App\Core\Support\Log::debug($data, 'UserModel.getUlid');

        if ($data) {
            return $data->ulid;
        }

        return false;
    }

    public static function updateClientToken($columnId, $id)
    {
        $token = generateRandomString();

        self::table(self::$tableM)->primaryKey($columnId);
        $update = self::table(self::$tableM)->updateWhere(['client_token' => $token], $columnId, $id);

        if (true === $update) {
            return $token;
        } else {
            return null;
        }
    }
}
