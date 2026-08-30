<?php

namespace Sort1API\Components;

class CompanyBalance
{
    private $_cb_arr=array();

    private function create_new_cb($company_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe company_balance");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_cb_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_cb_arr[$val['Field']]=0;
    		else $this->_cb_arr[$val['Field']]="";
    	    }
    	    if ($val['Field']=="company_id") $this->_cb_arr[$val['Field']]=$company_id;
    	    if ($val['Field']=="main_company_id") $this->_cb_arr[$val['Field']]=$_SESSION['main_company'];
    	}
    	//$this->Save();
    }

    function __construct($company_id = 0){
        if ($company_id>0)
    	    $this->Load($company_id);
      	else {
      		return 0;
      	}
    }

    public function Load($company_id)
    {
        $db = DB::getInstance();
        if ($company_id>0) {
            $company_data=$db->getRow("select * from company_balance where company_id=?i and main_company_id=?i",$company_id,$_SESSION['main_company']);
			//$fields=$db->getInd("Field","describe company_balance");
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$company_data)>0){
          		foreach($company_data as $key=>$val){
          		    $this->_cb_arr[$key]=$val;
          		}
            }
      	    else $this->create_new_cb($company_id);
        }
	      return 0;
    }

	public function __get($name) {
		if (isset($this->_cb_arr[$name])) {
			return $this->_cb_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_cb_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_cb_arr[$name])) {
			$this->_cb_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update company_balance set ?u where id=?i";
            $db->query($sql,$this->_cb_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            //$this->create_date=date("Y-m-d H:i:s");
            //$save_data['create_date']=$this->create_date;
            $sql="insert ignore into company_balance set ?u";
	    //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_cb_arr);
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
