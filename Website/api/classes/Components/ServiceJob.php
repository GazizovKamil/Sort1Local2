<?php

namespace Sort1API\Components;

class ServiceJob
{
    private $_service_job_arr=array();

    private function create_new_service_job(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe service_jobs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_service_job_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_service_job_arr[$val['Field']]=$val['Default'];
        		    else $this->_service_job_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_service_job_arr[$val['Field']]=$val['Default'];
        		    else $this->_service_job_arr[$val['Field']]="";
        		}
    	    }
    	}
    }

    function __construct($service_job_id = 0){
        if ($service_job_id>0)
    	    $this->Load($service_job_id);
      	else
      	  $this->create_new_service_job();
    }

    public function Load($service_job_id)
    {
        $db = DB::getInstance();
        if ($service_job_id>0) {
            $service_job_data=$db->getRow("select * from service_jobs where id=?i",$service_job_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$service_job_data)>0){
          		foreach($service_job_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_service_job_arr[$key]=$val;
          		    else $this->_service_job_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_service_job_arr[$name])) {
			return $this->_service_job_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_service_job_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_service_job_arr[$name])) {
			$this->_service_job_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
	    $this->update_date=date("Y-m-d H:i:s");
	    $this->update_by=$_SESSION['user_id'];
            $sql="update service_jobs set ?u where id=?i";
            $db->query($sql,$this->_service_job_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_service_job_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            //$save_data['create_date']=$this->create_date;
            $sql="insert ignore into service_jobs set ?u";
	    //echo $sql." ".print_r($this->_service_job_arr,true);
            $db->query($sql,$this->_service_job_arr);
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
