<?php

namespace Sort1API\Components;

class InventUser
{
    private $_invent_user_arr=array();

    private function create_new_invent_user(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe invent_users");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_invent_user_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_inventr_user_arr[$val['Field']]=$val['Default'];
        		    else $this->_invent_user_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_invent_user_arr[$val['Field']]=$val['Default'];
        		    else $this->_invent_user_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_invent_user_arr['create_date']=date("Y-m-d H:i:s");
    }

    function __construct($invent_user_id = 0,$user_id = 0,$invent_id = 0){
        if ($invent_id>0)
    	    $this->Load($invent_user_id);
      	else
      	  $this->create_new_invent_user();
    }

    public function Load($invent_user_id)
    {
        $db = DB::getInstance();
        if ($invent_user_id>0) {
            $inventu_data=$db->getRow("select * from invent_users where id=?i",$invent_user_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$inventu_data)>0){
          		foreach($inventu_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_invent_user_arr[$key]=$val;
          		    else $this->_invent_user_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_invent_user_arr[$name])) {
			return $this->_invent_user_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_invent_user_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_invent_user_arr[$name])) {
			$this->_invent_user_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update invent_users set ?u where id=?i";
            $db->query($sql,$this->_invent_user_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into invent_users set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_invent_user_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return 1;
      	    }
      	    else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
