<?php

namespace Sort1API\Components\Controllers;

use Sort1API\Components\Models\Details;
use Sort1API\Components\Models\Crosses;
use Sort1API\Components\Models\Tooltips;
use Sort1API\Components\Models\Abcp;
use Sort1API\Components\Models\Companys;
use Sort1API\Components\Models\Services;
use Sort1API\Components\Models\Sklads;
use Sort1API\Components\Models\SkladDetails;
use Sort1API\Components\Models\SkladDetailLocations;
use Sort1API\Components\Models\SkladTopologys;
use Sort1API\Components\Models\FixPriceDetails;
use Sort1API\Components\Models\Documents;
use Sort1API\Components\Models\Dogovors;
use Sort1API\Components\Models\DocumentDetails;
use Sort1API\Components\Models\DocumentJobs;
use Sort1API\Components\Models\Users;
use Sort1API\Components\Models\PriceTypes;
use Sort1API\Components\Models\PriceLists;
use Sort1API\Components\Models\PriceListDetails;
use Sort1API\Components\Models\BasketDetails;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Models\Sort1s;
use Sort1API\Components\Models\TaxTypes;
use Sort1API\Components\Models\DeliveryAddresses;
use Sort1API\Components\Models\CompanyRekvizits;
use Sort1API\Components\Models\CompanyCars;
use Sort1API\Components\Models\Zakazs;
use Sort1API\Components\Models\MarketZakazs;
use Sort1API\Components\Models\MarketZakazDetails;
use Sort1API\Components\Models\ZakazDetails;
use Sort1API\Components\Models\ZakazJobs;
use Sort1API\Components\Models\Payments;
use Sort1API\Components\Models\Calendars;
use Sort1API\Components\Models\Citys;
use Sort1API\Components\Models\PriceTypeDifferencialValues;
use Sort1API\Components\Models\LogisticCars;
use Sort1API\Components\Models\LogisticDrivers;
use Sort1API\Components\Models\LogisticOrders;
use Sort1API\Components\Models\LogisticOrderDetails;
use Sort1API\Components\Models\DeliveryToWorkshops;
use Sort1API\Components\Models\DeliveryToWorkshopDetails;
use Sort1API\Components\Models\Reports;
use Sort1API\Components\Models\OnlineProfiles;
use Sort1API\Components\Models\SystemMessages;
use Sort1API\Components\Models\Invents;
use Sort1API\Components\Models\SkladPrices;
//use Sort1API\Components\Models\TempCart;
use Sort1API\Components\Auth;
use Sort1API\Components\Functions;
//use Sort1API\Components\User;
use Sort1API\Components\CompanyCar;
use Sort1API\Components\DB;
//use Sort1API\Components\UploadHandler;
use Sort1API\Components\Models\ExcelToBases;
//use Sort1API\Components\Config;
use Sort1API\Components\Models\Bugs;
use Sort1API\Components\Models\ServiceJobs;
use Sort1API\Components\Models\ServiceEmployees;
use Sort1API\Components\Models\ServiceWorkplaces;
use Sort1API\Components\Models\ServiceNotes;
use Sort1API\Components\Models\Proposals;
use Sort1API\Components\Models\GTDs;
use Sort1API\Components\Models\OFDs;
use Sort1API\Components\Models\Acquirings;
use Sort1API\Components\Models\Marketplaces;
use Sort1API\Components\Models\LogisticsCompanyConfigs;
use Sort1API\Components\Models\LocalCrosses;
use Sort1API\Components\Models\CashDesks;
use Sort1API\Components\Models\Encashments;
use Sort1API\Components\Models\RKOs;
use Sort1API\Components\Models\PKOs;
use Sort1API\Components\Models\MarketingChannels;
use Sort1API\Components\Models\ZakazFooters;
use Sort1API\Components\Models\ZakazGarants;
use Sort1API\Components\Models\DetailGroups;
use Sort1API\Components\Models\DetailCategorys;
use Sort1API\Components\Models\PriceExports;
use Sort1API\Components\Models\Catalogs;
use Sort1API\Components\Models\Timezones;
use Sort1API\Components\Models\Laximo;
use Sort1API\Components\Models\DiagnosticCards;
use Sort1API\Components\Models\FindToVinConfigs;
use Sort1API\Components\Models\FavoriteDetails;
use Sort1API\Components\Models\MarketplaceCategorys;
use Sort1API\Components\Models\BilingGuides;
use Sort1API\Components\Models\CdekApi;
use Sort1API\Components\Models\Crossdatas;
use Sort1API\Components\Models\PlannedDealerPayments;
use Sort1API\Components\Models\EmailConfigs;
use Sort1API\Components\Models\NewInSort1s;
use Sort1API\Components\Models\DetailMarks;
use Sort1API\Components\Models\B2BClientService;

require_once __DIR__ . '/BaseController.php'; 

class Controller extends BaseController {

	/**
	*
	* 	Actions:
	*
	*   syntax function "action_{name of action posted in requested json}"($request)
	*
	*   var $request - need to post the object of $request class
	*
	*
	*/
	private static function check_role($module_id,$rights_name,$rw){
		$role_id=$_SESSION['roles']; 
		//$action=$request->action;
		//$req=(object)array("role_id"=>$role_id);
		$roles=Users::get_my_role();
		//file_put_contents("/var/log/sort1/check_roles","module_id=$module_id, rights_name=$rights_name, rw=$rw\n
		//right=".$roles['roles']['modules_rights']['modules']['m'.$module_id]['rights'][$rights_name][$rw]." \n roles=".print_r($roles,true)."\n",FILE_APPEND);
		if(isset($roles['roles']['modules_rights']['modules']['m'.$module_id]['rights'][$rights_name])){ 
			if($roles['roles']['modules_rights']['modules']['m'.$module_id]['rights'][$rights_name][$rw]==1) 
				return 1;
			else return 0;
		}
		else return 1;
	}

	public static function action_get_seed($request){
		return array( "status"=>"ok","seed"=>hash("sha256",md5(date("Y-m-d"))) );
	}

	public static function action_login($request){
		//$sessionid=session_id();
		if(Auth::login($request)){
		    $ret['status']="ok";
		    $ret['err']="";
		    $ret['sesskey']=session_id();
		    return $ret;
		}
		else {
			//print_r(Auth::$_err_msg);
			if(Auth::$_err_msg!="") return self::_error(Auth::$_err_msg); 
			else return self::_error("Не правильные данные пользователя");
		}

	}

	public static function action_user_login($request){
		//$sessionid=session_id();
		if(Auth::user_login($request)){
			$db = DB::getInstance();
				preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
				$sql="select company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
				$site_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
			if((int)$site_data['shop_verify_phone']==1){
				$is_phone_confirmed=$db->getOne("select mphone_confirmed from users where id=?i",$_SESSION['user_id']);
				if((int)$is_phone_confirmed==0){
					//return array("status"=>"ok","err"=>"Введите код отправленный на ваш номер телефона","sesskey"=>"","code"=>1);
				}
			}
		    $ret['status']="ok";
		    $ret['err']="";
		    $ret['sesskey']=session_id();
		    return $ret;
		}
		else {
		    return array("status"=>"err","err"=>"Не правильные данные пользователя","sesskey"=>session_id());
		}

	}

	public static function action_user_login_market($request){
		//$sessionid=session_id();
		if(Auth::user_login_market($request)){
		    $ret['status']="ok";
		    $ret['err']="";
		    $ret['sesskey']=session_id();
		    return $ret;
		}
		else {
		    return array("status"=>"err","err"=>"Не правильные данные пользователя","sesskey"=>session_id());
		}

	}

	public static function action_send_mphone_confirm_code($request){
		return Users::send_mphone_confirm_code($request);
	}

	public static function action_register_user_market($request){
		return Users::register_user_market($request);
	}

	public static function action_recover_password_email_shop($request){
		return Users::recover_password_email_shop($request);
	}

	public static function action_get_current_city($request){
		return Users::get_current_city($request);
	}

	public static function action_change_company($request){
		return Auth::change_company($request);
	}

	public static function action_change_my_sklad($request){
		return Auth::change_my_sklad($request);
	}

	public static function action_change_my_service($request){
		return Auth::change_my_service($request);
	}

	public static function action_get_current_company($request){
		return Auth::get_current_company($request);
	}

	public static function action_get_current_sklad_shop($request){
		return Auth::get_current_sklad_shop($request);
	}

	public static function action_isAuth($request){
		//$sessionid=session_id();
		if(session_id()!=$request->sesskey){
		    $ret['status']="ok";
		    $ret['err']="";
		    $ret['sesskey']=session_id();
		    return $ret;
		}
		else {
		    return self::_error("Не авторизован");
		}

	}

	public static function action_logout($request){
		//$sessionid=session_id();
		if(Auth::logout($request)){
		    $ret['status']="ok";
		    $ret['err']="";
		    //$ret['sesskey']=session_id();
		    return $ret;
		}
		else {
		    return self::_error("Не правильные данные пользователя");
		}

	}

	public static function action_get_details($request) {
		/**
		* Filters or other posted data: need to check and transfer to model:
		*/

		$searchstr = $request->searchstr;
		//or:
		$detail_id = $request->detail_id;

		$client_id = $request->client_id;
		$profile_id = $request->profile_id;

		$hwid =  $request->hwid;

		/**
		*
		* $type - EXtremely required field
		* possible values:
		* "1" - incompleted type - we try to response suggestions
		* "2" - completed type - search article in details table (then in "Sites", then in Laximo/abcp, etc...)
		* "3" - chosen suggestion - search only in details table(+ laximo/abcp)
		*/
		$type = $request->type;

		//May check parameters and return Error:
		if (empty($client_id) || empty($profile_id) || empty($hwid))
			return self::_error("Не определен клиент");
		if (empty(Functions::convert_article($searchstr)) && empty($detail_id))
			return self::_error("Пустая строка поиска");
		if (empty($type) || !in_array($type, [1,2,3]))
			return self::_error("Не известный тип запроса");



		//Get data from Model:
		$res = Details::get_details([
										"searchstr" => $searchstr,
										"detail_id" => $detail_id,
										"client_id" => $client_id,
										"profile_id" => $profile_id,
										"type" => $type,
									]);
		return array_merge(["status" => "ok",
				"err" => "",
				"time" => date("d.m.Y H:i:s"),
				], $res);

	}


	/////////////////////
	//for test:///////////
	////////////////////////
	public static function action_get_details2($request) {
		/**
		* Filters or other posted data: need to check and transfer to model:
		*/

		$searchstr = $request->searchstr;
		//or:
		$detail_id = $request->detail_id;

		$client_id = $request->client_id;
		$profile_id = $request->profile_id;

		$hwid =  $request->hwid;

		/**
		*
		* $type - EXtremely required field
		* possible values:
		* "1" - incompleted type - we try to response suggestions
		* "2" - completed type - search article in details table (then in "Sites", then in Laximo/abcp, etc...)
		* "3" - chosen suggestion - search only in details table(+ laximo/abcp)
		*/
		$type = $request->type;

		//May check parameters and return Error:
		if (empty($client_id) || empty($profile_id) || empty($hwid))
			return self::_error("Не определен клиент");
		if (empty(Functions::convert_article($searchstr)) && empty($detail_id))
			return self::_error("Пустая строка поиска");
		if (empty($type) || !in_array($type, [1,2,3]))
			return self::_error("Не известный тип запроса");



		//Get data from Model:
		$res = Details::get_details2([
										"searchstr" => $searchstr,
										"detail_id" => $detail_id,
										"client_id" => $client_id,
										"profile_id" => $profile_id,
										"type" => $type,
									]);
		return array_merge(["status" => "ok",
				"err" => "",
				"time" => date("d.m.Y H:i:s"),
				], $res);

	}



	public static function action_get_crosses($request) {

		// Support for pricelist:
		$searchstr = $request->searchstr;
		$brands = $request->brands;
		// Single detail_id or array support:
		$detail_id = $request->detail_id;

		// New: $detail may contain:
		// 1) single int
		// 2) array of int
		// 3) array of object {"k":required, "a":article required, "b": brands(string with separator) not required }
		$detail = $request->detail;

		$include_aliases = $request->include_aliases;
		$add_fields = $request->add_fields;

		if (empty(Functions::convert_article($searchstr)) && empty($detail_id) && empty($detail))
			return self::_error("Пустая строка поиска");

		//echo "Array of detail: ".print_r($detail,true);

		$res = Crosses::get_crosses([
										"searchstr" => $searchstr,
										"brands" => $brands,
										"detail_id" => $detail_id,
										"detail" => $detail,
										"include_aliases" => $include_aliases,
										"add_fields" => $add_fields,
									]);
		return $res;




	}


	public static function action_get_tooltips($request) {
		$searchstr = $request->searchstr;

		if (empty(Functions::convert_article($searchstr)))
			return self::_error("Пустая строка поиска");


		$res = Tooltips::update_tooltips([
										"searchstr" => $searchstr,
									]);

		return $res;

	}


	public static function action_get_tooltips_old_ver($request) {
		$searchstr = $request->searchstr;

		if (empty(Functions::convert_article($searchstr)))
			return self::_error("Пустая строка поиска");

		$res = Tooltips::get_tooltips_old_ver([
												"searchstr" => $searchstr,
											]);

		return ["status" => "ok",
				"err" => "",
				"response" => $res,
				"time" => date("d.m.Y H:i:s"),
				];


	}


	public static function action_get_abcp_test($request) {

		$art = $request->article;
		$brand = $request->brand;
		if (empty(Functions::convert_article($art)) || empty($brand))
			return self::_error("Пустая строка поиска");

		$res = Abcp::abcp_get_crosses(["brand"=>$brand, "number"=>$art]);

		return [
				"status" => "ok",
				"err" => "",
				"response" => $res,
				"time" => date("d.m.Y H:i:s"),
		];
	}

	public static function action_enable_api($request) {
	    return Users::enable_api($request);
	}

	public static function action_disable_api($request) {
	    return Users::disable_api($request);
	}

	public static function action_register_user($request) {
	    return Users::register_user($request);
	}

	public static function action_get_market_captcha($request) {
	    return Users::get_market_captcha($request);
	}

	public static function action_register_callback($request) {
	    return Users::register_callback($request);
	}

	public static function action_recover_password_email_market($request) {
	    return Users::recover_password_email_market($request);
	}

	public static function action_save_user_data($request) {
	    return Users::save_user_data($request);
	} // action_save_company

	public static function action_save_user_data_market($request) {
	    return Users::save_user_data_market($request);
	}

	public static function action_change_user_password($request) {
	    return Users::change_user_password($request);
	}

	public static function action_change_my_password($request) {
	    return Users::change_my_password($request);
	}

	public static function action_get_user_data($request) {
	    return Users::get_user_data($request);
	}

	public static function action_set_city_user($request) {
	    return Users::set_city_user($request);
	}

	public static function action_get_user_pref($request) {
	    return Users::get_user_pref($request);
	}

	public static function action_save_user_pref($request) {
	    return Users::save_user_pref($request);
	}

	public static function action_fire_user($request) {
	    return Users::fire_user($request);
	}

	public static function action_save_clients($request) {
		//if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		//if((int)$request->btype===1) $rights_name="clients";
		//if((int)$request->btype===3) $rights_name="my_company";
		//if(!isset($rights_name)) $rights_name="clients";
		//if(self::check_role(2,$rights_name,"write"))
			return Companys::save_clients($request);
		//else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_company($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		if((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"write"))
			return Companys::save_company($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	} // action_save_company

	public static function action_fast_save_company($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		elseif((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"write"))
			return Companys::fast_save_company($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_company($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		if((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"delete"))
			return Companys::delete_company($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_restore_company($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		if((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"delete"))
			return Companys::restore_company($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_company($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		if((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"read"))
			return Companys::load_company_data($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_main_org($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		if((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"read"))
			return Companys::get_main_orgs($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_my_company($request) {
		//if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		//if((int)$request->btype===1) $rights_name="clients";
		//if((int)$request->btype===3) $rights_name="my_company";
		//if(!isset($rights_name)) $rights_name="clients";
		$request->btype=3;
		//if(self::check_role(2,$rights_name,"read"))
			return Companys::load_company_data($request);
		//else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_company_data_from_api($request) {
	    return Companys::get_company_data_from_api($request);
	}

	public static function action_get_bank_data_from_api($request) {
	    return Companys::get_bank_data_from_api($request);
	}

	public static function action_get_company_users($request) {
		//if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		//if((int)$request->btype===1) 
			$rights_name="clients";
		if(self::check_role(2,$rights_name,"read"))
			return Users::get_company_users($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_my_company_users($request) {
	    return Users::get_my_company_users($request);
	}

	public static function action_change_company_bonus($request) {
		if($_SESSION['roles']<3){
	    	return Companys::change_company_bonus($request);
		}
		else {
			array("status"=>"err","err"=>"не хватает прав");
		}
	}

	public static function action_get_site_users($request) {
	    return Users::get_site_users($request);
	}

	public static function action_get_roles($request) {
	    return Users::get_roles($request);
	}

	public static function action_get_role($request) {
	    return Users::get_role($request);
	}

	public static function action_get_my_role($request) {
	    return Users::get_my_role($request);
	}

	public static function action_save_role($request) {
	    return Users::save_role($request);
	}

	public static function action_check_included_jetparts($request) {
	    return Users::check_included_jetparts($request);
	}

	public static function action_get_clients($request) {
		if(self::check_role(2,"clients","read"))
			return Companys::get_clients($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_dealers($request) {
		if(self::check_role(2,"dealers","read"))
			return Companys::get_dealers($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_logistic_companys($request) {
		if(self::check_role(7,"logistic_companys","read"))
			return Companys::get_logistic_companys($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_my_companies($request) {
		$rights_name="my_company";
		if(self::check_role(2,$rights_name,"read") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
			return Companys::get_my_companies($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_company_user($request){
		return array("status"=>"ok", "company"=>$_SESSION['company_id']);;
	}

	public static function action_get_sklads($request) {
	    return Sklads::get_sklads($request);
	}

	public static function action_get_delivery_sklads($request) {
	    return Sklads::get_delivery_sklads($request);
	}

	public static function action_get_sklad($request) {
	    return Sklads::get_sklad($request);
	}

	public static function action_get_sklad_by_session($request) {
	    return Sklads::get_sklad_by_session($request);
	}

	public static function action_save_sklad($request) {
	    return Sklads::save_sklad($request);
	}

	public static function action_save_topology($request) {
		if(self::check_role(9,"sklad_topology","write"))
			return SkladTopologys::save_sklad_topology($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_topologys($request) {
		if(self::check_role(9,"sklad_topology","read"))
			return SkladTopologys::get_sklad_topologys($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_topology($request) {
		if(self::check_role(9,"sklad_topology","read"))
			return SkladTopologys::get_sklad_topology($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_sklad($request) {
		if(self::check_role(6,"sklad","delete"))
			return Sklads::delete_sklad($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_lists($request) {
		if(self::check_role(6,"price_list","read"))
			return PriceLists::get_price_lists($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_list($request) {
		if(self::check_role(6,"price_list","read"))
			return PriceLists::get_price_list($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_price_list($request) {
		if(self::check_role(6,"price_list","write"))
			return PriceLists::save_price_list($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_price_list($request) {
		if(self::check_role(6,"price_list","delete"))
			return PriceLists::delete_price_list($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_list_csv($request) {
		if(self::check_role(6,"price_list","read"))
			return PriceLists::get_price_list_csv($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_list_xls($request) {
		if(self::check_role(6,"price_list","read"))
			return PriceLists::get_price_list_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_detail_by_ean13($request){
		return SkladDetails::get_sklad_detail_by_ean13($request);
	}

	public static function action_get_sklad_detail($request) {
	    return SkladDetails::get_sklad_detail($request);
	}

	public static function action_get_sklad_doubles($request) {
	    return SkladDetails::get_sklad_doubles($request);
	}

	public static function action_change_min_count_on_sklad($request) {
	    return SkladDetails::change_min_count_on_sklad($request);
	}

	public static function action_save_sklad_detail($request) {
		if(self::check_role(6,"sklad","write"))
			return SkladDetails::save_sklad_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_sklad_detail_markup_price($request) {
		if(self::check_role(6,"sklad","write"))
			return SkladDetails::save_sklad_detail_markup_price($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_sklad_detail_name($request) {
		if(self::check_role(6,"sklad","write"))
			return SkladDetails::save_sklad_detail_name($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_details($request) {
		if(self::check_role(6,"sklad","read"))
		return SkladDetails::get_sklad_details($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_details_oem($request) {
		if(self::check_role(6,"sklad","read"))
		return SkladDetails::get_sklad_details_oem($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_csv($request) {
		if(self::check_role(6,"sklad","read"))
		return Sklads::get_sklad_csv($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_documents_csv($request) {
		if($request->znak=="+") $rights_name="document_plus";
		else $rights_name="document_minus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_documents_csv($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_documents_xls($request) {
		if($request->znak=="+") $rights_name="document_plus";
		else $rights_name="document_minus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_documents_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_upd_xls($request) {
		$rights_name="document_plus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_upd_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_akt_sverki_xls($request) {
		$rights_name="document_plus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_akt_sverki_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_ukd_xls($request) {
		$rights_name="document_plus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_ukd_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_join_documents($request) {
		$rights_name="document_minus";
		if(self::check_role(10,$rights_name,"write"))
		return Documents::join_documents($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_print_zakaz_xls($request) {
		$rights_name="document_plus";
		if(self::check_role(10,$rights_name,"read"))
		return Zakazs::get_print_zakaz_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_print_tovar_check_xls($request) {
		$rights_name="document_plus";
		if(self::check_role(10,$rights_name,"read"))
		return Zakazs::get_print_tovar_check_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_clients_xls($request) {
		if((int)$request->btype===2 || (int)$request->btype===5 || (int)$request->btype===4) $rights_name="dealers";
		if((int)$request->btype===1) $rights_name="clients";
		if((int)$request->btype===3) $rights_name="my_company";
		if(!isset($rights_name)) $rights_name="clients";
		if(self::check_role(2,$rights_name,"read"))
			return Companys::get_clients_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sklad_xls($request) {
		if(self::check_role(6,"sklad","read"))
		return Sklads::get_sklad_xls($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_sklad_detail($request) {
		if(self::check_role(6,"sklad","delete"))
		return SkladDetails::delete_sklad_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_fix_price_detail($request) {
		if(self::check_role(9,"fixed_prices","read"))
		return FixPriceDetails::get_fix_price_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_fix_price_detail($request) {
		if(self::check_role(9,"fixed_prices","write"))
		return FixPriceDetails::save_fix_price_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_fix_price_details($request) {
		if(self::check_role(9,"fixed_prices","read"))
		return FixPriceDetails::get_fix_price_details($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_fix_price_detail($request) {
		if(self::check_role(9,"fixed_prices","delete"))
		return FixPriceDetails::delete_fix_price_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_list_detail($request) {
		if(self::check_role(6,"price_list","read"))
		return PriceListDetails::get_price_list_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_price_list_detail($request) {
		if(self::check_role(6,"price_list","write"))
		return PriceListDetails::save_price_list_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_list_details($request) {
		if(self::check_role(6,"price_list","read"))
		return PriceListDetails::get_price_list_details($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_price_list_detail($request) {
		if(self::check_role(6,"price_list","delete"))
		return PriceListDetails::delete_price_list_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_documents($request) {
		if($request->znak=="+") $rights_name="document_plus";
		else $rights_name="document_minus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_documents($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_documents_return_to_dealer($request) {
		$rights_name="document_plus";
		if(self::check_role(10,$rights_name,"read"))
		return Documents::get_documents_return_to_dealer($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_make_document_detail_return_to_dealer($request) {
		$rights_name="document_minus";
		if(self::check_role(10,$rights_name,"write"))
		return DocumentDetails::make_document_detail_return_to_dealer($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_document_details_in_sklad($request){
		return Search::get_document_details_in_sklad($request);
	}

	public static function action_get_document($request) {
	    return Documents::get_document($request);
	}

	public static function action_set_document_obrabotan($request) {
	    return Documents::set_document_obrabotan($request);
	}

	public static function action_save_document($request) {
	    return Documents::save_document($request);
	}

	public static function action_delete_document($request) {
		if($request->znak=="+") $rights_name="document_plus";
		else $rights_name="document_minus";
		if(self::check_role(10,$rights_name,"delete"))
	    	return Documents::delete_document($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_document_types($request) {
	    return Documents::get_document_types($request);
	}

	public static function action_get_document_detail($request) {
	    return DocumentDetails::get_document_detail($request);
	}

	public static function action_checked_document_details($request) {
	    return DocumentDetails::checked_document_details($request);
	}

	public static function action_print_document_details($request) {
	    return DocumentDetails::print_document_details($request);
	}

	public static function action_save_document_detail($request) {
	    return DocumentDetails::save_document_detail($request);
	}

	public static function action_save_document_details($request) {
	    return DocumentDetails::save_document_details($request);
	}

	public static function action_get_document_details($request) {
	    return DocumentDetails::get_document_details($request);
	}

	public static function action_get_document_details_xls($request) {
	    return DocumentDetails::get_document_details_xls($request);
	}

	public static function action_delete_document_detail($request) {
		if($request->znak=="+") $rights_name="document_plus";
		else $rights_name="document_minus";
		if(self::check_role(10,$rights_name,"delete"))
	    	return DocumentDetails::delete_document_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_gocument_job($request) {
	    return DocumentJobs::get_document_job($request);
	}

	public static function action_save_document_job($request) {
	    return DocumentJobs::save_document_job($request);
	}

	public static function action_save_document_jobs($request) {
	    return DocumentJobs::save_document_jobs($request);
	}

	public static function action_get_document_jobs($request) {
	    return DocumentJobs::get_document_jobs($request);
	}

	public static function action_delete_document_job($request) {
		if($request->znak=="+") $rights_name="document_plus";
		else $rights_name="document_minus";
		if(self::check_role(10,$rights_name,"delete"))
	    	return DocumentJobs::delete_document_job($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_types($request){
		if(self::check_role(9,"nacenki_skidki","read"))
			return PriceTypes::get_price_types($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_price_type($request){
		if(self::check_role(9,"nacenki_skidki","read"))
		return PriceTypes::get_price_type($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_price_type($request){
		if(self::check_role(9,"nacenki_skidki","write"))
		return PriceTypes::save_price_type($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_price_type($request){
		if(self::check_role(9,"nacenki_skidki","delete"))
		return PriceTypes::delete_price_type($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_tax_types($request){
		if(self::check_role(9,"taxes","read"))
		return TaxTypes::get_tax_types($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_tax_type($request){
		if(self::check_role(9,"taxes","read"))
		return TaxTypes::get_tax_type($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_tax_type($request){
		if(self::check_role(9,"taxes","write"))
		return TaxTypes::save_tax_type($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_tax_type($request){
		if(self::check_role(9,"taxes","delete"))
		return TaxTypes::delete_tax_type($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_currency_kurs($request){
	    $db = DB::getInstance();
	    $currency_kurs=$db->getAll("select * from currency_kurs order by date desc limit 34");
	    $currency_kurs[34]=array(
		"NumCode"=>"1",
		"CharCode"=>"RUB",
		"Nominal"=>"1",
		"Name"=>"Российский рубль",
		"Value"=>"1"
	    );
	    $ret=array(
		"status"=>"ok",
		"currency_kurs"=>$currency_kurs,
		"msg"=>"",
		"err"=>""
	    );
	    return $ret;
	}

	public static function action_upload_file(){
	    return ExcelToBases::upload_file();
	}

	public static function action_bank_upload($request){
	    return Payments::bank_upload($request);
	}

	public static function action_get_uploaded_file_page($request){
	    return ExcelToBases::get_uploaded_file_page($request);
	}

	public static function action_SetColAssoc($request){
	    return ExcelToBases::SetColAssoc($request);
	}

	public static function action_get_loader_job_status($request){
	    return ExcelToBases::get_loader_job_status($request);
	}

	public static function action_get_dogovors($request) {
	    return Dogovors::get_dogovors($request);
	}

	public static function action_get_dogovor($request) {
	    return Dogovors::get_dogovor($request);
	}

	public static function action_get_company_dogovors($request) {
	    return Dogovors::get_company_dogovors($request);
	}

	public static function action_save_dogovor($request) {
	    return Dogovors::save_dogovor($request);
	}

	public static function action_delete_dogovor($request) {
		if(self::check_role(2,"clients","delete"))
	    	return Dogovors::delete_dogovor($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_company_sites($request) {
	    return Companys::get_company_sites($request);
	}

	public static function action_get_colors_site($request){
		return Companys::get_colors_site($request);
	}

	public static function action_save_colors_site($request){
		return Companys::save_colors_site($request);
	}

	public static function action_get_company_site($request) {
	    return Companys::get_company_site($request);
	}

	public static function action_get_site_data($request) {
	    return Companys::get_site_data($request);
	}

	public static function action_save_company_site($request) {
	    return Companys::save_company_site($request);
	}

	public static function action_get_laximo_data($request) {
	    return Companys::get_laximo_data($request);
	}

	public static function action_save_laximo_data($request) {
	    return Companys::save_laximo_data($request);
	}

	public static function action_save_company_site_header($request) {
	    return Companys::save_company_site_header($request);
	}

	public static function action_get_catalogs($request){
		return Catalogs::get_catalogs($request);
	}

	public static function action_get_config_catalog($request){
		return Catalogs::get_config_catalog($request);
	}

	public static function action_delete_company_site($request) {
	    return Companys::delete_company_site($request);
	}

	public static function action_delete_site_header($request) {
	    return Companys::delete_site_header($request);
	}

	public static function action_save_site_colors($request) {
	    return Companys::save_site_colors($request);
	}

	public static function action_save_site_pages($request) {
	    return Companys::save_site_pages($request);
	}

	public static function action_get_pwa($request) {
	    return Companys::get_pwa($request);
	}

	public static function action_save_pwa($request) {
	    return Companys::save_pwa($request);
	}

	public static function action_search_by_article($request){
	    return Search::search_by_article($request);
	}

	public static function action_search_by_articles($request){
	    return Search::search_by_articles($request);
	}

	public static function action_search_by_article_market($request){
	    return Search::search_by_article_market($request);
	}

	public static function action_search_by_words_market($request){
	    return Search::search_by_words_market($request);
	}

	public static function action_search_crosses_by_article_market($request){
	    return Search::search_crosses_by_article_market($request);
	}

	public static function action_search_by_ean13($request){
	    return Search::search_by_ean13($request);
	}

	public static function action_search_sort1($request){
	    return Search::search_sort1($request);
	}

	public static function action_new_search_sort1_ver($request){
	    return Search::new_search_sort1_ver($request);
	}

	public static function action_get_search_results_ver($request){
	    return Search::get_search_results_ver($request);
	}


	public static function action_get_results($request){
	    return Search::get_results($request);
	}

	public static function action_get_results_ver($request){
	    return Search::get_results_ver($request);
	}

	public static function action_search_history($request){
	    return Search::search_history($request);
	}

	public static function action_get_brands($request){
	    return Search::get_brands($request);
	}

	public static function action_get_brand_id($request){
	    return Search::get_brand_id($request);
	}

	public static function action_search_brand_id($request){
	    return Search::search_brand_id($request);
	}

	public static function action_get_detail_info($request){
	    return Search::get_detail_info($request);
	}

	public static function action_get_detail_info_market($request){
	    return Search::get_detail_info_market($request);
	}

	public static function action_get_details_info_market($request){
	    return Search::get_details_info_market($request);
	}

	public static function action_get_detail_images($request){
	    return Search::get_detail_images($request);
	}

	public static function action_get_popular_details($request){
	    return Search::get_popular_details($request);
	}

	public static function action_search_categorys($request){
	    return Search::search_categorys($request);
	}

	public static function action_get_all_details_sklad($request){
	    return Search::get_all_details_sklad($request);
	}

	public static function action_get_all_details_sklad_market($request){
	    return Search::get_all_details_sklad_market($request);
	}

	public static function action_search_crosses($request){
	    return Search::search_crosses($request);
	}

	public static function action_search_crosses_market($request){
	    return Search::search_crosses_market($request);
	}

	public static function action_get_detail_documents($request){
	    return Documents::get_detail_documents($request);
	}

	public static function action_get_brands_online($request){
	    return Search::get_brands_online($request);
	}

	public static function action_get_detail_by_ean13($request){
	    return DocumentDetails::get_detail_by_ean13($request);
	}

	public static function action_get_detail_by_my_code($request){
	    return DocumentDetails::get_detail_by_my_code($request);
	}

	public static function action_get_ean13_of_detail($request){
	    return DocumentDetails::get_ean13_of_detail($request);
	}

	public static function action_put_to_cart($request){
	    return Search::put_to_cart($request);
	}

	public static function action_get_cart_results($request){
	    return Search::cart_results($request);
	}

	public static function action_get_plugins($request){
	    return Sort1s::get_plugins($request);
	}

	public static function action_get_profile_plugins($request){
	    return Search::get_profile_plugins($request);
	}

	public static function action_get_api_plugins($request){
	    return Sort1s::get_api_plugins($request);
	}

	public static function action_get_api_ver_plugins($request){
	    return Sort1s::get_api_ver_plugins($request);
	}

	public static function action_get_plugin_settings($request){
	    return Sort1s::get_plugin_settings($request);
	}

	public static function action_save_plugin_settings($request){
		if(self::check_role(9,"online_profiles","write"))
		return Sort1s::save_plugin_settings($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_ver_plugin_settings($request){
		return Sort1s::save_ver_plugin_settings($request);
	}

	public static function action_get_params($request){
	    return Sort1s::get_params($request);
	}

	public static function action_get_lic($request){
	    return Sort1s::get_lic($request);
	}

	public static function action_to_basket($request){
	    return BasketDetails::save_basket_detail($request);
	}

	public static function action_save_basket_detail($request){
	    return BasketDetails::save_basket_detail($request);
	}

	public static function action_save_basket_detail_market($request){
	    return BasketDetails::save_basket_detail_market($request);
	}

	public static function action_save_basket($request){
	    return BasketDetails::save_basket($request);
	}

	public static function action_clear_basket($request){
	    return BasketDetails::clear_basket($request);
	}

	public static function action_get_basket_detail($request){
	    return BasketDetails::get_basket_detail($request);
	}

	public static function action_get_basket_details($request){
	    return BasketDetails::get_basket_details($request);
	}

	public static function action_delete_basket_detail($request){
	    return BasketDetails::delete_basket_detail($request);
	}

	public static function action_delete_basket_details($request){
	    return BasketDetails::delete_basket_details($request);
	}

	public static function action_get_basket_count($request){
	    return BasketDetails::get_basket_count($request);
	}

	public static function action_save_delivery_address($request){
	    return DeliveryAddresses::save_delivery_address($request);
	}

	public static function action_get_delivery_addresses($request){
	    return DeliveryAddresses::get_delivery_addresses($request);
	}

	public static function action_get_my_delivery_addresses($request){
	    return DeliveryAddresses::get_my_delivery_addresses($request);
	}

	public static function action_save_company_rekvizit($request){
	    return CompanyRekvizits::save_company_rekvizit($request);
	}

	public static function action_get_company_rekvizits($request){
	    return CompanyRekvizits::get_company_rekvizits($request);
	}

	public static function action_get_company_rekvizit($request){
	    return CompanyRekvizits::get_company_rekvizit($request);
	}

	public static function action_delete_company_rekvizit($request){
	    return CompanyRekvizits::delete_company_rekvizit($request);
	}

	public static function action_save_company_car($request){
	    return CompanyCars::save_company_car($request);
	}

	public static function action_get_company_cars($request){
	    return CompanyCars::get_company_cars($request);
	}

	public static function action_get_company_car($request){
	    return CompanyCars::get_company_car($request);
	}

	public static function action_delete_company_car($request){
	    return CompanyCars::delete_company_car($request);
	}

	public static function action_save_zakaz($request){
		if(self::check_role(3,"client_zakazs","write") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return Zakazs::save_zakaz($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_zakaz_shop($request){
		if(self::check_role(3,"client_zakazs","write") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return Zakazs::save_zakaz_shop($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_zakaz_market($request){
		if(self::check_role(3,"client_zakazs","write") || $_SESSION['roles']==20)
		return Zakazs::save_zakaz_market($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakaz($request){
		if(self::check_role(3,"client_zakazs","read") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return Zakazs::get_zakaz($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_zakaz($request){
		if(self::check_role(3,"client_zakazs","delete") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return Zakazs::delete_zakaz($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakazes($request){
		if(self::check_role(3,"client_zakazs","read") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return Zakazs::get_zakazes($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_seller($request){
		if(self::check_role(3,"client_zakazs","read") || $_SESSION['roles']==20)
		return Zakazs::get_seller($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakaz_details($request){
		if(self::check_role(3,"client_zakazs","read") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return ZakazDetails::get_zakaz_details($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_zakaz_detail_name($request){
		if(self::check_role(3,"client_zakazs","write") || $_SESSION['roles']==10 || $_SESSION['roles']==20)
		return ZakazDetails::save_zakaz_detail_name($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_set_zd_status_to_20($request){
		if(self::check_role(3,"client_zakazs","write") || $_SESSION['roles']==10 || $_SESSION['roles']==20){
			return ZakazDetails::set_zd_status_to_20($request);
		}
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_set_zakaz_detail_dealer($request){
		if(self::check_role(3,"client_zakazs","write") || $_SESSION['roles']==10 || $_SESSION['roles']==20){
			return ZakazDetails::set_zakaz_detail_dealer($request);
		}
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_client_zakaz_details($request){
		if(self::check_role(3,"client_zakazs","read"))
		return ZakazDetails::get_client_zakaz_details($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_all_zakaz_details($request){
		if(self::check_role(3,"client_zakazs","read"))
		return ZakazDetails::get_all_zakaz_details($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakaz_details_by_document_id($request){
	    return ZakazDetails::get_zakaz_details_by_document_id($request);
	}

	public static function action_get_zakaz_detail($request){
		if(self::check_role(3,"client_zakazs","read"))
		return ZakazDetails::get_zakaz_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakaz_detail_by_id($request){
		if(self::check_role(3,"client_zakazs","read"))
		return ZakazDetails::get_zakaz_detail_by_id($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_zakaz_detail($request){
		if(self::check_role(3,"client_zakazs","write"))
		return ZakazDetails::save_zakaz_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_make_zakaz_detail_return($request){
		if(self::check_role(3,"client_zakazs","write"))
		return ZakazDetails::make_zakaz_detail_return($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_cancel_zakaz_detail_return_money($request){
		if(self::check_role(3,"client_zakazs","write"))
		return ZakazDetails::cancel_zakaz_detail_return_money($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_make_zakaz_detail_return_to_dealer($request){ 
		if(self::check_role(3,"client_zakazs","write"))
		return ZakazDetails::make_zakaz_detail_return_to_dealer($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_akt_sverki($request){
		if(self::check_role(2,"clients","read"))
	    	return Reports::get_akt_sverki($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakaz_jobs($request){
		return ZakazJobs::get_zakaz_jobs($request);
	}

	public static function action_get_zakaz_job($request){
		return ZakazJobs::get_zakaz_job($request);
	}

	public static function action_get_zakaz_job_statuses($request){
		return ZakazJobs::get_zakaz_job_statuses($request);
	}

	public static function action_save_zakaz_job($request){
		return ZakazJobs::save_zakaz_job($request);
	}
	public static function action_delete_zakaz_job($request){
		return ZakazJobs::delete_zakaz_job($request);
	}

	public static function action_get_fullfilment_id($request){
	    return Zakazs::get_fullfilment_id($request);
	}

	public static function action_get_fullfilment_address($request){
		return Zakazs::get_fullfilment_address($request);
	}

	public static function action_get_my_fullfilment_id($request){
	    return Zakazs::get_my_fullfilment_id($request);
	}

	public static function action_get_ext_not_commited_zakaz_details($request){
	    return ZakazDetails::get_ext_not_commited_zakaz_details($request);
	}

	public static function action_get_ext_commited_zakaz_details($request){
	    return ZakazDetails::get_ext_commited_zakaz_details($request);
	}

	public static function action_get_zakaz_statuses($request){
	    return Zakazs::get_zakaz_statuses($request);
	}

	public static function action_save_user_zakaz_statuses($request){
	    return Zakazs::save_user_zakaz_statuses($request);
	}

	public static function action_get_zakaz_detail_status_history($request){
		return ZakazDetails::get_zakaz_detail_status_history($request);
	}

	public static function action_get_zakaz_detail_statuses($request){
	    return ZakazDetails::get_zakaz_detail_statuses($request);
	}

	public static function action_save_user_zakaz_detail_statuses($request){
	    return ZakazDetails::save_user_zakaz_detail_statuses($request);
	}

	public static function action_delete_zakaz_detail_by_manager($request){
		if(self::check_role(3,"client_zakazs","write"))
			return ZakazDetails::delete_zakaz_detail_by_manager($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_sort1_order($request){
		if(self::check_role(3,"orders","write"))
			return ZakazDetails::get_sort1_order($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_reorder_detail($request){
		if(self::check_role(3,"client_zakazs","write"))
		return ZakazDetails::save_reorder_detail($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_payments($request){
		if(self::check_role(4,"client_payments","read"))
		return Payments::get_payments($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_zakaz_payments($request){
		if(self::check_role(4,"client_payments","read"))
		return Payments::get_zakaz_payments($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_set_excise_in_zakaz_detail($request){
		return ZakazDetails::set_excise_in_zakaz_detail($request);
	}

	public static function action_set_excise_in_basket_detail($request){
		return BasketDetails::set_excise_in_basket_detail($request);
	}

	public static function action_set_marking_in_zakaz_detail($request){
		return ZakazDetails::set_marking_in_zakaz_detail($request);
	}

	public static function action_set_marking_in_basket_detail($request){
		return BasketDetails::set_marking_in_basket_detail($request);
	}

	public static function action_get_payment_types($request){
		return Payments::get_payment_types($request);
	}

	public static function action_get_delivery_payments($request){
		if(self::check_role(4,"dealer_payments","read"))
		return Payments::get_delivery_payments($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_return_payments($request){
		if(self::check_role(4,"return_payments","read"))
		return Payments::get_return_payments($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_return_payment($request){
		//if(self::check_role(4,"return_payments","read"))
		return Payments::return_payment($request);
		//else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_payment($request){
		if(self::check_role(4,"client_payments","read"))
		return Payments::get_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_payment($request){
		$db = DB::getInstance();

		switch($request->payment_direction){
			case 1: if(self::check_role(4,"client_payments","write")){
						return Payments::save_payment($request);
					}
					else{ 
						return self::_error("Недостаточно прав для выполнения данного действия");
					}
					break;
			case 2: if(self::check_role(4,"dealer_payments","write")){
						return Payments::save_payment($request);
					}
					else{ 
						return self::_error("Недостаточно прав для выполнения данного действия");
					}
					break;
			case 3:
			case 4:
			case 5: if(self::check_role(4,"return_payments","write")){
						return Payments::save_payment($request);
					}
					else{ 
						return self::_error("Недостаточно прав для выполнения данного действия");
					}
					break;
		}
	}

	public static function action_delete_payment($request){
		$db = DB::getInstance();
		$payment=$db->getRow("select * from payment where id=?i",$request->payment_id);
		if(self::check_role(4,"client_payments","delete"))
		return Payments::delete_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_fiscalize_payment($request){
		if(self::check_role(4,"client_payments","write"))
		return Payments::fiscalize_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_fiscalized_payment($request){
		if(self::check_role(4,"client_payments","write"))
		return Payments::save_fiscalized_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_import_payments($request){
		if(self::check_role(4,"client_payments","write"))
		return Payments::import_payments($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_do_sber_pay($request){
		if(self::check_role(4,"client_payments","write"))
			return Payments::do_sber_pay($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_do_sber_pay_market($request){
		if(self::check_role(10,"client_payments","write") || $_SESSION['roles']==20)
			return Payments::do_sber_pay_market($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_do_tinkoff_pay($request){
		if(self::check_role(4,"client_payments","write"))
			return Payments::do_tinkoff_pay($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_cancel_sber_pay($request){
		if(self::check_role(4,"client_payments","write"))
			return Payments::cancel_sber_pay($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_status_sber_pay($request){
		if(self::check_role(4,"client_payments","write"))
			return Payments::status_sber_pay($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_planned_dealer_payments($request){
		if(self::check_role(4,"client_payments","read"))
		return PlannedDealerPayments::get_planned_dealer_payments($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_delete_planned_dealer_payment($request){
		if(self::check_role(4,"client_payments","delete"))
		return PlannedDealerPayments::delete_planned_dealer_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_planned_dealer_payment($request){
		if(self::check_role(4,"client_payments","write"))
		return PlannedDealerPayments::get_planned_dealer_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_save_planned_dealer_payment($request){
		if(self::check_role(4,"client_payments","read"))
		return PlannedDealerPayments::save_planned_dealer_payment($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_commit_zakaz($request){
		if(self::check_role(3,"client_zakazs","write"))
		return Zakazs::commit_zakaz($request);
		else return self::_error("Недостаточно прав для выполнения данного действия");
	}

	public static function action_get_city($request){
	    return Citys::get_city($request);
	}

	public static function action_get_countrys($request){
	    return Citys::get_countrys($request);
	}

	public static function action_get_citys($request){
	    return Citys::get_citys($request);
	}

	public static function action_get_price_type_differencial_values($request){
	    return PriceTypeDifferencialValues::get_price_type_differencial_values($request);
	}

	public static function action_get_price_type_differencial_value($request){
	    return PriceTypeDifferencialValues::get_price_type_differencial_value($request);
	}

	public static function action_save_price_type_differencial_value($request){
	    return PriceTypeDifferencialValues::save_price_type_differencial_value($request);
	}

	public static function action_delete_price_type_differencial_value($request){
	    return PriceTypeDifferencialValues::delete_price_type_differencial_value($request);
	}

	    /**
     * События в колоночке
     *
     */
    public static function action_deskColumn() {
        $result = Calendars::getEventsForClients();
        $response = [
            'db' => $result,
            'settings' => [
                'id' => 1,
            ],
        ];
        return $response;
    }

    /**
     * Редактировать событие
     * @param $request
     */
    public static function action_editEvent($request) {
        $result = Functions::arrs($request->arrData);
        //debug($request);
        $res = Calendars::editEvent($result);

        return $res;
    }

    /**
     * Добавить событие в БД
     * @param $request
     */
    public static function action_addEvents($request) {
        $result = Functions::arrs($request->arrData);
        //$res['debug'][]=$result;
        $res = Calendars::addEvent($result);

        return $res;
    }

		public static function action_get_logistic_cars($request){
			if(self::check_role(7,"logistic_cars","read"))
			return LogisticCars::get_logistic_cars($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_logistic_car($request){
			if(self::check_role(7,"logistic_cars","read"))
			return LogisticCars::get_logistic_car($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_logistic_car($request){
			if(self::check_role(7,"logistic_cars","write"))
			return LogisticCars::save_logistic_car($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_logistic_car($request){
			if(self::check_role(7,"logistic_cars","write"))
			return LogisticCars::delete_logistic_car($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_logistic_orders($request){
			if(self::check_role(7,"logistic_orders","read"))
			return LogisticOrders::get_logistic_orders($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_logistic_order($request){
			if(self::check_role(7,"logistic_orders","read"))
			return LogisticOrders::get_logistic_order($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		} 

		public static function action_save_logistic_order($request){
			if(self::check_role(7,"logistic_orders","write"))
			return LogisticOrders::save_logistic_order($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_logistic_order($request){
			if(self::check_role(7,"logistic_orders","write"))
			return LogisticOrders::delete_logistic_order($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_logistic_order_details($request){
			if(self::check_role(7,"logistic_orders","read"))
			return LogisticOrderDetails::get_logistic_order_details($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_logistic_order_detail($request){
			if(self::check_role(7,"logistic_orders","read"))
			return LogisticOrderDetails::get_logistic_order_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_logistic_order_detail($request){
			if(self::check_role(7,"logistic_orders","write"))
			return LogisticOrderDetails::save_logistic_order_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_logistic_order_detail($request){
			if(self::check_role(7,"logistic_orders","write"))
			return LogisticOrderDetails::delete_logistic_order_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_delivery_to_workshops($request){
			if(self::check_role(7,"logistic_orders","read"))
			return DeliveryToWorkshops::get_delivery_to_workshops($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_delivery_to_workshop($request){
			if(self::check_role(7,"logistic_orders","read"))
			return DeliveryToWorkshops::get_delivery_to_workshop($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		} 

		public static function action_save_delivery_to_workshop($request){
			if(self::check_role(7,"logistic_orders","write"))
			return DeliveryToWorkshops::save_delivery_to_workshop($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_deliverys_to_workshop($request){
			if(self::check_role(7,"logistic_orders","write"))
			return DeliveryToWorkshops::save_deliverys_to_workshop($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_delivery_to_workshop($request){
			if(self::check_role(7,"logistic_orders","write"))
			return DeliveryToWorkshops::delete_delivery_to_workshop($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_delivery_to_workshop_details($request){
			if(self::check_role(7,"logistic_orders","read"))
			return DeliveryToWorkshopDetails::get_delivery_to_workshop_details($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_delivery_to_workshop_detail($request){
			if(self::check_role(7,"logistic_orders","read"))
			return DeliveryToWorkshopDetails::get_delivery_to_workshop_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_delivery_to_workshop_detail($request){
			if(self::check_role(7,"logistic_orders","write"))
			return DeliveryToWorkshopDetails::save_delivery_to_workshop_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_delivery_to_workshop_detail($request){
			if(self::check_role(7,"logistic_orders","write"))
			return DeliveryToWorkshopDetails::delete_delivery_to_workshop_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_return_delivery_to_workshop_detail($request){
			if(self::check_role(7,"logistic_orders","write"))
			return DeliveryToWorkshopDetails::return_delivery_to_workshop_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_auto_makers($request){
			return LogisticCars::get_auto_makers($request);
		}

		public static function action_get_auto_models($request){
			return LogisticCars::get_auto_models($request);
		}

		public static function action_get_logistic_drivers($request){
			if(self::check_role(7,"logistic_drivers","read"))
			return LogisticDrivers::get_logistic_drivers($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_logistic_driver($request){
			if(self::check_role(7,"logistic_drivers","read"))
			return LogisticDrivers::get_logistic_driver($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_logistic_driver($request){
			if(self::check_role(7,"logistic_drivers","write"))
			return LogisticDrivers::save_logistic_driver($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_report_profit($request){
			if(self::check_role(11,"report_profit","write"))
			return Reports::get_report_profit($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_report_by_goods($request){
			if(self::check_role(11,"report_by_goods","write"))
			return Reports::get_report_by_goods($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_report_by_goods_from_sklad($request){
			if(self::check_role(11,"report_by_goods_from_sklad","write"))
			return Reports::get_report_by_goods_from_sklad($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_report_by_oil($request){
			if(self::check_role(11,"report_by_oil","write"))
			return Reports::get_report_by_oil($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_incoming_report($request){
			if(self::check_role(11,"incoming_report","write"))
			return Reports::get_incoming_report($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_marketing_channel_report($request){
			if(self::check_role(11,"marketing_channel_report","write"))
			return Reports::get_marketing_channel_report($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_report_clients($request){
			if(self::check_role(11,"report_by_clients","write"))
			return Reports::get_report_clients($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_report_dealers($request){
			if(self::check_role(11,"report_dealers","write"))
			return Reports::get_report_dealers($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_payments_report($request){
			if(self::check_role(11,"payments_report","write"))
			return Reports::get_payments_report($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_nelikvid_report($request){
			if(self::check_role(11,"nelikvid_report","write"))
			return Reports::get_nelikvid_report($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_nelikvid_clients($request){
			if(self::check_role(11,"nelikvid_clients_report","write"))
			return Reports::get_nelikvid_clients($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_limit_zakupok_report($request){
			//if(self::check_role(11,"report_profit","write"))
			return Reports::get_limit_zakupok_report($request);
			//else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_plan_report($request){
			//if(self::check_role(11,"report_profit","write"))
			return Reports::get_plan_report($request);
			//else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_plan_report_reestr($request){
			//if(self::check_role(11,"report_profit","write"))
			return Reports::get_plan_report_reestr($request);
			//else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_plan_report_reestr($request){
			//if(self::check_role(11,"report_profit","write"))
			return Reports::save_plan_report_reestr($request);
			//else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_plan_month_balance($request){
			//if(self::check_role(11,"report_profit","write"))
			return Reports::get_plan_month_balance($request);
			//else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_fill_sklad_min_count_by_sale_goods($request){
			return Reports::fill_sklad_min_count_by_sale_goods($request);
		}

		public static function action_save_sklad_detail_location($request){
			if(self::check_role(6,"sklad","write"))
			return SkladDetailLocations::save_sklad_detail_location($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_sklad_detail_location($request){
			if(self::check_role(6,"sklad","read"))
			return SkladDetailLocations::get_sklad_detail_location($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_sklad_detail_location($request){
			if(self::check_role(6,"sklad","write"))
			return SkladDetailLocations::delete_sklad_detail_location($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_online_profiles($request){
			return OnlineProfiles::get_online_profiles($request);
		}

		public static function action_get_online_profile($request){
			return OnlineProfiles::get_online_profile($request);
		}

		public static function action_save_online_profile($request){
			if(self::check_role(9,"online_profiles","write"))
			return OnlineProfiles::save_online_profile($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_online_profile($request){
			if(self::check_role(9,"online_profiles","write"))
			return OnlineProfiles::delete_online_profile($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_company_online_profiles($request){
			return OnlineProfiles::get_company_online_profiles($request);
		}

		public static function action_get_company_online_profile($request){
			return OnlineProfiles::get_company_online_profile($request);
		}

		public static function action_save_company_online_profile($request){
			if(self::check_role(9,"online_profiles","read"))
			return OnlineProfiles::save_company_online_profile($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_system_messages($request){
			return SystemMessages::get_system_messages($request);
		}


		public static function action_get_orders($request){
			if(self::check_role(3,"orders","read"))
			return Search::get_orders($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_deliverers_list($request){
			return OnlineProfiles::get_deliverers_list($request);
		}

		public static function action_get_deliverers_list_wzdc($request){
			return OnlineProfiles::get_deliverers_list_wzdc($request);
		}

		public static function action_get_invents($request) {
			if(self::check_role(10,"document_invent","read"))
			return Invents::get_invents($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_invent_details_xls($request) {
			if(self::check_role(10,"document_invent","read"))
			return Invents::get_invent_details_xls($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_invent($request) {
			if(self::check_role(10,"document_invent","read"))
			return Invents::get_invent($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_add_invent_user($request) {
			if(self::check_role(10,"document_invent","write"))
			return Invents::add_invent_user($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_invent($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::save_invent($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_invent($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::delete_invent($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_start_invent($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::start_invent($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_invent_details($request){
			if(self::check_role(10,"document_invent","read"))
			return Invents::get_invent_details($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_invent_detail($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::save_invent_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_invent_submit($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::invent_submit($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_add_invent_detail_to_start($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::add_invent_detail_to_start($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_invent_detail_processed($request){
			if(self::check_role(10,"document_invent","write"))
			return Invents::invent_detail_processed($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_bugs($request){
            return Bugs::get_bugs($request);
        }

		public static function action_get_faqs($request){
            return Bugs::get_faqs($request);
        }

        public static function action_get_bug($request){
            return Bugs::get_bug($request);
        }

		public static function action_get_faq($request){
            return Bugs::get_faq($request);
        }

		public static function action_save_bug($request){
				return Bugs::save_bug($request);
		}

		public static function action_delete_bug($request){
				return Bugs::delete_bug($request);
		}

		public static function action_save_bug_comment($request){
				return Bugs::save_bug_comment($request);
		}

		public static function action_upload_bug_file($request){
				return Bugs::upload_bug_file($request);
		}

		public static function action_get_proposals($request){
				return Proposals::get_proposals($request);
		}

		public static function action_get_proposal($request){
				return Proposals::get_proposal($request);
		}

		public static function action_save_proposal($request){
				return Proposals::save_proposal($request);
		}

		public static function action_save_proposal_comment($request){
				return Proposals::save_proposal_comment($request);
		}

		public static function action_upload_proposal_file($request){
				return Proposals::upload_proposal_file($request);
		}
		
		public static function action_save_gtd($request){
			return GTDs::save_gtd($request);
		}

		public static function action_get_document_det_gtd($request){
			return GTDs::get_document_det_gtd($request);
		}

		public static function action_delete_document_det_gtd($request){
			return GTDs::delete_document_det_gtd($request);
		}

		public static function action_get_1c_export_file($request){
			if(self::check_role(10,"document_export","read"))
			return Documents::get_1c_export_file($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_ofds($request){
			return OFDs::get_ofds($request);
		}

		public static function action_get_ofd_env($request){
			return OFDs::get_ofd_env($request);
		}

		public static function action_save_ofd_kassa($request){
			if(self::check_role(9,"OFD","write"))
			return OFDs::save_ofd_kassa($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_ofd_kassa($request){
			if(self::check_role(9,"OFD","write"))
			return OFDs::delete_ofd_kassa($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_shift_data($request){
			if(self::check_role(9,"OFD","write"))
			return OFDs::save_shift_data($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_ofd_kassas($request){
			if(self::check_role(9,"OFD","read"))
			return OFDs::get_ofd_kassas($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_active_kassas($request){
			if(self::check_role(9,"OFD","read"))
			return OFDs::get_active_kassas($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_acquiring_operators($request){
			return Acquirings::get_acquiring_operators($request);
		}

		public static function action_save_acquiring($request){
			if(self::check_role(9,"OFD","write"))
			return Acquirings::save_acquiring($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_acquirings($request){
			if(self::check_role(9,"OFD","write"))
			return Acquirings::get_acquirings($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_sklad_fill($request){
			return SkladDetails::get_sklad_fill($request);
		}

		public static function action_get_service_jobs($request){
			return ServiceJobs::get_service_jobs($request);
		}
	
		public static function action_get_service_job($request){
			return ServiceJobs::get_service_job($request);
		}
	
		public static function action_save_service_job($request){
			return ServiceJobs::save_service_job($request);
		}

		public static function action_save_jobes($request){
			return ServiceJobs::save_jobes($request);
		}
	
		public static function action_delete_service_job($request){
			return ServiceJobs::delete_service_job($request);
		}

		public static function action_get_service_employees($request){
			return ServiceEmployees::get_service_employees($request);
		}
	
		public static function action_get_service_employee($request){
			return ServiceEmployees::get_service_employee($request);
		}
	
		public static function action_save_service_employee($request){
			return ServiceEmployees::save_service_employee($request);
		}
	
		public static function action_delete_service_employee($request){
			return ServiceEmployees::delete_service_employee($request);
		}

		public static function action_get_service_workplaces($request){
			return ServiceWorkplaces::get_service_workplaces($request);
		}
	
		public static function action_get_service_workplace($request){
			return ServiceWorkplaces::get_service_workplace($request);
		}
	
		public static function action_save_service_workplace($request){
			return ServiceWorkplaces::save_service_workplace($request);
		}
	
		public static function action_delete_service_workplace($request){
			return ServiceWorkplaces::delete_service_workplace($request);
		}

		public static function action_get_service_notes($request){
			return ServiceNotes::get_service_notes($request);
		}
	
		public static function action_get_service_note($request){
			return ServiceNotes::get_service_note($request);
		}
	
		public static function action_save_service_note($request){
			return ServiceNotes::save_service_note($request);
		}
	
		public static function action_delete_service_note($request){
			return ServiceNotes::delete_service_note($request);
		}

		public static function action_save_local_cross($request){
			if(self::check_role(1,"add_cross","write"))
				return LocalCrosses::save_local_cross($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_local_crosses($request){
			if(self::check_role(1,"add_cross","write"))
				return LocalCrosses::save_local_crosses($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_local_crosses($request){
			return LocalCrosses::get_local_crosses($request);
		}

		public static function action_get_local_cross($request){
			return LocalCrosses::get_local_cross($request);
		}

		public static function action_delete_local_cross($request){
			if(self::check_role(1,"add_cross","delete"))
				return LocalCrosses::delete_local_cross($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_new_in_sort1($request){
			if($_SESSION['user_id']==66)
				return NewInSort1s::save_new_in_sort1($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_new_in_sort1s($request){
			return NewInSort1s::get_new_in_sort1s($request);
		}

		public static function action_get_new_in_sort1s_unread_count($request){
			return NewInSort1s::get_new_in_sort1s_unread_count($request);
		}

		public static function action_get_new_in_sort1($request){
			return NewInSort1s::get_new_in_sort1($request);
		}

		public static function action_delete_new_in_sort1($request){
			if($_SESSION['user_id']==66)
				return NewInSort1s::delete_new_in_sort1($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_new_in_sort1_comment($request){
			return NewInSort1s::save_new_in_sort1_comment($request);
		}

		public static function action_upload_new_in_sort1_file($request){
				return NewInSort1s::upload_new_in_sort1_file($request);
		}

		public static function action_get_services($request) {
			if(self::check_role(8,"service_notes","read"))
				return Services::get_services($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
	
		public static function action_get_service($request) {
			if(self::check_role(8,"service_notes","read"))
				return Services::get_service($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
	
		public static function action_save_service($request) {
			if(self::check_role(8,"service_notes","write"))
				return Services::save_service($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_service($request) {
			if(self::check_role(8,"service_notes","write"))
				return Services::delete_service($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_detail_group($request){
			return DetailGroups::save_detail_group($request);
		}

		public static function action_get_detail_groups($request){
			return DetailGroups::get_detail_groups($request);
		}

		public static function action_add_detail_group_library($request){
			return DetailGroups::add_detail_group_library($request);
		}

		public static function action_move_detail_group($request){
			return DetailGroups::move_detail_group($request);
		}

		public static function action_get_detail_categorys($request){
			return DetailCategorys::get_detail_categorys($request);
		}

		public static function action_get_detail_categorys_market($request){
			return DetailCategorys::get_detail_categorys_market($request);
		}

		public static function action_get_uri_data_market($request){
			return DetailCategorys::get_uri_data_market($request);
		}

		public static function action_get_all_categorys($request){
			return DetailCategorys::get_all_categorys($request);
		}

		public static function action_get_category_parents($request){
			return DetailCategorys::get_category_parents($request);
		}

		public static function action_get_uri_data($request){
			return DetailCategorys::get_uri_data($request);
		}

		public static function action_get_detail_group($request){
			return DetailGroups::get_detail_group($request);
		}

		public static function action_get_detail_group_details($request){
			return DetailGroups::get_detail_group_details($request);
		}

		public static function action_get_detail_group_details_from_sklad($request){
			return DetailGroups::get_detail_group_details_from_sklad($request);
		}

		public static function action_add_detail_group_detail_to_start($request){
			return DetailGroups::add_detail_group_detail_to_start($request);
		}

		public static function action_delete_detail_group_detail($request){
			return DetailGroups::delete_detail_group_detail($request);
		}

		public static function action_delete_detail_group($request){
			return DetailGroups::delete_detail_group($request);
		}

		public static function action_get_zakaz_commit($request){
			return Zakazs::get_zakaz_commit($request);
		}

		public static function action_save_zakaz_commit($request){
			return Zakazs::save_zakaz_commit($request);
		}

		public static function action_get_document_set_price($request){
			if(self::check_role(9,"document_settings","read") && self::check_role(9,"document_settings","show"))
				return Documents::get_document_set_price($request);
			else
				return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_document_set_price($request){
			if(self::check_role(9,"document_settings","write"))
				return Documents::save_document_set_price($request);
			else
				return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_cash_desk($request){
			if(self::check_role(4,"cashdesk","write"))
			return CashDesks::save_cash_desk($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_cash_desk($request){
			if(self::check_role(4,"cashdesk","read"))
			return CashDesks::get_cash_desk($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_cash_desks($request){
			if(self::check_role(4,"cashdesk","show"))
			return CashDesks::get_cash_desks($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_delete_cash_desk($request){
			if(self::check_role(4,"cashdesk","delete"))
			return CashDesks::delete_cash_desk($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_cash_desk_history($request){
			if(self::check_role(4,"cashdesk","read"))
			return CashDesks::get_cash_desk_history($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_cash_desk_sverka($request){
			if(self::check_role(4,"cashdesk","read"))
			return CashDesks::get_cash_desk_sverka($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_encashment($request){
			if(self::check_role(4,"cashdesk","write"))
			return Encashments::save_encashment($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_encashment($request){
			if(self::check_role(4,"cashdesk","read"))
			return Encashments::get_encashment($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_encashments($request){
			if(self::check_role(4,"cashdesk","show"))
			return Encashments::get_encashments($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_delete_encashment($request){
			if(self::check_role(4,"cashdesk","delete"))
			return Encashments::delete_encashment($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_RKO($request){
			if(self::check_role(4,"RKO","write"))
			return RKOs::save_RKO($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_RKO($request){
			if(self::check_role(4,"RKO","read"))
			return RKOs::get_RKO($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_RKOs($request){
			if(self::check_role(4,"RKO","show"))
			return RKOs::get_RKOs($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_delete_RKO($request){
			if(self::check_role(4,"RKO","delete"))
			return RKOs::delete_RKO($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_PKO($request){
			if(self::check_role(4,"PKO","write"))
				return PKOs::save_PKO($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_PKO($request){
			if(self::check_role(4,"PKO","read"))
				return PKOs::get_PKO($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_get_PKOs($request){
			if(self::check_role(4,"PKO","show"))
			return PKOs::get_PKOs($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
		public static function action_delete_PKO($request){
			if(self::check_role(4,"PKO","delete"))
			return PKOs::delete_PKO($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_marketing_channel($request){
			return MarketingChannels::save_marketing_channel($request);
		}
		public static function action_get_marketing_channel($request){
			return MarketingChannels::get_marketing_channel($request);
		}
		public static function action_get_marketing_channels($request){
			return MarketingChannels::get_marketing_channels($request);
		}
		public static function action_delete_marketing_channel($request){
			return MarketingChannels::delete_marketing_channel($request);
		}

		public static function action_get_sklad_prices($request) {
			if(self::check_role(10,"document_invent","read"))
			return SkladPrices::get_sklad_prices($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_sklad_price($request) {
			if(self::check_role(10,"document_invent","read"))
			return SkladPrices::get_sklad_price($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_sklad_price($request){
			if(self::check_role(10,"document_invent","write"))
			return SkladPrices::save_sklad_price($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_delete_sklad_price($request){
			if(self::check_role(10,"document_invent","write"))
			return SkladPrices::delete_sklad_price($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
	
		public static function action_get_sklad_price_details($request){
			if(self::check_role(10,"document_invent","read"))
			return SkladPrices::get_sklad_price_details($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_save_sklad_price_detail($request){
			if(self::check_role(10,"document_invent","write"))
			return SkladPrices::save_sklad_price_detail($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}
	
		public static function action_add_sklad_price_detail_to_start($request){
			if(self::check_role(10,"document_invent","write"))
			return SkladPrices::add_sklad_price_detail_to_start($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_zakaz_footers($request){
			return ZakazFooters::get_zakaz_footers($request);
		}

		public static function action_get_zakaz_footer($request){
			return ZakazFooters::get_zakaz_footer($request);
		}

		public static function action_save_zakaz_footer($request){
			return ZakazFooters::save_zakaz_footer($request);
		}

		public static function action_delete_zakaz_footer($request){
			return ZakazFooters::delete_zakaz_footer($request);
		}

		public static function action_get_zakaz_garants($request){
			return ZakazGarants::get_zakaz_garants($request);
		}

		public static function action_get_zakaz_garant($request){
			return ZakazGarants::get_zakaz_garant($request);
		}

		public static function action_save_zakaz_garant($request){
			return ZakazGarants::save_zakaz_garant($request);
		}

		public static function action_delete_zakaz_garant($request){
			return ZakazGarants::delete_zakaz_garant($request);
		}

		public static function action_get_stock_balances($request){
			return Sklads::get_stock_balances($request);
		}

		public static function action_update_company_balance($request){
			return Companys::update_company_balance($request);
		}

		public static function action_save_price_export($request){
			return PriceExports::save_price_export($request);
		}

			public static function action_get_details_from_without_category($request){
				return PriceExports::get_details_from_without_category($request);
			}

			public static function action_get_price_exports($request){
				return PriceExports::get_price_exports($request);
			}

		public static function action_set_price_export_show_price_name($request){
			return PriceExports::set_price_export_show_price_name($request);
		}

		public static function action_get_price_export($request){
			return PriceExports::get_price_export($request);
		}

		public static function action_delete_price_export($request){
			return PriceExports::delete_price_export($request);
		}

		public static function action_get_export_file($request){
			require '../vendor/autoload.php';
			return PriceExports::get_export_file($request);
		}

		public static function action_send_export_file_to_email($request){
			return PriceExports::send_export_file_to_email($request);
		}

		public static function action_kick_user($request){
			return Auth::kick_user($request);
		}

		public static function action_clear_unused_details($request){
			return Sklads::clear_unused_details($request);
		}

		public static function action_get_marketplaces($request){
			return Marketplaces::get_marketplaces($request);
		}

		public static function action_bind_market_to_sort1_zakaz($request){
			return Marketplaces::bind_market_to_sort1_zakaz($request);
		}

		public static function action_get_marketplace_config($request){
			return Marketplaces::get_marketplace_config($request);
		}

		public static function action_save_marketplace_config($request){
			return Marketplaces::save_marketplace_config($request);
		}

		public static function action_check_marketplace_config($request){
			return Marketplaces::check_marketplace_config($request);
		}

		public static function action_get_seller_orders($request){
			return Marketplaces::get_seller_orders($request);
		}

		public static function action_create_zakaz_sort1($request){
			return Marketplaces::create_zakaz_sort1($request);
		}

		public static function action_check_zakaz_in_sort1($request){
			return Marketplaces::check_zakaz_in_sort1($request);
		}

		public static function action_get_all_chat_messages_market($request){
			return Marketplaces::get_all_chat_messages_market($request);
		}

		public static function action_send_message_market($request){
			return Marketplaces::send_message_market($request);
		}

		public static function action_get_market_zakazes($request){
			return MarketZakazs::get_market_zakazes($request);
		}

		public static function action_get_market_zakaz($request){
			return MarketZakazs::get_market_zakaz($request);
		}

		public static function action_delete_market_zakaz($request){
			if(self::check_role(3,"client_zakazs","delete") || $_SESSION['roles']==10)
			return MarketZakazs::delete_market_zakaz($request);
			else return self::_error("Недостаточно прав для выполнения данного действия");
		}

		public static function action_get_market_zakaz_statuses($request){
			return MarketZakazs::get_market_zakaz_statuses($request);
		}

		public static function action_get_market_zakaz_details($request){
			return MarketZakazDetails::get_market_zakaz_details($request);
		}

		public static function action_get_market_zakaz_detail($request){
			return MarketZakazDetails::get_market_zakaz_detail($request);
		}

		public static function action_save_market_zakaz_detail($request){
			return MarketZakazDetails::save_market_zakaz_detail($request);
		}
		// public static function action_get_market_zakazs($request){
		// 	return MarketZakazs::get_market_zakazs($request);
		// }

		public static function action_get_timezones($request){
			return Timezones::get_timezones($request);
		}

		public static function action_save_zakaz_order($request){
			return ZakazDetails::save_zakaz_order($request);
		}

		public static function action_get_price_export_cron($request){
			return PriceExports::get_price_export_cron($request);
		}

		public static function action_save_price_export_cron($request){
			return PriceExports::save_price_export_cron($request);
		}

		public static function action_get_price_list_cron($request){
			return PriceLists::get_price_list_cron($request);
		}

		public static function action_save_price_list_cron($request){
			return PriceLists::save_price_list_cron($request);
		}

		public static function action_get_ftv_id($request){
			return FindToVinConfigs::get_ftv_id($request);
		}

		public static function action_get_ftv($request){
			return FindToVinConfigs::get_ftv($request);
		}

		public static function action_save_ftv_config($request){
			return FindToVinConfigs::save_ftv_config($request);
		}

		public static function action_get_ftv_config($request){
			return FindToVinConfigs::get_ftv_config($request);
		}

		public static function action_get_ftv_config_for_site($request){
			return FindToVinConfigs::get_ftv_config_for_site($request);
		}

		public static function action_get_logistics($request){
			return LogisticsCompanyConfigs::get_logistics($request);
		}

		public static function action_save_logistic_config($request){
			return LogisticsCompanyConfigs::save_logistic_config($request);
		}

		public static function action_get_logistic_config($request){
			return LogisticsCompanyConfigs::get_logistic_config($request);
		}

		public static function action_check_test_config($request){
			return LogisticsCompanyConfigs::check_test_config($request);
		}

		public static function action_calculate_shipping_cost_Dostavista($request){
			return LogisticsCompanyConfigs::calculate_shipping_cost_Dostavista($request);
		}

		public static function action_get_name_logistic_config($request){
			return LogisticsCompanyConfigs::get_name_logistic_config($request);
		}

		public static function action_save_order_logistic($request){
			return LogisticsCompanyConfigs::save_order_logistic($request);
		}

		public static function action_send_order_logistic($request){
			return LogisticsCompanyConfigs::send_order_logistic($request);
		}

			public static function action_refresh_order_logistic_status($request){
				return LogisticsCompanyConfigs::refresh_order_logistic_status($request);
			}

			public static function action_get_details_weights($request){
				return LogisticsCompanyConfigs::get_details_weights($request);
			}

			public static function action_increase_request_sort1($request){
				return Laximo::increase_request_sort1($request);
			}


		public static function action_get_car_by_vin($request){
			$car=new CompanyCar();
			return Laximo::getCarByVin($request->vin,$car);
		}

		public static function action_get_car_by_plate($request){
			$car=new CompanyCar();
			return Laximo::getCarByPlateNumber($request->plateNumber,$car);
		}

		public static function action_get_my_company_users_basket($request) {
			return Users::get_my_company_users_basket($request);
		}

		public static function action_unload_basket_user($request){
			return BasketDetails::unload_basket_user($request);
		}

		public static function action_get_diagnostic_card($request){
			return DiagnosticCards::get_diagnostic_card($request);
		}

		public static function action_save_diagnostic_card($request){
			return DiagnosticCards::save_diagnostic_card($request);
		}

		public static function action_get_brands_wiki($request){
			return WikiCross::get_brands_wiki($request);
		}

		public static function action_get_marks_wiki($request){
			return WikiCross::get_marks_wiki($request);
		}

		public static function action_get_categorys_wiki($request){
			return WikiCross::get_categorys_wiki($request);
		}

		public static function action_get_favorite_details($request){
			return FavoriteDetails::get_favorite_details($request);
		}

		public static function action_add_favorite_detail($request){
			return FavoriteDetails::add_favorite_detail($request);
		}

		public static function action_delete_favorite_detail($request){
			return FavoriteDetails::delete_favorite_detail($request);
		}

		public static function action_add_skidka_to_zakaz($request){
			return Zakazs::add_skidka_to_zakaz($request);
		}

		public static function action_notify_client($request){
			return Zakazs::notify_client($request);
		}

		public static function action_get_settings_avito($request){
			return MarketplaceCategorys::get_settings_avito($request);
		}

		public static function action_get_unlinked_avito_categorys($request){
			return MarketplaceCategorys::get_unlinked_avito_categorys($request);
		}

		public static function action_toggle_marketplace_unbinding($request){
			return MarketplaceCategorys::toggle_marketplace_unbinding($request);
		}

		public static function action_toggle_marketplace_binding($request){
			return MarketplaceCategorys::toggle_marketplace_binding($request);
		}

		public static function action_get_avito_categorys($request){
			return MarketplaceCategorys::get_avito_categorys($request);
		}

		public static function action_save_category_avito($request){
			return MarketplaceCategorys::save_category_avito($request);
		}

		public static function action_get_detail_group_details_from_sklad_binding($request){
			return DetailGroups::get_detail_group_details_from_sklad_binding($request);
		}

		public static function action_get_detail_group_details_from_price_list_binding($request){
			return DetailGroups::get_detail_group_details_from_price_list_binding($request);
		}

		public static function action_set_undefined_details_group($request){
			return DetailGroups::set_undefined_details_group($request);
		}

		public static function action_form_guide_sort1($request){
			return BilingGuides::form_guide_sort1($request);
		}

		public static function action_shipping_cost_calculation_cdek($request){
			return CdekApi::shipping_cost_calculation($request);
		}

		public static function action_login_wiki($request){
			//$sessionid=session_id();
			if(WikiCross::login_wiki($request)){
				$ret['status']="ok";
				$ret['err']="";
				$ret['sesskey']=session_id();
				return $ret;
			}
			else {
				//print_r(Auth::$_err_msg);
				if(WikiCross::$_err_msg!="") return self::_error(WikiCross::$_err_msg); 
				else return self::_error("Не правильные данные пользователя");
			}
	
		}

		public static function action_get_all_categorys_market($request){
			return DetailCategorys::get_all_categorys_market($request);
		}

		public static function action_search_brand_crossdata($request){
			return Crossdatas::search_brand_crossdata($request);
		}

		public static function action_search_categorys_crossdata($request){
			return Crossdatas::search_categorys_crossdata($request);
		}

		public static function action_upload_images_crossdata($request){
			return Crossdatas::upload_images_crossdata($request);
		}

		public static function action_get_email_configs($request){
			return EmailConfigs::get_email_configs($request);
		}
		public static function action_get_email_config($request){
			return EmailConfigs::get_email_config($request);
		}
		public static function action_test_email_config($request){
			return EmailConfigs::test_email_config($request);
		}
		public static function action_save_email_config($request){
			return EmailConfigs::save_email_config($request);
		}
		public static function action_get_detail_photos($request){
			return SkladDetails::get_detail_photos($request);
		}
		public static function action_toggle_photo_active($request){
			return SkladDetails::toggle_photo_active($request);
		}
		public static function action_toggle_photo_public($request){
			return SkladDetails::toggle_photo_public($request);
		}
		public static function action_upload_detail_images($request){
			return SkladDetails::upload_detail_images($request);
		}
		public static function action_delete_detail_photo($request){
			return SkladDetails::delete_detail_photo($request);
		}
		public static function action_invent_sklad($request){
			return Sklads::invent_sklad($request);
		}

		public static function action_save_detail_mark($request){
			return DetailMarks::save_detail_mark($request);
		}

		public static function action_get_detail_mark($request){
			return DetailMarks::get_detail_mark($request);
		}

		public static function action_get_document_detail_marks($request){
			return DetailMarks::get_document_detail_marks($request);
		}

		public static function action_delete_detail_mark($request){
			return DetailMarks::delete_detail_mark($request);
		}

		// B2B Client Portal actions
		public static function action_register_legal_entity($request){
			return B2BClientService::register_legal_entity($request);
		}

		public static function action_get_client_finance($request){
			return B2BClientService::get_finance_info($request);
		}

		public static function action_get_client_payments($request){
			return B2BClientService::get_my_payments($request);
		}

		public static function action_get_client_shipments($request){
			return B2BClientService::get_shipments($request);
		}

		public static function action_get_client_returns($request){
			return B2BClientService::get_returns($request);
		}

		public static function action_download_akt_sverki($request){
			return B2BClientService::generate_akt_sverki($request);
		}

		public static function action_print_client_invoice($request){
			return B2BClientService::print_invoice($request);
		}

		public static function action_print_client_upd($request){
			return B2BClientService::print_upd($request);
		}
}


?>
