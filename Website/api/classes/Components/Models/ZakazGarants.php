<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ZakazGarant;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class ZakazGarants extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_zakaz_garant($request) {
        $db = DB::getInstance();
        if(!isset($request->ZakazGarant_id) && (int)$request->ZakazGarant_id<1){
            $ZakazGarant=new ZakazGarant();
        }
        else 
            $ZakazGarant=new ZakazGarant((int)$request->ZakazGarant_id);
        if(isset($request->descr)) $ZakazGarant->descr=$request->descr;
        if(isset($request->zakaz_garant)) $ZakazGarant->zakaz_garant=$request->zakaz_garant;
        if(isset($request->is_default) && $request->is_default=="on") {
            $db->query("update zakaz_garant set is_default=0 where main_company_id=?i and deleted=0",$_SESSION['main_company']);
            $ZakazGarant->is_default=1;
        }
        else $ZakazGarant->is_default=0;
        $save=$ZakazGarant->Save();
        switch($save){
            case 0: return array("status"=>"err","err"=>"Не удалось сохранить данные"); break;
            case 1:
                return array("status"=>"ok","msg"=>""); break;
        }
    }
    
    public static function get_zakaz_garants($request){
        $db = DB::getInstance();
            $sql="select * from zakaz_garant where main_company_id=?i and deleted=0 order by create_date desc";
            $res=$db->getAll($sql,$_SESSION['main_company']);
            if($res)
                $users=$db->getInd("id","select id,name,middlename,lastname from users where (id in (?a) or id in (?a))order by create_date desc",array_column($res,"user_id"),array_column($res,"confirmed_by"));
            else $users=array();
        
        
        if($res){
            return array(
                "status"=>"ok",
                "msg"=>"",
                "ZakazGarants"=>$res,
                "users"=>$users,
            );
        }
        else 
            return array(
                "status"=>"ok",
                "msg"=>"",
                "ZakazGarants"=>array(),
            );
    }

    public static function get_zakaz_garant($request){
        $db = DB::getInstance();
        if(!isset($request->ZakazGarant_id) || (int)$request->ZakazGarant_id<1){
            return array("status"=>"ok","msg"=>"","ZakazGarant"=>array(),"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0",$_SESSION['main_company']));
        }
        $db = DB::getInstance();
        $sql="select cd.* from zakaz_garant cd 
        where cd.id=?i and cd.main_company_id=?i";
        $res=$db->getRow($sql,(int)$request->ZakazGarant_id,$_SESSION['main_company']);
        return array("status"=>"ok","msg"=>"","ZakazGarant"=>$res,"users"=>$db->getAll("select id,name,lastname,middlename from users where company_id=?i and admin_disabled=0 and roles<10",$_SESSION['main_company']));
    }

    public static function delete_zakaz_garant($request){
        $db = DB::getInstance();
        if($_SESSION['roles']>2) return self::_error_arr("Не хватает прав");
        if(!isset($request->ZakazGarant_id) && (int)$request->ZakazGarant_id<1){
            return self::_error_arr("Не указан номер");
        }
        else 
            $ZakazGarant=new ZakazGarant((int)$request->ZakazGarant_id);
        $ZakazGarant->deleted=1;
        $ZakazGarant->save();
        return array("status"=>"ok","msg"=>"");
    }

}
?>