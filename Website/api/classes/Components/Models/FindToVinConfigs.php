<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\FindToVinConfig;

require 'vendor/autoload.php';

class FindToVinConfigs extends Model{

    public static function get_ftv_id($request){
        $db = DB::getInstance();
        if (!isset($request->site_id)) return self::_error_arr("Не указан сайт!");
        $ftv_config_id = $db->getOne("select cs.find_to_vin_config_id from company_sites cs WHERE cs.id = ?i", $request->site_id);

        $ret['status']="ok";
        $ret['err']="";
        $ret['ftv_config_id']=$ftv_config_id;
        $ret['msg']="";

        return $ret;
    }

    public static function get_ftv_config($request){
        if(empty($request->ftv_config_id) || (int)$request->ftv_config_id<1) {
            return self::_error_arr("Не указан ftv_config_id");
        }
        $ftv=new FindToVinConfig(null,$request->ftv_config_id);
        return array("status"=>"ok", "msg"=>"", "err"=>"", "ftv_config"=>json_decode($ftv->find_to_vin_config));
    }

    public static function get_ftv_config_for_site($request){
        $db = DB::getInstance();
		$ret=array();

		preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);
		$sqlConfig = "SELECT id FROM company_sites WHERE site_name = ?s";
		$site_id = $db->getOne($sqlConfig, str_replace("www.", "", $origin[1]));

        $sqlConfig = 'SELECT * From find_to_vin_config ftvc where ftvc.id = (SELECT cs.find_to_vin_config_id From company_sites cs where cs.id = ?i)';
        $res = $db->getRow($sqlConfig, $site_id);
        $ret['status']="ok";
        $ret['err']="";
        $ret['ftv']=$res;
        $ret['msg']="";
        return $ret;
    }

    public static function get_ftv($request){
        $db = DB::getInstance();
        $sql="select * from find_to_vin";
	    $res=$db->getAll($sql);
        if (isset($request->ftv_config_id)){
            $ret['ftv_find_id']= $db->getOne("SELECT ftvc.find_to_vin_id FROM find_to_vin_config ftvc where ftvc.id = ?i", $request->ftv_config_id);
        }
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['find_to_vin_config']=json_decode($ofd_val['find_to_vin_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['ftv']=$res;
    		$ret['msg']="";
    	}
    	else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['ftv']=array();
        }
        //$ret['sklads']=$db->getAll("select id,name from sklad where company_id=?i",$_SESSION['main_company']);
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
    }

    public static function save_ftv_config($request) {
        $db = DB::getInstance();
        if(empty($request->find_to_vin_id) || (int)$request->find_to_vin_id<1) {
            return self::_error_arr("Не указан каталог");
        }
        if(empty($request->site_id) || (int)$request->site_id<1) {
            return self::_error_arr("Не указан сайт!");
        }
        $ftv=new FindToVinConfig($request->find_to_vin_id,$request->ftv_id);
        if(!empty($request->find_to_vin_config)) $ftv->find_to_vin_config=json_encode($request->find_to_vin_config);
        $err=$ftv->Save();
        switch($err) {
            case 10: $status="err"; $msg="Данные не изменились\n"; break;
        case 1:  
            $status="ok"; $msg=""; 
            $db->query('UPDATE company_sites cs set cs.find_to_vin_config_id = ?i where cs.id = ?i', $ftv->id, $request->site_id);
            break;
            default: $status="err"; $msg="error: ".$err."\n";
        }
        if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg);
    }
    
}
?>