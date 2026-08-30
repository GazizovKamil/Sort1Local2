<?php

namespace Sort1API\Components;

class ZakazFooter
{
    private $_ZakazFooter_arr=array();

    private function create_new_ZakazFooter(){
    	$db= DB::getInstance();
		$res=$db->getAll("describe zakaz_footers");
		//echo "describe=".print_r($res,true)."\n";
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_ZakazFooter_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
					//echo "this->_inventr_arr[".$val['Field']."]=".$val['Default']."\n";
        		    if(!empty($val['Default'])) $this->_ZakazFooter_arr[$val['Field']]=$val['Default'];
        		    else $this->_ZakazFooter_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_ZakazFooter_arr[$val['Field']]=$val['Default'];
        		    else $this->_ZakazFooter_arr[$val['Field']]="";
        		}
    	    }
    	}
      $this->_ZakazFooter_arr['user_id']=$_SESSION['user_id'];
      $this->_ZakazFooter_arr['main_company_id']=$_SESSION['main_company'];
    }

    function __construct($ZakazFooter_id = 0){
        if ($ZakazFooter_id>0)
    	    $this->Load($ZakazFooter_id);
      	else
      	  $this->create_new_ZakazFooter();
    }

    public function Load($ZakazFooter_id)
    {
        $db = DB::getInstance();
        if ($ZakazFooter_id>0) {
            $ZakazFooter_data=$db->getRow("select * from zakaz_footers where id=?i",$ZakazFooter_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$ZakazFooter_data)>0){
          		foreach($ZakazFooter_data as $key=>$val){
          		    if(!empty($val) || $val == 0) $this->_ZakazFooter_arr[$key]=$val;
          		    else $this->_ZakazFooter_arr[$key]="";
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_ZakazFooter_arr[$name])) {
			return $this->_ZakazFooter_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_ZakazFooter_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_ZakazFooter_arr[$name])) {
			$this->_ZakazFooter_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update zakaz_footers set ?u where id=?i";
            $db->query($sql,$this->_ZakazFooter_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into zakaz_footers set ?u";
	          //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_ZakazFooter_arr);
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
