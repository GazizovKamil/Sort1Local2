<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\CashDesk;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class CashDesks extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_cash_desk($request) {
        $db = DB::getInstance();
        if(!isset($request->cash_desk_id) && (int)$request->cash_desk_id<1){
            $cash_desk=new CashDesk();
        }
        else 
            $cash_desk=new CashDesk((int)$request->cash_desk_id);
        
        if(!empty($request->name)) $cash_desk->name=htmlentities($request->name,ENT_QUOTES);
        if(!empty($request->user_id)){
             if((int)$request->user_id>0) $cash_desk->user_id=(int)$request->user_id;
             else {
                 if((int)$request->user_id==-1) {
                     $cash_desk->user_id=(int)$request->user_id;
                     $cash_desk->main_kassa=1;
                 }
             }
        }
        if((int)$cash_desk->sklad_id==0 && isset($_SESSION['my_sklad_id'])) $cash_desk->sklad_id=$_SESSION['my_sklad_id'];
        else {
            if((int)$request->user_id!=-1) return self::_error_arr("Не указан пользователь, выберите пользователя к которому привязана касса");
        }
        $save=$cash_desk->Save();
        switch($save){
            case 0: 
                return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_cash_desks($request){
        $db = DB::getInstance();
            $sql="select * from cash_desks where main_company_id=?i and deleted=0 and sklad_id=?i";
            $res=$db->getAll($sql,$_SESSION['main_company'],$_SESSION['my_sklad_id']);
            if($res)
                $users=$db->getInd("id","select id,name,middlename,lastname from users where id in (?a) order by create_date desc",array_column($res,"user_id"));
            else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "cash_desks"=>$res,
                "users"=>$users
            );
        }
        else 
            return array("status"=>"ok","msg"=>"","cash_desks"=>array());
    }

    public static function get_cash_desk($request){
        $db = DB::getInstance();
        if(!isset($request->cash_desk_id) || (int)$request->cash_desk_id<1){
            return array("status"=>"ok","msg"=>"","cash_desk"=>array(),"users"=>$users,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from cash_desks cd 
        where cd.id=?i and cd.main_company_id=?i and deleted=0";
        $res=$db->getRow($sql,(int)$request->cash_desk_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","cash_desk"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
    }

    public static function get_cash_desk_history($request){
        $db = DB::getInstance();
        if(!isset($request->cash_desk_id) || (int)$request->cash_desk_id<1){
            return array("status"=>"ok","msg"=>"","cashdesk_history"=>array());
        }
        if(empty($request->date_from)){
            $date_from=date("Y-m-d",strtotime("1 month ago"));
        }
        else $date_from=date("Y-m-d",strtotime($request->date_from));
        if(empty($request->date_to)){
            $date_to=date("Y-m-d")." 23:59:59";
        }
        else $date_to=date("Y-m-d",strtotime($request->date_to))." 23:59:59";
        $db = DB::getInstance();
        $sql="select * from cashdesk_history where cashdesk_id=?i and main_company_id=?i and create_date>=?s and create_date<=?s";
        $res=$db->getAll($sql,(int)$request->cash_desk_id,$_SESSION['main_company'],$date_from,$date_to);
        $res[]=array(
            "date"=>date("Y-m-d"),
            "cashdesk_id"=>(int)$request->cash_desk_id,
            "summ"=>$db->getOne("select summ from cash_desks where id=?i",(int)$request->cash_desk_id),
            "create_date"=>date("Y-m-d H:i:s"),
            "main_company_id"=>$_SESSION["main_company"]
        );
        return array("status"=>"ok","msg"=>"","cash_desk_history"=>$res);
    }

    public static function get_cash_desk_sverka($request){
        $db = DB::getInstance();
        if(!isset($request->cash_desk_id) || (int)$request->cash_desk_id<1){
            return array("status"=>"ok","msg"=>"","cash_desk_sverka"=>array());
        }
        if(empty($request->date)){
            $date=date("Y-m-d");
        }
        else $date=date("Y-m-d",strtotime($request->date));
        $yesterday=date("Y-m-d",strtotime($request->date." -1 day"));
        $db = DB::getInstance();
        $cashdesk_history=array(
            "yesterday"=>$db->getRow("select * from cashdesk_history where date=?s and cashdesk_id=?i",$yesterday,(int)$request->cash_desk_id),
            "today"=>(
                strtotime($date)<strtotime(date("Y-m-d"))?
                $db->getRow("select * from cashdesk_history where date=?s and cashdesk_id=?i",$date,(int)$request->cash_desk_id):
                array("date"=>date("Y-m-d"),
                    "cashdesk_id"=>(int)$request->cash_desk_id,
                    "summ"=>$db->getOne("select summ from cash_desks where id=?i",(int)$request->cash_desk_id),
                    "create_date"=>date("Y-m-d H:i:s"),
                    "main_company_id"=>$_SESSION["main_company"]
                )
            ),
        );
        $zakazes=$db->getCol("select id from zakaz where delivery_type=1 and delivery_type_id=?i",$_SESSION['my_sklad_id']);
        $zakazes1=$db->getCol("select id from zakaz where delivery_type=2 and fullfilment_id=?i",$_SESSION['my_sklad_id']);
        $zakazes=array_unique(array_merge($zakazes,$zakazes1));
        $sql="select * from payment where (payment_type=1 or payment_type=3) and main_company_id=?i and create_date>=?s and create_date<=?s and deleted=0 and zakaz_id in (?b)";
        $payments=$db->getAll($sql,$_SESSION['main_company'],$date,$date." 23:59:59",$zakazes);
        $sql="select * from RKOs where main_company_id=?i and create_date>=?s and create_date<=?s and from_cashdesk=?i and deleted=0";
        $RKOs=$db->getAll($sql,$_SESSION['main_company'],$date,$date." 23:59:59",(int)$request->cash_desk_id);
        $sql="select * from PKOs where main_company_id=?i and create_date>=?s and create_date<=?s and to_cashdesk=?i and deleted=0";
        $PKOs=$db->getAll($sql,$_SESSION['main_company'],$date,$date." 23:59:59",(int)$request->cash_desk_id);
        return array("status"=>"ok","msg"=>"","payments"=>$payments,"PKOs"=>$PKOs,"RKOs"=>$RKOs,"cashdesk_history"=>$cashdesk_history);
    }

    public static function delete_cash_desk($request){
        $db = DB::getInstance();
        if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->cashdesk_id) && (int)$request->cashdesk_id<1){
            return self::_error_arr("Не указан номер кассы");
        }
        else 
            $cashdesk=new CashDesk((int)$request->cashdesk_id);
        $cashdesk->deleted=1;
        $cashdesk->save();

        return array("status"=>"ok","msg"=>"");
    }    

}
?>