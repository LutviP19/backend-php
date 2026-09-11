<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database\BaseModel;
use PDO; // new PDO object

class User extends BaseModel
{
    protected static string $table = "users";

    public function __construct(?PDO $pdo = null)
    {
        // Default connection
        parent::__construct($pdo);
    }

    public static function getAllUser()
    {
        $data = static::select()->get();
        if ($data) {
            return $data;
        }

        return null;
    }

    public static function getUserByEmail($email)
    {
        $data = static::select([
                       'id',
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

        if ($data) {
            return $data;
        }

        return null;
    }

    public static function getUserByUlid($ulid)
    {
        $data = static::select([
                       'id',
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
                   ->where('ulid', '=', $ulid)
                   ->whereAnd('status', '=', 1)
                   ->first();

        if ($data) {
            return $data;
        }

        return null;
    }

    public static function getClientId($id, $columnId = 'id')
    {
        $data = static::select(['client_token'])
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
        $data = static::select(['ulid'])->where('id', '=', $id)->first();
        // \App\Core\Support\Log::debug($data, 'UserModel.getUlid');

        if ($data) {
            return $data->ulid;
        }

        return false;
    }

    public static function updateClientToken($columnId, $id)
    {
        $token = generateRandomString();

        static::$primaryKey = $columnId;
        $update = static::updateById($id, ['client_token' => $token]);

        if ($update > 0) {
            return $token;
        } else {
            return null;
        }
    }
}
