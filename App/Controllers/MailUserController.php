<?php
namespace App\Controllers;

use App\Controller;

class MailUserController extends Controller {

    public static $TABLE_NAME = "email_users";
    public static $_MEMBER_FIELD = [
        '성명'=>['c_name', 'name_kr'],
        '이메일'=>['c_email', 'email'],
        '휴대폰'=>['c_pcs', 'phone'],
        '소속'=>['c_office', 'office_kr'],
        
    ]; //회원과 연동하여 사용하는 부분 (주소록: 검색해서 입력)

    public $sid;
    public $mail_sid;
    public $name;
    public $email;
    public $seq;
    public $send_status;
    public $sended_at;
    public $read_at;

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

    public function send_ok($seq) {

        if(!empty($this->content)) {
            unset($this->content);
        }

        $this->set([
            'seq' => $seq,
            'send_status' => 'Y',
            'sended_at' => date('Y-m-d H:i:s')
        ]);

        $this->update();
    }

}