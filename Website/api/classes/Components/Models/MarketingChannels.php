<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\MarketingChannel;
//require 'vendor/autoload.php';

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class MarketingChannels extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_marketing_channel($request) {
          $db = DB::getInstance();
    	    if (isset($request->marketing_channel_id)) $marketing_channel_id=(int)$request->marketing_channel_id;
    	    if (isset($marketing_channel_id) && $marketing_channel_id>0) {
    		      $marketing_channel=new MarketingChannel($marketing_channel_id);
    	    }
    	    else
    		    $marketing_channel=new MarketingChannel(); 
    	    if (isset($request->name)) $marketing_channel->name=$request->name;
      	    if ($marketing_channel->name=="") return self::_error_arr("Укажите наименование канала продаж");
      	    $err=$marketing_channel->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->marketing_channel_id) && (int)$request->marketing_channel_id>0){
                          		$status="ok"; $msg="";
                      		}
                      		else {
                          	    $status="ok"; $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg, "marketing_channel_id"=>$marketing_channel->id);
        }


	public static function get_marketing_channels($request) {
	    $db = DB::getInstance();
	    $sql="select * from marketing_channels where main_company_id=?i and deleted=0";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['marketing_channels']=$res;
    		$ret['msg']="";
		}
		else {
			$ret['status']="ok";
    		$ret['err']="";
    		$ret['marketing_channels']=array();
    		$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_marketing_channel($request) {
	    $db = DB::getInstance();
	    if (isset($request->marketing_channel_id) && (int)$request->marketing_channel_id>0){
			$marketing_channel_id=(int)$request->marketing_channel_id;
			$marketing_channel=new MarketingChannel($marketing_channel_id);
	    }
	    else {
			return self::_error_arr("не указан id сервиса");
	    }
		$sql="select * from marketing_channels where id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getRow($sql,$marketing_channel_id,(int)$_SESSION['main_company']);
	    if ($res['id']>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['marketing_channel']=$res;
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_marketing_channel($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->marketing_channel_id)) {$marketing_channel_id=(int)$request->marketing_channel_id;}
	    if (isset($marketing_channel_id) && $marketing_channel_id>0){
    		$res2=$db->query("update marketing_channels set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$marketing_channel_id,$_SESSION['user_id']);
    		//echo "delete from marketing_channel where id=".$marketing_channel_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить Сервис";
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
