<?php
namespace App;

use App\Site;

class Func
{
	##########################################################################################
	##location

	/**
	 * @brief meta tag Refresh
	 */
	public static function redirect($url) {
	   $url = urlencode($url);
	   echo "<meta http-equiv='Refresh' content='0; URL=/member/?url=" . $url . "'>";
	   exit;
	}

	public static function PutMessage($msg)
	{
		print("<script language=\"JavaScript\">
			<!--
			alert('$msg');
			//-->
			</script>");
	}

	public static function PutMessageBack($msg){
		print("<script language=\"JavaScript\">
			  <!--
			  alert('$msg');
			  history.back();
			  //-->
			  </script>");
		exit;
	}

	public static function PutMessageRefreshURL($msg, $url)
	{
		print("<script language=\"JavaScript\">
			  <!--
			  alert('$msg');
			  //-->
			  </script>");
		print("<meta http-equiv='Refresh' content='0; url=$url'>");
		exit;
	}

	public static function PutLocation($url) {
		$print  = "<script type=\"text/javascript\">\n//<![CDATA[\n";
		$print .= "location.href='".$url."';\n";
		$print .= "//]]>\n</script>\n";
		print($print);
		exit;
	}

	public static function RefreshURL($url)
	{
		print("<meta http-equiv='Refresh' content='0; url=$url'>");
		exit;
	}

	public static function PutMessageClose($msg){
		$print  = "<script type=\"text/javascript\">\n//<![CDATA[\n";
		$print .= "alert(\"${msg}\");\nself.close();";
		$print .= "//]]>\n</script>\n";
		print($print);
		exit;
	}

	public static function PutMessageCloseOpenerLocation($msg,$url){
		$print  = "<script type=\"text/javascript\">\n//<![CDATA[\n";
		$print .= "alert(\"${msg}\");\n";
		$print .= "opener.location.href='${url}';\n";
		$print .= "self.close();\n";
		$print .= "//]]>\n</script>\n";
		print($print);
		exit;
	}
	
	public static function PutMessageCloseOpenerReload($msg){
		$print  = "<script type=\"text/javascript\">\n//<![CDATA[\n";
		$print .= "alert(\"${msg}\");\n";
		$print .= "opener.location.reload();\n";
		$print .= "self.close();\n";
		$print .= "//]]>\n</script>\n";
		print($print);
		exit;
	}
	
	##########################################################################################
	##location


	##########################################################################################
	##auth
	public static function isLogined() {
		global $_COOKIE;
		if(isset($_COOKIE['member_id'])) return true;
		return false;
	}

	public static function procLoginChk() {
		global $_SERVER;
		if(self::isLogined()) return true;
		else self::redirect( $_SERVER['PHP_SELF'] . "?" .$_SERVER['QUERY_STRING']);
	}

	public static function isAdminLogined() {
		GLOBAL $_COOKIE;
		if(isset($_COOKIE["member_id"]) && $_COOKIE['member_level'] == 'M') {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public static function procAdminLoginChk() {
		self::procLoginChk();
		if(self::isAdminLogined()) return;
		else self::PutMessageBack('관리자만 이용가능한 페이지 입니다.');
	}

	public static function isCheckMember() {
		if(in_array($_SERVER['REMOTE_ADDR'], Site::$CONFIG['checked_ip'])) return true;
		else return false;
	}

	// public static function isMaster() { //개발자용 으로만 사용
	// 	$ips = ['218.235.94.225']; //개발자만 수정하도록 이쪽에서 설정
	// 	if(in_array($_SERVER['REMOTE_ADDR'], $ips)) return true;
	// 	else return false;
	// }

	public static function isDeveloper() { //부디 개발자용 으로만 사용
		$ips = ['218.235.94.225']; //개발자만 수정하도록 이쪽에서 설정
		if(in_array($_SERVER['REMOTE_ADDR'], $ips)) return true;
		else return false;
	}

	##auth
	##########################################################################################

	
	## print input
	##########################################################################################
	
	public static function printSelectBox(array $arr, $id, $value='', $option='', $default_option='', $set_type='1') {
		if(!is_array($arr)) return "Is Not Array.";
		if(!$id) {
			$return="<select ${option}>\n";
		} else {
			$return="<select name=\"${id}\" id=\"${id}\" ${option}>\n";
		}

		if($default_option) {
			if(is_array($default_option)) {
				$return .= "<option value='".key($default_option)."'>".current($default_option)."</option>";
			} else {
				$return .= "<option value=''>".$default_option."</option>";
			}
		}

		foreach($arr as $tkey => $tval){

			if($set_type == '2') { $tkey = $tval;}
			$return.="<option value=\"${tkey}\" " . ($value==$tkey ? "selected='selected'":"") . "  >${tval}</option>\n";

		}
		$return.="</select>";
		return $return;
	}


	public static function printRadioBox(array $arr, $id, $value='', $option='', $set_type='1', $br=false) {
		if(!is_array($arr)) return "Is Not Array.";
		if(!$id) return "ID is null";

		$i=1;
		$return="";
		foreach($arr as $tkey => $tval) {
			
			if($set_type == '2') { $tkey = $tval;}

			if(is_array($option)) { //특정 key 에만 적용
				
			}
			
			$return.="<input type=\"radio\" name=\"${id}\" id=\"${id}_${i}\" ${option} value=\"${tkey}\" ". ($value==$tkey ? "checked='checked'":"") . "style=\"border:0px;\" /> <label for=\"${id}_${i}\" class=\"hand\">${tval}</label>\n";
	
			if($br&&$i%$br==0) $return.="<br />";
			$i++;
		}
		return $return;
	}


	public static function printCheck(Array $arr, $id, Array $value=[], $option='', $br=false) {
		if(!is_array($arr)) return "Is Not Array.";
		if(!$id) return "ID is null";
		$i=1;
		$return="";
		foreach($arr as $tkey => $tval){
			if(in_array($tkey, $value)){
				$return.="<input type=\"checkbox\" name=\"${id}[]\" id=\"${id}_${i}\" ${option} value=\"${tkey}\" checked/><label for=\"${id}_${i}\">${tval}</label>\n";
			} else {
				$return.="<input type=\"checkbox\" name=\"${id}[]\" id=\"${id}_${i}\" ${option} value=\"${tkey}\"/><label for=\"${id}_${i}\">${tval}</label>\n";
			}
			if($br) $return.="<br/>";
			$i++;
		}
		return $return;
	}


	

	## print input
	##########################################################################################
	

	public static function is_mobile() {
		// 모바일 기종(배열 순서 중요, 대소문자 구분 안함)
		$ary_m = array("iPhone","iPod","IPad","Android","Blackberry","SymbianOS|SCH-M\d+","Opera Mini","Windows CE","Nokia","Sony","Samsung","LGTelecom","SKT","Mobile","Phone");
		for($i=0; $i<count($ary_m); $i++){
			if(preg_match("/$ary_m[$i]/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
				return true;
				break;
			}
		}
		return false;
	}


	# 파일 아이콘 정리
	public static function IconType($filename, $iconlocation='/icon/', $str='') {
		$icon_value=explode(".",$filename);
		$tmp=strtolower($icon_value[sizeof($icon_value)-1]);
		switch($tmp){
			case 'avi': case 'doc':case 'docx': case 'ppt': case 'pptx': case 'exe': case 'gif': case 'jpg': case 'hwp':
			case 'pdf':	case 'mp3': case 'wav': case 'xls': case 'xlsx': case 'zip': case 'alz': break;
			default: $icon='etc'; break;
		}
		if($str) return self::printImg("${iconlocation}${str}${tmp}.gif", $filename, false, 'align="middle"');
		else return self::printImg("${iconlocation}${tmp}.gif", $filename, false, 'align="middle"');
	}
	
	# 이미지 태그 자동 넣기
	public static function printImg($src, $alt='', $type=false, $opt='') {
		
		if(!$src) return '이미지를 입력해 주세요';
		$root=$_SERVER['DOCUMENT_ROOT'];
		if(!strcmp('/', substr($root, -1))) $root=substr($root, 0, strlen($root)-1);
		//$local=$root.$src;
		//if(!file_exists($local)) return '';
		$local=Site::$CONFIG['pcUrl'].$src;		
		
		$info = getimagesize($local);
		if(!$type){
			return "<img src=\"${local}\" width=\"${info[0]}\" height=\"${info[1]}\" alt=\"${alt}\" ${opt}/>";
		} else {
			return "<input type=\"image\" src=\"${local}\" alt=\"${alt}\" ${opt}/>";
		}
	}

	# config 파일 가져오기
	public static function config($name) {
		return require($_SERVER['DOCUMENT_ROOT'] . "/config/". $name .".config.php");
	}

	# 메일용 템플릿
	public static function template($template_file, $configs = []){

		if(count($configs)) {
			foreach($configs as $key => $val) {
				$$key = $val;
			}
		}

		if( file_exists($template_file) ) {
			ob_start(); //출력 버퍼링 활성
			include $template_file;
			$mail_body .= ob_get_contents(); //파일내용 변수에 저장
			ob_end_clean(); //출력 버퍼 지우고 출력 버퍼링 종료
			return $mail_body;
		}
	}


	//ex) form_send_message("/member/join_comp.php", ['sid'=>$new_sid] );
	public static function form_send_message($url, array $param, $msg="")
	{
		$form ="<form name='func_send_form' method='post' action='${url}'>";
		foreach($param as $key => $val){
		$form .="<input type='hidden' name='${key}' value='${val}'>";
		}
		$form .="</form>";
		
		$form .="<script>";
		if($msg) $form .="alert('".$msg."');";
		$form .="document.func_send_form.submit();";
		$form .="</script>";
		echo $form;
	}

	# 숫자를 한글로
	public static function number2hangul($number) {

		$num = array('', '일', '이', '삼', '사', '오', '육', '칠', '팔', '구');
        $unit4 = array('', '만', '억', '조', '경');
        $unit1 = array('', '십', '백', '천');

        $res = array();

        $number = str_replace(',','',$number);
        $split4 = str_split(strrev((string)$number),4);

		if(is_array($split4)) {
			for($i=0;$i<count($split4);$i++){
					$temp = array();
					$split1 = str_split((string)$split4[$i], 1);

					if(is_array($split1)) {
						for($j=0;$j<count($split1);$j++){
								$u = (int)$split1[$j];
								if($u > 0) $temp[] = $num[$u].$unit1[$j];
						}
					}
					if(count($temp) > 0) $res[] = implode('', array_reverse($temp)).$unit4[$i];
			}
		}
        return implode('', array_reverse($res));
	}

	public static function getYoil($date, $type = '1') {
		$week1 = array("일", "월", "화", "수", "목", "금", "토") ;
		$week2 = array("일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일") ;

		$week = ${"week" . $type};

		$date = str_replace('.', '-', $date);

		return $week[ date('w', strtotime($date)) ];
	}

	public static function getParams($except = []) {
		global $_GET;

		$params = "";
		foreach($_GET as $key => $val) {
			if(in_array($key, $except)) continue;
			$params .= "&" . $key . "=" . $val;
		}
		return $params;
	}

	public static function print_orderby($naming, $field, $sort_field, $orderby, $search_url) {
		$color = ($field==$sort_field) ? "style='color:red;'" : '';
	
		if($orderby=="" || $orderby=="desc" || $field!=$sort_field){
			echo "<a href='".$_SERVER['PHP_SELF']."?order=".$field."&sort=asc".$search_url."' ".$color.">".$naming."↓</a>";
		} else {
			echo "<a href='".$_SERVER['PHP_SELF']."?order=".$field."&sort=desc".$search_url."' ".$color.">".$naming."↑</a>";
		}
	}

	public static function isMobileRoot($mobile_folder_name = 'mobile') {
		$folder = substr($_SERVER['DOCUMENT_ROOT'], strrpos($_SERVER['DOCUMENT_ROOT'], '/') + 1); 
		return $folder === $mobile_folder_name;
	}
}