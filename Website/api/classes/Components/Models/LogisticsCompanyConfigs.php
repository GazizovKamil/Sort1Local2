<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\LogisticsCompanyConfig;
use Sort1API\Components\DostavistaApi;
use Sort1API\Components\Models\CdekApi;
use Sort1API\Components\DostavistaZakaz;
use Sort1API\Components\Config;
use Sort1API\Components\SafeMySql;
use Sort1API\Components\Basket;

require 'vendor/autoload.php';

class LogisticsCompanyConfigs extends Model{

    public static function get_logistics($request) {
	    $db = DB::getInstance();
		$sql="select * from logistics";
	    $res=$db->getAll($sql);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['logistic_config']=json_decode($ofd_val['logistic_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['logistics']=$res;
    		$ret['msg']="";
    	}
    	else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['logistics']=array();
        }
        //$ret['sklads']=$db->getAll("select id,name from sklad where company_id=?i",$_SESSION['main_company']);
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

    public static function save_logistic_config($request) {
        $db = DB::getInstance();
        if(empty($request->logistic_id) || (int)$request->logistic_id<1) {
            return self::_error_arr("Не указана логистичекая компания");
        }
        $logistic=new LogisticsCompanyConfig($request->logistic_id,$request->logistic_config_id);
        if(!empty($request->main_company_id)) $logistic->company_id = $request->main_company_id;
        if(!empty($request->config_name)) $logistic->config_name=$request->config_name;
        if(!empty($request->logistic_config)) $logistic->logistics_config=json_encode($request->logistic_config);
        if(isset($request->active) && $request->active==true) $logistic->active=1;
        else $logistic->active=0;
        // $logistic->tested=0;
          $err=$logistic->Save();
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

    public static function get_logistic_config($request) {
	    $db = DB::getInstance();
        $sql="select ac.*,ao.name as logistic_name,c.name as org_name,c.inn as org_inn, c2.name as name_main_company_id from logistics_company_configs ac 
        left join logistics ao on (ac.logistics_id=ao.id) 
        left join company c on (c.id=ac.main_company_id)
        left join company c2 on (c2.id=ac.company_id)
        where ac.main_company_id=?i";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['logistics_config']=json_decode($ofd_val['logistics_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['logistic_config']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['logistic_config']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

    public static function check_test_config($request){
        $db = DB::getInstance();
        $logistic = new LogisticsCompanyConfig(0,$request->logistic_index);
        // $res = false;

        if (isset($request->logistic_id)){
            if ((int)$logistic->logistics_id==1 && isset($request->logistic_index)){
                $res = DostavistaApi::check_test_config($request);
            }
            if ((int)$logistic->logistics_id==2 && isset($request->logistic_index)){
                $res = CdekApi::check_test_config($request);
            }
        }
        
        if ($res === true){
            $ret['status']="ok";
    		$ret['err']="";
    		$ret['msg']="Протестирован удачно";
            $logistic->tested=1;
            $logistic->Save();
        }else{
            $ret['status']="ok";
    		$ret['msg']=$res;
            $logistic->tested=0;
            $logistic->Save();
        }
        return $ret;
    }

    public static function calculate_shipping_cost_Dostavista($request){
        $res = DostavistaApi::shipping_cost_calculation($request);
        $ret['status']="ok";
        $ret['err']="";
        $ret['payment_amount']=json_decode($res,true)['order']['payment_amount'];
        $ret['msg']="";
        return $ret;
    }

    public static function get_name_logistic_config($request){
        $db = DB::getInstance();
        $sql = "SELECT lc.id, lc.config_name, lc.logistics_id FROM logistics_company_configs lc Where lc.user_id = ?i";
        $res = $db->getAll($sql,$_SESSION['user_id']);
        if(count((array)$res)>0){
            $ret['status']="ok";
    		$ret['err']="";
    		$ret['logistic_configs']=$res;
    		$ret['msg']="";
        }
        else{
            $ret['status']="err";
    		$ret['err']="Нет конфига достависты";
    		$ret['msg']="Нет конфига достависты";
        }
        return $ret;
    }

    public static function save_order_logistic($request){
        $db = DB::getInstance();
        $order = new DostavistaZakaz(0);
        if (isset($request->zakaz_id_in_sort1)) $order->zakaz_id_in_sort1=$request->zakaz_id_in_sort1;
        if (isset($request->addressDelivery)) $order->delivery_to_address=$request->addressDelivery;
        if (isset($request->addressSklad)) $order->delivery_from_address=$request->addressSklad;
        if (isset($request->comment)) $order->comment=$request->comment;
        if (isset($request->coordinatesDelivery)) $order->delivery_to_latitude = $request->coordinatesDelivery[0];$order->delivery_to_longitude = $request->coordinatesDelivery[1];
        if (isset($request->coordinatesSklad)) $order->delivery_from_latitude = $request->coordinatesSklad[0];$order->delivery_from_longitude = $request->coordinatesSklad[1];
        if (isset($request->delivery_type)) $order->delivery_type=$request->delivery_type;
        if (isset($request->is_client_notification_enabled)) $order->is_client_notification_enabled=$request->is_client_notification_enabled ? 1 : 0;
        if (isset($request->is_contact_person_notification_enabled)) $order->is_contact_person_notification_enabled=$request->is_contact_person_notification_enabled ? 1 : 0;
        if (isset($request->loaders_count)) $order->loaders_count=$request->loaders_count;
        if (isset($request->total_weight_kg)) $order->total_weight_kg=$request->total_weight_kg;
        if (isset($request->numberphoneDelivery)) $order->numberphoneDelivery=$request->numberphoneDelivery;
        if (isset($request->nameDelivery)) $order->nameDelivery=$request->nameDelivery;
        $order->user_id=$_SESSION['user_id'];
        if (isset($request->logistic_config_id)){
            $order->logistic_config_id = $request->logistic_config_id;
            $sql = "SELECT lc.company_id FROM logistics_company_configs lc where lc.id = ?i";
            $order->company_id = $db->getOne($sql,$request->logistic_config_id);
        }
        $res = $order->Save();
        if ($res == 1){
            $ret['status']="ok";
    		$ret['msg']="";
        }else{
            $ret['status']="err";
    		$ret['msg']="Не удалось сохранить";
        }
        return $ret;
    }

    public static function send_order_logistic($request){
        $res = DostavistaApi::send_order($request);
        if (gettype($res) == 'boolean'){
            if ($res){
                $ret['status']="ok";
                $ret['err'] = "";
                $ret['msg']="";
            }else{
                $ret['status']="err";
                $ret['err'] = "Не удалось создать доставку";
                $ret['msg']="Не удалось создать доставку";
            }
        }else{
            $ret['status']="err";
            $ret['err'] = $res;
            $ret['msg']=$res;
        }
        return $ret;
    }

    public static function refresh_order_logistic_status($request){
        $res = DostavistaApi::refresh_status($request);
        if ($res) {
            $ret['status']="ok";
            $ret['err'] = "";
            $ret['msg']="";
        }
        return $ret;
    }

        // if($_SESSION['roles']==1){
		// 	$users=$db->getCol("select user_id from user_companys where company_id=?i",$_SESSION['main_company']);
		// 	$sql="select b.id as basket_id, u.id from users u left join basket b on (b.user_id = u.id) where u.id in (?b)";
		// 	$basket_users=$db->getCol($sql,$users);
		// 	if(!in_array($request->basket_id,$basket_users)){
		// 		return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
		// 	}
		// }
		// else {
		// 	if((int)$request->basket_id!=$basket->id)
		//      return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
		// }
    public static function get_details_weights($request){
        $db1 = DB::getInstance("libr");
        $db = new SafeMySQL(Config::get_section('mysql-', true));
        $basket=new Basket();
    
        $sql_details="SELECT detail_id, article, brand FROM basket_details WHERE basket_id=?i";
        $details = $db->getAll($sql_details, $basket->id);
    
        $res = [];
    
        foreach($details as $detail){
            $sql_weight = "SELECT value FROM details_info WHERE detail_id=?i AND name='weight'";
            $weight = $db1->getOne($sql_weight, $detail['detail_id']);
    
            $res[] = ['detail_id' => $detail['detail_id'],'article' => $detail['article'],'brand' => $detail['brand'], 'weight' => ($weight && $weight != "") ? $weight : ""];
        }
        
        $ret = [];
    
        $ret['status'] = "ok";
        $ret['err'] = "";
        $ret['parameters'] = $res;
        $ret['msg'] = "";
    
        return $ret;
    }
}
?>