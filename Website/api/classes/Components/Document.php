<?php

namespace Sort1API\Components;

class Document
{
    private $_document_arr=array();
	public $is_exist=0;

    private function create_new_document(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe document");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_document_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_document_arr[$val['Field']]=$val['Default'];
        		    else $this->_document_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_document_arr[$val['Field']]=$val['Default'];
        		    else $this->_document_arr[$val['Field']]="";
        		}
    	    }
		}
		$this->_document_arr['user_id']=$_SESSION['user_id'];
		$this->_document_arr['document_date']=date("Y-m-d H:i:s");
		$this->_document_arr['chf_date']=date("Y-m-d H:i:s");
    }

    function __construct($document_id = 0){
        if ($document_id>0)
    	    $this->Load($document_id);
	      else
	       $this->create_new_document();
    }

    public function Load($document_id)
    {
        $db = DB::getInstance();
        if ($document_id>0) {
            $document_data=$db->getRow("select * from document where id=?i",$document_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$document_data)>0){
          		foreach($document_data as $key=>$val){
          		    $this->_document_arr[$key]=$val;
          		}
				$this->is_exist=1;
            }
	          $this->_document_arr['update_date']=date("Y-m-d H:i:s");
        }
    }

  	public function __get($name) {
  		if (isset($this->_document_arr[$name])) {
  			return $this->_document_arr[$name];
  		} else {
  			return null;
  		}
  	}

  	public function __isset($name){
  	    return isset($this->_document_arr[$name]);
  	}

  	public function __set($name,$val) {
		//echo $name."\n";
  		if (isset($this->_document_arr[$name])) {
			//echo "$name exist\n";
  			$this->_document_arr[$name]=$val;
  		}
		//else echo "not exist and val=".var_dump($this->_document_arr[$name])."\n";
  	}

    public function Save(){
        $db = DB::getInstance();
		if($this->return_confirm_date=='') $this->return_confirm_date='0000-00-00';
        if ($this->id>0) {
            $sql="update document set ?u where id=?i";
            $db->query($sql,$this->_document_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_document_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    	else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into document set ?u";
	    	//echo $sql." ".print_r($this->_document_arr,true);
            $db->query($sql,$this->_document_arr);
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
