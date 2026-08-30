<?php

namespace Sort1API\Components;

class FixPriceDetail
{
    private $_fix_price_detail_arr=array();
    public $is_exist=0;

    private function create_new_fix_price_detail(){
      	$db= DB::getInstance();
      	$res=$db->getAll("describe fix_price_details");
      	foreach($res as $key=>$val){
      	    if ($val['Field']=="create_date") $this->_fix_price_detail_arr[$val['Field']]=date("Y-m-d H:i:s");
      	    else {
          		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
          		    if(!empty($val['Default'])) $this->_fix_price_detail_arr[$val['Field']]=$val['Default'];
          		    else $this->_fix_price_detail_arr[$val['Field']]=0;
          		}
          		else {
          		    if(!empty($val['Default'])) $this->_fix_price_detail_arr[$val['Field']]=$val['Default'];
          		    else $this->_fix_price_detail_arr[$val['Field']]="";
          		}
      	    }
      	}
      	$this->is_exist=0;
      	$this->_fix_price_detail_arr['main_company']=$_SESSION['main_company'];
    }

    function __construct($detail_id = 0){
        if ($detail_id!=0)
    	    $this->Load($detail_id);
      	else
      	    $this->create_new_fix_price_detail();
    }

    public function Load($detail_id){
        $db = DB::getInstance();
        if ($detail_id!=0) {
            $fix_price_data=$db->getRow("select * from fix_price_details where detail_id=?i and main_company=?i",$detail_id,$_SESSION['main_company']);
            //print_r($fix_price_data,true);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$fix_price_data)>0){
          		foreach($fix_price_data as $key=>$val){
          		    $this->_fix_price_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
          	else {
          		$this->create_new_fix_price_detail();
          		$this->_fix_price_detail_arr['detail_id']=$detail_id;
          		$this->is_exist=0;
          	}
        }
    }

	public function __get($name) {
		if (isset($this->_fix_price_detail_arr[$name])) {
			return $this->_fix_price_detail_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_fix_price_detail_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_fix_price_detail_arr[$name])) {
			$this->_fix_price_detail_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist>0) {
	          $this->_fix_price_detail_arr['update_date']=date("Y-m-d H:i:s");
            $sql="update fix_price_details set ?u where detail_id=?i";
            $db->query($sql,$this->_fix_price_detail_arr,$this->detail_id);
            //echo "db->query($sql,".print_r($this->_fix_price_detail_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $sql="insert ignore into fix_price_details set ?u";
	          //echo $sql." ".print_r($this->_fix_price_detail_arr,true);
            $db->query($sql,$this->_fix_price_detail_arr);
            if ($db->affectedRows()>0) {
          		//$this->id=$db->insertId();
          		return 1;
	           }
	           else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
