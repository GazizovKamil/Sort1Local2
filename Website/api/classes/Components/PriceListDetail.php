<?php

namespace Sort1API\Components;

class PriceListDetail
{
    private $_price_list_detail_arr=array();
    public $is_exist=0;

    private function create_new_price_list_detail(){
	$db= DB::getInstance();
	$res=$db->getAll("describe price_list_details");
	foreach($res as $key=>$val){
	    if ($val['Field']=="create_date") $this->_price_list_detail_arr[$val['Field']]=date("Y-m-d H:i:s");
	    else {
		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
		    if(!empty($val['Default'])) $this->_price_list_detail_arr[$val['Field']]=$val['Default'];
		    else $this->_price_list_detail_arr[$val['Field']]=0;
		}
		else {
		    if(!empty($val['Default'])) $this->_price_list_detail_arr[$val['Field']]=$val['Default'];
		    else $this->_price_list_detail_arr[$val['Field']]="";
		}
	    }
	}
	$this->is_exist=0;
    }

    function __construct($price_list_id = 0,$detail_id = 0){
        if ($price_list_id>0)
    	    $this->Load($price_list_id,$detail_id);
	else 
	    $this->create_new_price_list_detail();
    }

    public function Load($price_list_id,$detail_id)
    {
		$db = DB::getInstance();
		//if($detail_id<0) $detail_id=-$detail_id;
        if ($price_list_id>0) {
            $price_list_data=$db->getRow("select * from price_list_details where price_list_id=?i and detail_id=?i",$price_list_id,$detail_id);
            //print_r($price_list_data,true);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$price_list_data)>0){
				foreach($price_list_data as $key=>$val){
					$this->_price_list_detail_arr[$key]=$val;
				}
				$this->is_exist=1;
            }
			else {
				$this->create_new_price_list_detail();
				$this->_price_list_detail_arr['price_list_id']=$price_list_id;
				$this->_price_list_detail_arr['detail_id']=$detail_id;
				$this->is_exist=0;
			}
        }
    }

	public function __get($name) {
		if (isset($this->_price_list_detail_arr[$name])) {
			return $this->_price_list_detail_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_price_list_detail_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_price_list_detail_arr[$name])) {
			$this->_price_list_detail_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist==1) {
	    	$this->_price_list_detail_arr['update_date']=date("Y-m-d H:i:s");
            $sql="update price_list_details set ?u where price_list_id=?i and detail_id=?i";
            $db->query($sql,$this->_price_list_detail_arr,$this->price_list_id,$this->detail_id);
            //echo "db->query($sql,".print_r($this->_sklad_detail_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
	    $this->default_markup=$db->getOne("select default_markup from price_list where id=?i",$this->price_list_id);
            //$save_data['create_date']=$this->create_date;
            $sql="insert ignore into price_list_details set ?u";
	    //echo $sql." ".print_r($this->_price_list_detail_arr,true);
            $db->query($sql,$this->_price_list_detail_arr);
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
