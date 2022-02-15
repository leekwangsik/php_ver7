<?php
namespace App; 

use App\Common\Lists;
// use App\Conn\Db;
use App\Db;

class Bbs
{   
    public static $_CONF;

    public $bbs_code;
    public $subject;
    public $content;
    public $name;

    function __construct($bbs_code="", $author_root = "")
    {
        if(is_null(self::$_CONF) && $bbs_code) {
            self::$_CONF = require_once ($author_root) ? $author_root . "/config.". $bbs_code. ".php" : Site::$CONFIG['pcRoot'] . "/bbs/config/config.". $bbs_code. ".php";
        }
    }



    public function getView($sid) {

        if(!$sid){
            Func::PutMessageBack("잘못된 접근입니다.");
        }

        $query = "SELECT * FROM ".self::$_CONF['tbl']['bbs']." WHERE del='N' AND bbs_code='".$_GET['code']."' AND sid=?";
        $list = Db::getObj($query , [$sid], self::class);

        if(!$list) {
            if(empty($_GET['code'])) {
                Func::PutMessageRefreshURL("이미 삭제 되었거나, 존재하지 않는 게시물 입니다.", "/main.php");
            } else {
                Func::PutMessageRefreshURL("이미 삭제 되었거나, 존재하지 않는 게시물 입니다.", "/bbs/?code=".$_GET['code']);
            }
        }

        if( self::$_CONF['use']['hide'] == 'Y' && $list->hide == 'Y' && !self::isBBSAdmin() ) {
            if($list->user_id != $_COOKIE['member_sid']) {
                Func::PutMessageBack('비밀글에 대한 열람권한이 없습니다.');
            }
        }

        if( self::$_CONF['use']['open'] == 'Y' && !self::isBBSAdmin() && $list->open=='N' ) {
            Func::PutMessageBack("잘못된 접근입니다.");
        }

    
        ## 조회수 업
        # 조회 게시판 이용하여 조회수 업데이트
        // $query="SELECT sid FROM ". self::$_CONF['tbl']['read'] ." WHERE bbs_code='". $_GET['code'] ."' AND bsid=". $_GET['number'] ." AND (user_id='". $_COOKIE['member_sid'] ."' OR ip='". $_SERVER['REMOTE_ADDR'] ."')";
        // $check = Db::num_rows($query);
    
        // if(!$check) {
        //     $query="INSERT INTO ". self::$_CONF['tbl']['read'] ." SET bbs_code='". $_GET['code'] ."', bsid='". $_GET['number']. "', user_id=".($_COOKIE['member_sid'] ?? 0).", ip='". $_SERVER['REMOTE_ADDR']. "'";
        //     Db::exec($query);
    
        //     $query="UPDATE ". self::$_CONF['tbl']['bbs'] ." SET ref=ref+1 WHERE sid=". $_GET['number'];
        //     Db::exec($query);
        // }

        #무조건 조회수 업데이트        
        if(!Func::isDeveloper()) {
            $query="UPDATE ". self::$_CONF['tbl']['bbs'] ." SET ref=ref+1 WHERE sid=". $_GET['number'];
            Db::exec($query);
        }


        return $list;


    }
    

    public function getList() {
        
        if(self::$_CONF['permit']['Logined']) { # 로그인 체크
            Func::procLoginChk();
        
            if(!self::isBBSPermit('list') && !self::isBBSAdmin()) {
                Func::PutMessageBack('접근권한이 없습니다. 관리자와 상담해 주세요.');
            }
        }
        
        if(self::$_CONF['permit']['AdminLogined']) { # 관리자 전용일 경우
            Func::procAdminLoginChk();
        }


        $num_per_page = $_GET['li_page'] ?? self::$_CONF['num_per_page'];
        $page_per_block = self::$_CONF['page_per_block'];
        $page = !empty($_GET['page']) ? $_GET['page'] : 1;


        $defualt_condition = "del='N' AND notice='N' AND bbs_code='". self::$_CONF['code'] ."'";
        $add_condition = "";

        # 공개/비공개 사용시
        if( self::$_CONF['use']['open'] ) {
            if(!self::isBBSAdmin()) {
                $add_condition .= " AND open='Y'";
            }
        }

        # 카테고리 사용시
        if( self::$_CONF['use']['category'] && !empty($_GET['category']) ) { 
            $add_condition .=" AND category='".$_GET['category']."'"; 
        }

        # 구분 사용시
        if( self::$_CONF['use']['gubun'] && !empty($_GET['gubun']) ) { 
            $add_condition .=" AND gubun='".$_GET['gubun']."'"; 
        }
        

        // $listObj = (new Lists(self::$_CONF['tbl']['bbs'], $defualt_condition))->list($page, $num_per_page, $page_per_block);
        $list = new Lists(self::$_CONF['tbl']['bbs'], $defualt_condition . $add_condition);
        if( isset(self::$_CONF['use']['thread']) && self::$_CONF['use']['thread'] ) {
            $list->orderBy("fid", "DESC")->orderBy("thread", "ASC");

        } else {
            $order = !empty($_GET['order']) ? $_GET['order'] : "created_at";
            $stand = !empty($_GET['stand']) ? $_GET['stand'] : "desc";
            
            $list->orderBy($order, $stand);        
        }

        $listObj = $list->pageList($page, $num_per_page, $page_per_block);


        if( self::$_CONF['use']['notice'] ) {
            $query = "SELECT * FROM " . self::$_CONF['tbl']['bbs'] . " WHERE del='N' AND bbs_code=? AND notice='Y' ORDER BY sid DESC";
            $notice_list = Db::getObjAll($query , [self::$_CONF['code']], self::class);

            $listObj->notice_list = $notice_list;
        }

        return $listObj;
    }

    public static function isAdmin() { # 게시판별 관리자인지 확인

		if(!isset($_COOKIE['member_id'])) return false;
		if(Func::isAdminLogined()) return true;
		return in_array($_COOKIE['member_id'], self::$_CONF['permit']['bbsAdmin']);
	}

	public static function isBBSPermit($act='list') { # 해당 액션의 권한이 있는지 확인 (등급)
		if(self::isAdmin()) return true;		
		return ( isset($_COOKIE['member_level']) && in_array($_COOKIE['member_level'], self::$_CONF['permit'][$act]) ) || empty(self::$_CONF['permit'][$act]);
	}

    public static function isBBSAdmin() { # 게시판별 관리자인지 확인

		//if(!$_COOKIE['member_id']) return false;
		if(!isset($_COOKIE['member_id'])) return false;
		if(Func::isAdminLogined()) return true;
		return in_array($_COOKIE['member_id'], self::$_CONF['permit']['bbsAdmin']);
	}

    public static function procBBSPopup($table = 'bbs_tbl') {
		global $_COOKIE;

		$query = "SELECT * FROM $table WHERE del='N' AND hide='N' AND popup='Y' AND NOW() between popup_startdate and popup_enddate";
		$result = Db::query($query);
		
		$print = "<script type=\"text/javascript\">\n//<![CDATA[\n";
		while($d = $result->fetch_assoc()) {
			if(empty($_COOKIE["popup". $d['bbs_code'] . $d['sid']])) {

				$scroll = $d['popup_scroll']=='Y' ? 'yes' : 'no' ;

				$print.= "window.open('/bbs/popup_view.php?code=".$d['bbs_code']."&number=".$d['sid']."', 'popup".$d['bbs_code'].$d['sid']."', 'width=".$d['popup_width'].",height=".$d['popup_height'].",left=".$d['popup_position_x'].",top=".$d['popup_position_y'].",scrollbars=".$scroll.",status=no,directories=no,menubar=no');\n";
			}
		}
		$print .= "//]]>\n</script>\n";
		print($print);
	}

}