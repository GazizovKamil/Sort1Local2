<?php

namespace Sort1API\Components;

class ZakazGarant
{
    private $_ZakazGarant_arr=array();

    private function create_new_ZakazGarant(){
    	$db= DB::getInstance();
		$res=$db->getAll("describe zakaz_garant");
		//echo "describe=".print_r($res,true)."\n";
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_ZakazGarant_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					//echo "this->_inventr_arr[".$val['Field']."]=".$val['Default']."\n";
        		    if(!empty($val['Default'])) $this->_ZakazGarant_arr[$val['Field']]=$val['Default'];
        		    else $this->_ZakazGarant_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_ZakazGarant_arr[$val['Field']]=$val['Default'];
        		    else $this->_ZakazGarant_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_ZakazGarant_arr['user_id']=$_SESSION['user_id'];
      $this->_ZakazGarant_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($ZakazGarant_id = 0){
        if ($ZakazGarant_id>0)
    	    $this->Load($ZakazGarant_id);
      	else
      	  $this->create_new_ZakazGarant();
    }

    public function Load($ZakazGarant_id)
    {
        $db = DB::getInstance();
        if ($ZakazGarant_id>0) {
            $ZakazGarant_data=$db->getRow("select * from zakaz_garant where id=?i",$ZakazGarant_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$ZakazGarant_data)>0){
          		foreach($ZakazGarant_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_ZakazGarant_arr[$key]=$val;
          		    else $this->_ZakazGarant_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_ZakazGarant_arr[$name])) {
			return $this->_ZakazGarant_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_ZakazGarant_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_ZakazGarant_arr[$name])) {
			$this->_ZakazGarant_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update zakaz_garant set ?u where id=?i";
            $db->query($sql,$this->_ZakazGarant_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into zakaz_garant set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_ZakazGarant_arr);
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
