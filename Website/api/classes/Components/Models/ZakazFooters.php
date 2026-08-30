<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ZakazFooter;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ZakazFooters extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_zakaz_footer($request) {
        $db = DB::getInstance();
        if(!isset($request->ZakazFooter_id) && (int)$request->ZakazFooter_id<1){
            $ZakazFooter=new ZakazFooter();
        }
        else 
            $ZakazFooter=new ZakazFooter((int)$request->ZakazFooter_id);
        if(isset($request->descr)) $ZakazFooter->descr=$request->descr;
        if(isset($request->zakaz_footer)) $ZakazFooter->zakaz_footer=$request->zakaz_footer;
        if(isset($request->is_default) && $request->is_default=="on") {
            $db->query("update zakaz_footers set is_default=0 where main_company_id=?i and deleted=0",$_SESSION['main_company']);
            $ZakazFooter->is_default=1;
        }
        else $ZakazFooter->is_default=0;
        $save=$ZakazFooter->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_zakaz_footers($request){
        $db = DB::getInstance();
            $sql="select * from zakaz_footers where main_company_id=?i and deleted=0 order by create_date desc";
            $res=$db->getAll($sql,$_SESSION['main_company']);
            if($res)
                $users=$db->getInd("id","select id,name,middlename,lastname from users where (id in (?a) or id in (?a))order by create_date desc",array_column($res,"user_id"),array_column($res,"confirmed_by"));
            else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "ZakazFooters"=>$res,
                "users"=>$users,
            );
        }
        else 
            return array(
                "status"=>"ok",
                "msg"=>"",
                "ZakazFooters"=>array(),
            );
    }

    public static function get_zakaz_footer($request){
        $db = DB::getInstance();
        if(!isset($request->ZakazFooter_id) || (int)$request->ZakazFooter_id<1){
            return array("status"=>"ok","msg"=>"","ZakazFooter"=>array(),"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from zakaz_footers cd 
        where cd.id=?i and cd.main_company_id=?i";
        $res=$db->getRow($sql,(int)$request->ZakazFooter_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","ZakazFooter"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0 and roles<10",$_SESSION['main_company']));
    }

    public static function delete_zakaz_footer($request){
        $db = DB::getInstance();
        if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->ZakazFooter_id) && (int)$request->ZakazFooter_id<1){
            return self::_error_arr("Не указан номер");
        }
        else 
            $ZakazFooter=new ZakazFooter((int)$request->ZakazFooter_id);
        $ZakazFooter->deleted=1;
        $ZakazFooter->save();
        return array("status"=>"ok","msg"=>"");
    }

}
?>