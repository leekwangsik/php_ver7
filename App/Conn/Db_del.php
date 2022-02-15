<?php
namespace App\Conn;

use \PDO;

class Db extends DbStatic
{	
	static $pdo;
	
	protected const DB_HOST = "117.52.153.219";
	const DB_NAME = "ksn_db";
	protected const DB_USER = "ksn";	
	protected const DB_PASSWORD = "tlswkd@!(zpdldptmdos";
	
    static function db_connect()
	{
		$dsn = "mysql:host=" . self::DB_HOST . ";port=3306;dbname=" . self::DB_NAME . ";charset=utf8";
		self::$pdo = new PDO($dsn, self::DB_USER, self::DB_PASSWORD);
		self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	protected static function get_name() {
		return get_called_class();
	}
}