<?php
namespace App\Controllers;

use App\Site;
use App\Controller;
use Carbon\Carbon;

use App\Db;

class MailController extends Controller {

    public static $TABLE_NAME = "emails";
    public static $FILE_PATH = "/upload/group_mail";

    public static $TO_NAME_GROUP = ['1'=>'회원발송','2'=>'주소록발송','3'=>'개별발송','99'=>'테스트발송'];
    public static $BTN_ARR = ['1'=>'자세히 보기','2'=>'바로가기','N'=>'사용안함'];
    public static $BTN_URL_ARR = ['1'=>'/admin/mail/image/mail_btn.png', '2'=>'/admin/mail/image/mail_btnHome.png'];

    public static $TEMPLATE_ARR = ['1'=>'템플릿 1', '5'=>'템플릿 5',/*, '3'=>'템플릿 3',*/ '4'=>'템플릿 4', '2'=>'템플릿 2', '99'=>'템플릿 없음'];

    //[wiseU].[dbo].[NVECARESENDLOG]
    public static $ERROR_CODE = 
    [
        '000' => 'mas에서 xure로 발송성공',
        '250' => '성공',
        '421' => '서비스 활용 불가능(상대방 SMTP의 시스템 문제)',
        '450' => '우편함이 잠긴 상태',
        '451' => '서버 에러(상대방 SMTP의 시스템 문제)',
        '452' => '시스템 저장 공간이 부족함',
        '550' => '사용자 없음',
        '552' => '메일 박스 용량 초과',
        '553' => '우편함 이름이 유효하지 않아서 명령어가 중단됨',
        '554' => '트랜잭션이 실패함',
        '600' => '호스트가 바쁘거나, 요청된 포트에서 서비스가 제공되지 않음',
        '601' => '연결 시간 초과',
        '602' => 'Connection Failure',
        '603' => 'Connection Error',
        '604' => '응답을 기다리던중 접속이 끊기거나 IOException이 발생',
        '610' => 'Unknown Host',
        '700' => '통신 중 접속이 끊어짐',
        '701' => 'IOExcepition',
        '702' => '응답 코드의 길이가 3 이하',
        '703' => '명령어 전송 또는 응답 코드를 기다리는 중 IOException',
        '704' => 'SMTP TIME OUT',
        '800' => 'Invalid Address',
        '888' => '기타 건수',
        '999' => '수신거부된 사용자'
    ];

    public $ecare_no = 130;

    public $sid;
    public $fid;
    public $subject;
    public $send_name;
    public $send_email;
    public $to_name_group;
    public $member_gubun;

    public $target_mails;
    public $testing_mail;
    
    public $btn_type = 'N';
    public $btn_link;
    public $mail_template = '5';
    public $content;

    public $user_file1;
    public $user_realfile1;
    public $user_file2;
    public $user_realfile2;
    public $user_file3;
    public $user_realfile3;
    public $user_file4;
    public $user_realfile4;
    public $user_file5;
    public $user_realfile5;
    public $user_file6;
    public $user_realfile6;
    public $user_file7;
    public $user_realfile7;
    public $user_file8;
    public $user_realfile8;
    public $user_file9;
    public $user_realfile9;
    public $user_file10;
    public $user_realfile10;


    public function getReadSend() {
        return !empty($this->send_date) ? 
        $this->total_read."/".$this->total_send. " (". round(($this->total_read/$this->total_send)*100) . ("%)")
        : "-";
    }

    

    public function getSenddate() {
        //return $this->send_date ? Carbon::create($this->send_date)->format('Y.m.d') : "-";
    }
    

    public function __construct($sid = "")
    {       
        if(!empty($sid)) {
            $this->get($sid);
        }
        return $this;
    }

    public function getTableName() {
        return $this::$TABLE_NAME;
    }


    public function setCreate($post_datas, $post_files) {

        $to_name_group = $post_datas['to_name_group'];

        if($to_name_group == '99') {
            $member_gubun_text = "";

        } else {
            $_post_member_gubun = $_POST['member_gubun'.$to_name_group] ?? [];
            $member_gubun_text = implode(',', $_post_member_gubun);
        }
        

        //IE에서 복붙시 붙는 주석 제거하는 부분
        $pattern = '/<!--(.*?)-->/is';
        $post_datas['content'] = preg_replace($pattern, "", $post_datas['content']);

        

        $this->set($post_datas);
        $this->set(['member_gubun'=>$member_gubun_text]);


        # 파일 생성
        for($i=1; $i<=10; $i++) {
            if(!empty($post_files['user_file'.$i]['tmp_name'])) {
                $this->setFile("user_file".$i, $post_files['user_file'.$i], Site::$CONFIG['uploadRoot'] . self::$FILE_PATH);

            } else if(!empty($post_datas['o_user_file'.$i])  ) {

                if(isset($post_datas['file_del'.$i]) && $post_datas['file_del'.$i]=='Y') {

                } else {
                    $this->set([
                        'user_file'.$i => $post_datas['o_user_file'.$i],
                        'user_realfile'.$i => $post_datas['o_user_realfile'.$i]
                    ]);

                }

               
            }
        }

        

        if(!empty($this->sid)) {
            $this->update();
        } else {

            $query = "SELECT max(sid) FROM ". self::$TABLE_NAME;
	
            $sid = Db::get_one($query);
            $sid = $sid ? $sid+1 : 1;

            $this->set(["sid"=> $sid]);
            if(!$this->fid) {
                $this->set(["fid"=> $sid]);
            }

            $this->sid = $this->create();
        }        

    }


    public function htmlParse($mail_user_sid = '') {

        $btn_type = $this->btn_type;
        $btn_link = $this->btn_link;

        if(!empty($this->btn_type) && $this->btn_type != 'N') {
            $link_btn = "<a href='".$btn_link."' target='_blank'><img src='". Site::$CONFIG['pcUrl'] . MailController::$BTN_URL_ARR[$btn_type] . "'/></a>";
        }

        $attach_text = "<br/><div style='text-align:left;margin-top:10px;line-height:150%;'>";
            
        for($i=1;$i<=10;$i++){
            if($user_file = $this->{"user_file". $i}) {
                $attach_text .= "<span style='color:red;'>첨부파일</span> : <a href='".Site::$CONFIG['pcUrl']. self::$FILE_PATH . "/".$this->{"user_realfile".$i}."' target='_blank'>".$user_file."</a><br/>";
            }
        }

        $this->add(['attach_file'=>$attach_text]);
        $this->add(['link_btn'=>$link_btn ?? '']);

        $html = \App\Func::template(Site::$CONFIG['pcRoot']."/mail_templates/admin/template.mail".sprintf('%02d',$this->mail_template).".php", ['mail'=>$this]);

        if($mail_user_sid) {
            $html .= '<div style="display:none;"><img src="'.\App\Site::$CONFIG['pcUrl']."/admin/mail/read.php?number=".$mail_user_sid.'" border="0" width="0" height="0" style="height:0px;max-height:0px;border-width:0px;border-color:initial;line-height:0px;font-size:0px;overflow:hidden;display:none;" loading="lazy"/></div>';
        }

        return $html;
    }

    public function getTonamegroup() {
        if($this->to_name_group == '1') {
            return self::$TO_NAME_GROUP[$this->to_name_group] . ' (' . \App\Models\User::getLevels($this->member_gubun) . ')';
        } else if($this->to_name_group == '2') {
            return self::$TO_NAME_GROUP[$this->to_name_group] . ' (' . Db::getone("select group_concat(c_grname) from tp_addgrcode where sid in ( $this->member_gubun ) ") . ")";
        } else if($this->to_name_group == '3') {
            return self::$TO_NAME_GROUP[$this->to_name_group] . " (" .$this->target_mails . ")" ;
        } else if($this->to_name_group == '99') {
            return self::$TO_NAME_GROUP[$this->to_name_group] . " (". $this->testing_mail . ")";
        } 
    }

    public function setReadcount() {
        $total_read = Db::numrows("select sid from " . \App\Controllers\MailUserController::$TABLE_NAME . " where mail_sid=". $this->sid . " and read_yn ='Y'" );
        $this->total_read = $total_read;
        $this->update();
    }

    public static function getAddrGroup() {
        return Db::getObjAll("select * from tp_addgrcode order by sid desc") ?? null;
    }
    

}