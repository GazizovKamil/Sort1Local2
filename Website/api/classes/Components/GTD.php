<?php

namespace Sort1API\Components;

class GTD
{
    private $_gtd_arr=array();

    private function create_new_gtd(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe gtd");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_gtd_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_gtd_arr[$val['Field']]=$val['Default'];
        		    else $this->_gtd_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_gtd_arr[$val['Field']]=$val['Default'];
        		    else $this->_gtd_arr[$val['Field']]="";
        		}
    	    }
    	}
     // $this->_gtd_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($gtd_id = 0){
        if ($gtd_id>0)
    	    $this->Load($gtd_id);
      	else
      	  $this->create_new_gtd();
    }

    public function Load($gtd_id)
    {
        $db = DB::getInstance();
        if ($gtd_id>0) {
            $gtd_data=$db->getRow("select * from gtd where id=?i",$gtd_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$gtd_data)>0){
          		foreach($gtd_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_gtd_arr[$key]=$val;
          		    else $this->_gtd_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_gtd_arr[$name])) {
			return $this->_gtd_arr[$name];
		} else {
			return null; 
		}
	}

	public function __isset($name){
	    return isset($this->_gtd_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_gtd_arr[$name])) {
			$this->_gtd_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update gtd set ?u where id=?i";
            $db->query($sql,$this->_gtd_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into gtd set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_gtd_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return 1;
      	    }
      	    else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
	}
	
	public function Delete(){
        $db = DB::getInstance();
        if ($this->id>0) {
            //$sql="delete from gtd where id=?i";
			//$db->query($sql,$this->id);
			$sql="delete from gtd_to_doc_det where gtd_id=?i";
            $db->query($sql,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
		}
	}
}
?>
