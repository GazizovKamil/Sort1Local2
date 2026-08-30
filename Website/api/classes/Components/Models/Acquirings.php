<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Acquiring;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Acquirings extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        
        public static function save_acquiring($request) {
            $db = DB::getInstance();
            if(empty($request->acquiring_operator_id) || (int)$request->acquiring_operator_id<1) {
                return self::_error_arr("Не указан банк эквайринга");
            }
            $acquiring=new Acquiring($request->acquiring_operator_id,$request->acquiring_id);
            $is_exist=$db->getOne("select id from acquiring_config where sklad_id=?i and active=1 and company_id=?i",$request->sklad_id,$_SESSION['main_company']);
            if($is_exist && $request->acquiring_id!=$is_exist && isset($request->active) && $request->active==true) return self::_error_arr("На данный момент можно привязать к складу только одну активную кассу");
            if(!empty($request->config_name)) $acquiring->config_name=$request->config_name;
            if(!empty($request->sklad_id)) $acquiring->sklad_id=$request->sklad_id;
            if(isset($request->active) && $request->active==true) $acquiring->active=1;
            else $acquiring->active=0;
			if(isset($request->test) && $request->test==true) $acquiring->test=1;
            else $acquiring->test=0;
            if(!empty($request->acquiring_config)) $acquiring->acquiring_config=json_encode($request->acquiring_config);
            //echo print_r($acquiring,true); //die();
      	    $err=$acquiring->Save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
                case 1:  
                    $status="ok"; $msg=""; 
                    break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_acquiring_operators($request) {
	    $db = DB::getInstance();
		$sql="select * from acquiring_operators";
	    $res=$db->getAll($sql);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['acquiring_operator_config']=json_decode($ofd_val['acquiring_operator_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['acquiring_operators']=$res;
    		$ret['msg']="";
    	}
    	else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['acquiring_operators']=array();
        }
        //$ret['sklads']=$db->getAll("select id,name from sklad where company_id=?i",$_SESSION['main_company']);
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

    public static function get_acquirings($request) {
	    $db = DB::getInstance();
        $sql="select ac.*,ao.name as acquiring_operator_name,ao.acquiring_operator_icon,s.name as sklad_name,c.name as org_name,c.inn as org_inn from acquiring_config ac 
            left join acquiring_operators ao on (ac.acquiring_operator_id=ao.id) 
            left join sklad s on (s.id=ac.sklad_id)
			left join company c on (c.id=ac.company_id)
            where ac.company_id=?i";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['acquiring_config']=json_decode($ofd_val['acquiring_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['acquirings']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['acquirings']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}



?>
