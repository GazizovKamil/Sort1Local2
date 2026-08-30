<?php

namespace Sort1API\Components;

class User
{
    private $_user_arr=array();

    function __construct($user_id = 0){
        if ($user_id>0)
            $this->Load($user_id);
        else {
            $this->create_new_user();
        }
    }

    private function create_new_user(){
        $db= DB::getInstance();
    	$res=$db->getAll("describe users");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_user_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_user_arr[$val['Field']]=$val['Default'];
        		    else $this->_user_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_user_arr[$val['Field']]=$val['Default'];
        		    else $this->_user_arr[$val['Field']]="";
        		}
    	    }
        }   
		$this->_user_arr['roles']=10;
    }

    public function Load($user_id)
    {
        $db = DB::getInstance();
        if ($user_id>0) {
            $user_data=$db->getRow("select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar,main_company_id,my_sklad_id,inn,my_service_id,search_in_all_sklad from users where id=?i",$user_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($user_data){
                $this->_user_arr['username']=$user_data['username'];
                $this->_user_arr['roles']=$user_data['roles'];
                $this->_user_arr['create_date']=$user_data['create_date'];
                $this->_user_arr['company_id']=(int)$user_data['company_id'];
		        $this->_user_arr['main_company_id']=(int)$user_data['main_company_id'];
                $this->_user_arr['name']=$user_data['name'];
                $this->_user_arr['lastname']=$user_data['lastname'];
                $this->_user_arr['middlename']=($user_data['middlename']===null)?"":$user_data['middlename'];
                $this->_user_arr['email']=$user_data['email'];
                $this->_user_arr['phone']=$user_data['phone'];
                $this->_user_arr['mphone']=$user_data['mphone'];
                $this->_user_arr['avatar']=$user_data['avatar'];
                $this->_user_arr['my_sklad_id']=$user_data['my_sklad_id'];
                $this->_user_arr['my_service_id']=$user_data['my_service_id'];
                $this->_user_arr['search_in_all_sklad']=$user_data['search_in_all_sklad'];
                $this->_user_arr['id']=$user_id;
                $this->_user_arr['inn']=$user_data['inn'];
            }
        }
    }

    public function __get($name) {
        if (isset($this->_user_arr[$name])) {
            return $this->_user_arr[$name];
        } else {
            return null;
        }
    }

    public function __isset($name){
        return isset($this->_user_arr[$name]);
    }

    public function __set($name,$val) {
        if (isset($this->_user_arr[$name])) {
            $this->_user_arr[$name]=$val;
        }
    }

    public function __set_password($val) {
        $this->_user_arr['password']=$val;
    }

    public function Save(){
        $db = DB::getInstance();
        
        if ($this->id>0) {
            $sql="update users set ?u where id=?i";
            $db->query($sql,$this->_user_arr,$this->id);
            //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");";
            if ($db->affectedRows()>0) {}
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $sql="insert ignore into users set ?u";
            $db->query($sql,$this->_user_arr);
            if ($db->affectedRows()>0) $this->id=$db->insertId();
            else return "Не удалось завести пользователя, возможно пользователь с таким e-mail уже заведен";
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
