<?php

namespace Sort1API\Components;
use Sort1API\Components\Sort1;
use Sort1API\Components\Models\Sort1s;
use Sort1API\Components\User;
use Sort1API\Components\Basket;

class Auth {

	private static $_is_auth = false;
	private static $_client = null;
	public static $_err_msg = "";

	public static function get_err_msg(){
		return $_err_msg;
	}

	public static function check_method($request) {

		if ( in_array($request->state('method'), explode(",", Config::get('http-method-allow'))) || Config::get('http-method-allow')=="*")
			return true;

		return false;
	}


	public static function check_ip($request) {
		if ( in_array($request->state('client_ip'), explode(",", Config::get('http-ip-allow'))) || Config::get('http-ip-allow')=="*")
			return true;

		return false;
	}

/**
*  Function depends on server (sql-auth or smth else)
*
* @param Request $request
*
*
* @return bool
*/

	public static function get_current_company(){
		$db = DB::getInstance();
		$company=$db->getRow("select id,name,mphone,address,email from company where id=?i",$_SESSION['company_id']);
		return array("status"=>"ok","current_company"=>$company);
	}

 	public static function change_company($request){
		$db = DB::getInstance();
		$is_your_company=$db->getRow("select company_id,main_company_id from user_companys where company_id=?i and user_id=?i",(int)$request->company_id,$_SESSION['user_id']);
		//echo print_r($is_your_company,true);
		if($_SESSION['roles']<10){
			if ((int)$is_your_company['company_id']>0){
					if((int)$is_your_company['main_company_id']>0) $_SESSION['main_company']=(int)$is_your_company['main_company_id'];
					else {
						if(isset($is_your_company['main_company_id']) && $is_your_company['main_company_id']==0) $_SESSION['main_company']=(int)$is_your_company['company_id'];
					}
					$_SESSION['company_id']=(int)$is_your_company['company_id'];
					$user=new User($_SESSION['user_id']);
					$user->company_id=(int)$_SESSION['main_company'];
					$user->save();
					$ret=array(
						"status" => "ok",
						"err" => "",
						"msg" => "",
					);
			}
			else {
				$ret=array(
					"status" => "err",
					"err" => "Вы не можете сменить компанию",
					"msg" => "Вы не можете сменить компанию",
				);
			}
		}
		else {
			if ((int)$is_your_company['company_id']>0){
					if((int)$is_your_company['main_company_id']>0) {
						$_SESSION['main_company']=(int)$is_your_company['main_company_id'];
						//echo print_r($_SESSION,true);
					}
					$_SESSION['company_id']=(int)$is_your_company['company_id'];
					$user=new User($_SESSION['user_id']);
					$user->company_id=(int)$is_your_company['company_id'];
					$user->save();
					$ret=array(
						"status" => "ok",
						"err" => "",
						"msg" => "",
					);
			}
			else {
				$ret=array(
					"status" => "err",
					"err" => "Вы не можете сменить компанию",
					"msg" => "Вы не можете сменить компанию",
				);
			}
		}
		self::check_my_sklad();
		if($_SESSION['roles']<10){
			self::check_my_service();
			Sort1s::activate();
			Sort1s::register($db,0);
			Sort1s::register($db,1);
			Sort1s::param_sync($db,0);
		}
		return $ret;
	}

	public static function change_my_sklad($request){
		$db = DB::getInstance();
		$is_your_sklad=$db->getRow("select id from sklad where company_id=?i and id=?i and deleted=0",$_SESSION['main_company'],(int)$request->my_sklad_id);
		//echo print_r($is_your_company,true);
		if($_SESSION['roles']<10){
			if ((int)$is_your_sklad['id']>0){
				$_SESSION['my_sklad_id']=(int)$is_your_sklad['id'];
				$user=new User($_SESSION['user_id']);
				$user->my_sklad_id=(int)$_SESSION['my_sklad_id'];
				$user->save();
				$ret=array(
					"status" => "ok",
					"err" => "",
					"msg" => "",
				);
			}
			else {
				$ret=array(
					"status" => "err",
					"err" => "Вы не можете сменить магазин",
					"msg" => "Вы не можете сменить магазин",
				);
			}
		}
		else if ($_SESSION['roles'] == 10) {
			if ((int)$is_your_sklad['id']>0){
				$_SESSION['my_sklad_id']=(int)$is_your_sklad['id'];
				$ret=array(
					"status" => "ok",
					"err" => "",
					"msg" => "",
				);
			}
			else {
				$ret=array(
					"status" => "err",
					"err" => "Вы не можете сменить магазин",
					"msg" => "Вы не можете сменить магазин",
				);
			}
		}
		else {
			$ret=array(
				"status" => "err",
				"err" => "Вы не можете сменить магазин",
				"msg" => "Вы не можете сменить магазин",
			);
		}
		return $ret;
	}

	public static function change_my_service($request){
		$db = DB::getInstance();
		$is_your_service=$db->getRow("select id,sklad_id from services where main_company_id=?i and id=?i and deleted=0",$_SESSION['main_company'],(int)$request->my_service_id);
		//echo print_r($is_your_company,true);
		if($_SESSION['roles']<10){
			if ((int)$is_your_service['id']>0){
				self::change_my_sklad((object)array("my_sklad_id"=>$is_your_service['sklad_id']));
				$_SESSION['my_service_id']=(int)$is_your_service['id'];
				$user=new User($_SESSION['user_id']);
				$user->my_service_id=(int)$_SESSION['my_service_id'];
				$user->save();
				$ret=array(
					"status" => "ok",
					"err" => "",
					"msg" => "",
				);
			}
			else {
				$_SESSION['my_service_id']=0;
				$user=new User($_SESSION['user_id']);
				$user->my_service_id=(int)$_SESSION['my_service_id'];
				$user->save();
				session_write_close();
				$ret=array(
					"status" => "ok",
					"err" => "",
					"msg" => "",
				);
				/*$ret=array(
					"status" => "err",
					"err" => "Вы не можете сменить сервис",
					"msg" => "Вы не можете сменить сервис",
				);*/
			}
		}
		else {
			$ret=array(
				"status" => "err",
				"err" => "Вы не можете сменить сервис",
				"msg" => "Вы не можете сменить сервис",
			);
		}
		return $ret;
	}

	public static function check_my_sklad(){
		$db = DB::getInstance();
		if($_SESSION['my_sklad_id']==0){
			$sklads=$db->getAll("select id from sklad where company_id=?i and deleted=0",$_SESSION['main_company']);
			if(count((array)$sklads)>0){
				$user=new User($_SESSION['user_id']);
				if($user->my_sklad_id==0){
					$user->my_sklad_id=$sklads[0]['id'];
					$user->save();
					$_SESSION['my_sklad_id']=$sklads[0]['id'];
				}
				else {
					$my_sklad_in_company=$db->getOne("select id from sklad where id=?i and company_id=?i and deleted=0",$user->my_sklad_id,$_SESSION['main_company']);
					if(!$my_sklad_in_company){
						$_SESSION['my_sklad_id']=$sklads[0]['id'];
						$user->my_sklad_id=$sklads[0]['id'];
						$user->save();
					}
					else {
						$_SESSION['my_sklad_id']=$user->my_sklad_id;
					}
				}
			}
		}
		else {
			$my_sklad_in_company=$db->getOne("select id from sklad where id=?i and company_id=?i and deleted=0",$_SESSION['my_sklad_id'],$_SESSION['main_company']);
			if(!$my_sklad_in_company){
				$sklads=$db->getAll("select id from sklad where company_id=?i and deleted=0",$_SESSION['main_company']);
				if(count((array)$sklads)>0){
					$user=new User($_SESSION['user_id']);
					if($user->my_sklad_id==0){
						$user->my_sklad_id=$sklads[0]['id'];
						$user->save();
						$_SESSION['my_sklad_id']=$sklads[0]['id'];
					}
					else {
						$user->my_sklad_id=$sklads[0]['id'];
						$user->save();
						$_SESSION['my_sklad_id']=$sklads[0]['id'];
					}
				}
				else {
					$_SESSION['my_sklad_id']=0;
				}
			}
		}
	}

	private static function check_my_service(){
		$db = DB::getInstance();
		if($_SESSION['my_service_id']==0){
			$services=$db->getAll("select id from services where main_company_id=?i and deleted=0",$_SESSION['main_company']);
			if(count((array)$services)>0){
				$user=new User($_SESSION['user_id']);
				$user->my_service_id=$services[0]['id'];
				$user->save();
				$_SESSION['my_service_id']=$services[0]['id'];
			}
		}
		else {
			$my_service_in_company=$db->getOne("select id from services where id=?i and main_company_id=?i and deleted=0",$_SESSION['my_service_id'],$_SESSION['main_company']);
			if(!$my_service_in_company){
				$services=$db->getAll("select id from services where main_company_id=?i and deleted=0",$_SESSION['main_company']);
				if(count((array)$services)>0){
					$user=new User($_SESSION['user_id']);
					$user->my_service_id=$services[0]['id'];
					$user->save();
					$_SESSION['my_service_id']=$services[0]['id'];
				}
				else {
					$_SESSION['my_service_id']=0;
				}
			}
		}
	}

	public static function login($request) {
	    if (isset($request->sesskey) && $request->sesskey!=""  && $request->sesskey!="undefined"){
				session_id($request->sesskey);
	    }
	    session_start();
		//file_put_contents("/var/log/shop/api/auth.log","request session_id=".$request->sesskey."\n real session_id=".session_id()."\nsession['user_id']=".$_SESSION['user_id']."\n");
	    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
		    self::$_is_auth = true;
			self::$_client = (int)$_SESSION['user_id'];
			self::check_my_sklad();
			//self::check_my_service();
		    return true;
	    }
	    else { 
				$login = $request->login;
				$pass = $request->password;

				if (!isset($login, $pass))
					return false;

				$db = DB::getInstance();

				$client = $db->getRow("select * from users where username=?s and roles<10", $request->login);
				if((int)$client['admin_disabled']==1) {
					self::$_err_msg="Вход заблокирован. Свяжитесь info@sort1.ru";
					return false;
				}
				if((int)$client['finance_disabled']==1) {
					self::$_err_msg="Доступ отключен из-за неоплаты. Для подключения свяжитесь с техподдержкой info@sort1.ru";
					return false;
				}
				if((int)$client['fired']==1) return false;
				//print_r($client);
				$seed=hash("sha256",md5(date("Y-m-d")));
				$passfb=hash("sha256",$client['password'].$seed);
				//echo "seed: $seed, passfb: $passfb\n login: $login, pass: $pass\n";
				if ($passfb===$pass && !empty($login) && !empty($pass)) {
					self::$_is_auth = true;
					self::$_client = $client;
					//session_start();
					$_SESSION['user_id']=$client['id'];
					$client_main_companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
					if(in_array($client['company_id'],$client_main_companys)){
						$_SESSION['company_id']=$client['company_id'];
					}
					else {
						$_SESSION['company_id']=$client_main_companys[0];
					}
					if(in_array($client['main_company_id'],$client_main_companys)){
						$_SESSION['main_company']=$client['main_company_id'];
					}
					else {
						$_SESSION['main_company']=$client_main_companys[0];
					}//$db->getOne("select main_company_id from user_companys where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['company_id']);
					$zakaz_commit=$db->getRow("select 
						zakaz_commit,
						document_set_price,
						document_edit_deny_date,
						document_detail_edit_deny,
						document_details_round,
						zakaz_marketing_channel,
						document_set_category,
						self_zakaz_sale_price 
						from company where id=?i",$client['main_company_id']);
					if($zakaz_commit) {
						$_SESSION['zakaz_commit']=$zakaz_commit['zakaz_commit'];
						$_SESSION['zakaz_marketing_channel']=$zakaz_commit['zakaz_marketing_channel'];
						$_SESSION['document_set_price']=$zakaz_commit['document_set_price'];
						$_SESSION['document_set_category']=$zakaz_commit['document_set_category'];
						$_SESSION['document_details_round']=$zakaz_commit['document_details_round'];
						$_SESSION['self_zakaz_sale_price']=$zakaz_commit['self_zakaz_sale_price'];
						$_SESSION['document_edit_deny_date']=$zakaz_commit['document_edit_deny_date'];
						$_SESSION['document_detail_edit_deny']=$zakaz_commit['document_detail_edit_deny'];
					}
					else $_SESSION['zakaz_commit']=0;
					$_SESSION['roles']=$client['roles'];
					//$_SESSION['my_sklad_id']=$client['my_sklad_id'];
					$_SESSION['my_service_id']=$client['my_service_id'];
					$db->query("insert into user_sessions (session_id,session_start,user_id,user_ip) values(?s,?s,?i,?s) on duplicate key update session_start=?s,user_id=?i,user_ip=?s",session_id(),date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SERVER['HTTP_X_REAL_IP'],date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SERVER['HTTP_X_REAL_IP']);
					self::check_my_sklad();
					self::check_my_service();
					Sort1s::activate(); 
					Sort1s::register($db,0);
					Sort1s::register($db,1);
					Sort1s::param_sync($db,0);
					return true;
				}
				else
					return false;
	  	}
	}

	public static function kick_user($request){
		$db = DB::getInstance();
		if($request->key!="Sdlkmtdfsl94mdk4965mkfd95"){
			return array("status"=>"err","err"=>"Неправильный ключ");
		}

		for($i = 0; $i < count((array)$request->users); $i++){
			// echo($request->users[$i]['id']);
			$sess = $db->getAll("select session_id from user_sessions where user_id=?i",$request->users[$i]['id']);
      
			foreach($sess as $value){
			// echo"$value";
				unlink("/var/lib/php/sessions/sess_".$value['session_id']);
			}
			$db->query("DELETE FROM user_sessions WHERE user_id=?i", $request->users[$i]['id']);
		}

		return array("status"=>"ok","msg"=>"");
	}

	public static function user_login($request) {
		//echo "session_id1=".session_id()."\n";
	    if (isset($request->sesskey) && $request->sesskey!="" && $request->sesskey!="undefined"){
			session_id($request->sesskey);
			//echo "session_id2=".session_id()."\n";
	    }
	    session_start();
		//echo "session_id3=".session_id()."\n";
	    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
		    self::$_is_auth = true;
		    self::$_client = (int)$_SESSION['user_id'];
		    return true;
	    }
	    else {
				if(empty($request->login)) return false;
				$login = $request->login;
				$pass = $request->password;

				
				$db = DB::getInstance();
				preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
				$sql="select company_id,shop_verify_phone,shop_sms_apikey from company_sites where site_name=?s";
				$site_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
				//echo $sql.$origin[1];
				//print_r($site_data);
				$main_company_id=$site_data['company_id'];

				if (!isset($login, $pass)){
					//sleep(20);
					if((int)$main_company_id>0){
						$_SESSION['main_company']=$main_company_id;
						if((int)$_SESSION['main_company']>0){
							Sort1s::activate();
							Sort1s::register($db,0); 
							Sort1s::register($db,1);
							Sort1s::param_sync($db);
						}
						// проверить !!!!!!!!!!!!
						//mysqli_commit($db->get_conn());
					}
					if (empty($_SESSION['user_id'])){
						//echo "login=".$login." pass=".$pass."\n";
						return false;
					}
				}

				$client_sql=$db->parse("select * from users where username=?s and main_company_id=?i and roles = 10", $request->login,$main_company_id);
				$client = $db->getRow("?p",$client_sql);
				//$seed=hash("sha256",md5(date("Y-m-d")));
				$passfb=$client['password'];
				if((int)$client['fired']==1) return false;
				//echo "client: ".var_dump($client).", passfb: $passfb\n login: $request->login, pass: $pass main_company: $main_company_id\nsql:".$client_sql;
				if ($passfb===$pass && !empty($login) && !empty($pass)) {
					self::$_is_auth = true;
					self::$_client = $client;
					//session_start();
					$_SESSION['user_id']=$client['id'];
					$_SESSION['company_id']=$client['company_id'];
					$_SESSION['main_company']=$client['main_company_id'];//$db->getOne("select main_company_id from user_companys where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['company_id']);
					$_SESSION['roles']=$client['roles'];
					$temp_basket=$db->getOne("select id from basket where session_id=?s and user_id=0",session_id());
					$db->query("update favorite_details set user_id=?i where session_id=?s", (int)$_SESSION['user_id'], session_id());
					$db->query("update favorite_details set session_id='' where user_id=?i", (int)$_SESSION['user_id']);
					$basket=new Basket(); //$db->getOne("select id from basket where user_id=?i and main_company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
					if($temp_basket) $db->query("update basket_details set basket_id=?i where basket_id=?i",$basket->id,(int)$temp_basket);
					if((int)$basket>0 && (int)$basket!=(int)$temp_basket) $db->query("delete from basket where id=?i",(int)$temp_basket);
					Sort1s::activate();
					Sort1s::register($db,0);
					Sort1s::register($db,1);
					Sort1s::param_sync($db);
					return true;
				} else {
					/*$_SESSION['main_company']=$main_company_id;
					Sort1s::activate();
					Sort1s::register($db,0);
					Sort1s::register($db,1);
					Sort1s::param_sync($db);*/
					return false;
				}
	    }
	}

	public static function api_login($request) {
		$db = DB::getInstance();
	    if (isset($request->api_key) && $request->api_key!=""){
			$sessid=$db->getOne("select session_id from user_sessions where user_id in (select id from users where api_key=?s)",$request->api_key);
			if($sessid!=""){
			    session_id($sessid);
			    session_start();
			}
	    }

	    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
		    self::$_is_auth = true;
		    self::$_client = (int)$_SESSION['user_id'];
		    return true;
	    }
	    else {
				$client = $db->getRow("select * from users where api_key=?s", $request->api_key);
				//$seed=hash("sha256",md5(date("Y-m-d")));
				//$passfb=$client['password'];
				//echo "seed: $seed, passfb: $passfb\n login: $login, pass: $pass\n";
				if (!empty($client['id']) && (int)$client['id']>0) {
					self::$_is_auth = true;
					self::$_client = $client;
					if(!isset($sessid) || $sessid=="") session_start();
					$_SESSION['user_id']=$client['id'];
					$_SESSION['company_id']=$client['company_id'];
					$_SESSION['main_company']=$client['main_company_id'];//$db->getOne("select main_company_id from user_companys where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['company_id']);
					$_SESSION['roles']=$client['roles'];
					$db->query("insert ignore into user_sessions (session_id,session_start,user_id,user_ip) values(?s,?s,?i,?s)",session_id(),date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SERVER['HTTP_X_REAL_IP']);
					return true;
				} else
					return false;
	    }
	}

	public static function logout($request) {
	    //unset($_SESSION['user_id']);
	    //unset($_SESSION['company_id']);
	    //unset($_SESSION['main_company']);
	    //unset($_SESSION['roles']);
	    session_unset();
	    self::$_is_auth = false;
	    self::$_client = null;
	    foreach (glob("../open_cart/temp/".session_id()."*") as $filename) {
		//echo "$filename size " . filesize($filename) . "\n";
		unlink($filename);
	    }
	    //unlink("../open_cart/temp/".session_id()."*");
	    return array("status"=>"ok","err"=>"");
	}


	public static function is_auth() {
		return self::$_is_auth;
	}

	public static function get_client() {
		return self::$_client;
	}

	public static function user_login_market($request) {
		//echo "session_id1=".session_id()."\n";
	    if (isset($request->sesskey) && $request->sesskey!="" && $request->sesskey!="undefined"){
			session_id($request->sesskey);
			//echo "session_id2=".session_id()."\n";
	    }
	    session_start();
		//echo "session_id3=".session_id()."\n";
	    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
		    self::$_is_auth = true;
		    self::$_client = (int)$_SESSION['user_id'];
		    return true;
	    }
	    else {
				if(empty($request->login)) return false;
				$login = $request->login;
				$pass = $request->password;

				$db = DB::getInstance();

				$main_company_id=35;

				if (!isset($login, $pass)){
					//sleep(20);
					if((int)$main_company_id>0){
						$_SESSION['main_company']=$main_company_id;
						// Sort1s::activate();
						// Sort1s::register($db,0); 
						// Sort1s::register($db,1);
						// Sort1s::param_sync($db);
						// проверить !!!!!!!!!!!!
						//mysqli_commit($db->get_conn());
					}
					if (empty($_SESSION['user_id'])){
						//echo "login=".$login." pass=".$pass."\n";
						return false;
					}
				}

				$client = $db->getRow("select * from users where username=?s and main_company_id=?i and roles = 20", $request->login,$main_company_id);
				//$seed=hash("sha256",md5(date("Y-m-d")));
				$passfb=$client['password'];
				if((int)$client['fired']==1) return false;
				//echo "seed: $seed, passfb: $passfb\n login: $login, pass: $pass main_company: $main_company_id\n";
				if ($passfb===$pass && !empty($login) && !empty($pass)) {
					self::$_is_auth = true;
					self::$_client = $client;
					//session_start();
					$_SESSION['user_id']=$client['id'];
					$_SESSION['company_id']=$client['company_id'];
					$_SESSION['main_company']=$client['main_company_id'];//$db->getOne("select main_company_id from user_companys where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['company_id']);
					$_SESSION['roles']=$client['roles'];
					
					if(!empty($_SESSION['city_id']) && $_SESSION['city_id'] != $client['city_id']){
						$db->query("update users set city_id=?i where id=?i and roles = 20",$_SESSION['city_id'],$_SESSION['user_id']);
					}
					$temp_basket=$db->getOne("select id from basket where session_id=?s and user_id=0",session_id());
					$basket=new Basket(); //$db->getOne("select id from basket where user_id=?i and main_company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);

					if($temp_basket) {
						$seess_details = $db->getAll("select * from basket_details where basket_id=?i",$temp_basket);
						$basket_details = $db->getAll("select * from basket_details where basket_id=?i",$basket->id);

						// Обрабатываем детали из временной корзины
						foreach ($seess_details as $seess_detail) {
							$found = false;

							// Проверяем, есть ли уже такая деталь в корзине
							foreach ($basket_details as &$basket_detail) {
								if ($basket_detail['detail_id'] == $seess_detail['detail_id'] && $basket_detail['deliverer_id'] == $seess_detail['deliverer_id']) {
									$basket_detail['count'] += $seess_detail['count'];

									// Если count больше чем max_count, то устанавливаем максимальное значение
									if ($basket_detail['max_count'] < $basket_detail['count']) {
										$db->query("update basket_details set count=?i where detail_id=?i and basket_id=?i",$basket_detail['max_count'],$basket_detail['detail_id'],$basket->id);
										$db->query("delete from basket_details where detail_id=?i and basket_id=?i",$basket_detail['detail_id'],$seess_detail['basket_id']);
									}
									else{
										$db->query("update basket_details set count=?i where detail_id=?i and basket_id=?i",$basket_detail['count'],$basket_detail['detail_id'],$basket->id);
										$db->query("delete from basket_details where detail_id=?i and basket_id=?i",$basket_detail['detail_id'],$seess_detail['basket_id']);
									}
								}
							}
						}
						$db->query("update basket_details set basket_id=?i where basket_id=?i",$basket->id,$temp_basket);
					}
					// Sort1s::activate();
					// Sort1s::register($db,0);
					// Sort1s::register($db,1);
					// Sort1s::param_sync($db);
					return true;
				} else {
					$_SESSION['main_company']=$main_company_id;
					// Sort1s::activate();
					// Sort1s::register($db,0);
					// Sort1s::register($db,1);
					// Sort1s::param_sync($db);
					return false;
				}
	    }
	}
	public static function get_current_sklad_shop($request){
		$db = DB::getInstance();
		if(empty($_SESSION['my_sklad_id']) || (int)$_SESSION['my_sklad_id']<=0){
			$_SESSION['my_sklad_id'] = $db->getOne('select id from sklad where company_id = ?i limit 1',$_SESSION['main_company']);
		}
		$sql="select address,id from sklad where id=?i";
		$res=$db->getRow($sql,(int)$_SESSION['my_sklad_id']);
		if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['address']=$res['address'];
			$ret['sklad_id']=$res['id'];
			$ret['msg']="";
		}
		else{
			$ret['status']="err";
			$ret['err']="Не заведен склад";
		}
		
	    //if ($ret['status']=="err") return $ret;
	    //else 
		return $ret;
	}
}



?>
