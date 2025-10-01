<?php

namespace App\Models;

use App\Core\Database\Model;
use App\Core\Database\QueryBuilder; // import the class.

class User extends Model
{
    /**
     * Table to query from.
     *
     * @var string
     */
    protected $table = "users";

    /**
     * Primary key column name.
     *
     * @var string
     */
    protected $pk = "id";


    //user model code....

    public static function getUserByEmail($email)
    {
        $data = self::select([
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

    public static function getUlid($id)
    {
        $data = self::select(['ulid'])->where('id', '=', $id)->first();
        // \App\Core\Support\Log::debug($data, 'UserModel.getUlid');

        if ($data) {
            return $data->ulid;
        }

        return false;
    }

    public static function updateClientToken($columnId, $id)
    {
        $token = generateRandomString();

        self::primaryKey($columnId);
        $update = self::updateWhere(['client_token' => $token], $columnId, $id);

        if (true === $update) {
            return $token;
        } else {
            return null;
        }
    }
}
