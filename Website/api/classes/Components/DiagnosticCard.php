<?php

namespace Sort1API\Components;

class DiagnosticCard
{
    private $_diagnostic_card_arr=array();

    private function create_new_diagnostic_card(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe diagnostic_cards");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_diagnostic_card_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_diagnostic_card_arr[$val['Field']]=$val['Default'];
        		    else $this->_diagnostic_card_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_diagnostic_card_arr[$val['Field']]=$val['Default'];
        		    else $this->_diagnostic_card_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_diagnostic_card_arr['main_company_id']=$_SESSION['main_company'];
      $this->_diagnostic_card_arr['user_id']=$_SESSION['user_id'];
      $this->_diagnostic_card_arr['create_date']=date("Y-m-d H:i:s");
    }

    function __construct($diagnostic_card_id = 0, $zakaz_id = 0){
        if ($diagnostic_card_id>0)
    	    $this->Load($diagnostic_card_id);
      	else{
            if ($zakaz_id>0)
    	        $this->LoadByZakazId($zakaz_id);
            else
      	        $this->create_new_diagnostic_card();
        }
    }

    public function Load($diagnostic_card_id)
    {
        $db = DB::getInstance();
        if ($diagnostic_card_id>0) {
            $diagnostic_card_data=$db->getRow("select * from diagnostic_cards where id=?i",$diagnostic_card_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$diagnostic_card_data)>0){
          		foreach($diagnostic_card_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_diagnostic_card_arr[$key]=$val;
          		    else $this->_diagnostic_card_arr[$key]="";
          		}
            }
            else {

            }
        }
    }

    public function LoadByZakazId($zakaz_id)
    {
        $db = DB::getInstance();
        if ($zakaz_id>0) {
            $diagnostic_card_data=$db->getRow("select * from diagnostic_cards where zakaz_id=?i",$zakaz_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$diagnostic_card_data)>0){
          		foreach($diagnostic_card_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_diagnostic_card_arr[$key]=$val;
          		    else $this->_diagnostic_card_arr[$key]="";
          		}
            }
            else {
                $this->create_new_diagnostic_card();
                $zakaz_car_id=$db->getRow("select car_id,company_id from zakaz where id=?i",$zakaz_id);
                $this->zakaz_id=$zakaz_id;
                $this->company_car_id=$zakaz_car_id['car_id'];
                $this->company_id=$zakaz_car_id['company_id'];
            }
        }
    }

	public function __get($name) {
		if (isset($this->_diagnostic_card_arr[$name])) {
			return $this->_diagnostic_card_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_diagnostic_card_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_diagnostic_card_arr[$name])) {
			$this->_diagnostic_card_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update diagnostic_cards set ?u where id=?i";
            $db->query($sql,$this->_diagnostic_card_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into diagnostic_cards set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_diagnostic_card_arr);
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
