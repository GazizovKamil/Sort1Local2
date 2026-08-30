<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class OnlineProfiles extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_online_profile($request) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
      		      return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance();
            if(empty($request->name))
              return self::_error_arr("Укажите наименование профиля");
            else {
              if((int)$request->profile_id==0){
                if($db->query("insert ignore into user_api_config_profiles set name=?s,main_company_id=?i",$request->name,$_SESSION['main_company'])){
                  if($db->affectedRows()==1)
                    $profile_id=$db->insertId();
                  else
                    return self::_error_arr("Профиль с таким наименованием уже существует");
                }
                else {
                  return self::_error_arr("Профиль с таким наименованием уже существует");
                }
              }
              elseif ((int)$request->profile_id>0) {
                $res_upd=$db->query("update user_api_config_profiles set name=?s where id=?i",$request->name,$request->profile_id);

              }
            }
            return array("status"=>"ok","msg"=>"","err"=>"");
        }


	public static function get_online_profile($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from user_api_config_profiles where id=?i and main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->profile_id,$_SESSION['main_company']);
	    if ($res){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['profile_id']=(int)$request->profile_id;
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
    		$ret['profile_name']=$res['name'];
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

  public static function get_online_profiles($request) {
    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2 && (int)$_SESSION['roles']!=3) {
        //return self::_error_arr("У Вас нет прав для данного действия");
    }
    $db = DB::getInstance();
    $selected_profile_id=$db->getOne("select profile_id from company_online_profiles where user_id=?i and company_id=?i and profile_type=3",$_SESSION['user_id'],$_SESSION['main_company']);
    $selected_profiles=$db->getInd("profile_type","select profile_type,profile_id from company_online_profiles where company_id=?i",$_SESSION['main_company']);
    $sql="select * from user_api_config_profiles where main_company_id=?i and deleted=0";
    $res=$db->getAll($sql,$_SESSION['main_company']);
    if ($res){
      $ret['status']="ok";
      $ret['err']="";
      //$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
      $ret['profiles']=$res;
      $ret['selected_profile_id']=$selected_profile_id;
      $ret['selected_profiles']=$selected_profiles;
      $ret['msg']="";
    }
    else {
      $ret['status']="ok";
      $ret['err']="";
      //$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
      $ret['profiles']=array();
      $ret['selected_profiles']=array();
      $ret['msg']="";
    }
    if ($ret['status']=="err") return self::_error_arr($ret['err']);
    else return $ret;
}

  public static function save_company_online_profile($request) {
      if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
         // return self::_error_arr("У Вас нет прав для данного действия");
      }
      $db = DB::getInstance();
      if(empty($request->profile_id) || (int)$request->profile_id<=0){
          if(!empty($request->profile_type) && in_array((int)$request->profile_type,array("1","2","3"))) {
            $res=$db->query("delete from company_online_profiles where company_id=?i and profile_type=?i",$_SESSION['main_company'],(int)$request->profile_type);
          }
          else {
            return self::_error_arr("Укажите профиль");
          }
      }
      else {
        if(isset($request->profile_type) && (int)$request->profile_type!=0){
          if((int)$request->profile_type==1 || (int)$request->profile_type==2){
            $res=$db->query("delete from company_online_profiles where company_id=?i and profile_type=?i",$_SESSION['main_company'],(int)$request->profile_type);
            $parsed=$db->parse("insert into company_online_profiles set profile_type=?i,company_id=?i,profile_id=?i,user_id=?i 
              on duplicate key update profile_id=?i",(int)$request->profile_type,$_SESSION['main_company'],(int)$request->profile_id,$_SESSION['user_id'],(int)$request->profile_id);
          }
          else {
            $parsed=$db->parse("insert into company_online_profiles set profile_type=?i,company_id=?i,profile_id=?i,user_id=?i 
              on duplicate key update profile_id=?i",(int)$request->profile_type,$_SESSION['main_company'],(int)$request->profile_id,$_SESSION['user_id'],(int)$request->profile_id);
          }
          if($db->query("?p",$parsed)){
            // типа все ок
          }
          else {
            return self::_error_arr("Не удалось присвоить профиль");
          }
        }
        elseif ((int)$request->topology_id>0) {
          $res_upd=$db->query("update user_api_config_profiles set name=?s where id=?i",$request->name,$request->profile_id);

        }
      }
      return array("status"=>"ok","msg"=>"Успешно сохранено","err"=>"");
  }


  public static function get_company_online_profile($request) {
      if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
        return self::_error_arr("У Вас нет прав для данного действия");
      }
      $db = DB::getInstance();
      $sql="select * from company_online_profiles where profile_type=?i and company_id=?i";
      $res=$db->getRow($sql,(int)$request->profile_type,$_SESSION['main_company']);
      if ($res){
        $ret['status']="ok";
        $ret['err']="";
        $ret['profile_id']=(int)$request->profile_id;
        //$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
        $ret['profile_name']=$res['name'];
        $ret['msg']="";
      }
      if ($ret['status']=="err") return self::_error_arr($ret['err']);
      else return $ret;
  }

  public static function get_company_online_profiles($request) {
      if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
        return self::_error_arr("У Вас нет прав для данного действия");
      }
      $db = DB::getInstance();
      $sql="select * from company_online_profiles where company_id=?i";
      $res=$db->getInd("profile_type",$sql,$_SESSION['main_company']);
      if ($res){
        $ret['status']="ok";
        $ret['err']="";
        //$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
        $ret['company_profiles']=$res;
        $ret['msg']="";
      }
      else {
        $ret['status']="ok"; $ret['err']=""; $ret['company_profiles']=array(); $ret['msg']="";
      }
      $ret['profile_types']=$db->getAll("select * from online_profile_types");
      $ret['config_profiles']=$db->getAll("select id,name from user_api_config_profiles where main_company_id=?i",$_SESSION['main_company']);
      return $ret;
  }

  public static function get_deliverers_list($request){
    $db = DB::getInstance();
    $active_profile=$db->getOne("select profile_id from company_online_profiles where user_id=?i and company_id=?i and profile_type=3",$_SESSION['user_id'],$_SESSION['main_company']);
    if(!$active_profile){
      $active_profile=$db->getOne("select profile_id from company_online_profiles where company_id=?i and profile_type=2 limit 1",$_SESSION['main_company']);
    }
    if(!$active_profile){
      $active_profiles=$db->getAll("select profile_id from company_online_profiles where company_id=?i",$_SESSION['main_company']);
      if(count((array)$active_profiles)==1) $active_profile=$active_profiles[0]['profile_id'];
      else
        return array("status"=>"err","err"=>"Не установлен активный профиль, зайдите в настройки -> профили онлайн поиска , выберите ваш профиль и нажмите рядом -> Мой профиль или Сделать активным профилем розничного магазина");
    }
    $active_plugins=$db->getAll("select name,icon,plugin_id,show_basket from user_api_config where plugin_id in 
    (select plugin_id from user_api_config_values where company_id=?i and 
    config_profile_id=?i and tested=1 and enabled=1) order by name",$_SESSION['main_company'],$active_profile);
    if(count((array)$active_plugins)>0) return array("status"=>"ok","msg"=>"","deliverers"=>$active_plugins);
    else return array("status"=>"ok","msg"=>"","deliverers"=>array());
  }

  public static function get_deliverers_list_wzdc($request){
    $db = DB::getInstance();
    $active_profile=$db->getOne("select profile_id from company_online_profiles where user_id=?i and company_id=?i and profile_type=3",$_SESSION['user_id'],$_SESSION['main_company']);
    if(!$active_profile){
      $active_profile=$db->getOne("select profile_id from company_online_profiles where company_id=?i and profile_type=2 limit 1",$_SESSION['main_company']);
    }
    if(!$active_profile){
      $active_profiles=$db->getAll("select profile_id from company_online_profiles where company_id=?i",$_SESSION['main_company']);
      if(count((array)$active_profiles)==1) $active_profile=$active_profiles[0]['profile_id'];
      else
        return array("status"=>"err","err"=>"Не установлен активный профиль, зайдите в настройки -> профили онлайн поиска , выберите ваш профиль и нажмите рядом -> Мой профиль или Сделать активным профилем розничного магазина");
    }
    $active_plugins=$db->getAll("select uac.name,uac.icon,uac.plugin_id,uac.show_basket,pl_zakaz_count.count as zakaz_details from user_api_config uac 
    left join 
    (SELECT COUNT(pl_zak_sum.id) as count,pl_zak_sum.deliverer_id as plugin_id FROM (SELECT id,deliverer_id FROM zakaz_details 
    WHERE deliverer_type=3 AND status>=10 and status<20 and status<>14 and zakaz_id IN (SELECT id FROM zakaz WHERE main_company_id=?i AND create_date>?s AND create_date<?s)) AS pl_zak_sum
    GROUP BY pl_zak_sum.deliverer_id) as pl_zakaz_count on (pl_zakaz_count.plugin_id=uac.plugin_id)
    where uac.plugin_id in 
    (select plugin_id from user_api_config_values where company_id=?i and 
    config_profile_id=?i and tested=1 and enabled=1) order by name",$_SESSION['main_company'],$request->search_dealer_baskets_date_from.' 00:00:00',
    $request->search_dealer_baskets_date_to.' 23:59:59',$_SESSION['main_company'],$active_profile);
    
    if(count((array)$active_plugins)>0) return array("status"=>"ok","msg"=>"","deliverers"=>$active_plugins);
    else return array("status"=>"ok","msg"=>"","deliverers"=>array());
  }

  public static function delete_online_profile($request){
    if(empty($request->profile_id) || (int)$request->profile_id<=0) return self::_error_arr("Не укзан профиль");
    $db = DB::getInstance();
    $deleted_max_index=$db->getOne("select max(deleted_index) from user_api_config_profiles where name=(select name from user_api_config_profiles where id=?i)",(int)$request->profile_id);
    $sql="update user_api_config_profiles uacp set deleted=1,deleted_index=?i where id=?i and main_company_id=?i and deleted=0";
    $db->query($sql,(int)$deleted_max_index+1,(int)$request->profile_id,(int)$_SESSION['main_company']);
    if($db->affectedRows()>0){
      return array("status"=>"ok","msg"=>"Профиль перемещен в архив");
    }
    else {
      return self::_error_arr("Что-то пошло не так");
    }
  }

}
?>
