<?php

namespace Sort1API\Components;

class PlannedDealerPayment
{
    private $_planned_dealer_payment_arr=array();

    private function create_new_planned_dealer_payment(){
    	$db= DB::getInstance();
		$res=$db->getAll("describe planned_dealer_payments");
		//echo "describe=".print_r($res,true)."\n";
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_planned_dealer_payment_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					//echo "this->_inventr_arr[".$val['Field']."]=".$val['Default']."\n";
        		    if(!empty($val['Default'])) $this->_planned_dealer_payment_arr[$val['Field']]=$val['Default'];
        		    else $this->_planned_dealer_payment_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_planned_dealer_payment_arr[$val['Field']]=$val['Default'];
        		    else $this->_planned_dealer_payment_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_planned_dealer_payment_arr['user_id']=$_SESSION['user_id'];
      $this->_planned_dealer_payment_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($planned_dealer_payment_id = 0){
        if ($planned_dealer_payment_id>0)
    	    $this->Load($planned_dealer_payment_id);
      	else
      	  $this->create_new_planned_dealer_payment();
    }

    public function Load($planned_dealer_payment_id)
    {
        $db = DB::getInstance();
        if ($planned_dealer_payment_id>0) {
            $planned_dealer_payment_data=$db->getRow("select * from planned_dealer_payments where id=?i",$planned_dealer_payment_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$planned_dealer_payment_data)>0){
          		foreach($planned_dealer_payment_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_planned_dealer_payment_arr[$key]=$val;
          		    else $this->_planned_dealer_payment_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_planned_dealer_payment_arr[$name])) {
			return $this->_planned_dealer_payment_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_planned_dealer_payment_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_planned_dealer_payment_arr[$name])) {
			$this->_planned_dealer_payment_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update planned_dealer_payments set ?u where id=?i";
            $db->query($sql,$this->_planned_dealer_payment_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into planned_dealer_payments set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_planned_dealer_payment_arr);
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
