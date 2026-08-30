<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\EmailConfig;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class EmailConfigs extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_email_config($request) {
            $db = DB::getInstance();
			if (isset($request->email_config_id)) $email_config_id=(int)$request->email_config_id;
			if (isset($email_config_id) && $email_config_id>0) {
				$email_config=new EmailConfig($email_config_id);
				if($email_config->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Не ваша эта работа");
			}
			else {
				$email_config=new EmailConfig();
				$email_config->main_company_id=$_SESSION['main_company'];
			}
			if (isset($request->name)) {
				$email_config->name=$request->name;
			}
			if (isset($request->email_provider_id)){
				$email_config->email_provider_id=$request->email_provider_id;
                switch((int)$request->email_provider_id){
                    case 1: $email_config->email_provider_text="Яндекс почта"; break;
                    case 2: $email_config->email_provider_text="mail.ru"; break;
                    case 3: $email_config->email_provider_text="gmail.com"; break;
                }
			}
			if (isset($request->email_provider_text)) $email_config->email_provider_text=$request->email_provider_text;
			if (isset($request->email_config_login)) $email_config->login=$request->email_config_login;
            if (isset($request->email_config_password)) $email_config->password=$request->email_config_password;
            if (isset($request->email_config_tested)) $email_config->tested=$request->email_config_tested;
			if (isset($request->name)) $email_config->name=$request->name;
			if (isset($request->email_config_price_folder)) $email_config->price_folder=$request->email_config_price_folder;
			if (isset($request->deleted)) $email_config->deleted=$request->deleted;
			//$email_config->service_id=$_SESSION['my_service_id'];
			$err=$email_config->save();
			switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->email_config_id) && (int)$request->email_config_id>0){
							$status="ok"; $msg="";
						}
						else {
							$status="ok"; $msg="";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"email_config_id"=>(int)$email_config->id);
        }

	public static function get_email_config($request) {
	    $db = DB::getInstance();
	    if (isset($request->email_config_id) && (int)$request->email_config_id>0) $email_config_id=(int)$request->email_config_id;
	    else return self::_error_arr("Не указан id");
	    $sql="select * from email_configs where id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getRow($sql,$email_config_id,$_SESSION['main_company']);
	    if ($res['id']>0){
            $ret['status']="ok";
            $ret['err']="";
            $ret['email_config']=$res;
        }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_email_configs($request) {
	    $db = DB::getInstance();
		$sql="select * from email_configs
			where main_company_id=?i and deleted=0";
		$parsed="";
		$res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['email_configs']=$res;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['email_configs']=array();
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_email_config($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->email_config_id)) {$email_config_id=(int)$request->email_config_id;}
	    if (isset($email_config_id) && $email_config_id>0){
			$res2=$db->query("update email_configs set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$email_config_id,$_SESSION['user_id']);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$ret['status']="ok";
				$ret['msg']="";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить Работу";
			}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

    public static function test_email_config($request) {
        $db = DB::getInstance();
        if (isset($request->email_config_id)) $email_config_id=(int)$request->email_config_id;
        if (isset($email_config_id) && $email_config_id>0) {
            $email_config=new EmailConfig($email_config_id);
            if($email_config->main_company_id!=$_SESSION['main_company']) return self::_error_arr("Не ваша эта работа");
        }
        else {
            $email_config=new EmailConfig();
            $email_config->main_company_id=$_SESSION['main_company'];
        }
        if (isset($request->name)) {
            $email_config->name=$request->name;
        }
        if (isset($request->email_provider_id)){
            $email_config->email_provider_id=$request->email_provider_id;
            switch((int)$request->email_provider_id){
                case 1: $email_config->email_provider_text="Яндекс почта"; break;
                case 2: $email_config->email_provider_text="mail.ru"; break;
                case 3: $email_config->email_provider_text="gmail.com"; break;
            }
        }
        if (isset($request->email_provider_text)) $email_config->email_provider_text=$request->email_provider_text;
        if (isset($request->email_config_login)) $email_config->login=$request->email_config_login;
        if (isset($request->email_config_password)) $email_config->password=$request->email_config_password;
        if (isset($request->name)) $email_config->name=$request->name;
        if (isset($request->email_config_price_folder)) $email_config->price_folder=$request->email_config_price_folder;
        if (isset($request->deleted)) $email_config->deleted=$request->deleted;
        //$email_config->service_id=$_SESSION['my_service_id'];
        //$err=$email_config->test();
        if(!empty($email_config->login) && !empty($email_config->password) && $email_config->email_provider_id>0){
            switch((int)$email_config->email_provider_id){
                case 1: $ml = imap_open("{imap.yandex.ru:993/imap/ssl}", $email_config->login, $email_config->password);
                    if($ml){
                        $mlist=imap_list($ml,"{imap.yandex.ru:993/imap/ssl}","*");
                        foreach($mlist as $mlkey=>$ml){
                            $mlist[$mlkey]=mb_convert_encoding($mlist[$mlkey], "utf-8", "UTF7-IMAP");
                        }
                        return array("status"=>"ok","mlist"=>$mlist);
                    }
                    else return array("status"=>"err","err"=>"Не удалось соединиться, проверьте имя пользователя и пароль","login"=>$email_config->login, "password"=>$email_config->password,"ml"=>$ml);
                    break;
                case 2: $ml = imap_open("{imap.mail.ru:993/imap/ssl}", $email_config->login, $email_config->password);
                    if($ml){
                        $mlist=imap_list($ml,"{imap.mail.ru:993/imap/ssl}","*");
                        foreach($mlist as $mlkey=>$ml){
                            $mlist[$mlkey]=mb_convert_encoding($mlist[$mlkey], "utf-8", "UTF7-IMAP");
                        }
                        return array("status"=>"ok","mlist"=>$mlist);
                    }
                    else return array("status"=>"err","err"=>"Не удалось соединиться, проверьте имя пользователя и пароль","login"=>$email_config->login, "password"=>$email_config->password,"ml"=>$ml);
                    break;
                case 3: $ml = imap_open("{imap.gmail.com:993/imap/ssl}", $email_config->login, $email_config->password);
                    if($ml){
                        $mlist=imap_list($ml,"{imap.gmail.com:993/imap/ssl}","*");
                        foreach($mlist as $mlkey=>$ml){
                            $mlist[$mlkey]=mb_convert_encoding($mlist[$mlkey], "utf-8", "UTF7-IMAP");
                        }
                        return array("status"=>"ok","mlist"=>$mlist);
                    }
                    else return array("status"=>"err","err"=>"Не удалось соединиться, проверьте имя пользователя и пароль","login"=>$email_config->login, "password"=>$email_config->password,"ml"=>$ml);
                    break;
            }
        }else{
            return array("status"=>"err","err"=>"Неправильно заданы параметры, проверьте обязательные параметры: Расположение, Имя пользователя и Пароль","login"=>$email_config->login,"pass"=>$email_config->password,"prov_id"=>$email_config->email_provider_id);
        }
        switch($err) {
        case 10: $status="err"; $msg="Данные не изменились\n"; break;
        case 1: if (isset($request->email_config_id) && (int)$request->email_config_id>0){
                        $status="ok"; $msg="";
                    }
                    else {
                        $status="ok"; $msg="";
                    }
            break;
        default: $status="err"; $msg="error: ".$err."\n";
        }
        if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg,"email_config_id"=>(int)$email_config->id);
    }

}



?>