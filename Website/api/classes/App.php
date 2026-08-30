<?php


namespace Sort1API;

use Sort1API\Components\DB;
use Sort1API\Components\Request;
use Sort1API\Components\Response;
use Sort1API\Components\Logger;
use Sort1API\Components\Config;
use Sort1API\Components\Auth;
use Sort1API\Components\User;
use Sort1API\Components\Controllers\Controller;
use Sort1API\Components\Routers\Router;

define('E_FATAL',  E_ERROR | E_USER_ERROR | E_PARSE | E_CORE_ERROR |
        E_COMPILE_ERROR | E_RECOVERABLE_ERROR);

//require '../vendor/autoload.php';
class App {

	public static $ROOT = null;
	public static $OUTPUT = 1;
	public static $EXTERNAL_SCRIPT = 0;

	public static function run() {
		//////////////////
		// Main part of Application
		//////////////////

		//////////////////
		// 1) Pre-Run:
		//////////////////
		if (empty(self::$ROOT))
			self::$ROOT = realpath(__DIR__."/../").DIRECTORY_SEPARATOR;

		//register autoload classes:
		self::_register_autoload();

		//register error handler func:
		set_error_handler('\\Sort1API\\App::_error_handler');

		//register shutdown func:
		register_shutdown_function('\\Sort1API\\App::_shutdown');



		//////////////////
		// 2) Main stream:
		//////////////////

		//get request instance:
		$request = Request::getInstance();
		//echo print_r($request,true);
		//create response instance:
		$response = new Response;
		$response->set_type($request->get_type());

		//check REQUEST params (if the request is valid), using AUTH class:

		//check method

		if (!Auth::check_method($request) && !self::$EXTERNAL_SCRIPT) {
			$response->set_content(["status"=>"err","err"=>"Метод не поддерживается"]);
			$response->set_http_code(405);
			if(self::$OUTPUT)  return $response->output();
		} 
		//echo print_r($request,true);
		if(!empty($request->token)) $request->api_key=$request->token;
		if(isset($request->api_key) && !Auth::api_login($request)){
			$response->set_content(["status"=>"err","err"=>"Неверный ключ API"]);
			$response->set_http_code(403);
			if(self::$OUTPUT) return $response->output();
		}

		//check ip if not return error
		//if (!Auth::check_ip($request)) {
		//	$response->set_content(["status"=>"err","err"=>"Доступ запрещен"]);
		//	$response->set_http_code(403);
		//	return $response->output();
		//}
		if($request->state('method')=="OPTIONS"){
		    $response->set_http_code(200);
		    header("Access-Control-Allow-Method: GET, POST");
		    header("Access-Control-Allow-Headers: *");
		    header("Access-Control-Allow-Origin: *");
		    if(self::$OUTPUT) return $response->options_output();
		}

		//if (isset($request->sesskey)) session_id($request->sesskey);

		//Функции разрешенные без авторизации
		$request_actions_white_list=array("login",
			"user_login",
			"login_wiki",
			"register_user",
			"register_user_wiki",
			"register_callback",
			"get_site_data",
			"get_api_ver_plugins",
			"delete_basket_detail",
			"send_mphone_confirm_code",
			"get_seed",
			"kick_user",
			"get_all_categorys",
			"get_brands_wiki",
			"get_categorys_wiki",
			"get_marks_wiki",
			"get_brand_details_wiki",
			"get_detail_info_wiki",
			"get_crosses_wiki",
			"get_category_data_wiki",
			"search_details_wiki",
			"add_favorite_detail",
			"get_favorite_details",
			"get_role_wiki",
			"delete_favorite_detail",
			"get_reviews_wiki",
			"get_market_captcha",
			"form_guide_sort1",
			"get_detail_info_market",
		);
		$action = $request->action;
		if (empty($action)) {
			if($route_action=Router::route_exist()){
				$action=$route_action;
			}
			else {
				if(!self::$EXTERNAL_SCRIPT){
					$response->set_content(["status"=>"err","err"=>"Не задано действие1"]);
					$response->set_http_code(400);
				}
				if(self::$OUTPUT) return $response->output();
			}
		}
		if (!Auth::login($request) && !in_array($action,$request_actions_white_list) && !self::check_internet_shop() && !self::check_jetparts()) {
			$response->set_content(["status"=>"err","err"=>"Auth need"]);
			$response->set_http_code(403);
			header("Access-Control-Allow-Method: GET, POST");
			header("Access-Control-Allow-Headers: *");
			header("Access-Control-Allow-Origin: *");
			if(self::$OUTPUT) return $response->output();
		}

		//check login/pass

		//No login/pass check, only internal requests:
		/*
		if(!Auth::check_auth($request)) {
			$response->set_content(["status"=>"err","err"=>"Неверный логин/пароль"]);
			$response->set_http_code(401);
			return $response->output();
		}
		*/

		//////
		// Get action, check it if exists and translate to Controller:
		//////

 
		

		if (!Controller::action_exists($action) && !self::$EXTERNAL_SCRIPT) {
			$response->set_content(["status"=>"err","err"=>"Не правильно задано действие"]);
			$response->set_http_code(400);
			if(self::$OUTPUT) return $response->output();
		}

		//echo print_r($request,true);
		$tmp1 = "action_".$action;
		if(self::$OUTPUT) $result = Controller::{$tmp1}($request);



		// Send results from Controller to Response's body and exit after outputs the response

		$response->set_content($result);
		$response->set_http_code(200);
		header("Access-Control-Allow-Method: GET, POST");
		header("Access-Control-Allow-Headers: *");
		header("Access-Control-Allow-Origin: *");
		if(self::$OUTPUT) return $response->output();


	}

	private static function _register_autoload() {
		if (empty(self::$ROOT))
			self::$ROOT = realpath(__DIR__."/../").DIRECTORY_SEPARATOR;

        spl_autoload_register(function ($class) {

            $class = str_replace("Sort1API\\","classes\\",$class);

            //echo $class."\n";


            $file = self::$ROOT.str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
	    //'

            //echo $file."\n";

            if (file_exists($file)) {
                require_once $file;
                return true;
            }
            return false;
        });
	}

	public static function _error_handler($errno, $errstr, $errfile, $errline) {

	   switch ($errno) {
			case E_ERROR: // 1 //
				$typestr = 'E_ERROR'; break;
			case E_WARNING: // 2 //
				$typestr = 'E_WARNING'; break;
			case E_PARSE: // 4 //
				$typestr = 'E_PARSE'; break;
			case E_NOTICE: // 8 //
				$typestr = 'E_NOTICE'; break;
			case E_CORE_ERROR: // 16 //
				$typestr = 'E_CORE_ERROR'; break;
			case E_CORE_WARNING: // 32 //
				$typestr = 'E_CORE_WARNING'; break;
			case E_COMPILE_ERROR: // 64 //
				$typestr = 'E_COMPILE_ERROR'; break;
			case E_CORE_WARNING: // 128 //
				$typestr = 'E_COMPILE_WARNING'; break;
			case E_USER_ERROR: // 256 //
				$typestr = 'E_USER_ERROR'; break;
			case E_USER_WARNING: // 512 //
				$typestr = 'E_USER_WARNING'; break;
			case E_USER_NOTICE: // 1024 //
				$typestr = 'E_USER_NOTICE'; break;
			case E_STRICT: // 2048 //
				$typestr = 'E_STRICT'; break;
			case E_RECOVERABLE_ERROR: // 4096 //
				$typestr = 'E_RECOVERABLE_ERROR'; break;
			case E_DEPRECATED: // 8192 //
				$typestr = 'E_DEPRECATED'; break;
			case E_USER_DEPRECATED: // 16384 //
				$typestr = 'E_USER_DEPRECATED'; break;
		}
		$message = '<b>'.$typestr.': </b>'.$errstr.' in <b>'.$errfile.'</b> on line <b>'.$errline.'</b><br/>';

		if (!($errno & Config::get("app-error-reporting")))
			return;

		//Logging error on php file error log...
		if (Config::get("app-error-log")) {
			Logger::error(strip_tags($message));
			// error_log(strip_tags($message), 0);
		}
	}

	public static function _shutdown() {
		$error = error_get_last();
		if($error && ($error['type'] & E_FATAL)){
			self::_error_handler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}

	private static function check_internet_shop(){
		$db = DB::getInstance();
		$request = Request::getInstance();
		$actions=array("get_brands",
						"search_by_article",
						"search_sort1",
						"save_basket_detail",
						"get_basket_details",
						"get_basket_count",
						"get_popular_details",
						"search_crosses",
						"get_detail_info",
						"get_uri_data",
						"get_detail_categorys",
						"get_category_parents",
						"search_categorys",
						"get_all_details_sklad",
						"get_delivery_sklads",
						"change_my_sklad",
						"get_current_sklad_shop",
						"recover_password_email_shop",
						"get_colors_site",
						"get_ftv_config_for_site",
						"get_laximo_data");
		if(session_id()==="") session_start();
		preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
		$sql="select company_id from company_sites where site_name=?s";
        $main_company_id=$db->getOne($sql,str_replace("www.","",$origin[1]));
        if((int)$main_company_id>0 && in_array($request->action,$actions)){
			$_SESSION['main_company']=(int)$main_company_id;
			$_SESSION['roles']=10;
			return true;
		}
		else return false;
	}

	private static function check_jetparts(){
		$db = DB::getInstance();
		$request = Request::getInstance();
		$actions=array("register_user_market",
						"user_login_market",
						"search_by_article_market",
						"get_city",
						"set_city_user",
						"get_citys",
						"get_current_city",
						"get_detail_categorys_market",
						"get_detail_info_market",
						"save_basket_detail_market",
						"search_crosse6s_by_article_market",
						"get_seller",
						"recover_password_email_market",
						"search_crosses_market",
						"get_details_info_market",
						"get_all_details_sklad_market",
						"get_uri_data_market",
						"search_by_words_market",
						"get_all_categorys_market");
		if(session_id()==="") session_start();
		preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
		$sql="select company_id from company_sites where site_name=?s";
        $main_company_id=$db->getOne($sql,str_replace("www.","",$origin[1]));
        if((int)$main_company_id>0 && in_array($request->action,$actions)){
			$_SESSION['main_company']=(int)$main_company_id;
			$_SESSION['roles']=20;
			return true;
		}
		else return false;
	}


}



?>
