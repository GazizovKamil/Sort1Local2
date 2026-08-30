<?php

namespace Sort1API\Components;

class Bug
{
    private $_bug_arr=array();

    private function create_new_bug(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe bugs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_bug_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_bug_arr[$val['Field']]=$val['Default'];
        		    else $this->_bug_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_bug_arr[$val['Field']]=$val['Default'];
        		    else $this->_bug_arr[$val['Field']]="";
        		}
    	    }
    	}
		$this->_bug_arr['company_id']=$_SESSION['main_company'];
		$this->_bug_arr['user_id']=$_SESSION['user_id'];
		$this->_bug_arr['status']=1;
		$this->_bug_arr['create_date']=date("Y-m-d H:i:s");
    }

    function __construct($bug_id = 0){
        if ($bug_id>0)
    	    $this->Load($bug_id);
      	else
      	  $this->create_new_bug();
    }

    public function Load($bug_id)
    {
        $db = DB::getInstance();
        if ($bug_id>0) {
            $bug_data=$db->getRow("select * from bugs where id=?i",$bug_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$bug_data)>0){
          		foreach($bug_data as $key=>$val){
          		    if(!empty($val) || $val == 0 ) $this->_bug_arr[$key]=$val;
          		    else $this->_bug_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_bug_arr[$name])) {
			return $this->_bug_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_bug_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_bug_arr[$name])) {
			$this->_bug_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$this->_bug_arr['update_date']=date("Y-m-d H:i:s");
            $sql="update bugs set ?u where id=?i";// and user_id=?i";
            $db->query($sql,$this->_bug_arr,$this->id);//,$_SESSION['user_id']);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            //$this->create_date=date("Y-m-d H:i:s");
            //$save_data['create_date']=$this->create_date;
            $sql="insert ignore into bugs set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_bug_arr);
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
