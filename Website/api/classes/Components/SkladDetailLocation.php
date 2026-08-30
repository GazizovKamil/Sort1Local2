<?php

namespace Sort1API\Components;

class SkladDetailLocation
{
    private $_sklad_detail_arr=array();
    public $is_exist=0;

    private function create_new_sklad_detail_location(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe sklad_detail_locations");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_sklad_detail_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_sklad_detail_arr[$val['Field']]=$val['Default'];
        		    else $this->_sklad_detail_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_sklad_detail_arr[$val['Field']]=$val['Default'];
        		    else $this->_sklad_detail_arr[$val['Field']]="";
        		}
	        }
    	}
		$this->is_exist=0;
		$this->_sklad_detail_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($sklad_id = 0,$detail_id = 0, $location = "", $location_id = 0){
      if($location_id>0){
        $this->LoadById($location_id);
      }
      else {
        if ($sklad_id>0 && $detail_id!=0 && !empty($location))
      	  $this->Load($sklad_id,$detail_id,$location);
  	    else
  	      $this->create_new_sklad_detail_location();
      }
    }

    public function Load($sklad_id,$detail_id,$location)
    {
        $db = DB::getInstance();
        if ($sklad_id>0) {
            $sklad_data=$db->getRow("select * from sklad_detail_locations where sklad_id=?i and detail_id=?i and location=?s",$sklad_id,$detail_id,$location);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$sklad_data)>0){
          		foreach($sklad_data as $key=>$val){
          		    $this->_sklad_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
	          else {
          		$this->create_new_sklad_detail_location();
          		$this->_sklad_detail_arr['sklad_id']=$sklad_id;
				$this->_sklad_detail_arr['detail_id']=$detail_id;
				$this->_sklad_detail_arr['location']=$location;
				$this->_sklad_detail_arr['main_company_id']=$_SESSION['main_company'];
          		$this->is_exist=0;
	          }
        }
    }

    public function LoadById($location_id)
    {
        $db = DB::getInstance();
        if ($location_id>0) {
            $sklad_data=$db->getRow("select * from sklad_detail_locations where id=?i",$location_id);
            //print_r($sklad_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count($sklad_data)>0){
          		foreach($sklad_data as $key=>$val){
          		    $this->_sklad_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
              //print_r($this->_sklad_detail_arr);
            }
        }
    }

	public function __get($name) {
		if (isset($this->_sklad_detail_arr[$name])) {
			return $this->_sklad_detail_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_sklad_detail_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_sklad_detail_arr[$name])) {
			$this->_sklad_detail_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist>0) {
    	    $this->_sklad_detail_arr['update_date']=date("Y-m-d H:i:s");
          /*$sql="update sklad_detail_locations set ?u where sklad_id=?i and detail_id=?i and location=?s";
          $db->query($sql,$this->_sklad_detail_arr,$this->sklad_id,$this->detail_id,$this->location);
          */
          $sql="update sklad_detail_locations set ?u where id=?i";
          $db->query($sql,$this->_sklad_detail_arr,$this->id);
          //echo "db->query($sql,".print_r($this->_sklad_detail_arr,true).",".$this->id.");";
          if ($db->affectedRows()>0) { return 1;}
    	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into sklad_detail_locations set ?u";
	          //echo $sql." ".print_r($this->_sklad_detail_arr,true);
            $db->query($sql,$this->_sklad_detail_arr);
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
