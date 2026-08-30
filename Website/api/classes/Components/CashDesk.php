<?php

namespace Sort1API\Components;

class CashDesk
{
    private $_cash_desk_arr=array();

    private function create_new_cash_desk(){
    	$db= DB::getInstance();
		$res=$db->getAll("describe cash_desks");
		//echo "describe=".print_r($res,true)."\n";
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_cash_desk_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					//echo "this->_inventr_arr[".$val['Field']."]=".$val['Default']."\n";
        		    if(!empty($val['Default'])) $this->_cash_desk_arr[$val['Field']]=$val['Default'];
        		    else $this->_cash_desk_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_cash_desk_arr[$val['Field']]=$val['Default'];
        		    else $this->_cash_desk_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_cash_desk_arr['main_company_id']=$_SESSION['main_company'];
	  $this->_cash_desk_arr['sklad_id']=$_SESSION['my_sklad_id'];
    }

    function __construct($cash_desk_id = 0){
        if ($cash_desk_id>0)
    	    $this->Load($cash_desk_id);
      	else
      	  $this->create_new_cash_desk();
    }

    public function Load($cash_desk_id)
    {
        $db = DB::getInstance();
        if ($cash_desk_id>0) {
            $cash_desk_data=$db->getRow("select * from cash_desks where id=?i",$cash_desk_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$cash_desk_data)>0){
          		foreach($cash_desk_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_cash_desk_arr[$key]=$val;
          		    else $this->_cash_desk_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_cash_desk_arr[$name])) {
			return $this->_cash_desk_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_cash_desk_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_cash_desk_arr[$name])) {
			$this->_cash_desk_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update cash_desks set ?u where id=?i";
            $db->query($sql,$this->_cash_desk_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into cash_desks set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_cash_desk_arr);
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
