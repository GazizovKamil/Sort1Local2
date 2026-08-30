<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Payment;
use Sort1API\Components\Zakaz;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\UploadHandler;
use Sort1API\Components\BankToBase;
use Sort1API\Components\Company;
use Sort1API\Components\CashDesk;
use Sort1API\Components\CompanyRekvizit;
use Sort1API\Components\Models\Companys;
use Sort1API\Components\TinkoffMerchantAPI;
use Sort1API\Components\ZzapApi;


/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Payments extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }
      	public static function add_to_balance($company_id,$sum,$field="balance"){
      	    $db = DB::getInstance();
      	    $balance=new CompanyBalance($company_id);
            switch($field){
      	      case "balance": $balance->balance+=$sum; break;
              case "cashback": $balance->cashback+=$sum; break;
              case "rezerv": $balance->rezerv+=$sum; break;
            }
      	    $balance->save();
            return $balance->balance;
      	    //$sql="update company_balance set balance=balance+?s where company_id=?i and main_company_id=?i";
      	    //$db->query($sql,$sum,$company_id,$main_company_id);
      	}

        public static function update_cash_desk($company_id,$sum,$payment){
          $db = DB::getInstance();
          $cashdesk_id=$db->getOne("select id from cash_desks where user_id=?i and main_company_id=?i and deleted=0 and sklad_id=?i",$payment->user_id,$_SESSION['main_company'],$_SESSION['my_sklad_id']);
          if(!$cashdesk_id){
            $cashdesk_id=$db->getOne("select id from cash_desks where main_kassa=1 and main_company_id=?i and deleted=0 and sklad_id=?i",$_SESSION['main_company'],$_SESSION['my_sklad_id']);
          }
          if($cashdesk_id && ($payment->payment_type==1 || $payment->payment_type==3)) {
            if($payment->payment_direction==2 && $payment->from_cashdesk_id>0) $cashdesk=new CashDesk($payment->from_cashdesk_id);
            else $cashdesk=new CashDesk($cashdesk_id);
          }
          if(isset($cashdesk)){
            $cashdesk->summ+=$sum;
            $cashdesk->save();
            return $cashdesk->summ;
          }
          else return 0;
          //$sql="update company_balance set balance=balance+?s where company_id=?i and main_company_id=?i";
          //$db->query($sql,$sum,$company_id,$main_company_id);
        }

        public static function save_payment($request) {
            $db = DB::getInstance();
            if(empty($request->company_id) || (int)$request->company_id<=0) return self::_error_arr("Не указана компания плательщик");
            if(empty($request->summ) || (float)$request->summ<=0) return self::_error_arr("Не указана сумма");
      	    if (isset($request->payment_id)) $payment_id=(int)$request->payment_id;
      	    if (isset($payment_id) && $payment_id>0) {
      		    $payment=new Payment($payment_id);
      	    }
      	    else
      		    $payment=new Payment(); 
      	    if (isset($request->company_id) && (int)$request->company_id>0) {
          		$companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
          		if ($companys && in_array($request->company_id,$companys))
                if($payment->company_id!=(int)$request->company_id && $payment->company_id>0 && $request->payment_type!=8){ // Изменение компании плательщика
                  // снять деньги со счета одной компании и перекинуть на новую
                  self::add_to_balance($payment->company_id,-(float)$request->summ,"balance");
                  self::add_to_balance($request->company_id,(float)$request->summ,"balance");
                  $payment->company_id=(int)$request->company_id;
                }
                else{
          		    $payment->company_id=(int)$request->company_id;
                }
          		else {
                if((int)$request->company_id==$_SESSION['main_company']) return self::_error_arr("Нельзя добавить платеж к основной компании");
          		  else return self::_error_arr("Нельзя добавить платеж к чужой компании");
      		    }
      	    }
      	    else $payment->company_id=$_SESSION['company_id'];

            $user_kassa = $db->getOne("select id from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=?i and registered_in_tax=1 and open_shift=1",(int)$_SESSION['my_sklad_id'],(int)$_SESSION['user_id']);
            if(empty($user_kassa)){
              $kassa = $db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=0",(int)$_SESSION['my_sklad_id']);
              /*if($kassa['dont_make_payment_in_close_shift'] == 1 && $kassa['open_shift'] == 0 && (int)$request->payment_type!=2){
                return self::_error_arr("Откройте смену кассы");
              }*/
            }

      	    if (isset($request->payment_type)) $payment->payment_type=(int)$request->payment_type;
            if($payment->payment_type==8){
              $company_balance=new CompanyBalance((int)$request->company_id);
              if((float)$company_balance->cashback<(float)str_replace(",",".",$request->summ)){
                return self::_error_arr("На бонусном счете не хватает денег, на бонусном счете:<b>".$company_balance->cashback." р.</b>");
              }
              else {
                $company_balance->cashback-=(float)str_replace(",",".",$request->summ);
                $company_balance->save();
              }
            }
            if (isset($request->zakaz_id)) $payment->zakaz_id=(int)$request->zakaz_id;
            if (isset($request->zakaz_detail_id)) $payment->zakaz_detail_id=(int)$request->zakaz_detail_id;
      	    if (isset($request->from_rs)) $payment->from_rs=(int)$request->from_rs;
      	    if (isset($request->from_ks)) $payment->from_ks=(int)$request->from_ks;
      	    if (isset($request->from_bank)) $payment->from_bank=$request->from_bank;
      	    if (isset($request->to_rs)) $payment->to_rs=(int)$request->to_rs;
      	    if (isset($request->to_ks)) $payment->to_ks=(int)$request->to_ks;
      	    if (isset($request->to_bank)) $payment->to_bank=$request->to_bank;
      	    if (isset($request->summ)) {
              $payment->summ=(float)str_replace(",",".",$request->summ);
            }
      	    if (isset($request->from_inn)) $payment->from_inn=(int)$request->from_inn;
      	    if (isset($request->to_inn)) $payment->to_inn=(int)$request->to_inn;
      	    if (isset($request->from_kpp)) $payment->from_kpp=(int)$request->from_kpp;
      	    if (isset($request->to_kpp)) $payment->to_kpp=(int)$request->to_kpp;
            if (isset($request->create_date)) $payment->create_date=str_replace("T"," ",$request->create_date);
            if (isset($request->is_advance) && ($request->is_advance===true || $request->is_advance=="on") && $request->is_advance!==0) $payment->is_advance=1;
            else $payment->is_advance=0;
            if (isset($request->dont_fiscalize) && ($request->dont_fiscalize===true || $request->dont_fiscalize=="on") && $request->dont_fiscalize!==0) $payment->dont_fiscalize=1;
            else $payment->dont_fiscalize=0;
            if (isset($request->payment_target)) $payment->payment_target=$request->payment_target;
            if (isset($request->payment_direction)) $payment->payment_direction=(int)$request->payment_direction;
            if (isset($request->payment_direction) && $request->payment_direction==2 && isset($request->payment_cashdesk_id) && $request->payment_cashdesk_id>0) {
              $payment->from_cashdesk_id=$request->payment_cashdesk_id;
            }
      	    if (isset($request->payment_num)) $payment->payment_num=$request->payment_num;
            if (isset($request->UniversalID)) $payment->UniversalID=$request->UniversalID;
      	    if (isset($request->company_rekvizit_id)) $payment->company_rekvizit_id=(int)$request->company_rekvizit_id;
      	    $payment->main_company_id=$_SESSION['main_company'];
            if($payment->payment_type==4 && $_SESSION['roles']>2) 
              return self::_error_arr("Нет полномочий для добавления безналичного платежа");
            //print_r($payment);
            if(isset($request->zakaz_id) && (int)$request->zakaz_id>0){
              $zakaz=new Zakaz((int)$request->zakaz_id);
              $payment->from_cashback_balance=$zakaz->zakaz_cashback_discount;
              if($payment->is_advance==0 && $payment->payment_type!=8) {
                if($payment->payment_direction<3) self::add_to_balance($payment->company_id,-(float)$payment->from_cashback_balance,"cashback");
                else 
                  self::add_to_balance($payment->company_id,(float)$payment->from_cashback_balance,"cashback");
              }
              $zakaz->zakaz_cashback_discount_repaid=1;
				      $zakaz->save();
            }
            $err_data=$payment->save(); 
            if(is_array($err_data)){
              $err=$err_data['code'];
              //$err_msg=$err_data['msg'];
            }
            else $err=$err_data;
            if($err==1 || $err==10){
              if(isset($request->zakaz_id) && (int)$request->zakaz_id>0){
                if(!isset($zakaz)) $zakaz=new Zakaz((int)$request->zakaz_id);
                if((int)$zakaz->company_id!=(int)$payment->company_id) return self::_error_arr("Заказ не принадлежит указанной компании");
                if((int)$payment->payment_direction==1) { // оплачиваем только заказы с оплатой от клиентов
                  //if((int)$zakaz->oplachen==1) return self::_error_arr("Данный заказ уже оплачен, выберите правильный заказ");
                  $zakaz->oplachen=1;
                }
                $zakaz->payment_type=$payment->payment_type;
                switch($zakaz->status){
                  case 1: //заказ не подтвержден и не зарезирвировано на складе
                    $zakaz->status=2; 
                    $zakaz->save();
                    $zakaz_details=$db->getAll("select id,zakaz_id,detail_id,deliverer_type,deliverer_id from zakaz_details where zakaz_id=?i",$zakaz->id);
                    foreach($zakaz_details as $det_key=>$det_val){
                        $zd=new ZakazDetail($det_val['id']);
                        if($zd->status<2){
                          $zd->status=2;
                          $zd->save();
                        }
                    }
                    $zakaz->status=3;
                    $zakaz->save();
                    foreach($zakaz_details as $det_key=>$det_val){
                      $zd=new ZakazDetail($det_val['id']);
                      if($zd->status<3){
                        $zd->status=3;
                        $zd->save();
                      }
                    }
                    break;
                  case 2: //заказ подтвержден
                    $zakaz->status=3;
                    $zakaz->save();
                    $zakaz_details=$db->getAll("select id,zakaz_id,detail_id,deliverer_type,deliverer_id from zakaz_details where zakaz_id=?i",$zakaz->id);
                    foreach($zakaz_details as $det_key=>$det_val){
                        $zd=new ZakazDetail($det_val['id']);
                        if($zd->status<3){
                          $zd->status=3;
                          $zd->save();
                        }
                    }
                    break;
                  case 3: 
                    //return self::_error_arr("Данный заказ уже оплачен, выберите правильный заказ");
                    break;
                  default: $zakaz->save();
                }
              }
            }
      		//if(isset($request->zakaz_id) && (int)$request->zakaz_id>0){
      		//	$zakaz=new Zakaz((int)$request->zakaz_id);
      		//	if($zakaz->oplachen && !isset($payment_id)) // Если заказ оплачен уже и это новый платеж привязанный к этому заказу
      		//	    return self::_error_arr("Данный заказ уже оплачен, выберите правильный заказ");
      		//	$zakaz->payment_id=$payment->id;
      		//	if($zakaz->zakaz_sum<=$payment->summ) {
      		//	    $raznost=$payment->summ-$zakaz->zakaz_sum;
      		//	    if($raznost>0) self::add_to_balance($_SESSION['main_company'],$payment->company_id,$raznost);
      		//	    $zakaz->oplachen=1;
      		//	}
      			//echo "zakaz->payment_id=".$zakaz->payment_id."\n zakaz_id=".$zakaz->id." payment->id=".$payment->id."\n";
      		//	$zakaz_err=$zakaz->save();
      		//}
      		//else {
      		    //if($payment->summ>0) self::add_to_balance($payment->company_id,$payment->summ);
      		//} 
        
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->payment_id) && (int)$request->payment_id>0){
                          	    $status="ok"; $msg="Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok"; $msg="Новый платеж добавлен";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
            }
            if(isset($err_data['err'])) $msg=$err_data['err'];
            if ($status=="err") return array("status"=>"err","err"=>$msg,"err_data"=>$err_data);
            else return array("status"=>$status,"msg"=>$msg,"check_data"=>$err_data['check_data'],"excise_check_data"=>$err_data['check_dataE'], "err_data"=>$err_data);
         }


	public static function get_payments($request) {
	    $db = DB::getInstance();
      $sf="";
      $parsed="";
	    if($_SESSION['roles']<10)
        $sql="select p.*,c.name as company_name,u.lastname,u.name,u.middlename,u.id as user_id from payment p
              left join company c on (c.id=p.company_id)
              left join users u on (u.id=p.user_id)
              where p.main_company_id=?i and p.deleted=0 and p.payment_direction=1 and p.zakaz_id in (?b)";
	    else
        $sql="select p.*,c.name as company_name from payment p
              left join company c on (c.id=p.company_id)
              where p.company_id=?i and p.deleted=0  and p.payment_direction=1";
      if(isset($request->from_date) && !empty($request->from_date)) {
        $parsed.=$db->parse(" and p.create_date>=?s",$request->from_date);
        //$sf.="f";
      }
      if(isset($request->to_date) && !empty($request->to_date)) {
        $parsed.=$db->parse(" and p.create_date<=?s",$request->to_date." 23:59:59");
        //$sf.="t";
      }
      if(isset($request->client) && !empty($request->client)) {
        $parsed.=$db->parse(" and p.company_id in (select id from company where name like ?s)","%".$request->client."%");
        //$sf.="c";
      }
      $sql.=" ?p order by create_date desc, payment_num desc";
      //switch ($sf) {
	    // case "f": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$parsed); break;
      // case "t": $res=$db->getAll($sql,$_SESSION['company_id'],$request->to_date,$parsed); break;
      // case "ft": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$request->to_date,$parsed); break; 
      // default: 
      $zakazes=$db->getCol("select id from zakaz where delivery_type=1 and delivery_type_id=?i",$_SESSION['my_sklad_id']);
      $zakazes1=$db->getCol("select id from zakaz where delivery_type=2 and fullfilment_id=?i",$_SESSION['my_sklad_id']);
      $zakazes=array_unique(array_merge($zakazes,$zakazes1));
      $zakazes[]=0;
      //$ret['zakazes']=$zakazes;
      if($_SESSION['roles']<10)
        $res=$db->getAll($sql,$_SESSION['company_id'],$zakazes,$parsed);
      else
        $res=$db->getAll($sql,$_SESSION['company_id'],$parsed);
     //}
	    //$res=$db->getAll($sql,$_SESSION['company_id']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['payments']=$res;
        $ret['payment_types']=$db->getIndCol("id","select id,name from payment_types");
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['payments']=[];
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
  }

  public static function get_zakaz_payments($request) {
    $db = DB::getInstance();
    $sf="";
    $parsed="";
    if(empty($request->zakaz_id) || (int)$request->zakaz_id<1) return array("status"=>"err","err"=>"Не указан номер заказа");
    if($_SESSION['roles']<10)
      $sql="select p.*,c.name as company_name,u.lastname,u.name,u.middlename,u.id as user_id from payment p
            left join company c on (c.id=p.company_id)
            left join users u on (u.id=p.user_id)
            where p.main_company_id=?i and p.deleted=0 ";
    else
      $sql="select p.*,c.name as company_name from payment p
            left join company c on (c.id=p.company_id)
            where p.company_id=?i and p.deleted=0 ";
    $parsed.=$db->parse(" and zakaz_id=?i",$request->zakaz_id);
    $sql.=" ?p order by create_date, payment_num desc";
    //switch ($sf) {
    // case "f": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$parsed); break;
    // case "t": $res=$db->getAll($sql,$_SESSION['company_id'],$request->to_date,$parsed); break;
    // case "ft": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$request->to_date,$parsed); break; 
    // default: 
    $res=$db->getAll($sql,$_SESSION['company_id'],$parsed);
   //}
    //$res=$db->getAll($sql,$_SESSION['company_id']);
    if (is_array($res) && count($res)>0){
      $ret['status']="ok";
      $ret['err']="";
      $ret['payments']=$res;
      $ret['payment_types']=$db->getIndCol("id","select id,name from payment_types");
      $ret['msg']="";
    }
    else {
      $ret['status']="ok";
      $ret['err']="";
      $ret['payments']=[];
      $ret['msg']="";
    }
    if ($ret['status']=="err") return self::_error_arr($ret['err']);
    else return $ret;
}
  
  public static function get_return_payments($request) {
    $db = DB::getInstance();
    $sf="";
    $parsed="";
    if($_SESSION['roles']<10)
      $sql="select p.*,c.name as company_name from payment p
            left join company c on (c.id=p.company_id)
            where p.main_company_id=?i and p.deleted=0 and (p.payment_direction=3 or p.payment_direction=4 or p.payment_direction=5) and p.zakaz_id in (?b)";
    else
      $sql="select p.*,c.name as company_name from payment p
            left join company c on (c.id=p.company_id)
            where p.company_id=?i and p.deleted=0  and p.payment_direction=3";
    if(isset($request->from_date) && !empty($request->from_date)) {
      $parsed.=$db->parse(" and p.create_date>=?s",$request->from_date);
      //$sf.="f";
    }
    if(isset($request->to_date) && !empty($request->to_date)) {
      $parsed.=$db->parse(" and p.create_date<=?s",$request->to_date." 23:59:59");
      //$sf.="t";
    }
    if(isset($request->client) && !empty($request->client)) {
      $parsed.=$db->parse(" and p.company_id in (select id from company where name like ?s)","%".$request->client."%");
      //$sf.="c";
    }
    $sql.=" ?p order by create_date desc, payment_num desc";
    //switch ($sf) {
    // case "f": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$parsed); break;
    // case "t": $res=$db->getAll($sql,$_SESSION['company_id'],$request->to_date,$parsed); break;
    // case "ft": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$request->to_date,$parsed); break; 
    // default: 
    $zakazes=$db->getCol("select id from zakaz where delivery_type=1 and delivery_type_id=?i",$_SESSION['my_sklad_id']);
    $zakazes1=$db->getCol("select id from zakaz where delivery_type=2 and fullfilment_id=?i",$_SESSION['my_sklad_id']);
    $zakazes=array_unique(array_merge($zakazes,$zakazes1));
    $zakazes[]=0;
    if($_SESSION['roles']<10) $res=$db->getAll($sql,$_SESSION['company_id'],$zakazes,$parsed);
    else $res=$db->getAll($sql,$_SESSION['company_id'],$parsed);
   //}
    //$res=$db->getAll($sql,$_SESSION['company_id']);
    if (is_array($res) && count($res)>0){
      $ret['status']="ok";
      $ret['err']="";
      $ret['payments']=$res;
      $ret['payment_types']=$db->getIndCol("id","select id,name from payment_types");
      $ret['msg']="";
    }
    else {
      $ret['status']="ok";
      $ret['err']="";
      $ret['payments']=[];
      $ret['msg']="";
    }
    if ($ret['status']=="err") return self::_error_arr($ret['err']);
    else return $ret;
}

  public static function get_delivery_payments($request) {
	    $db = DB::getInstance();
      $sf="";
      $parsed="";
	    if($_SESSION['roles']<10) 
        $sql="select p.*,c.name as company_name,c.inn as company_inn,c.kpp as company_kpp,
          u.lastname,u.name,u.middlename,u.id as user_id 
          from payment p 
          left join company c on (c.id=p.company_id)
          left join users u on (u.id=p.user_id) 
          where p.main_company_id=?i and p.deleted=0 and p.payment_direction=2";
	    else $sql="select * from payment where company_id=?i and deleted=0 and payment_direction=2";
      if(isset($request->from_date) && !empty($request->from_date)) {
        $parsed.=$db->parse(" and p.create_date>=?s",$request->from_date);
        //$sf.="f";
      }
      if(isset($request->to_date) && !empty($request->to_date)) {
        $parsed.=$db->parse(" and p.create_date<=?s",$request->to_date." 23:59:59");
        //$sf.="t";
      }
      if(isset($request->client) && !empty($request->client)) {
        $parsed.=$db->parse(" and p.company_id in (select id from company where name like ?s)","%".$request->client."%");
        //$sf.="c";
      }
      $sql.=" ?p order by create_date desc, payment_num desc";
      //switch ($sf) {
	    // case "f": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date); break;
      // case "t": $res=$db->getAll($sql,$_SESSION['company_id'],$request->to_date); break;
      // case "ft": $res=$db->getAll($sql,$_SESSION['company_id'],$request->from_date,$request->to_date); break;
      // default: 
      
      $res=$db->getAll($sql,$_SESSION['company_id'],$parsed);
     //}
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['payments']=$res;
        $ret['payment_types']=$db->getIndCol("id","select id,name from payment_types");
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['payments']=[];
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_payment($request) {
	    $db = DB::getInstance();
	    if($_SESSION['roles']<10) $sql="select p.*,c.name as company_name from payment p left join company c on (c.id=p.company_id) where p.id=?i and p.main_company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) and p.deleted=0";
	    else $sql="select * from payment where id=?i and company_id in (select company_id from user_companys where user_id=?i) and deleted=0";
	    if (isset($request->payment_id) && (int)$request->payment_id>0){
        $payment_id=(int)$request->payment_id;
        $payment=new Payment($payment_id);
	    }
	    else {
		    return self::_error_arr("не указан id платежа");
	    }
	    $res=$db->getRow($sql,$payment_id,(int)$_SESSION['user_id']);
	    if ($res['id']>0){
        $ret['status']="ok";
        $ret['err']="";
        $ret['payment']=$res;
        $ret['company_balance']=$db->getOne("select balance from company_balance where main_company_id=?i and company_id=?i",$_SESSION['main_company'],$res['company_id']);
        if($res['from_cashdesk_id']>0){
          $ret['cashdesk']=$db->getRow("select id,name from cash_desks where id=?i",$res['from_cashdesk_id']);
        }
        $ret['msg']="";
        //$ret['price_types']=$db->getAll("select * from dict_price_type where type=2");
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_payment($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->payment_id)) {$payment_id=(int)$request->payment_id;}
	    if (isset($payment_id) && $payment_id>0){
        $payment=new Payment($payment_id);
        if($payment->fiscalized=="1"){
          return self::_error_arr("Платеж уже фискализирован, его нельзя удалять");
        }
    		$res2=$db->getCol("select company_id from user_companys where main_company_id=0 and user_id=?i",$_SESSION['user_id']);
    		//echo "delete from payment where id=".$payment_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if (in_array($payment->main_company_id,$res2)) {
            $payment->deleted=1;
            $payment->save();
            if($payment->summ>0 && (int)$payment->payment_type!=8) { 
              switch((int)$payment->payment_direction){
                case 1: 
                  $balance=self::add_to_balance($payment->company_id,-$payment->summ); 
                  self::update_cash_desk($payment->company_id,-$payment->summ,$payment); 
                  if($payment->is_advance==0) self::add_to_balance($payment->company_id,(float)$payment->from_cashback_balance,"cashback");
                  break;
                case 2: $balance=self::add_to_balance($payment->company_id,-$payment->summ); self::update_cash_desk($payment->company_id,$payment->summ,$payment); 
                  if($payment->is_advance==0) self::add_to_balance($payment->company_id,(float)$payment->from_cashback_balance,"cashback");
                  break;
                case 3: $balance=self::add_to_balance($payment->company_id,$payment->summ); self::update_cash_desk($payment->company_id,$payment->summ,$payment); 
                  if($payment->is_advance==0) self::add_to_balance($payment->company_id,-(float)$payment->from_cashback_balance,"cashback");
                  break;
                case 4: 
                case 5: $balance=self::add_to_balance($payment->company_id,$payment->summ);
                  if($payment->is_advance==0) self::add_to_balance($payment->company_id,-(float)$payment->from_cashback_balance,"cashback");
                  break;
              }
            }
            if($payment->payment_type==8){
              $company_balance=new CompanyBalance((int)$payment->company_id);
              $company_balance->cashback+=(float)$payment->summ;
              $company_balance->save();
            }
            if((int)$payment->zakaz_id>0 && (int)$payment->payment_direction<3){
              $db->query("update zakaz set oplachen=0 where id=?i",$payment->zakaz_id);
            }
            if($balance>=0 || ((int)$payment->payment_direction>=2 && (int)$payment->payment_direction<=5)){
    		      $ret['status']="ok"; 
    		      $ret['msg']="Платеж успешно удален";
            }
            else {
              $ret['status']="err";
              $ret['err']="Не хватает денег на балансе";
            }
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить Платеж, платеж не вашей компании";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

  public static function bank_upload($request){
    $upload_handler = new UploadHandler();
    //echo print_r($upload_handler,true);
    foreach($upload_handler->response['files'] as $file_key=>$file_val){
      //echo print_r($file_val,true);
      $file_name=$file_val->realname;
      //$local_file_name=Config::get("app-upload-dir").$file_val->name;
      $local_file_name=dirname(self::get_server_var('SCRIPT_FILENAME')).'/files/'.$file_val->name;

      //if(!preg_match("/Кодировка/",$file_content)){
        $file_content=file_get_contents($local_file_name);
        $out_file_content=mb_convert_encoding($file_content,"UTF-8","cp1251");
        file_put_contents($local_file_name,$out_file_content);

      //}
      //file_put_contents("/var/log/shop/api/bank_upload.log","file_name: $local_file_name\n encoding=".mb_detect_encoding($file_content,"cp1251,UTF-8")."\n",FILE_APPEND);
      //$local_file_name="../../../files/".$file_val->name;
      $bank_to_base = new BankToBase($file_name,$local_file_name);
      return $bank_to_base->GetRows();
    }

  }

  public static function import_payments($request){
    $db = DB::getInstance();
    if($request->type=="in") $payment_direction=1;
    else $payment_direction=2;
    if($payment_direction==1){
      foreach($request->payments as $pkey=>$pval){
        if(isset($pval['ПлательщикРасчСчет'])) $platraschet=$pval['ПлательщикРасчСчет'];
        else $platraschet=$pval['ПлательщикСчет'];
        if(isset($pval['ПолучательРасчСчет'])) $polraschet=$pval['ПолучательРасчСчет'];
        else $polraschet=$pval['ПолучательСчет'];
        //file_put_contents("/var/log/shop/api/import_payments.log","pval:".print_r($pval,true)."\n",FILE_APPEND);
        $company_ids=$db->getCol("select id from company where inn=?i",(int)$pval['ПлательщикИНН']);
        if($company_ids && count((array)$company_ids)>1){
          $my_contragents=$db->getCol("select company_id from user_companys where main_company_id=?i and company_id in (?b) and deleted=0",$_SESSION['main_company'],$company_ids);
          if($my_contragents){
            if(count((array)$my_contragents)>1){
              $company_id=$my_contragents[0];
            }
            else {
              $company_id=$my_contragents[0];
            }
          } 
          else {
            $company_id=$company_ids[0];
          }
        }
        else {
          $company_id=$db->getOne("select id from company where inn=?i",(int)$pval['ПлательщикИНН']);//,(int)$pval['ПлательщикКПП']);
        }
        //echo "id_client: $company_id";
        if($company_id){
          $company=new Company($company_id);
        }
        else {
          $company=new Company();
          $company_data=Companys::get_company_data_from_api(json_decode(json_encode(array("inn"=>$pval['ПлательщикИНН']))));
          //file_put_contents("/var/log/shop/api/import_payments.log","company_data:".print_r($company_data,true)."\n",FILE_APPEND);
          foreach($company_data['suggestions'] as $sugkey=>$sugval){
            $company->inn=$sugval['data']['inn'];
            if(isset($sugval['data']['kpp'])) $company->kpp=$sugval['data']['kpp'];
            else $company->kpp=0;
            if($company->inn==(int)$pval['ПлательщикИНН'] && (count((array)$company_data['suggestions'])==1 || $company->kpp==(int)$pval['ПлательщикКПП'])){
              if($company->kpp!=(int)$pval['ПлательщикКПП']) $company->kpp=(int)$pval['ПлательщикКПП'];
              $company->name=$sugval['value'];
              $company->ogrn=$sugval['data']['ogrn'];
              $company->ruk=$sugval['data']['management']['name'];
              $company->rukdol=$sugval['data']['management']['post'];
              if(isset($sugval['data']['address']['value'])) {
                $company->address=$sugval['data']['address']['value'];
              }
              else {
                $company->address=$sugval['data']['address']['data']['source'];
              }
              switch($sugval['data']['opf']['short']){
                case "ИП": $company->type=1; break;
                case "ООО": $company->type=2; break;
                case "НАО": $company->type=2; break;
                case "Филиал": $company->type=2; break;
                case "ПАО": $company->type=2; break;
                case "ЗАО": $company->type=2; break;
              }
              $company->btype=1;//Покупатель
              $company->save();
              $db->query("insert into user_companys set user_id=?i,main_company_id=?i,company_id=?i,btype=1",$_SESSION['user_id'],$_SESSION['main_company'],$company->id);
              // insert company_rekvizit
              break;
            }
          }
        } // end insert new company
        //проверим привязана ли компания к пользователю
        if($company->id==0) {
          $company->kpp=(int)$pval['ПлательщикКПП'];
          $company->inn=(int)$pval['ПлательщикИНН'];
          $company->name=$pval['Плательщик'];
          $company->type=2;
          $company->btype=1;
          $company->save();
          if((int)$company->id>0) 
            $db->query("insert into user_companys set user_id=?i,main_company_id=?i,company_id=?i,btype=1",$_SESSION['user_id'],$_SESSION['main_company'],$company->id);
          else continue;
        }
        $is_user_company=$db->getOne("select company_id from user_companys where main_company_id=?i and company_id=?i and deleted=0",$_SESSION['main_company'],$company->id);
        if(empty($is_user_company) && (int)$is_user_company<1){
          //необходимо привязать клента к главной компании
          $db->query("insert into user_companys set user_id=?i,main_company_id=?i,company_id=?i,btype=1",$_SESSION['user_id'],$_SESSION['main_company'],$company->id);
        }
        $company_rekvizit_id=$db->getOne("select id from company_rekvizits where company_id=?i and rs=?s and ks=?s and deleted=0 and main_company=?i limit 1",$company->id,$platraschet,$pval['ПлательщикКорсчет'],$_SESSION['main_company']);
        if($company_rekvizit_id){
            //echo "Реквизиты непустые и они есть";
            $company_rekvizit=new CompanyRekvizit($company_rekvizit_id);
        }
        else {
          $company_rekvizit_id=$db->getOne("select id from company_rekvizits where company_id=?i and rs='' and ks='' and deleted=0 and main_company=?i limit 1",$company->id,$_SESSION['main_company']);
          if($company_rekvizit_id){ // у компании пустые реквизиты
            //echo "Пустые реквизиты но они есть";
            $company_rekvizit=new CompanyRekvizit($company_rekvizit_id);
            $company_rekvizit->company_id=$company->id;
            $company_rekvizit->main_company=$_SESSION['main_company'];
            $company_rekvizit->user_id=$_SESSION['user_id'];
            $company_rekvizit->rs=$platraschet;
            $company_rekvizit->ks=$pval['ПлательщикКорсчет'];
            $company_rekvizit->bik=$pval['ПлательщикБИК'];
            $company_rekvizit->bank=$pval['ПлательщикБанк1'];
            $company_rekvizit->save();
          }
          else {
            //echo "Реквизиты отсутствуют";
            $company_rekvizit=new CompanyRekvizit();
            $company_rekvizit->company_id=$company->id;
            $company_rekvizit->main_company=$_SESSION['main_company'];
            $company_rekvizit->user_id=$_SESSION['user_id'];
            //$company_rekvizit->rs=$pval['ПлательщикРасчСчет'];
            $company_rekvizit->rs=$platraschet;
            $company_rekvizit->ks=$pval['ПлательщикКорсчет'];
            $company_rekvizit->bik=$pval['ПлательщикБИК'];
            $company_rekvizit->bank=$pval['ПлательщикБанк1'];
            $company_rekvizit->save();
          }
        }
        $payment=new Payment();
        $payment->payment_type=4;
        $payment->company_id=$company->id;
        $payment->company_rekvizit_id=$company_rekvizit->id;
        $payment->main_company_id=$_SESSION['main_company'];
        $payment->from_rs=$platraschet;
        $payment->from_ks=$pval['ПлательщикКорсчет'];
        $payment->from_bank=$pval['ПлательщикБанк1'];
        $payment->from_inn=(int)$pval['ПлательщикИНН'];
        $payment->from_kpp=(int)$pval['ПлательщикКПП'];
        $payment->summ=(float)$pval['Сумма'];
        $payment->to_rs=$polraschet;
        $payment->to_ks=$pval['ПолучательКорсчет'];
        $payment->to_bank=$pval['ПолучательБанк1'];
        $payment->to_inn=(int)$pval['ПолучательИНН'];
        $payment->to_kpp=(int)$pval['ПолучательКПП'];
        $payment->payment_num=$pval['Номер'];
        $payment->create_date=date("Y-m-d",strtotime($pval['Дата']))." 01:00:00";
        $payment->payment_target=$pval['НазначениеПлатежа'];
        $payment->payment_direction=$payment_direction;
        $payment_saved[$pval['return_index']]=$payment->save();
      }
    }
    else { // payment_direction=2
      foreach($request->payments as $pkey=>$pval){
        if(isset($pval['ПлательщикРасчСчет'])) $platraschet=$pval['ПлательщикРасчСчет'];
        else $platraschet=$pval['ПлательщикСчет'];
        if(isset($pval['ПолучательРасчСчет'])) $polraschet=$pval['ПолучательРасчСчет'];
        else $polraschet=$pval['ПолучательСчет'];
        //file_put_contents("/var/log/shop/api/import_payments.log","pval:".print_r($pval,true)."\n",FILE_APPEND);
        //$company_id=$db->getOne("select id from company where inn=?i",(int)$pval['ПолучательИНН']);//,(int)$pval['ПолучательКПП']);
        
        if((int)$pval['ПолучательКПП']>0){
          $company_ids=$db->getCol("select id from company where inn=?i and kpp=?i",(int)$pval['ПолучательИНН'],(int)$pval['ПолучательКПП']);
          file_put_contents("/var/log/shop/api/import_payments.log","with kpp ".(int)$pval['ПолучательИНН'].",".(int)$pval['ПолучательКПП']." company_id:".print_r($company_ids,true)."\n",FILE_APPEND);
        }
        else {
          if((int)$pval['ПолучательИНН']==0){
            if(!empty($pval['Получатель']))
              $company_ids=$db->getCol("select id from company where name=?s",$pval['Получатель']);
            else{
              if(!empty($pval['Получатель1']))
                $company_ids=$db->getCol("select id from company where name=?s",$pval['Получатель1']);
            }
          }
          else {
            $company_ids=$db->getCol("select id from company where inn=?i",(int)$pval['ПолучательИНН']);
          }
        }
        if($company_ids && count((array)$company_ids)>0){
          if(count((array)$company_ids)>1){
            $my_contragents=$db->getCol("select company_id from user_companys where main_company_id=?i and company_id in (?b) and deleted=0",$_SESSION['main_company'],$company_ids);
            if($my_contragents){
              file_put_contents("/var/log/shop/api/import_payments.log","my_contragents:".print_r($my_contragents,true)."\n",FILE_APPEND);
              if(count((array)$my_contragents)>1){
                $company_id=$my_contragents[0];
              }
              else {
                $company_id=$my_contragents[0];
              }
            } 
            else {
              $company_id=$company_ids[0];
            }
          }
          else {
            $company_id=$company_ids[0];
          }
        }
        else {
          $company_id=false;
        }
        if($company_id){
          $company=new Company($company_id);
        }
        else {
          $company=new Company();
          $company_data=Companys::get_company_data_from_api(json_decode(json_encode(array("inn"=>$pval['ПолучательИНН']))));
          file_put_contents("/var/log/shop/api/import_payments.log","company_data:".print_r($company_data,true)."\n",FILE_APPEND);
          foreach($company_data['suggestions'] as $sugkey=>$sugval){
            $company->inn=$sugval['data']['inn'];
            if(isset($sugval['data']['kpp'])) $company->kpp=$sugval['data']['kpp'];
            else $company->kpp=0;
            //if($company->inn==(int)$pval['ПолучательИНН'] && (count($company_data['suggestions'])==1 || $company->kpp==(int)$pval['ПолучательКПП'])){
            if($company->inn==(int)$pval['ПолучательИНН'] && $surgkey==0){
              if($company->kpp==0 || $company->kpp=="" || $company->kpp!=(int)$pval['ПолучательКПП']) $company->kpp=(int)$pval['ПолучательКПП'];
              $company->name=$sugval['value'];
              $company->ogrn=$sugval['data']['ogrn'];
              $company->ruk=$sugval['data']['management']['name'];
              $company->rukdol=$sugval['data']['management']['post'];
              if(isset($sugval['data']['address']['value'])) {
                $company->address=$sugval['data']['address']['value'];
              }
              else {
                $company->address=$sugval['data']['address']['data']['source'];
              }
              switch($sugval['data']['opf']['short']){
                case "ИП": $company->type=1; break;
                case "ООО": $company->type=2; break;
              }
              $company->btype=2;//Продавец
              $company->save();
              $db->query("insert into user_companys set user_id=?i,main_company_id=?i,company_id=?i,btype=2",$_SESSION['user_id'],$_SESSION['main_company'],$company->id);
              // insert company_rekvizit
              break;
            }
          }
        } // end insert new company
        if($company->id==0) {
          $company->kpp=(int)$pval['ПолучательКПП'];
          $company->inn=(int)$pval['ПолучательИНН'];
          $company->name=$pval['Получатель'];
          $company->type=2;
          $company->btype=2;
          $company->save();
          if((int)$company->id>0) 
            $db->query("insert into user_companys set user_id=?i,main_company_id=?i,company_id=?i,btype=2",$_SESSION['user_id'],$_SESSION['main_company'],$company->id);
          else continue;
        }
        $is_user_company=$db->getOne("select company_id from user_companys where main_company_id=?i and company_id=?i and deleted=0",$_SESSION['main_company'],$company->id);
        if(empty($is_user_company) && (int)$is_user_company<1){
          //необходимо привязать клента к главной компании
          $db->query("insert into user_companys set user_id=?i,main_company_id=?i,company_id=?i,btype=2",$_SESSION['user_id'],$_SESSION['main_company'],$company->id);
        }
        $company_rekvizit_id=$db->getOne("select id from company_rekvizits where company_id=?i and rs=?s and ks=?s and deleted=0 and main_company=?i limit 1",$company->id,$polraschet,$pval['ПолучательКорсчет'],$_SESSION['main_company']);
          if($company_rekvizit_id){
              $company_rekvizit=new CompanyRekvizit($company_rekvizit_id);
          }
          else {
            $company_rekvizit_id=$db->getOne("select id from company_rekvizits where company_id=?i and rs='' and ks='' and deleted=0  and main_company=?i limit 1",$company->id,$_SESSION['main_company']);
            if($company_rekvizit_id){ // у компании пустые реквизиты
              $company_rekvizit=new CompanyRekvizit($company_rekvizit_id);
              $company_rekvizit->company_id=$company->id;
              $company_rekvizit->main_company=$_SESSION['main_company'];
              $company_rekvizit->user_id=$_SESSION['user_id']; 
              $company_rekvizit->rs=$polraschet;
              $company_rekvizit->ks=$pval['ПолучательКорсчет'];
              $company_rekvizit->bik=$pval['ПолучательБИК'];
              $company_rekvizit->bank=$pval['ПолучательБанк1'];
              $company_rekvizit->save();
            }
            else {
              $company_rekvizit=new CompanyRekvizit();
              $company_rekvizit->company_id=$company->id;
              $company_rekvizit->main_company=$_SESSION['main_company'];
              $company_rekvizit->user_id=$_SESSION['user_id'];
              $company_rekvizit->rs=$polraschet;
              $company_rekvizit->ks=$pval['ПолучательКорсчет'];
              $company_rekvizit->bik=$pval['ПолучательБИК'];
              $company_rekvizit->bank=$pval['ПолучательБанк1'];
              $company_rekvizit->save();
            }
        }
        $payment=new Payment();
        $payment->payment_type=4;
        $payment->company_id=$company->id;
        $payment->company_rekvizit_id=$company_rekvizit->id;
        $payment->main_company_id=$_SESSION['main_company'];
        $payment->from_rs=$platraschet;
        $payment->from_ks=$pval['ПлательщикКорсчет']; 
        $payment->from_bank=$pval['ПлательщикБанк1'];
        $payment->from_inn=$pval['ПлательщикИНН'];
        $payment->from_kpp=(int)$pval['ПлательщикКПП'];
        $payment->summ=$pval['Сумма'];
        $payment->to_rs=$polraschet;
        $payment->to_ks=$pval['ПолучательКорсчет'];
        $payment->to_bank=$pval['ПолучательБанк1'];
        $payment->to_inn=$pval['ПолучательИНН'];
        $payment->to_kpp=(int)$pval['ПолучательКПП'];
        $payment->payment_num=$pval['Номер'];
        $payment->create_date=date("Y-m-d",strtotime($pval['Дата']))." 01:00:00";
        $payment->payment_target=$pval['НазначениеПлатежа'];
        $payment->payment_direction=$payment_direction;
        $is_exist=$db->getOne("select id from payment where payment_num=?s and main_company_id=?i and create_date=?s and company_id=?i and payment_type=?i and deleted=0",
        $payment->payment_num,$_SESSION['main_company'],$payment->create_date,$payment->company_id,$payment->payment_type);
        if(!$is_exist)
          $payment_saved[$pval['return_index']]=$payment->save();
        else 
          $payment_saved[$pval['return_index']]=10;
      }
    }
    //if($payment)
    return array("status"=>"ok","msg"=>"","payments_saved"=>$payment_saved);
  }

  public static function get_payment_types($request){
    $db = DB::getInstance();
    $types=$db->getAll("select * from payment_types");
    return array("status"=>"ok","msg"=>"","err"=>"","payment_types"=>$types);
  }

  protected static function get_server_var($id) {
			return @$_SERVER[$id];
	}

  public static function do_sber_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    //echo "sess_company_id=".$_SESSION['company_id']." zakaz_c_id=".(int)$zakaz->company_id."\n";
    //if((int)$zakaz->company_id!=$_SESSION['company_id']) return self::_error_arr("Заказ не принадлежит указанной компании");
    //if((int)$payment->payment_direction==1) { // оплачиваем только заказы с оплатой от клиентов
      if((int)$zakaz->oplachen==1) return self::_error_arr("Данный заказ уже оплачен, выберите правильный заказ");
      //$zakaz->oplachen=1;
    //}
    $is_exist_register=$db->getOne("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='register.do'",$zakaz->id,$_SESSION['main_company']);
    if($is_exist_register){
      $bank_response=json_decode($is_exist_register);
      return array("status"=>"ok","msg"=>"","sber_response"=>$bank_response);
    }
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $acquiring_configs=$db->getRow("select acquiring_config,test,acquiring_operator_id from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    //if(!$acquiring_configs) 
      //return self::_error_arr("Нет данных по эквайрингу");
    switch((int)$acquiring_configs['acquiring_operator_id']){
      case 1: // sber
        $returnUrl="https://sort1.pro/sber/success_sber_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
        $failUrl="https://sort1.pro/sber/fail_sber_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
        //if((int)$acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/register.do";
        //else 
        if((int)$acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/register.do";
        else $register_url="https://securepayments.sberbank.ru/payment/rest/register.do";
        break;
      case 2://echo "tinkoff!!!!";
          return self::do_tinkoff_pay($request);
          break;
      case 3:// ukassa
        return self::do_ukassa_pay($request);
        break;
      case 4: // alfabank
        $returnUrl="https://sort1.pro/sber/success_alfa_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
        $failUrl="https://sort1.pro/sber/fail_alfa_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
        //if((int)$acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/register.do";
        //else 
        if((int)$acquiring_configs['test']==1) $register_url="https://alfa.rbsuat.com/payment/rest/register.do";
        else $register_url="https://payment.alfabank.ru/payment/rest/register.do";
        break;
    }
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['userName']) || empty($acquiring_data['password'])) 
      return self::_error_arr("Не заполнены поля авторизации");
    $sber_register_do=array();
    $sber_register_do['userName']=$acquiring_data['userName'];
    $sber_register_do['password']=$acquiring_data['password'];
    $sber_register_do['orderNumber']=(int)$request->zakaz_id;
    $sber_register_do['amount']=(float)$zakaz->zakaz_sum*100;
    $sber_register_do['sessionTimeoutSecs']=259200;
    $sber_register_do['returnUrl']=$returnUrl;
    $sber_register_do['failUrl']=$failUrl;
    $sber_register_do['description']="Оплата заказа №".$zakaz->id." в Интернет-магазине";
    $sber_register_do['clientId']=$_SESSION['user_id'];
    //if((int)$acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/register.do";
    //else $register_url="https://securepayments.sberbank.ru/payment/rest/register.do";
    
    //$send_str=json_encode($sber_register_do);
    $send_str="";
    foreach($sber_register_do as $key=>$val){
      $send_str.=urlencode($key)."=".urlencode($val)."&";
    }
    $send_str.="";
    $send_str_enc=urlencode($send_str);
    file_put_contents("/var/log/sort1/sber_pay.log","sber_url:".$register_url."\nsend_str:".$send_str,FILE_APPEND);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
     $response=json_decode($result);
     if(isset($response->orderId)) {
       $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"register.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
     }
    return array("status"=>"ok","msg"=>"","sber_response"=>$response);//,"full_resp"=>print_r($ret,true),"send"=>$send_str);
  }

  public static function cancel_sber_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $resp=$db->getRow("select bank_response,amount from bank_operations where zakaz_id=?i and bank_action=?s and paid=1",$zakaz->id,"register.do");
    $b_resp=json_decode($resp['bank_response']);
    $acquiring_configs=$db->getRow("select acquiring_config,test,acquiring_operator_id from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    //if(!$acquiring_configs) 
      //return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    switch($acquiring_configs['acquiring_operator_id']){
      case 2://echo "tinkoff!!!!";
        return self::cancel_tinkoff_pay($request);
        break;
      case 3:
        return self::cancel_ukassa_pay($request);
        break;
    }
    if(empty($acquiring_data['userName']) || empty($acquiring_data['password'])) 
      return self::_error_arr("Не заполнены поля авторизации1");
    $send=array();
    $send['userName']=$acquiring_data['userName'];
    $send['password']=$acquiring_data['password'];
    $send['merchantLogin']=$acquiring_data['userName'];
    $send['orderId']=$b_resp->orderId;
    //echo "b_resp=".print_r($resp,true);
    //$send['amount']=(float)$zakaz->zakaz_sum*100;
    if($acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/decline.do";
    else $register_url="https://securepayments.sberbank.ru/payment/rest/decline.do";
    //$send_str=json_encode($sber_register_do);

    $send_str="";
    foreach($send as $key=>$val){
      $send_str.=urlencode($key)."=".urlencode($val)."&";
    }
    $send_str.="";
    $send_str_enc=urlencode($send_str);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
      $response=json_decode($result);
      if(isset($response->errorCode) && (int)$response->errorCode==0) {
        $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"decline.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
      }
    return array("status"=>"ok","msg"=>"","sber_response"=>$response,"full_resp"=>print_r($ret,true),"send"=>$send_str);
  }

  public static function status_sber_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $resp=$db->getRow("select bank_response,amount from bank_operations where zakaz_id=?i and bank_action=?s",$zakaz->id,"register.do");
    $b_resp=json_decode($resp['bank_response']);
    $acquiring_configs=$db->getRow("select acquiring_config,test from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['userName']) || empty($acquiring_data['password'])) 
      return self::_error_arr("Нет заполнены поля авторизации");
    $send=array();
    $send['userName']=$acquiring_data['userName'];;
    $send['password']=$acquiring_data['password'];
    $send['orderId']=$b_resp->orderId;
    //echo "b_resp=".print_r($resp,true);
    //$send['amount']=(float)$zakaz->zakaz_sum*100;
    if($acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/getOrderStatusExtended.do";
    else $register_url="https://securepayments.sberbank.ru/payment/rest/getOrderStatusExtended.do";
    //$send_str=json_encode($sber_register_do);

    $send_str="";
    foreach($send as $key=>$val){
      $send_str.=urlencode($key)."=".urlencode($val)."&";
    }
    $send_str.="";
    $send_str_enc=urlencode($send_str);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
      $response=json_decode($result);
      if(isset($response->errorCode) && (int)$response->errorCode==0) {
        $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date) values (?i,?s,?s,?s,?s)",$zakaz->id,$zakaz->zakaz_sum,"getOrderStatusExtended.do",$result,date("Y-m-d H:i:s"));
      }
    return array("status"=>"ok","msg"=>"","sber_response"=>$response,"full_resp"=>print_r($ret,true),"send"=>$send_str);
  }

  public static function do_sber_refund_zakaz_detail($request){
    $db = DB::getInstance(); 
    if(!isset($request->zakaz_detail_id) || (int)$request->zakaz_detail_id<=0){
      return array("status"=>"err","err"=>"Не указан номер детали заказа");
    }
    $zakaz_detail=new ZakazDetail((int)$request->zakaz_detail_id);
    $zakaz=new Zakaz((int)$zakaz_detail->zakaz_id);
    //echo "sess_company_id=".$_SESSION['company_id']." zakaz_c_id=".(int)$zakaz->company_id."\n";
    //if((int)$zakaz->company_id!=$_SESSION['company_id']) return self::_error_arr("Заказ не принадлежит указанной компании");
    //if((int)$payment->payment_direction==1) { // оплачиваем только заказы с оплатой от клиентов
      if((int)$zakaz->oplachen!=1) return self::_error_arr("Данный заказ еще не оплачен, выберите правильный заказ");
      //$zakaz->oplachen=1;
    //}
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $acquiring_configs=$db->getRow("select acquiring_config,test,acquiring_operator_id from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    $acquiring_register=$db->getRow("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='register.do'",$zakaz->id,$_SESSION['main_company']);
    $acquiring_order_status=$db->getRow("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='getOrderStatusExtended.do' limit 1",$zakaz->id,$_SESSION['main_company']);
    if(!$acquiring_configs) 
      return array("status"=>"ok","err"=>""); //return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    switch((int)$acquiring_configs['acquiring_operator_id']){
      case 1:
        if(empty($acquiring_data['userName']) || empty($acquiring_data['password'])) 
        return self::_error_arr("Не заполнены поля авторизации");
        break;
      case 2:
        return self::cancel_tinkoff_pay((object)array("zakaz_id"=>$zakaz->id,"amount"=>(int)((float)$zakaz_detail->price*(float)$request->return_count)*100)); 
        //if(empty($acquiring_data['TerminalKey']) || empty($acquiring_data['SecretKey'])) 
        //return self::_error_arr("Не заполнены поля авторизации");
        break;
      case 3:
        return self::cancel_ukassa_pay((object)array("zakaz_id"=>$zakaz->id,"amount"=>(int)((float)$zakaz_detail->price*(float)$request->return_count)*100)); 
        //if(empty($acquiring_data['TerminalKey']) || empty($acquiring_data['SecretKey'])) 
        //return self::_error_arr("Не заполнены поля авторизации");
        break;
      default:  array("status"=>"ok","err"=>"");//return self::_error_arr("Нет данных по эквайрингу");
    }
    
    $register_data=json_decode($acquiring_register['bank_response'],true);
    $sber_refund_do=array();
    $sber_refund_do['userName']=$acquiring_data['userName'];
    $sber_refund_do['password']=$acquiring_data['password'];
    $sber_refund_do['orderId']=$register_data['orderId'];
    $sber_refund_do['amount']=(float)$zakaz_detail->price*(int)$request->return_count*100;
     //$sber_register_do['returnUrl']="https://sort1.pro/sber/success_sber_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
    //$sber_register_do['failUrl']="https://sort1.pro/sber/fail_sber_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
    //$sber_register_do['description']="Оплата заказа №".$zakaz->id." в Интернет-магазине";
    //$sber_register_do['clientId']=$_SESSION['user_id'];
    if((int)$acquiring_configs['test']==1) $refund_url="https://3dsec.sberbank.ru/payment/rest/refund.do";
    else $refund_url="https://securepayments.sberbank.ru/payment/rest/refund.do";
    
    //$send_str=json_encode($sber_register_do);
    $send_str="";
    foreach($sber_refund_do as $key=>$val){
      $send_str.=urlencode($key)."=".urlencode($val)."&";
    }
    $send_str.="";
    $send_str_enc=urlencode($send_str);
    file_put_contents("/var/log/sort1/sber_refund.log","sber_url:".$register_url."\nsend_str:".$send_str,FILE_APPEND);
    $ch = curl_init($refund_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
    $response=json_decode($result);
    if(isset($response->errorCode) && (int)$response->errorCode==0) {
      $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"refund.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
      return array("status"=>"ok","msg"=>"","sber_response"=>$response);//,"full_resp"=>print_r($ret,true),"send"=>$send_str);
    }
     else {
      return self::_error_arr("Ошибка при попытке возврата из банка, Ошибка: ".$result);
     }
    
  }

  public static function fiscalize_payment($request){
    if(isset($request->correction)) $correction=(int)$request->correction;
    else $correction=0;
    if(empty($request->payment_id)) return self::_error_arr("Не указан номер платежа");
    $db = DB::getInstance();
    $res=$db->getRow("select id,is_advance,zakaz_id from payment where main_company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->payment_id);
    if(!$res) return self::_error_arr("Не ваш платеж");
    //$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and ")
    $payment=new Payment((int)$request->payment_id);
    if((($payment->payment_type>=1 && $payment->payment_type<4) || $payment->payment_type==6 || $payment->payment_type==7) 
      && ($payment->payment_direction==1 || $payment->payment_direction==3 || $payment->payment_direction==4 || $payment->payment_direction==5)){
      //$zakaz_data=$db->getRow("select * from zakaz where id=?i and delivery_type=1",$this->zakaz_id);
      if(isset($_SESSION['my_sklad_id']) && (int)$_SESSION['my_sklad_id']>0){
          //$kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0 order by create_date limit 1",$_SESSION['my_sklad_id']);
          $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=?i",$_SESSION['my_sklad_id'],$_SESSION['user_id']);
          if(!$kassa_data){
              $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=?i",$_SESSION['my_sklad_id'],0);
          }
          //echo "my_sklad_id=".$_SESSION['my_sklad_id']."\n";
          switch((int)$kassa_data['ofd_operator_id']){
              case 1: 
                  $print_res=$payment->print_ofd_komtet_check();
                  //print_r($print_res);
                  //if($print_res['status']=="err") 
                      return array("status"=>"ok","print_res"=>$print_res);
                  //else 
                  //    return 1;
                  break;
              case 2:
                  $print_res=$payment->print_kkm_server_check($correction);
                  return array("status"=>"ok","msg"=>"","code"=>$print_res['code'],"check_data"=>$print_res['check_data'],"excise_check_data"=>$print_res['check_dataE'],"my_sklad_id"=>$_SESSION['my_sklad_id'],"pr"=>$print_res, "kassa_data"=>$kassa_data,"payment_date"=>$payment->create_date);
                  break;
              case 3: 
                $print_res=$payment->print_aqsi_check();
                $ret=OFDs::create_aqsi_order( 
                  (object)array(
                    "status"=>"ok",
                    "msg"=>"",
                    "code"=>$print_res['code'],
                    "aqsi_data"=>$print_res['check_data'], 
                    "excise_aqsi_data"=>$print_res['check_dataE'],
                    "my_sklad_id"=>$_SESSION['my_sklad_id'],
                    "pr"=>$print_res, 
                    "kassa_data"=>$kassa_data,
                    "payment_id"=>(int)$request->payment_id
                  )
                );
                //echo "aqsi recieve ".print_r($ret);
                $ret['print_res']=$print_res;
                //echo "aqsi recieve ".print_r($ret);
                return $ret;
                //return array("status"=>"ok","msg"=>"","code"=>$print_res['code'],"aqsi_data"=>$print_res['check_data'],"excise_aqsi_data"=>$print_res['check_dataE'],"my_sklad_id"=>$_SESSION['my_sklad_id'],"pr"=>$print_res, "kassa_data"=>$kassa_data);
                break;
              default: return array("status"=>"err","err"=>"Касса не определена");
          }
      }
      else {
          // Склад не определен и кассы нет
          return array("status"=>"err","err"=>"Касса не определена");
      }
    }
  }

  public static function save_fiscalized_payment($request){
    if(empty($request->payment_id)) return self::_error_arr("Не указан номер платежа");
    $db = DB::getInstance();
    $res=$db->getRow("select id,zakaz_id,payment_direction,is_advance,zakaz_detail_id,zakaz_job_id from payment where main_company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->payment_id);
    if(!$res) return self::_error_arr("Не ваш платеж");
    $payment=new Payment((int)$request->payment_id);
    if(isset($request->fiscalized) && (int)$request->fiscalized==1){
      $payment->fiscalized=1;
      $payment->Save();
      if((int)$res['zakaz_id']>0){
        if($res['payment_direction']==1 || $res['payment_direction']==2){
          if($res['is_advance']==0){
            $db->query("update zakaz_details set fiscalized=1 where zakaz_id=?i and is_excise=0 and fiscalized=0",(int)$res['zakaz_id']);
            $db->query("update zakaz_jobs set fiscalized=1 where zakaz_id=?i and fiscalized=0",(int)$res['zakaz_id']);
          }
        }
        else {
          $zakaz_detail_count=$db->getOne("select count from zakaz_details where id=?i",(int)$res['zakaz_detail_id']);
          if((int)$zakaz_detail_count==0){
            $db->query("update zakaz_details set return_fiscalized=1 where id=?i and is_excise=0 and fiscalized=0",(int)$res['zakaz_detail_id']);
          }
          $db->query("update zakaz_jobs set return_fiscalized=1 where id=?i and fiscalized=0",(int)$res['zakaz_job_id']);
        }
      }
      return array("status"=>"ok","msg"=>"");
    }
    else {
      if(isset($request->fiscalized_excise) && (int)$request->fiscalized_excise==1){
        $payment->fiscalized_excise=1;
        $payment->Save();
        if((int)$res['zakaz_id']>0){
          if($res['payment_direction']==1 || $res['payment_direction']==2){
            if($res['is_advance']==0){
              $db->query("update zakaz_details set fiscalized=1 where zakaz_id=?i and is_excise=1 and fiscalized=0",(int)$res['zakaz_id']);
              $db->query("update zakaz_jobs set fiscalized=1 where zakaz_id=?i and fiscalized=0",(int)$res['zakaz_id']);
            }
          }
          else {
            $zakaz_detail_count=$db->getOne("select count from zakaz_details where id=?i",(int)$res['zakaz_detail_id']);
            if((int)$zakaz_detail_count==0){
              $db->query("update zakaz_details set return_fiscalized=1 where id=?i and is_excise=1 and fiscalized=0",(int)$res['zakaz_detail_id']);
            }
            $db->query("update zakaz_jobs set return_fiscalized=1 where id=?i and fiscalized=0",(int)$res['zakaz_job_id']);
          }
        }
        return array("status"=>"ok","msg"=>"");
      }
      else{
        return self::_error_arr("Не удалось фискализировать платеж");
      }
    }
  }

  public static function check_payment($request){ // not
    $db = DB::getInstance();
    $ret=array();
    $ret['status']="ok";
    if(isset($request->zakaz_id) && (int)$request->zakaz_id>0){
      $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i",$request->zakaz_id);
      $excise_details=$db->getAll("select detail_id from sklad_details where detail_id in (?b) and sklad_id in (select sklad_id from zakaz where id=?i) and is_excise=1",
                      array_column("detail_id",$zakaz_details),$request->zakaz_id);
      if(count((array)$excise_details)>0){
        foreach($zakaz_details as $zd){
          if(in_array($zd['detail_id'],$excise_details))
            $ret['excise_details'][]=$zd;
        }
      }
      else $ret['excise_details']=array();
    }
    else {
      $ret['excise_details']=array();
    }
    return $ret;
  }

  public static function do_tinkoff_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    //echo "sess_company_id=".$_SESSION['company_id']." zakaz_c_id=".(int)$zakaz->company_id."\n";
    //if((int)$zakaz->company_id!=$_SESSION['company_id']) return self::_error_arr("Заказ не принадлежит указанной компании");
    //if((int)$payment->payment_direction==1) { // оплачиваем только заказы с оплатой от клиентов
      if((int)$zakaz->oplachen==1) return self::_error_arr("Данный заказ уже оплачен, выберите правильный заказ");
      //$zakaz->oplachen=1;
    //}
    $is_exist_register=$db->getOne("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='init'",$zakaz->id,$_SESSION['main_company']);
    if($is_exist_register){
      $bank_response=json_decode($is_exist_register);
      return array("status"=>"ok","msg"=>"","tinkoff_response"=>$bank_response);
    }
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $acquiring_configs=$db->getRow("select acquiring_config,test from acquiring_config where company_id=?i and active=1 and acquiring_operator_id=2",$_SESSION['main_company']);
    
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['TerminalKey']) || empty($acquiring_data['SecretKey'])) 
      return self::_error_arr("Не заполнены поля авторизации");
    $api = new TinkoffMerchantAPI(
      $acquiring_data['TerminalKey'],  //Ваш Terminal_Key
      $acquiring_data['SecretKey']   //Ваш Secret_Key
    );
    $paydata=array();
    $paydata['TerminalKey']=$acquiring_data['TerminalKey'];
    $paydata['OrderId']=(int)$request->zakaz_id;
    $paydata['Amount']=(int)$zakaz->zakaz_sum*100;
    $paydata['SuccessURL']="https://sort1.pro/sber/success_tinkoff_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
    $paydata['FailURL']="https://sort1.pro/sber/success_tinkoff_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id;
    $paydata['Description']="Оплата заказа №".$zakaz->id." в Интернет-магазине";
    
    $result=$api->init($paydata);
     $response=json_decode($result);
     if(isset($response->PaymentId)) {
       $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"init",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
     }
    return array("status"=>"ok","msg"=>"","tinkoff_response"=>$response);//,"full_resp"=>print_r($ret,true),"send"=>$send_str);
  }

  public static function cancel_tinkoff_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $resp=$db->getRow("select bank_response,amount from bank_operations where zakaz_id=?i and bank_action=?s and paid=1",$zakaz->id,"init");
    if(!$resp){
      return self::_error_arr("Нет данных по оплате");
    }
    $b_resp=json_decode($resp['bank_response']);
    $acquiring_configs=$db->getRow("select acquiring_config,test from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['TerminalKey']) || empty($acquiring_data['SecretKey'])) 
      return self::_error_arr("Не заполнены поля авторизации2");

    $send=array();
    $api = new TinkoffMerchantAPI(
      $acquiring_data['TerminalKey'],  //Ваш Terminal_Key
      $acquiring_data['SecretKey']   //Ваш Secret_Key
    );
    $send['PaymentId']=$b_resp->PaymentId;
    if(isset($request->amount) && (int)$request->amount>0)
      $send['Amount']=(int)$request->amount;
    $result=$api->Cancel($send);
    $response=json_decode($result);
      if(isset($response->Status) && (int)$response->Status=="REFUNDED") {
        $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"Cancel",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
      }
    return array("status"=>"ok","msg"=>"","tinkoff_response"=>$response,"full_resp"=>print_r($result,true),"send"=>$send);
  }

  public static function return_payment($request){
    if(!isset($request->payment_id) || (int)$request->payment_id<=0){
      return self::_error_arr("Не указан номер оплаты для возврата");
    }
    $db = DB::getInstance();
    $payment=new Payment((int)$request->payment_id);
    if((int)$payment->main_company_id!=(int)$_SESSION['main_company']){
      return self::_error_arr("Не ваш платеж");
    }
    $req=array(
      "company_id"=>$payment->company_id,
      "from_inn"=>$payment->from_inn,
      "from_kpp"=>$payment->from_kpp,
      "payment_direction"=>3,
      "payment_type"=>$payment->payment_type,
      "payment_target"=>"Возврат оплаты №".$payment->id,
      "summ"=>(float)$payment->summ,
      "zakaz_id"=>$payment->zakaz_id,
      "zakaz_detail_id"=>$payment->zakaz_detail_id,
      "is_advance"=>$payment->is_advance,
    ); 
    $return_payment=self::save_payment((object)$req);
    if($return_payment['status']=="err") return self::_error_arr($return_payment['err']);
    else {
      if((int)$payment->zakaz_id>0){
        $zakaz=new Zakaz ((int)$payment->zakaz_id);
        $zakaz->oplachen=0;
        $zakaz->save();
      }
      $return_payment['send_data']=$req;
      return $return_payment;
    }
  }

  public static function do_sber_pay_market($request){
    if(isset($request->zakaz_id)) $zakaz_id = $request->zakaz_id;
    else return self::_error_arr("Не указан заказ");
    $db = DB::getInstance();
    $zakaz_company = $db->getOne("select main_company_id from zakaz where id=?i",$zakaz_id);
    $_SESSION['main_company'] = $zakaz_company;

    $res = self::do_sber_pay((object)array("zakaz_id"=>$zakaz_id));

    if($res['status'] = 'ok'){
      $_SESSION['main_company'] = 35;
      return $res;
    }
  }

  public static function do_ukassa_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    //echo "sess_company_id=".$_SESSION['company_id']." zakaz_c_id=".(int)$zakaz->company_id."\n";
    //if((int)$zakaz->company_id!=$_SESSION['company_id']) return self::_error_arr("Заказ не принадлежит указанной компании");
    //if((int)$payment->payment_direction==1) { // оплачиваем только заказы с оплатой от клиентов
      if((int)$zakaz->oplachen==1) return self::_error_arr("Данный заказ уже оплачен, выберите правильный заказ");
      //$zakaz->oplachen=1;
    //}
    $is_exist_register=$db->getOne("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='register.do'",$zakaz->id,$_SESSION['main_company']);
    if($is_exist_register){
      $bank_response=json_decode($is_exist_register);
      return array("status"=>"ok","msg"=>"","ukassa_response"=>$bank_response);
    }
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $acquiring_configs=$db->getRow("select acquiring_config,test,acquiring_operator_id from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['shop_id']) || empty($acquiring_data['SecretKey'])) 
      return self::_error_arr("Не заполнены поля авторизации");
    $ukassa_register_do=array();
    $username=$acquiring_data['shop_id'];
    $password=$acquiring_data['SecretKey'];
    $ikey=md5((float)$zakaz->zakaz_sum."_".$zakaz->id."_".date("Y-m-d H:i"));
    //$ukassa_register_do['orderNumber']=(int)$request->zakaz_id;
    $ukassa_register_do['amount']=array("value"=>(float)$zakaz->zakaz_sum,"currency"=>"RUB");
    $ukassa_register_do['capture']=true;
    $ukassa_register_do['confirmation']=array('type'=>'redirect','return_url'=>"https://nur.sort1.pro/ukassa/success_ukassa_pay.php?maincid=".$_SESSION['main_company']."&zakaz_id=".$zakaz->id);
    $ukassa_register_do['description']="Оплата заказа №".$zakaz->id." в Интернет-магазине";
    if((int)$acquiring_configs['test']==1) $register_url="https://api.yookassa.ru/v3/payments";
    else $register_url="https://api.yookassa.ru/v3/payments";
    
    //$send_str=json_encode($sber_register_do);
    $send_str=json_encode($ukassa_register_do);
    file_put_contents("/var/log/sort1/ukassa_pay.log","sber_url:".$register_url."\nsend_str:".$send_str,FILE_APPEND);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Idempotence-Key: '.$ikey));
      
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
     $response=json_decode($result);
     if(isset($response->id)) {
       $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"ukassa_register.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
     }
    return array("status"=>"ok","msg"=>"","ukassa_response"=>$response,"full_resp"=>print_r($ret,true),"send"=>$send_str);
  }

  public static function cancel_ukassa_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $resp=$db->getRow("select id,bank_response,amount from bank_operations where zakaz_id=?i and bank_action=?s and paid=1 and returned_pay=0 order by create_date desc limit 1",$zakaz->id,"ukassa_register.do");
    if(!$resp){
      return self::_error_arr("Немогу найти оплату или оплата уже возвращена");
    }
    $b_resp=json_decode($resp['bank_response']);
    $acquiring_configs=$db->getRow("select acquiring_config,test,acquiring_operator_id from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['shop_id']) || empty($acquiring_data['SecretKey'])) 
      return self::_error_arr("Не заполнены поля авторизации");
    $ukassa_register_refund=array();
    $username=$acquiring_data['shop_id'];
    $password=$acquiring_data['SecretKey'];
    $ikey=md5((float)$zakaz->zakaz_sum."_".$zakaz->id."_".date("Y-m-d H:i"));
    //$ukassa_register_do['orderNumber']=(int)$request->zakaz_id;
    $ukassa_register_refund['amount']=array("value"=>(isset($request->amount)?$request->amount/100:$b_resp->amount->value),"currency"=>"RUB");
    $ukassa_register_refund['payment_id']=$b_resp->id;
    if((int)$acquiring_configs['test']==1) $register_url="https://api.yookassa.ru/v3/refunds";
    else $register_url="https://api.yookassa.ru/v3/refunds";
    
    //$send_str=json_encode($sber_register_do);
    $send_str=json_encode($ukassa_register_refund);
    file_put_contents("/var/log/sort1/ukassa_pay.log","url:".$register_url."\nsend_str:".$send_str,FILE_APPEND);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Idempotence-Key: '.$ikey));
      
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
     $response=json_decode($result);
      if(isset($response->type) && $response->type=="error") {
        $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"ukassa_decline.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
      }
      else {
        /*$payment_send=array(
          "zakaz_id"=>$zakaz->id,
          "company_id"=>$zakaz->company_id,
          "payment_type"=>6,
          "summ"=>$zakaz->zakaz_sum,
          "payment_target"=>"возврат оплаты заказа №".$zakaz->id." банковской картой через эквайринг",
          "payment_direction"=>4,
          "payment_num"=>$response->items[0]->id,
  
        );
        $zakaz=new Zakaz((int)$zakaz->id); // могло изменится состояние оплаты
        if((int)$zakaz->oplachen==1){
          //$payment_res=Payments::save_payment((object)$payment_send);
        }
        if($payment_res['status']="ok"){
          //$zakaz->oplachen=0;
          //$zakaz->save();*/
          $db->query("update bank_operations set returned_pay=1 where id=?i",$resp['id']);
          $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"ukassa_refund.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
       /* }
        else 
          echo "Ошибка при возврате оплаты заказа: ".$payment_res['err']."\n"; */
      }
    return array("status"=>"ok","msg"=>"","ukassa_response"=>$response,"full_resp"=>$ret,"send"=>$send_str);
  }

  public static function status_ukassa_pay($request){
    $db = DB::getInstance();
    if(!isset($request->zakaz_id) || (int)$request->zakaz_id<=0){
      return array("status"=>"err","err"=>"Не указан номер заказа");
    }
    $zakaz=new Zakaz((int)$request->zakaz_id);
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $resp=$db->getRow("select bank_response,amount from bank_operations where zakaz_id=?i and bank_action=?s",$zakaz->id,"register.do");
    $b_resp=json_decode($resp['bank_response']);
    $acquiring_configs=$db->getRow("select acquiring_config,test from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    $acquiring_data=json_decode($acquiring_configs['acquiring_config'],true);
    if(empty($acquiring_data['userName']) || empty($acquiring_data['password'])) 
      return self::_error_arr("Нет заполнены поля авторизации");
    $send=array();
    $send['userName']=$acquiring_data['userName'];;
    $send['password']=$acquiring_data['password'];
    $send['orderId']=$b_resp->orderId;
    //echo "b_resp=".print_r($resp,true);
    //$send['amount']=(float)$zakaz->zakaz_sum*100;
    if($acquiring_configs['test']==1) $register_url="https://3dsec.sberbank.ru/payment/rest/getOrderStatusExtended.do";
    else $register_url="https://securepayments.sberbank.ru/payment/rest/getOrderStatusExtended.do";
    //$send_str=json_encode($sber_register_do);

    $send_str="";
    foreach($send as $key=>$val){
      $send_str.=urlencode($key)."=".urlencode($val)."&";
    }
    $send_str.="";
    $send_str_enc=urlencode($send_str);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
      $response=json_decode($result);
      if(isset($response->errorCode) && (int)$response->errorCode==0) {
        $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date) values (?i,?s,?s,?s,?s)",$zakaz->id,$zakaz->zakaz_sum,"getOrderStatusExtended.do",$result,date("Y-m-d H:i:s"));
      }
    return array("status"=>"ok","msg"=>"","sber_response"=>$response,"full_resp"=>print_r($ret,true),"send"=>$send_str);
  }

  public static function do_ukassa_refund_zakaz_detail($request){
    $db = DB::getInstance(); 
    if(!isset($request->zakaz_detail_id) || (int)$request->zakaz_detail_id<=0){
      return array("status"=>"err","err"=>"Не указан номер детали заказа");
    }
    $zakaz_detail=new ZakazDetail((int)$request->zakaz_detail_id);
    $zakaz=new Zakaz((int)$zakaz_detail->zakaz_id);
    //echo "sess_company_id=".$_SESSION['company_id']." zakaz_c_id=".(int)$zakaz->company_id."\n";
    //if((int)$zakaz->company_id!=$_SESSION['company_id']) return self::_error_arr("Заказ не принадлежит указанной компании");
    //if((int)$payment->payment_direction==1) { // оплачиваем только заказы с оплатой от клиентов
      if((int)$zakaz->oplachen!=1) return self::_error_arr("Данный заказ еще не оплачен, выберите правильный заказ");
      //$zakaz->oplachen=1;
    //}
    $companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    if (!$companys || !in_array($zakaz->company_id,$companys)){
      return self::_error_arr("Неправильно оформлен заказ");
    }
    $acquiring_configs=$db->getRow("select acquiring_config,test,acquiring_operator_id from acquiring_config where company_id=?i and active=1",$_SESSION['main_company']);
    $acquiring_register=$db->getRow("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='ukassa_register.do'",$zakaz->id,$_SESSION['main_company']);
    $acquiring_order_status=$db->getRow("select bank_response from bank_operations where zakaz_id=?i and main_company_id=?i and bank_action='getOrderStatusExtended.do' limit 1",$zakaz->id,$_SESSION['main_company']);
    if(!$acquiring_configs) 
      return self::_error_arr("Нет данных по эквайрингу");
    if(empty($acquiring_configs['shop_id']) || empty($acquiring_configs['SecretKey'])) 
      return self::_error_arr("Не заполнены поля авторизации");
    $ukassa_register_refund=array();
    if($acquiring_register){
      $acquiring_register_decoded=json_decode($acquiring_register['bank_response']);
    }
    $username=$acquiring_configs['shop_id'];
    $password=$acquiring_configs['SecretKey'];
    $ikey=md5((float)$zakaz->zakaz_sum."_".$zakaz->id."_".date("Y-m-d"));
    //$ukassa_register_do['orderNumber']=(int)$request->zakaz_id;
    $ukassa_register_refund['amount']=array("value"=>(float)$zakaz_detail->price*(int)$request->return_count,"currency"=>"RUB");
    $ukassa_register_refund['payment_id']=$acquiring_register_decoded->id;
    if((int)$acquiring_configs['test']==1) $register_url="https://api.yookassa.ru/v3/refunds";
    else $register_url="https://api.yookassa.ru/v3/refunds";
    
    //$send_str=json_encode($sber_register_do);
    $send_str=json_encode($ukassa_register_refund);
    file_put_contents("/var/log/sort1/ukassa_pay.log","sber_url:".$register_url."\nsend_str:".$send_str,FILE_APPEND);
    $ch = curl_init($register_url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_str);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Idempotence-Key: '.$ikey));
      
      //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    	$result = curl_exec($ch);
      $info = curl_getinfo($ch); 
      $ret=array(
        'header' => substr($result,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($result,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => curl_errno($ch),
        'err' => curl_error($ch),
        'http_code' => $info['http_code'],
        'x' => $x
      );
     curl_close($ch);
     $response=json_decode($result);
     if(isset($response->id)) {
       $db->query("insert into bank_operations (zakaz_id,amount,bank_action,bank_response,create_date,user_id,main_company_id) values (?i,?s,?s,?s,?s,?i,?i)",$zakaz->id,$zakaz->zakaz_sum,"ukassa_refund.do",$result,date("Y-m-d H:i:s"),$_SESSION['user_id'],$_SESSION['main_company']);
     }
    return array("status"=>"ok","msg"=>"","ukassa_response"=>$response,"full_resp"=>print_r($ret,true),"send"=>$send_str);
    
  }
}



?>
