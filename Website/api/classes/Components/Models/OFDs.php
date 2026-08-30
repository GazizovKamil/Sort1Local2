<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\OFD;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class OFDs extends Model {

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

        
        public static function save_ofd_kassa($request) {
            $db = DB::getInstance();
            if(empty($request->ofd_id) || (int)$request->ofd_id<1) {
                return self::_error_arr("Не указан оператор ОФД");
            }
            $ofd_kassa=new OFD($request->ofd_id,$request->ofd_kassa_id);
            $is_exist=$db->getOne("select id from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=?i",$request->sklad_id,(int)$request->user_id);
            if($is_exist && empty($request->ofd_kassa_id)) return self::_error_arr("Касса для данного склада и кассира уже существует");
            if(!empty($request->config_name)) $ofd_kassa->config_name=$request->config_name;
            if(!empty($request->sklad_id)) $ofd_kassa->sklad_id=$request->sklad_id;
			if(isset($request->registered_in_tax)) $ofd_kassa->registered_in_tax=(int)$request->registered_in_tax;
			if(!empty($request->kassa_ip_port)) $ofd_kassa->kassa_ip_port=$request->kassa_ip_port;
            if(!empty($request->ofd_config)) $ofd_kassa->kassa_config=json_encode($request->ofd_config);
			if(!empty($request->ofd_config['DontMakePaymentOnCloseShift']) && $request->ofd_config['DontMakePaymentOnCloseShift'] == true) {
				$ofd_kassa->dont_make_payment_in_close_shift = 1;
			}
			else{
				$ofd_kassa->dont_make_payment_in_close_shift = 0;
			}
			if(isset($request->user_id)) $ofd_kassa->user_id=(int)$request->user_id;
            //echo print_r($ofd_kassa,true); //die();
      	    $err=$ofd_kassa->Save();
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

	public static function save_shift_data($request){
		if(!isset($request->ofd_kassa_id)){
			return self::_error_arr("Не указана касса");
		}
		if(isset($request->ofd_kassa_id) && (int)$request->ofd_kassa_id>0){
			$ofd_kassa=new OFD(0,$request->ofd_kassa_id);
			$ofd_kassa->open_shift=(int)$request->open_shift;
			/*
			if((int)$request->open_shift==1) $ofd_kassa->user_id=$_SESSION['user_id'];
			else $ofd_kassa->user_id=0; 
			*/
			$saved=$ofd_kassa->save();
			if($saved){
				return array("status"=>"ok","msg"=>"");
			}
			else return array("status"=>"err","err"=>"Ошибка при открытии/закрытии кассы");
		}
	}

	public static function get_ofds($request) {
	    $db = DB::getInstance();
		$sql="select * from ofd_operators";
	    $res=$db->getAll($sql);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['ofd_config']=json_decode($ofd_val['ofd_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['ofds']=$res;
    		$ret['msg']="";
    	}
    	else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['ofds']=array();
        }
        //$ret['sklads']=$db->getAll("select id,name from sklad where company_id=?i",$_SESSION['main_company']);
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

    public static function get_ofd_kassas($request) {
	    $db = DB::getInstance();
		$users=$db->getInd("id","select id,name,lastname,middlename from users where id in (select user_id from user_companys where main_company_id=0 and company_id=?i and deleted=0)",$_SESSION['main_company']);
		$ret=array("users"=>$users);
		$sql="select okc.*,oo.name as ofd_operator_name,oo.ofd_icon,s.name as sklad_name,c.name as org_name,c.inn as org_inn from ofd_kassa_config okc 
            left join ofd_operators oo on (okc.ofd_operator_id=oo.id) 
            left join sklad s on (s.id=okc.sklad_id)
			left join company c on (c.id=okc.company_id)
            where okc.company_id=?i and okc.deleted=0";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['kassa_config']=json_decode($ofd_val['kassa_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['kassas']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok"; 
    		$ret['msg']="";
    		$ret['kassas']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_active_kassas($request){
		$db = DB::getInstance();
		$users=$db->getInd("user_id","select id,name,lastname,middlename from users where id in (select user_id from user_companys where main_company_id=0 and company_id=?i and deleted=0)",$_SESSION['main_company']);
		$sql="select * from ofd_kassa_config where company_id=?i and registered_in_tax=1 and deleted=0 and user_id=?i";
		$res=$db->getAll($sql,$_SESSION['main_company'],$_SESSION['user_id']);
		if($res){
			foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['kassa_config']=json_decode($ofd_val['kassa_config'],true);
            }
			return array("status"=>"ok","kassas"=>$res,"msg"=>"","user_id"=>$_SESSION['user_id'],"users"=>$users);
		}
		else {
			$sql1="select * from ofd_kassa_config where company_id=?i and registered_in_tax=1 and deleted=0 and user_id=0";
			$res1=$db->getAll($sql1,$_SESSION['main_company']);
			if($res1){
				foreach($res1 as $ofdkey=>$ofd_val){
					$res1[$ofdkey]['kassa_config']=json_decode($ofd_val['kassa_config'],true);
				}
				return array("status"=>"ok","kassas"=>$res1,"msg"=>"","user_id"=>$_SESSION['user_id'],"users"=>$users);
			}
			else {
				return array("status"=>"ok","msg"=>"","kassas"=>array(),"users"=>$users);
			}
		}
	}

	public static function delete_ofd_kassa($request){
		$db = DB::getInstance();
		$kassa_config=$db->getRow("select * from ofd_kassa_config where id=?i",(int)$request->kassa_id);
		if($kassa_config['company_id']!=$_SESSION['main_company']) return array("status"=>"err","err"=>"Не ваша касса");
		else {
			$res=$db->query("update ofd_kassa_config set deleted=1 where id=?i",(int)$request->kassa_id);
			if($res) return array("status"=>"ok","msg"=>"");
			else return array("status"=>"err","err"=>"Не удалось удалить кассу");
		}
	}

	public static function get_ofd_env($request){
		if(!isset($request->ofd_id)){
			return array("status"=>"err","err"=>"Не указан опертор фискальных данных");
		}
		if(!isset($request->conf_name)){
			return array("status"=>"err","err"=>"Не указан параметр");
		}
		if(empty($request->ofd_x_client_key)){
			return array("status"=>"err","err"=>"Не указан ключ доступа");
		}
		switch($request->ofd_id){
			case 3: // AQSI
				switch($request->conf_name){
					case "device": 
						return self::get_AQSI($request->ofd_x_client_key,"device");
						break;
					case "cashier": 
						return self::get_AQSI($request->ofd_x_client_key,"cashier");
						break;
					case "shop": 
						return self::get_AQSI($request->ofd_x_client_key,"shop");
						break;
				}
				break;
		}
	}

	private static function get_AQSI($key,$req){
		$url="https://api.aqsi.ru";
		switch($req){
			case "device":
				$req_url=$url."/pub/v1/Devices";
				$header=array("x-client-key: Application ".$key);
				$ch = curl_init($req_url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				//curl_setopt($ch, CURLOPT_POST, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				$result = curl_exec($ch);
				$res_str=json_decode($result);
				return array("status"=>"ok","res"=>$result,"key"=>$key,"req"=>$req,"url"=>$req_url,"curl_error"=>curl_error($ch),"res_str"=>$res_str);
				return $res_str;
				break;
			case "cashier":
				$req_url=$url."/pub/v2/Cashiers/list";
				$header=array("x-client-key: Application ".$key);
				$ch = curl_init($req_url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				//curl_setopt($ch, CURLOPT_POST, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				$result = curl_exec($ch);
				$res_str=json_decode($result);
				return array("status"=>"ok","res"=>$result,"key"=>$key,"req"=>$req,"url"=>$req_url,"curl_error"=>curl_error($ch),"res_str"=>$res_str);
				return $res_str;
				break;
			case "shop":
				$req_url=$url."/pub/v2/Shops/list";
				$header=array("x-client-key: Application ".$key);
				$ch = curl_init($req_url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				//curl_setopt($ch, CURLOPT_POST, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, "");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				$result = curl_exec($ch);
				$res_str=json_decode($result);
				return array("status"=>"ok","res"=>$result,"key"=>$key,"req"=>$req,"url"=>$req_url,"curl_error"=>curl_error($ch),"res_str"=>$res_str);
				return $res_str;
				break;
		}

	}

	public static function create_aqsi_order($request){
		if(count((array)$request->aqsi_data['details'])>0){
			switch((int)$request->aqsi_data['PaymentType']){
				case 1: $type=1;
					break;
				case 2: $type=1;
					break;
				case 3: $type=1;
					break;
				case 4: $type=1;
					break;
				case 5: $type=1;
					break;
				case 6: $type=1;
					break;
				case 7: 
					$type=1;
					break;
				default: $type=1;
			}
			$order=array( 
				"id"=>$request->aqsi_data['zakaz_data']['id'],
				"number"=>$request->aqsi_data['zakaz_data']['id'],
				"dateTime"=>str_replace(" ","T",$request->aqsi_data['zakaz_data']['create_date']).".0000000+03:00",
				"device"=>$request->aqsi_data['kassa_config']['device'],
				"shop"=>$request->aqsi_data['kassa_config']['shop'],
				"cashier"=>$request->aqsi_data['kassa_config']['cashier'],
				"comment"=>$request->aqsi_data['zakaz_data']['comment'],
				"clientId"=> null,
				"deliveryAddress"=> null,
				"pickAddress"=> null,
				"status"=> null,
				"cancellationReason"=> null,
				"content"=>array(
					"type"=>$type, //1 - Приход 2 -	Возврат прихода 3 - Расход 4 - Возврат расхода
					"positions"=>array(),
				),	
			);
			foreach($request->aqsi_data['details'] as $key => $detail){
				$order['content']['positions'][$key]=array(
					"quantity"=>$detail['count'],
					"price"=>$detail['price'],
					"text"=>mb_substr($detail['name'],0,60),
					"tax"=>$request->aqsi_data['TaxVariant'],
					"paymentSubjectType"=>1,
					"paymentMethodType"=>4,
				);
				if($request->aqsi_data['is_excise']=="1"){
					$order['content']['positions'][$key]['paymentSubjectType']=2;
				}
				if($request->aqsi_data['is_advance']=="1"){
					$order['content']['positions'][$key]['paymentMethodType']=3;
				}
			}
			//$kassa_config=json_decode($request->kassa_data->kassa_config);
			$key=$request->aqsi_data['kassa_config']['x_client_key'];
			$url="https://api.aqsi.ru";
			$req_url=$url."/pub/v2/Orders/simple";
			$header=array(
				"Content-Type: application/json",
				"x-client-key: Application ".$key
			);
			$ch = curl_init($req_url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			
			$result = curl_exec($ch);
			$info = curl_getinfo($ch);
			//print_r($result);
			//echo "order=".print_r($order)."\n";
			//$res_header=substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
		}
		if(count((array)$request->excise_aqsi_data['details'])>0){
			switch((int)$request->excise_aqsi_data['PaymentType']){
				case 1: $type=1;
					break;
				case 2: $type=1;
					break;
				case 3: $type=1;
					break;
				case 4: $type=1;
					break;
				case 5: $type=1;
					break;
				case 6: $type=1;
					break;
				case 7: 
					$type=1;
					break;
				default: $type=1;
			}
				$order_e=array( 
					"id"=>$request->aqsi_data['zakaz_data']['id']."A",
					"number"=>$request->aqsi_data['zakaz_data']['id']."A",
					"dateTime"=>str_replace(" ","T",$request->aqsi_data['zakaz_data']['create_date']).".0000000+03:00",
					"device"=>$request->excise_aqsi_data['kassa_config']['device'],
					"shop"=>$request->excise_aqsi_data['kassa_config']['shop'],
					"cashier"=>$request->excise_aqsi_data['kassa_config']['cashier'],
					"comment"=>$request->excise_aqsi_data['zakaz_data']['comment'],
					"clientId"=> null,
					"deliveryAddress"=> null,
					"pickAddress"=> null,
					"status"=> null,
					"cancellationReason"=> null,
					"content"=>array(
						"type"=>$type, //1 - Приход 2 -	Возврат прихода 3 - Расход 4 - Возврат расхода
						"positions"=>array(),
					),	
				);
			foreach($request->excise_aqsi_data['details'] as $key => $detail){
				$order_e['content']['positions'][]=array(
					"quantity"=>$detail['count'],
					"price"=>$detail['price'],
					"text"=>mb_substr($detail['name'],0,60),
					"tax"=>$request->excise_aqsi_data['TaxVariant'],
					"paymentSubjectType"=>1,
					"paymentMethodType"=>4,
				);
				if($request->excise_aqsi_data['is_excise']=="1"){
					$order_e['content']['positions'][count((array)$order_e['content']['positions'])-1]['paymentSubjectType']=2;
				}
				if($request->excise_aqsi_data['is_advance']=="1"){
					$order_e['content']['positions'][count((array)$order_e['content']['positions'])-1]['paymentMethodType']=3;
				}
			}
			//$kassa_config=json_decode($request->kassa_data->kassa_config);
			$key=$request->excise_aqsi_data['kassa_config']['x_client_key'];
			$url="https://api.aqsi.ru";
			$req_url=$url."/pub/v2/Orders/simple";
			$header=array(
				"Content-Type: application/json",
				"x-client-key: Application ".$key
			);
			$ch = curl_init($req_url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_e));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			
			$result_e = curl_exec($ch);
			$info_e = curl_getinfo($ch);
			//print_r($result);
			//echo "order=".print_r($order)."\n";
			//$res_header=substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE));
		}
		$res_str=json_decode($result);
		$res_str_e=json_decode($result_e);
		/*return array(
			"status"=>"ok",
			"req"=>$request,
			"order"=>$order,
			"order_e"=>$order_e,
			"res_str_e"=>$res_str_e,
			"result"=>$result,
			"result_e"=>$result_e,
			"info_e"=>$info_e,
			//"key"=>$key,
			//"url"=>$req_url,
			"curl_error"=>curl_error($ch),
			"res_str"=>$res_str,
			//"res_header"=>$res_header,
			//"info"=>$info
		);*/
		if(isset($res_str->guid) || isset($res_str_e->guid)){
			$db = DB::getInstance();
			$db->query("update payment set aqsi_guid=?s where id=?i",($res_str->guid===null?"null":$res_str->guid),(int)$request->payment_id);
			return array(
				"status"=>"ok",
				"req"=>$request,
				"order"=>$order,
				"order_e"=>$order_e,
				"res_str_e"=>$res_str_e,
				"result"=>$result,
				"result_e"=>$result_e,
				//"key"=>$key,
				//"url"=>$req_url,
				"curl_error"=>curl_error($ch),
				"res_str"=>$res_str,
				//"res_header"=>$res_header,
				//"info"=>$info
			);
		}
		else {
			if(isset($res_str->errors)){
				return array("status"=>"err","err"=>implode(", ",$res_str->errors),"order"=>$order);
			}
		}
	}

}



?>
