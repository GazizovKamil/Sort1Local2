<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Encashment;
use Sort1API\Components\CashDesk;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class Encashments extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_encashment($request) {
        $db = DB::getInstance();
        if(!isset($request->encashment_id) || (int)$request->encashment_id<1){
            $encashment=new Encashment();
        }
        else 
            $encashment=new Encashment((int)$request->encashment_id);
        
        if(isset($request->from_cashdesk) && (int)$request->from_cashdesk>0) $encashment->from_cashdesk=(int)$request->from_cashdesk;
        else return array("status"=>"err","err"=>"Не указана касса с которой происходит инкассация");
        if(isset($request->remain)) $encashment->remain=(int)$request->remain;
        if(isset($request->summ) && (float)$request->summ>0) $encashment->summ=(float)$request->summ;
        else {
            return self::_error_arr("Не указана сумма инкассации");
        }
        $cashdesk=new CashDesk((int)$request->from_cashdesk);
        //if($_SESSION['roles']>4 && $cashdesk->user_id==-1) return self::_error_arr("Не хватает прав");
        //if((int)$_SESSION['roles']>2 && (int)$encashment->id>0) return self::_error_arr("Не хватает прав");
        //echo $_SESSION['roles']." ".$encashment->id." ".((int)$_SESSION['roles']>2 && (int)$encashment->id>0)."\n";
        if($cashdesk->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Не ваша касса");
        //if($cashdesk->user_id!=$_SESSION['user_id'] && $encashment->id==0) return self::_error_arr("Не ваша касса");
        if($cashdesk->summ<$encashment->summ && $encashment->id==0) return self::_error_arr("В кассе не хватает денег для инкассации");
        if(isset($request->confirmed_summ) && (float)$request->confirmed_summ>0) {
            $encashment->confirmed_summ=$request->confirmed_summ;
            $encashment->confirmed_by=$_SESSION['user_id'];
            $encashment->confirmed=1;
        }
        else {
            $encashment->confirmed_summ=0;
        }
        if($encashment->id==0) $cashdesk->summ-=$encashment->summ;
        else {

        }
        $save_cashdesk=$cashdesk->save();
        if(!$save_cashdesk) return self::_error_arr("Произошла ошибка при сохранении кассы");
        $save=$encashment->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_encashments($request){
        $db = DB::getInstance();
        $parsed="";
        if(isset($request->encashment_filter_date_from)){
            $parsed.=$db->parse(" and create_date>=?s",date("Y-m-d",strtotime($request->encashment_filter_date_from))." 00:00:00");
        }
        if(isset($request->encashment_filter_date_to)){
            $parsed.=$db->parse(" and create_date<=?s",date("Y-m-d",strtotime($request->encashment_filter_date_to))." 23:59:59");
        }
        
            $sql="select * from encashments where main_company_id=?i and deleted=0 and from_cashdesk in (select id from cash_desks where main_company_id=?i and sklad_id=?i) ?p order by create_date desc";
            $res=$db->getAll($sql,$_SESSION['main_company'],$_SESSION['main_company'],$_SESSION['my_sklad_id'],$parsed);
            if($res)
                $users=$db->getInd("id","select id,name,middlename,lastname from users where (id in (?b) or id in (?b)) order by create_date desc",array_column($res,"user_id"),array_column($res,"confirmed_by"));
            else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "encashments"=>$res,
                "users"=>$users
            );
        }
        else 
            return array("status"=>"ok","msg"=>"","encashments"=>array());
    }

    public static function get_encashment($request){
        $db = DB::getInstance();
        if(!isset($request->encashment_id) || (int)$request->encashment_id<1){
            return array("status"=>"ok","msg"=>"","encashment"=>array(),"users"=>$users,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from encashments cd 
        where cd.id=?i and cd.main_company_id=?i";
        $res=$db->getRow($sql,(int)$request->encashment_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","encashment"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0 and roles<10",$_SESSION['main_company']));
    }

    public static function delete_encashment($request){
        $db = DB::getInstance();
        if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->encashment_id) && (int)$request->encashment_id<1){
            return self::_error_arr("Не указан номер инкассации кассы");
        }
        else 
            $encashment=new Encashment((int)$request->encashment_id);
        $cashdesk=new CashDesk($encashment->from_cashdesk);
        $cashdesk->summ+=$encashment->summ;
        $cashdesk->save();
        $encashment->deleted=1;
        $encashment->save();
        return array("status"=>"ok","msg"=>"");
    }

}
?>