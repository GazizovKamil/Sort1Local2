<?php

namespace Sort1API\Components;

class FavoriteDetail
{
    private $_details_arr=array();

    private function create_new_favorite_detail(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe favorite_details");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_details_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default'])) $this->_details_arr[$val['Field']]=$val['Default'];
        		    else $this->_details_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default'])) $this->_details_arr[$val['Field']]=$val['Default'];
        		    else $this->_details_arr[$val['Field']]="";
        		}
    	    }
    	}
    }

    function __construct($detail_id = 0){
        if ($detail_id!=0)
    	    $this->Load($detail_id);
      	else
      	  $this->create_new_favorite_detail();

		$db= DB::getInstance();
		preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
		$sql="select id,company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
		$site_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
		$this->main_company_id=$site_data['company_id'];
			
		if ($_SESSION['user_id']>0) {
			$this->main_company_id=$_SESSION['main_company'];
			$this->user_id=$_SESSION['user_id'];
			$this->site_id=$site_data['id'];
		}
		else {
			$this->session_id=session_id();
			$this->site_id=$site_data['id'];
		}
    }

    public function Load($detail_id)
    {
		if((int)$detail_id!=0){
			$db = DB::getInstance();
			if ($_SESSION['user_id']>0) {
				$fd_data=$db->getRow("select * from favorite_details where user_id=?i and main_company_id=?i and detail_id=?i limit 1",$_SESSION['user_id'],$_SESSION['main_company'],$detail_id);

				if (count((array)$fd_data)>0){
					foreach($fd_data as $key=>$val){
						if(!empty($val) || $val == 0) $this->_details_arr[$key]=$val;
						else $this->_details_arr[$key]="";
					}
				}
				else
      	  			$this->create_new_favorite_detail();
			}
			else {
				preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
				$sql="select id,company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
				$site_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
				$main_company_id=$site_data['company_id'];
				$fd_data=$db->getRow("select * from favorite_details where session_id=?s and main_company_id=?i and detail_id=?i limit 1",session_id(),$main_company_id,$detail_id);

				if (count((array)$fd_data)>0){
					foreach($fd_data as $key=>$val){
						if(!empty($val) || $val == 0) $this->_details_arr[$key]=$val;
						else $this->_details_arr[$key]="";
					}
				}
				else
      	  			$this->create_new_favorite_detail();
			}
		}
		else
      	  $this->create_new_favorite_detail();
    }

	public function __get($name) {
		if (isset($this->_details_arr[$name])) {
			return $this->_details_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_details_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_details_arr[$name])) {
			$this->_details_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update favorite_details set ?u where user_id=?i";
            $db->query($sql,$this->_details_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_details_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    	else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into favorite_details set ?u";
	    	//echo $sql." ".print_r($this->_details_arr,true);
            $db->query($sql,$this->_details_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return 1;
      	    }
      	    else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

	public function delete(){
		$db = DB::getInstance();
        if ($this->id>0) {
            $sql="delete from favorite_details where id=?i";
            $db->query($sql,$this->id);
            //echo "db->query($sql,".print_r($this->_details_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	    	else { return 10; }
        }
        else {
			return 0;
		}
	}
}
?>
