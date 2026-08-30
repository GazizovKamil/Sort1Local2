<?php

namespace Sort1API\Components;

class LocalCrossBlack
{
    private $_local_cross_black_arr=array();
    public $is_exist=0;

    private function create_new_local_cross_black(){
		$db= DB::getInstance();
		$res=$db->getAll("describe local_cross_black");
		foreach($res as $key=>$val){
			if ($val['Field']=="create_date") $this->_local_cross_black_arr[$val['Field']]=date("Y-m-d H:i:s");
			else {
				if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					if(!empty($val['Default'])) $this->_local_cross_black_arr[$val['Field']]=$val['Default'];
					else $this->_local_cross_black_arr[$val['Field']]=0;
				}
				else {
					if(!empty($val['Default'])) $this->_local_cross_black_arr[$val['Field']]=$val['Default'];
					else $this->_local_cross_black_arr[$val['Field']]="";
				}
			}
		}
		$this->is_exist=0; 
		$this->user_id=$_SESSION['user_id'];
		$this->main_company_id=$_SESSION['main_company'];
    }

    function __construct($local_cross_black_id = 0){
        if ($local_cross_black_id>0)
    	    $this->Load($local_cross_black_id);
	else 
	    $this->create_new_local_cross_black();
    }

    public function Load($local_cross_black_id)
    {
		$db = DB::getInstance();
		//if($detail_id<0) $detail_id=-$detail_id;
        if ($local_cross_black_id>0) {
            $local_cross_black_data=$db->getRow("select * from local_cross_black where id=?i and main_company_id=?i",$local_cross_black_id,$_SESSION['main_company']);
            //print_r($price_list_data,true);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$local_cross_black_data)>0){
				foreach($local_cross_black_data as $key=>$val){
					$this->_local_cross_black_arr[$key]=$val;
				}
				$this->is_exist=1;
            }
			else {
				$this->create_new_local_cross_black();
				$this->_local_cross_black_arr['user_id']=$_SESSION['user_id'];
				$this->_local_cross_black_arr['main_company_id']=$_SESSION['main_company'];
				$this->is_exist=0;
			}
        }
    }

	public function __get($name) {
		if (isset($this->_local_cross_black_arr[$name])) {
			return $this->_local_cross_black_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_local_cross_black_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_local_cross_black_arr[$name])) {
			$this->_local_cross_black_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist==1) {
	    $this->_local_cross_black_arr['update_date']=date("Y-m-d H:i:s");
            $sql="update local_cross_black set ?u where id=?i";
            $db->query($sql,$this->_local_cross_black_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_detail_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
			$this->update_date='0000-00-00';
            $sql="insert into local_cross_black set ?u";
            $db->query($sql,$this->_local_cross_black_arr);
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
