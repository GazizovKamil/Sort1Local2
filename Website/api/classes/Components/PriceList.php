<?php

namespace Sort1API\Components;

class PriceList
{
    private $_price_arr=array();

    private function create_new_price(){
	$db= DB::getInstance();
	$res=$db->getAll("describe price_list");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_price_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_price_arr[$val['Field']]=0;
		else $this->_price_arr[$val['Field']]="";
	    }
	}
    }

    function __construct($price_id = 0){
        if ($price_id>0)
    	    $this->Load($price_id);
	else 
	    $this->create_new_price();
    }

    public function Load($price_id)
    {
        $db = DB::getInstance();
        if ($price_id>0) {
            $price_data=$db->getRow("select * from price_list where id=?i",$price_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$price_data)>0){
		foreach($price_data as $key=>$val){
		    $this->_price_arr[$key]=$val;
		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_price_arr[$name])) {
			return $this->_price_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_price_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_price_arr[$name])) {
			$this->_price_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
	    if ($this->status==0) $sqlst="update price_list_details set is_active=0 where price_list_id=?i";
	    elseif($this->status==1) $sqlst="update price_list_details set is_active=1 where price_list_id=?i";
	    if(isset($sqlst)) $db->query($sqlst,$this->id);
            $sql="update price_list set ?u where id=?i";
            $db->query($sql,$this->_price_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_price_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into price_list set ?u";
	    //echo $sql." ".print_r($this->_price_arr,true);
            $db->query($sql,$this->_price_arr);
            if ($db->affectedRows()>0) {
				$this->id=$db->insertId();
				return 1;
			}
	    	else return "Прайс-лист с таким наименованием уже существует";
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
