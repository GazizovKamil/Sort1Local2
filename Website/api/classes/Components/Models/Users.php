<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\User;
use Sort1API\Components\Models\Citys;
use Sort1API\Components\Company;
use Sort1API\Components\Notify;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Users extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_user_data($request) {
            $db = DB::getInstance();
            if (isset($request->user_id) && (int)$request->user_id>0){
                $user_id=(int)$request->user_id;
                //echo "user_id=$user_id\n";
		        $user=new User($user_id);
	        }
            else{
                if((int)$_SESSION['roles']==10 && (int)$_SESSION['roles']==20){
                    $user=new User($_SESSION['user_id']);
                }
                else {
                    $user=new User();
                }
            }
            if (!empty($request->lastname)) $user->lastname=$request->lastname;
            if (!empty($request->name)) $user->name=$request->name;
            if(!empty($request->inn) && (strlen($request->inn)>12 || strlen($request->inn)<10)){
                return self::_error_arr("Неправильно указан ИНН");
            }
            if (!empty($request->inn)) 
                $user->inn=$request->inn;
            else $user->inn='';
            //echo "request->inn=".$request->inn." ".print_r($user,true);
            if (!empty($request->email)){
                $user->username=$request->email;
                $user->email=$request->email;
                $sql="select id from users where username=?s and roles <10";
                $is_exist=$db->getOne($sql,$request->email);
                if ((int)$is_exist>0 && (int)$is_exist!=(int)$request->user_id && $user->roles<10 && (int)$request->user_id>0){
                    return self::_error_arr("Пользователь с таким именем пользователя уже существует в системе, измените e-mail пользователя");
                }
            }
            else return self::_error_arr("Не указан e-mail пользователя (email является именем пользователя для входа в систему), на него будут отправлены данные для авторизации");
            if (!empty($request->middlename)) $user->middlename=$request->middlename;
            if (!empty($request->search_in_all_sklad) && $request->search_in_all_sklad=="on") $user->search_in_all_sklad=1;
            else $user->search_in_all_sklad=0;
            if (!empty($request->roles) && $user_id!=$_SESSION['user_id']) {
                $user->roles=self::check_roles($request->roles);
            }
            if (!empty($request->phone)) $user->phone=str_replace(array('+',' ','-','(',')'),"",$request->phone);
            if (!empty($request->mphone)) $user->mphone=str_replace(array('+',' ','-','(',')'),"",$request->mphone);
            if (!empty($request->companys[0])) $user->company_id=(int)$request->companys[0];
	        else $user->company_id=(int)$_SESSION['company_id'];
            $user->main_company_id=(int)$_SESSION['main_company'];
            $user->password=self::generatePassword();
            $user->mphone_confirmed=1;
            $user->email_confirmed=1;
            //if (isset($request->lastname)) $save_arr['lastname']=$_POST['lastname'];
            $err=$user->Save();
            if((int)$request->user_id==0 && (int)$user->roles<10){
                //добавим профиль по умолчанию в мой профиль
                $profiles=$db->getAll("SELECT id FROM user_api_config_profiles WHERE main_company_id=?i",$_SESSION['main_company']);
                $db->query("INSERT ignore INTO company_online_profiles VALUES (?i,3,?i,?i)",$_SESSION['main_company'],$profiles[0]['id'],$user->id);
            }
           //echo "request=".print_r($request->middlename,true)."\nuser=".print_r($user->middlename,true);
            if ($user->id>0 && (int)$user->id!=(int)$_SESSION['user_id']){
                $db->query("delete from user_companys where main_company_id=0 and user_id=?i",$user->id);
                if (count((array)$request->companys)>0){
                    $sql="select company_id from user_companys where main_company_id=0 and user_id=?i and deleted=0";
                    $allowed_companys=$db->getCol($sql,(int)$_SESSION['user_id']);
                    foreach($request->companys as $compkey=>$compval){
                        if (in_array($compval,$allowed_companys)){
                            $sql="insert ignore into user_companys set user_id=?i,main_company_id=?i,company_id=?i";
                            if($user->roles<10) $db->query($sql,(int)$user->id,0,$compval);
			                else $db->query($sql,(int)$user->id,$user->main_company_id,$compval);
                        }
                    }
                }
                else {
                    $sql="insert ignore into user_companys set user_id=".(int)$user->id.",main_company_id=?i,company_id=?i";
                    $db->query($sql,(int)$_SESSION['main_company'],(int)$_SESSION['company_id']);
                }

            }
            if ($err){ $status="err"; $msg="error: ".$err."\n"; }
            else {
                if (isset($request->new_user) && (int)$request->new_user==1){
                    $send_text="Данные для входа на сайт sort1.pro:  имя пользователя: ".$user->email.", пароль:".$user->password;
                    Notify::mail("Данные по регистрации на сайте sort1.pro",$send_text,$user->email);
                    $status="ok"; $msg="Пользователь успешно добавлен, данные для авторизации отправлены на указанную почту";
                }
                else {
                    $status="ok"; $msg="Данные успешно изменены";
                }
            }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }

	public static function get_company_users($request){
	    $db = DB::getInstance();
	    $user_ids=$db->getCol("select user_id from user_companys where company_id=?i and deleted=0",$request->company_id);
	    $sql="select id,username,roles,create_date,company_id,name,middlename,lastname,email,phone,mphone,inn from users where id in (?a) and roles>=10";
	    $comp_users=$db->getAll($sql,$user_ids);
	    if(count((array)$comp_users)>0){
            $ret['status']="ok";
            $ret['msg']="";
            $ret['users']=$comp_users;
	    }
	    else {
            $ret['status']="ok";
            $ret['msg']="";
            $ret['users']=array();
	    }
	    return $ret;
    }
    
    public static function get_my_company_users($request){
	    $db = DB::getInstance();
	    $user_ids=$db->getInd("user_id","select user_id,deleted from user_companys where company_id=?i",$_SESSION['main_company']);
	    $sql="select id,username,roles,create_date,company_id,name,middlename,lastname,email,phone,mphone,inn from users where id in (?a)";
	    $comp_users=$db->getAll($sql,array_column($user_ids,"user_id"));
	    if(count((array)$comp_users)>0){
            foreach($comp_users as $i=>$val){
                $comp_users['deleted']=$user_ids[$val['id']];
            }
		$ret['status']="ok";
		$ret['msg']="";
		$ret['users']=$comp_users;
	    }
	    else {
		$ret['status']="ok";
		$ret['msg']="";
		$ret['users']=array();
	    }
	    return $ret;
	}

    public static function get_site_users($request){
	    $db = DB::getInstance();
        $parsed="";
        if(isset($request->show_deleted) && $request->show_deleted=="on"){
            $parsed="";
        }
        else $parsed=$db->parse(" and deleted=0");
	    $user_ids=$db->getInd("user_id","select user_id,deleted from user_companys where main_company_id=?i ?p",$_SESSION['main_company'],$parsed);
	    $sql="select id,username,roles,create_date,company_id,name,middlename,lastname,email,phone,mphone,inn from users where id in (?a) and roles=10 order by name";
	    $comp_users=$db->getAll($sql,array_column($user_ids,"user_id"));
	    if(count((array)$comp_users)>0){
            foreach($comp_users as $i=>$val){
                $comp_users[$i]['deleted']=$user_ids[$val['id']]['deleted'];
            }
            $ret['status']="ok";
            $ret['msg']="";
            $ret['users']=$comp_users;
	    }
	    else {
		$ret['status']="ok";
		$ret['msg']="";
		$ret['users']=array();
	    }
	    return $ret;
	}

    public static function get_my_company_users_basket($request){
	    $db = DB::getInstance();
	    $user_ids=$db->getCol("select user_id from user_companys where company_id=?i and user_id!=?i",$_SESSION['main_company'],$_SESSION['user_id']);
	    $sql="select b.id as basket_id,u.id,u.username,u.roles,u.create_date,u.company_id,u.name,u.middlename,u.lastname,u.email,u.phone,u.mphone,u.inn from users u left join basket b on (b.user_id = u.id) where u.id in (?a)";
	    $comp_users=$db->getAll($sql,$user_ids);
	    if(count((array)$comp_users)>0){
		$ret['status']="ok";
		$ret['msg']="";
		$ret['users']=$comp_users;
	    }
	    else {
		$ret['status']="ok";
		$ret['msg']="";
		$ret['users']=array();
	    }
	    return $ret;
	}

    public static function get_user_data($request){
	    $db = DB::getInstance();
	    //$user_ids=$db->getCol("select user_id from user_companys where company_id=?i",$request->company_id);
	    $sql="select u.id,u.username,u.roles,r.name_rus as role_name,u.create_date,u.create_date,u.company_id,u.name,u.middlename,u.lastname,u.email,u.phone,u.mphone,u.inn, c.city  from users u
        left join city c on (c.id = u.city_id)
        left join roles r on (r.id = u.roles)
        where u.id=?i";
	    $comp_users=$db->getAll($sql,(int)$_SESSION['user_id']);
	    if(count($comp_users)>0){
            $ret['status']="ok";
            $ret['msg']="";
            $ret['user']=$comp_users;
	    }
	    else {
            $ret['status']="ok";
            $ret['msg']="";
            $ret['users']=array();
	    }
	    return $ret;
	}

	public static function get_roles($request){
	    $db = DB::getInstance();
	    $sql="select id,name,name_rus from roles where id>=?i";
	    $res=$db->getAll($sql,$_SESSION['roles']);
	    $ret=array();
	    if($res){
		$ret['status']="ok";
		$ret['roles']=$res;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['roles']=array();
		$ret['msg']="";
	    }
	    return $ret;
    }
    
    public static function intersectRecursive($array1, $array2)
    {
        $properties = [];

        foreach ($array1 as $key => $value) {
            //if (isset($array2[$key])) {
                // key the same - check value
                $value1 = $value;
                if (isset($array2[$key])) $value2 = $array2[$key];
                else $value2 = $value;

                if (is_array($value1)) {
                    $intersectValue = self::intersectRecursive($array1[$key], $array2[$key]);

                    if ($intersectValue) {
                        $properties[$key] = $intersectValue;
                        //echo $key."=\n";
                    }
                } else {
                    $properties[$key] = $value2;
                    //echo $key."\n";
                }
            //}
        }
        return $properties;
    }

    public static function get_role($request){
        $db = DB::getInstance();
        if(empty($request->role_id)) return array("status"=>"err","err"=>"Не указана роль");
        if($_SESSION['roles']!=1) return array("status"=>"err","err"=>"Нет прав для редактирования ролей");
	    $sql="select id,name,name_rus,modules_rights from roles_of_company where id=?i and main_company_id=?i";
        $res1=$db->getRow($sql,$request->role_id,$_SESSION['main_company']);
        $res2=$db->getRow($sql,$request->role_id,0);
        if(!$res1 || empty($res1['modules_rights'])) {
            $res=$res2;
        }
        else $res=$res1;
        $res1['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res1['modules_rights']),true);
        $res2['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res2['modules_rights']),true);
        if(empty($res1['modules_rights'])) $res1['modules_rights']=array();
        if(empty($res2['modules_rights'])) $res2['modules_rights']=array();
        $ret=array();
	    if($res){
            //$res['modules_rights_orig']=preg_replace("/[\t\n]/","",$res['modules_rights']);
            
            //$res['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res['modules_rights']),true);
            //$res['modules_rights']=array_replace_recursive($res2['modules_rights'],$res1['modules_rights']);
            
            $ret['intersect']=self::intersectRecursive($res2['modules_rights'],$res1['modules_rights']);
            if(count((array)$ret['intersect'])>0) $res['modules_rights']=$ret['intersect'];
            //if(empty($ret['merged'])) $ret['merged']=array_merge_recursive($res1['modules_rights'],$res2['modules_rights']);
            //$ret['r1mr']=$res1['modules_rights'];
            //$ret['r2mr']=$res2['modules_rights'];
            $ret['status']="ok";
            $ret['roles']=$res;
            $ret['msg']="";
	    }
	    else {
            $ret['status']="ok";
            $ret['roles']=array();
            $ret['msg']="";
	    }
	    return $ret;
    }

    public static function get_my_role(){
        $db = DB::getInstance();
        //if(empty($request->role_id)) return array("status"=>"err","err"=>"Не указана роль");
        //if($_SESSION['roles']!=1) return array("status"=>"err","err"=>"Нет прав для редактирования ролей");
	    $sql="select id,name,name_rus,modules_rights from roles_of_company where id=?i and main_company_id=?i";
        $res1=$db->getRow($sql,$_SESSION['roles'],$_SESSION['main_company']);
        $res2=$db->getRow($sql,$_SESSION['roles'],0);
        if(!$res1 || empty($res1['modules_rights'])) {
            
            $res=$res2;
            
        }
        else $res=$res1;
        $res1['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res1['modules_rights']),true);
        $res2['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res2['modules_rights']),true);
        if(empty($res1['modules_rights'])) $res1['modules_rights']=array();
        if(empty($res2['modules_rights'])) $res2['modules_rights']=array();
        $ret=array();
	    if($res){
            //$res['modules_rights_orig']=preg_replace("/[\t\n]/","",$res['modules_rights']);
            
            //$res['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res['modules_rights']),true);
            //$res['modules_rights']=array_replace_recursive($res2['modules_rights'],$res1['modules_rights']);
            
            $ret['intersect']=self::intersectRecursive($res2['modules_rights'],$res1['modules_rights']);
            if(count((array)$ret['intersect'])>0) $res['modules_rights']=$ret['intersect'];
            //if(empty($ret['merged'])) $ret['merged']=array_merge_recursive($res1['modules_rights'],$res2['modules_rights']);
            //$ret['r1mr']=$res1['modules_rights'];
            //$ret['r2mr']=$res2['modules_rights'];
            $ret['status']="ok";
            $ret['roles']=$res;
            $ret['msg']="";
	    }
	    else {
            $ret['status']="ok";
            $ret['roles']=array();
            $ret['msg']="";
	    }
	    return $ret;
    }
    
    public static function save_role($request){
        if(empty($request->role_id) || (int)$request->role_id<1 || (int)$request->role_id>10) return array("status"=>"err","err"=>"Не указана роль");
        if($_SESSION['roles']!=1) return array("status"=>"err","err"=>"Нет прав для редактирования ролей");
        $db = DB::getInstance();
        $sql="select id,name,name_rus,modules_rights from roles_of_company where id=?i and main_company_id=?i";
        $res1=$db->getRow($sql,$request->role_id,$_SESSION['main_company']);
        $res2=$db->getRow($sql,$request->role_id,0);
        if(!$res1 || empty($res1['modules_rights'])) {
            $res=$res2; 
        }
        else $res=$res1;
        $res1['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res1['modules_rights']),true);
        $res2['modules_rights']=json_decode(preg_replace("/[\t\n]/","",$res2['modules_rights']),true);
        if(empty($res1['modules_rights'])) $res1['modules_rights']=array();
        if(empty($res2['modules_rights'])) $res2['modules_rights']=array();
        $modules_rights=json_decode($res['modules_rights'],true);
        $ret['intersect']=self::intersectRecursive($res2['modules_rights'],$res1['modules_rights']);
        if(count((array)$ret['intersect'])>0) $modules_rights=$ret['intersect'];
        foreach($modules_rights['modules'] as $module_key=>$module_val){
            //echo print_r($request->{$module_val['name']},true);
            if(isset($request->{$module_val['name']}['show']) && $request->{$module_val['name']}['show']=="on") $modules_rights['modules'][$module_key]['show']=1;
            else $modules_rights['modules'][$module_key]['show']=0;
            foreach($module_val['rights'] as $mkey=>$mval){
                //echo print_r($request->{$module_val['name']})."\n";
                //echo "isset(".$request->{$module_val['name']}[$mkey].") && ".$request->{$module_val['name']}[$mkey]['show']."==on\n";
                if(isset($request->{$module_val['name']}[$mkey]) && $request->{$module_val['name']}[$mkey]['show']=="on") $modules_rights['modules'][$module_key]['rights'][$mkey]['show']=1;
                else $modules_rights['modules'][$module_key]['rights'][$mkey]['show']=0;
                if(isset($request->{$module_val['name']}[$mkey]) && $request->{$module_val['name']}[$mkey]['read']=="on") $modules_rights['modules'][$module_key]['rights'][$mkey]['read']=1;
                else $modules_rights['modules'][$module_key]['rights'][$mkey]['read']=0;
                if(isset($request->{$module_val['name']}[$mkey]) && $request->{$module_val['name']}[$mkey]['write']=="on") $modules_rights['modules'][$module_key]['rights'][$mkey]['write']=1;
                else $modules_rights['modules'][$module_key]['rights'][$mkey]['write']=0;
                if(isset($request->{$module_val['name']}[$mkey]) && $request->{$module_val['name']}[$mkey]['delete']=="on") $modules_rights['modules'][$module_key]['rights'][$mkey]['delete']=1;
                else $modules_rights['modules'][$module_key]['rights'][$mkey]['delete']=0;
            }
        }
        $ret=array();
        //$res['modules_rights']=json_encode($modules_rights);
        $res['modules_rights']=$modules_rights;
        if(count((array)$res1)>1){
            $sql1="update roles_of_company set modules_rights=?s where id=?i and main_company_id=?i";
            $res3=$db->query($sql1,json_encode($modules_rights),$request->role_id,$_SESSION['main_company']);
        }
        else {
            $sql1="insert into roles_of_company values(?i,?s,?s,?s,?i)";
            $res3=$db->query($sql1,$res['id'],$res['name'],$res['name_rus'],json_encode($modules_rights),$_SESSION['main_company']);
        }
	    if($res3 && !empty(json_encode($modules_rights))){
            $ret['status']="ok";
            $ret['roles']=$res;
            $ret['msg']="";
            $ret['r1mr']=$res1['modules_rights'];
            $ret['r2mr']=$res2['modules_rights'];
	    }
	    else {
            $ret['status']="err";
            $ret['roles']=array();
            $ret['err']="Не удается сохранить роль";
	    }
	    return $ret;
	}

  public static function change_user_password($request){
    if(isset($request->password)) $password=$request->password;
    if(isset($request->company_id)) $company_id=$request->company_id;
    if(isset($request->user_id)) $user_id=$request->user_id;
    if(!isset($password) || !isset($company_id) || !isset($user_id) || empty($password)){
      return self::_error_arr("Недостаточно данных");
    }
    $db = DB::getInstance();
    // Проверим на соответствие компании клиентам
    $sql="select company_id from user_companys where main_company_id=?i and company_id=?i and user_id=?i";
    if($res=$db->getOne($sql,$_SESSION['main_company'],$company_id,$user_id)){
      if($res!=$company_id){
        return self::_error_arr("Невозможно выполнить операцию: попытка изменения пароля не вашего клиента");
      }
      $sql1="update users set password=?s where id=?i";
      $res=$db->query($sql1,$password,$user_id);
      if ($db->affectedRows()>0) {
        return array("status"=>"ok","err"=>"","msg"=>"");
      }
      else {
        return array("status"=>"err","err"=>"Не удалось изменить пароль пользователя","msg"=>"");
      }
    }
    else {
      return self::_error_arr("Ошибка выполнения запроса");
    }
  }

  public static function change_my_password($request){
    if(!empty($request->old_password)) $old_password=$request->old_password;
    else return array("status"=>"err","err"=>"Не указан текущий пароль");
    if(!empty($request->new_password)) $new_password=$request->new_password;
    else return array("status"=>"err","err"=>"Не указан новый пароль");
    if($request->new_password!=$request->new_password_conf){
        return array("status"=>"err","err"=>"Не совпадает подтверждение пароля");
    }
    $db = DB::getInstance();
    // Проверим на соответствие компании клиентам
    $sql="select password from users where id=?i";
    if($res=$db->getOne($sql,$_SESSION['user_id'])){
      if($res!=$old_password){
        return self::_error_arr("Невозможно выполнить операцию: попытка изменения пароля не удалась, неправильный текущий пароль");
      }
      $sql1="update users set password=?s where id=?i";
      $res=$db->query($sql1,$new_password,$_SESSION['user_id']);
      if ($db->affectedRows()>0) {
        return array("status"=>"ok","err"=>"","msg"=>"");
      }
      else {
        return array("status"=>"err","err"=>"Не удалось изменить пароль пользователя","msg"=>"");
      }
    }
    else {
      return self::_error_arr("Ошибка выполнения запроса");
    }
  }

  public static function change_site_user_password($request){
    if(isset($request->password)) $password=$request->password;
    if(isset($request->new_password)) $company_id=$request->company_id;
    if(isset($request->user_id)) $user_id=$request->user_id;
    if(!isset($password) || !isset($company_id) || !isset($user_id) || empty($password)){
      return self::_error_arr("Недостаточно данных");
    }
    $db = DB::getInstance();
    // Проверим на соответствие компании клиентам
    $sql="select company_id from user_companys where main_company_id=?i and company_id=?i and user_id=?i";
    if($res=$db->getOne($sql,$_SESSION['main_company'],$company_id,$user_id)){
      if($res!=$company_id){
        return self::_error_arr("Невозможно выполнить операцию: попытка изменения пароля не вашего клиента");
      }
      $sql1="update users set password=?s where id=?i";
      $res=$db->query($sql1,$password,$user_id);
      if ($db->affectedRows()>0) {
        return array("status"=>"ok","err"=>"","msg"=>"");
      }
      else {
        return array("status"=>"err","err"=>"Не удалось изменить пароль пользователя","msg"=>"");
      }
    }
    else {
      return self::_error_arr("Ошибка выполнения запроса");
    }
  }

    public static function register_user($request){
        $db=DB::getInstance();
        $ret=array(
        "SERVER" => $_SERVER 
        );
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        //echo $origin[1];
        if($origin[1]=="sort1.pro" || $origin[1]=="sort1.ru" || $request->site=="sort1.ru" || $origin[1]=="nur.sort1.pro" || $origin[1]=="192.168.39.148:91") return self::register_pro_user($request);
        if(preg_match("/[^@]+@[^@]+/",$request->email)){
            $sql="select company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
            $shop = $db->getRow($sql,str_replace("www.","",$origin[1]));
            $main_company_id = $shop['company_id'];
            $site_name = $origin[1];
            $site_logo = $shop['logo'];
            if((int)$main_company_id>0){
                if (!isset($request->mphone) || !preg_match('/^\+\d\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $request->mphone)) {
                    return array("status" => "err", "err" => "Некорректный телефон", "message" => "Введите телефон в формате +X(XXX)XXX-XX-XX");
                }
                
                if (!isset($request->name) || empty(trim($request->name))) {
                    return array("status" => "err", "err" => "Имя не должно быть пустым", "message" => "Имя не должно быть пустым");
                }

                if (!isset($request->captcha) || empty(trim($request->captcha))) {
                    return array("status" => "err", "err" => "Капча не должно быть пустым", "message" => "Капча не должно быть пустым");
                }

                $captcha = trim($request->captcha);

                if((int)$captcha !== (int)$_SESSION['captcha']){
                    return array("status" => "err", "err" => "Капча не прошла проверку", "message" => "Капча не прошла проверку");
                }

                $pass = self::generatePassword();
                $company = new Company();
                $company->type = 3;
                $company->name = trim($request->name);

                if (!empty($request->lastname)) {
                    $company->name .= " " . trim($request->lastname);
                }

                $company->mphone = $request->mphone;
                $company->email = $request->email;
                $company->btype = 1;
                $company->save();

                $user = new User();
                $user->main_company_id = $main_company_id;
                $user->company_id = $company->id;
                $user->password = $pass;
                $user->username = $request->email;
                $user->email = $request->email;
                $user->mphone_confirmation_code = random_int(10000000, 99999999);
                $user->name = trim($request->name);

                if (!empty($request->lastname)) {
                    $user->lastname = trim($request->lastname);
                }

                $user->mphone = $request->mphone;
                $user_err=$user->save();

                if($user_err) return array("status"=>"err","err"=>$user_err);
                if((int)$company->id>0 && $user->id>0){
                    $sql="insert into user_companys (user_id,main_company_id,company_id,btype) values (?i,?i,?i,?i)";
                    $res=$db->query($sql,$user->id,$main_company_id,$company->id,1);
                    if($res){
                        $send_text = '
                            <head>
                            <title>Rating Reminder</title>
                            <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
                            <meta content="width=device-width" name="viewport">
                            <style type="text/css">
                                @font-face {
                                    font-family: \'Postmates Std\';
                                    font-weight: 600;
                                    font-style: normal;
                                    src: local(\'Postmates Std Bold\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-bold.woff) format(\'woff\');
                                }
            
                                @font-face {
                                    font-family: \'Postmates Std\';
                                    font-weight: 500;
                                    font-style: normal;
                                    src: local(\'Postmates Std Medium\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-medium.woff) format(\'woff\');
                                }
            
                                @font-face {
                                    font-family: \'Postmates Std\';
                                    font-weight: 400;
                                    font-style: normal;
                                    src: local(\'Postmates Std Regular\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-regular.woff) format(\'woff\');
                                }
                            </style>
                            <style media="screen and (max-width: 680px)">
                                @media screen and (max-width: 680px) {
                                    .page-center {
                                        padding-left: 0 !important;
                                        padding-right: 0 !important;
                                    }
            
                                    .footer-center {
                                        padding-left: 20px !important;
                                        padding-right: 20px !important;
                                    }
                                }
                            </style>
                            <style>
                                .a {
                                    color: #fff;
                                }
                            </style>
                        </head>
                        <body style="background-color: #f4f4f5;">
                            <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background-color: #f4f4f5; text-align: center;">
                                <tbody>
                                    <tr>
                                        <td style="text-align: center;">
                                            <table align="center" cellpadding="0" cellspacing="0" id="body" style="background-color: #fff; width: 100%; max-width: 680px; height: 100%;">
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <table align="center" cellpadding="0" cellspacing="0" class="page-center" style="text-align: left; padding-bottom: 88px; width: 100%; padding-left: 120px; padding-right: 120px;">
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="padding-top: 24px; text-align: center;">
                                                                            <img src="'.$site_logo.'" style="width: 200px;">
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="2" style="padding-top: 72px; text-align: center; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #000000; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 32px; font-smoothing: always; font-style: normal; font-weight: 600; letter-spacing: -2.6px; line-height: 52px; mso-line-height-rule: exactly; text-decoration: none;">
                                                                            Данные для входа на '.$site_name.'
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="padding-top: 48px;">
                                                                            <table cellpadding="0" cellspacing="0" style="width: 100%">
                                                                                <tbody>
                                                                                    <tr>
                                                                                        <td style="width: 100%; height: 1px; max-height: 1px; background-color: #d9dbe0; opacity: 0.81">
                                                                                        </td>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarel\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                            Логин: <b><a style="color: #9095a2; text-decoration: none;">'.$request->email.'</a></b>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                            Пароль: <b>'.$pass.'</b>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </body>
                        </html>
                        ';
                        Notify::mail("Данные по регистрации на сайте ".$origin[1],$send_text,$request->email);
                        return array("status"=>"ok","email"=>$request->email,"message"=>"Данные отправлены на ваш email");
                    }
                    else
                        return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя, ошибка записи в базу данных","message"=>"Не удалось зарегистрировать пользователя");
                }
                else 
                    return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя, не удалось создать пользователя или организацию","message"=>"Не удалось зарегистрировать пользователя");
            }
            else {
                return array("status"=>"err","err"=>"Сайт не найден","ref"=>$origin[1],"message"=>"Сайт не найден");
            }
        }
        else 
            return array("status"=>"err","err"=>"Неправильно указан email","message"=>"Неправильно указан email");
        //return $ret;
    }

    public static function register_user_market($request){
        $db=DB::getInstance();
        $log_file = '/var/log/sort1/avito.log';
        file_put_contents($log_file, "Request URL: " . print_r($request, true));

        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        //echo $origin[1];
        if(preg_match("/[^@]+@[^@]+/",$request->email)){
            $pass=self::generatePassword();
            if(!empty($request->name) && !empty($request->lastname) && !empty($request->mphone)) {
                if (!isset($request->name) || empty(trim($request->name))) {
                    return array("status" => "err", "err" => "Имя не должно быть пустым", "message" => "Имя не должно быть пустым");
                }

                if (!isset($request->lastname) || empty(trim($request->lastname))) {
                    return array("status" => "err", "err" => "Фамилия не должно быть пустым", "message" => "Имя не должно быть пустым");
                }
                
                if (!isset($request->mphone) || !preg_match('/^\+\d\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $request->mphone)) {
                    return array("status" => "err", "err" => "Некорректный телефон", "message" => "Введите телефон в формате +X(XXX)XXX-XX-XX");
                }

                $captcha = trim($request->captcha);

                if((int)$captcha !== (int)$_SESSION['captcha']){
                    return array("status" => "err", "err" => "Капча не прошла проверку", "message" => "Капча не прошла проверку");
                }

                $name=$request->name.' '.$request->lastname;
                $sql="select * from company where name=?s and mphone=?s and inn=0 and kpp=0";
                $exist_company = $db->getRow($sql, $name, $request->mphone);
                $create_new_company = true;

                if ($exist_company) {
                    $company = new Company($exist_company['id']);

                    if ($company->email == "") {
                        $company->email = $request->email;
                    }
                    if ($company->email != $request->email) {
                        $create_new_company = true;
                    }
                    // print_r($company);
                }

                if ($create_new_company) {
                    $company = new Company();
                    $company->name = trim($request->name);

                    if (!empty($request->lastname)) {
                        $company->name .= " " . trim($request->lastname);
                    }
                    $company->type = 3;
                    $company->btype = 1;
                    $company->email = $request->email;
                    $company->mphone = $request->mphone;

                    if (!empty($request->email)) {
                    }
                }
                // print_r($company);
                $err=$company->save();
                if ($err != 1){
                    return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя","message"=>"Не удалось зарегистрировать пользователя");
                }
            }
            else return array("status"=>"err","err"=>"Имя и фамилия не должно быть пустым","message"=>"Имя и фамилия не должно быть пустым");
            //$_SESSION['main_company']=$main_company_id;
            $user=new User();
            $user->main_company_id=35;
            $user->company_id=$company->id;
            $user->password=$pass;
            $user->username=$request->email;
            $user->email=$request->email;
            $user->roles=20;
            $user->mphone_confirmation_code=random_int(10000000,99999999);//md5($mphone.date("Y-m-d H:i:s"));
            $user->name = trim($request->name);

            if (!empty($request->lastname)) {
                $user->lastname = trim($request->lastname);
            }

            $user->mphone = $request->mphone;
            $user->save();
            if((int)$company->id>0 && $user->id>0){
                $sql="insert into user_companys (user_id,main_company_id,company_id,btype) values (?i,?i,?i,?i)";
                $res=$db->query($sql,$user->id,$user->main_company_id,$company->id,1);
                if($res){
                    $send_text = '
                    <head>
                    <title>Rating Reminder</title>
                    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
                    <meta content="width=device-width" name="viewport">
                    <style type="text/css">
                        @font-face {
                            font-family: \'Postmates Std\';
                            font-weight: 600;
                            font-style: normal;
                            src: local(\'Postmates Std Bold\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-bold.woff) format(\'woff\');
                        }

                        @font-face {
                            font-family: \'Postmates Std\';
                            font-weight: 500;
                            font-style: normal;
                            src: local(\'Postmates Std Medium\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-medium.woff) format(\'woff\');
                        }

                        @font-face {
                            font-family: \'Postmates Std\';
                            font-weight: 400;
                            font-style: normal;
                            src: local(\'Postmates Std Regular\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-regular.woff) format(\'woff\');
                        }
                    </style>
                    <style media="screen and (max-width: 680px)">
                        @media screen and (max-width: 680px) {
                            .page-center {
                                padding-left: 0 !important;
                                padding-right: 0 !important;
                            }

                            .footer-center {
                                padding-left: 20px !important;
                                padding-right: 20px !important;
                            }
                        }
                    </style>
                </head>
                <body style="background-color: #f4f4f5;">
                    <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background-color: #f4f4f5; text-align: center;">
                        <tbody>
                            <tr>
                                <td style="text-align: center;">
                                    <table align="center" cellpadding="0" cellspacing="0" id="body" style="background-color: #fff; width: 100%; max-width: 680px; height: 100%;">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <table align="center" cellpadding="0" cellspacing="0" class="page-center" style="text-align: left; padding-bottom: 88px; width: 100%; padding-left: 120px; padding-right: 120px;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="padding-top: 24px; text-align: center;">
                                                                    <img src="https://sun9-65.userapi.com/impg/SF9ojAuG-An6KPY1NMcGg2ddk6ah63sX6v74tQ/uaYRBKnhZyE.jpg?size=1000x634&quality=96&sign=ef8cf2ebb7ffeef1a4f7d3a334672df5&type=album" style="width: 100px;">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" style="padding-top: 72px; text-align: center; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #000000; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 32px; font-smoothing: always; font-style: normal; font-weight: 600; letter-spacing: -2.6px; line-height: 52px; mso-line-height-rule: exactly;">
                                                                    Данные для входа на Jetparts.ru
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-top: 48px;">
                                                                    <table cellpadding="0" cellspacing="0" style="width: 100%">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td style="width: 100%; height: 1px; max-height: 1px; background-color: #d9dbe0; opacity: 0.81">
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                    Логин: <b><a style="color: #9095a2; text-decoration: none;">$request->email</a></b>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                    Пароль: <b>$pass</b>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </body>
                </html>
                ';
                    Notify::mail("Данные по регистрации на ".$origin[1],$send_text,$request->email);
                    return array("status"=>"ok","email"=>$request->email,"message"=>"Данные отправлены на ваш email");
                }
                else
                    return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя","message"=>"Не удалось зарегистрировать пользователя");
            }
            else {
                return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя","message"=>"Не удалось зарегистрировать пользователя");
            }
        }
        else {
            return array("status"=>"err","err"=>"неправильно указан email","message"=>"неправильно указан email");
        }
    }


    public static function register_pro_user($request){
        $db=DB::getInstance();
        $ret=array(
        "SERVER" => $_SERVER
        );
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        if(!empty($request->name)) $name=htmlentities($request->name,ENT_QUOTES);
        if(!empty($request->middlename)) $middlename=htmlentities($request->middlename,ENT_QUOTES);
        if(!empty($request->lastname)) $lastname=htmlentities($request->lastname,ENT_QUOTES);
        if(!empty($request->email)) $email=htmlentities($request->email,ENT_QUOTES);
        if(!empty($request->mphone)) $mphone=str_replace(array('+',' ','-','(',')'),"",htmlentities($request->mphone,ENT_QUOTES));
        if(!empty($request->inn)) $inn=$request->inn;
        if(isset($inn) && strlen((string)(int)$inn)>=9){
            $company_data=Companys::get_company_data_from_api((object)array("inn"=>$inn));
            $company_from_base=$db->getOne("select id from company where inn=?i limit 1",(int)$inn);
            if($company_from_base){
                $company=new Company($company_from_base);
            }
            else {
                $company=new Company();
                $company->inn=$inn;
                $company->kpp=$company_data['suggestions'][0]['data']['kpp'];
                $company->ogrn=$company_data['suggestions'][0]['data']['ogrn'];
                if(isset($company_data['suggestions'][0]['data']['address']['value']))
                    $company->address=$company_data['suggestions'][0]['data']['address']['value'];
                else  
                    $company->address=$company_data['suggestions'][0]['data']['address']['data']['source'];
                $company->name=$company_data['suggestions'][0]['value'];
                $company->ruk=$company_data['suggestions'][0]['data']['management']['name'];
                $company->rukdol=$company_data['suggestions'][0]['data']['management']['post'];
                $company->btype=3;
                $company->email=$email;
                //$company_name=$company->name;
                file_put_contents("/var/log/sort1/register_user.log",date("Y-m-d H:i:s")."\n request: ".print_r($request,true)."\ncompany_name: ".$company_name."\n empty: ".empty($company_name)."\n
                    preg_match: ".preg_match("/active/i",$company_data['suggestions'][0]['data']['state']['status'])."\n
                    company status: ".$company_data['suggestions'][0]['data']['state']['status']."\n company_data:".print_r($company_data,true)."\n",FILE_APPEND);
                if(!empty($company->name) && preg_match("/active/i",$company_data['suggestions'][0]['data']['state']['status'])){
                    $company_saved=$company->Save();
                    file_put_contents("/var/log/sort1/register_user.log","company:".print_r($company,true)."\ncompany_saved: $company_saved\n company_id:".$company->id."\n",FILE_APPEND);
                }
                else {
                    //Проверим по базе ФНС
                    //$from_fns_api=file_get_contents("https://api-fns.ru/api/egr?req=$inn&key=faf186dc071db4e64a586f7a5c33aa3c65c18336");
                    if(isset($request->lang) && $request->lang=="en")
                        return array("status"=>"err","err"=>"Wrong ITN or legal entity liquidated");
                    else
                        return array("status"=>"err","err"=>"Неверный ИНН или организация ликвидирована","message"=>"Неверный ИНН или организация ликвидирована");
                }
            }
            $is_registered_company=$db->getAll("select user_id from user_companys where main_company_id=0 and company_id=?i and deleted=0",$company->id);
            //file_put_contents("/var/log/sort1/register_user.log","is_registered_company: ".print_r($is_registered_company,true)."\n count:".count($is_registered_company)."\n",FILE_APPEND);
            if(count((array)$is_registered_company)>0){
                if(isset($request->lang) && $request->lang=="en")
                    return array("status"=>"err","err"=>"Legal entity with the specified ITN already tied to the user. Check your e-mail or contact the admin info@sort1.ru");
                else
                    return array("status"=>"err","err"=>"Организация с указанным ИНН уже привязана к пользователю. Проверьте ваш и-мейл или обратитесь к администрации info@sort1.ru.","message"=>"Организация с указанным ИНН уже привязана к пользователю. Проверьте ваш и-мейл или обратитесь к администрации info@sort1.ru.");
            }
            //echo print_r($company_data)."\n";
            //return array("status"=>"ok","company_data"=>$company_data);
        }
        else {
            if(isset($request->lang) && $request->lang=="en")
                return array("status"=>"err","err"=>"Wrong ITN");
            else
                return array("status"=>"err","err"=>"Указан неправильный ИНН","message"=>"Указан неправильный ИНН");
        }
        if(preg_match("/[^@]+@[^@]+/",$email)){
            $user_data=$db->getRow("select id,email_confirmation_code,email_confirmed from users where email=?s",$email);
            if($user_data){
                if(isset($user_data['email_confirmed']) && $user_data['email_confirmed']==0){
                    //email не подтвержден

                }
                else {
                    return array("status"=>"err","err"=>"Невозможно зарегистрировать пользователя","message"=>"Невозможно зарегистрировать пользователя");
                }
            }
            else {
                //пользователь с таким емаил еще не заведен
                $user=new User();
                $user->name=$name;
                $user->middlename=$middlename;
                $user->lastname=$lastname;
                $user->email=$email;
                $user->mphone=$mphone;
                $user->email_confirmation_code=md5($email.date("Y-m-d H:i:s"));
                $user->mphone_confirmation_code=random_int(10000000,99999999);//md5($mphone.date("Y-m-d H:i:s"));
                $user->company_id=$company->id;
                $user->main_company_id=$company->id;
                $user->username=$email;
                $user->roles=1;
                $pass=self::generatePassword();
                $user->password=$pass;
                $user->Save();
                $is_profile=$db->getAll("select id from user_api_config_profiles where main_company_id=?i",$user->main_company_id);
                if(count((array)$is_profile)<1){
                    $db->query("insert into user_api_config_profiles (name,main_company_id) values (?s,?i)","По умолчанию",$user->main_company_id);
                    $user_config_profile=$db->insertId();
                    $db->query("insert into company_online_profiles (company_id,profile_type,profile_id,user_id) values (?i,?i,?i,?i)",$user->main_company_id,3,$user_config_profile,$user->id);
                }
            }
            if((int)$company->id>0){
                if($user->id>0){
                    $sql="insert into user_companys (user_id,main_company_id,company_id,btype) values (?i,?i,?i,?i)";
                    $res=$db->query($sql,$user->id,0,$company->id,3);
                    if($res){
                        $db->query("insert ignore into user_companys (user_id,main_company_id,company_id,btype) values (?i,?i,?i,?i)",$user->id,$company->id,471,3);
                        $send_text="Ваш E-mail был указан при регистрации на сайте sort1.pro.<br>
                        Для подтверждения адреса и продолжения регистрации перейдите пожалуйста 
                        <a href=\"http://sort1.pro/confirm_email.php?hash=".$user->email_confirmation_code."&email=".$email."\">http://sort1.pro/confirm_email.php?hash=".$user->email_confirmation_code."&email=".$email."</a><br>";
                        //Данные для входа на сайт ".$origin[1]." login:".$user->username." password:".$pass;
                        Notify::mail("Данные для регистрации на сайте sort1.pro",$send_text,$request->email);
                        return array("status"=>"ok","email"=>$request->email,"msg"=>"","message"=>"Для продолжения регистрации проверьте свою почту");
                    }
                    else {
                        if(isset($request->lang) && $request->lang=="en")
                            return array("status"=>"err","err"=>"User registration failed");
                        else
                            return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя","message"=>"Не удалось зарегистрировать пользователя");
                    }
                }
                if(isset($request->lang) && $request->lang=="en")
                    return array("status"=>"err","err"=>"User registration failed");
                else
                    return array("status"=>"err","err"=>"Не удалось зарегистрировать пользователя","message"=>"Не удалось зарегистрировать пользователя");
            }
            else {
                if(isset($request->lang) && $request->lang=="en")
                    return array("status"=>"err","err"=>"Wrong ITN, User registration failed");
                else
                    return array("status"=>"err","err"=>"Неправильно указан ИНН, Не удалось зарегистрировать пользователя","message"=>"Неправильно указан ИНН, Не удалось зарегистрировать пользователя");
            }
        }
        else {
            if(isset($request->lang) && $request->lang=="en")
                return array("status"=>"err","err"=>"Wrong e-mail.");
            else
                return array("status"=>"err","err"=>"Неверный формат email.","message"=>"Неверный формат email.");
        }
        //return $ret;
    }

    private static function generatePassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%^&';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

    private static function generateApiKey($length = 18) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%^&';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }


    public static function enable_api($request){
        $db=DB::getInstance();
        if(isset($request->user_id) && (int)$request->user_id>0){
            $can=$db->getRow("select id,api_key from users where id=?i and main_company_id=?i",(int)$request->user_id,$_SESSION['main_company']);
            if($can && $can['api_key']==""){
                $api_key=self::generateApiKey();
                if($db->query("update users set api_key=?s where id=?i",$api_key,(int)$request->user_id))
                    return array("status"=>"ok","err"=>"","msg"=>"","api_key"=>$api_key);
                else 
                    return array("status"=>"err","err"=>"Не удалось записать ключ API");
            }
            else return array("status"=>"err","err"=>"Ключ уже определен");
        }
        else {
            return array("status"=>"err","err"=>"Неправильные параметры");
        }
    }

    public static function disable_api($request){
        $db=DB::getInstance();
        if(isset($request->user_id) && (int)$request->user_id>0){
            $can=$db->getRow("select id,api_key from users where id=?i and main_company_id=?i",(int)$request->user_id,$_SESSION['main_company']);
            if($can && $can['api_key']!=""){
                if($db->query("update users set api_key=?s where id=?i","",(int)$request->user_id))
                    return array("status"=>"ok","err"=>"","msg"=>"");
                else 
                    return array("status"=>"err","err"=>"Не удалось удалить ключ API");
            }
            else return array("status"=>"err","err"=>"API уже выключен");
        }
        else {
            return array("status"=>"err","err"=>"Неправильные параметры");
        }
    }

    public static function register_callback($request){
        $db=DB::getInstance();
        $ret=array(
        "SERVER" => $_SERVER
        );
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        //if($origin[1]=="sort1.pro") return self::register_pro_user($request);
        if(empty($request->user_name)) return array("status"=>"err","err"=>"Пустое имя");
        if(empty($request->user_mphone)) return array("status"=>"err","err"=>"Пустой номер телефона");
    
        $sql="insert into pro_show_orders (user_name,user_mphone,user_email,from_site,comment,sended) values (?s,?s,?s,?s,?s,?i)";
        $res=$db->query($sql,$request->user_name,$request->user_mphone,$request->user_email,$origin[1],$request->comment,0);
        $send_text="Поступила заявка на демонстрацию от ".$request->user_name." \n тел:".$request->user_mphone."\n email: ".$request->user_email."\n Комментарий пользователя: ".$request->comment;
        Notify::mail("Новая заявка на демонстрацию sort1.pro",$send_text,"info@sort1.ru");
        return array("status"=>"ok","phone"=>$request->user_mphone,"email"=>$request->user_email,"msg"=>"");
        //return $ret;
    }

    public static function get_user_pref($request){
        $db=DB::getInstance();
        if(empty($request->type)) return array("status"=>"err","err"=>"не указан тип");
        switch ($request->type){
            case "group_by":
                $user_pref=$db->getOne("select data from user_preferences where user_id=?i and type=?s",$_SESSION['user_id'],$request->type);
                if($user_pref) return array("status"=>"ok","msg"=>"","user_pref"=>json_decode($user_pref));
                else return array("status"=>"ok","msg"=>"","user_pref"=>array(0=>array('sklad_orig','sklad_analog','orig','analog')));
                break;
            case "search_opts":
                $user_pref=$db->getOne("select data from user_preferences where user_id=?i and type=?s",$_SESSION['user_id'],$request->type);
                if($user_pref) return array("status"=>"ok","msg"=>"","search_opts"=>json_decode($user_pref));
                else return array("status"=>"ok","msg"=>"","search_opts"=>array());
                break;
            case "search_fields":
                $user_pref=$db->getOne("select data from user_preferences where user_id=?i and type=?s",$_SESSION['user_id'],$request->type);
                if($user_pref) return array("status"=>"ok","msg"=>"","search_fields"=>json_decode($user_pref));
                else return array("status"=>"ok","msg"=>"","search_fields"=>array());
                break;
            case "zakazfilter":
                $user_pref=$db->getOne("select data from user_preferences where user_id=?i and type=?s",$_SESSION['user_id'],$request->type);
                if($user_pref) return array("status"=>"ok","msg"=>"","zakazfilter"=>json_decode($user_pref),"is"=>1);
                else return array("status"=>"ok","msg"=>"","zakazfilter"=>"");
                break;
            case "makezakaz":
                $user_pref=$db->getOne("select data from user_preferences where user_id=?i and type=?s",$_SESSION['user_id'],$request->type);
                if($user_pref) return array("status"=>"ok","msg"=>"","makezakaz"=>json_decode($user_pref),"is"=>1);
                else return array("status"=>"ok","msg"=>"","makezakaz"=>"");
                break;
        }
    }

    public static function save_user_pref($request){
        $db=DB::getInstance();
        if(empty($request->type)) return array("status"=>"err","err"=>"не указан тип");
        $user_pref=$db->getRow("select id,data from user_preferences where user_id=?i and type=?s",$_SESSION['user_id'],$request->type);
        if($user_pref['id']>0) {
            $db->query("update user_preferences set data=?s where user_id=?i and type=?s",$request->data,$_SESSION['user_id'],$request->type);
            return array("status"=>"ok","msg"=>"");
        }
        else {
            $db->query("insert into user_preferences (type,data,user_id,main_company_id) values (?s,?s,?i,?i)",$request->type,$request->data,$_SESSION['user_id'],$_SESSION['main_company']);
            return array("status"=>"ok","msg"=>"");
        }
    }

    public static function send_mphone_confirm_code($request){
        $db=DB::getInstance();
        if(empty($request->login)) return array("status"=>"err","err"=>"Не указано имя пользователя");
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        $sql="select company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
        $site_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
        $main_company_id=$site_data['company_id'];
        if($site_data['shop_verify_phone']=="1"){
            $user_data=$db->getRow("select mphone_confirmation_code,mphone from users where username=?s",$request->login);
            $msg="Проверочный код для входа на сайт:".$user_data['mphone_confirmation_code'];
            $send=json_decode(file_get_contents("https://sms.ru/sms/send?api_id=".$site_data['shop_sms_apikey']."&to=".urlencode($user_data['mphone'])."&msg=".urlencode($msg)."&json=1"));
            //if()
            return array("status"=>"ok","msg"=>"СМС отправлен на номер телефона указанный при регистрации","sms_resp"=>$send);
        }
        else return array("status"=>"err","err"=>"Сайт не производит проверки телефоного номера");

    }

    public static function fire_user($request){
        $db=DB::getInstance();
        if(empty($request->user_id)) return array("status"=>"err","err"=>"не указан пользователь");
        $fired=$db->query("update users set fired=1 where id=?i",$request->user_id);
        if($fired) {
            return array("status"=>"ok","msg"=>"");
        }
        else {
            return array("status"=>"err","err"=>"Не удалось совершить данную операцию попробуйте позже");
        }
    }

    public static function set_city_user($request){
        $db=DB::getInstance();
        if(isset($request->city_id)){
            $sql="select * from city where id=?i";
            $our_citys=$db->getRow($sql,(int)$request->city_id);
            $city_id = $our_citys['id'];
        }
        else if(isset($request->city)){
            $res = Citys::get_city((object)array("city_name"=>$request->city));
            $city_id = $res['citys'][0]['id'];
        }
        if($city_id){
            $_SESSION['city_id'] = $city_id;
            if(isset($_SESSION['user_id'])){
                $user=$db->query("update users set city_id=?i where id=?i and roles = 20",$_SESSION['city_id'],$_SESSION['user_id']);
                if ($db->affectedRows()>0) {
                    return array("status"=>"ok","msg"=>"");
                }
                else return array("status"=>"err","msg"=>"Не удалось изменить город");
            }
            else{
                return array("status"=>"ok","msg"=>"");
            }
        }
        else{
            return array("status"=>"err","msg"=>"Не удалось найти город");
        }
    }

    public static function get_current_city($request){
        $db=DB::getInstance();
        if(!empty($_SESSION['city_id'])){
            $sql="select * from city where id=?i";
            $our_citys=$db->getRow($sql,(int)$_SESSION['city_id']);
            $city_id = $our_citys['city'];
            return array("status"=>"ok","msg"=>"","city"=>$city_id);
        } 
        return array("status"=>"ok","msg"=>"","city"=>"");
    }

    public static function check_included_jetparts($request){
        $db=DB::getInstance();
        if(!empty($_SESSION['user_id'])){
            $sql="select use_jetparts from users where id=?i";
            $res =$db->getOne($sql,(int)$_SESSION['user_id']);
            if($res){
                return array("status"=>"ok","msg"=>"");
            }
            else{
                return array("status"=>"err","err"=>"Нет доступа на подключение к jetparts");
            }
        } 
        return array("status"=>"err","err"=>"Auth need");
    }

    public static function save_user_data_market($request) {
        $db = DB::getInstance();
        if((int)$_SESSION['roles']==20){
            $user=new User($_SESSION['user_id']);
            $company = new Company($_SESSION['company_id']);
        }
        else {
            return self::_error_arr("Авторизуйтесь");
        }
        if (!empty($request->lastname)) $user->lastname=$request->lastname;
        if (!empty($request->name)) $user->name=$request->name;
        if (!empty($request->middlename)) $user->middlename=$request->middlename;
        if (!empty($request->mphone)) $user->mphone= $request->mphone;
        $company->name = $user->lastname . ' ' . $user->name . ' ' . $user->middlename;
        $company->Save();
        $user->company_id=(int)$_SESSION['company_id'];
        $user->main_company_id=(int)$_SESSION['main_company'];
        $user->password=self::generatePassword();
        $user->mphone_confirmed=1;
        $user->email_confirmed=1;
        //if (isset($request->lastname)) $save_arr['lastname']=$_POST['lastname'];
        $err=$user->Save();
        if ($err){ $status="err"; $msg="error: ".$err."\n"; }
        else {
            $status="ok"; $msg="Данные успешно изменены";
        }
        if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg);
    }

    public static function get_market_captcha() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $_SESSION['captcha'] = $num1 + $num2;

        return array("status"=>"ok","data"=>"Сколько будет $num1 плюс $num2?");
    }

    public static function recover_password_email_market($request){
        $db=DB::getInstance();
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        //echo $origin[1];
        if(preg_match("/[^@]+@[^@]+/",$request->email)){
            $pass=self::generatePassword();
            $user_id = $db->getOne("select id from users where email=?s and roles=20",$request->email);
            if($user_id){
                $user=new User($user_id);
                $user->__set_password($pass);
                $user->Save();
                //$send_text="Данные для входа на сайт ".$origin[1]." login:".$request->email." password:".$pass;
                $send_text = '
                <head>
                <title>Rating Reminder</title>
                <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
                <meta content="width=device-width" name="viewport">
                <style type="text/css">
                    @font-face {
                        font-family: \'Postmates Std\';
                        font-weight: 600;
                        font-style: normal;
                        src: local(\'Postmates Std Bold\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-bold.woff) format(\'woff\');
                    }

                    @font-face {
                        font-family: \'Postmates Std\';
                        font-weight: 500;
                        font-style: normal;
                        src: local(\'Postmates Std Medium\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-medium.woff) format(\'woff\');
                    }

                    @font-face {
                        font-family: \'Postmates Std\';
                        font-weight: 400;
                        font-style: normal;
                        src: local(\'Postmates Std Regular\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-regular.woff) format(\'woff\');
                    }
                </style>
                <style media="screen and (max-width: 680px)">
                    @media screen and (max-width: 680px) {
                        .page-center {
                            padding-left: 0 !important;
                            padding-right: 0 !important;
                        }

                        .footer-center {
                            padding-left: 20px !important;
                            padding-right: 20px !important;
                        }
                    }
                </style>
                <style>
                    .a {
                        color: #fff;
                    }
                </style>
            </head>
            <body style="background-color: #f4f4f5;">
                <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background-color: #f4f4f5; text-align: center;">
                    <tbody>
                        <tr>
                            <td style="text-align: center;">
                                <table align="center" cellpadding="0" cellspacing="0" id="body" style="background-color: #fff; width: 100%; max-width: 680px; height: 100%;">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <table align="center" cellpadding="0" cellspacing="0" class="page-center" style="text-align: left; padding-bottom: 88px; width: 100%; padding-left: 120px; padding-right: 120px;">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding-top: 24px; text-align: center;">
                                                                <img src="https://sun9-65.userapi.com/impg/SF9ojAuG-An6KPY1NMcGg2ddk6ah63sX6v74tQ/uaYRBKnhZyE.jpg?size=1000x634&quality=96&sign=ef8cf2ebb7ffeef1a4f7d3a334672df5&type=album" style="width: 100px;">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" style="padding-top: 72px; text-align: center; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #000000; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 32px; font-smoothing: always; font-style: normal; font-weight: 600; letter-spacing: -2.6px; line-height: 52px; mso-line-height-rule: exactly; text-decoration: none;">
                                                                Данные для входа на Jetparts.ru
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 48px;">
                                                                <table cellpadding="0" cellspacing="0" style="width: 100%">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td style="width: 100%; height: 1px; max-height: 1px; background-color: #d9dbe0; opacity: 0.81">
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                Логин: <b><a style="color: #9095a2; text-decoration: none;">'.$request->email.'</a></b>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                Пароль: <b>'.$pass.'</b>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </body>
            </html>
            ';
                Notify::mail("Данные по регистрации на ".$origin[1],$send_text,$request->email);
                return array("status"=>"ok","email"=>$request->email,"message"=>"Данные отправлены на ваш email");
            }
            else {
                return array("status"=>"err","err"=>"Не удалось восстановить пароль","message"=>"Не удалось восстановить пароль");
            }
        }
        else {
            return array("status"=>"err","err"=>"неправильно указан email","message"=>"неправильно указан email");
        }
    }

    public static function recover_password_email_shop($request){
        $db=DB::getInstance();
        preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
        $sql="select shop_logo as logo,company_id from company_sites where site_name=?s";
        $shop = $db->getRow($sql,str_replace("www.","",$origin[1]));
        $main_company_id = $shop['company_id'];
        $site_name = $origin[1];
        $site_logo = $shop['logo'];
        //echo $origin[1];
        if(preg_match("/[^@]+@[^@]+/",$request->email)){
            $pass=self::generatePassword();
            $user_id = $db->getOne("select id from users where email=?s and main_company_id=?i and roles = 10",$request->email,$main_company_id);
            if(!empty($user_id)){
                $user=new User($user_id);
                $user->__set_password($pass);
                $user->Save();
                if($user){
                    //$send_text="Данные для входа на сайт ".$origin[1]." login:".$request->email." password:".$pass;
                    $send_text = '
                    <head>
                    <title>Rating Reminder</title>
                    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
                    <meta content="width=device-width" name="viewport">
                    <style type="text/css">
                        @font-face {
                            font-family: \'Postmates Std\';
                            font-weight: 600;
                            font-style: normal;
                            src: local(\'Postmates Std Bold\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-bold.woff) format(\'woff\');
                        }
    
                        @font-face {
                            font-family: \'Postmates Std\';
                            font-weight: 500;
                            font-style: normal;
                            src: local(\'Postmates Std Medium\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-medium.woff) format(\'woff\');
                        }
    
                        @font-face {
                            font-family: \'Postmates Std\';
                            font-weight: 400;
                            font-style: normal;
                            src: local(\'Postmates Std Regular\'), url(https://s3-us-west-1.amazonaws.com/buyer-static.postmates.com/assets/email/postmates-std-regular.woff) format(\'woff\');
                        }
                    </style>
                    <style media="screen and (max-width: 680px)">
                        @media screen and (max-width: 680px) {
                            .page-center {
                                padding-left: 0 !important;
                                padding-right: 0 !important;
                            }
    
                            .footer-center {
                                padding-left: 20px !important;
                                padding-right: 20px !important;
                            }
                        }
                    </style>
                    <style>
                        .a {
                            color: #fff;
                        }
                    </style>
                </head>
                <body style="background-color: #f4f4f5;">
                    <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background-color: #f4f4f5; text-align: center;">
                        <tbody>
                            <tr>
                                <td style="text-align: center;">
                                    <table align="center" cellpadding="0" cellspacing="0" id="body" style="background-color: #fff; width: 100%; max-width: 680px; height: 100%;">
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <table align="center" cellpadding="0" cellspacing="0" class="page-center" style="text-align: left; padding-bottom: 88px; width: 100%; padding-left: 120px; padding-right: 120px;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="padding-top: 24px; text-align: center;">
                                                                    <img src="'.$site_logo.'" style="width: 200px;">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2" style="padding-top: 72px; text-align: center; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #000000; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 32px; font-smoothing: always; font-style: normal; font-weight: 600; letter-spacing: -2.6px; line-height: 52px; mso-line-height-rule: exactly; text-decoration: none;">
                                                                    Данные для входа на '.$site_name.'
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-top: 48px;">
                                                                    <table cellpadding="0" cellspacing="0" style="width: 100%">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td style="width: 100%; height: 1px; max-height: 1px; background-color: #d9dbe0; opacity: 0.81">
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarel\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                    Логин: <b><a style="color: #9095a2; text-decoration: none;">'.$request->email.'</a></b>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td style="padding-top: 24px; -ms-text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-font-smoothing: antialiased; -webkit-text-size-adjust: 100%; color: #9095a2; font-family: \'Postmates Std\', \'Helvetica\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', \'Roboto\', \'Oxygen\', \'Ubuntu\', \'Cantarell\', \'Fira Sans\', \'Droid Sans\', \'Helvetica Neue\', sans-serif; font-size: 16px; font-smoothing: always; font-style: normal; font-weight: 400; letter-spacing: -0.18px; line-height: 24px; mso-line-height-rule: exactly; text-decoration: none; vertical-align: top; width: 100%;">
                                                                    Пароль: <b>'.$pass.'</b>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </body>
                </html>
                ';
                    Notify::mail("Данные по регистрации на ".$origin[1],$send_text,$request->email);
                    return array("status"=>"ok","email"=>$request->email,"message"=>"Данные отправлены на ваш email");
                }
                else {
                    return array("status"=>"err","err"=>"Не удалось восстановить пароль","message"=>"Не удалось восстановить пароль");
                }
            }
            else {
                return array("status"=>"err","err"=>"Не удалось восстановить пароль","message"=>"Не удалось восстановить пароль");
            }
        }
        else {
            return array("status"=>"err","err"=>"неправильно указан email","message"=>"неправильно указан email");
        }
    }

}
?>
