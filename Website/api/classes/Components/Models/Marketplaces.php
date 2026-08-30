<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Marketplace;
use Sort1API\Components\ZzapApi;
use Sort1API\Components\AvitoApi;
use Sort1API\Components\MarketZakaz;
use Sort1API\Components\MarketZakazDetail;
use Sort1API\Components\MarketplaceCompany;
use Sort1API\Components\Zakaz;
use Sort1API\Components\Models\Companys;
use Sort1API\Components\Models\Zakazs;

require 'vendor/autoload.php';

use \XMLReader;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Marketplaces extends Model {

        public static function check_roles($role){
			$main_user=new User((int)$_SESSION['user_id']);
			if ($main_user->roles<=$role) return $role;
			else return $main_user->roles;
		}
        
        public static function save_marketplace_config($request) {
            $db = DB::getInstance();
            if(empty($request->marketplace_id) || (int)$request->marketplace_id<1) {
                return self::_error_arr("Не указан маркетплейс");
            }
            $marketplace=new Marketplace($request->marketplace_id,$request->marketplace_config_id);
	
            if(!empty($request->config_name)) $marketplace->config_name=$request->config_name;
            if(!empty($request->marketplace_config)) $marketplace->marketplace_config=json_encode($request->marketplace_config);
			if(isset($request->active) && $request->active==true) $marketplace->active=1;
            else $marketplace->active=0;
			$marketplace->tested=0;
			if(isset($request->marketing_channel_id)) {
				$marketplace->marketing_channel_id=(int)$request->marketing_channel_id;
				//echo $marketplace->marketing_channel_id."=".(int)$request->marketing_channel_id."\n";
			}
			//print_r($marketplace);
            //echo print_r($acquiring,true); //die();
      	    $err=$marketplace->Save();
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


	public static function get_marketplaces($request) {
	    $db = DB::getInstance();
		$sql="select * from marketplaces";
	    $res=$db->getAll($sql);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['marketplace_config']=json_decode($ofd_val['marketplace_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['marketplaces']=$res;
    		$ret['msg']="";
    	}
    	else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['marketplaces']=array();
        }
        //$ret['sklads']=$db->getAll("select id,name from sklad where company_id=?i",$_SESSION['main_company']);
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

    public static function get_marketplace_config($request) {
	    $db = DB::getInstance();
        $sql="select ac.*,ao.name as marketplace_name,c.name as org_name,c.inn as org_inn, ac.marketing_channel_id from marketplaces_configs ac 
            left join marketplaces ao on (ac.marketplace_id=ao.id) 
			left join company c on (c.id=ac.company_id)
            where ac.company_id=?i";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
            foreach($res as $ofdkey=>$ofd_val){
                $res[$ofdkey]['marketplace_config']=json_decode($ofd_val['marketplace_config'],true);
            }
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['marketplace_config']=$res;
    		$ret['msg']="";
    	    }
    	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['marketplace_config']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function check_marketplace_config($request) {
	    $db = DB::getInstance();
		if(empty($request->marketplace_id) || (int)$request->marketplace_id<1) {
			return self::_error_arr("Не указан маркетплейс");
		}
		$marketplace=new Marketplace($request->marketplace_id,$request->marketplace_config_id);

		if((int)$request->marketplace_id == 1)
		{
			$marketplace_config_id = $request->marketplace_config_id;
			$res = ZzapApi::get_order_status($marketplace_config_id);
	
			if (empty($res['error'])){
				$ret['status']="ok";
				$ret['err']=$res;
				$ret['msg']="Протестирован";
				$marketplace->tested = 1;
			}
			else {
				$ret['status']="ok";
				$ret['msg']= $res['error'];
				$marketplace->tested = 0;
			}
		}
		else if((int)$request->marketplace_id == 2)
		{
			$marketplace_config_id = $request->marketplace_config_id;
			$res = AvitoApi::get_avito_token($marketplace_config_id);
			if($res != null){
				$ret['status']="ok";
				$ret['err']="";
				$ret['msg']="Протестирован";
				$marketplace->tested = 1;
			}
			else {
				$ret['status']="err";
				$ret['err']= $res;
				$ret['msg']= $res;
				$marketplace->tested = 0;
			}
		}
		$marketplace->Save();

	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_seller_orders($request) {
	    $db = DB::getInstance();
		// if(empty($request->marketplace_id) || (int)$request->marketplace_id<1) {
		// 	return self::_error_arr("Не указан маркетплейс");
		// }
		$marketplaces_configs_id = (int)$request->marketplaces_configs_id;

		$marketplace_id = $db->getRow('select m.marketplace_id as id, m.marketing_channel_id, mc.name from marketplaces_configs m left join marketing_channels mc on (mc.id = m.marketing_channel_id) where m.id=?i', $marketplaces_configs_id);
		if($marketplace_id['id'] == 1)
		{ 
			$res = ZzapApi::get_seller_orders($request);

			for ($i = 0; $i < $res['row_count']; $i++) {
				$market_zakaz = new MarketZakaz($res['table'][$i]->code_order);

				$client = new MarketplaceCompany($res['table'][$i]->client_email);
				
				if($client->id>0){
					$check_bind = $db->getRow("Select * from marketplace_user_companys where main_company_id=?i and company_id=?i",$_SESSION['main_company'],$client->id);
					if($check_bind){
						$market_zakaz->company_id = $client->id;
					}
					else{
						$db->query("insert ignore into marketplace_user_companys SET user_id=?i,main_company_id=?i,company_id=?i,btype=1, marketplace_id=1, deleted=0",$_SESSION['user_id'],$_SESSION['main_company'],$client->id);
						$market_zakaz->company_id = $client->id;
					}
				}
				else {
					$client->name = $res['table'][$i]->client_name;
					$client->mphone = $res['table'][$i]->client_phone;
					$client->email = $res['table'][$i]->client_email;
					$client->Save();

					$market_zakaz->company_id = $client->id;
					$db->query("insert ignore into marketplace_user_companys SET user_id=?i,main_company_id=?i,company_id=?i,btype=1, marketplace_id=1, deleted=0",$_SESSION['user_id'],$_SESSION['main_company'],$client->id);
					//echo "insert ignore into marketplace_user_companys SET user_id=?i,main_company_id=?i,company_id=?i, marketplace_id=1, deleted=0 ".$_SESSION['user_id'].",".$_SESSION['main_company'].",".$client->id."\n";
				}

				$market_zakaz->zakaz_id_in_marketplace = $res['table'][$i]->code_order;
				$market_zakaz->marketplaces_config_id = $marketplaces_configs_id;
				$market_zakaz->comment = $res['table'][$i]->message;
				$market_zakaz->create_date = date('Y-m-d H:i:s', strtotime($res['table'][$i]->create_date));
				$market_zakaz->pozition_count = 1;
				$market_zakaz->zakaz_sum = $res['table'][$i]->priceV2*$res['table'][$i]->qty_order;
				$market_zakaz->status = $res['table'][$i]->code_track;
				$market_zakaz->marketing_channel_id = $marketplace_id['marketing_channel_id'];
				$market_zakaz->marketing_channel_name = $marketplace_id['name'];
				// print_r($market_zakaz);
				$market_zakaz->Save();

				$market_zakaz_details = new MarketZakazDetail($market_zakaz->id);

				$market_zakaz_details->market_zakaz_id = $market_zakaz->id;
				$market_zakaz_details->time = $res['table'][$i]->delivery_days;
				$market_zakaz_details->market_zakaz_detail_id = $res['table'][$i]->code_order;
				$market_zakaz_details->article = $res['table'][$i]->partnumber;
				$market_zakaz_details->brand = $res['table'][$i]->class_man;
				$market_zakaz_details->name = $res['table'][$i]->class_cat;
				$market_zakaz_details->count = $res['table'][$i]->qty_order;
				$market_zakaz_details->price = $res['table'][$i]->priceV2;
				$market_zakaz_details->status = $res['table'][$i]->code_track;
				$market_zakaz_details->create_date = date('Y-m-d H:i:s', strtotime($res['table'][$i]->create_date));
				// print_r($market_zakaz_details);
				$market_zakaz_details->Save();
			}
		}
		else if($marketplace_id['id'] == 2){
			$res = AvitoApi::get_seller_orders($request);

			if($res['status'] === "ok" && !empty($res['orders']))
			{
				foreach ($res['orders'] as $order) {
					$market_zakaz = new MarketZakaz($order['id']);
					
					$chatId = $order['items'][0]['chatId'];
	
					$client = AvitoApi::get_or_create_client($chatId, $marketplaces_configs_id);
					$client_id = $client->id;
	
					$market_zakaz->company_id = $client_id;
					$market_zakaz->zakaz_id_in_marketplace = (string)$order['id'];
					$market_zakaz->marketplaces_config_id = $marketplaces_configs_id;
					$market_zakaz->comment = "Заказ №".$order['marketplaceId'];
					$market_zakaz->create_date = date('Y-m-d H:i:s', strtotime($order['createdAt']));
					$market_zakaz->pozition_count = count($order['items']);
					$market_zakaz->zakaz_sum = $order['prices']['total'];
					$status =  $db->getOne("SELECT market_status_id FROM market_zakaz_statuses WHERE client_descr = ?s",$order['status']);
					$market_zakaz->status = $status;
					$market_zakaz->chat_id = $chatId;
					$market_zakaz->marketing_channel_id = $marketplace_id['marketing_channel_id'];
					$market_zakaz->marketing_channel_name = $marketplace_id['name'];
					$market_zakaz->Save();
	
					foreach ($order['items'] as $item) {

						$parts = explode("_", $encoded_Id);
						$type = $parts[0]; // 1 или 2
						$first_id = $parts[1]; // либо price_list_id, либо sklad_id
						$detail_id = $parts[2]; // detail_id

						if ($type == "2") {
							$price_list_id = $first_id;
							$sklad_id = null;

							$detail = $db->getRow("SELECT * FROM price_list_details WHERE price_list_id = ?i AND detail_id = ?i", $price_list_id, (int)$detail_id);
						} else {
							$price_list_id = null;
							$sklad_id = $first_id;
 
							$detail = $db->getRow("SELECT * FROM sklad_details WHERE sklad_id = ?i AND detail_id = ?i", $sklad_id, $detail_id);					
						}

						$market_zakaz_details = new MarketZakazDetail();
						
						$market_zakaz_details->market_zakaz_id = $market_zakaz->id;
						$market_zakaz_details->time = 0;
						$market_zakaz_details->market_zakaz_detail_id = $item['avitoId'];
						$market_zakaz_details->name = $item['title'];
						$market_zakaz_details->count = $item['count'];

						$market_zakaz_details->article = $detail['article'];
						$market_zakaz_details->brand = $detail['brand'];
						$market_zakaz_details->price = $item['prices']['price'];
						$market_zakaz_details->status = $status;
						$market_zakaz_details->create_date = date('Y-m-d H:i:s', strtotime($order['createdAt']));
						$market_zakaz_details->Save();
					}
				}
			}
			else {
				file_put_contents('/var/log/sort1/avito.log', "Error fetching orders: " . json_encode($res) . "\n", FILE_APPEND);
			}
		}		
		
		// echo "res=".print_r($res)."\n";
	    if (!empty($res['table']) || !empty($res['orders'])){
    		$ret['status']="ok";
    		$ret['msg']= "";
    	}
    	else {
    		$ret['status']="err";
    		$ret['msg']= "Заказов нет";
		}

	    if ($ret['status']=="err") return self::_error_arr($res['err']);
	    else return $ret;
	}

	public static function create_zakaz_sort1($request) {
		$db = DB::getInstance();
		$logFile = "/var/log/sort1/avito.log";
	
		// Очистка файла перед записью
		file_put_contents($logFile, "");
	
		// Логирование входящего запроса
		file_put_contents($logFile, "zzap request: " . print_r($request, true) . "\n", FILE_APPEND);
	
		if (!isset($request->company_id)) return self::_error_arr("Компания не установлена");
		if (!isset($request->zakaz_id_in_marketplace)) return self::_error_arr("Заказ не выбран");
		if (!isset($request->marketplace_config_id)) return self::_error_arr("Не установлен канал продаж");
	
		$company_id = $request->company_id;
		$zakaz_id_in_marketplace = (string)$request->zakaz_id_in_marketplace;
		$marketplace_config_id = $request->marketplace_config_id;
	
		file_put_contents($logFile, "Step 1: Initial validation passed\n", FILE_APPEND);
	
		$marketplace_config = $db->getRow(
			"SELECT m.name, m.id,marketplace_id FROM marketplaces_configs mc 
			LEFT JOIN marketing_channels m ON (m.id = mc.marketing_channel_id) 
			WHERE mc.company_id = ?i AND mc.id = ?i",
			$_SESSION['main_company'], $marketplace_config_id
		);
	
		$marketplace_user_companys = $db->getRow(
			"SELECT * FROM marketplace_user_companys 
			WHERE main_company_id = ?i AND company_id = ?i",
			$_SESSION['main_company'], $company_id
		);
	
		if (empty($marketplace_user_companys)) return self::_error_arr("Компания не прикреплена к вам");
	
		file_put_contents($logFile, "Step 2: Company validation passed\n", FILE_APPEND);
	
		$marketplace_company_email = $db->getRow(
			"SELECT email FROM marketplace_company WHERE id=?i",
			$company_id
		);
	
		$marketplace_company = new MarketplaceCompany($marketplace_company_email['email']);
		$marketplace_company->company_name = $marketplace_company->name;
		$marketplace_company->okopf = 3;
	
		file_put_contents($logFile, "Step 3: Marketplace company object created: " . print_r($marketplace_company, true) . "\n", FILE_APPEND);
	
		$market_zakaz = new MarketZakaz($zakaz_id_in_marketplace);
		$save_zakaz = clone $market_zakaz;
		$save_zakaz->marketing_channel_id = $marketplace_config['id'];
		$save_zakaz->marketing_channel_name = $marketplace_config['name'];
		$save_zakaz->comment = "Заказ из " . $marketplace_config['name'];
		$save_zakaz->user_id = $_SESSION['user_id'];
		$save_zakaz->status = 1;
		$save_zakaz->details = " ";
	
		file_put_contents($logFile, "Step 4: Order object prepared: " . print_r($db->getStats(), true) . "\n", FILE_APPEND);
	
		$check_company_in_sort1_email = null;
		$check_company_in_sort1_phone = null;

		if (!empty($marketplace_company->email)) {
			$check_company_in_sort1_email = $db->getOne(
				"SELECT id AS company_id, name FROM company WHERE email LIKE ?s",
				$marketplace_company->email
			);
		}
		
		if (!empty($marketplace_company->mphone)) {
			$clean_phone = preg_replace("/\D+/", "", $marketplace_company->mphone);
			if (!empty($clean_phone)) {
				$check_company_in_sort1_phone = $db->getOne(
					"SELECT id AS company_id, name FROM company WHERE mphone LIKE ?s",
					$clean_phone
				);
			}
		}
		file_put_contents($logFile, "Step 4: Order object prepared: " . print_r($check_company_in_sort1_phone, true) . "\n". print_r($check_company_in_sort1_email, true) , FILE_APPEND);

		$comp_id = (object) array();
		if ($check_company_in_sort1_phone === null && count((array)$check_company_in_sort1_email) > 0) {
			$comp_id->company_id = $check_company_in_sort1_email;
		} elseif ($check_company_in_sort1_email === null && count((array)$check_company_in_sort1_phone) > 0) {
			$comp_id->company_id = $check_company_in_sort1_phone;
		} elseif (count((array)$check_company_in_sort1_email) > 0 && count((array)$check_company_in_sort1_phone) > 0) {
			$comp_id->company_id = $check_company_in_sort1_email;
		}

		if((int)$marketplace_config['marketplace_id'] == 1)
		{
			file_put_contents($logFile, "Step 5: Company check completed: " . print_r($comp_id, true) . "\n", FILE_APPEND);

			if (empty($comp_id->company_id)) {
				$company = Companys::save_company($marketplace_company);
				$comp_id->company_id = $company['company_id'];
				$save_zakaz->__set("company_id", $company['company_id']);
			} else {
				$company = Companys::save_company($comp_id);
				$save_zakaz->company_id = $comp_id->company_id;
			}

			$check_user_companys = $db->getRow(
				"SELECT * FROM user_companys 
				WHERE user_id=?i AND main_company_id=?i AND company_id=?i",
				$_SESSION['user_id'], $_SESSION['main_company'], $comp_id->company_id
			);
		
			if (empty($check_user_companys)) {
				
				$db->query(
					"INSERT IGNORE INTO user_companys 
					SET user_id=?i, main_company_id=?i, company_id=?i",
					$_SESSION['user_id'], $_SESSION['main_company'], $comp_id->company_id
				);
			}
			file_put_contents($logFile, "Step 6: User-company relation checked\n", FILE_APPEND);

		}
		elseif ((int)$marketplace_config['marketplace_id'] == 2){
			$comp_id->company_id = 471;
			$save_zakaz->company_id = $comp_id->company_id;

			$check_user_companys = $db->getRow(
				"SELECT * FROM user_companys 
				WHERE user_id=?i AND main_company_id=?i AND company_id=?i",
				$_SESSION['user_id'], $_SESSION['main_company'], $comp_id->company_id
			);
		
			if (empty($check_user_companys)) {
				
				$db->query(
					"INSERT IGNORE INTO user_companys 
					SET user_id=?i, main_company_id=?i, company_id=?i",
					$_SESSION['user_id'], $_SESSION['main_company'], $comp_id->company_id
				);
			}
		}
		else{
			$ret = [
				'status' => "err",
				'msg' => "Ошибка при создания заказа",
			];
			return $ret;
		}
		$res = Zakazs::save_zakaz($save_zakaz);
	
		file_put_contents($logFile, "Step 7: Order saved with result: " . print_r($res, true) . "\n", FILE_APPEND);
	
		if ($res['status'] == "ok") {
			$ret = [
				'status' => "ok",
				'msg' => "",
				'zakaz_id' => $res['zakaz_id']
			];
			$db->query(
				"UPDATE market_zakaz 
				SET zakaz_id_in_sort1=?i 
				WHERE id=?i",
				$res['zakaz_id'], $market_zakaz->id
			);
		} else {
			$ret = [
				'status' => "err",
				'msg' => $res['err']
			];
		}
	
		file_put_contents($logFile, "zzap_return: " . print_r($ret, true) . "\n", FILE_APPEND);
	
		return $ret;
	}
	

	public static function set_order_status($request) {
		$db = DB::getInstance();

		$detail_id = (int)$request->detail_id;
		if(isset($request->zakaz_id_in_marketplace) && (int)$request->zakaz_id_in_marketplace>0){
			$zakaz_id=$request->zakaz_id_in_marketplace;
		}
		else {
			$zakaz_id = $db->getOne('select market_zakaz_detail_id from market_zakaz_details where sort1_detail_id=?i',$detail_id);
		}
		$marketplaces_config = $db->getOne('Select marketplaces_config_id from market_zakaz where zakaz_id_in_marketplace =?i', (int)$zakaz_id);
		$marketplace = $db->getOne("Select marketplace_id from marketplaces_configs where id=?i", (int)$marketplaces_config);

		$req = (object)[
			'zakaz_id' => $zakaz_id,
			'status' => $request->status, 
			'comment' => $request->comment, 
			'marketplace_config_id' => $marketplaces_config, 
		];
		// file_put_contents("/var/log/sort1/zzap_status.log","zzap request: ".print_r($req,true)."\n zzap_return:".print_r($zzap_result,true)."\n",FILE_APPEND);
		if((int)$marketplace == 1){
			$zzap_result =  ZzapApi::set_order_status($req);
			file_put_contents("/var/log/sort1/zzap_status.log","marketplace=1, zzap request: ".print_r($req,true)."\n zzap_return:".print_r($zzap_result,true)."\n",FILE_APPEND);
			return $zzap_result;
		}
		elseif((int)$marketplace == 2){
			$avito_result =  AvitoApi::set_order_status($req);
			file_put_contents("/var/log/sort1/zzap_status.log","marketplace=1, zzap request: ".print_r($req,true)."\n zzap_return:".print_r($zzap_result,true)."\n",FILE_APPEND);
			return $avito_result;
		}

		return $req;
	}

	public static function check_zakaz_in_sort1($request) {
		$db = DB::getInstance();

		if(isset($request->zakaz_id_in_marketplace)) $market_zakaz = $request->zakaz_id_in_marketplace;
		else {
			$ret['status']="err";
    		$ret['err']= "Выберите заказ";
		};

		$check_in_sort1 = $db->getOne("select zakaz_id_in_sort1 from market_zakaz where zakaz_id_in_marketplace = ?i", $market_zakaz);
		if(!empty($check_in_sort1)){
			$ret['status']="err";
    		$ret['err']= "Заказ в Sort1 уже создан";
		}
		else{
			$zakaz_id_in_sort1 = $db->getOne('select z.id from market_zakaz mz 
			left join zakaz z on (z.id = mz.zakaz_id_in_sort1)
			where mz.company_id = (SELECT company_id from market_zakaz where zakaz_id_in_marketplace = ?i) and z.status < 70 and mz.main_company_id = ?i', $market_zakaz, $_SESSION['main_company']);

			if(!empty($zakaz_id_in_sort1)){
				$ret['status']="ok";
				$ret['msg']= "";
				$ret['zakaz_id']= $zakaz_id_in_sort1;
			}
			else{
				$ret['status']="ok";
				$ret['msg']= "";
				$ret['zakaz_id']= "";
			}
		}
		if ($res['status']=="err") return self::_error_arr($res['err']);
	    else return $ret;
	}

	public static function bind_market_to_sort1_zakaz($request){
		$db = DB::getInstance();

		if(isset($request->market_zakaz_id)) $market_zakaz_id = $request->market_zakaz_id;
		if(isset($request->zakaz_id)) $sort1_zakaz_id = $request->zakaz_id;
		if(isset($request->zakaz_detail_id)) $zakaz_detail_id = $request->zakaz_detail_id;

		if(isset($market_zakaz_id) && isset($sort1_zakaz_id)) {
			$bind_zakaz = $db->query("update market_zakaz set zakaz_id_in_sort1=?i where zakaz_id_in_marketplace=?i",(int)$sort1_zakaz_id, (int)$market_zakaz_id);
		}
		if(isset($market_zakaz_id) && isset($zakaz_detail_id)) {
			$bind_detail = $db->query("update market_zakaz_details set sort1_detail_id=?i where market_zakaz_detail_id=?i",(int)$zakaz_detail_id, (int)$market_zakaz_id);
		}

		if ($db->affectedRows()>0){ 
			$ret['status']="ok";
			$ret['zakaz_id']= $sort1_zakaz_id;
		}
	    else { 
			$ret['status']="ok";
			$ret['msg']= "";
		}

		if ($res['status']=="err") return self::_error_arr($res['err']);
	    else return $ret;
	}

	public static function get_all_chat_messages_market($request){
		$db = DB::getInstance();

		$marketplaces_configs_id = (int)$request->marketplaces_configs_id;
		$chat_id = $request->chat_id;
		$zakaz_id = $request->zakaz_id;

		$marketplace_id = $db->getRow('select m.marketplace_id as id, m.marketing_channel_id, mc.name from marketplaces_configs m left join marketing_channels mc on (mc.id = m.marketing_channel_id) where m.id=?i', $marketplaces_configs_id);
		if((int)$marketplace_id['id'] == 2)
		{ 
			$client = AvitoApi::get_or_create_client($chat_id, $marketplaces_configs_id);
			$market_zakaz = new MarketZakaz($zakaz_id);
			$client_id = $client->id;

			$market_zakaz->company_id = $client_id;
			$market_zakaz->Save();

			$avito_data = AvitoApi::get_avito_token($marketplaces_configs_id);
			$res = AvitoApi::get_all_chat_messages($chat_id, $marketplace_id['id'],$avito_data['user_id'], $avito_data['access_token']);
			AvitoApi::mark_chat_as_read($chat_id, $avito_data['user_id'], $avito_data['access_token']);

			return $res;
		}
	}

	public static function send_message_market($request){
		$db = DB::getInstance();

		$marketplaces_configs_id = (int)$request->marketplaces_configs_id;
		$chat_id = $request->chat_id;
		$message = $request->message;

		$marketplace_id = $db->getRow('select m.marketplace_id as id, m.marketing_channel_id, mc.name from marketplaces_configs m left join marketing_channels mc on (mc.id = m.marketing_channel_id) where m.id=?i', $marketplaces_configs_id);
		if((int)$marketplace_id['id'] == 2)
		{ 
			$avito_data = AvitoApi::get_avito_token($marketplaces_configs_id);
			$res = AvitoApi::send_chat_message($chat_id, $avito_data['user_id'], $avito_data['access_token'], $message);

			return $res;
		}
	}
}
?>