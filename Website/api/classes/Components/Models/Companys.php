<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Company;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Models\CompanyCars;
use Sort1API\Components\Models\Dogovors;

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sort1API\Components\Functions;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Companys extends Model {
	/**
	* Punto switcher (0: rus -> eng, 1: eng -> rus, false -> eng & rus -> rus & eng)
	*
	* @param string $text
	* @param int|bool $arrow
	* @return string
	*/
	public static function stringSwitcher($text, $arrow = 0): string
	{
		$str[0] = array(
		'й' => 'q', 'ц' => 'w', 'у' => 'e', 'к' => 'r', 'е' => 't', 'н' => 'y', 'г' => 'u', 'ш' => 'i', 'щ' => 'o', 'з' => 'p', 'х' => '[', 'ъ' => ']', 'ф' => 'a', 'ы' => 's', 'в' => 'd',
		'а' => 'f', 'п' => 'g', 'р' => 'h', 'о' => 'j', 'л' => 'k', 'д' => 'l', 'ж' => ';', 'э' => '\'', 'я' => 'z', 'ч' => 'x', 'с' => 'c', 'м' => 'v', 'и' => 'b', 'т' => 'n', 'ь' => 'm',
		'б' => ',', 'ю' => '.', 'Й' => 'Q', 'Ц' => 'W', 'У' => 'E', 'К' => 'R', 'Е' => 'T', 'Н' => 'Y', 'Г' => 'U', 'Ш' => 'I', 'Щ' => 'O', 'З' => 'P', 'Х' => '[', 'Ъ' => ']', 'Ф' => 'A',
		'Ы' => 'S', 'В' => 'D', 'А' => 'F', 'П' => 'G', 'Р' => 'H', 'О' => 'J', 'Л' => 'K', 'Д' => 'L', 'Ж' => ';', 'Э' => '\'', '?' => 'Z', 'ч' => 'X', 'С' => 'C', 'М' => 'V', 'И' => 'B',
		'Т' => 'N', 'Ь' => 'M', 'Б' => ',', 'Ю' => '.',
		);
		$str[1] = array(
		'q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ', 'a' => 'ф', 's' => 'ы', 'd' => 'в',
		'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', '\'' => 'э', 'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь',
		',' => 'б', '.' => 'ю', 'Q' => 'Й', 'W' => 'Ц', 'E' => 'У', 'R' => 'К', 'T' => 'Е', 'Y' => 'Н', 'U' => 'Г', 'I' => 'Ш', 'O' => 'Щ', 'P' => 'З', '[' => 'Х', ']' => 'Ъ', 'A' => 'Ф',
		'S' => 'Ы', 'D' => 'В', 'F' => 'А', 'G' => 'П', 'H' => 'Р', 'J' => 'О', 'K' => 'Л', 'L' => 'Д', ';' => 'Ж', '\'' => 'Э', 'Z' => '?', 'X' => 'ч', 'C' => 'С', 'V' => 'М', 'B' => 'И',
		'N' => 'Т', 'M' => 'Ь', ',' => 'Б', '.' => 'Ю',
		);
	
		return strtr($text, $str[$arrow] ?? \array_merge($str[0], $str[1]));
	}
	

	public static function fast_save_company($request){
		$db = DB::getInstance();
		if(empty($request->company_name)) return array("status"=>"err","err"=>"Пустое имя клиента");
		if(empty($request->mphone)) return array("status"=>"err","err"=>"Не указан номер телефона");
		$is_exist=$db->getAll("select id,name,mphone,email from company where name like ?s and replace(replace(replace(replace(replace(replace(mphone,'	',''),'.',''),' ',''),'-',''),'(',''),')','')=?s and id in (select company_id from user_companys where main_company_id=?i)","%".preg_replace("/\s+/"," ",$request->company_name)."%",preg_replace("/\D+/","",$request->mphone),$_SESSION['main_company']);
		if(count((array)$is_exist)>0) return array("status"=>"ok","msg"=>"","companys"=>$is_exist);
		if (isset($request->company_id) && (int)$request->company_id>0) {$company_id=(int)$request->company_id;}
	    if(isset($company_id)) {
				$company=new Company($company_id);
	    }
	    else {
				$company=new Company();
		}
		switch((int)$request->okopf){
			case 1: $company->type=1; break;
			case 2: $company->type=2; break;
			case 3: $company->type=3; break;
		}
		if (isset($request->company_name)) {$company->name=$request->company_name;}
		if (isset($request->company_inn)) {$company->inn=$request->company_inn;}
		if (isset($request->company_kpp)) {$company->kpp=$request->company_kpp;}
		if (isset($request->company_uraddress)) {$company->address=$request->company_uraddress;}
		if (isset($request->company_ruk)) {$company->ruk=$request->company_ruk;}
		if (isset($request->company_ogrn)) {$company->ogrn=$request->company_ogrn;}
		if (isset($request->company_okpo)) {$company->okpo=$request->company_okpo;}
		if (isset($request->company_okved)) {$company->okved=$request->company_okved;}
		if (isset($request->mphone)) {$company->mphone=preg_replace("/\D+/","",$request->mphone);}
		$company->btype=1;
		if (isset($request->email)) {$company->email=$request->email;}
		$comp_saved=$company->save();
		if ((int)$company->id>0){
			if($db->query("insert ignore into user_companys SET user_id=?i,main_company_id=?i,company_id=?i,btype=?i,create_date=?s",$_SESSION['user_id'],$_SESSION['main_company'],$company->id,$company->btype,date("Y-m-d H:i:s"))){
				$ret['status']="ok";
				$ret['msg']="Клиент успешно добавлен";
				$ret['company_id']=(int)$company->id;
				$company_balance=new CompanyBalance((int)$company->id);
				$company_balance->Save();
				if(isset($request->price_type) && (int)$request->price_type>0){
					$req=[
						"company_id"=>$company->id,
						"price_type_id"=>$request->price_type,
						"descr"=>"Договор со скидкой"
					];
					$is_your=$db->getOne("select id from dict_price_type where id=?i and main_company=?i",$request->price_type,$_SESSION['main_company']);
					if($is_your){
						$dogovor_res=Dogovors::save_dogovor((object)$req);
						$ret['dogovor_res']=$dogovor_res;
					}
				}
				if((int)$company->id>0 && !empty($request->vin)){
					$car_data=array("vin"=>$request->vin,"company_id"=>(int)$company->id);
					$car_res=CompanyCars::save_company_car((object)$car_data);
					//$db->query("insert into company_cars set company_id=?i,vin=?s,create_date=?s,main_company_id=?i",(int)$company->id,$request->vin,date("Y-m-d H:i:s"),$_SESSION['main_company']);
				}
				if((int)$company->id>0 && empty($request->vin) && !empty($request->auto_gov_num) && strlen($request->auto_gov_num)>=8){
					$car_data=array("auto_gov_num"=>$request->auto_gov_num,"company_id"=>(int)$company->id);
					$car_res=CompanyCars::save_company_car((object)$car_data);
					//$db->query("insert into company_cars set company_id=?i,vin=?s,create_date=?s,main_company_id=?i",(int)$company->id,$request->vin,date("Y-m-d H:i:s"),$_SESSION['main_company']);
				}
			}
			else { 
				$ret['status']="err";
				$ret['err']="Не удалось добавить клиента";
			}
		}
		//$ret['company']=print_r($company);
		//$ret['comp_saved']=$comp_saved;
		return $ret;
	}

	public static function save_clients($request){
		$db = DB::getInstance();
		$i=0;
		$ret=array();
		foreach($request->clients as $cl_key=>$client){
			$i++;
			unset($company);
			if(empty($client['client_name'])) {
				$ret[$i]['client_name']=trim($client['client_name']);
				$ret[$i]['status']="err";
				$ret[$i]['err']="Пустое имя клиента";
				continue;
				//return array("status"=>"err","err"=>"Пустое имя клиента");
			}
			$client_names=explode(" ",$client['client_name']);
			if(!empty($client['client_okopf']) && trim($client['client_okopf'])=="Физ. лицо" && count($client_names)<2){
				$ret[$i]['client_name']=trim($client['client_name']);
				$ret[$i]['status']="err";
				$ret[$i]['err']="Слишком короткое имя клиента";
				continue;
			}
			//if(empty($request->mphone)) return array("status"=>"err","err"=>"Не указан номер телефона");
			$is_exist=$db->getAll("select id,name,mphone,email,birthday,inn,kpp from company where name=?s and id in (select company_id from user_companys where main_company_id=?i)",preg_replace("/\s+/"," ",$client['client_name']),$_SESSION['main_company']);
			//if(count($is_exist)>0) continue; //return array("status"=>"ok","msg"=>"","companys"=>$is_exist);
			file_put_contents("/var/log/sort1/save_users.log","is_exist=".print_r($is_exist,true),FILE_APPEND);
			if(count((array)$is_exist)==1) {
				if( ($is_exist[0]['mphone']==$client['client_mphone'] || $is_exist[0]['birthday']==$client['client_birthday']) || (empty($client['client_birthday']) && empty($client['client_mphone']))) {
					$company=new Company($is_exist[0]['id']);
				}
				else {
				
					$is_exist_company=$db->getAll("select id from company where name=?s and inn=?i and kpp=?i and (mphone=?s or birthday=?s)",$client['client_name'],(int)$client['client_inn'],(int)$client['client_kpp'],(string)$client['client_mphone'],$client['client_birthday']);
					if(count((array)$is_exist_company)==1){
						$company=new Company($is_exist_company[0]['id']);
					}
					else {
						if(!empty($client['client_birthday']))
							$company=new Company();
						file_put_contents("/var/log/sort1/save_users.log","!!! New Company count(is_exist)!=1 && count(is_exist_company)=".count((array)$is_exist_company)."\n is_exist_company=".print_r($is_exist_company,true),FILE_APPEND);
					}
				}
			}
			else {
				if(count((array)$is_exist)>1) {
					file_put_contents("/var/log/sort1/save_users.log","count(is_exist)>1\n",FILE_APPEND);
					foreach($is_exist as $comp_key=>$comp_val){
						if( (((string)$comp_val['mphone']==(string)$client['client_mphone'] && !empty($client['client_mphone'])) 
							|| ((string)$comp_val['birthday']==(string)$client['client_birthday'] && !empty($client['client_birthday']))) 
							|| ( empty($client['client_mphone']) && empty($client['client_birthday']) && empty($comp_val['birthday']) && empty($comp_val['mphone']) ) 
						){
							file_put_contents("/var/log/sort1/save_users.log",$comp_val['mphone']."==".$client['client_mphone']." || ".$comp_val['birthday']."==".$client['client_birthday']."\n".
							((string)$comp_val['mphone']==(string)$client['client_mphone'] && !empty($client['client_mphone'])).
							" || ".((string)$comp_val['birthday']==(string)$client['client_birthday'] && !empty($client['client_birthday'])).
							" || ".(empty($client['client_mphone']) && empty($client['client_birthday']) && empty($comp_val['birthday']) && empty($comp_val['mphone']))."\n",FILE_APPEND);
							$company=new Company($comp_val['id']);
							break;
						}
					}
					if(empty($company)){
						file_put_contents("/var/log/sort1/save_users.log","count(is_exist)>1 && empty(company)\n",FILE_APPEND);
						$comp_balances=$db->getInd("company_id","select * from company_balance where company_id in (?b)",array_column($is_exist,"id"));
						$max_sum_trade=0;
						foreach($is_exist as $comp_key1=>$comp_val1){
							if (isset($client['sum_trade']) && $client['sum_trade']>0){
								if($max_sum_trade<$comp_balances[$comp_val1['id']]['sum_trade']) $max_sum_trade=$comp_balances[$comp_val1['id']]['sum_trade'];
								if($comp_balances[$comp_val1['id']]['sum_trade']==$client['sum_trade']){
									$company=new Company($is_exist[$comp_key1]['id']);
								}
							}
						}
						if(empty($company)){
							if($client['sum_trade']<$max_sum_trade) $client['sum_trade']=$max_sum_trade;
							$company=new Company($is_exist_company[0]['id']);
						}
					}
				}
				else {
					file_put_contents("/var/log/sort1/save_users.log","count(is_exist)==0\n",FILE_APPEND);
					$is_exist_company=$db->getAll("select id from company where name=?s and inn=?i and kpp=?i and (mphone=?s or birthday=?s)",$client['client_name'],(int)$client['client_inn'],(int)$client['client_kpp'],(string)$client['client_mphone'],$client['client_birthday']);
					if(count((array)$is_exist_company)==1){
						foreach($is_exist_company as $comp_key1=>$comp_val1){
							if($comp_val1['mphone']==$client['client_mphone'] && $comp_val1['birthday']==$client['client_birthday']){
								$company=new Company($comp_val1['id']);
								break;
							}
						}
						if(empty($company)){
							$company=new Company($is_exist_company[0]['id']);
						}

					}
					else {
						if(count((array)$is_exist_company)>1){
							if(empty($company)){
								$comp_balances=$db->getInd("company_id","select * from company_balance where company_id in (?b)",array_column($is_exist_company,"id"));
								$max_sum_trade=0;
								foreach($is_exist_company as $comp_key1=>$comp_val1){
									if (isset($client['sum_trade']) && $client['sum_trade']>0){
										if($max_sum_trade<$comp_balances[$comp_val1['id']]['sum_trade']) $max_sum_trade=$comp_balances[$comp_val1['id']]['sum_trade'];
										if($comp_balances[$comp_val1['id']]['sum_trade']==$client['sum_trade']){
											$company=new Company($is_exist_company[$comp_key1]['id']);
										}
									}
								}
								if(empty($company)){
									if($client['sum_trade']<$max_sum_trade) $client['sum_trade']=$max_sum_trade;
									$company=new Company($is_exist_company[0]['id']);
								}
							}
							//$company=new Company($is_exist_company[0]['id']);
						}
						else {
							$company=new Company();
							file_put_contents("/var/log/sort1/save_users.log","count(is_exist)==0 && count(is_exist_company)=".count((array)$is_exist_company)."\n is_exist_company=".print_r($is_exist_company,true),FILE_APPEND);
						}
					}
				}
			}
			if(empty($company)){
				file_put_contents("/var/log/sort1/save_users.log","!!! New company, not setted, empty(company)\n",FILE_APPEND);
				$company=new Company();
			}
			file_put_contents("/var/log/sort1/save_users.log","company=".print_r($company,true)."\nclient=".print_r($client,true)."\n",FILE_APPEND);
			if(!empty($client['client_okopf'])){
				switch(trim($client['client_okopf'])){
					case "Физ. лицо":
						$company->type=3; break;
					case "Юр. лицо":
						$company->type=2;
						break;
					case "ИП":
					case "Индивидуальный предприниматель":
						$company->type=1;
						break;
					default: $company->type=3;
				}
			}
			else {
				switch(strlen($client['client_inn'])){
					case 0:
						$company->type=3; break;
					case 10:
						$company->type=2;
						break;
					case 12:
						$company->type=1;
						break;
					default: $company->type=3;
				}
			}
			if (isset($client['client_name'])) {$company->name=trim($client['client_name']);}
			if (isset($client['client_inn'])) {$company->inn=(int)$client['client_inn'];}
			if (isset($client['client_kpp'])) {$company->kpp=(int)$client['client_kpp'];}
			if (isset($client['client_okpo'])) {$company->okpo=$client['client_okpo'];}
			if (isset($client['client_mphone'])) {$company->mphone=$client['client_mphone'];}
			if (isset($client['client_email'])) {$company->email=$client['client_email'];}
			if (isset($client['client_address'])) {$company->address=$client['client_address'];}
			if (isset($client['client_birthday'])) {$company->birthday=$client['client_birthday'];}
			if (isset($client['client_post_address'])) {$company->post_address=$client['client_post_address'];}
			if (isset($client['client_ruk'])) {$company->ruk=$client['client_ruk'];}
			if(isset($client['client_is_client']) && $client['client_is_client']=="Да" && isset($client['client_is_dealer']) && $client['client_is_dealer']=="Да"){
				$company->btype=4;
			}
			elseif(isset($client['client_is_client']) && $client['client_is_client']=="Да"){
				$company->btype=1;
			}
			elseif(isset($client['client_is_dealer']) && $client['client_is_dealer']=="Да"){
				$company->btype=2;
			}
			else {
				$company->btype=1;
			}
			if (isset($request->email)) {$company->email=$request->email;}
			
			//if(count($is_exist_company)>0){
				// Продолжим, один хер не запишет выдаст ошибку на дупликате кей
				//continue;
				//$company=new Company($is_exist_company[0]['id']);
			//}
			//else {
			$is_duplicated=$db->getRow("select * from company where name=?s and inn=?i and kpp=?i and mphone=?s",$company->name,$company->inn,$company->kpp,$company->mphone);
			if($is_duplicated) $company->id=$is_duplicated['id'];
			if(empty($company->document_edit_deny_date)) $company->document_edit_deny_date='0000-00-00';
			if(empty($company->ipreg_date)) $company->ipreg_date='0000-00-00';
			if(empty($company->birthday)) $company->birthday='0000-00-00';
			$comp_saved=$company->save();
			//}
			if ((int)$company->id>0){
				if($db->query("insert ignore into user_companys SET user_id=?i,main_company_id=?i,company_id=?i,btype=?i,create_date=?s",$_SESSION['user_id'],$_SESSION['main_company'],$company->id,$company->btype,date("Y-m-d H:i:s"))){
						$ret[$i]['status']="ok";
						$ret[$i]['msg']="Клиент успешно добавлен";
						$ret[$i]['company_id']=(int)$company->id;
						$ret[$i]['client_name']=$client['client_name'];
						$company_balance=new CompanyBalance((int)$company->id);
						if (isset($client['sum_trade'])) {$company_balance->sum_trade=(float)$client['sum_trade'];}
						if (isset($client['client_cashback'])) {$company_balance->cashback=(float)$client['client_cashback'];}
						$company_balance->Save();
						$vin="";
						/*$client_comment_arr=explode(" ",$client['client_comment']);
						foreach($client_comment_arr as $cc_key=>$cc_val){
							if(strlen($cc_val)==17){
								$vin=$cc_val;
							}
						}*/
						if((int)$company->id>0 && (!empty($client['auto_vin']) || !empty($client['auto_maker']) || !empty($client['auto_model']) || !empty($client['auto_gov_num']))){
							$many_vins=preg_match("/[;,]+/",$client['auto_vin']);
							if($many_vins){
								$auto_vins1=explode(";",$client['auto_vin']);
								foreach($auto_vins1 as $auto_vin1){
									$auto_vins2=explode(",",$auto_vin1);
									foreach($auto_vins2 as $auto_vin2){
										if(strlen($auto_vin2)==17){
											$car_data=array(
												"vin"=>(!empty($auto_vin2)?$auto_vin2:""),
												"auto_maker"=>(!empty($client['auto_maker'])?$client['auto_maker']:""),
												"auto_model"=>(!empty($client['auto_model'])?$client['auto_model']:""),
												"auto_gov_num"=>(!empty($client['auto_gov_num'])?$client['auto_gov_num']:""),
												"made_year"=>(!empty($client['auto_made_year'])?$client['auto_made_year']:""),
												"company_id"=>(int)$company->id);
											$car_res=CompanyCars::save_company_car((object)$car_data);
										}
									}
								}
							}
							else {
								$car_data=array(
									"vin"=>(!empty($client['auto_vin'])?$client['auto_vin']:""),
									"auto_maker"=>(!empty($client['auto_maker'])?$client['auto_maker']:""),
									"auto_model"=>(!empty($client['auto_model'])?$client['auto_model']:""),
									"auto_gov_num"=>(!empty($client['auto_gov_num'])?$client['auto_gov_num']:""),
									"made_year"=>(!empty($client['auto_made_year'])?$client['auto_made_year']:""),
									"company_id"=>(int)$company->id);
								$car_res=CompanyCars::save_company_car((object)$car_data);
							}
							//$db->query("insert into company_cars set company_id=?i,vin=?s,create_date=?s,main_company_id=?i",(int)$company->id,$request->vin,date("Y-m-d H:i:s"),$_SESSION['main_company']);
						}
						if((int)$company->id>0 && (!empty($client['card_number']) || (!empty($client['price_type_id']) && $client['price_type_id']>0))){
							$dogovor_data=array(
								"price_type_id"=>(int)$client['price_type_id'],
								"card_number"=>$client['card_number'],
								"company_id"=>(int)$company->id);
							$car_res=Dogovors::save_dogovor((object)$dogovor_data);
						}
				}
				else {
					$ret[$i]['client_name']=$client['client_name'];
					$ret[$i]['status']="err";
					$ret[$i]['err']="Не удалось добавить клиента";
				}
			}
		}
		return array("status"=>"ok","returns"=>$ret);
	}

	public static function save_company($request) {
		$fields="";
		$db = DB::getInstance();
		$my_companys=$db->getCol("select company_id from user_companys where main_company_id=0 and user_id=?i",$_SESSION['user_id']);
		if($_SESSION['roles']>2 && in_array($request->company_id,$my_companys)) return self::_error_arr("У вас не хватает прав для изменения данных");

		if (isset($request->company_id) && (int)$request->company_id>0) {$company_id=(int)$request->company_id;}
	    if(isset($company_id)) {
				$company=new Company($company_id);
				/*if((int)$company->inn!=(int)$request->inn || $company->name!=$request->company_name){
					$company=new Company();
				}*/
	    }
	    else {
				$company=new Company();
		}
		if($company_id==471) return self::_error_arr("Это системный контрагент, его нельзя изменять");
	    if (isset($request->okopf)) {$company->type=(int)$request->okopf;}
	    if (isset($request->company_name)) {$company->name=$request->company_name;}
		if (isset($request->company_short_name)) {$company->short_name=$request->company_short_name;}
	    if (isset($request->inn)) {$company->inn=$request->inn;}
	    if (isset($request->kpp)) {$company->kpp=$request->kpp;}
	    if (isset($request->ogrn)) {$company->ogrn=(int)$request->ogrn;}
		if (isset($request->okpo)) {$company->okpo=(int)$request->okpo;}
		if (isset($request->okved)) {$company->okved=(int)$request->okved;}
	    if (isset($request->address)) {$company->address=$request->address;}
	    if (isset($request->ruk)) {$company->ruk=$request->ruk; }
		if (isset($request->company_timezone)) {$company->timezone=$request->company_timezone; }
		if (isset($request->rukdol)) {$company->rukdol=$request->rukdol;}
		
		if (isset($request->buh)) {$company->buh=$request->buh; }
	    if (isset($request->buhdol)) {$company->buhdol=$request->buhdol;}
	    if (isset($request->rs)) {$company->rs=$request->rs;}
	    if (isset($request->ks)) {$company->ks=$request->ks;}
	    if (isset($request->bik)) {$company->bik=(int)$request->bik;}
	    if (isset($request->bank)) {$company->bank=$request->bank;}
	    if (isset($request->btype)) {$company->btype=$request->btype;}
		if (isset($request->ipreg_num)) {$company->ipreg_num=$request->ipreg_num;}
		if (!empty($request->ipreg_date) && $request->ipreg_date!="") {$company->ipreg_date=$request->ipreg_date;}
		else{$company->ipreg_date='0000-00-00';}
		if (!empty($request->birthday) && $request->birthday!="") {$company->birthday=$request->birthday;}
		else{$company->birthday='0000-00-00';}
	    if (isset($request->mphone)) {$company->mphone=$request->mphone;}//preg_replace("/\D+/","",$request->mphone);}
	    if (isset($request->email)) {$company->email=$request->email;}
	    if (isset($request->tax_type)) {$company->tax_type=$request->tax_type;}
		if (isset($request->main_org_id)) {$company->main_org_id=$request->main_org_id;}
		if (isset($request->buyer_in_upd) && $request->buyer_in_upd=="on") {$company->buyer_in_upd=1;} else {$company->buyer_in_upd=0;}
		if (isset($request->descr)) $descr=$request->descr; else $descr="";
		if (isset($request->show_descr)) $show_descr=1; else $show_descr=0;
		if (isset($request->main_company) && (int)$request->main_company==1 && $_SESSION['roles']<10)
			$main_company=0;
		else
			$main_company=(int)$_SESSION['main_company'];
		if($company->btype==3 && $_SESSION['roles']<10) {
			$main_company=0;
		}
	    //print_r($_GET);
	    //echo $company->kpp;
	    $comp_saved=$company->save();
		if (!isset($company_id)) { // Если это новая компания надо сделать привязку к основной компании
			if ((int)$company->id>0){
				$is_profile=$db->getAll("select id from user_api_config_profiles where main_company_id=?i",(int)$company->id);
                if(count((array)$is_profile)<1){
                    $db->query("insert into user_api_config_profiles (name,main_company_id) values (?s,?i)","По умолчанию",(int)$company->id);
                    $user_config_profile=$db->insertId();
                    $db->query("insert into company_online_profiles (company_id,profile_type,profile_id,user_id) values (?i,?i,?i,?i)",(int)$company->id,3,$user_config_profile,$_SESSION['user_id']);
                }
			    if($db->query("insert ignore into user_companys SET user_id=?i,main_company_id=?i,company_id=?i,btype=?i,descr=?s,show_descr=?i,create_date=?s",$_SESSION['user_id'],$main_company,$company->id,$company->btype,$descr,$show_descr,date("Y-m-d H:i:s"))){
					if ($db->affectedRows()>0){
						$ret['status']="ok";
						$ret['msg']="Клиент успешно добавлен";
						$ret['company_id']=(int)$company->id;
						$ret['company_name']=$company->name;
						$company_balance=new CompanyBalance((int)$company->id);
						$company_balance->Save();
					}
					else {
						//в базе уже есть она удалена
						$db->query("update user_companys set deleted=0,descr=?s,show_descr=?i where user_id=?i and main_company_id=?i and company_id=?i",$descr,$show_descr,$_SESSION['user_id'],$main_company,$company->id);
						if ($db->affectedRows()>0){
							$ret['status']="ok";
							$ret['msg']="Клиент успешно добавлен";
							$ret['company_id']=(int)$company->id;
							$ret['company_name']=$company->name;
							$company_balance=new CompanyBalance((int)$company->id);
							$company_balance->Save();
						}
						else {
							$ret['status']="err";
							$ret['err']="Не удалось добавить компанию";
						}
					}
			    }
			    else {
						$ret['status']="err";
						$ret['err']="Не удалось добавить клиента";
			    }
			}
			else {
			    $comp_id=$db->getOne("select id from company where inn=?i and kpp=?i and name=?s",$company->inn,$company->kpp,$company->name);
			    if ((int)$comp_id>0) {
					$is_profile=$db->getAll("select id from user_api_config_profiles where main_company_id=?i",(int)$comp_id);
					if(count((array)$is_profile)<1){
						$db->query("insert into user_api_config_profiles (name,main_company_id) values (?s,?i)","По умолчанию",(int)$comp_id);
						$user_config_profile=$db->insertId();
						$db->query("insert into company_online_profiles (company_id,profile_type,profile_id,user_id) values (?i,?i,?i,?i)",(int)$comp_id,3,$user_config_profile,$_SESSION['user_id']);
					}
					$db->query("insert ignore into user_companys SET user_id=?i,main_company_id=?i,company_id=?i,btype=?i,descr=?s,show_descr=?i,create_date=?s",$_SESSION['user_id'],$main_company,$comp_id,$company->btype,$descr,$show_descr,date("Y-m-d H:i:s"));
					$ret['status']="ok";
					$ret['msg']="Клиент успешно добавлен";
					$company_balance=new CompanyBalance((int)$comp_id);
					$company_balance->Save();
			    }
			    else {
						$ret['status']="err";
						$ret['err']="Не удалось добавить клиента";
			    }
			}
		}
		else {
			$db->query("update user_companys SET btype=?i,descr=?s,show_descr=?i where main_company_id=?i and company_id=?i",$company->btype,$descr,$show_descr,$main_company,$company->id);
			if($comp_saved){
				//$db->query("update user_companys SET btype=?i where main_company_id=?i and company_id=?i",$company->btype,$main_company,$company->id);
					$ret['status']="ok";
					$ret['msg']="Данные успешно изменены";
					$company_balance=new CompanyBalance((int)$company->id);
					$company_balance->Save();
			}
			else {
				//$db->query("update user_companys SET btype=?i where main_company_id=?i and company_id=?i",$company->btype,$main_company,$company->id);
				$ret['status']="ok";
				$ret['msg']="Данные успешно изменены";
				$company_balance=new CompanyBalance((int)$company->id);
				$company_balance->Save();
			}
		}
		if (isset($request->btype) && (int)$request->btype==3){
			$db->query("insert ignore into user_companys (user_id,main_company_id,company_id,btype,create_date) values (?i,?i,?i,?i,?s)",$_SESSION['user_id'],$company->id,471,1,date("Y-m-d H:i:s"));
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	} // action_save_company


	public static function delete_company($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->company_id)) {
			$company_id=(int)$request->company_id;
			if($company_id==471) return self::_error_arr("Это системный клиент, его вы не можете удалить");
			if($company_id==$_SESSION['main_company']) return self::_error_arr("Это ваша основная компания, его вы не можете удалить");
		}
	    if (isset($company_id) && $company_id>0){
			$is_main_company=$db->getAll("select * from user_companys where company_id=?i and main_company_id=0",$company_id);
			//$res1=$db->query("delete from company where id=?i",$company_id);
			if ((int)$_SESSION['main_company']!=$company_id && empty($is_main_company)){
				//$user_ids=$db->getCol("select user_id from user_companys where main_company_id=0 and company_id=?i",$_SESSION['main_company']);
				$res2=$db->query("update user_companys set deleted=1 where company_id=?i and main_company_id=?i",$company_id,$_SESSION['main_company']);
			}
			else {
				$user_ids=$db->getCol("select user_id from user_companys where main_company_id=0 and company_id=?i",$company_id);
				$res2=$db->query("update user_companys set deleted=1 where company_id=?i and main_company_id=0 and user_id in (?b)",$company_id,$user_ids);
			}
			if ($res2){
				$ret['status']="ok";
				$res3=$db->query("update dogovor set deleted=1 where company_id=?i and user_id=?i",$company_id,(int)$_SESSION['user_id']);
				//$res4=$db->query("delete from ");
				$ret['msg']="";//"Компания успешно удалена";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить компанию";
			}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function restore_company($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
			return self::_error_arr("У Вас нет прав для восстановления удаленных клиентов");
	    }
	    if (isset($request->company_id)) {
			$company_id=(int)$request->company_id;
			if($company_id==471) return self::_error_arr("Это системный клиент, нельзя с ним проводить операций");
			if($company_id==$_SESSION['main_company']) return self::_error_arr("Это ваша основная компания, нельзя с ним проводить операций");
		}
	    if (isset($company_id) && $company_id>0){
			$is_main_company=$db->getAll("select * from user_companys where company_id=?i and main_company_id=0 and deleted=1",$company_id);
			//$res1=$db->query("delete from company where id=?i",$company_id);
			if ((int)$_SESSION['main_company']!=$company_id && empty($is_main_company)){
				//$user_ids=$db->getCol("select user_id from user_companys where main_company_id=0 and company_id=?i",$_SESSION['main_company']);
				$res2=$db->query("update user_companys set deleted=0 where company_id=?i and main_company_id=?i",$company_id,$_SESSION['main_company']);
			}
			else {
				$user_ids=$db->getCol("select user_id from user_companys where main_company_id=0 and company_id=?i",$company_id);
				$res2=$db->query("update user_companys set deleted=0 where company_id=?i and main_company_id=0 and user_id in (?b)",$company_id,$user_ids);
			}
			if ($res2){
				$ret['status']="ok";
				$res3=$db->query("update dogovor set deleted=0 where company_id=?i and user_id=?i",$company_id,(int)$_SESSION['user_id']);
				//$res4=$db->query("delete from ");
				$ret['msg']="Компания успешно восстановлена";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось восстановить компанию";
			}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_clients_xls($request){
		$db = DB::getInstance();
		if($_SESSION['roles']<10){
			$parsed="";
			$user_companys=$db->getInd("company_id","select company_id,btype from user_companys where main_company_id=?i and (btype=1 or btype=0 or btype=3 or btype=4) and deleted=0",$_SESSION['company_id']);
			$sql="select c.*,cb.balance as company_balance,cb.rezerv as company_rezerv
			 from company c 
			 left join company_balance cb on (cb.company_id=c.id and cb.main_company_id=?i)
			 where c.id in (?b) ?p order by c.name";
			if(!empty($request->search_clients_client_name) && empty($request->client_id) && (int)$request->client_id==0){
				$parsed=$db->parse(" and (c.name like ?s or c.mphone like ?s)","%".$request->search_clients_client_name."%","%".$request->search_clients_client_name."%");
				//$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['company_id']);
			}
			if(!empty($request->client_id) && (int)$request->client_id>0){
				$parsed.=$db->parse(" and c.id=?i",(int)$request->client_id);
			}
			if(!empty($request->limit) && (int)$request->limit>0) $sql.=" limit ".(int)$request->limit;
			$res=$db->getAll($sql,$_SESSION['company_id'],array_column($user_companys,"company_id"),$parsed);
			foreach($res as $rkey=>$rval){
				$res[$rkey]['btype']=$user_companys[$rval['id']]['btype'];
			}
		}
		else {
			$user_companys=$db->getInd("company_id","select company_id,btype from user_companys where main_company_id=?i and (btype=1 or btype=0) and user_id=?i and deleted=0",$_SESSION['main_company']);
			$sql="select c.*,cb.balance as company_balance,cb.rezerv as company_rezerv
				from company c 
				left join company_balance cb on (cb.company_id=c.id and cb.main_company_id=?i)
			 	where c.id in (?b)";
	    	$res=$db->getAll($sql,$_SESSION['company_id'],array_column($user_companys,"company_id"),$_SESSION['user_id']);
			foreach($res as $rkey=>$rval){
				$res[$rkey]['btype']=$user_companys[$rval['id']]['btype'];
			}
		}
		$clients=array();
		foreach($res as $cl_key=>$cl_val){
			$cars=$db->getAll("select auto_maker_name,auto_model,vin from company_cars where company_id=?i and main_company_id=?i and deleted=0",$cl_val['id'],$_SESSION['main_company']);
			//$company_car_vins='';
			if($cars){
				foreach($cars as $car){
					//if($company_car_vins=='') $company_car_vins=$car['vin'];
					//else $company_car_vins.=";".$car['vin'];
				
					array_push($clients,array(
						"N"=>$cl_val['id'],
						"Наименование"=>$cl_val['name'],
						"тел"=>$cl_val['mphone'],
						"email"=>$cl_val['email'],
						"адрес"=>$cl_val['address'],
						"инн"=>$cl_val['inn'],
						"кпп"=>$cl_val['kpp'],
						"огрн"=>$cl_val['ogrn'],
						"дата создания"=>$cl_val['create_date'],
						"руководитель"=>$cl_val['ruk'],
						"marka"=>$car['auto_maker_name'],
						"model"=>$car['auto_model'],
						"vin"=>$car['vin'],
						"День рождения"=>($cl_val['birthday']=="0000-00-00"?"":date("d.m.Y",strtotime($cl_val['birthday']))),
					));
				}
			}
			else {
				array_push($clients,array(
					"N"=>$cl_val['id'],
					"Наименование"=>$cl_val['name'],
					"тел"=>$cl_val['mphone'],
					"email"=>$cl_val['email'],
					"адрес"=>$cl_val['address'],
					"инн"=>$cl_val['inn'],
					"кпп"=>$cl_val['kpp'],
					"огрн"=>$cl_val['ogrn'],
					"дата создания"=>$cl_val['create_date'],
					"руководитель"=>$cl_val['ruk'],
					"marka"=>'',
					"model"=>'',
					"vin"=>'',
					"День рождения"=>($cl_val['birthday']=="0000-00-00"?"":date("d.m.Y",strtotime($cl_val['birthday']))),
				));
			}
		}
			$csv = implode(",", array_keys(reset($clients))) . PHP_EOL;
			foreach ($clients as $row) {
				$i=0;
				//var_dump($row);
				foreach($row as $row_val){
					if($i>0) $csv.=","; 
					$csv .= '"'.str_replace('"','\'',$row_val).'"';
					$i++;
					
				}
				$csv.=PHP_EOL; 
				//echo $csv;
			}
			file_put_contents("/tmp/export_clients_".$_SESSION['user_id'].".csv",$csv);
			require 'vendor/autoload.php';

			//use PhpOffice\PhpSpreadsheet\Spreadsheet;
			//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

			$spreadsheet = new Spreadsheet();
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

			/* Set CSV parsing options */

			$reader->setDelimiter(',');
			$reader->setEnclosure('"');
			$reader->setSheetIndex(0);

			/* Load a CSV file and save as a XLS */

			$spreadsheet = $reader->load("/tmp/export_clients_".$_SESSION['user_id'].".csv");
			$writer = new Xlsx($spreadsheet);
			$writer->save("/tmp/export_clients_".$_SESSION['user_id'].".xlsx");

			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/export_clients_".$_SESSION['user_id'].".xlsx"));
			unlink("/tmp/export_clients_".$_SESSION['user_id'].".xlsx");
			unlink("/tmp/export_clients_".$_SESSION['user_id'].".csv");
			return array("status"=>"ok","msg"=>"","file"=>$file);
	}

	public static function get_clients($request) {
	    $db = DB::getInstance();
		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($_SESSION['roles']<10){
			$parsed=""; 
			if(!empty($request->show_deleted) && (int)$request->show_deleted=="on"){
				$uc_parsed.=$db->parse("");
			}
			else {
				$uc_parsed=$db->parse(" and deleted=0");
			}
			$user_companys=$db->getInd("company_id","select company_id,btype,descr,show_descr,deleted 
				from user_companys 
				where main_company_id=?i and (btype=1 or btype=0 or btype=3 or btype=4) ?p",$_SESSION['company_id'],$uc_parsed);
			$sql_count="select count(c.id)
			 from company c 
			 where c.id in (?b) ?p order by c.name";
			$sql="select c.*,cb.balance as company_balance,cb.rezerv as company_rezerv,cb.sum_trade
			 from company c 
			 left join company_balance cb on (cb.company_id=c.id and cb.main_company_id=?i)
			 where c.id in (?b) ?p order by c.name";
			if(!empty($request->search_clients_client_name) && empty($request->client_id) && (int)$request->client_id==0){
				$is_dogovor=$db->getOne("select company_id from dogovor where card_number=?s and main_company=?i and card_number<>0 and deleted=0",$request->search_clients_client_name,$_SESSION['main_company']);
				if($is_dogovor){
					$parsed=$db->parse(" and c.id=?i",$is_dogovor);
				}
				else {
					
					if(mb_strlen($request->search_clients_client_name)==17 || mb_strlen($request->search_clients_client_name)==16){
						$is_car_by_win=$db->getAll("select * from company_cars where vin=?s and main_company_id=?i",$request->search_clients_client_name,$_SESSION['main_company']);
						if($is_car_by_win){
							$parsed=$db->parse(" and c.id in (?b)",array_column($is_car_by_win,'company_id'));
						}
					}
					else{
						if((int)preg_replace("/[^0-9]/","",$request->search_clients_client_name)>999){
							$parsed=$db->parse(" and (c.name like ?s or c.mphone like ?s or c.inn=?i)","%".$request->search_clients_client_name."%","%".$request->search_clients_client_name."%",(int)$request->search_clients_client_name);
						//$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['company_id']);
						}
						else {
							$search_client_name_rus=self::stringSwitcher($request->search_clients_client_name,1);
							$ret['search_client_name_rus']=$search_client_name_rus;
							if($search_client_name_rus!=$request->search_clients_client_name){
								$parsed=$db->parse(" and (c.name like ?s or c.name like ?s or c.mphone like ?s)","%".$request->search_clients_client_name."%","%".$search_client_name_rus."%","%".$request->search_clients_client_name."%");
							}
							else {
								$parsed=$db->parse(" and (c.name like ?s or c.mphone like ?s)","%".$request->search_clients_client_name."%","%".$request->search_clients_client_name."%");
							}
						}
					}
				}
			}
			if(!empty($request->client_id) && (int)$request->client_id>0){
				$parsed.=$db->parse(" and c.id=?i",(int)$request->client_id);
			}
			
			$companys_count=$db->getOne($sql_count,array_column($user_companys,"company_id"),$parsed);
			if($page_size=="all") $page_size=$companys_count;
	    	$pages=ceil($companys_count/$page_size);
			if(isset($request->page)) {
				if((int)$request->page>(int)$pages) $request->page=1;
				$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
			}
			else
				$sql.=" limit 0,".$page_size;
			//if(!empty($request->limit) && (int)$request->limit>0) $sql.=" limit ".(int)$request->limit;
			$res=$db->getAll($sql,$_SESSION['company_id'],array_column($user_companys,"company_id"),$parsed);
			foreach($res as $rkey=>$rval){
				$res[$rkey]['inn']=(strlen($rval['inn'])==9 || strlen($rval['inn'])==11)?"0".$rval['inn']:$rval['inn'];
				$res[$rkey]['kpp']=(strlen($rval['kpp'])==8)?"0".$rval['kpp']:$rval['kpp'];
				$res[$rkey]['btype']=$user_companys[$rval['id']]['btype'];
				$res[$rkey]['descr']=$user_companys[$rval['id']]['descr'];
				$res[$rkey]['deleted']=$user_companys[$rval['id']]['deleted'];
				$res[$rkey]['show_descr']=$user_companys[$rval['id']]['show_descr'];
			}
			
		}
		else {
			$user_companys=$db->getInd("company_id","select company_id,btype from user_companys where main_company_id=?i and (btype=1 or btype=0) and user_id=?i and deleted=0",$_SESSION['main_company']);
			$sql="select c.*,cb.balance as company_balance,cb.rezerv as company_rezerv
				from company c 
				left join company_balance cb on (cb.company_id=c.id and cb.main_company_id=?i)
			 	where c.id in (?b)";
	    	$res=$db->getAll($sql,$_SESSION['company_id'],array_column($user_companys,"company_id"),$_SESSION['user_id']);
			foreach($res as $rkey=>$rval){
				$res[$rkey]['btype']=$user_companys[$rval['id']]['btype'];
			}
		}
		//$ret['parsed']=$parsed;
		//$ret['is_car_by_win']=$is_car_by_win;
		//$ret['is_car_sql']=$db->parse("select * from company_cars where vin=?s and main_company_id=?i",$request->search_clients_client_name,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['clients']=$res;
				$ret['msg']="";
				$sql="select * from company_business_types";
				$btypes=$db->getAll($sql);
				foreach($btypes as $bkey=>$bval){
				    $bistypes[$bval['id']]=$bval['descr'];
				}
				$bistypes[0]="Не определен";
				$ret['btypes']=$bistypes;
				$ret['clients_pages']=$pages;
				if (isset($request->page)) $ret['selected_page']=$request->page;
				if(!empty($request->search_clients_client_name)) $ret['search_clients_client_name']=$request->search_clients_client_name;
				else $ret['search_clients_client_name']="";
				//$ret['user_companys']=$user_companys;
		}
		else {
				$ret['status']="ok";
				$ret['msg']="";
				$ret['clients']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_dealers($request) {
	    $db = DB::getInstance();
		$sql="select c.*,cb.balance as company_balance from company c 
		left join company_balance cb on (cb.company_id=c.id and cb.main_company_id=?i)
		where (c.id in (select company_id from user_companys where main_company_id=?i and (btype=2 or btype=4) and deleted=0) or c.id=?i) ?p";
		$parsed="";
		if(!empty($request->search_clients_dealer_name)){
			$search_dealer_name_rus=self::stringSwitcher($request->search_clients_dealer_name,1);
			$ret['search_dealer_name_rus']=$search_dealer_name_rus;
			if($search_delaer_name_rus!=$request->search_clients_dealer_name){
				$parsed.=$db->parse(" and (c.name like ?s or c.name like ?s or c.mphone like ?s or short_name like ?s ","%".$request->search_clients_dealer_name."%","%".$search_dealer_name_rus."%","%".$request->search_clients_dealer_name."%","%".$request->search_clients_dealer_name."%");
			}
			else {
				$parsed.=$db->parse(" and (c.name like ?s or c.mphone like ?s or short_name like ?s ","%".$request->search_clients_dealer_name."%","%".$request->search_clients_dealer_name."%","%".$request->search_clients_dealer_name."%");
			}
			if((int)$request->search_clients_dealer_name>999999999) $parsed.=$db->parse(" or inn = ?i",(int)$request->search_clients_dealer_name);
			$parsed.=")";
			//$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['company_id'],$_SESSION['main_company'],);
		}
		//else
		$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['company_id'],$_SESSION['main_company'],$parsed);
	    if (is_array($res) && count($res)>0){
			foreach($res as $key=>$val){
				$res[$key]['inn']=(strlen($val['inn'])==9 || strlen($val['inn'])==11)?"0".$val['inn']:$val['inn'];
				$res[$key]['kpp']=(strlen($val['kpp'])==8)?"0".$val['kpp']:$val['kpp'];
			}
				$ret['status']="ok";
				$ret['err']="";
				$ret['dealers']=$res;
				$ret['msg']="";
				$sql="select * from company_business_types";
				$btypes=$db->getAll($sql);
				foreach($btypes as $bkey=>$bval){
				    $bistypes[$bval['id']]=$bval['descr'];
				}
				$bistypes[0]="Не определен";
				$ret['btypes']=$bistypes;
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['dealers']=$res;
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_logistic_companys($request) {
	    $db = DB::getInstance();
	    $sql="select * from company where id in (select company_id from user_companys where main_company_id=?i and btype=5 and deleted=0) or id=?i";
	    $res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
			foreach($res as $key=>$val){
				$res[$key]['inn']=(strlen($val['inn'])==9 || strlen($val['inn'])==11)?"0".$val['inn']:$val['inn'];
				$res[$key]['kpp']=(strlen($val['kpp'])==8)?"0".$val['kpp']:$val['kpp'];
			}
			$ret['status']="ok";
			$ret['err']="";
			$ret['logistic_companys']=$res;
			$ret['msg']="";
			$sql="select * from company_business_types";
			$btypes=$db->getAll($sql);
			foreach($btypes as $bkey=>$bval){
				$bistypes[$bval['id']]=$bval['descr'];
			}
			$bistypes[0]="Не определен";
			$ret['btypes']=$bistypes;
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['logistic_companys']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_my_companies($request) {
	    $db = DB::getInstance();
		//if($_SESSION['roles']<10) $sql="select * from company where id in (select company_id from user_companys where user_id=?i and main_company_id=0) or id=?i";
		if($_SESSION['roles']<10) { 
			$sql="select * from company where id in (select company_id from user_companys where user_id=?i and main_company_id=0 and deleted=0)";
			$res=$db->getAll($sql,$_SESSION['user_id']);
		}
	    else {
			$sql="select * from company where id in (select company_id from user_companys where user_id=?i and deleted=0) or id=?i";
			$res=$db->getAll($sql,$_SESSION['user_id'],$_SESSION['company_id']);
		}
	    if (is_array($res) && count($res)>0){
			foreach($res as $key=>$val){
				$res[$key]['inn']=(strlen($val['inn'])==9 || strlen($val['inn'])==11)?"0".$val['inn']:$val['inn'];
				$res[$key]['kpp']=(strlen($val['kpp'])==8)?"0".$val['kpp']:$val['kpp'];
			}
			$ret['status']="ok";
			$ret['err']="";
			$ret['clients']=$res;
			$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function load_company_data($request) {
		$db = DB::getInstance();
		$user_company_info=$db->getRow("select company_id,descr,show_descr from user_companys where main_company_id=?i and company_id=?i",$_SESSION['main_company'],(int)$request->company_id);
	    if (isset($request->company_id) && (int)$request->company_id>0) {
				$company=new Company((int)$request->company_id);
				if ($company->id>=0){
				    $ret['id']=$company->id;
				    $ret['type']=$company->type;
				    $ret['name']=$company->name;
					$ret['short_name']=$company->short_name;
				    $ret['mphone']=$company->mphone;
				    $ret['address']=$company->address;
				    $ret['email']=$company->email;
				    if($user_company_info) {
						$ret['descr']=$user_company_info['descr']!=null?$user_company_info['descr']:"";
						$ret['show_descr']=$user_company_info['show_descr'];
					}
					else {
						$ret['descr']=$company->descr;
						$ret['show_descr']=0;
					}
				    $ret['fullname']=$company->fullname;
				    $ret['inn']=(strlen($company->inn)==9 || strlen($company->inn)==11)?"0".$company->inn:$company->inn;
				    $ret['kpp']=(strlen($company->kpp)==8)?"0".$company->kpp:$company->kpp;
				    $ret['ogrn']=$company->ogrn;
					$ret['okpo']=$company->okpo;
					$ret['okved']=$company->okved;
				    $ret['fax']=$company->fax;
				    $ret['ruk']=$company->ruk;
					$ret['rukdol']=$company->rukdol;
					$ret['buh']=$company->buh;
					$ret['birthday']=$company->birthday;
				    $ret['buhdol']=$company->buhdol;
				    $ret['create_date']=$company->create_date;
				    $ret['btype']=$company->btype;
				    $ret['bank']=$company->bank;
				    $ret['rs']=$company->rs;
				    $ret['ks']=$company->ks;
				    $ret['bik']=$company->bik;
					$ret['ipreg_num']=$company->ipreg_num;
					$ret['ipreg_date']=$company->ipreg_date;
					$ret['buyer_in_upd']=$company->buyer_in_upd;
				    //$ret['btype']=$company->btype;
				    $ret['tax_type']=$company->tax_type;
				    $ret['main_org_id']=$company->main_org_id;
				    if((int)$company->main_org_id>0) $ret['main_org_name']=$db->getOne("select name from company where id=?i",$company->main_org_id);
					else $ret['main_org_name']="";
				    $company_balance=$db->getRow("select balance,rezerv,sum_trade,cashback from company_balance where company_id=?i and main_company_id=?i",$company->id,$_SESSION['main_company']);
				    $ret['company_balance']=(float)$company_balance['balance'];
					$ret['company_cashback']=(float)$company_balance['cashback'];
					$ret['sum_trade']=(float)$company_balance['sum_trade'];
				    $ret['company_rezerv']=(float)$company_balance['rezerv'];
				    $delivery_addresses=$db->getAll("select id,company_id,delivery_address,delivery_days,delivery_time_start,delivery_time_stop from delivery_address where company_id=?i",$company->id);
				    $ret['delivery_addresses']=$delivery_addresses;
				    $rekvizits=$db->getAll("select id,rs,ks,bik,bank from company_rekvizits where company_id=?i and main_company=?i and deleted=0",$company->id,$_SESSION['main_company']);
				    $ret['rekvizits']=$rekvizits;
					$ret['timezone']=$company->timezone;
				    $ret['status']="ok";
				    $ret['msg']="";

				}
				else {
					//$ret['status']="err";
					//$ret['err']="Необходимо ввести данные компании";
				}
			}
			$c_types=$db->getAll("select * from company_types");
			$i=0;
			foreach($c_types as $c_key => $c_val){
					$ret['company_types'][$i]['id']=$c_val['id'];
					$ret['company_types'][$i]['type']=$c_val['type'];
					$ret['company_types'][$i]['okopf']=$c_val['okopf'];
					$i++;
			}
			$b_types=$db->getAll("select * from company_business_types");
			$i=0;
			foreach($b_types as $b_key => $b_val){
					$ret['company_btypes'][$i]['id']=$b_val['id'];
					$ret['company_btypes'][$i]['descr']=$b_val['descr'];
					$i++;
			}
			$tax_types=$db->getAll("select * from tax_type where deleted=0");
			$i=1;
				$ret['tax_types'][0]['id']=0;
				$ret['tax_types'][0]['name']="-";
				$ret['tax_types'][0]['tax_rate']="0";
			foreach($tax_types as $b_key => $b_val){
					$ret['tax_types'][$i]['id']=$b_val['id'];
					$ret['tax_types'][$i]['name']=$b_val['name'];
					$ret['tax_types'][$i]['tax_rate']=$b_val['tax_rate'];
					$i++;
			}
	    if (isset($ret)) return $ret;
	}

	public static function load_my_company_data($request) {
		$db = DB::getInstance();
		$my_companys=$db->getCol("select company_id from user_companys where main_company_id=0 and user_id=?i",$_SESSION['user_id']);
	    if (isset($request->company_id) && in_array($request->company_id,$my_companys)) {
				$company=new Company((int)$request->company_id);
				if ($company->id>=0){
				    $ret['id']=$company->id;
				    $ret['type']=$company->type;
				    $ret['name']=$company->name;
				    $ret['mphone']=$company->mphone;
				    $ret['address']=$company->address;
				    $ret['email']=$company->email;
				    $ret['descr']=$company->descr;
				    $ret['fullname']=$company->fullname;
				    $ret['inn']=(strlen($company->inn)==9 || strlen($company->inn)==11)?"0".$company->inn:$company->inn;
				    $ret['kpp']=(strlen($company->inn)==8)?"0".$company->kpp:$company->kpp;
				    $ret['ogrn']=$company->ogrn;
					$ret['okpo']=$company->okpo;
					$ret['okved']=$company->okved;
				    $ret['fax']=$company->fax;
				    $ret['ruk']=$company->ruk;
					$ret['rukdol']=$company->rukdol;
					$ret['buh']=$company->buh;
				    $ret['buhdol']=$company->buhdol;
				    $ret['create_date']=$company->create_date;
				    $ret['btype']=$company->btype;
				    $ret['bank']=$company->bank;
				    $ret['rs']=$company->rs;
				    $ret['ks']=$company->ks;
				    $ret['bik']=$company->bik;
					$ret['ipreg_num']=$company->ipreg_num;
					$ret['ipreg_date']=$company->ipreg_date;
				    //$ret['btype']=$company->btype;
				    $ret['tax_type']=$company->tax_type;
				    
				    $c_types=$db->getAll("select * from company_types");
				    $i=0;
				    foreach($c_types as $c_key => $c_val){
							$ret['company_types'][$i]['id']=$c_val['id'];
							$ret['company_types'][$i]['type']=$c_val['type'];
							$ret['company_types'][$i]['okopf']=$c_val['okopf'];
							$i++;
				    }
				    $b_types=$db->getAll("select * from company_business_types");
				    $i=0;
				    foreach($b_types as $b_key => $b_val){
							$ret['company_btypes'][$i]['id']=$b_val['id'];
							$ret['company_btypes'][$i]['descr']=$b_val['descr'];
							$i++;
				    }
				    $tax_types=$db->getAll("select * from tax_type where deleted=0");
				    $i=1;
						$ret['tax_types'][0]['id']=0;
						$ret['tax_types'][0]['name']="-";
						$ret['tax_types'][0]['tax_rate']="0";
				    foreach($tax_types as $b_key => $b_val){
							$ret['tax_types'][$i]['id']=$b_val['id'];
							$ret['tax_types'][$i]['name']=$b_val['name'];
							$ret['tax_types'][$i]['tax_rate']=$b_val['tax_rate'];
							$i++;
				    }
				    $company_balance=$db->getRow("select balance,rezerv from company_balance where company_id=?i and main_company_id=?i",$company->id,$_SESSION['main_company']);
				    $ret['company_balance']=(float)$company_balance['balance'];
				    $ret['company_rezerv']=(float)$company_balance['rezerv'];
				    $delivery_addresses=$db->getAll("select id,company_id,delivery_address,delivery_days,delivery_time_start,delivery_time_stop from delivery_address where company_id=?i",$company->id);
				    $ret['delivery_addresses']=$delivery_addresses;
				    $rekvizits=$db->getAll("select id,rs,ks,bik,bank from company_rekvizits where company_id=?i and deleted=0",$company->id);
				    $ret['rekvizits']=$rekvizits;
				    $ret['status']="ok";
				    $ret['msg']="";

				}
				else {
					//$ret['status']="err";
					//$ret['err']="Необходимо ввести данные компании";
				}
			}
	    if (isset($ret)) return $ret;
	}

	public static function get_company_data_from_api($request){
		if (isset($request->inn) && (int)$request->inn>0) $inn=$request->inn;
		if (isset($request->org_name) && $request->org_name!="") $org_name=$request->org_name;
			$api_key="4e4c3f5a453e7eae95343a3c88f7518a20d31af3";

		if (isset($inn) || isset($org_name)) {
			if (isset($inn)) {
				$postfield='{ "query": "'.$inn.'" }';
				$data=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party",array("Content-Type: application/json","Accept: application/json","Authorization: Token ".$api_key),$postfield,true,true);
			}
			else
				if (isset($org_name)) {
					$postfield=json_encode(array("query" => $org_name));
					$data=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party",array("Content-Type: application/json","Accept: application/json","Authorization: Token ".$api_key),$postfield,true,true);
				}
		return json_decode($data['body'],true);
		}
		else
		return array("status"=>"error");
	}

	public static function get_bank_data_from_api($request){
		if (isset($request->bik) && (int)$request->bik>0) {
			$bik=$request->bik;
			$api_key="4e4c3f5a453e7eae95343a3c88f7518a20d31af3";

			if (isset($bik)) {
				$postfield='{ "query": "'.$bik.'" }';
				$data=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/bank",array("Content-Type: application/json","Accept: application/json","Authorization: Token ".$api_key),$postfield,true,true);
			}
			else
				if (isset($org_name)) {
					$postfield=json_encode(array("query" => $org_name));
					$data=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party",array("Content-Type: application/json","Accept: application/json","Authorization: Token ".$api_key),$postfield,true,true);
				}
			return json_decode($data['body'],true);
		}
		else 
		return array("status"=>"error", "err"=>"Не указан БИК");
	}

	private static function resolve_site_id($request) {
		$db = DB::getInstance();
		$site_id = (int)($request->site_id ?? 0);
		if ($site_id > 0) return $site_id;

		preg_match("/https*:\/\/([^\/]+)\/* /", $_SERVER['HTTP_REFERER'] ?? '', $origin);
		if (!empty($origin[1])) {
			$by_ref = (int)$db->getOne("SELECT id FROM company_sites WHERE site_name = ?s", str_replace("www.", "", $origin[1]));
			if ($by_ref) return $by_ref;
		}
		$by_company = (int)$db->getOne("SELECT id FROM company_sites WHERE company_id = ?i LIMIT 1", $_SESSION['main_company'] ?? 0);
		return $by_company;
	}

	public static function get_company_sites($request){
		$db = DB::getInstance();
		$ret=array();
		$sql="select * from company_sites where company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)";
		$company_sites=$db->getAll($sql,$_SESSION['user_id']);
		if(count((array)$company_sites)>0){
			$ret['status']="ok";
			$ret['msg']="";
			$ret['company_sites']=$company_sites;
			$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
			$ret['err']="";
		}
		else {
			$ret['status']="ok";
			$ret['msg']="";
			$ret['company_sites']=[];
			$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
			$ret['err']="";
		}
		return $ret;
	}

	public static function get_company_site($request){
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return array("status"=>"err","error"=>"Не указан id сайта");
		$ret=array();
		$sql="select * from company_sites where company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i) and id=?i";
		$company_sites=$db->getRow($sql,$_SESSION['user_id'],$site_id);
		if(count((array)$company_sites)>0){
			if(!empty($company_sites['theme_palette'])) $company_sites['theme_palette'] = json_decode($company_sites['theme_palette'], true);
			if(!empty($company_sites['pwa'])) $company_sites['pwa'] = json_decode($company_sites['pwa'], true);
			$ret['status']="ok";
			$ret['msg']="";
			$ret['company_site']=$company_sites;
			$ret['headers']=$db->getAll("select id,name,uri,value,enabled from company_sites_header where site_id=?i",$site_id);;
			$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['err']="Не найден сайт";
		}
		return $ret;
	}

	public static function get_colors_site($request){
		$db = DB::getInstance();
		$ret=array();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return array("status"=>"err","error"=>"Не указан id сайта");

		$sql = "select cs.id_site_color from company_sites cs where cs.id = ?i";
		$id_site_color = $db->getOne($sql, $site_id);
		
		if(is_null($id_site_color)){
			$db->query('insert into sites_colors set color="#fff", color_dark="#4377FD", text_in_color_dark="#fff", color_button="#515466", text_color_in_button="#fff", color_links="#000", color_links_analog="#000", color_footer="#f2f5f9"');
			if($db->affectedRows()>0){
				$id_sites_colors = $db->insertId();
				$sql = "update company_sites set id_site_color=?i where id=?i";
				$db->query($sql, $id_sites_colors, $site_id);
				$sql = "select * from sites_colors sc where sc.id = ?i";
				$colors = $db->getRow($sql, $id_sites_colors);
				$colors_id = $colors['id'];
				unset($colors['id']);

				$ret['status']="ok";
				$ret['msg']="";
				$ret['err']="";
				$ret['colors']=$colors;
				$ret['id_colors'] = $colors_id;
			}
			else {
				$ret['status']="err";
				$ret['msg']="";
				$ret['err']="Ошибка при добавление цветовой палитры";
			}
		}else{
			$sql = "select * from sites_colors sc where sc.id = ?i";
			$colors = $db->getRow($sql, $id_site_color);
			$colors_id = $colors['id'];
			unset($colors['id']);
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			$ret['colors']=$colors;
			$ret['id_colors'] = $colors_id;
		}
		
		return $ret;
	}

	public static function save_colors_site($request){
		if (!isset($request->id_color_site)) return array("status"=>"err","error"=>"Не указан id цветовой палитры");

		$db = DB::getInstance();
		$ret=array();

		if (!isset($request->color)) $request->color = "#fff";
		if (!isset($request->color_dark)) $request->color_dark="#4377FD";
		if (!isset($request->text_in_color_dark)) $request->text_in_color_dark="#fff";
		if (!isset($request->color_button)) $request->color_button="#515466";
		if (!isset($request->text_color_in_button)) $request->text_color_in_button="#fff";
		if (!isset($request->color_links)) $request->color_links="#000";
		if (!isset($request->color_links_analog)) $request->color_links_analog="#000";
		if (!isset($request->color_footer)) $request->color_footer="#f2f5f9";
		
		$sql = 'update sites_colors set color=?s, color_dark=?s, text_in_color_dark=?s, color_button=?s, text_color_in_button=?s, color_links=?s, color_links_analog=?s, color_footer=?s where id=?i';
		$db->query($sql, $request->color, $request->color_dark, $request->text_in_color_dark, $request->color_button, $request->text_color_in_button, $request->color_links, $request->color_links_analog, $request->color_footer, $request->id_color_site);
		if($db->affectedRows()>0){
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['msg']="";
			$ret['err']="Ошибка при смене цвета";
		}
		
		return $ret;
	}

	public static function add_company_site($request){
		$db = DB::getInstance();
		$ret=array();
		if(empty($request->site_name)) return self::_error_arr("Название сайта не должно быть пустым");
		$sql="select * from company_sites where site_name=?s"; // <-- where company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i) and
		$company_sites=$db->getAll($sql,$request->site_name);
		if(count((array)$company_sites)>0){
			$ret['status']="err";
			$ret['msg']="";
			//$ret['company_sites']=$company_sites;
			//$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
			$ret['err']="Такой сайт уже заведен";
		}
		else {
			$db->query("insert into company_sites set company_id=?i,site_name=?s",$_SESSION['main_company'],$request->site_name);
			if($db->affectedRows()>0){
				$ret['status']="ok";
				$ret['msg']="";
				$ret['err']="";
			}
			else {
				$ret['status']="err";
				$ret['msg']="";
				//$ret['company_sites']=$company_sites;
				//$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
				$ret['err']="Ошибка при заведении сайта";
			}
		}
		return $ret;
	}

	public static function save_company_site($request){
		$db = DB::getInstance();
		$ret=array();
		if(empty($request->site_name)) return self::_error_arr("Название сайта не должно быть пустым");
		if(empty($request->site_id) || (int)$request->site_id<1) {
			$request->site_id = self::resolve_site_id($request);
		}
		$sql="select * from company_sites where site_name=?s";
		$company_site=$db->getRow($sql,$request->site_name);
		if((int)$request->site_id==0 && (int)$company_site['company_id']!=$_SESSION["main_company"] && (int)$company_site['id']>0){
				return self::_error_arr("Такое наименование сайта уже заведено");
		}
		else {
			$parsed="";
			if(isset($request->site_title)) $parsed.=$db->parse(",site_title=?s",$request->site_title);
			if(isset($request->hero_title)) $parsed.=$db->parse(",hero_title=?s",$request->hero_title);
			if(isset($request->hero_subtitle)) $parsed.=$db->parse(",hero_subtitle=?s",$request->hero_subtitle);
			if(isset($request->vin_enabled)) $parsed.=$db->parse(",vin_enabled=?i",(int)$request->vin_enabled);
			if(isset($request->headers)){
				$headers = $request->headers;
				$sql = "update company_sites_header set value=?s,enabled=?i where id=?i";
				for ($i=0; $i < count((array)$headers); $i++) { 
					$db->query($sql,$headers[$i]['value'],$headers[$i]['enabled'],(int)$headers[$i]['id']);
				}
			}
			// if(isset($request->site_about)) $parsed.=$db->parse(",about=?s",$request->site_about);
			// if(isset($request->site_delivery)) $parsed.=$db->parse(",delivery=?s",$request->site_delivery);
			// if(isset($request->site_oferta)) $parsed.=$db->parse(",oferta=?s",$request->site_oferta);
			// if(isset($request->site_payment)) $parsed.=$db->parse(",payment=?s",$request->site_payment);
			if(isset($request->site_privacy)) $parsed.=$db->parse(",privacy=?s",$request->site_privacy);
			// if(isset($request->site_contacts)) $parsed.=$db->parse(",contacts=?s",$request->site_contacts);
			if(isset($request->site_text_on_main)) $parsed.=$db->parse(",text_on_main=?s",$request->site_text_on_main);
			if(isset($request->shop_coords)) $parsed.=$db->parse(",shop_coords=?s",$request->shop_coords);
			if(isset($request->shop_address)) $parsed.=$db->parse(",shop_address=?s",$request->shop_address);
			if(isset($request->shop_telegram)) $parsed.=$db->parse(",shop_telegram=?s",$request->shop_telegram);
			if(isset($request->shop_whatsapp)) $parsed.=$db->parse(",shop_whatsapp=?s",$request->shop_whatsapp);
			if(isset($request->shop_logo)) $parsed.=$db->parse(",shop_logo=?s",$request->shop_logo);
			if(isset($request->favicon)) $parsed.=$db->parse(",favicon=?s",$request->favicon);
			if(isset($request->shop_viber)) $parsed.=$db->parse(",shop_viber=?s",$request->shop_viber);
			if(isset($request->shop_phone)) $parsed.=$db->parse(",shop_phone=?s",$request->shop_phone);
			if(isset($request->shop_email)) $parsed.=$db->parse(",shop_email=?s",$request->shop_email);
			if(isset($request->disabled_categorys)) $parsed.=$db->parse(",disabled_categorys=?s",$request->disabled_categorys);
			// if(isset($request->about_enabled) && $request->about_enabled=="on") $parsed.=$db->parse(", about_enabled=?i", 1);
			// else $parsed.=$db->parse(", about_enabled=?i", 0);
			// if(isset($request->delivery_enabled) && $request->delivery_enabled=="on") $parsed.=$db->parse(", delivery_enabled=?i", 1);
			// else $parsed.=$db->parse(", delivery_enabled=?i", 0);
			// if(isset($request->payment_enabled) && $request->payment_enabled=="on") $parsed.=$db->parse(", payment_enabled=?i", 1);
			// else $parsed.=$db->parse(", payment_enabled=?i", 0);
			// if(isset($request->return_garant_enabled) && $request->return_garant_enabled=="on") $parsed.=$db->parse(", return_garant_enabled=?i", 1);
			// else $parsed.=$db->parse(", return_garant_enabled=?i", 0);
			// if(isset($request->oferta_enabled) && $request->oferta_enabled=="on") $parsed.=$db->parse(", oferta_enabled=?i", 1);
			// else $parsed.=$db->parse(", oferta_enabled=?i", 0);
			if(isset($request->privacy_enabled) && $request->privacy_enabled=="on") $parsed.=$db->parse(", privacy_enabled=?i", 1);
			else $parsed.=$db->parse(", privacy_enabled=?i", 0);
			// if(isset($request->shop_verify_phone) && $request->shop_verify_phone=="on") $parsed.=$db->parse(",shop_verify_phone=?i",1);
			// else $parsed.=$db->parse(",shop_verify_phone=?i",0);
			if(isset($request->use_catalog_sort1) && $request->use_catalog_sort1=="on") $parsed.=$db->parse(",use_catalog_sort1=?i",1);
			else $parsed.=$db->parse(", use_catalog_sort1=?i", 0);
			// if(isset($request->contacts_enabled) && $request->contacts_enabled=="on") $parsed.=$db->parse(", contacts_enabled=?i", 1);
			// else $parsed.=$db->parse(", contacts_enabled=?i", 0);

			if(isset($request->text_on_main_enabled) && $request->text_on_main_enabled=="on") $parsed.=$db->parse(", text_on_main_enabled=?i", 1);
			else $parsed.=$db->parse(", text_on_main_enabled=?i", 0);

			if(isset($request->popular_parts_enabled) && $request->popular_parts_enabled=="on") $parsed.=$db->parse(", popular_parts_enabled=?i", 1);
			else $parsed.=$db->parse(", popular_parts_enabled=?i", 0);
			if(isset($request->parts_by_categorys_enabled) && $request->parts_by_categorys_enabled=="on") $parsed.=$db->parse(", parts_by_categorys_enabled=?i", 1);
			else $parsed.=$db->parse(", parts_by_categorys_enabled=?i", 0);
			if(isset($request->popular_goods_enabled) && $request->popular_goods_enabled=="on") $parsed.=$db->parse(", popular_goods_enabled=?i", 1);
			else $parsed.=$db->parse(", popular_goods_enabled=?i", 0);
			if(isset($request->popular_categories) && $request->popular_categories=="on") $parsed.=$db->parse(", popular_categories=?i", 1);
			else $parsed.=$db->parse(", popular_categories=?i", 0);
			if(isset($request->find_to_vin_enabled) && $request->find_to_vin_enabled=="on") $parsed.=$db->parse(", find_to_vin_enabled=?i", 1);
			else $parsed.=$db->parse(", find_to_vin_enabled=?i", 0);
			if(isset($request->request_vin_enabled) && $request->request_vin_enabled=="on") $parsed.=$db->parse(", request_vin_enabled=?i", 1);
			else $parsed.=$db->parse(", request_vin_enabled=?i", 0);
			if(isset($request->yandex_rating_enabled) && $request->yandex_rating_enabled=="on") $parsed.=$db->parse(", yandex_rating_enabled=?i", 1);
			else $parsed.=$db->parse(", yandex_rating_enabled=?i", 0);
			if(isset($request->laximo_enabled) && $request->laximo_enabled=="on") $parsed.=$db->parse(", laximo_enabled=?i", 1);
			else $parsed.=$db->parse(", laximo_enabled=?i", 0);

			if(isset($request->tg_chat_id)) $parsed.=$db->parse(",tg_chat_id=?s",$request->tg_chat_id);
			if(isset($request->yandex_rating_value)) $parsed.=$db->parse(",yandex_rating_value=?s",$request->yandex_rating_value);
			if(isset($request->shop_sms_apikey)) $parsed.=$db->parse(",shop_sms_apikey=?s",$request->shop_sms_apikey);
			if(isset($request->site_return_garant)) $parsed.=$db->parse(",return_garant=?s",$request->site_return_garant);
			if(isset($request->select_catalog)) {if ($request->select_catalog != 0){$parsed.=$db->parse(",id_catalog=?i", $request->select_catalog);}else{$parsed.=$db->parse(",id_catalog=?i", null);}}
			else $parsed.=$db->parse(",id_catalog=?i", null);
			if(isset($request->catalog_config)) $parsed.=$db->parse(",catalog_config=?s",json_encode($request->catalog_config));
			if((int)$request->site_id>0){
				$db->query("update company_sites set company_id=?i,site_name=?s?p where id=?i",$_SESSION['main_company'],$request->site_name,$parsed,$request->site_id);
				//if($db->affectedRows()>0){
					$ret['status']="ok";
					$ret['msg']="";
					//$ret['company_sites']=$company_sites;
					//$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
					$ret['err']="";
				//}
				//else {
				//	$ret['status']="err";
				//	$ret['msg']="";
					//$ret['company_sites']=$company_sites;
					//$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
				//	$ret['err']="Ошибка при изменении данных";
				//}
			}
			else {
				$db->query("insert ignore into company_sites set company_id=?i,site_name=?s?p",$_SESSION['main_company'],$request->site_name,$parsed);
				$site_id = $db->insertId();
				$data = [
					[
						'site_id' => $site_id,
						'header_name' => 'О нас',
					],
					[
						'site_id' => $site_id,
						'header_name' => 'Доставка',
					],
					[
						'site_id' => $site_id,
						'header_name' => 'Оплата',
					],
					[
						'site_id' => $site_id,
						'header_name' => 'Возврат и гарантия',
					],
					[
						'site_id' => $site_id,
						'header_name' => 'Оферта',
					],
					[
						'site_id' => $site_id,
						'header_name' => 'Контакты',
					],
				];
				foreach ($data as $request) {
					$request = (object)$request;
					$result = self::save_company_site_header($request);
					
					// обрабатываем результат
				   
					if($result['status']== "ok") {
					  continue;
					}
					else {
						return self::_error_arr($result['err']);
					}
				}  
				if($db->affectedRows()>0){
					$ret['status']="ok";
					$ret['msg']="";
					$ret['err']="";
				}
				else {
					$ret['status']="err";
					$ret['msg']="";
					//$ret['company_sites']=$company_sites;
					//$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
					$ret['err']="Ошибка при заведении сайта";
				}
			}
		}		
		return $ret;
	}

	public static function save_company_site_header($request){
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if($site_id < 1) return self::_error_arr("Не указан сайт");
		if(empty($request->header_name)) return self::_error_arr("Название заголовка не должно быть пустым");
		else $header = $request->header_name;
		if(!empty($request->header_id)) $header_id = (int)$request->header_id;

		$uri = Functions::translitIt($header);
		$uri = Functions::translitUrl($uri);
		$uri = str_replace('--', '-', $uri);

		if($header_id != 0){
			$sql="UPDATE company_sites_header SET name=?s, uri=?s WHERE id=?i";
			$db->query($sql,$header,$uri,$header_id);
		}
		else{
			$sql="INSERT IGNORE INTO company_sites_header (site_id, name, uri) VALUES (?i, ?s, ?s)";
			$db->query($sql,$site_id,$header,$uri);
			$header_id = $db->insertId();
		}
		
		if($db->affectedRows()>0){
			$ret['status']="ok";
			$ret['header']=$db->getRow("select * from company_sites_header where id=?i",$header_id);;
			$ret['msg']="";
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['msg']="";
			$ret['err']="Ошибка при изменении данных";
		}
		return $ret;
	}

	public static function delete_company_site($request){
		$db = DB::getInstance();
		$ret=array();
		$site_id = self::resolve_site_id($request);
		if($site_id < 1) return self::_error_arr("Не знаю что удалять");
		$sql="delete from company_sites where id=?i and company_id=?i";
		$company_site=$db->getRow($sql,$site_id,$_SESSION['main_company']);
		if($db->affectedRows()>0){
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['msg']="";
			$ret['err']="Ошибка при изменении данных";
				//}
		}
		return $ret;
	}

	public static function get_site_data($request){
		preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
		$db = DB::getInstance();
		//$sql="select about,delivery,payment,return_garant,oferta,privacy,shop_coords,shop_address,shop_telegram,shop_whatsapp,shop_viber from company_sites where site_name=?s"; 
		//$ret_data=$db->getRow($sql,$origin[1]);
		switch($request->request_data){
			case "about": $sql="select about,shop_coords,shop_address from company_sites where site_name=?s"; $ret_data=$db->getRow($sql,str_replace("www.","",$origin[1])); break;
			case "id_catalog": $sql="select id_catalog, catalog_config from company_sites where site_name=?s"; $ret_data=$db->getRow($sql,str_replace("www.","",$origin[1])); break;
			case "delivery": $sql="select delivery from company_sites where site_name=?s"; $ret_data=$db->getOne($sql,str_replace("www.","",$origin[1])); break;
			case "payment": $sql="select payment from company_sites where site_name=?s"; $ret_data=$db->getOne($sql,str_replace("www.","",$origin[1])); break;
			case "return_garant": $sql="select return_garant from company_sites where site_name=?s"; $ret_data=$db->getOne($sql,str_replace("www.","",$origin[1])); break;
			case "oferta": $sql="select oferta from company_sites where site_name=?s"; $ret_data=$db->getOne($sql,str_replace("www.","",$origin[1])); break;
			case "privacy": $sql="select privacy from company_sites where site_name=?s"; $ret_data=$db->getOne($sql,str_replace("www.","",$origin[1])); break;
			case "all": 
				$sql="select text_on_main,shop_coords,shop_address,shop_telegram,shop_whatsapp,shop_viber,shop_phone,shop_email,site_name,site_title,hero_title,hero_subtitle,shop_logo,favicon,id_catalog, catalog_config, text_on_main_enabled,privacy,privacy_enabled,popular_parts_enabled,parts_by_categorys_enabled,popular_goods_enabled,popular_categories,find_to_vin_enabled,request_vin_enabled,tg_chat_id,yandex_rating_enabled,yandex_rating_value,laximo_enabled,theme_palette,pwa,vin_enabled from company_sites where site_name=?s"; 
				$ret_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
				$ret_data['headers'] = $db->getAll('select name,uri,value,enabled from company_sites_header where site_id=(select id from company_sites where site_name=?s)',str_replace("www.","",$origin[1]));
				if (!empty($ret_data['theme_palette'])) $ret_data['theme_palette'] = json_decode($ret_data['theme_palette'], true);
				if (!empty($ret_data['pwa'])) $ret_data['pwa'] = json_decode($ret_data['pwa'], true);
				break;
		}
		//$ret_data=$db->getOne($sql,$origin[1]);
		if($ret_data) {
			return array("status"=>"ok","msg"=>"","data"=>$ret_data);
		}
		else {
			return array("status"=>"ok","err"=>"data is empty","data"=>"");
		}
	}

	public static function get_main_orgs($request) {
	    $db = DB::getInstance();
	    $sql="select id,inn,kpp,name from company where inn=?i and main_org_id=0";
	    $res=$db->getAll($sql,(int)$request->inn);
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['main_orgs']=$res;
				$ret['msg']="";
				$sql="select * from company_business_types";
				$btypes=$db->getAll($sql);
				foreach($btypes as $bkey=>$bval){
				    $bistypes[$bval['id']]=$bval['descr'];
				}
				$bistypes[0]="Не определен";
				$ret['btypes']=$bistypes;
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['main_orgs']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function update_company_balance($request){
		if((int)$request->company_id<=0){
			return array("status"=>"err","err"=>"Не указана компания");
		}
		$db = DB::getInstance();
		$res=$db->query("update company_balance set balance=?s where company_id=?i and main_company_id=?i",(float)$request->balance,(int)$request->company_id,$_SESSION['main_company']);
		if($res){
			return array("status"=>"ok","msg"=>"Баланс успешно изменен");
		}
		else {
			return array("status"=>"err","err"=>"Не удалось изменить баланс");
		}
	}

	public static function delete_site_header($request){
		$db = DB::getInstance();
		$ret=array();
		if(empty($request->header_id) || (int)$request->header_id<1) return self::_error_arr("Нет заголовка");
		$sql="delete from company_sites_header where id=?i";
		$company_site=$db->getRow($sql,$request->header_id);
		if($db->affectedRows()>0){
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['msg']="";
			$ret['err']="Ошибка при изменении данных";
		}
		return $ret;
	}

	public static function get_laximo_data($request){
		$db = DB::getInstance();
		if(empty($request->site_id) || (int)$request->site_id<1) {
			preg_match("/https*:\/\/([^\/]+)\/*/",$_SERVER['HTTP_REFERER'],$origin);
			$request->site_id = $db->getOne("select id from company_sites where site_name = ?s", str_replace("www.","",$origin[1]));
			if(empty($request->site_id) || (int)$request->site_id<1) {return self::_error_arr("Не указан сайт");}
		}
		$ret=array();
		$sql = "SELECT cs.laximo_login, cs.laximo_key  FROM company_sites cs where cs.id = ?i";
		$laximo_data = $db->getRow($sql, $request->site_id);
		if (is_array($laximo_data) && count($laximo_data)>0){
			$ret['status']="ok";
			$ret["laximo_data"]=$laximo_data;
			$ret['msg']="";
			$ret['err']="";
		}else{
			$ret['status']="err";
			$ret['msg']="";
			$ret['err']="";
		}
		return $ret;
	}

	public static function save_laximo_data($request){
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if($site_id < 1) return self::_error_arr("Не указан сайт");
		if(!isset($request->laximo_login)) return self::_error_arr("Не указаны данные пользователя");
		if(!isset($request->laximo_key)) return self::_error_arr("Не указаны данные пользователя");
		$res = $db->query('update company_sites set laximo_login = ?s, laximo_key = ?s where id = ?i', $request->laximo_login, $request->laximo_key, $site_id);
		if($res){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return array("status"=>"err","err"=>"");
		}
	}

	public static function save_site_colors($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$palette = (array)($request->palette ?? []);
		$tokens = ['bg','surface','surface2','text','muted','border','primary','primaryFg','success','danger'];
		$themes = ['light','dark'];

		foreach ($themes as $theme) {
			if (!isset($palette[$theme])) return self::_error_arr("Отсутствует тема: ".$theme);
			foreach ($tokens as $t) {
				$v = $palette[$theme][$t] ?? '';
				if (!preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
					return self::_error_arr("Неверный HEX в ".$theme.".",$t);
				}
			}
		}

		$db->query("UPDATE company_sites SET theme_palette = ?s WHERE id = ?i", json_encode($palette), $site_id);
		return ["status"=>"ok","err"=>""];
	}

	public static function save_site_pages($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$is_owner = $db->getOne("SELECT 1 FROM company_sites cs
			JOIN user_companys uc ON cs.company_id = uc.company_id
			WHERE cs.id = ?i AND uc.main_company_id = 0 AND uc.user_id = ?i", $site_id, $_SESSION['user_id']);
		if (!$is_owner) return self::_error_arr("Нет прав на редактирование сайта");

		$allowed_tags = '<p><h2><h3><strong><em><ul><ol><li><a><br>';
		$incoming_ids = [];
		$headers_in = (array)($request->headers ?? []);

		foreach ($headers_in as $h) {
			$id = (int)($h['id'] ?? 0);
			$name = trim($h['name'] ?? '');
			$uri  = trim($h['uri'] ?? '');
			$value = strip_tags($h['value'] ?? '', $allowed_tags);
			$enabled = (int)($h['enabled'] ?? 1) ? 1 : 0;

			if ($name === '' || $uri === '') continue;

			$base_uri = $uri;
			$suffix = 1;
			while ($db->getOne("SELECT 1 FROM company_sites_header WHERE site_id = ?i AND uri = ?s AND id != ?i", $site_id, $uri, $id)) {
				$uri = $base_uri . '-' . $suffix++;
			}

			if ($id > 0) {
				$db->query("UPDATE company_sites_header SET name=?s, uri=?s, value=?s, enabled=?i WHERE id=?i AND site_id=?i",
					$name, $uri, $value, $enabled, $id, $site_id);
				$incoming_ids[] = $id;
			} else {
				$db->query("INSERT INTO company_sites_header (site_id, name, uri, value, enabled) VALUES (?i, ?s, ?s, ?s, ?i)",
					$site_id, $name, $uri, $value, $enabled);
				$incoming_ids[] = (int)$db->insertId();
			}
		}

		if (!empty($incoming_ids)) {
			$db->query("DELETE FROM company_sites_header WHERE site_id = ?i AND id NOT IN (?b)", $site_id, $incoming_ids);
		} else {
			$db->query("DELETE FROM company_sites_header WHERE site_id = ?i", $site_id);
		}

		$headers = $db->getAll("SELECT id, name, uri, value, enabled FROM company_sites_header WHERE site_id = ?i", $site_id);
		return ["status"=>"ok","err"=>"","headers"=>$headers];
	}

	public static function get_pwa($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$raw = $db->getOne("SELECT pwa FROM company_sites WHERE id = ?i", $site_id);
		$pwa = $raw ? json_decode($raw, true) : [];
		return ["status"=>"ok","pwa"=>$pwa];
	}

	public static function save_pwa($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$in = (array)($request->pwa ?? []);
		$hex = '/^#[0-9a-fA-F]{6}$/';

		$clean = [
			'appName' => substr((string)($in['appName'] ?? ''), 0, 255),
			'shortName' => substr((string)($in['shortName'] ?? ''), 0, 255),
			'themeColor' => preg_match($hex, $in['themeColor'] ?? '') ? $in['themeColor'] : '#f7a600',
			'backgroundColor' => preg_match($hex, $in['backgroundColor'] ?? '') ? $in['backgroundColor'] : '#0a0a0a',
		];

		$db->query("UPDATE company_sites SET pwa = ?s WHERE id = ?i", json_encode($clean), $site_id);
		return ["status"=>"ok","err"=>""];
	}

	public static function change_company_bonus($request){
		$db = DB::getInstance();
		if(empty($request->company_id) || (int)$request->company_id<1) return self::_error_arr("Не указан клиент");
		if(!isset($request->bonus)) return self::_error_arr("Не указан бонус");
		$res = $db->query('update company_balance set cashback = ?s where company_id = ?i and main_company_id=?i', $request->bonus, $request->company_id, $_SESSION['main_company']);
		if($res){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return array("status"=>"err","err"=>"");
		}
	}
}



?>
