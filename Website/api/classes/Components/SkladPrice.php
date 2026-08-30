<?php

namespace Sort1API\Components;

class SkladPrice
{
    private $_sklad_price_arr=array();

    private function create_new_sklad_price(){
    	$db= DB::getInstance();
		$res=$db->getAll("describe sklad_price");
		//echo "describe=".print_r($res,true)."\n";
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_sklad_price_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					//echo "this->_sklad_pricer_arr[".$val['Field']."]=".$val['Default']."\n";
        		    if(!empty($val['Default'])) $this->_sklad_price_arr[$val['Field']]=$val['Default'];
        		    else $this->_sklad_price_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_sklad_price_arr[$val['Field']]=$val['Default'];
        		    else $this->_sklad_price_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_sklad_price_arr['company_id']=$_SESSION['main_company'];
    }

    function __construct($sklad_price_id = 0){
        if ($sklad_price_id>0)
    	    $this->Load($sklad_price_id);
      	else
      	  $this->create_new_sklad_price();
    }

    public function Load($sklad_price_id)
    {
        $db = DB::getInstance();
        if ($sklad_price_id>0) {
            $sklad_price_data=$db->getRow("select * from sklad_price where id=?i",$sklad_price_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$sklad_price_data)>0){
          		foreach($sklad_price_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_sklad_price_arr[$key]=$val;
          		    else $this->_sklad_price_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_sklad_price_arr[$name])) {
			return $this->_sklad_price_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_sklad_price_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_sklad_price_arr[$name])) {
			$this->_sklad_price_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update sklad_price set ?u where id=?i";
            $db->query($sql,$this->_sklad_price_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into sklad_price set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_sklad_price_arr);
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
