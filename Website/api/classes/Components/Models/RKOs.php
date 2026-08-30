<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\RKO;
use Sort1API\Components\CashDesk;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class RKOs extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_RKO($request) {
        $db = DB::getInstance();
        if(!isset($request->RKO_id) && (int)$request->RKO_id<1){
            $RKO=new RKO();
        }
        else 
            $RKO=new RKO((int)$request->RKO_id);
        $RKO_old_sum=$RKO->summ;
        if(isset($request->from_cashdesk) && (int)$request->from_cashdesk>0) $RKO->from_cashdesk=(int)$request->from_cashdesk;
        else return array("status"=>"err","err"=>"Не указана касса с которой происходит списание");
        if(isset($request->remain)) $RKO->remain=(int)$request->remain;
        if(isset($request->descr)) $RKO->descr=$request->descr;
        if(isset($request->payment_reason)) $RKO->payment_reason=$request->payment_reason;
        if(isset($request->summ) && (float)$request->summ>0) $RKO->summ=(float)$request->summ;
        else {
            return self::_error_arr("Не указана сумма списания");
        }
        $cashdesk=new CashDesk((int)$request->from_cashdesk);
        //if($_SESSION['roles']>4 && $cashdesk->user_id==-1) return self::_error_arr("Не хватает прав");
        //if($_SESSION['roles']>2 && $RKO->id>0) return self::_error_arr("Не хватает прав");
        if($cashdesk->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Не ваша касса");
        //if($cashdesk->user_id!=$_SESSION['user_id'] && $RKO->id==0 && $_SESSION['roles']>4) return self::_error_arr("Не ваша касса");
        if($cashdesk->summ<$RKO->summ && $RKO->id==0) return self::_error_arr("В кассе не хватает денег для списания");
        if($RKO->id==0) $cashdesk->summ-=$RKO->summ;
        else {
            $cashdesk->summ-=($RKO->summ-$RKO_old_sum);
        }
        $save_cashdesk=$cashdesk->save();
        if(!$save_cashdesk) return self::_error_arr("Произошла ошибка при сохранении кассы");
        $save=$RKO->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_RKOs($request){
        $db = DB::getInstance();
        if(isset($request->date_from)) $date_from=date("Y-m-d",strtotime($request->date_from));
        else $date_from=date("Y-m-d H:i:s",strtotime("1 month ago"));
        if(isset($request->date_to)) $date_to=date("Y-m-d",strtotime($request->date_to));
        else $date_to=date("Y-m-d");
            $sql="select * from RKOs where main_company_id=?i and deleted=0 and create_date>=?s and create_date<=?s and from_cashdesk in (select id from cash_desks where main_company_id=?i and sklad_id=?i) order by create_date desc";
            $res=$db->getAll($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$_SESSION['my_sklad_id']);
            if($res)
                $users=$db->getInd("id","select id,name,middlename,lastname from users where (id in (?a) or id in (?a))order by create_date desc",array_column($res,"user_id"),array_column($res,"confirmed_by"));
            else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "RKOs"=>$res,
                "users"=>$users,
                "date_from"=>$date_from,
                "date_to"=>$date_to
            );
        }
        else 
            return array(
                "status"=>"ok",
                "msg"=>"",
                "RKOs"=>array(),
                "date_from"=>$date_from,
                "date_to"=>$date_to
            );
    }

    public static function get_RKO($request){
        $db = DB::getInstance();
        if(!isset($request->RKO_id) || (int)$request->RKO_id<1){
            return array("status"=>"ok","msg"=>"","RKO"=>array(),"users"=>$users,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from RKOs cd 
        where cd.id=?i and cd.main_company_id=?i";
        $res=$db->getRow($sql,(int)$request->RKO_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","RKO"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0 and roles<10",$_SESSION['main_company']));
    }

    public static function delete_RKO($request){
        $db = DB::getInstance();
        //if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->RKO_id) && (int)$request->RKO_id<1){
            return self::_error_arr("Не указан номер списания");
        }
        else 
            $RKO=new RKO((int)$request->RKO_id);
        $cashdesk=new CashDesk($RKO->from_cashdesk);
        $cashdesk->summ+=$RKO->summ;
        $cashdesk->save();
        $RKO->deleted=1;
        $RKO->save();
        return array("status"=>"ok","msg"=>"");
    }

}
?>