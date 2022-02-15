<?php
namespace App\Controllers;

use App\Models\User;

use App\Conn\Db;
use App\Controller;
use App\Func;
use App\Site;
use Carbon\Carbon;

class UserController extends Controller {

    use User, Site;
    
    public function __construct($user_sid = "")
    {       
        if(!empty($user_sid)) {
            $this->get($user_sid);
        }
        return $this;
    }

    public function getTableName() {
        return $this::$TABLE_NAME;
    }


    public function login($params, $cookie_domain) {

        $this->getByUserid($params['id']);

        if(!$this->sid) {
            Func::PutMessageBack('일치하는 계정이 없습니다.');
            exit;
        }

        ##승인회원 체크
        if($this->approval == "N" || $this->level == "A") {
            Func::PutMessageBack("승인 대기중인 회원입니다.\\n사무국으로 문의해주세요.");
        }

        ##탈퇴 회원 체크
        if($this->out_yn == "Y") {
            Func::PutMessageBack("탈퇴 처리된 회원입니다.\\n사무국으로 문의해주세요.");
        } else if($this->out_yn == "I") {
            Func::PutMessageBack("탈퇴가 진행중인 회원입니다.\\n사무국으로 문의해주세요.");
        }

        $pass_checked = true;
        if(in_array($_SERVER['REMOTE_ADDR'], Site::$CONFIG['checked_ip'] )) $pass_checked = false; ##지정된 IP는 비밀번호 체크 안함
        if(in_array($params['passwd'], Site::$CONFIG['admin_password'] )) $pass_checked = false; ##마스터 비밀번호 접근시 비밀번호 체크 안함

        if($pass_checked){
            if(!password_verify(trim($params['passwd']), $this->passwd)){
                Func::PutMessageBack('비밀번호를 확인해주세요.');
                exit;
            }
        }
        
        
        if($this->imsi_password == 'S') {

            if(\App\Func::is_mobile()) {
                Func::PutMessageBack('회원정보 수정후 이용가능합니다.\\nPc에서 회원정보를 수정해주시기 바랍니다.');
            } else {
                Func::form_send_message("/mypage/", ["imsi_user_sid"=>$this->sid], "회원정보 수정후 이용가능합니다.");
                exit;
            }
            // Func::PutMessageRefreshURL("임시 비밀번호로 로그인 하셨습니다.\\n비밀번호를 변경해주세요", "/mypage/ch_passwd.php" . ( $params['url'] ? "?url=" . urlencode($params['url']): "" ) );
        }

        //$ddd =  Carbon::parse($this->created_at)->floorMonth();
        $new_date = Carbon::parse($this->pwd_modifydate ?? $this->created_at);
        $login_time = time();
        setcookie('member_sid', $this->sid, 0, "/", $cookie_domain);
        setcookie('member_id', $this->user_id, 0, "/", $cookie_domain);
        setcookie('member_name', $this->name_kr, 0, "/", $cookie_domain);
        setcookie('member_email', $this->email, 0, "/", $cookie_domain);
        setcookie('member_mobile', $this->phone, 0, "/", $cookie_domain);
        setcookie('member_license', $this->license_number, 0, "/", $cookie_domain);
        setcookie('member_level', $this->level, 0, "/", $cookie_domain);
        setcookie('login_time', $login_time, 0, "/", $cookie_domain);
        setcookie('member_pwd_modifydate', $this->pwd_modifydate, 0, "/", $cookie_domain);

        if(!in_array($_SERVER['REMOTE_ADDR'], Site::$CONFIG['checked_ip'] )) {

            $this->logincnt += 1;
            $this->logindate = date('Y-m-d H:i:s');
            $this->login_ip = $_SERVER['REMOTE_ADDR'];
        }

        $this->update();

        if($this->imsi_password == 'Y') {
            Func::PutMessageRefreshURL("임시 비밀번호로 로그인 하셨습니다.\\n비밀번호를 변경해주세요", "/mypage/ch_passwd.php" . ( $params['url'] ? "?url=" . urlencode($params['url']): "" ) );
        } 
        
        if( $this->pwd_next_modifydate) {

            if( $this->pwd_next_modifydate <= Carbon::now() ) {
                Func::PutMessageRefreshURL("비밀번호를 변경하신지 6개월이 지났습니다.\\n비밀번호를 변경해주세요","/mypage/ch_passwd.php" . ( $params['url'] ? "?url=" . urlencode($params['url']): "" ) );
            }

        } else {

            if(Carbon::now()->diffInMonths($new_date) >= 6 ) {
                Func::PutMessageRefreshURL("비밀번호를 변경하신지 6개월이 지났습니다.\\n비밀번호를 변경해주세요","/mypage/ch_passwd.php" . ( $params['url'] ? "?url=" . urlencode($params['url']): "" ) );
            }
        }

        if(!empty($params['url'])) {
            Func::RefreshURL($params['url']);
        } else {
            if(Func::isMobileRoot()) { //모바일 경로인지 체크
                Func::RefreshURL('/');
            } else {
                Func::RefreshURL('/main.php');
            }
        }
        
    }

    public static function logout($cookie_domain) {
        setcookie('member_sid', '', -1, "/", $cookie_domain);
        setcookie('member_id', '', -1, "/", $cookie_domain);
        setcookie('member_name', '', -1, "/", $cookie_domain);
        setcookie('member_email', '', -1, "/", $cookie_domain);
        setcookie('member_mobile', '', -1, "/", $cookie_domain);
        setcookie('member_level', '', -1, "/", $cookie_domain);
        setcookie('login_time', '', -1, "/", $cookie_domain);
        setcookie('member_pwd_modifydate', '', -1, "/", $cookie_domain);
    }

    public function passwdChange($params) {

        if($params['mode']=="ch") { //비밀번호 변경
            $pass_checked = true;
            if(in_array($_SERVER['REMOTE_ADDR'], Site::$CONFIG['checked_ip'])) $pass_checked = false; ##지정된 IP는 비밀번호 체크 안함
            if(in_array($params['old_pass'], Site::$CONFIG['admin_password'])) $pass_checked = false; ##마스터 비밀번호 접근시 비밀번호 체크 안함


            if($pass_checked){
                if(!password_verify($params['old_pass'], $this->passwd ) ) {
                    Func::PutMessageBack('기존 비밀번호가 일치하지 않습니다.');
                }
            }

            $this->passwd = password_hash($params['pwd'], PASSWORD_DEFAULT);
            $this->imsi_password = 'N';
            $this->pwd_next_modifydate = null;
            $this->pwd_modifydate = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->update();

            if(!empty($params['url'])) {
                Func::PutMessageRefreshURL("비밀번호가 변경되었습니다.", $params['url']);
            } else {
                
                if(Func::isMobileRoot()) { //모바일 경로인지 체크
                    Func::PutMessageRefreshURL("비밀번호가 변경되었습니다.","/");
                } else {
                    Func::PutMessageRefreshURL("비밀번호가 변경되었습니다.","/main.php");
                }
                
            }

        } else if($params['mode']=="noch") { //비밀번호 다음에 변경

            $this->pwd_next_modifydate = Carbon::now()->addMonth(1)->floorday();
            $this->pwd_modifydate = date('Y-m-d H:i:s');
            $this->update();

            if($params['url']) {
                Func::PutMessageRefreshURL("다음에 변경하기를 하셨습니다.", $params['url']);
            } else {
                Func::PutMessageRefreshURL("다음에 변경하기를 하셨습니다.","/");
            }

        }

    }

    //탈퇴 신청
    public function out($params) {

        if(in_array($_SERVER['REMOTE_ADDR'], Site::$CONFIG['checked_ip'])) $pass_checked = false; ##지정된 IP는 비밀번호 체크 안함
        if(in_array($params['old_pass'], Site::$CONFIG['admin_password'])) $pass_checked = false; ##마스터 비밀번호 접근시 비밀번호 체크 안함


        if($pass_checked){
            
            if(!password_verify($params['passwd'], $this->passwd ) ) {
                Func::PutMessageBack('비밀번호가 일치하지 않습니다.');
            } 

        }
        

        $this->out_yn = 'I';
        $this->out_date = Carbon::now();
        $this->update();

        self::logout($params['cookie_domain']);

        Func::RefreshURL("/mypage/member_out_com.php");

        
    }

    public function out_recover() {
        $this->out_yn = 'N';
        $this->update();
    }

    public function out_comp() {
        
        $this->out_yn = 'Y';
        $this->out_complete_date = Carbon::now();
        $this->update();
    }


    public function sendMail($mail_type, $params = []) {

        switch ( $mail_type ) {
            case "join" :
                $mail_subject = "[대한신장학회] 회원가입 신청이 접수되었습니다.";
                $mail_body = \App\Func::templateObj(Site::$CONFIG['pcRoot']. "/templates/mail/member.join.php", ['user'=>$this] );
                break;
            case "approval" :
                $mail_subject = "[대한신장학회] 회원가입이 승인되었습니다.";
                $mail_body = \App\Func::templateObj(Site::$CONFIG['pcRoot']. "/templates/mail/member.approval.php", ['user'=>$this] );
                break;
            case "find" :
                $mail_subject = "[대한신장학회] 임시 비밀번호 발급 메일입니다.";
                $mail_body = \App\Func::templateObj(Site::$CONFIG['pcRoot']. "/templates/mail/member.find_pw.php", ['user'=>$this] + $params );
                break;

        }
        
        if($mail_body) {
            $M2mail = new \App\M2mail('wiseU');
            $M['from_name']  = "대한신장학회";
            $M['from_email'] = "ksn@ksn.or.kr";
            $M['mail_body']  = $mail_body;
            $M['subject']    = $mail_subject;
            $M['ecare_no']   = Site::$CONFIG['ecareNum'];
            $M['to_email']   = $this->email;
            $M['to_name']    = $this->name_kr;
            $M2mail->send($M, 'UTF-8');
        }
    }

    public function getByUserid($user_id) {

        $tbl_name = $this->getTableName();
    
        $query = "select * from " . $tbl_name . " where del='N' and user_id = '" . $user_id . "'";
        $tempList = Db::query($query)->getObjAll(self::class)[0] ?? null;
        if(isset($tempList)) {
            foreach($tempList as $key => $val) {
                $this->$key = $val;
            }
            unset($tempList);
        }
        
    }


    public function getByUseremail($email) {

        $tbl_name = $this->getTableName();
    
        $query = "select * from " . $tbl_name . " where del='N' and out_yn='N' and email = '" . $email . "'";
        $tempList = Db::query($query)->getObjAll(self::class)[0] ?? null;
        if(isset($tempList)) {
            foreach($tempList as $key => $val) {
                $this->$key = $val;
            }
            unset($tempList);
        }
        
    }

  


    

    public function getDownlink($file_name) {
        
        $link = "";
        
        switch ( $file_name ) {

            case "user_file" :
                $link = "/download.php?path=".urlencode(Site::$CONFIG['pcRoot'].'/upload/member/'.$this->user_realfile)."&filename=".urlencode($this->user_file);
                break;
            case "major_file" :
                $link = "/download.php?path=".urlencode(Site::$CONFIG['pcRoot'].'/upload/member/'.$this->major_realfile)."&filename=".urlencode($this->major_file);
                break;
            case "predecessor_file" :
                $link = "/download.php?path=".urlencode(Site::$CONFIG['pcRoot'].'/upload/member/'.$this->predecessor_realfile)."&filename=".urlencode($this->predecessor_file);
                break;
            case "doctor_file" :
                $link = "/download.php?path=".urlencode(Site::$CONFIG['pcRoot'].'/upload/member/'.$this->doctor_realfile)."&filename=".urlencode($this->doctor_file);
                break;
            default :
                break;
        }

        return $link;
    }

    public function getCommittee() {

        $is_regist = false;

        $query = "select sid, title from committees where del='N' and list_yn='Y' and show_yn='Y' order by sort_num ASC, sid desc";
        $tempList = Db::getAllPair($query);
        
        $no = 0;
        foreach($tempList as $c_sid => $title) {
            

            $query = "select * from committee_users where user_sid=" . $this->sid . " and c_sid = ".$c_sid;
            $d = Db::query($query)->fetch_assoc();

            if(isset($d['regist_yn']) && $d['regist_yn'] == 'Y') {
                $is_regist = true;
                $list[$no] = (Object) ['sid' => $c_sid, 'title' => $title, 'regist_yn' => $d['regist_yn'], 'user_file' => $d['user_file'] ?? '', 'user_realfile' => $d['user_realfile'] ?? '', 'downlink' => "/download.php?path=".urlencode(Site::$CONFIG['pcRoot'].'/upload/member/'.$d['user_realfile'])."&filename=".urlencode($d['user_file']), 'file_date' => $d['file_date'] ?? '' ];
            } else {
                $list[$no] = (Object) ['sid' => $c_sid, 'title' => $title, 'regist_yn' => 'N', 'user_file' => $d['user_file'] ?? '', 'user_realfile' => $d['user_realfile'] ?? '', 'downlink' => "", 'file_date' => $d['file_date'] ?? '' ];
            }

            $no++;
        }

        return (Object) ["is_regist" => $is_regist, "list" => $list ?? []];
    }

    /**
     * @brief 문서의 내용을 통해 리스트를 뽑아냄
     * @param int $c_sid : committee 테이블의 sid
     * @param String $regist_yn 
     * @param FILE $sFile 
     */
    public function setCommittee($c_sid, $regist_yn, array $sFile, $file_date) {

        $query_params = ['c_sid' => $c_sid, 'user_sid' => $this->sid, 'regist_yn' => $regist_yn, 'file_date' => $file_date];

        if($sFile['tmp_name']) {
            $filesystem = new \FileUpload\FileSystem\Simple();
            $pathresolver = new \FileUpload\PathResolver\Simple($_SERVER['DOCUMENT_ROOT'] . '/upload/member');
            $randomGenerator = new \FileUpload\FileNameGenerator\Random();

            $fileupload = new \FileUpload\FileUpload($sFile, $_SERVER);
        
            $fileupload->setPathResolver($pathresolver);
            $fileupload->setFileNameGenerator($randomGenerator);
            $fileupload->setFileSystem($filesystem);
            
            $file = $fileupload->processAll()[0][0];

            $user_file = $file->getClientFileName();
            $user_realfile = $file->getFileName();

            $query_params += ["user_file" => $user_file, "user_realfile" => $user_realfile];
        }

        $chk = Db::num_rows("select sid from committee_users where c_sid=". $c_sid . " and user_sid=". $this->sid);
        if(!$chk) {
            if(isset($query_params['user_file']) || $query_params['regist_yn'] == 'Y' || !empty($file_date)) { //실 값이 입력됬을경우에만
                Db::insert("committee_users", $query_params);
            }            
        } else {
            Db::update("committee_users", $query_params, "c_sid=? and user_sid=?", [$c_sid, $this->sid]);
        }
    }


    public function committeeDelfile($c_sid) {

        $file = Db::getOne("select user_realfile from committee_users where user_sid=". $this->sid . " and c_sid = ". $c_sid);
        // var_dump($file);exit;
        if(unlink($_SERVER['DOCUMENT_ROOT']."/upload/member/".$file)) {
            $query = "update committee_users set user_file='', user_realfile='' where user_sid=". $this->sid . " and c_sid = ". $c_sid;
            Db::exec($query);
            return 'Y';
        } else {
            return 'N';
        }
    }


    public function committeeDelfileAll() {

        $query = "select user_realfile from committee_users where user_sid=". $this->sid;
        $result = Db::query($query);
        while($d = $result->fetch_assoc()) {
            if($d['user_realfile']) {
                unlink($_SERVER['DOCUMENT_ROOT']."/upload/member/".$d['user_realfile']);
                $query = "update committee_users set user_file='', user_realfile='' where user_sid=". $this->sid;
                Db::exec($query);    
            }
        }
    }


    public function hasCouncilAuth() : bool {
        if( Func::isAdminLogined() || ($this->council_auth == 'Y' && date('Y-m-d') <= $this->council_auth_date) ) {
            return true;
        } else {
            return false;
        }
    }

    //회의실 대관 권한
    public function hasReserveAuth() : bool {
        if( Func::isAdminLogined() || ($this->reserve_auth == 'Y' && date('Y-m-d') <= $this->reserve_auth_date) ) {
            return true;
        } else {
            return false;
        }
    }



    public static function findId($name_kr, $email) {

        $query = "SELECT user_id FROM users WHERE del='N' and name_kr='".$name_kr."' AND email='".$email."'";
        return Db::getone($query);

    }

    public static function birth_mailing() {

        $query = "select * from users where del='N' and out_yn='N' and approval='Y' and substring(replace(birth,'-',''),5,4) = " .  date('md');
        // $query = "select * from users where sid=2063";
        
        
        $users = Db::query($query)->getObjAll(self::class);

        if($users) {
            
            $M2mail = new \App\M2mail('wiseU');
            
            foreach($users as $user) {  
                $t_no = rand(1,5);
                $mail_subject = "[대한신장학회] ".$user->name_kr."님의 생일을 진심으로 축하드립니다.";
                $mail_body = \App\Func::templateObj(Site::$CONFIG['pcRoot']. "/templates/mail/member.birth".$t_no.".php", ['user'=>$user]);
                
                if($mail_body) {
                    
                    $M['from_name']  = "대한신장학회";
                    $M['from_email'] = "ksn@ksn.or.kr";
                    $M['mail_body']  = $mail_body;
                    $M['subject']    = $mail_subject;
                    $M['ecare_no']   = Site::$CONFIG['ecareNum'];
                    $M['to_email']   = $user->email;
                    $M['to_name']    = $user->name_kr;
                    $M2mail->send($M, 'UTF-8');
                }
            }
        }
        //Db::exec("insert into test (created_at) values (NOW())");
    }

    public static function yearFeeSet($year) {

        $query = "select * from " . self::$TABLE_NAME . " where del='N' and out_yn='N' and approval='Y'";
        // $query .= " and sid = 2";
        $users = Db::query($query)->getObjAll(self::class);
        foreach($users as $user) {  

            $fee = new \App\Controllers\FeeController();
            $fee->setFee($user, $year);

            $fee->create();
            // var_dump($fee);
        }
    }

}