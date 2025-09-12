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
	
	public static function updateClientToken($columnId, $id)
	{
		$token = generateRandomString();
		
		self::primaryKey($columnId);
		self::updateWhere(['client_token' => $token], $columnId, $id);
	}
}