<?php

namespace Sort1API\Components;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\Document;
use Sort1API\Components\LogisticOrderDetail;
use Sort1API\Components\LogisticOrder;
use Sort1API\Components\Models\DocumentDetails;
use Sort1API\Components\Zakaz;

class MarketZakazDetail
{
    private $market_zakaz_detail_arr=array();
    public $is_exist=0;

    private function create_new_zakaz_detail($zakaz_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe market_zakaz_details");
    	foreach($res as $key=>$val){
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
    		    if(!empty($val['Default'])) $this->market_zakaz_detail_arr[$val['Field']]=$val['Default'];
    		    else $this->market_zakaz_detail_arr[$val['Field']]=0;
    		}
    		else {
    		    if(!empty($val['Default'])) $this->market_zakaz_detail_arr[$val['Field']]=$val['Default'];
    		    else $this->market_zakaz_detail_arr[$val['Field']]="";
    		}
    	}
    	$this->market_zakaz_id=$zakaz_id;
    	$this->is_exist=0;
    }

    function __construct($market_zakaz_id=0){
        if ($market_zakaz_id>0)
            $this->Load($market_zakaz_id);
        else {
            $this->create_new_zakaz_detail($market_zakaz_id);
        }
    }

    public function Load($market_zakaz_id)
    {
        $db = DB::getInstance();
        if ($market_zakaz_id>0) {
            $zakaz_data=$db->getRow("select * from market_zakaz_details where market_zakaz_id=?i",$market_zakaz_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$zakaz_data)>0){
          		foreach($zakaz_data as $key=>$val){
          		    $this->market_zakaz_detail_arr[$key]=$val;
          		}
            }
      	    else {
          		$this->create_new_zakaz_detail($market_zakaz_id);
          		$this->is_exist=0;
      	    }
        }
    }

	public function __get($name) {
		if (isset($this->market_zakaz_detail_arr[$name])) {
			return $this->market_zakaz_detail_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->market_zakaz_detail_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->market_zakaz_detail_arr[$name])) {
			$this->market_zakaz_detail_arr[$name]=$val;
		}
        else{
			$this->market_zakaz_detail_arr[$name]=$val;
		}
	}

    private function convert_string($str){
      $del=array("/","\\","-","+"," ","\xC2\xA0","\n","\t","\r",".","_","*","%","$","#","@","!","&","^");
      return mb_strtoupper(str_replace($del,"",$str));
    }

    public function Save() {
        $db = DB::getInstance();
    
        if ($this->id > 0) {
            $this->market_zakaz_detail_arr['update_date'] = date("Y-m-d H:i:s");
            $sql = "UPDATE market_zakaz_details SET ?u WHERE market_zakaz_id = ?i";
            $db->query($sql, $this->market_zakaz_detail_arr, $this->market_zakaz_id);
    
            if ($db->affectedRows() > 0) {
                return 1;
            } else {
                return 10;
            }
        } else {
            $sql = "INSERT IGNORE INTO market_zakaz_details SET ?u";
            $db->query($sql, $this->market_zakaz_detail_arr);
    
            return $db->insertId();
        }
    }
}
?>
