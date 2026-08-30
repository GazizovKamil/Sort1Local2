<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\PKO;
use Sort1API\Components\CashDesk;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class PKOs extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_PKO($request) {
        $db = DB::getInstance();
        if(!isset($request->PKO_id) && (int)$request->PKO_id<1){
            $PKO=new PKO();
        }
        else 
            $PKO=new PKO((int)$request->PKO_id);
        $PKO_old_sum=$PKO->summ;
        if(isset($request->to_cashdesk) && (int)$request->to_cashdesk>0) $PKO->to_cashdesk=(int)$request->to_cashdesk;
        else return array("status"=>"err","err"=>"Не указана касса с которой происходит списание");
        if(isset($request->remain)) $PKO->remain=(int)$request->remain;
        if(isset($request->descr)) $PKO->descr=$request->descr;
        if(isset($request->payment_reason)) $PKO->payment_reason=$request->payment_reason;
        if(isset($request->summ) && (float)$request->summ>0) $PKO->summ=(float)$request->summ;
        else {
            return self::_error_arr("Не указана сумма списания");
        }
        $cashdesk=new CashDesk((int)$request->to_cashdesk);
       // if($_SESSION['roles']>4 && $cashdesk->user_id==-1) return self::_error_arr("Не хватает прав");
       // if($_SESSION['roles']>2 && $PKO->id>0) return self::_error_arr("Не хватает прав");
        if($cashdesk->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Не ваша касса");
        if($cashdesk->user_id!=$_SESSION['user_id'] && $PKO->id==0 && $_SESSION['roles']>4) return self::_error_arr("Не ваша касса");
        //if($cashdesk->summ<$PKO->summ && $PKO->id==0) return self::_error_arr("В кассе не хватает денег для списания");
        if($PKO->id==0) $cashdesk->summ+=$PKO->summ;
        else {
            $cashdesk->summ+=($PKO->summ-$PKO_old_sum);
        }
        $save_cashdesk=$cashdesk->save();
        if(!$save_cashdesk) return self::_error_arr("Произошла ошибка при сохранении кассы");
        $save=$PKO->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_PKOs($request){
        $db = DB::getInstance();
        if(isset($request->date_from)) $date_from=date("Y-m-d",strtotime($request->date_from));
        else $date_from=date("Y-m-d",strtotime("1 month ago"));
        if(isset($request->date_to)) $date_to=date("Y-m-d",strtotime($request->date_to));
        else $date_to=date("Y-m-d");
            $sql="select * from PKOs where main_company_id=?i and deleted=0  and create_date>=?s and create_date<=?s  and to_cashdesk in (select id from cash_desks where main_company_id=?i and sklad_id=?i) order by create_date desc";
            $res=$db->getAll($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$_SESSION['my_sklad_id']);
            if($res)
                $users=$db->getInd("id","select id,name,middlename,lastname from users where (id in (?b) or id in (?b))order by create_date desc",array_column($res,"user_id"),array_column($res,"confirmed_by"));
            else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "PKOs"=>$res,
                "users"=>$users,
                "date_from"=>$date_from,
                "date_to"=>$date_to
            );
        }
        else 
            return array(
                "status"=>"ok",
                "msg"=>"","PKOs"=>array(),
                "date_from"=>$date_from,
                "date_to"=>$date_to
            );
    }

    public static function get_PKO($request){
        $db = DB::getInstance();
        if(!isset($request->PKO_id) || (int)$request->PKO_id<1){
            return array("status"=>"ok","msg"=>"","PKO"=>array(),"users"=>$users,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from PKOs cd 
        where cd.id=?i and cd.main_company_id=?i";
        $res=$db->getRow($sql,(int)$request->PKO_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","PKO"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0 and roles<10",$_SESSION['main_company']));
    }

    public static function delete_PKO($request){
        $db = DB::getInstance();
        //if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->PKO_id) && (int)$request->PKO_id<1){
            return self::_error_arr("Не указан номер прихода");
        }
        else 
            $PKO=new PKO((int)$request->PKO_id);
        $cashdesk=new CashDesk($PKO->to_cashdesk);
        $cashdesk->summ-=$PKO->summ;
        $cashdesk->save();
        $PKO->deleted=1;
        $PKO->save();
        return array("status"=>"ok","msg"=>"");
    }

}
?>