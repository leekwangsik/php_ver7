<?php
namespace App\Controllers;

use App\Models\FeeUser;
use App\Conn\Db;
use App\Site;
use App\Controller;

class FeeUserController extends Controller {

    use FeeUser, Site;

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

    public function get(int $user_sid) {
        $query = "select * from fee_users where user_sid = " . $user_sid;
        $tempList = Db::query($query)->getObjAll(self::class)[0] ?? null;

        if(isset($tempList)) {
            foreach($tempList as $key => $val) {
                $this->$key = $val;
            }
            unset($tempList);            
        }

        return $this;
    }

    public function createByFee(\App\Controllers\FeeController $fee) {

        if($this->sid) {
            DB::exec('update fee_users set year_price = JSON_SET(year_price, "$.'.$fee->year.'", "'.$fee->pay_status.'") WHERE sid=' . $this->sid);
        } else {
            $this->set( ["user_sid"=>$fee->user_sid, "year_price" => json_encode([$fee->year => $fee->pay_status]) ] );
            $this->create();
        }

    }
    
    public function sendMail($mail_type, \App\Controllers\UserController $user = null, $mode='send') {
        
        if(!$user) {
            $user = new \App\Controllers\UserController($this->user_sid);
        }        

        switch ( $mail_type ) {
            case "guide" :     
                
                $query = "select year, price from fees where del='N' and user_sid=" . $user->sid . " order by year desc limit 3";
                $year_price = DB::getAllPair($query);

                $mail_subject = "[대한신장학회] 연회비 납부 안내";
                $mail_body = \App\Func::templateObj($_SERVER['DOCUMENT_ROOT']. "/templates/mail/fee.guide.php", ["fee_user"=>$this, "user"=>$user, "year_price"=>$year_price] );
                break;

            case "info" :     
            
                $query = "select year, price from fees where del='N' and user_sid=" . $user->sid . " order by year desc limit 3";
                $year_price = DB::getAllPair($query);

                $mail_subject = "[대한신장학회] 연회비 납부 안내";
                $mail_body = \App\Func::templateObj($_SERVER['DOCUMENT_ROOT']. "/templates/mail/fee.info.php", ["fee_user"=>$this, "user"=>$user, "year_price"=>$year_price] );
                break;

        }
        
        

        if($mail_body) {

            if($mode == 'preview') {
                echo $mail_body;
            } else {

                $M2mail = new \App\M2mail('wiseU');
                $M['from_name']  = "대한신장학회";
                $M['from_email'] = "ksn@ksn.or.kr";
                $M['mail_body']  = $mail_body;
                $M['subject']    = $mail_subject;
                $M['ecare_no']   = Site::$CONFIG['ecareNum'];
                $M['to_email']   = $user->email;
                $M['to_name']    = $user->name_kr;


                // if($mail_type == 'info') { // 임시 테스트
                //     $M['to_email']   = 'registry@ksn.or.kr';
                //     $M['to_email']   = 'zmi77@m2community.co.kr';
                //     $M['to_name']    = $user->name_kr;
                // }
                
                $M2mail->send($M, 'UTF-8');

            }

           
        }
    }

    public function getYearStatus($year) {

        if($this->year_price) {
            $year_price = json_decode($this->year_price, true);
            $status = !empty($year_price[$year]) ? \App\Models\Fee::$PAYSTATUS[$year_price[$year]] : "-";
        }

        return $status ?? "-";
    }

    public function getRecentPay($approval_year) : bool {

        if(!$approval_year) {
            return false;
        }

        if($this->year_price) {

            
            $year_price = json_decode($this->year_price, true);

            if($approval_year == date('Y')) { //당해년도 가입자

                if( isset($year_price[date('Y')]) && ($year_price[date('Y')] == 'Y' || $year_price[date('Y')] == 'M') ) {
                    return true;
                } else {
                    return false;
                }

            } else {

                $pay_confirm_year = date('Y') - $approval_year;
                if($pay_confirm_year > 3) $pay_confirm_year = 3; //확인할 년수

                $pay_chk_year = 0; //확인된 년수

                for($i=1; $i <= $pay_confirm_year; $i++) {
                    if(isset($year_price[date('Y')-$i])) {
                        if($year_price[date('Y')-$i] == 'Y' || $year_price[date('Y')-$i] == 'M') { //납부 또는 면제시
                            $pay_chk_year++;
                        }
                    }
                }
                
                return $pay_confirm_year === $pay_chk_year;
            }
    
        } else {
            return false;
        }

        


        // $pay_confirm_year = 3; //확인할 년수
        // $pay_chk_year = 0; //확인된 년수

        // if($this->year_price) {

        //     $year_price = json_decode($this->year_price, true);

        //     for($i=0; $i< $pay_confirm_year; $i++) {
        //         if(isset($year_price[date('Y')-$i])) {
        //             if($year_price[date('Y')-$i] == 'Y' || $year_price[date('Y')-$i] == 'M') { //납부 또는 면제시
        //                 $pay_chk_year++;
        //             }
        //         } else {
        //             $pay_chk_year++;
        //         }
        //     }
            
        //     return $pay_confirm_year === $pay_chk_year;
    
        // } else {
        //     return false;
        // }

    }

    public function getAllpay() : bool {
    	
        if($this->year_price) {

            $year_price = json_decode($this->year_price, true);
            if(count($year_price) == 0) {
                return false;
            }

            foreach($year_price as $year => $state) {

                if(date('Y') < $year) { //올해 이후에 등록한 내역이 있을경우
                    continue;
                }
            	
                if($state != 'Y' && $state != 'M') { //납부 또는 면제시
                    return false;
                }
            }

            return true;
    
        } else {
            return false;
        }

    }

    public function setPayStatus($year, $status) {
        DB::exec('update fee_users set year_price = JSON_SET(year_price, "$.'.$year.'", "'.$status.'") WHERE sid=' . $this->sid);
    }

    public function delPayStatus($year) {
        DB::exec('update fee_users set year_price = JSON_REMOVE(year_price, "$.'.$year.'") WHERE sid=' . $this->sid);
    }
}