<?php
namespace App\Models;

use Carbon\Carbon;
use App\Site;

trait User {
    public static $TABLE_NAME = "users";

    public static $LEVEL = ['A' => '미승인', '1' => '준회원', '2' => '정회원', '3' => '교신회원', '4' => '평의원', '5' => '명예회원', 'M' => '관리자'];
    public static $GUBUN = ['1' => '의사', '2' => '비의사'];
    public static $NATIONALITY = ['K' => '내국인', 'F' => '외국인'];
    public static $SEX = ['M' => '남', 'F' => '여'];
    public static $OFFICE_GUBUN = ["대학병원", "종합병원", "개원의", "기타"];
    public static $DEPARTMENT = ["신장내과", "내과", "병리과", "비뇨기과", "소아청소년과", "소화기내과", "약리과", "외과", "기타"];
    public static $RECEIVE_POST = ['F' => '직장', 'H' => '자택', 'N' => '수령안함'];
    public static $DEGREE = ["의학사"=>"의학사", "의학석사"=>"의학석사", "의학박사"=>"의학박사", "self"=>"직접입력"];
    public static $REGULAR_MEMBER_TYPE = ["1"=>"의사", "2"=>"이과학자"];
    public static $AGREE_YN = ["Y" => "예", "N" => "아니오"];

    public static $COUNCIL_AUTH = ['N' => '접근불가', 'Y' => '접근 가능'];

    public $sid;
    public $level = 'A';
    public $user_id;
    public $name_kr;
    public $passwd;
    public $gubun; //회원구분
    public $name_en_first;
    public $name_en_last;

    public $nationality; //국적
    public $birth;
    public $sex;
    public $email;
    public $phone;
    public $license_number;
    public $office_gubun;

    public $office_kr;
    public $office_en;
    public $department;
    public $department_etc;
    public $position;
    public $receive_post;
    public $office_zipcode;
    public $office_addr1;
    public $office_addr2;
    public $office_tel;

    public $zipcode;
    public $addr1;
    public $addr2;
    public $tel;
    public $user_file;
    public $user_realfile;

    public $special_number; //전문의번호
    public $special_dialysis_number; //투석 전문의번호
    public $depart_special_number; //분과전문의번호
    public $univ; //출신대학
    public $univ_year; //졸업년도
    public $degree; //최종학위
    public $degree_etc;
    public $degree_univ; //취득학교
    public $degree_year;

    public $major;
    public $major_hospital;
    public $major_sdate;
    public $major_edate;
    public $major_file;
    public $major_realfile;
    public $regular_member_request;

    public $regular_member_type;
    public $predecessor_hospital;
    public $predecessor_sdate;
    public $predecessor_edate;
    public $predecessor_file;
    public $predecessor_realfile;
    public $doctor_hospital;
    public $doctor_date;
    public $doctor_file;
    public $doctor_realfile;
    public $sms_yn;
    public $email_yn;
    public $search_yn;
    public $collect_yn;

    public $approval = 'N'; //승인여부
    public $approval_date;
    public $regular_member_date; // 정회원 승인일
    public $councilor_date; // 평의원 승인일
    public $council_auth = 'N';
    public $council_auth_date;

    public $memo;

    public function getJoinDate() {

        return $this->created_at ? Carbon::create($this->created_at)->format('Y-m-d') : "";
    }

    // public function getApprovalDate() {
    //     return $this->approval_date ? Carbon::create($this->approval_date)->format('Y-m-d') : "";
    // }

    // public function getCouncilorDate() {
    //     return $this->councilor_date ? Carbon::create($this->councilor_date)->format('Y-m-d') : "";
    // }

    public function getUpDate() {
        return $this->updated_at ? Carbon::create($this->updated_at)->format('Y-m-d') : "";
    }

    public function getOutDate() {
        return $this->out_date ? Carbon::create($this->out_date)->format('Y-m-d') : "";
    }

    public function getEmail() : array {
        if($this->email) {
            $email = explode("@", $this->email);
        } 

        return $email ?? [];
    }
    
    public function getPhone() {
        if($this->phone) {
            $phone = trim( str_replace(" ","",$this->phone) );
            if(strlen($phone) == 10) {
                $phone = substr($phone,0,3) . "-" . substr($phone,3,3) . "-" . substr($phone,6,4);
            } else if(strlen($phone) == 11) {
                $phone = substr($phone,0,3) . "-" . substr($phone,3,4) . "-" . substr($phone,7,4);
            }
        } 

        return $phone ?? "";
    }

    public function getOfficeAddr() {
        return $this->office_zipcode. ") " . $this->office_addr1 . " " . $this->office_addr2;
    }

    public function getMajorDate($gubun) {

        if($gubun == "s") {
            if($this->major_sdate) {
                $major_date = explode("-", $this->major_sdate);
            }
        } else if($gubun == 'e') {
            if($this->major_edate) {
                $major_date = explode("-", $this->major_edate);
            }
        }

        return $major_date ?? [];
    }

    public function getPredecessorDate($gubun) {

        if($gubun == "s") {
            if($this->predecessor_sdate) {
                $predecessor_date = explode("-", $this->predecessor_sdate);
            }
        } else if($gubun == 'e') {
            if($this->predecessor_edate) {
                $predecessor_date = explode("-", $this->predecessor_edate);
            }
        }

        return $predecessor_date ?? [];
    }
    
    public function getDoctorDate() {
        if($this->doctor_date) {
            $doctor_date = explode("-", $this->doctor_date);
        }

        return $doctor_date ?? [];
    }
    
    public function getUserfile() {        
        return $this->user_realfile ?  Site::$CONFIG['pcUrl'] . "/upload/member/" . $this->user_realfile
        : "";
    }

    /**
     * @brief 만나이 체크
     */
    public function getExemption(int $age = 65) {
        $birthday = date("Ymd", strtotime($this->birth));

        return floor( (date("Ymd") - $birthday) /10000 ) >= $age;
    }

    public function getGubun() {
        return $this->gubun ? self::$GUBUN[$this->gubun] : '';
    }

    public function getLevel() {
        return $this->level ? self::$LEVEL[$this->level] : '';
    }

    public function getSex() {
        return $this->sex ? self::$SEX[$this->sex] : '';
    }

    public static function getLevels($levels) {
        $level_txt_arr = [];
        $level = explode(',', $levels);
        foreach($level as $key) {
            $level_txt_arr[] = self::$LEVEL[$key];
        }

        return implode(',', $level_txt_arr);
        
    }


}