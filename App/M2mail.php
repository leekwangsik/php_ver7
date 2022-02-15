<?php
namespace App;

use \PDO;
use \PDOException;

use App\Controllers\MailController;
use App\Controllers\MailUserController;

class M2mail {

    // private const DB_HOST = "121.254.129.73";
	// private const DB_NAME = "wiseU";
	// private const DB_USER = "wiseu";	
	// private const DB_PASSWORD = "wiseu";

    private const DB_HOST = "121.254.129.104";
	private const DB_NAME = "lee_test";
	private const DB_USER = "leetest";	
	private const DB_PASSWORD = "1q2w3e4r!!";

    
    private static $pdo;

    private static $kk = 1;
    private static $sseq;
    private static $tseq;

    public $ECARE_NO;

    public $conn;
    public $SENDER_NM;
    public $SENDER;
    public $DATA;
    public $SUBJECT;
    public $RECEIVER;
    public $RECEIVER_NM;
    public $sendSel;


    public function __construct($sendSel) {       
        $this->sendSel = $sendSel;
    }

    private function conn() {
        if(is_null(self::$pdo)) { 
            self::$pdo = new PDO("dblib:host=". self::DB_HOST .":1433; dbname=". self::DB_NAME .";", self::DB_USER, self::DB_PASSWORD);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function get_sseq() {
        return self::$sseq;
    }

    public function set_sseq($sseq) {
        self::$sseq = $sseq;
    }

    public function set_kk($kk) {
        self::$kk = $kk;
    }

    public function get_seq() {
        return self::$tseq;
    }


    public function send(array $d, $charset='utf8') {
        if ($this->sendSel == 'wiseU') {
            $this->conn();
            $this->wiseU(['info'=>$d], $charset);

        } else if ($this->sendSel == 'sendmail') {
            //$this->Sendmail($d, $mail); //미구현
        } else {
            die("잘못된 접근");
        }
    }

    public function sendObj(MailController $mail, MailUserController $mail_user, $charset = "utf8") {
        if ($this->sendSel == 'wiseU') {
            $this->conn();
            $this->wiseU( ["mail"=>$mail, "mail_user"=>$mail_user], $charset);

        } else if (self::$sendSel == 'sendmail') {
            //$this->Sendmail($d, $mail); //미구현
        } else {
            die("잘못된 접근");
        }
    }


    private function wiseU(array $mail_info, $charset){
        
        if(count($mail_info) == 1) {
            $d = $mail_info['info'];
            $this->mailInfoSet($d);

        } else if(count($mail_info) == 2) {
            
            $mail = $mail_info['mail'];
            $mail_user = $mail_info['mail_user'];
            $this->mailInfoSetObj($mail, $mail_user);
        }

        // 미구현    
        // if($charset == 'EUC-KR' || $charset == 'euc-kr'|| $charset == 'euckr') {
        //     $d = $this->charsetConvertEucKr($d);
        // }
    
        //메일 정보 셋팅
        
    
        //수신자 이메일 있다면 발송
        if($this->RECEIVER) {

            if( empty(self::$sseq) ) { 
                self::$sseq = time().rand(11, 99).sprintf("%03s", $this->ECARE_NO); 
            }

            $SEQ = self::$tseq = self::$sseq . sprintf("%05s", self::$kk);

            $query = "
            insert into nvrealtimeaccept(
                    ECARE_NO,
                    RECEIVER_ID,
                    CHANNEL,
                    SEQ,
                    REQ_DT,
                    REQ_TM,
                    TMPL_TYPE,
                    RECEIVER_NM,
                    RECEIVER,
                    SENDER_NM,
                    SENDER,
                    SUBJECT,
                    SEND_FG,
                    DATA_CNT        
            ) values ( ?,?,?,?,?,?,?,?,?,?,?,?,?,? )";

            try {
                $sth = self::$pdo->prepare($query); 
                $sth->execute([
                    $this->ECARE_NO,
                    $SEQ,
                    'M', 
                    $SEQ,
                    date("Ymd"),
                    date("His"),
                    'T',
                    $this->RECEIVER_NM,
                    $this->RECEIVER,
                    $this->SENDER_NM,
                    $this->SENDER,
                    $this->SUBJECT,
                    'R',
                    1 
                ]);
                
            } catch (PDOException $e) {
                die($e->getMessage());			
            }

            //메일 body
            $query = "
            insert into NVREALTIMEACCEPTDATA(
                SEQ, 
                DATA_SEQ,  
                ATTACH_YN, 
                DATA        
            ) values ( ?,?,?,? )";

            try {
                $sth = self::$pdo->prepare($query); 
                $sth->execute([
                    $SEQ, 
                    self::$kk,
                    'N',
                    $this->DATA
                ]);
                
            } catch (PDOException $e) {
                die($e->getMessage());			
            }
            
            self::$kk++;
    
        }
    }

    //메일 정보 euc-kr로 변경
    private function charsetConvertEucKr($d) {
        foreach($d as $_tmp['k'] => $_tmp['v']) {
            if (is_array($d[$_tmp['k']])) {
            foreach($d[$_tmp['k']] as $_tmp['k1'] => $_tmp['v1'])
                $d[$_tmp['k']][$_tmp['k1']] = ${$_tmp['k']}[$_tmp['k1']] = iconv('UTF-8', 'EUC-KR',$_tmp['v1']);
            } else {
                $d[$_tmp['k']] = ${$_tmp['k']} = iconv('UTF-8', 'EUC-KR',$_tmp['v']);
            } 
        }

        return $d;
    }

    //메일 정보 셋팅
    private function mailInfoSet($d) {
        //발신자 명
        $this->SENDER_NM = $d['from_name'];

        //발신자 이메일
        $this->SENDER = $d['from_email'];

        //메일 내용
        $this->DATA = $d['mail_body'];

        //메일 제목
        $this->SUBJECT = $d['subject'];

        //이케어 번호
        $this->ECARE_NO = $d['ecare_no'];

        //수신자 이메일 처리
        $this->RECEIVER  = trim($d['to_email']);

        //수신자 성명
        $this->RECEIVER_NM  = trim($d['to_name']);

        //수신자 성명이 없다면 메일로 대체
        if(empty($this->RECEIVER_NM)) {
            $this->RECEIVER_NM = $this->RECEIVER;
        } 

    }


    private function mailInfoSetObj($mail, $mail_user) {
        //발신자 명
        $this->SENDER_NM = $mail->send_name;

        //발신자 이메일
        $this->SENDER = $mail->send_email;

        //메일 내용
        $this->DATA = $mail_user->content;

        //메일 제목
        $this->SUBJECT = $mail->subject;

        //이케어 번호
        $this->ECARE_NO = $mail->ecare_no;

        //수신자 이메일 처리
        $this->RECEIVER  = trim($mail_user->email);

        //수신자 성명
        $this->RECEIVER_NM  = trim($mail_user->name);

        //수신자 성명이 없다면 메일로 대체
        if(empty($this->RECEIVER_NM)) {
            $this->RECEIVER_NM = $this->RECEIVER;
        } 

    }

    ### db 끊기
    public function disconnect(){
        unset(self::$pdo);
    }


}