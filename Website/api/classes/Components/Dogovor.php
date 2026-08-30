<?php

namespace Sort1API\Components;

class Dogovor
{
    private $_dogovor_arr=array();

    private function create_new_dogovor(){
	$db= DB::getInstance();
	$res=$db->getAll("describe dogovor");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_dogovor_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
		    if(!empty($val['Default'])) $this->_dogovor_arr[$val['Field']]=$val['Default'];
		    else $this->_dogovor_arr[$val['Field']]=0;
		}
		else {
		    if(!empty($val['Default'])) $this->_dogovor_arr[$val['Field']]=$val['Default'];
		    else $this->_dogovor_arr[$val['Field']]="";
		}
	    }
	}
    }

    function __construct($dogovor_id = 0){
        if ($dogovor_id>0)
    	    $this->Load($dogovor_id);
	else 
	    $this->create_new_dogovor();
    }

    public function Load($dogovor_id)
    {
        $db = DB::getInstance();
        if ($dogovor_id>0) {
            $dogovor_data=$db->getRow("select * from dogovor where id=?i",$dogovor_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$dogovor_data)>0){
		foreach($dogovor_data as $key=>$val){
		    $this->_dogovor_arr[$key]=$val;
		}
            }
	    $this->_dogovor_arr['update_date']=date("Y-m-d H:i:s");
        }
    }

	public function __get($name) {
		if (isset($this->_dogovor_arr[$name])) {
			return $this->_dogovor_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_dogovor_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_dogovor_arr[$name])) {
			$this->_dogovor_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			if(empty($this->num)) $this->num=$this->id;
            $sql="update dogovor set ?u where id=?i";
            $db->query($sql,$this->_dogovor_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_dogovor_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
			$this->update_date='0000-00-00';
            $sql="insert ignore into dogovor set ?u";
	    //echo $sql." ".print_r($this->_document_arr,true);
            $db->query($sql,$this->_dogovor_arr);
            if ($db->affectedRows()>0) {
				$this->id=$db->insertId();
				if(empty($this->num)) {
					$this->num=$this->id;
					$sql="update dogovor set ?u where id=?i";
            		$db->query($sql,$this->_dogovor_arr,$this->id);
				}
				return 1;
			}
			else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
