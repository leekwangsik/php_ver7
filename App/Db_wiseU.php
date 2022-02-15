<?php
namespace App;

use \PDO;
use App\Conn\DbStatic;

class Db_wiseU extends DbStatic
{	
	static $pdo;
	
	protected const DB_HOST = "";
	const DB_NAME = "";
	protected const DB_USER = "";	
	protected const DB_PASSWORD = "";
	
    static function db_connect()
	{
        self::$pdo = new PDO("dblib:host=". self::DB_HOST .":1433; dbname=". self::DB_NAME .";", self::DB_USER, self::DB_PASSWORD);
		self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	protected static function get_name() {
		return get_called_class();
	}
}
