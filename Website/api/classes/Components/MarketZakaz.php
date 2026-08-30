<?php

namespace Sort1API\Components;

class MarketZakaz
{
	private $market_zakaz_arr=array();

    private function create_new_zakaz(){
		$db= DB::getInstance();
		$res=$db->getAll("describe market_zakaz");
		foreach($res as $key=>$val){
			if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
				if(!empty($val['Default'])) $this->market_zakaz_arr[$val['Field']]=$val['Default'];
				else $this->market_zakaz_arr[$val['Field']]=0;
			}
			else {
				if(!empty($val['Default'])) $this->market_zakaz_arr[$val['Field']]=$val['Default'];
				else $this->market_zakaz_arr[$val['Field']]="";
			}
			if ($val['Field']=="user_id") $this->market_zakaz_arr[$val['Field']]=$_SESSION['user_id'];
			if ($val['Field']=="main_company_id") $this->market_zakaz_arr[$val['Field']]=$_SESSION['main_company'];
		}
    }

    function __construct($zakaz_id_in_marketplace){
		$db = DB::getInstance();
		$zakaz_data=$db->getRow("select * from market_zakaz where zakaz_id_in_marketplace=?i",$zakaz_id_in_marketplace);
		// print_r($zakaz_data);
        if (count((array)$zakaz_data)>0)
    	    $this->Load($zakaz_id_in_marketplace);
		else {
			$this->create_new_zakaz();
		}
    }

    public function Load($zakaz_id_in_marketplace)
    {
        $db = DB::getInstance();
		$zakaz_data=$db->getRow("select * from market_zakaz where zakaz_id_in_marketplace=?i",$zakaz_id_in_marketplace);

		foreach($zakaz_data as $key=>$val){
			$this->market_zakaz_arr[$key]=$val;
		}
    }

	public function __get($name) {
		if (isset($this->market_zakaz_arr[$name])) {
			return $this->market_zakaz_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->market_zakaz_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->market_zakaz_arr[$name])) {
			$this->market_zakaz_arr[$name]=$val;
		}
		else{
			$this->market_zakaz_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
			$this->update_date=date("Y-m-d H:i:s");
            $sql="update market_zakaz set ?u where id=?i";
            $db->query($sql,$this->market_zakaz_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
			// print_r($db->getStats());
			// print_r($this->market_zakaz_arr);
            if ($db->affectedRows()>0) { return 1;}
			else { return 1; }
        }
        else {
            $sql="insert ignore into market_zakaz set ?u";
	    	//echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->market_zakaz_arr);
			// print_r($db->getStats());
			// print_r($this->market_zakaz_arr);
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
