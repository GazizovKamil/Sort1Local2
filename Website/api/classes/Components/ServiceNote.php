<?php

namespace Sort1API\Components;

class ServiceNote
{
    private $_service_note_arr=array();
	private $_service_note_arr_old=array();

    private function create_new_service_note(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe service_notes");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_service_note_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_service_note_arr[$val['Field']]=$val['Default'];
        		    else $this->_service_note_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_service_note_arr[$val['Field']]=$val['Default'];
        		    else $this->_service_note_arr[$val['Field']]="";
        		}
    	    }
    	}
		$this->service_id=(int)$_SESSION['my_service_id'];
		$this->user_id=(int)$_SESSION['user_id'];
    }

    function __construct($service_note_id = 0){
        if ($service_note_id>0)
    	    $this->Load($service_note_id);
      	else
      	  $this->create_new_service_note();
    }

    public function Load($service_note_id)
    {
        $db = DB::getInstance();
        if ($service_note_id>0) {
            $service_note_data=$db->getRow("select * from service_notes where id=?i",$service_note_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$service_note_data)>0){
          		foreach($service_note_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_service_note_arr[$key]=$val;
          		    else $this->_service_note_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_service_note_arr[$name])) {
			return $this->_service_note_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_service_note_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_service_note_arr[$name])) {
			$this->_service_note_arr_old[$name]=$this->_service_note_arr[$name];
			$this->_service_note_arr[$name]=$val;
		}
	}

	private function do_status_action(){
		$db = DB::getInstance();
		switch($this->_service_note_arr['status']){
			case 2: break;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$this->update_date=date("Y-m-d H:i:s");
			$this->update_by=$_SESSION['user_id'];
			$sql="update service_notes set ?u where id=?i";
			$db->query($sql,$this->_service_note_arr,$this->id);
			//echo "db->query($sql,".print_r($this->_service_note_arr,true).",".$this->id.");";
			if ($db->affectedRows()>0) { return 1;}
			else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
			$this->update_date="0000-00-00 00:00:00";
            //$save_data['create_date']=$this->create_date;
            $sql="insert ignore into service_notes set ?u";
	    //echo $sql." ".print_r($this->_service_note_arr,true);
            $db->query($sql,$this->_service_note_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
				if((int)$this->zakaz_id>0){
					if($this->_service_note_arr_old['status']!=$this->_service_note_arr['status']){
						do_status_action();
					}
				}
          		return 1;
      	    }
      	    else return "Не удается добавить запись в сервис";
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
