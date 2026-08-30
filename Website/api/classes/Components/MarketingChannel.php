<?php

namespace Sort1API\Components;

class MarketingChannel
{
    private $_marketing_channel_arr=array();

    private function create_new_marketing_channel(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe marketing_channels");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_marketing_channel_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_marketing_channel_arr[$val['Field']]=$val['Default'];
        		    else $this->_marketing_channel_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_marketing_channel_arr[$val['Field']]=$val['Default'];
        		    else $this->_marketing_channel_arr[$val['Field']]="";
        		}
    	    }
    	}
		$this->main_company_id=$_SESSION['main_company'];
        $this->user_id=$_SESSION['user_id'];
    }

    function __construct($marketing_channel_id = 0){
        if ($marketing_channel_id>0)
    	    $this->Load($marketing_channel_id);
      	else
      	  $this->create_new_marketing_channel();
    }

    public function Load($marketing_channel_id)
    {
        $db = DB::getInstance();
        if ($marketing_channel_id>0) {
            $marketing_channel_data=$db->getRow("select * from marketing_channels where id=?i",$marketing_channel_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$marketing_channel_data)>0){
          		foreach($marketing_channel_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_marketing_channel_arr[$key]=$val;
          		    else $this->_marketing_channel_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_marketing_channel_arr[$name])) {
			return $this->_marketing_channel_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_marketing_channel_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_marketing_channel_arr[$name])) {
			$this->_marketing_channel_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update marketing_channels set ?u where id=?i";
            $db->query($sql,$this->_marketing_channel_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_marketing_channel_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into marketing_channels set ?u";
	    //echo $sql." ".print_r($this->_marketing_channel_arr,true);
            $db->query($sql,$this->_marketing_channel_arr);
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
