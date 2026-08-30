<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\SystemMessage;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class SystemMessages extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_system_message($request) {
          $db = DB::getInstance();
    	    if (isset($request->system_message_id)) $system_message_id=(int)$request->system_message_id;
    	    if (isset($system_message_id) && $system_message_id>0) {
    		      $system_message=new SystemMessage($system_message_id);
    	    }
    	    else
    		    $system_message=new SystemMessage();
    	    if (isset($request->company_id) && (int)$request->company_id>0) {
        		$companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
        		if ($companys && in_array($request->company_id,$companys))
        		    $system_message->company_id=(int)$request->company_id;
        		else {
        		    return self::_error_arr("Нельзя добавить склад к чужой компании");
        		}
    	    }
    	    else $system_message->company_id=$_SESSION['company_id'];
    	    if (isset($request->address)) $system_message->address=$request->address;
    	    if (isset($request->descr)) $system_message->descr=$request->descr;
    	    if (isset($request->name)) $system_message->name=$request->name;
    	    if (isset($request->status)) $system_message->status=$request->status;
          if (isset($request->topology_id)) $system_message->topology_id=(int)$request->topology_id;
    	    if (isset($request->price_type)) $system_message->price_type=$request->price_type;
    	    if (isset($request->city_id)) $system_message->city_id=(int)$request->city_id;
    	    if (isset($request->city_name)) $system_message->city_name=$request->city_name;
    	    if (isset($request->punkt_vydachi) && $request->punkt_vydachi=="on") $system_message->punkt_vydachi=1;
		  if (isset($request->fullfilment) && $request->fullfilment=="on") $system_message->fullfilment=1;
		  else $system_message->fullfilment=0;
    	    if (isset($request->default_markup)) {
        		if ((int)$system_message->default_markup!=(int)$request->default_markup){
        		    $sql="update system_message_details set default_markup=?i where system_message_id=?i";
        		    $res=$db->query($sql,$request->default_markup,$system_message->id);
        		    $system_message->default_markup=(int)$request->default_markup;
        		    // poka ne znau
        		}
      		  //$db->query("update system_message_details set default_markup=?i where system_message_id=?i",$request->default_markup,$system_message->id);
      		  //$system_message->default_markup=$request->default_markup;
	       }
      	    if ($system_message->name=="") return self::_error_arr("Укажите наименование склада");
      	    $err=$system_message->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->system_message_id) && (int)$request->system_message_id>0){
                          		$status="ok"; $msg="Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok"; $msg="Новый склад добавлен";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_system_messages($request) {
	    $db = DB::getInstance();
	    $sql="SELECT *
            FROM system_messages
            WHERE user_id=?i order by create_date desc";
	    $res=$db->getAll($sql,$_SESSION['user_id']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['system_messages']=$res;
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_delivery_system_messages($request) {
	    $db = DB::getInstance();
	    $sql="select id,descr,address from system_messages where company_id=?i and deleted=0 and punkt_vydachi=1";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['system_messages']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_system_message($request) {
	    $db = DB::getInstance();
	    $sql="select * from system_messages where id=?i and company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) and deleted=0";
	    if (isset($request->system_message_id) && (int)$request->system_message_id>0){
		$system_message_id=(int)$request->system_message_id;
		$system_message=new system_message($system_message_id);
	    }
	    else {
		return self::_error_arr("не указан id склада");
	    }
	    $res=$db->getRow($sql,$system_message_id,(int)$_SESSION['user_id']);
	    if ($res['id']>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['system_message']=$res;
    		$ret['msg']="";
    		$ret['price_types']=$db->getAll("select * from dict_price_type where type=2 or type=4 and main_company=?i",$_SESSION['main_company']);
        $ret['topologys']=$db->getAll("select id,name from system_message_topology where main_company_id=?i",$_SESSION['main_company']);
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_system_message($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->system_message_id)) {$system_message_id=(int)$request->system_message_id;}
	    if (isset($system_message_id) && $system_message_id>0){
    		$res2=$db->query("update system_messages set deleted=1 where id=?i and company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$system_message_id,$_SESSION['user_id']);
    		//echo "delete from system_message where id=".$system_message_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="Склад успешно удален";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить Склад";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

}



?>
