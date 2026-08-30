<?php

namespace Sort1API\Components;

class DetailMark
{
    private $_detail_mark_arr=array();

    private function create_new_detail_mark(){
    	$db= DB::getInstance();
		$res=$db->getAll("describe detail_mark");
		//echo "describe=".print_r($res,true)."\n";
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_detail_mark_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					//echo "this->_detail_markr_arr[".$val['Field']."]=".$val['Default']."\n";
        		    if(!empty($val['Default'])) $this->_detail_mark_arr[$val['Field']]=$val['Default'];
        		    else $this->_detail_mark_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_detail_mark_arr[$val['Field']]=$val['Default'];
        		    else $this->_detail_mark_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_detail_mark_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($detail_mark_id = 0){
        if ($detail_mark_id>0)
    	    $this->Load($detail_mark_id);
      	else
      	  $this->create_new_detail_mark();
    }

    public function Load($detail_mark_id)
    {
        $db = DB::getInstance();
        if ($detail_mark_id>0) {
            $detail_mark_data=$db->getRow("select * from detail_mark where id=?i",$detail_mark_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$detail_mark_data)>0){
          		foreach($detail_mark_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_detail_mark_arr[$key]=$val;
          		    else $this->_detail_mark_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_detail_mark_arr[$name])) {
			return $this->_detail_mark_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_detail_mark_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_detail_mark_arr[$name])) {
			$this->_detail_mark_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update detail_mark set ?u where id=?i and main_company_id=?i";
            $db->query($sql,$this->_detail_mark_arr,$this->id,$_SESSION['main_company']);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $this->main_company_id=$_SESSION['main_company'];
            $this->user_id=$_SESSION['user_id'];
            $sql="insert ignore into detail_mark set ?u";
	        //echo $sql." ".print_r($this->_detail_mark_arr,true);
            $db->query($sql,$this->_detail_mark_arr);
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
