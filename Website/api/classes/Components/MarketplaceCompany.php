<?php

namespace Sort1API\Components;

class MarketplaceCompany
{
    private $_comp_arr=array();
    private $_comp_rekvizits=array();

    private function create_new_comp(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe marketplace_company");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_comp_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_comp_arr[$val['Field']]=0;
    		else $this->_comp_arr[$val['Field']]="";
    	    }
    	}
    }

    private function create_new_comp_rek(){
    	$db= DB::getInstance();
    	// $res=$db->getAll("describe company_rekvizits");
    	foreach($res as $key=>$val){
    	    if ($val['Field']!="company_id" && $val['Field']!="user_id" && $val['Field']!="main_company")
          		if ($val['Field']=="create_date") $this->_comp_rekvizits[$val['Field']]=date("Y-m-d H:i:s");
          		else {
          		    if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_comp_rekvizits[$val['Field']]=0;
          		    else $this->_comp_rekvizits[$val['Field']]="";
          		}
    	}
    }

	public function __construct($company_identifier) {
        $db = DB::getInstance();

        if (is_numeric($company_identifier)) {
            $this->Load((int)$company_identifier);
        } else {
            $company = $db->getRow('SELECT id FROM marketplace_company WHERE email LIKE ?s', $company_identifier);
            if (!empty($company)) {
                $this->Load($company['id']);
            } else {
                $this->create_new_comp();
            }
        }
    }

    public function Load($company_id) {
        $db = DB::getInstance();

        if ((int)$company_id > 0) {
            $company_data = $db->getRow("SELECT * FROM marketplace_company WHERE id = ?i", $company_id);

            if (!empty($company_data)) {
                foreach ($company_data as $key => $val) {
                    $this->_comp_arr[$key] = (!empty($val) || $val == 0) ? $val : '';
                }
            } else {
                $this->create_new_comp();
            }
        }
    }

	public function __get($name) {
		if (isset($this->_comp_arr[$name])) {
			return $this->_comp_arr[$name];
		} else {
			// if (isset($this->_comp_rekvizits[$name])) {
			//     return $this->_comp_rekvizits[$name];
			// }
			return null;
		}
	}

	public function __isset($name){
	    return (isset($this->_comp_arr[$name]) || isset($this->_comp_rekvizits[$name]));
	}

	public function __set($name,$val) {
		if (isset($this->_comp_arr[$name])) {
			$this->_comp_arr[$name]=$val;
		}
		else {
			$this->_comp_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update marketplace_company set ?u where id=?i";
            $db->query($sql,$this->_comp_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_comp_arr,true).",".$this->id."); comp_rekvizits=".print_r($this->_comp_rekvizits,true);
            if ($db->affectedRows()>0) {
          		// $db->query("insert ignore into company_rekvizits set ?u,company_id=?i,user_id=?i,main_company=?i",$this->_comp_rekvizits,$this->id,(int)$_SESSION['user_id'],(int)$_SESSION['main_company']);
          		return 1;
	          }
      	    else {
          		// $db->query("insert ignore into company_rekvizits set ?u,company_id=?i,user_id=?i,main_company=?i",$this->_comp_rekvizits,$this->id,(int)$_SESSION['user_id'],(int)$_SESSION['main_company']);
          		if ($db->affectedRows()>0) return 1;
          		else return 0;
      	    }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into marketplace_company set ?u";
            $db->query($sql,$this->_comp_arr);
            if ($db->affectedRows()>0) {
		           $this->id=$db->insertId();
	          }
            else {
              $this->id=$db->getOne("select id from marketplace_company where email Like ?s",$this->email);
            }
	        //   $db->query("insert ignore into company_rekvizits set ?u,company_id=?i,main_company=?i,user_id=?i",$this->_comp_rekvizits,$this->id,(int)$_SESSION['main_company'],(int)$_SESSION['user_id']);
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }
}
?>
