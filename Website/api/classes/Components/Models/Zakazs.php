<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Zakaz;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\ZakazJob;
use Sort1API\Components\Basket;
use Sort1API\Components\DeliveryAddress;
use Sort1API\Components\Models\ZakazDetails;
use Sort1API\Components\Models\ZakazJobs;
use Sort1API\Components\Models\Payments;
use Sort1API\Components\Models\OFDs;
use Sort1API\Components\LogisticOrderDetail;
use Sort1API\Components\LogisticOrder;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Company;
use Sort1API\Components\Functions;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Zakazs extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_zakaz($request) {
			$db = DB::getInstance();
			if (isset($request->zakaz_id)) $zakaz_id=(int)$request->zakaz_id;
      	    if (isset($zakaz_id) && $zakaz_id>0) {
					$zakaz=new Zakaz($zakaz_id);
					$zakaz_is_new=0;
            }
            else {
				$zakaz=new Zakaz();
				$zakaz_is_new=1;
			}
			$companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
			if (isset($request->company_id) && $companys && !in_array($request->company_id,$companys) && (string)$request->company_id!="-1" && $request->company_id!=$_SESSION['main_company']){
				return self::_error_arr("Нельзя добавлять заказ к чужой компании");
			}
			if((string)$request->company_id=="0" || !isset($request->company_id) || empty($request->company_id)) {
				if((int)$zakaz->company_id==0)
					return self::_error_arr("Вы не выбрали клиента");
				else 
					$request->company_id=(int)$zakaz->company_id;
			}
			else {
				// проверим меняется ли клиент
				if((int)$request->company_id!=(int)$zakaz->company_id && $zakaz_is_new==0){
					$old_company=new Company((int)$zakaz->company_id);
					switch($zakaz->status){
						case 1;
							$company_balance=new CompanyBalance((int)$request->company_id);
							break;
						case 2:
							$old_company_balance=new CompanyBalance($old_company->id);
							$company_balance=new CompanyBalance((int)$request->company_id);
							$old_company_balance->rezerv-=(float)$zakaz->zakaz_sum;
							$company_balance->rezerv+=(float)$zakaz->zakaz_sum;
							$old_company_balance->save();
							$company_balance->save();
							break;
						case 3:
							$old_company_balance=new CompanyBalance($old_company->id);
							$company_balance=new CompanyBalance((int)$request->company_id);
							$zakaz_payments=$db->getOne("select sum(summ) as sum from payment where zakaz_id=?i and deleted=0 and (payment_direction=1 or payment_direction=2) group by zakaz_id",$zakaz->id);
							if($zakaz_payments>0){
								if((int)$old_company->id!=$_SESSION['main_company']){
									$old_company_balance->balance-=$zakaz_payments;
								}
								if((int)$request->company_id!=$_SESSION['main_company']){
									$company_balance->balance+=$zakaz_payments;
								}
							}
							if((int)$old_company->id!=$_SESSION['main_company']){
								$old_company_balance->rezerv-=(float)$zakaz->zakaz_sum;
							}
							if((int)$request->company_id!=$_SESSION['main_company']){
								$company_balance->rezerv+=(float)$zakaz->zakaz_sum;
							}
							$old_company_balance->save();
							$company_balance->save();
							$db->query("update payment set company_id=?i where zakaz_id=?i",(int)$request->company_id,$zakaz->id);
							break;
						case 10:
						case 12:
						case 20:
						case 30:
						case 37:
						case 40:
							$old_company_balance=new CompanyBalance($old_company->id);
							$company_balance=new CompanyBalance((int)$request->company_id);
							$zakaz_payments=(float)$db->getOne("select sum(summ) as sum from payment where zakaz_id=?i and deleted=0 and (payment_direction=1 or payment_direction=2) group by zakaz_id",$zakaz->id);
							if($zakaz_payments>0){
								if((int)$old_company->id!=$_SESSION['main_company']){
									$old_company_balance->balance-=$zakaz_payments;
								}
								if((int)$request->company_id!=$_SESSION['main_company']){
									$company_balance->balance+=$zakaz_payments;
								}
							}
							if((int)$old_company->id!=$_SESSION['main_company']){
								$old_company_balance->rezerv-=(float)$zakaz->zakaz_sum;
							}
							if((int)$request->company_id!=$_SESSION['main_company']){
								$company_balance->rezerv+=(float)$zakaz->zakaz_sum;
							}
							$old_company_balance->save();
							$company_balance->save();
							$db->query("update payment set company_id=?i where zakaz_id=?i",(int)$request->company_id,$zakaz->id);
							break;
						case 70:
							$old_company_balance=new CompanyBalance($old_company->id);
							$company_balance=new CompanyBalance((int)$request->company_id);
							$zakaz_payments=(float)$db->getOne("select sum(summ) as sum from payment where zakaz_id=?i and deleted=0 and (payment_direction=1 or payment_direction=2) group by zakaz_id",$zakaz->id);
							$document_sum=(float)$db->getOne("select sum(count*price) from document_details where document_id in (select id from document where zakaz_id=?i and deleted=0) and deleted=0",$zakaz->id);
							$db->query("update document set company_id=?i where zakaz_id=?i and deleted=0",(int)$request->company_id,$zakaz_id);
							$zakaz_payments=$zakaz_payments-$document_sum;
							if($zakaz_payments>0){
								if((int)$old_company->id!=$_SESSION['main_company']){
									$old_company_balance->balance-=$zakaz_payments;
								}
								if((int)$request->company_id!=$_SESSION['main_company']){
									$company_balance->balance+=$zakaz_payments;
								}
							}
							$old_company_balance->save();
							$company_balance->save();
							$db->query("update payment set company_id=?i where zakaz_id=?i",(int)$request->company_id,$zakaz->id);
							$db->query("update document set company_id=?i where zakaz_id=?i",(int)$request->company_id,$zakaz->id);
							break;
						default: return self::_error_arr("Нельзя изменить клиента в заказе");
					}
				}
				else {
					if($_SESSION['roles']==20){
						switch($zakaz->status){
							case 1;
								$company_balance=new CompanyBalance((int)$request->company_id);
								$company_balance->balance=0;	
								$company_balance->rezerv=0;	
								$company_balance->save();
							break;
							default: return self::_error_arr("Нельзя изменить клиента в заказе");
						}
					}
				}
			}
      	    
            if (isset($request->company_id) && (int)$request->company_id>0 && $_SESSION['roles']<10) {
                if (($companys && in_array($request->company_id,$companys)) || $request->company_id==$_SESSION['main_company'])
                    $zakaz->company_id=(int)$request->company_id;
                else {
                    return self::_error_arr("Нельзя добавлять заказ к чужой компании");
                }
            }
            else {
				if($_SESSION['roles']==10 || $_SESSION['roles']==20) $zakaz->company_id=$_SESSION['company_id'];
				else {
					if((string)$request->company_id=="-1")
						$zakaz->company_id=$_SESSION['main_company'];
					//else {
					//	return self::_error_arr("Вы не выбрали клиента");
					//} 
				}
			}
			if(
				(int)$db->getOne("select zakaz_marketing_channel from company where id=?i",$_SESSION['main_company'])==1 && 
				(empty($request->marketing_channel_name) || (int)$request->marketing_channel_id<=0) && 
				$zakaz_is_new
				&& $_SESSION['roles']<10){
					return self::_error_arr("Обязательно указание маркетингового канала продаж");
			}
			if (isset($request->marketing_channel_name)) $zakaz->marketing_channel_name=$request->marketing_channel_name;
			if (isset($request->marketing_channel_id)) $zakaz->marketing_channel_id=$request->marketing_channel_id;
			if (isset($request->zakaz_cashback_discount)) $zakaz->zakaz_cashback_discount=$request->zakaz_cashback_discount;
			if($_SESSION['roles']==10) { $zakaz->marketing_channel_name="Сайт"; $zakaz->marketing_channel_id=44; }
            if (isset($request->pozition_count)) $zakaz->pozition_count=(int)$request->pozition_count;
            if (isset($request->status)) {
                		if($zakaz->status<(int)$request->status){
							$zakaz_status_old=$zakaz->status;
							//$zakaz->status=(int)$request->status;
							switch((int)$request->status){
								case 2: 
									if(!isset($_SESSION['zakaz_commit']) || $_SESSION['zakaz_commit']==0){
										$balance_check=self::check_balance_for_close($zakaz);
										if($balance_check['status']=="err"){ // && (int)$zakaz->delivery_type==2){ //поставим проверку при всех случаях
											if(!isset($request->force) || (int)$request->force==0) {
												$balance_check['err_code']=1002;
												$company_name=$db->getOne("select name from company where id=?i",$zakaz->company_id);
												return array(
													"status"=>"err",
													"data"=>$balance_check,
													"error"=>"Невозможно подтвердить заказ! Не хватает денег на счете клиента<br>
													баланс клиента: ".$balance_check['company_balance']."<br>
													стоимость заказа: ".$balance_check['zakaz_sum']."<br>
													Вы можете: <ul>
													<li><a onclick=\"$('button[data-bb-handler=ok]').click(); select_payment_type_from_zakaz(".$zakaz->company_id.",".$zakaz->id.",'".$zakaz->zakaz_sum."');\">оплатить заказ полностью</a> ".$zakaz->zakaz_sum." руб.</li>
													<li><a onclick=\"$('button[data-bb-handler=ok]').click(); select_payment_type_from_zakaz(".$zakaz->company_id.",".$zakaz->id.",'".((float)$zakaz->zakaz_sum-(float)$balance_check['company_balance'])."');\">оплатить заказ с учетом баланса</a> ".((float)$zakaz->zakaz_sum-(float)$balance_check['company_balance'])." руб.</li> 
													<li><a onclick=\"$('button[data-bb-handler=ok]').click(); add_new_dogovor_in_zakaz(".$zakaz->company_id.",'".str_replace(array('"','"'),"",$company_name)."',".$zakaz->id.");\">добавить кредитный лимит</a></li>
													<li><a onclick=\"$('button[data-bb-handler=ok]').click(); confirm_zakaz(".$zakaz->id.",1);\">Все равно подтвердить заказ</a></li>
													</ul>");
											}
										}
									}
									self::commit_zakaz($request);//почему-то дальше по цепочки не меняется статус на 40
									break;
								case 3:
									if((int)$zakaz_status_old<=2){
										// self::commit_zakaz($request);
										return self::_error_arr("Статус оплачен выставляется автоматически после оплаты заказа во вкладке оплаты");
									}
									break;
								case 37: break; // сделано в классе Zakaz
								case 40: break; // сделано в классе Zakaz
								case 70: 
									if(isset($request->force) && (int)$request->force==1){
										if(!empty($request->issue_details) && count((array)$request->issue_details)>0){
											$zakaz_close_status=self::close_zakaz($request,$db,1,$request->issue_details);
										}
										else
											$zakaz_close_status=self::close_zakaz($request,$db,1);
										//$zakaz_close_status=self::close_zakaz($request,$db,1);
										return $zakaz_close_status;
									}
									else {
										if(!empty($request->issue_details) && count((array)$request->issue_details)>0){
											$zakaz_close_status=self::close_zakaz($request,$db,0,$request->issue_details);
										}
										else
											$zakaz_close_status=self::close_zakaz($request,$db,0);
									}
									if($zakaz_close_status['status']=="err"){
										return $zakaz_close_status;
									}
									//$zakaz->status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
									break;
								case 100: if($zakaz_status_old==70) return array("status"=>"err","error"=>"Нельзя отменить выполненный заказ");
								case 102; 
									if($zakaz_status_old==70) return array("status"=>"err","error"=>"Нельзя отменить выполненный заказ");	
									self::cancel_zakaz($request);
									break;
								case 200: self::return_zakaz($request);
									break;
								default: return array("status"=>"err","error"=>"Не все статусы можно выставлять вручную");
							}
                		}
      	    }
      	    //if (isset($request->oplachen)) $zakaz->oplachen=(int)$request->oplachen;
			if (isset($request->delivery_type)) $zakaz->delivery_type=(int)$request->delivery_type;
			if($zakaz->delivery_type==1){ //самовывоз
				if (isset($request->delivery_type_id) && (int)$request->delivery_type_id>0) {// если указан склад самомвывоза то пишем его адрес
					$delivery_address=$db->getOne("select address from sklad where id=?i and company_id=?i",(int)$request->delivery_type_id,$_SESSION['main_company']);
					if($delivery_address){
						$zakaz->delivery_address=$delivery_address;
						$zakaz->delivery_type_id=(int)$request->delivery_type_id;
					}
					else {
						return array("status"=>"err","err"=>"Не заполнен адрес склада, пожалуйста укажите адрес вашего склада");
						$delivery_address=$db->getRow("select id,address from sklad where company_id=?i and punkt_vydachi=1 and deleted=0 limit 1",$_SESSION['main_company']);
						if($delivery_address){
							$zakaz->delivery_address=$delivery_address['address'];
							$zakaz->delivery_type_id=(int)$delivery_address['id'];
						}
					}
				}
				else {
					if (isset($request->delivery_address)) $zakaz->delivery_address=$request->delivery_address;
					$zakaz->delivery_type_id=(int)$_SESSION['my_sklad_id'];
					//else return array("status"=>"err","error"=>"Не указан склад самомвывоза");
				}
			}
			else { //доставка
				if (isset($request->delivery_address)) {
					$zakaz->delivery_address=$request->delivery_address;
					if(isset($request->is_new_address) && (int)$request->is_new_address==1){
						$new_delivery_address=new DeliveryAddress();
						$new_delivery_address->company_id=$zakaz->company_id;
						$new_delivery_address->delivery_address=$request->delivery_address;
						$new_delivery_address->save();
						$zakaz->delivery_type_id=$new_delivery_address->id;
					}
					else {
						$zakaz->delivery_type_id=(int)$request->delivery_type_id;
					}
				}
			}
      	    if (isset($request->comment)) $zakaz->comment=$request->comment;

      	    if(isset($request->details)) {
            		$zakaz->pozition_count=count((array)$request->details);
            		$zakaz_sum=0;
            		foreach($request->details as $det_key=>$det_val){
            		    $zakaz_sum+=$det_val['sale_price']*$det_val['count'];
            		}
            		$zakaz->zakaz_sum=$zakaz_sum;
      	    }
			if(isset($request->car_id)){
				$zakaz->car_id = (int)$request->car_id;
			}
			else {
				$zakaz->car_id = 0;
			}
			/*else { //при каждом изменении статуса сбрасывается автомобиль на первый, поэтому убрал
				$cars=$db->getAll("select id from company_cars where company_id=?i and main_company_id=?i and deleted=0",$zakaz->company_id,$_SESSION['main_company']);
				if($cars && count($cars)>0){
					$zakaz->car_id=$cars[0]['id'];
				}
			}*/
      	    //if (isset($request->delivery_type_id)) {
			//	if(isset($request->is_new_address) && (int)$request->is_new_address==1){
					// в этом случае у нас деливери тип id уже присвоен
			//	}
			//	else {
			//		$zakaz->delivery_type_id=(int)$request->delivery_type_id; //с какого склада забирать если type=1(самовывоз), если type=2(доставка) на какой адрес доставлять
			//	}
			//}
            if (isset($request->company_dogovor_id)) { 
				$check_dogovor=$db->getOne("select id from dogovor where company_id=?i and id=?i",(int)$request->company_id,(int)$request->company_dogovor_id);
				if((int)$check_dogovor==(int)$request->company_dogovor_id) $zakaz->dogovor_id=(int)$request->company_dogovor_id;
			}
      	    if (isset($request->payment_type)) $zakaz->payment_type=(int)$request->payment_type;
            if (isset($request->fullfilment_id)) $zakaz->fullfilment_id=(int)$request->fullfilment_id;
            else { 

            }
			//вычислим статус заказа по минимальному статусу деталей и работ, с учетом не null
			$zakaz_detail_min_status=$db->getOne("select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0",$zakaz->id);
			$zakaz_job_min_status=$db->getOne("select min(status) from zakaz_jobs where zakaz_id=?i",$zakaz->id);
			if($zakaz_detail_min_status!==null){
				if($zakaz_job_min_status!==null){
					if((int)$zakaz_detail_min_status<(int)$zakaz_job_min_status) {
						$zakaz->status=(int)$zakaz_detail_min_status;
					}
					else {
						$zakaz->status=(int)$zakaz_job_min_status;
					}
				}
				else {
					$zakaz->status=(int)$zakaz_detail_min_status;
				}
			}
			else {
				if($zakaz_job_min_status!==null){
					$zakaz->status=(int)$zakaz_job_min_status;
				}
			}
			//$zakaz->status=(int)$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);

			if((int)$zakaz->status==0){
				$zakaz->status=1;
			}

			if(isset($request->user_id) && (int)$request->user_id>0 && (int)$request->user_id!=(int)$zakaz->user_id && (int)$zakaz->user_id>0){
				if($_SESSION['roles']<2){
					$is_our_user=$db->getOne("select user_id from user_companys where main_company_id=0 and company_id=?i and user_id=?i and deleted=0",$_SESSION['main_company'],(int)$request->user_id);
					if($is_our_user) $zakaz->user_id=(int)$request->user_id;
					//else return array("status"=>"err","err"=>"Не ваш пользователь sql: select user_id from user_companys where main_company_id=0 and company_id=".$_SESSION['main_company']." and user_id=".(int)$request->user_id." and deleted=0");
				}
				else return array("status"=>"err","err"=>"Не хватает прав для изменения менеджера заказа");
			}
			//else return array("status"=>"err","err"=>"Не указан пользователь");
			// echo print_r($zakaz,true)."\n";
      	    $err=$zakaz->save();
			// echo print_r($zakaz,true)."\n";
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1:
          			/* if($zakaz->reserved==0 && $zakaz->zakaz_sum>0){
          			    $sql="update company_balance set rezerv=rezerv+?s where company_id=?i and main_company_id=?i";
          			    $rezerved=$db->query($sql,$zakaz->zakaz_sum,$zakaz->company_id,$zakaz->main_company_id);
          			    if($rezerved) {
          				$zakaz->rezerved=1;
          				if(!$zakaz->save()) {
          				    // Откатить баланс компании ?????
          				}
          			    }
          			} */
          			$basket=new Basket();
					$details_ok=1;
					// print_r($basket);
					// print_r($request->details);
					if($zakaz_is_new && count((array)$request->details)==0) {
						if(!isset($request->from_service)){ 
							$status="err"; $msg="Нет деталей для добавления в заказ"; 
						}
						else {
							if(isset($request->from_service_id) && (int)$request->from_service_id>0){
								$service_sklad=$db->getOne("select sklad_id from services where id=?i",$request->from_service_id);
								if((int)$request->delivery_type_id==(int)$service_sklad){
									$status="ok"; $msg="";
								}
								else {
									$status="err"; $msg="Неправильно выбран склад выдачи сервиса, выберите пожалуйста склад выдачи привязанный к этому сервису";
								}
							}
							else {
								$status="ok"; $msg="";
							}
						}
					}
					else {
						// print_r($request->details);
						foreach($request->details as $det_key=>$det_val){
							if($basket->id==$det_val['basket_id']) {   //Проверка является ли корзина с которой перекидывают корзиной самого пользователя,
								$det_val['zakaz_id']=$zakaz->id;
								$zd_status=ZakazDetails::save_zakaz_detail(json_decode(json_encode($det_val)));
								if($zd_status['status']!="ok") {
									$details_ok=0;
									$det_ok_status[$det_key]=$zd_status;
								}
							}
							else {
								$status="err"; $msg="Не ваша корзина";
							}  
						}
						if ($details_ok){
							$status="ok"; $msg="";
						}
						else {
							$status="err"; $msg="Не удалось перекинуть некоторые детали из корзины в заказ";
							return array("status"=>"err","err"=>$msg,"det_status"=>$det_ok_status);
						}
					}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
			}
			if($status=="ok" && isset($request->fast_sale) && (int)$request->fast_sale==1){
				$payment_arr=array(
					"company_id" => $request->company_id,
					"zakaz_id" => $zakaz->id,
					"summ" => $zakaz->zakaz_sum,
					"payment_target" =>"Оплата заказа №".$zakaz->id.". Быстрая продажа",
					"payment_direction" =>1,
					"payment_type" => $zakaz->payment_type
				);
				if(isset($request->dont_fiscalize) && $request->dont_fiscalize==true){
					$payment_arr['dont_fiscalize']=true;
				}
				$numDevice=0;
				$PayByProcessing=false;
				if($zakaz->payment_type==2){
					$ofd_kassas=OFDs::get_active_kassas((object)[]);
					$kassas=$ofd_kassas['kassas'];				
					$debug['kassas']=$ofd_kassas['kassas'];
					//return array("status"=>$status,"msg"=>$msg, "debug"=>$debug,"zakaz_id"=>$zakaz->id);
					if(count((array)$kassas)>0){ 
						$klen=count((array)$kassas);
						
						for($i=0; $i<$klen; $i++){
						if($kassas[$i]['sklad_id']==$_SESSION['my_sklad_id'] && !empty($kassas[$i]['kassa_config']['NumDeviceByProcessing']) && !empty($kassas[$i]['kassa_config']['PayByProcessing'])){
							$numDevice=$kassas[$i]['kassa_config']['NumDeviceByProcessing'];
							$PayByProcessing=$kassas[$i]['kassa_config']['PayByProcessing'];
							break;
						}
						}
					}
				}
				if($numDevice==0 || $PayByProcessing==false){
					$payment_res=Payments::save_payment((object)$payment_arr);
					if($payment_res['status']=="ok"){
						//$zakaz->status=70;
						//$vydan=$zakaz->save();
						$close_zakaz_array=array(
							"zakaz_id"=>$zakaz->id,	
						);
						$zakaz_close_status=self::close_zakaz((object)$close_zakaz_array,$db,1);
						//return $zakaz_close_status;
						if($zakaz_close_status['status']=="ok") 
							return array("status"=>"ok","msg"=>"","zakaz_id"=>$zakaz->id,"payment"=>$payment_res);
						else 
							return array("status"=>"err","err"=>"Не удалось выдать заказ","payment_res"=>$payment_res,"zakaz_close_status"=>$zakaz_close_status);
					}
					else 
						return array("status"=>"err","err"=>"Не удалось провести оплату заказа","payment_res"=>$payment_res);
				}
			}  
            if ($status=="err")
		           return self::_error_arr($msg);
            else
		          return array("status"=>$status,"msg"=>$msg, "debug"=>$debug,"zakaz_id"=>$zakaz->id);
        }


	public static function get_zakazes($request) {
		$db = DB::getInstance();
		$filter="";
	    if($_SESSION['roles']<10){
		    $sql="select z.id,z.create_date,z.update_date,z.pozition_count,z.zakaz_sum,z.status,z.oplachen,z.user_id,u.lastname as user_lastname,u.name as user_name,u.middlename as user_middlename,
					   u.roles as user_roles,z.comment,z.payment_type,z.delivery_type,z.delivery_address,z.delivery_type_id,if(z.delivery_type=1,s.name,fs.name) as delivery_type_name,z.fullfilment_id,
					   z.company_id,c.name as company_name,c.mphone as company_phone,c.address as company_address,cb.balance as company_balance,cb.rezerv as company_rezerv,cb.cashback as company_cashback,
						zd.id as rejected_details,z.discount_price_type_id,z.client_notified
		            from zakaz z
				left join company c on(z.company_id=c.id)
				left join company_balance cb on (cb.company_id=z.company_id and cb.main_company_id=z.main_company_id)
				LEFT JOIN zakaz_details zd ON (zd.id=(SELECT id FROM zakaz_details WHERE zakaz_id=z.id AND status=101 and reorder_detail_id=0 LIMIT 1))
				left join sklad s on (s.id=z.delivery_type_id)
				left join sklad fs on (fs.id=z.fullfilment_id)
				left join users u on (u.id=z.user_id)
				where z.main_company_id=?i";
			if(!isset($request->show_archive) || $request->show_archive!="on") $sql.=" and z.status<>102 && z.status<>100 and z.deleted=0";
		    if(isset($request->company_id)) $sql.=" and z.company_id=".(int)$request->company_id;
			if(isset($request->sklad_id)) $sql.=" and z.delivery_type=1 and z.delivery_type_id=".(int)$request->sklad_id;
	    }
	    else{
		    $sql="SELECT IFNULL((SELECT 1
				 FROM acquiring_config
				 WHERE company_id = z.main_company_id AND active = 1
				 LIMIT 1),
				0
			) AS acquiring_config_exists,
			z.id, z.create_date, z.update_date, z.pozition_count, z.zakaz_sum, z.status,
			z.oplachen, z.comment, z.payment_type, z.delivery_type, z.delivery_address,
			z.delivery_type_id, c.name AS company_name, cm.name AS seller_name,
			cm.mphone AS seller_phone, z.main_company_id AS seller_company_id
			FROM zakaz z
			LEFT JOIN company c ON (z.company_id = c.id)
			LEFT JOIN company cm ON (z.main_company_id = cm.id)
			WHERE z.company_id = ?i AND z.deleted = 0 ?p order by z.create_date desc;";
		}
		if(!empty($request->search_zakaz_date_from)) {
			$date_from=date("Y-m-d",strtotime($request->search_zakaz_date_from));
			$filter.=$db->parse(" and z.create_date>=?s",$date_from);
		}
		else {
			$date_from=date("Y-m-d",strtotime("10 days ago"));
			$filter.=$db->parse(" and z.create_date>=?s",$date_from);
		}
		if(!empty($request->search_zakaz_date_to)) {
			$date_to=date("Y-m-d",strtotime($request->search_zakaz_date_to));
			$filter.=$db->parse(" and z.create_date<=?s",$date_to." 23:59:59");
		}
		else {
			$date_to=date("Y-m-d");
			$filter.=$db->parse(" and z.create_date<=?s",$date_to." 23:59:59");
		}
		if(!empty($request->search_zakaz_client_name)){
			if(strlen(trim($request->search_zakaz_client_name))==17){
				$cars=$db->getCol("select id from company_cars where UPPER(vin)=?s",strtoupper(trim($request->search_zakaz_client_name)));
				$filter.=$db->parse(" and z.car_id in (?b)",$cars);
			}
			else {
				if((int)$request->search_zakaz_client_name>999999999){
					//echo (int)$request->search_zakaz_client_name;
					$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where (main_company_id=?i or company_id=?i) and btype<=4) and (name like ?s or mphone like ?s or inn=?i)";
					$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_zakaz_client_name.'%','%'.$request->search_zakaz_client_name.'%', (int)$request->search_zakaz_client_name);
				}
				else {
					if(strlen($request->search_zakaz_client_name)==17){
						$res_cl=$db->getAll("select company_id as id from company_cars where vin=?s and main_company_id=?i",$request->search_zakaz_client_name,$_SESSION['main_company']);
					}
					else {
						$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where (main_company_id=?i or company_id=?i) and btype<=4) and (name like ?s or mphone like ?s)";
						$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_zakaz_client_name.'%','%'.$request->search_zakaz_client_name.'%');
					}
				}
				if($res_cl){
					$search_companys=array_column($res_cl,"id");
					$filter.=$db->parse(" and z.company_id in (?a)",$search_companys);
				}
				else {
					// поисковая строка не пустая а компаний нет
					return self::_error_arr("Ничего не найдено");
				}
				$ret['search_zakaz_client_name']=$request->search_zakaz_client_name;
			}
		}
      	
		if($_SESSION['roles']<10){
			$sql.="?p order by z.create_date desc";
			$res=$db->getAll($sql,$_SESSION['main_company'],$filter);
		}
		else {
			$res=$db->getAll($sql,$_SESSION['company_id'],$filter);
		}
		if(!empty($request->search_zakaz_article)){
			$res_art=$db->getCol("select distinct(zakaz_id) from zakaz_details where zakaz_id in (?a) and replace(replace(replace(replace(article,'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),"%".Functions::convert_article(trim($request->search_zakaz_article))."%");
			foreach($res as $zakaz_key => $search_zakaz){
				if(in_array($search_zakaz["id"],$res_art)){
					$ret_res[]=$res[$zakaz_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_zakaz_article']=$request->search_zakaz_article;
		}
		if(!isset($ret_res)) $ret_res=$res;
		if(!empty($request->search_zakaz_article)){
			
		}
		$payments_plus=$db->getInd("zakaz_id","select zakaz_id,sum(summ) as sum from payment where zakaz_id in (?a) and deleted=0 and (payment_direction=1 or payment_direction=2) group by zakaz_id",array_column($ret_res,"id"));
		$payments_minus=$db->getInd("zakaz_id","select zakaz_id,sum(summ) as sum from payment where zakaz_id in (?a) and deleted=0 and (payment_direction>=3 and payment_direction<=5) group by zakaz_id",array_column($ret_res,"id"));
		foreach($ret_res as $ret_res_key=>$ret_res_val){
			if(isset($payments_plus[$ret_res_val['id']])){
				$ret_res[$ret_res_key]['pay_sum']=(float)$payments_plus[$ret_res_val['id']]['sum']-(float)$payments_minus[$ret_res_val['id']]['sum'];
			}
		}
	    if (is_array($res) && count((array)$res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakazs']=$ret_res;
			//$ret['payments']=$payments;
			$ret['msg']="";
			$ret['search_zakaz_date_to']=$date_to;
			$ret['search_zakaz_date_from']=$date_from;
			//$ret['filter']=$filter;
	    }
      	else {
        	$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakazs']=[];
			//$ret['payments']=[];
			$ret['msg']="";
			$ret['search_zakaz_date_to']=$date_to;
			$ret['search_zakaz_date_from']=$date_from;
			//$ret['filter']=$filter;
			//$ret['res_cl']=$res_cl;
      	}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_zakaz($request) {
	    $db = DB::getInstance();
	    if($_SESSION['roles']<10){
			$sql="select z.id,z.create_date,z.update_date,z.user_id,z.pozition_count,z.zakaz_sum,z.status,z.oplachen,z.comment,z.payment_type,z.car_id,
			z.delivery_type,z.delivery_address,z.delivery_type_id,z.company_id,c.name as company_name,z.fullfilment_id,z.dogovor_id,z.marketing_channel_name,z.marketing_channel_id,z.zakaz_cashback_discount
		    from zakaz z left join company c on(z.company_id=c.id) where z.id=?i and z.main_company_id in (select company_id from user_companys where user_id=".(int)$_SESSION['user_id']." and main_company_id=0)";
	    }
	    else {
			$sql="select id,create_date,update_date,pozition_count,zakaz_sum,status,oplachen,comment,payment_type,delivery_type,
			delivery_address,delivery_type_id,fullfilment_id,zakaz_cashback_discount from zakaz where id=?i and company_id=".(int)$_SESSION['company_id']." and main_company_id=".(int)$_SESSION['main_company']." and deleted=0";
	    }
	    if (isset($request->zakaz_id) && (int)$request->zakaz_id>0){
    		$zakaz_id=(int)$request->zakaz_id;
    		$res=$db->getRow($sql,$zakaz_id);
			$ret['sql']=$db->parse($sql,$zakaz_id);
	    }
	    else {
		      return self::_error_arr("не указан id Заказа");
	    }
	    if ($res['id']>0){
			$deliv_types=$db->getAll("select * from delivery_types");
			foreach($deliv_types as $deliv_key=>$deliv_val){
				$delivery_types[$deliv_val['id']]=$deliv_val['name'];
			}
			if((int)$res['company_id']==$_SESSION['main_company']) 
				$res['company_id_is_main']=1;
			if((int)$res['dogovor_id']>0)
				$res['dogovor']=$db->getRow("select * from dogovor where id=?i",(int)$res['dogovor_id']);
			else $res['dogovor']=array();
			$ret['status']="ok";
			$ret['err']="";
			$ret['zakaz']=$res;
			$ret['cars']=$db->getInd("id","select * from company_cars where id=?i",$res['car_id']);
			$ret['delivery_types']=$delivery_types;
			$ret['zakaz_payments']=$db->getAll("select * from payment where zakaz_id=?i and deleted=0",$zakaz_id);
			$ret['users']=$db->getInd("id","select id,name,lastname,middlename from users where id in (select user_id from user_companys where main_company_id=0 and company_id=?i and deleted=0)",$_SESSION['main_company']);
			$fullfilments=$db->getAll("select id,name from sklad where company_id=?i and fullfilment=1",$_SESSION['main_company']);
			if($fullfilments) $ret['fullfilments']=$fullfilments;
			else $ret['fullfilments']=array();
				$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_zakaz($request) {
		$fields="";
		$res2=false;
		$db = DB::getInstance();
		$can_do_it=1;
		
	    if (isset($request->zakaz_id)) {$zakaz_id=(int)$request->zakaz_id;}
	    if (isset($zakaz_id) && $zakaz_id>0){
			$zakaz=new Zakaz($zakaz_id);
			if($zakaz->status>=20 && $zakaz->status<100){
				$can_do_it=0;
				//return self::_error_arr("Нельзя удалить заказ уже оформленный у поставщика");
			}
			if ((int)$_SESSION['roles']==10 && $can_do_it && $zakaz->status!=1) {
				$can_do_it=0;
			}
			if ((int)$_SESSION['roles']>2 && (int)$_SESSION['roles']<10) {
				$can_do_it=0;
				//return self::_error_arr("У Вас нет прав для удаления");
			}
			if ((int)$_SESSION['roles']==1) $can_do_it=1;
			if((int)$zakaz->user_id==$_SESSION['user_id'] && (int)$zakaz->status<70) 
				$can_do_it=1;
			if(!$can_do_it){
				return self::_error_arr("У Вас нет прав для удаления");
			}
			$sql="select id from zakaz_details where zakaz_id=?i";
			$zakaz_details=$db->getCol($sql,$zakaz_id);
			$det_flag=0;
			foreach($zakaz_details as $zakaz_detail_id){
				$req=(object)(array());
				$req->zakaz_id=$zakaz_id;
				$req->id=$zakaz_detail_id;
				if($_SESSION['roles']<10)
					$zak_ret=ZakazDetails::delete_zakaz_detail_by_manager($req);
				else
					$zak_ret=ZakazDetails::delete_zakaz_detail_by_client($req);
				if($zak_ret['status']=="err") $det_flag=1;
			}
			if(!$det_flag)
				$res2=$db->query("update zakaz set deleted=1 where id=?i and (company_id in (select company_id from user_companys where main_company_id=?i) or company_id=0)",$zakaz_id,$_SESSION['main_company']);
			else
				return self::_error_arr("Не удалось удалить заказ");

    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить Заказ";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_zakaz_statuses($request){
	    $db = DB::getInstance();
		$ret=$db->getInd("id","select id,descr,color from user_zakaz_statuses where user_id=?i",$_SESSION['user_id']);
		if(!$ret) $ret=$db->getInd("id","select id,descr,color from user_zakaz_statuses where company_id=?i",$_SESSION['main_company']);
	    if(!$ret) $ret=$db->getInd("id","select id,descr,color from zakaz_statuses");
		foreach($ret as $key=>$val){
			if($ret[$key]['color']===null || empty($ret[$key]['color'])) $ret[$key]['color']="#FFFFFF";
		}
	    return $ret;
	}

	public static function save_user_zakaz_statuses($request){
	    $db = DB::getInstance();
		if(count((array)$request->user_statuses)>0){
			$ret=$db->query("delete from user_zakaz_statuses where company_id=?i and user_id=?i",$_SESSION['main_company'],$_SESSION['user_id']);
			foreach((array)$request->user_statuses as $key=>$val){
				$db->query("insert into user_zakaz_statuses values(?i,?s,?s,?i,?i,?s,?s,?i)",$val['id'],$val['descr'],$val['color'],$_SESSION['user_id'],$_SESSION['main_company'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),0);
				$ins[]=$db->parse("insert into user_zakaz_statuses values(?i,?s,?s,?i,?i,?s,?s,?i)",$val['id'],$val['descr'],$val['color'],$_SESSION['user_id'],$_SESSION['main_company'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),0);
			}
		}
	    return ["status"=>"ok","msg"=>"","err"=>"","ins"=>$ins,"u_s"=>(array)$request->user_statuses];
	}

	public static function commit_zakaz($request){
	    $db = DB::getInstance();
	    if(isset($request->zakaz_id)) $zakaz_id=(int)$request->zakaz_id;
	    if(isset($zakaz_id) && $zakaz_id>0){
    		$zakaz=new Zakaz($zakaz_id);
    		//echo $request->action."\n";
    		if($request->action=="commit_zakaz"){
    			$zakaz->status=2;
    			$zakaz->save();
    		}
    		//$zakaz_id,$detail_id,$deliverer_type,$deliverer_id
    		$zakaz_details=$db->getAll("select id,zakaz_id,detail_id,deliverer_type,deliverer_id from zakaz_details where zakaz_id=?i",$zakaz_id);
        // здесь можно просто проапдейтить таблицу не залезая в foreach
    		foreach($zakaz_details as $det_key=>$det_val){
    		    $zd=new ZakazDetail($det_val['id']);
    		    if($zd->status<2){
              		$zd->status=2;
    		    	$zd->save();
            	}
				if($zd->deliverer_type==1 && ($zd->status==2 || $zd->status==3)){
					$zd->status=40;
					$zd->save();
				}
    		}
	    }
	    else {
		     return self::_error_arr("Невозможно подтвердить заказ, не указан номер заказа");
	    }
	}

	public static function close_zakaz($request,$db,$force=0,$issue_details=array()){
	    $db = DB::getInstance();
	    if(isset($request->zakaz_id)) $zakaz_id=(int)$request->zakaz_id;
	    if(isset($zakaz_id) && $zakaz_id>0){
    		$zakaz=new Zakaz($zakaz_id);
    		//echo $request->action."\n";
    		if($request->action=="close_zakaz"){
    			//$zakaz->status=9;
    			//$zakaz->save(); 
    		}
			//$zakaz_id,$detail_id,$deliverer_type,$deliverer_id
			if((int)$zakaz->delivery_type!=2){
				$checked=self::check_zakaz_details_for_close($zakaz_id,$db,$issue_details);
				if(count((array)$checked['not_exist'])>0 || count((array)$checked['not_exist_count'])>0){
					if(!$force) {
						$checked['err_code']=1001;
						return array("status"=>"err","data"=>$checked,"error"=>"Невозможно выдать заказ! Не все детали есть на складе");
					} 
					else {
						if(count((array)$issue_details)==0)
							foreach($checked['exist'] as $ex_key=>$ex_val){
								$issue_details[]=$ex_val['id'];
							}
							//file_put_contents("/var/log/sort1/zakazs.log","issue_details=".print_r($issue_details,true)."\n",FILE_APPEND);
					}
				}
			}
			$balance_check=self::check_balance_for_close($zakaz,$issue_details);
			if($balance_check['status']=="err"){
				if(!$force) {
					$balance_check['err_code']=1002;
					//return array("status"=>"err","data"=>$balance_check,"error"=>"Невозможно выдать заказ! Не хватает денег на счете клиента");
					return array("status"=>"err",
					"data"=>$balance_check,
					"error"=>"Невозможно закрыть заказ! Не хватает денег на счете клиента<br>
					баланс клиента: ".$balance_check['company_balance']."<br>
					стоимость заказа: ".$balance_check['zakaz_sum']."<br>
					Вы можете: <ul>
					<li><a onclick=\"$('button[data-bb-handler=ok]').click(); select_payment_type_from_zakaz(".$zakaz->company_id.",".$zakaz->id.",'".$balance_check['zakaz_sum']."');\">оплатить заказ полностью</a> ".$balance_check['zakaz_sum']." руб.</li>
					<li><a onclick=\"$('button[data-bb-handler=ok]').click(); select_payment_type_from_zakaz(".$zakaz->company_id.",".$zakaz->id.",'".((float)$balance_check['zakaz_sum']-(float)$balance_check['company_balance'])."');\">оплатить заказ с учетом баланса</a> ".((float)$balance_check['zakaz_sum']-(float)$balance_check['company_balance'])." руб.</li> 
					<li><a onclick=\"$('button[data-bb-handler=ok]').click(); add_new_dogovor_in_zakaz(".$zakaz->company_id.",'".str_replace(array('"','"'),"",$company_name)."',".$zakaz->id.");\">добавить кредитный лимит</a></li>
					</ul>");
				}
			}
			if(count((array)$issue_details)>0){
				$parsed=$db->parse(" and id in (?a)",$issue_details);
			}
			else $parsed="";
			$zakaz_details=$db->getAll("select id,detail_id from zakaz_details where zakaz_id=?i and reorder_detail_id=0 ?p",$zakaz_id,$parsed);
			//file_put_contents("/var/log/shop/api/close_zakaz.log","zakaz_details=".print_r($zakaz_details,true)."\n",FILE_APPEND);
			//file_put_contents("/var/log/shop/api/close_zakaz.log","checked=".print_r($checked,true)."\n",FILE_APPEND);
    		foreach($zakaz_details as $det_key=>$det_val){
				//file_put_contents("/var/log/shop/api/close_zakaz.log","in_array(".$det_val['detail_id'].",".print_r(array_column($checked['exist'],"detail_id"),true)."\n",FILE_APPEND);
				if(in_array($det_val['detail_id'],array_column($checked['exist'],"detail_id")) || (int)$zakaz->delivery_type==2){
					$zd=new ZakazDetail($det_val['id']);
					if($zd->status<70){ 
						$zd->status=70;
						$zd->save();
					}
				}
    		}
			$zakaz_jobs=$db->getAll("select id,job_id from zakaz_jobs where zakaz_id=?i and status<70",$zakaz_id);
			//file_put_contents("/var/log/shop/api/close_zakaz.log","zakaz_details=".print_r($zakaz_details,true)."\n",FILE_APPEND);
			//file_put_contents("/var/log/shop/api/close_zakaz.log","checked=".print_r($checked,true)."\n",FILE_APPEND);
    		$not_set_employees=array();
			$err_text='';$i=0;
			foreach($zakaz_jobs as $job_key=>$job_val){
				$i++;
				//file_put_contents("/var/log/shop/api/close_zakaz.log","in_array(".$det_val['detail_id'].",".print_r(array_column($checked['exist'],"detail_id"),true)."\n",FILE_APPEND);
				$zj=new ZakazJob($job_val['id']);
				$is_employee=$db->getOne("select count(id) from zakaz_job_employees where zakaz_job_id=?i",$job_val['id']);
				if(!$is_employee) {
					$not_set_employees[$job_val['id']]=$db->getOne("select name from service_jobs where id=?i and deleted=0",$job_val['job_id']);
					$err_text.="<b>".$i.". ".$not_set_employees[$job_val['id']]."</b><br>";
				}
				if($zj->status<70){ 
					$zj->status=70;
					$zj->save();
				}
    		}
			if(count($not_set_employees)>0) return array("status"=>"err","error"=>"<font color='red'>Невозможно закрыть заказ,</font> <br> У следующих работ не назначены исполнители:<br>".$err_text);
			if($zakaz->zakaz_cashback_discount>0 && $zakaz->zakaz_cashback_discount_repaid==0){
				$company_balance=new CompanyBalance($zakaz->company_id);
				$company_balance->cashback-=$zakaz->zakaz_cashback_discount;
				$company_balance->save();
				$zakaz->zakaz_cashback_discount_repaid=1;
				$zakaz->save();
			}
	    }
	    else {
		      return array("status"=>"err","error"=>"Невозможно закрыть заказ","request"=>$request);
		}
		return array("status"=>"ok","msg"=>"");
	}

	public static function return_zakaz($request){
	    $db = DB::getInstance();
	    if(isset($request->zakaz_id)) $zakaz_id=(int)$request->zakaz_id;
	    if(isset($zakaz_id) && $zakaz_id>0){
			$zakaz=new Zakaz($zakaz_id);
			$zakaz_details=$db->getAll("select id,detail_id from zakaz_details where zakaz_id=?i",$zakaz_id);
			file_put_contents("/var/log/shop/api/return_zakaz.log","zakaz_details=".print_r($zakaz_details,true)."\n",FILE_APPEND);
    		foreach($zakaz_details as $det_key=>$det_val){
				file_put_contents("/var/log/shop/api/return_zakaz.log","in_array(".$det_val['detail_id'].",".print_r(array_column($checked['exist'],"detail_id"),true)."\n",FILE_APPEND);
				$zd=new ZakazDetail($det_val['id']);
				if((int)$zd->status==70){
					$zd->status=200;
					$zd->save();
				}
    		}
    		//echo $request->action."\n";
			//$zakaz_id,$detail_id,$deliverer_type,$deliverer_id
			//вернуть заказ на склад ???? выяснить на какой склад возвращают
	    }
	    else {
		      return array("status"=>"err","error"=>"Невозможно вернуть заказ");
		}
		return array("status"=>"ok","msg"=>"");
	}

	public static function check_balance_for_close(&$zakaz,$issue_details=array()){
		$db = DB::getInstance();
		$sql="select cb.balance,z.zakaz_sum,d.credit_limit,cb.company_id,d.id as dogovor_id from company_balance cb 
		left join zakaz z on (z.id=?i)
		left join dogovor d on (z.dogovor_id<>0 and d.id=z.dogovor_id and d.deleted=0)
		where cb.company_id in (select company_id from zakaz where id=?i) and cb.main_company_id=?i";
		
		$balance=$db->getRow($sql,$zakaz->id,$zakaz->id,$_SESSION['main_company']);
		if (empty($balance['balance'])){
			$company_balance=new CompanyBalance($db->getOne("select company_id from zakaz where id=?i",$zakaz->id));
			$company_balance->Save();
			$balance=$db->getRow($sql,$zakaz->id,$zakaz->id,$_SESSION['main_company']);
		}
		if(empty($balance['credit_limit']) || ((float)$balance['credit_limit']<=0 && $balance['dogovor_id']==0)) {
			$dogovors=$db->getAll("select id,credit_limit from dogovor where company_id in (select company_id from zakaz where id=?i) and main_company=?i and deleted=0",$zakaz->id,$_SESSION['main_company']);
			if(count((array)$dogovors)==1){
				$balance['credit_limit']=$dogovors[0]['credit_limit'];
				$zakaz->dogovor_id=$dogovors[0]['id'];
				//$db->query("update zakaz set dogovor_id=?i where id=?i",$dogovors[0]['id'],$zakaz_id);
			}
		}
		if(count((array)$issue_details)>0){
			$parsed=$db->parse(" and id in (?a)",$issue_details);
		}
		else $parsed="";
		$zakaz_details_sum=round($db->getOne("select sum(price*count) from zakaz_details where zakaz_id=?i and status<70 and status<>14 and reorder_detail_id=0 ?p",$zakaz->id,$parsed),2);
		$zakaz_jobs_sum=round($db->getOne("select sum(price*count*difficult_co) from zakaz_jobs where zakaz_id=?i and status<70",$zakaz->id),2);
		$zakaz_sum=$zakaz_details_sum+$zakaz_jobs_sum;
		if((int)$balance['company_id']==$_SESSION['main_company']){
			return array("status"=>"ok","company_balance"=>2,"zakaz_sum"=>1);
		}
		//if((float)$balance['credit_limit'])<=0){
			//договор не назначен был изначально, или на нем не было кредитного лимита

		//}
		if(((float)$balance['balance']+(float)$balance['credit_limit'])>=(float)$zakaz_sum && (float)$balance['zakaz_sum']>=0){
			//echo ((float)$balance['balance']+(float)$balance['credit_limit']).">=".(float)$zakaz_sum."\n";
			return array("status"=>"ok","company_balance"=>($balance['balance']+(float)$balance['credit_limit']),"zakaz_sum"=>$zakaz_sum);
		}
		else {
			//echo ((float)$balance['balance']+(float)$balance['credit_limit']).">=".(float)$zakaz_sum."\n";
			return array("status"=>"err","company_balance"=>($balance['balance']+(float)$balance['credit_limit']),"zakaz_sum"=>$zakaz_sum);
		}
	}

	private static function check_zakaz_details_for_close($zakaz_id,$db,$issue_details=array()){
		$sklad_res=$db->getRow("select delivery_type,delivery_type_id,fullfilment_id from zakaz where id=?i",$zakaz_id);
		if($sklad_res['delivery_type']==1) $sklad_id=$sklad_res['delivery_type_id'];
		else {
			if($sklad_res['fullfilment_id']!=0) $sklad_id=$sklad_res['fullfilment_id'];
			else {

			}
		}
		if(count((array)$issue_details)>0){
			$parsed=$db->parse(" and id in (?a)",$issue_details);
		}
		else $parsed="";
		$sql="select detail_id,count,reserved_count from sklad_details where sklad_id=?i and detail_id in (select detail_id from zakaz_details where zakaz_id=?i and status<70 ?p)";
		$res_sd=$db->getInd("detail_id",$sql,$sklad_id,$zakaz_id,$parsed);
		$res_zd=$db->getAll("select id,detail_id,article,brand,name,count,brand_id,status from zakaz_details where zakaz_id=?i and status<70 and reorder_detail_id=0 ?p",$zakaz_id,$parsed);
		$exist_in_sklad=array();
		$not_exist_count_in_sklad=array();
		$not_exist_in_sklad=array();
		foreach($res_zd as $key=>$zd){
			//echo (int)$res_sd[$zd['detail_id']]['count'].">=".(int)$zd['count']."\n";
			if((int)$zd['status']==51 || (int)$zd['status']==52){
				$exist_in_sklad[]=$zd;
			}
			else {
				$zd['in_sklad_count']=(int)$res_sd[$zd['detail_id']]['count'];
				if(isset($res_sd[$zd['detail_id']])){
					// в заказе может быть несколько деталей с одним detail_id надо просуммировать
					$before_count=0;
					foreach($exist_in_sklad as $eis_key=>$eis_val){
						//file_put_contents("/var/log/sort1/zakazs.log","eis_val=".print_r($eis_val,true)."\n ",FILE_APPEND);
						if($eis_val['detail_id']==$zd['detail_id']){
							$before_count+=(int)$eis_val['count'];
							//file_put_contents("/var/log/sort1/zakazs.log","before_count=$before_count\n ",FILE_APPEND);
						}
					}
					if((int)$res_sd[$zd['detail_id']]['count']>=((int)$zd['count']+$before_count)){
						$exist_in_sklad[]=$zd;
					}
					else {
						//$zd['in_sklad_count']=$res_sd[$zd['detail_id']]['count'];
						$alt_sklad_detail=$db->getRow("select * from sklad_details where article=?s and sklad_id=?i and brand_id=?i and count>=?i limit 1",$zd['article'],$sklad_id,$zd['brand_id'],((int)$zd['count']+$before_count));
						if($alt_sklad_detail) 
							$exist_in_sklad[]=$zd;
						else 
							$not_exist_count_in_sklad[]=$zd;
					}
				}
				else {
					$not_exist_in_sklad[]=$zd;
				}
			}
		}
		//echo print_r($exist_in_sklad,true);
		//echo print_r($not_exist_in_sklad,true);
		//echo print_r($not_exist_count_in_sklad,true);
		return array("exist"=>$exist_in_sklad, "not_exist"=>$not_exist_in_sklad,"not_exist_count"=>$not_exist_count_in_sklad);
	}

	public static function cancel_zakaz($request){
	    $db = DB::getInstance();
	    if(isset($request->zakaz_id)) $zakaz_id=(int)$request->zakaz_id;
	    if(isset($zakaz_id) && $zakaz_id>0){
    		$zakaz=new Zakaz($zakaz_id);
    		//echo $request->action."\n";
    		if($request->action=="cancel_zakaz"){
    			$zakaz->status=(int)$request->status;
    			$zakaz->save();
    		}
    		//$zakaz_id,$detail_id,$deliverer_type,$deliverer_id
    		$zakaz_details=$db->getAll("select id,zakaz_id,detail_id,deliverer_type,deliverer_id from zakaz_details where zakaz_id=?i",$zakaz_id);
    		foreach($zakaz_details as $det_key=>$det_val){
    		    //$zd=new ZakazDetail(0,$det_val['zakaz_id'],$det_val['detail_id'],$det_val['deliverer_type'],$det_val['deliverer_id']);
            	$zd=new ZakazDetail($det_val['id']);
    		    $zd->status=(int)$request->status;
    		    $zd->save();
    		}
	    }
	    else {
		    return self::_error_arr("Невозможно отменить заказ");
	    }
	}

  public static function get_fullfilment_id($request){
	$db = DB::getInstance();
	$ret=array();
	$sql="select id,name,descr from sklad where deleted=0 and fullfilment=1 and company_id=?i";
	$res=$db->getAll($sql,$_SESSION['main_company']);
	if($res){
		// Есть отмеченный 1 или несколько фулфилмент складов
		if(count((array)$res)==1){
			// 1 sklad
			$ret['fullfilments'][]=$res[0];
		}
		else {
			// >1 sklad
			foreach($res as $key=>$val){
				$ret['fullfilments'][]=$val;
			}
		}
	}
	else {
		// проверим есть ли вообще склады и их количество
		$sql1="select id,name,descr from sklad where deleted=0 and company_id=?i";
		$res1=$db->getAll($sql1,$_SESSION['main_company']);
		if($res1){
			switch(count((array)$res1)){
				case 1: //склад 1 и его считаем также фулфилмент складом
					$ret['sklads'][]=$res1[0];
					break;
				default: // складов несколько и не один не отмечен как фулфилмент
					foreach($res1 as $key=>$val){
						$ret['sklads'][]=$val;
					}
			}

		}
		else {
			return self::_error_arr("У вас не заведены склады. Заведите в системе склад");
		}
	}
	$ret['status']="ok";
	$ret['msg']="";
	return $ret;
  }

  public static function get_fullfilment_address($request){
	$db = DB::getInstance();
	$ret=array();
	$sql="select id,address from sklad where deleted=0 and fullfilment=1 and company_id=?i";
	$res=$db->getAll($sql,$_SESSION['main_company']);
	if($res){
		// Есть отмеченный 1 или несколько фулфилмент складов
		if(count((array)$res)==1){
			// 1 sklad
			$ret['fullfilments'][]=$res[0];
		}
		else {
			// >1 sklad
			foreach($res as $key=>$val){
				$ret['fullfilments'][]=$val;
			}
		}
	}
	else {
		// проверим есть ли вообще склады и их количество
		$sql1="select id,name,descr from sklad where deleted=0 and company_id=?i";
		$res1=$db->getAll($sql1,$_SESSION['main_company']);
		if($res1){
			switch(count((array)$res1)){
				case 1: //склад 1 и его считаем также фулфилмент складом
					$ret['sklads'][]=$res1[0];
					break;
				default: // складов несколько и не один не отмечен как фулфилмент
					foreach($res1 as $key=>$val){
						$ret['sklads'][]=$val;
					}
			}

		}
		else {
			return self::_error_arr("У вас не заведены склады. Заведите в системе склад");
		}
	}
	$ret['status']="ok";
	$ret['msg']="";
	return $ret;
  }

  public static function get_my_fullfilment_id($request){
	$db = DB::getInstance();
	$ret=array();
	$sql="select id,name,descr from sklad where deleted=0 and fullfilment=1 and company_id=?i";
	$res=$db->getAll($sql,$_SESSION['main_company']);
	if($res){
		// Есть отмеченный 1 или несколько фулфилмент складов
		if(count((array)$res)==1){
			// 1 sklad
			$ret['fullfilments'][]=$res[0];
		}
		else {
			// >1 sklad
			foreach($res as $key=>$val){
				$ret['fullfilments'][]=$val;
			}
		}
	}
	else {
		// проверим есть ли вообще склады и их количество
		$sql1="select id,name,descr from sklad where deleted=0 and company_id=?i";
		$res1=$db->getAll($sql1,$_SESSION['main_company']);
		if($res1){
			if(count($res1) == 1) {
				// склад 1 и его считаем также фулфилмент складом
				$ret['sklads'][] = $res1[0];
			} else {
				// складов несколько и не один не отмечен как фулфилмент
				foreach($res1 as $key => $val) {
					$ret['sklads'][] = $val;
				}
			}

		}
		else {
			return self::_error_arr("У вас не заведены склады. Заведите в системе склад");
		}
	}
	$ret['status']="ok";
	$ret['msg']="";
	return $ret;
  }

  public static function get_zakaz_commit($request){
	  return array("status"=>"ok","zakaz_commit"=>$_SESSION['zakaz_commit'],"zakaz_marketing_channel"=>$_SESSION['zakaz_marketing_channel'],"self_zakaz_sale_price"=>$_SESSION['self_zakaz_sale_price'],"msg"=>"");
  }

  public static function save_zakaz_commit($request){
	$db = DB::getInstance();
	if(isset($request->zakaz_commit) && $request->zakaz_commit==true) $zakaz_commit=1;
	else $zakaz_commit=0;
	if(isset($request->zakaz_marketing_channel) && $request->zakaz_marketing_channel==true) $zakaz_marketing_channel=1;
	else $zakaz_marketing_channel=0;
	if(isset($request->self_zakaz_sale_price) && $request->self_zakaz_sale_price==true) $self_zakaz_sale_price=1;
	else $self_zakaz_sale_price=0;
	if($db->query("update company set zakaz_commit=?i,zakaz_marketing_channel=?i,self_zakaz_sale_price=?i where id=?i",$zakaz_commit,$zakaz_marketing_channel,$self_zakaz_sale_price,$_SESSION['main_company'])) {
		$_SESSION['zakaz_marketing_channel']=$zakaz_marketing_channel;
		$_SESSION['zakaz_commit']=$zakaz_commit;
		$_SESSION['self_zakaz_sale_price']=$self_zakaz_sale_price;
	}
	return array("status"=>"ok","zakaz_commit"=>$_SESSION['zakaz_commit'],"zakaz_marketing_channel"=>$_SESSION['zakaz_marketing_channel'],"self_zakaz_sale_price"=>$_SESSION['self_zakaz_sale_price'],"msg"=>"");
  }

  public static function save_zakaz_market($request) {
	$db = DB::getInstance();
	$basket = new Basket();
	$sql="SELECT bd.*, c.id AS company_id, s.id AS sklad_id
	FROM basket_details bd 
	LEFT JOIN sklad s ON (s.id = bd.deliverer_id AND bd.deliverer_type = 1)
	LEFT JOIN company c ON (c.id = s.company_id)
	WHERE bd.basket_id = ?i 
	AND bd.checked = 1 
	AND bd.create_date >= NOW() - INTERVAL 24 HOUR";

	$details = $db->getAll($sql,$basket->id);
	if(count((array)$details) > 0){
		$companyDetails = [];

		foreach ($details as $detail) {
			if($detail != null){
				$company = $detail['company_id'];
			
				if (!isset($companyDetails[$company])) {
					$companyDetails[$company] = [];
				}
				$_SESSION['main_company'] = $company;
				$basket = new Basket();
				$detail['basket_id'] = $basket->id;
				
				$companyDetails[$company][] = $detail;
			}
		}
		// print_r($companyDetails);
		foreach ($companyDetails as $index => $details) {
			// echo $index;
			$_SESSION['main_company'] = $index;
			$check_user_companys = $db->getRow("select * from user_companys where user_id=?i and main_company_id=?i and company_id=?i", $_SESSION['user_id'], $_SESSION['main_company'], $_SESSION['company_id']);
			if(empty($check_user_companys)){
				$db->query("insert ignore into user_companys SET user_id=?i,main_company_id=?i,company_id=?i",$_SESSION['user_id'],$_SESSION['main_company'], $_SESSION['company_id']);
			}
			$delivery_type_id = $details[0]['sklad_id'];
			// print_r($delivery_type_id);
			//print_r((object)array("details"=>$companyDetails[$index], "company_id"=>$request->company_id, "delivery_type"=>$request->delivery_type, "delivery_type_id"=>$request->delivery_type_id, "payment_type"=>$request->payment_type));
			$res = self::save_zakaz((object)array("details"=>$details, "company_id"=>$_SESSION['company_id'], "delivery_type"=>$request->delivery_type, "delivery_type_id"=>$delivery_type_id, "payment_type"=>$request->payment_type));
			// print_r($res);

			if($res['status'] = 'ok'){
				continue;
			}
			else{
				$_SESSION['main_company'] = 35;
				return $res;
			}
		}
		// print_r($db->getStats());

		$_SESSION['main_company'] = 35;
		return array("status"=>"ok","msg"=>"");
	}
	else{
		return array("status"=>"err","msg"=>"Корзина пустая");
	}
  }

  public static function save_zakaz_shop($request) {
	$db = DB::getInstance();
	$basket = new Basket();
	$sql="select bd.*,c.id as company_id,s.id as sklad_id from basket_details bd 
	left join sklad s on (s.id=bd.deliverer_id and bd.deliverer_type=1)
	left join company c on (c.id=s.company_id)
	where bd.basket_id=?i and bd.checked = 1";
	$details = $db->getAll($sql,$basket->id);
	if(count((array)$details) > 0){
		$res = self::save_zakaz((object)array("details"=>$details, "company_id"=>$_SESSION['company_id'], "delivery_type"=>$request->delivery_type, "delivery_type_id"=>$request->delivery_type_id, "payment_type"=>$request->payment_type, "comment"=>$request->comment));
		
		if($res['status'] = 'ok'){
			return array("status"=>"ok","msg"=>"");
		}
		else{
			return $res;
		}
	}
	else{
		return array("status"=>"err","msg"=>"Корзина пустая");
	}
  }

  public static function get_seller($request) {
	$db = DB::getInstance();
	if(isset($request->company_id)){
		$company_id = $request->company_id;
	}
	else{
		return array("status"=>"err","err"=>"Не указан заказ");
	}

	$seller = $db->getRow("select c.name as seller_name, c.mphone as seller_phone, c.email, c.inn
	from  company c where c.id = ?i",(int)$company_id); 

	if(!empty($seller)){
		return array("status"=>"ok","msg"=>"","seller"=>$seller);
	}
	else{
		return array("status"=>"ok","msg"=>"","seller"=>array());
	}
  }

  public static function add_skidka_to_zakaz($request){
	$db = DB::getInstance();
	if(empty($request->zakaz_id)) return array("status"=>"err","err"=>"Не указан номер заказа");
	$zakaz=$db->getRow("select * from zakaz where id=?i and main_company_id=?i",(int)$request->zakaz_id,$_SESSION['main_company']);	
	if($zakaz){
		$z=new Zakaz((int)$request->zakaz_id);
		if($zakaz['status']>=70) return array("status"=>"err","err"=>"В данном заказе нельзя применить скидку");
		$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and status<70",$request->zakaz_id);
		if((int)$request->discount_price_type_id>0){
			$z->discount_price_type_id=(int)$request->discount_price_type_id;
			$price_type=$db->getRow("select * from dict_price_type where id=?i and deleted=0 and main_company=?i",$request->discount_price_type_id,$_SESSION['main_company']);
			if($price_type['type']==3){
				$prt_diff_vals=$db->getAll("select * from dict_price_type_differencial_values where dict_price_type_id=?i order by min_sum",$price_type['id']);
			}
		}
		else {
			$z->discount_price_type_id=0;
			$price_type=array("type"=>0,"proc"=>0,"round_for"=>1);
		}
		$z->save();
		foreach($zakaz_details as $zakaz_detail){
			$zakaz_det=new ZakazDetail($zakaz_detail['id']);
			if($zakaz_det->first_price==0){
				$zakaz_det->first_price=$zakaz_det->price;
			}
			if((int)$request->discount_price_type_id>0){
				if($price_type['type']==1){ // && $price_type['proc']>0){
					$zakaz_det->price=ceil(round($zakaz_det->first_price - $zakaz_det->first_price/100*$price_type['proc'],2)/$price_type['round_for'])*$price_type['round_for'];
				}
				elseif($price_type['type']==3){
					$skidka=0; $skidka_exist=0;
					foreach($prt_diff_vals as $diff_val){
						if($diff_val['min_sum']<$zakaz_det->first_price){
							$skidka=$diff_val['value'];
							$skidka_exist=1;
							$round_for=$diff_val['round_for'];
						}
					}
					if(empty($round_for)) $round_for=1;
					if($skidka_exist){
						$zakaz_det->price=ceil(round($zakaz_det->first_price - $zakaz_det->first_price/100*$skidka,2)/$round_for)*$round_for;
					}
				}
				if($zakaz_det->price<$zakaz_det->dealer_price){
					$zakaz_det->price=$zakaz_det->first_price;
				}
			}
			else {
				$zakaz_det->price=$zakaz_det->first_price;
				$zakaz['discount_price_type_id']=0;
			}
			$zakaz_det->save();
		}
		return array("status"=>"ok","err"=>"","msg"=>"");
	}
	else {
		return array("status"=>"err","err"=>"Заказ не найден");
	}
  }

  public static function notify_client($request){
	$db = DB::getInstance();
	if(empty($request->zakaz_id)) return array("status"=>"err","err"=>"Не указан номер заказа");
	$zakaz=$db->getRow("select * from zakaz where id=?i and main_company_id=?i",(int)$request->zakaz_id,$_SESSION['main_company']);	
	if($zakaz){
		$z=new Zakaz((int)$request->zakaz_id);
		if($z->client_notified==1) {
			$z->client_notified=0;
			$z->status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$z->id);
		}
		else {
			$z->client_notified=1;
			if($z->status<41 && $z->status!=14){
				$z->status=41;
			}
		}
		$z->save();
		return array("status"=>"ok","err"=>"","msg"=>"");
	}
	else {
		return array("status"=>"err","err"=>"Заказ не найден");
	}
  }

  public static function get_print_zakaz_xls($request){
	$db=DB::getInstance();

	if(isset($request->zakaz_id)){
		$zakaz_id=$request->zakaz_id;
		$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
		$zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
			left join service_jobs sj on (sj.id=zj.job_id)
			where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
		$zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
	}
	if(isset($request->document_id)){
		$zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$request->document_id);
		$zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$request->document_id);
		$zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.document_id=?i and dj.deleted=0",$request->document_id);
		$zakaz_data=$db->getRow("select * from document where id=?i",$request->document_id);
		$zakaz_data['main_company_id']=$zakaz_data['main_company'];
		//$zakaz_data['id']=$zakaz_id;
		//}
	}
	if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
		return array("status"=>"err","err"=>"Выберите свой заказ");
	}
	//echo "select * from zakaz where id=$zakaz_id<br>";
	//echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
	$client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
	$client_cars=$db->getAll("select id,auto_maker_name,auto_model,vin,made_year from company_cars where company_id=?i and main_company_id=?i and deleted=0",$client_data['id'],$_SESSION['main_company']);
	if($client_data['main_org_id']>0) $client_main_org_data=$db->getRow("select * from company where id=?i",$client_data['main_org_id']);
	$mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
	$poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
	$pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id'],$zakaz_data['company_id']);
	$mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
	$sklad_data=$db->getRow("select * from sklad where id=(select delivery_type_id from zakaz where id=?i)",$zakaz_id);
	if(isset($request->zakaz_id) && $mainc_taxtype['is_nds']==1 ){
	die("При работе с НДС, распечатка документов возможна только из вкладки документы");
	}
	$ruk_arr=explode(" ",$mainc_data['ruk']);
	//echo print_r($ruk_arr,true)."<br>";
	$ruk_name=mb_substr($ruk_arr[1],0,1);
	//echo print_r($ruk_name,true)."<br>";
	$ruk_otch=mb_substr($ruk_arr[2],0,1);
	$ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";

	$data = array(
		'buyer' => array(
			'name' => $mainc_data['name'],
			'address' => $mainc_data['address'],
			'inn' => $mainc_data['inn'],
			'kpp' => $mainc_data['kpp'],
			'anotherAdress' => $mainc_data['post_address'],
			'recipient' => $client_data['name'].",".$client_data['address'],
		),
		'seller' => array(
			'name' => $client_data['name'],
			'address' => $client_data['address'],
			'inn' => $client_data['inn'],
			'kpp' => $client_data['kpp']
		),
		'goods' => array(),
	);
	
	foreach($zakaz_details as $zd_key=>$zd_val){
		array_push($data['goods'],
			array(
				'id' => $zd_val['article'],
				'brand' => $zd_val['brand'],
				'name' => $zd_val['name'],
				'count' => $zd_val['count'],
				'price' => $zd_val['price'],
				'country' => array(
					'id' => '',
					'title' => ''
				),
				'regNum' => ''
			)
		);
	}

	$arr = [
		'января',
		'февраля',
		'марта',
		'апреля',
		'мая',
		'июня',
		'июля',
		'августа',
		'сентября',
		'октября',
		'ноября',
		'декабря'
	  ];
	  
	  $month = date('n')-1;
	  $currentDate = date('d').' '.$arr[$month].' '.date('Y').'г.';
	
	  $schet = '070122-1266';
	  if(!empty($zakaz_data['number'])){
		$schet = $zakaz_data['number'];
	  }
	  else {
		//if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) 
		$schet = $zakaz_id;
		//else $schet = "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
	  }

	  if($zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
		$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
	  }
	  else {
		if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
		$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
		else
		$currentDate = date("d.m.Y",strtotime($zakaz_data['create_date']));
	  }
	
		$inputFileName = dirname(@$_SERVER['SCRIPT_FILENAME']).'/classes/Components/files/xls_templates/print_zakaz_template.xlsx';
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load($inputFileName);
		$sheet = $spreadsheet->getActiveSheet();
		
		$sheet-> setCellValue("A1", "Заказ № ".$schet." от ".$currentDate);
		//$sheet-> setCellValue("AE1", $currentDate);
		//$sheet-> setCellValue("AE1", "№ {$schet}            от {$currentDate}");
		
		$sheet-> setCellValue("C4", $mainc_data['name'].($mainc_data['inn']>0?", ИНН "
			.$mainc_data['inn']:"").($mainc_data['kpp']>0?", КПП "
			.$mainc_data['kpp']:"").($sklad_data['address']!=""?", "
			.$sklad_data['address']:"").", тел:".$mainc_data['mphone']);
		$sheet->setCellValue("C6",$client_data['name'].($client_data['inn']>0?", ИНН ".$client_data['inn']:"").($client_data['kpp']>0?", КПП ".$client_data['kpp']:"").(trim($client_data['address'])!=""?", ".$client_data['address']:"").(trim($sklad_data['phone'])!=""?", тел. магазина:".$sklad_data['phone']:"").(trim($client_data['mphone'])!=""?", тел:".$client_data['mphone']:""));
		$spreadsheet->getActiveSheet()->mergeCells("A7:B7");
		$spreadsheet->getActiveSheet()->mergeCells("C7:H7");
		if(count((array)$client_cars)>0){
			if($zakaz_data['car_id']>0){
				foreach($client_cars as $car){
					if($car['id']==$zakaz_data['car_id']) {
						$sheet-> setCellValue("A7", "Автомобиль:");
						$sheet-> setCellValue("C7", 'марка: '.$car['auto_maker_name'].', модель:'.$car['auto_model'].', вин:'.$car['vin'].', год:'.(empty($car['made_year'])?"не указан":$car['made_year']));
					}
				}
			}
			else {

			}
		}

	$goods = $data['goods'];
	if(count((array)$goods)>1)
		$spreadsheet->getActiveSheet()->insertNewRowBefore(12, count((array)$goods) - 1);
	
	$arrTotalPrice = [];
	$arrTax = [];
	$arrTotalTax = [];
	
	for ($i=0; $i < count((array)$goods); $i++) { 
		$x = $i + 10;
		$y = $i + 1;
		/*$spreadsheet->getActiveSheet()->mergeCells("B{$x}:C{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("D{$x}:I{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("J{$x}:V{$x}");
		
		$spreadsheet->getActiveSheet()->mergeCells("W{$x}:AB{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AC{$x}:AE{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AG{$x}:AK{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AL{$x}:AR{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AS{$x}:AV{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AW{$x}:AY{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AZ{$x}:BD{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BE{$x}:BH{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BI{$x}:BN{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BO{$x}:BQ{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BS{$x}:BX{$x}");*/
		$totalPrice = (float) $goods[$i]['count'] * (float) $goods[$i]['price'];
		$tax = ($totalPrice / 100) * 20;
		$totalTax = $totalPrice + $tax;
		$sheet-> setCellValue("A{$x}", $y);
		$sheet-> setCellValue("B{$x}", $goods[$i]['id']);
		$sheet-> setCellValue("C{$x}", $goods[$i]['brand']);
		//$sheet-> setCellValue("E{$x}", $y);
		$sheet-> setCellValue("D{$x}", $goods[$i]['name']);
		$sheet-> setCellValue("F{$x}", $goods[$i]['count']);
		//$sheet-> setCellValue("AL{$x}", $goods[$i]['price']);
		$sheet-> setCellValue("E{$x}", number_format($goods[$i]['price'],2,","," "));
		//$sheet-> setCellValue("T{$x}", (string) round($totalPrice, 2));
		$sheet-> setCellValue("H{$x}", number_format($goods[$i]['price']*$goods[$i]['count'],2,","," "));
		$styleArray = array(
			'borders' => array(
				'outline' => array(
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => array('argb' => '000000'),
				),
			),
		);
		$sheet ->getStyle("A{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("B{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("C{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("D{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("E{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("F{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("G{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("H{$x}")->applyFromArray($styleArray);
		$zakaz_sum+=$goods[$i]['price']*$goods[$i]['count'];
		$zakaz_count_sum+=$goods[$i]['count'];
		$sum_without_nds+=round(($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
		$sum_nds+=round(($goods[$i]['price']*$goods[$i]['count'])-($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
		//array_push($arrTotalPrice, round($totalPrice, 2));
		//array_push($arrTax, round($tax, 2));
		//array_push($arrTotalTax, round($totalTax, 2));    
	}
	
	$indexCell = count((array)$goods) + 11;
	$sheet->setCellValue("H{$indexCell}", number_format($zakaz_sum,2,","," "));
	$indexCell+=1;
	if((int)$mainc_taxtype['is_nds']==1)
		$sheet->setCellValue("H{$indexCell}", number_format(round($zakaz_sum-$zakaz_sum/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," "));
	else
		$sheet->setCellValue("H{$indexCell}", "Без НДС");
	$indexCell+=1;
	$sheet->setCellValue("H{$indexCell}", number_format($zakaz_sum,2,","," "));
	$indexCell+=1;
	$sheet->setCellValue("A{$indexCell}",'Всего наименований '.count((array)$goods).' на сумму '.number_format(round($zakaz_sum,2),2,"."," ").' руб.');

	$payments_sum=0;
	$payments=$db->getAll("select zakaz_id,sum(summ) as sum,payment_type from payment where zakaz_id=?i and deleted=0 group by zakaz_id,payment_type",$zakaz_data['id']);
	if($payments) 
		$spreadsheet->getActiveSheet()->insertNewRowBefore(count((array)$goods)+19, count((array)$payments) - 1);
	$indexCell+=4;
	$sheet->setCellValue("D{$indexCell}","");
	foreach($payments as $payment){
		switch ($payment['payment_type']){
		case "1": $sheet->setCellValue("D{$indexCell}", "Наличными: ".number_format(round($payment['sum'],2),2,"."," ")." руб."); break;
		case "2": $sheet->setCellValue("D{$indexCell}", "Картой: ".number_format(round($payment['sum'],2),2,"."," ")." руб."); break;
		case "3": $sheet->setCellValue("D{$indexCell}", "Наличными курьеру: ".number_format(round($payment['sum'],2),2,"."," ")." руб."); break;
		case "4": $sheet->setCellValue("D{$indexCell}", "Перечислением: ".number_format(round($payment['sum'],2),2,"."," ")." руб."); break;
		case "6": $sheet->setCellValue("D{$indexCell}", "Оплата по QR коду (СБП): ".number_format(round($payment['sum'],2),2,"."," ")." руб."); break;
		case "7": $sheet->setCellValue("D{$indexCell}", "Переводом: ".number_format(round($payment['sum'],2),2,"."," ")." руб."); break;
		}
		$payments_sum+=round($payment['sum'],2);
		$indexCell+=1;
	}
	$sheet->setCellValue("D{$indexCell}", number_format(round($zakaz_sum-$payments_sum,2),2,"."," ")." руб.");
	$indexCell+=2;
	$spreadsheet->getActiveSheet()->mergeCells("A{$indexCell}:H{$indexCell}");
	$sheet->setCellValue("A{$indexCell}","");
	$zakaz_footer=$db->getOne("select zakaz_footer from zakaz_footers where deleted=0 and is_default=1 and main_company_id=?i",$_SESSION['main_company']);
	if($zakaz_footer){
		$sheet->setCellValue("A{$indexCell}",$zakaz_footer);
	}
	else {
		$sheet->setCellValue("A{$indexCell}","Наименование деталей с моих слов записаны верно, с ценами и условиями размещения заказа у поставщиков деталей ознакомлен и согласен.
		Детали по настоящему заказу мною получены, не бракованны и полностью соответствуют заказанным, претензий не имею.

		ПРИМЕЧАНИЕ: Оригинальные запчасти и запчасти, заказанные со слов заказчика, обмену и возврату не подлежат!

		Я, ".($inkognito==0?$client_data['name']:"").", даю согласие на использование персональных данных исключительно в целях формирования заказа на автозапчасти, 
		а также на хранение всех вышеназванных данных на электронных носителях. Также данным согласием я разрешаю сбор моих персональных данных, их хранение, 
		систематизацию, обновление, использование (в т.ч. передачу третьим лицам для обмена информацией), а также осуществление любых иных действий, 
		предусмотренных действующим законом Российской Федерации.
		До моего сведения доведено, что ".$mainc_data['name']." гарантирует обработку моих персональных данных в соответствии с действующим законодательством Российской Федерации. Срок действия данного согласия не ограничен. Согласие может быть отозвано в любой момент по моему письменному заявлению.
		Подтверждаю, что давая согласие я действую без принуждения, по собственной воле и в своих интересах.");

	}
	$indexCell+=2;
	if(!empty($mainc_data['ruk']) && $mainc_data['type']==2) $sheet->setCellValue("C{$indexCell}","/".$ruk);
	else $sheet->setCellValue("C{$indexCell}","/");
	$indexCell+=2;
	$ipreg_data="";
	$writer = new Xlsx($spreadsheet);
	$writer->save("/tmp/print_zakaz_".$_SESSION['user_id'].".xlsx");
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);
		$file=base64_encode(file_get_contents("/tmp/print_zakaz_".$_SESSION['user_id'].".xlsx"));
		unlink("/tmp/print_zakaz_".$_SESSION['user_id'].".xlsx");
		return array("status"=>"ok","msg"=>"","file"=>$file);//,"zakaz_details"=>$zakaz_details,"data"=>$data);
	//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	//header('Content-Disposition: attachment;filename="peredatochny_doc.xls"');
	//$writer->save('php://output');
	
}
  
public static function get_print_tovar_check_xls($request){
	$db=DB::getInstance();

	if(isset($request->zakaz_id)){
		$zakaz_id=$request->zakaz_id;
		$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
		$zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
			left join service_jobs sj on (sj.id=zj.job_id)
			where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
		$zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
	}
	if(isset($request->document_id)){
		$zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$request->document_id);
		$zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$request->document_id);
		$zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.document_id=?i and dj.deleted=0",$request->document_id);
		$zakaz_data=$db->getRow("select * from document where id=?i",$request->document_id);
		$zakaz_data['main_company_id']=$zakaz_data['main_company'];
		//$zakaz_data['id']=$zakaz_id;
		//}
	}
	if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
		return array("status"=>"err","err"=>"Выберите свой заказ");
	}
	//echo "select * from zakaz where id=$zakaz_id<br>";
	//echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
	$client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
	$client_cars=$db->getAll("select id,auto_maker_name,auto_model,vin,made_year from company_cars where company_id=?i and main_company_id=?i and deleted=0",$client_data['id'],$_SESSION['main_company']);
	if($client_data['main_org_id']>0) $client_main_org_data=$db->getRow("select * from company where id=?i",$client_data['main_org_id']);
	$mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
	$poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
	$pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id'],$zakaz_data['company_id']);
	$mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
	$sklad_data=$db->getRow("select * from sklad where id=(select delivery_type_id from zakaz where id=?i)",$zakaz_id);
	if(isset($request->zakaz_id) && $mainc_taxtype['is_nds']==1 ){
	die("При работе с НДС, распечатка документов возможна только из вкладки документы");
	}
	$ruk_arr=explode(" ",$mainc_data['ruk']);
	//echo print_r($ruk_arr,true)."<br>";
	$ruk_name=mb_substr($ruk_arr[1],0,1);
	//echo print_r($ruk_name,true)."<br>";
	$ruk_otch=mb_substr($ruk_arr[2],0,1);
	$ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";

	$data = array(
		'buyer' => array(
			'name' => $mainc_data['name'],
			'address' => $mainc_data['address'],
			'inn' => $mainc_data['inn'],
			'kpp' => $mainc_data['kpp'],
			'anotherAdress' => $mainc_data['post_address'],
			'recipient' => $client_data['name'].",".$client_data['address'],
		),
		'seller' => array(
			'name' => $client_data['name'],
			'address' => $client_data['address'],
			'inn' => $client_data['inn'],
			'kpp' => $client_data['kpp']
		),
		'goods' => array(),
	);
	
	foreach($zakaz_details as $zd_key=>$zd_val){
		array_push($data['goods'],
			array(
				'id' => $zd_val['article'],
				'brand' => $zd_val['brand'],
				'name' => $zd_val['name'],
				'count' => $zd_val['count'],
				'price' => $zd_val['price'],
				'country' => array(
					'id' => '',
					'title' => ''
				),
				'regNum' => ''
			)
		);
	}

	$arr = [
		'января',
		'февраля',
		'марта',
		'апреля',
		'мая',
		'июня',
		'июля',
		'августа',
		'сентября',
		'октября',
		'ноября',
		'декабря'
	  ];
	  
	  $month = date('n')-1;
	  $currentDate = date('d').' '.$arr[$month].' '.date('Y').'г.';
	
	  $schet = '070122-1266';
	  if(!empty($zakaz_data['number'])){
		$schet = $zakaz_data['number'];
	  }
	  else {
		//if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) 
		$schet = $zakaz_id;
		//else $schet = "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
	  }

	  if($zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
		$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
	  }
	  else {
		if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
		$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
		else
		$currentDate = date("d.m.Y",strtotime($zakaz_data['create_date']));
	  }
	
		$inputFileName = dirname(@$_SERVER['SCRIPT_FILENAME']).'/classes/Components/files/xls_templates/print_tovar_check_template.xlsx';
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load($inputFileName);
		$sheet = $spreadsheet->getActiveSheet();
		
		$sheet-> setCellValue("B7", "Товарный чек № ".$schet." от ".$currentDate);
		//$sheet-> setCellValue("AE1", $currentDate);
		//$sheet-> setCellValue("AE1", "№ {$schet}            от {$currentDate}");
		
		$sheet-> setCellValue("E2", $mainc_data['name']);
		$sheet-> setCellValue("C3", ($mainc_data['inn']>0?$mainc_data['inn']:""));
		$sheet-> setCellValue("C4", ($sklad_data['address']!=""?$sklad_data['address']:"").", тел:".$mainc_data['mphone']);
		$sheet->setCellValue("C5",$client_data['name'].($client_data['inn']>0?", ИНН ".$client_data['inn']:"").($client_data['kpp']>0?", КПП ".$client_data['kpp']:"").(trim($client_data['address'])!=""?", ".$client_data['address']:"").(trim($sklad_data['phone'])!=""?", тел. магазина:".$sklad_data['phone']:"").(trim($client_data['mphone'])!=""?", тел:".$client_data['mphone']:""));

	$goods = $data['goods'];
	if(count((array)$goods)>1)
		$spreadsheet->getActiveSheet()->insertNewRowBefore(12, count((array)$goods) - 1);
	
	$arrTotalPrice = [];
	$arrTax = [];
	$arrTotalTax = [];
	
	for ($i=0; $i < count((array)$goods); $i++) { 
		$x = $i + 10;
		$y = $i + 1;
		/*$spreadsheet->getActiveSheet()->mergeCells("B{$x}:C{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("D{$x}:I{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("J{$x}:V{$x}");
		
		$spreadsheet->getActiveSheet()->mergeCells("W{$x}:AB{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AC{$x}:AE{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AG{$x}:AK{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AL{$x}:AR{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AS{$x}:AV{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AW{$x}:AY{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("AZ{$x}:BD{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BE{$x}:BH{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BI{$x}:BN{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BO{$x}:BQ{$x}");
		$spreadsheet->getActiveSheet()->mergeCells("BS{$x}:BX{$x}");*/
		$totalPrice = (float) $goods[$i]['count'] * (float) $goods[$i]['price'];
		$tax = ($totalPrice / 100) * 20;
		$totalTax = $totalPrice + $tax;
		$sheet-> setCellValue("A{$x}", $y);
		if($request->show_art==1) $sheet-> setCellValue("B{$x}", $goods[$i]['id']);
		$sheet-> setCellValue("C{$x}", $goods[$i]['brand']);
		//$sheet-> setCellValue("E{$x}", $y);
		$sheet-> setCellValue("D{$x}", $goods[$i]['name']);
		$sheet-> setCellValue("F{$x}", $goods[$i]['count']);
		//$sheet-> setCellValue("AL{$x}", $goods[$i]['price']);
		$sheet-> setCellValue("E{$x}", number_format($goods[$i]['price'],2,","," "));
		//$sheet-> setCellValue("T{$x}", (string) round($totalPrice, 2));
		$sheet-> setCellValue("G{$x}", number_format($goods[$i]['price']*$goods[$i]['count'],2,","," "));
		$styleArray = array(
			'borders' => array(
				'outline' => array(
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => array('argb' => '000000'),
				),
			),
		);
		$sheet ->getStyle("A{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("B{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("C{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("D{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("E{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("F{$x}")->applyFromArray($styleArray);
		$sheet ->getStyle("G{$x}")->applyFromArray($styleArray);
		$zakaz_sum+=$goods[$i]['price']*$goods[$i]['count'];
		$zakaz_count_sum+=$goods[$i]['count'];
		$sum_without_nds+=round(($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
		$sum_nds+=round(($goods[$i]['price']*$goods[$i]['count'])-($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
		//array_push($arrTotalPrice, round($totalPrice, 2));
		//array_push($arrTax, round($tax, 2));
		//array_push($arrTotalTax, round($totalTax, 2));    
	}
	
	$indexCell = count((array)$goods) + 11;
	$sheet->setCellValue("G{$indexCell}", number_format($zakaz_sum,2,","," "));
	$indexCell+=1;
	/*if((int)$mainc_taxtype['is_nds']==1)
		$sheet->setCellValue("G{$indexCell}", number_format(round($zakaz_sum-$zakaz_sum/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," "));
	else
		$sheet->setCellValue("G{$indexCell}", "Без НДС");*/
	$indexCell+=1;
	//$sheet->setCellValue("G{$indexCell}", number_format($zakaz_sum,2,","," "));
	$indexCell+=1;
	$sheet->setCellValue("A{$indexCell}",'Всего наименований '.count((array)$goods).' на сумму '.number_format(round($zakaz_sum,2),2,"."," ").' руб.');
	$indexCell+=2;
	if(!empty($mainc_data['ruk']) && $mainc_data['type']==2) $sheet->setCellValue("C{$indexCell}",$ruk);
	$indexCell+=2;
	$ipreg_data="";
	$writer = new Xlsx($spreadsheet);
	$writer->save("/tmp/print_tovar_check_".$_SESSION['user_id'].".xlsx");
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);
		$file=base64_encode(file_get_contents("/tmp/print_tovar_check_".$_SESSION['user_id'].".xlsx"));
		unlink("/tmp/print_tovar_check_".$_SESSION['user_id'].".xlsx");
		return array("status"=>"ok","msg"=>"","file"=>$file);//,"zakaz_details"=>$zakaz_details,"data"=>$data);
	//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	//header('Content-Disposition: attachment;filename="peredatochny_doc.xls"');
	//$writer->save('php://output');
	
}

}



?>
