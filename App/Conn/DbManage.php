<?php
namespace App\Conn;

use \PDO;
use \PDOException;

class DbManage
{
	private $sth;
	private $parent_class;

	function __construct($parent_class)
    {	
		$this->parent_class = $parent_class;
		if($this->parent_class::$pdo === null) {
			// DbStatic::connect();
			$this->parent_class::db_connect();
		}

    }

	function query($query, $params = [])
	{
		try {

			$this->sth = $this->parent_class::$pdo->prepare($query);
			$this->sth->execute($params);
			
		} catch (PDOException $e) {
			
			if(\App\Func::isDeveloper()) {
				die( $e->getMessage() . "<br/>" . $this->parent_class::interpolateQuery($query, $params) );
			} else {
				$this->parent_class::record_error_log($query, $params);
				\App\Func::PutMessageBack("실행오류입니다. 관리자에게 문의해주세요.");
			}
			
		} 

		return $this;

	}

	public function fetch_assoc()
    {
		return $this->sth->fetch(PDO::FETCH_ASSOC);
	}

	public function fetch_array()
    {
		return $this->sth->fetch(PDO::FETCH_NUM);
	}

	public function num_rows()
	{
		return $this->sth->rowCount();
	}

	public function numrows()
	{
		return $this->num_rows();
	}

	public function getAll()
	{
		return $this->sth->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getAllCol($column = 0) {
		return $this->sth->fetchAll(PDO::FETCH_COLUMN, $column);
	}

	public function getAllPair() {
		return $this->sth->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	public function getObj(...$classnames) {		
		if(count($classnames) <= 1) {
			return ($obj = $this->sth->fetchObject( $classnames[0] ?? 'stdClass' )) ? $obj : null;
		} else {
			$return_arr = [];

			if($object = $this->sth->fetch(PDO::FETCH_NUM)) {
				$arr = [];

				foreach($classnames as $classname) {
					
					$tmp = new $classname();
					$tmp->setAll($object);

					array_push($arr, $tmp);
					unset($tmp);
				}

				array_push($return_arr, $arr);

			}
			
			return count($return_arr) ? $return_arr : null;
		}
		
	}

	public function getObjAll(...$classnames)
	{		
		if(count($classnames) <= 1) {
			return ($obj = $this->sth->fetchAll(PDO::FETCH_CLASS, $classnames[0] ?? 'stdClass')) ? $obj : [];
		} else {
			$return_arr = [];

			$objects = $this->sth->fetchAll(PDO::FETCH_NUM);

			foreach($objects as $object) {

				$arr = [];
				foreach($classnames as $classname) {
					
					$tmp = new $classname();
					$tmp->setAll($object);
					
					array_push($arr, $tmp);
					unset($tmp);
				}

				array_push($return_arr, $arr);
			}

			return $return_arr;
		}
		
	}

	public function close()
	{
		$this->sth->closeCursor();
	}


}