<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\PlannedDealerPayment;
use Sort1API\Components\CashDesk;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class PlannedDealerPayments extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_planned_dealer_payment($request) {
        $db = DB::getInstance();
        if(!isset($request->planned_dealer_payment_id) && (int)$request->planned_dealer_payment_id<1){
            $planned_dealer_payment=new PlannedDealerPayment();
        }
        else 
            $planned_dealer_payment=new PlannedDealerPayment((int)$request->planned_dealer_payment_id);
        $planned_dealer_payment_old_sum=$planned_dealer_payment->summ;
        if(isset($request->descr)) $planned_dealer_payment->descr=$request->descr;
        if(isset($request->repeatedly) && $request->repeatedly=="on") {
            $planned_dealer_payment->repeatedly=1;
            if(isset($request->repeat_period)) $planned_dealer_payment->repeat_period=(int)$request->repeat_period;
        }
        else {
            $planned_dealer_payment->repeatedly=0;
        }
        if(isset($request->summ) && (float)$request->summ>0) $planned_dealer_payment->summ=(float)$request->summ;
        else {
            return self::_error_arr("Не указана сумма списания");
        }
        if(isset($request->payment_date)) {
            $planned_dealer_payment->day_of_month=date("d",strtotime($request->payment_date));
            $planned_dealer_payment->month=date("m",strtotime($request->payment_date));
            $planned_dealer_payment->year=date("Y",strtotime($request->payment_date));
        }
        $save=$planned_dealer_payment->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_planned_dealer_payments($request){
        $db = DB::getInstance();
        if(isset($request->date_from)) $date_from=date("Y-m-d",strtotime($request->date_from));
        else $date_from=date("Y-m-d",strtotime("1 month ago"));
        if(isset($request->date_to)) $date_to=date("Y-m-d",strtotime($request->date_to));
        else $date_to=date("Y-m-d");
        $sql="select * from planned_dealer_payments where main_company_id=?i and deleted=0  and create_date>=?s and create_date<=?s order by create_date desc";
        $res=$db->getAll($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
        if($res)
            $users=$db->getInd("id","select id,name,middlename,lastname from users where (id in (?b) or id in (?b))order by create_date desc",array_column($res,"user_id"),array_column($res,"confirmed_by"));
        else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "planned_dealer_payments"=>$res,
                "users"=>$users,
                "date_from"=>$date_from,
                "date_to"=>$date_to
            );
        }
        else 
            return array(
                "status"=>"ok",
                "msg"=>"","planned_dealer_payments"=>array(),
                "date_from"=>$date_from,
                "date_to"=>$date_to
            );
    }

    public static function get_planned_dealer_payment($request){
        $db = DB::getInstance();
        if(!isset($request->planned_dealer_payment_id) || (int)$request->planned_dealer_payment_id<1){
            return array("status"=>"ok","msg"=>"","planned_dealer_payment"=>array(),"users"=>$users,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from planned_dealer_payments cd 
        where cd.id=?i and cd.main_company_id=?i";
        $res=$db->getRow($sql,(int)$request->planned_dealer_payment_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","planned_dealer_payment"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0 and roles<10",$_SESSION['main_company']));
    }

    public static function delete_planned_dealer_payment($request){
        $db = DB::getInstance();
        //if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->planned_dealer_payment_id) && (int)$request->planned_dealer_payment_id<1){
            return self::_error_arr("Не указан номер прихода");
        }
        else 
            $planned_dealer_payment=new PlannedDealerPayment((int)$request->planned_dealer_payment_id);
        $planned_dealer_payment->deleted=1;
        $planned_dealer_payment->save();
        return array("status"=>"ok","msg"=>"");
    }

}
?>