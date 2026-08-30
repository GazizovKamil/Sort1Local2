<?php

namespace Sort1API\Components;

class NewInSort1
{
    private $_new_in_sort1_arr=array();
    public $is_exist=0;

    private function create_new_new_in_sort1(){
		$db= DB::getInstance();
		$res=$db->getAll("describe new_in_sort1");
		foreach($res as $key=>$val){
			if ($val['Field']=="create_date") $this->_new_in_sort1_arr[$val['Field']]=date("Y-m-d H:i:s");
			else {
				if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					if(!empty($val['Default'])) $this->_new_in_sort1_arr[$val['Field']]=$val['Default'];
					else $this->_new_in_sort1_arr[$val['Field']]=0;
				}
				else {
					if(!empty($val['Default'])) $this->_new_in_sort1_arr[$val['Field']]=$val['Default'];
					else $this->_new_in_sort1_arr[$val['Field']]="";
				}
			}
		}
		$this->is_exist=0; 
		$this->create_by=$_SESSION['user_id'];
    }

    function __construct($new_in_sort1_id = 0){
        if ($new_in_sort1_id>0)
    	    $this->Load($new_in_sort1_id);
	else 
	    $this->create_new_new_in_sort1();
    }

    public function Load($new_in_sort1_id)
    {
		$db = DB::getInstance();
		//if($detail_id<0) $detail_id=-$detail_id;
        if ($new_in_sort1_id>0) {
            $new_in_sort1_data=$db->getRow("select * from new_in_sort1 where id=?i and deleted=0",$new_in_sort1_id);
            //print_r($price_list_data,true);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$new_in_sort1_data)>0){
				foreach($new_in_sort1_data as $key=>$val){
					$this->_new_in_sort1_arr[$key]=$val;
				}
				$this->is_exist=1;
            }
			else {
				$this->create_new_new_in_sort1();
				$this->_new_in_sort1_arr['user_id']=$_SESSION['user_id'];
				$this->_new_in_sort1_arr['main_company_id']=$_SESSION['main_company'];
				$this->is_exist=0;
			}
        }
    }

	public function __get($name) {
		if (isset($this->_new_in_sort1_arr[$name])) {
			return $this->_new_in_sort1_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_new_in_sort1_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_new_in_sort1_arr[$name])) {
			$this->_new_in_sort1_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist==1) {
	        $this->_new_in_sort1_arr['update_date']=date("Y-m-d H:i:s");
            $this->update_by=$_SESSION['user_id'];
            $sql="update new_in_sort1 set ?u where id=?i";
            $db->query($sql,$this->_new_in_sort1_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_detail_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	        else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
			$this->update_date='0000-00-00';
            $sql="insert into new_in_sort1 set ?u";
            $db->query($sql,$this->_new_in_sort1_arr);
            if ($db->affectedRows()>0) {
		    return 1;
	    }
	    else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
