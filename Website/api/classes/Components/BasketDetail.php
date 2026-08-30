<?php

namespace Sort1API\Components;

class BasketDetail
{
    private $_basket_detail_arr=array();
    public $is_exist=0;

    private function create_new_basket_detail($basket_id=0){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe basket_details");
    	//echo print_r($res,true);
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_basket_detail_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default']))
        			$this->_basket_detail_arr[$val['Field']]=$val['Default'];
        		    else
        			$this->_basket_detail_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default']))
        			$this->_basket_detail_arr[$val['Field']]=$val['Default'];
        		    else
        			$this->_basket_detail_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_basket_detail_arr['basket_id']=$basket_id;
    	$this->is_exist=0;
    }

    function __construct($basket_id = 0,$detail_id = 0, $deliverer_type=0, $deliverer_id=0, $sort1_id="", $document_detail_id=0){
		//echo "$basket_id ,$detail_id, $deliverer_type, $deliverer_id, $sort1_id, $document_detail_id\n";

        if ($basket_id>0 && $detail_id!=0 && ($deliverer_type>0 || (int)$deliverer_type==-1) && ($deliverer_id>0 || (int)$deliverer_id==-1))
    	    $this->Load($basket_id,$detail_id,$deliverer_type,$deliverer_id, $sort1_id, $document_detail_id);
      	else
      	  $this->create_new_basket_detail($basket_id);
    }

    public function Load($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id,$document_detail_id)
    {
        $db = DB::getInstance();
        if ($basket_id>0) {
            $basket_data=$db->getRow("select * from basket_details where basket_id=?i and detail_id=?i and deliverer_type=?i and deliverer_id=?i and sort1_id=?s and document_detail_id=?i",$basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id,$document_detail_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$basket_data)>0){
				foreach($basket_data as $key=>$val){
					$this->_basket_detail_arr[$key]=$val;
				}
				$this->is_exist=1;
            }
	    else {
			$this->create_new_basket_detail();
			$this->_basket_detail_arr['basket_id']=$basket_id;
			$this->_basket_detail_arr['detail_id']=$detail_id;
			$this->is_exist=0;
	    }
        }
    }

	public function __get($name) {
		if (isset($this->_basket_detail_arr[$name])) {
			return $this->_basket_detail_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_basket_detail_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_basket_detail_arr[$name])) {
			$this->_basket_detail_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist>0) {
	    	$this->_basket_detail_arr['update_date']=date("Y-m-d H:i:s");
            $sql="update basket_details set ?u where basket_id=?i and detail_id=?i and deliverer_type=?i and deliverer_id=?i and sort1_id=?s";
            $db->query($sql,$this->_basket_detail_arr,$this->basket_id,$this->detail_id,$this->deliverer_type,$this->deliverer_id,$this->sort1_id);
            //echo "db->query($sql,".print_r($this->_sklad_detail_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
			$this->_basket_detail_arr['update_date']=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into basket_details set ?u";
	    //echo $sql." ".print_r($this->_sklad_detail_arr,true);
            $db->query($sql,$this->_basket_detail_arr);
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
