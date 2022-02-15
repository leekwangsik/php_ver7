<?php
namespace App\Conn;

use \PDO;
use \PDOException;

abstract class DbStatic
{
	static abstract function db_connect();

    static function connect()
    {	
		if(static::$pdo === null) {
			static::db_connect();
		}
    }

    private static function prepare($query, $params = [])
	{
		self::connect();

		try {

			$sth = static::$pdo->prepare($query); 
			$sth->execute($params);
			
		} catch (PDOException $e) {
			if(\App\Func::isDeveloper()) {
				die( $e->getMessage() . "<br/>" . self::interpolateQuery($query, $params) );
			} else {
				self::record_error_log($query, $params);
				\App\Func::PutMessageBack("저장오류입니다. 관리자에게 문의해주세요.");
			}
		} 

		return $sth;

	}


    public static function exec($query, $params = [])
    {
		self::connect();

		try {

			$sth = static::$pdo->prepare($query); 
			$sth->execute($params);
			
		} catch (PDOException $e) {
			
			if (static::$pdo->inTransaction()) {
				static::$pdo->rollBack();
			}

			if(\App\Func::isDeveloper()) {
				die( $e->getMessage() . "<br/>" . self::interpolateQuery($query, $params) );
			} else {
				self::record_error_log($query, $params);
				\App\Func::PutMessageBack("저장오류입니다. 관리자에게 문의해주세요.");			
			}
		}

		unset($sth);
    }

    public static function insert($tbl_name, $params)
	{
		
		if(is_object($params)) {
			$params = get_object_vars($params);
		}

		if(isset($params['sid']) && empty($params['sid'])) {
			unset($params['sid']);
		}

		$query = "insert into $tbl_name (" . implode(', ', array_keys($params)) . ") values (" . implode(", ", array_fill(0, count($params), "?")) . ")";
		$datas = array_values($params);
		
		self::exec($query, $datas);
		return static::$pdo->lastInsertId();
	}

    public static function update($tbl_name, $params, $condition, $condition_params, array $except_params = [])
	{
		if(is_object($params)) {
			$params = get_object_vars($params);
		}

		if(count($except_params)) {
			$params = array_diff_key($params, array_flip($except_params) );
		}

		$query = "update $tbl_name set ". implode(' = ?, ', array_keys($params)) ." =? WHERE ";
		$datas = array_values($params);

		if($condition) {
		
			$query .= $condition;

			if(is_array($condition_params)) {
				$datas = array_merge($datas, $condition_params);
			} else {
				array_push($datas, $condition_params);
			}
		}

		self::exec($query, $datas);
	}


    public static function get_one($query, $params = [])
	{
		$sth = self::prepare($query, $params);		
		return $sth->fetchAll(PDO::FETCH_COLUMN)[0] ?? '';
	}

	public static function getone($query, $params = [])
	{
		return self::get_one($query, $params);
	}

    public static function num_rows($query, $params = []) : int
	{
		$sth = self::prepare($query, $params);
		$result = $sth->rowCount();
		return $result;
	}

	public static function numrows($query = '', $params = [])
	{
		return self::num_rows($query, $params);
	}

    public static function get_all($query, $params = [])
	{
		$sth = self::prepare($query, $params);
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function getall($query, $params = [])
	{
		return self::get_all($query, $params);
	}

    public static function getAllCol($query, $params = [], $column = 0) {
		$sth = self::prepare($query, $params);
		return $sth->fetchAll(PDO::FETCH_COLUMN, $column);
	}

    public static function getAllPair($query, $params = []) {
		$sth = self::prepare($query, $params);
		return $sth->fetchAll(PDO::FETCH_KEY_PAIR);
	}

    public static function getObj($query, $params = [], $classname = 'stdClass') {		
		$sth = self::prepare($query, $params);
		return $sth->fetchAll(PDO::FETCH_CLASS, $classname)[0] ?? null;
	}

	public static function getObjAll($query, $params = [], $classname = 'stdClass') {		
		$sth = self::prepare($query, $params);
		return $sth->fetchAll(PDO::FETCH_CLASS, $classname);		
	}

	static function interpolateQuery($string, $data) {

		$indexed = $data == array_values($data);

		foreach($data as $k=>$v) {
            if(is_string($v)) $v="'$v'";
            if($indexed) $string=preg_replace('/\?/',$v,$string,1);
            else $string=str_replace(":$k",$v,$string);
        }
        return $string;
	}

	static function record_error_log($query, $params) {
		$query_text = self::interpolateQuery($query, $params);

		$log_query = "insert into db_error_log (query_text, url) values (?, ?)";
		
		try {
			$sth = static::$pdo->prepare($log_query); 
			$sth->execute(
				[$query_text, $_SERVER['REQUEST_URI']
			]);			
		} catch (PDOException $e) {

		}
		
	}

	public static function query($query, $params = []) 
	{
		$parent_class = static::get_name();
		$dmg = new DbManage($parent_class);
		return $dmg->query($query, $params);
	}

	
}