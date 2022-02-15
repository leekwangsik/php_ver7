<?php
namespace App\Common;

// use App\Conn\Db;
use App\Db;
use App\Common\Block;

class Lists {

    private static $TABLE_DESCRIBE;

    private $list_class = [];
    private $order_stand = [];

    private $default_condition;
    private $param_excepts = []; //검색 제외 부분
    private $param_equals = []; //같음(=) 비교대상
    private $param_likes = []; //like 비교대상

    private $param_datePeriod = []; //기간 검색
    public $param_search_all = ['subject', 'content']; //search_key 가 all 일경우 검색 필드

    public $ex_add_query = [];
    public $ex_search_url = [];

    function __construct($table_val, $default_condition = "")
    {
        self::$TABLE_DESCRIBE = $table_val;
        $this->default_condition = $default_condition;
        $this->setParamExcept(['search_key', 'search_val']);

        return $this;
    }

    public function setClass(array $arr_classname) {
        return $this->list_class = $arr_classname;
    }

    public function getClass() {
        return $this->list_class;
    }

    public function orderBy($order, $stand = 'DESC') {
        
        if($order) {
            array_push($this->order_stand, $order . " " . $stand);
        }

        return $this;
    }

    public function getTableDescribe() {
        return self::$TABLE_DESCRIBE;
    }

    /**
     * 검색에서 제외할 파라메터
     */
    public function setParamExcept($param) {
        if(is_array($param)) {
            $this->param_excepts += $param;
        } else {
            array_push($this->param_excepts, $param);
        }
    }

    public function setParamEqual($param) {
        if(is_array($param)) {
            $this->param_equals += $param;
        } else {
            array_push($this->param_equals, $param);
        }
    }

    public function setParamDatePeriod($field, array $date_Arr) {
        array_push($this->param_datePeriod, ['key'=>$field, 'date'=>$date_Arr] );
    }

    public function setParamLike($param) {
        if(is_array($param)) {
            $this->param_likes += $param;
        } else {
            array_push($this->param_likes, $param);
        }
    }

    /**
     * @brief 기본 파라메터값 추가
     * @param String $ex_param 
     * @remark parameter=value 방식
     */
    public function addExparam($ex_param) {
        array_push($this->ex_search_url, $ex_param);
    }

    public function setByParams($params) {

        $search_url = [];
        $add_query = [];

        $return_add_query = null;
        
        if($this->default_condition) {
            $add_query[] = $this->default_condition;
        }


        if(!empty($params)) {

            if(count($this->param_equals)) {

                foreach($this->param_equals as $key) {
                    if(is_array($key)) {
                        $n_key = key($key);
                        if(array_key_exists($n_key, $params) && $params[$n_key]) {
                            $add_query[] =  $key[$n_key] . "='". $params[$n_key] ."'";
                            $search_url[] = $n_key . "=" . $params[$n_key];
                        }
                    } else {
                        if(array_key_exists($key, $params) && $params[$key]) {
                            $add_query[] =  $key . "='". $params[$key] ."'";
                            $search_url[] = $key . "=" . $params[$key];
                        }
                    }
                }
            }


            if(count($this->param_likes)) {

                foreach($this->param_likes as $key) {
                    if(is_array($key)) {
                        $n_key = key($key);
                        if(array_key_exists($n_key, $params) && $params[$n_key]) {
                            $add_query[] =  $key[$n_key] . " like '%". $params[$n_key] ."%'";
                            $search_url[] = $n_key . "=" . $params[$n_key];
                        }
                    } else {
                        if(array_key_exists($key, $params) && $params[$key]) {
                            $add_query[] =  $key . " like '%". $params[$key] ."%'";
                            $search_url[] = $key . "=" . $params[$key];
                        }
                    }
                }
            }


            if(count($this->param_datePeriod)) {

                foreach($this->param_datePeriod as $d_obj ) {
                    
                    $sdate_key = $d_obj['date'][0];
                    $edate_key = $d_obj['date'][1];

                    if( !empty($params[$sdate_key]) || !empty($params[$edate_key]) ) {
                        
                        if( !empty($params[$sdate_key]) && !empty($params[$edate_key]) ) {
                            $add_query[] =  $d_obj['key'] . " >= '". $params[$sdate_key] ."' and ". $d_obj['key'] . " <= '". $params[$edate_key] ."'";
                            $search_url[] = $sdate_key . "=" . $params[$sdate_key];
                            $search_url[] = $edate_key . "=" . $params[$edate_key];

                        } else if( !empty($params[$sdate_key]) ) {
                            $add_query[] =  $d_obj['key'] . " >= '". $params[$sdate_key] ."'";
                            $search_url[] = $sdate_key . "=" . $params[$sdate_key];

                        }  else if( !empty($params[$edate_key]) ) {
                            $add_query[] =  $d_obj['key'] . " <= '". $params[$edate_key] ."'";
                            $search_url[] = $edate_key . "=" . $params[$edate_key];
                        }
                    }
                }
            }

        }

 
        if( !empty($params['search_key']) && !empty($params['search_val']) && !in_array($params['search_key'], $this->param_excepts) ) {

            if(strstr($params['search_key'],'||')) {
                $search_keys = explode('||', $params['search_key']);
                $add_query_sub = [];
                foreach($search_keys as $search_key) {
                    $add_query_sub[] = $search_key." like '%". $params['search_val'] ."%'";    
                }

                $add_query[] = '(' . implode(' OR ', $add_query_sub) . ')';

            } else if($params['search_key'] == 'all') {

                foreach($this->param_search_all as $key) {
                    $add_query[] = $key." like '%". $params['search_val'] ."%'";
                }

            } else if($params['search_key'] == 'phone') {
                $add_query[] = "replace(". $params['search_key']. ", '-', '') like '%". $params['search_val'] ."%'";
                
            } else {
                $add_query[] = $params['search_key']." like '%". $params['search_val'] ."%'";
            }
            
            $search_url[] = "search_key=" . $params['search_key'];
            $search_url[] = "search_val=" . $params['search_val'];
        }

        if(count($add_query)) {
            $return_add_query = "WHERE " . implode(" AND ", $add_query);
        }

        
        if(count($this->ex_add_query)) {
            $return_add_query .= $return_add_query ? " AND " . implode(" AND ", $this->ex_add_query) : "";
        }

        if(count($this->ex_search_url)) {
            $search_url = array_merge($search_url, $this->ex_search_url);
        }

        return (Object) ["add_query" => $return_add_query, "search_url" => $search_url];
    }


    public function pageList(int $page=1, int $numPerPage=10, int $numPerBlock=10) { 

        $query = "SELECT * FROM " . self::$TABLE_DESCRIBE;
        $condition = $this->setByParams($_GET);
        
        if($condition->add_query) {
            $query .= " " . $condition->add_query;
        }

        if($condition->search_url) {
            $search_url = implode("&", $condition->search_url);
        }
        
        $totalRecord = Db::query($query)->num_rows();

        if(count($this->order_stand)) {
            $query .= " ORDER BY " . implode(', ', $this->order_stand) ;
        } else { //기본
            $query .= " ORDER BY sid DESC";
        }

        
        $query .= " LIMIT ". $numPerPage . " OFFSET " . $numPerPage * ($page-1);

        if(\App\Func::isDeveloper()) {
            echo $query;
        }
        
        $list = Db::query($query)->getObjAll(...$this->list_class);
        $recordNo = $totalRecord - $numPerPage * ($page-1);

        return (object) [ "list" => $list, "pager" => (object) ["recordNo"=> $recordNo, "search_url"=> $search_url ?? '', "block" => new Block($page, $totalRecord, $numPerPage, $numPerBlock)]];

    }


    public function list() {

        $query = "SELECT * FROM " . self::$TABLE_DESCRIBE;
        $condition = $this->setByParams($_GET);

        if($condition->add_query) {
            $query .= " " . $condition->add_query;
        }

        if(count($this->order_stand)) {
            $query .= " ORDER BY " . implode(', ', $this->order_stand) ;
        } else { //기본
            $query .= " ORDER BY sid DESC";
        }

        $list = Db::query($query)->getObjAll(...$this->list_class);
        
        return $list;

    }

    public static function view($table_name, $condition = []) {
        $query = "SELECT * FROM " .$table_name;

        if(count($condition)) {
            $query .= " WHERE " . implode(' and ', array_map(function ($a, $b) { return $a . "='". $b ."'"; }, array_keys($condition), array_values($condition)));
        } 

        return Db::getObj($query);
    }
    
}