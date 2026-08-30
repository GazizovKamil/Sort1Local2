<?php

namespace Sort1API\Components;

class Basket
{
    private $_basket_arr=array();

    private function create_new_basket(){
		$db= DB::getInstance();
		$res=$db->getAll("describe basket");
		foreach($res as $key=>$val){
			if ($val['Field']=="create_date") $this->_basket_arr[$val['Field']]=date("Y-m-d H:i:s");
			else {
			if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_basket_arr[$val['Field']]=0;
			else $this->_basket_arr[$val['Field']]="";
			}
			if ($val['Field']=="user_id" && !empty($_SESSION['user_id'])) $this->_basket_arr[$val['Field']]=$_SESSION['user_id'];
			if ($val['Field']=="company_id") $this->_basket_arr[$val['Field']]=$_SESSION['company_id'];
			if ($val['Field']=="main_company_id") $this->_basket_arr[$val['Field']]=$_SESSION['main_company'];
			if ($val['Field']=="session_id") $this->_basket_arr[$val['Field']]=session_id();
		}
		$this->Save();
    }

    function __construct($basket_id = 0, $create=false){
        if ($basket_id>0)
    	    $this->Load($basket_id);
      	else {
			$db = DB::getInstance();
			if(empty($_SESSION['user_id'])){
				$basket_id=$db->getOne("select id from basket where session_id=?s and main_company_id=?i",session_id(),$_SESSION['main_company']);
			}
			else {  
				$basket_id=$db->getOne("select id from basket where user_id=?i and company_id=?i and main_company_id=?i",$_SESSION['user_id'],$_SESSION['company_id'],$_SESSION['main_company']);
			}
      	    if($basket_id) {
      		    $this->Load($basket_id);
      	    }
      	    else {
      		    if($create) $this->create_new_basket();
				else {
					if(!empty($_SESSION['user_id'])) $this->create_new_basket();
					else $this->_basket_arr['id']=0;
				}
      	    }
      	}
    }

    public function Load($basket_id)
    {
        $db = DB::getInstance();
        if ($basket_id>0) {
            $basket_data=$db->getRow("select * from basket where id=?i",$basket_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$basket_data)>0){
          		foreach($basket_data as $key=>$val){
          		    $this->_basket_arr[$key]=$val;
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_basket_arr[$name])) {
			return $this->_basket_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_basket_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_basket_arr[$name])) {
			$this->_basket_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update basket set ?u where id=?i";
            $db->query($sql,$this->_basket_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    	else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into basket set ?u";
	    //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_basket_arr);
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
