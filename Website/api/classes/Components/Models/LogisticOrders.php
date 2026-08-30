<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\LogisticOrder;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class LogisticOrders extends Model {

	public static function save_logistic_order($request) {
	    $fields="";
		$db = DB::getInstance();
	    if (isset($request->logistic_order_id) && (int)$request->logistic_order_id>0) {$logistic_order_id=(int)$request->logistic_order_id;}
	    if(isset($logistic_order_id) && $logistic_order_id>0) {
				$logistic_order=new LogisticOrder($logistic_order_id);
	    }
	    else {
				$logistic_order=new LogisticOrder();
		}
		if($logistic_order->status==40){
			return array("status"=>"err","err"=>"Нельзя менять выполненную заявку");
		} 
	    if (isset($request->from_company_id)) { $logistic_order->from_company_id=(int)$request->from_company_id; }
		if (isset($request->from_sklad_id) && (int)$request->from_sklad_id>0) { $logistic_order->from_sklad_id=$request->from_sklad_id; }
	    if (isset($request->from_address)) {$logistic_order->from_address=$request->from_address;}
	    if (isset($request->to_company_id)) {$logistic_order->to_company_id=$request->to_company_id;}
	    if (isset($request->to_company_address)) {$logistic_order->to_company_address=$request->to_company_address;}
	    if (isset($request->to_sklad_id)) {$logistic_order->to_sklad_id=$request->to_sklad_id;}
		if (isset($request->logistic_car_id)) {$logistic_order->logistic_car_id=$request->logistic_car_id;}
		if (isset($request->logistic_driver_id)) {$logistic_order->logistic_driver_id=$request->logistic_driver_id;}
		if (isset($request->logistic_company_id)) {$logistic_order->logistic_company_id=$request->logistic_company_id;}
	    if (isset($request->comment)) {$logistic_order->comment=$request->comment; }
		if (isset($request->status)) {$logistic_order->status=$request->status; }
		if(!isset($request->logistic_order_type)) $logistic_order->logistic_order_type=1;
		else $logistic_order->logistic_order_type=$request->logistic_order_type;
		if($logistic_order->from_sklad_id==$logistic_order->to_sklad_id && $logistic_order->logistic_order_type!=2){
			return array("status"=>"err","err"=>"Нельзя перемещать детали на тот же склад");
		}
	    //print_r($_GET);
	    //echo $company->kpp;
	    $logistic_order_saved=$logistic_order->save();
		    if($logistic_order_saved['status']>=1){
					$ret['status']="ok";
					$ret['msg']="";
					if((int)$request->logistic_order_id==0)
						$ret['logistic_order_id']=$logistic_order->id;
		    }
		    else {
					$ret['status']="err";
					$ret['err']=$logistic_order_saved['msg'];
					$ret['ret']=$logistic_order_saved;
		    }
	    if ($ret['status']=="err") return $ret;
	    else return $ret;
	} // action_save_company


	public static function delete_logistic_order($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
		}
		
	    if (isset($request->logistic_order_id) && (int)$request->logistic_order_id>0){
			$logistic_order=$db->getRow("select * from logistic_orders where id=?i and main_company_id=?i",(int)$request->logistic_order_id,(int)$_SESSION['main_company']);
			if($logistic_order['status']!=1 && $logistic_order['status']!=10){
				return self::_error_arr("Эту заявку нельзя удалить, она находится в работе");
			}
			if((int)$logistic_order['id']>0){
				$res2=$db->query("delete from logistic_orders where id=?i and main_company_id=?i",(int)$logistic_order['id'],(int)$_SESSION['main_company']);
				$res3=$db->query("delete from logistic_order_details where logistic_order_id=?i",(int)$logistic_order['id']);
				if ($res2 && $res3){
					$ret['status']="ok";
					$ret['msg']="";
				}
				else {
					$ret['status']="err";
					$ret['err']="не удалось удалить заявку";
				}
			}
			else {
				return self::_error_arr("Заявка не существует");
			}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_logistic_orders($request) {
		$db = DB::getInstance();
		$filter="";
		if(!empty($request->search_logistic_order_date_from)){
			//if(empty($filter)) $filter.=$db->parse(" create_date>=?s",$request->search_logistic_order_date_from);
			//else 
			$filter.=$db->parse(" and create_date>=?s",$request->search_logistic_order_date_from);
		}
		if(!empty($request->search_logistic_order_date_to)){
			//echo $request->search_logistic_order_date_to;
			//if(empty($filter)) $filter.=$db->parse(" create_date<=?s",$request->search_logistic_order_date_to);
			//else 
			$filter.=$db->parse(" and create_date<=?s",$request->search_logistic_order_date_to);
			//echo $filter;
		}
		if(!empty($request->search_logistic_order_client_name)){
			$sql_cl="select id from company where (id in (select distinct(company_id) from user_companys where main_company_id=?i and (btype=1 or btype=0 or btype=3)) or id=?i) and name like ?s";
			$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_logistic_order_client_name.'%');
			if($res_cl){
				$search_companys=array_column($res_cl,"id");
				$filter.=$db->parse(" and to_company_id in (?b)",$search_companys);
			}
			else {
				// поисковая строка не пустая а компаний нет
				
			}
			$ret['search_zakaz_client_name']=$request->search_zakaz_client_name;
		}
	    $sql="select * from logistic_orders where main_company_id=?i ?p order by create_date desc";
	    $res=$db->getAll($sql,$_SESSION['main_company'],$filter);
		$sklads=array();
		$delivery_addresses=array();
		$companys=array();
		$cars=array();
		$drivers=array();
		foreach($res as $res_key=>$res_data){
			if($res_data['from_company_id']>0) $companys[]=$res_data['from_company_id'];
			if($res_data['to_company_id']>0) $companys[]=$res_data['to_company_id'];
			if($res_data['logistic_company_id']>0) $companys[]=$res_data['logistic_company_id'];
			if($res_data['from_sklad_id']>0) $sklads[]=$res_data['from_sklad_id'];
			if($res_data['to_sklad_id']>0 && ($res_data['logistic_order_type']==1 || $res_data['logistic_order_type']==3)) $sklads[]=$res_data['to_sklad_id'];
			if($res_data['to_sklad_id']>0 && $res_data['logistic_order_type']==2) $delivery_addresses[]=$res_data['to_sklad_id'];
			if($res_data['logistic_car_id']>0) $cars[]=$res_data['logistic_car_id'];
			if($res_data['logistic_driver_id']>0) $drivers[]=$res_data['logistic_driver_id'];
			//if($res_data['']>0) $sklads[]=$res_data['to_sklad_id'];
		}
		$ret_cars=$db->getInd("id","select * from logistic_cars where id in (?b)",array_unique($cars));
		$ret_drivers=$db->getInd("id","select * from logistic_drivers where id in (?b)",array_unique($drivers));
		$ret_companys=$db->getInd("id","select * from company where id in (?b)",array_unique($companys));
		$ret_sklads=$db->getInd("id","select * from sklad where id in (?b)",array_unique($sklads));
		$ret_delivery_addresses=$db->getInd("id","select * from delivery_address where id in (?b)",array_unique($delivery_addresses));
		$ret_statuses=$db->getInd("id","select * from logistic_order_status");
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['logistic_orders']=$res;
			$ret['cars']=$ret_cars;
			$ret['drivers']=$ret_drivers;
			$ret['companys']=$ret_companys;
			$ret['sklads']=$ret_sklads;
			$ret['delivery_addresses']=$ret_delivery_addresses;
			$ret['statuses']=$ret_statuses;
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['logistic_orders']=[];
			$ret['cars']=$ret_cars;
			$ret['drivers']=$ret_drivers;
			$ret['companys']=$ret_companys;
			$ret['sklads']=$ret_sklads;
			$ret['delivery_addresses']="";
			$ret['statuses']=$ret_statuses;
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_logistic_order($request) {
	    $db = DB::getInstance();
		if(empty($request->logistic_order_id) || (int)$request->logistic_order_id==0){
			$ret_statuses=$db->getInd("id","select * from logistic_order_status");
			$my_company=$db->getRow("select id,name from company where id=?i",$_SESSION['main_company']);
			$my_sklads=$db->getAll("select id,descr,address,name from sklad where company_id=?i and deleted=0",$_SESSION['main_company']);
			return array("status"=>"ok","msg"=>'',"statuses"=>$ret_statuses,"my_company"=>$my_company,"my_sklads"=>$my_sklads);
		}	
	    $sql="select * from logistic_orders where id=?i and main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->logistic_order_id,$_SESSION['main_company']);
		$sklads=array();
		$companys=array();
		$cars=array();
		$drivers=array();
		$delivery_addresses=array();
		if($res['from_company_id']>0) $companys[]=$res['from_company_id'];
		if($res['to_company_id']>0) $companys[]=$res['to_company_id'];
		if($res['logistic_company_id']>0) $companys[]=$res['logistic_company_id'];
		if($res['from_sklad_id']>0) $sklads[]=$res['from_sklad_id'];
		if($res['to_sklad_id']>0 && ($res['logistic_order_type']==1 || $res['logistic_order_type']==3)) $sklads[]=$res['to_sklad_id'];
		if($res['to_sklad_id']>0 && $res['logistic_order_type']==2) $delivery_addresses[]=$res['to_sklad_id'];
		if($res['logistic_car_id']>0) $cars[]=$res['logistic_car_id'];
		if($res['logistic_driver_id']>0) $drivers[]=$res['logistic_driver_id'];
		$ret_cars=$db->getInd("id","select * from logistic_cars where id in (?b)",$cars);
		$ret_drivers=$db->getInd("id","select * from logistic_drivers where id in (?b)",$drivers);
		$ret_companys=$db->getInd("id","select * from company where id in (?b)",$companys);
		$ret_sklads=$db->getInd("id","select * from sklad where id in (?b)",$sklads);
		$ret_delivery_addresses=$db->getInd("id","select * from delivery_address where id in (?b)",$delivery_addresses);
		$ret_statuses=$db->getInd("id","select * from logistic_order_status");
	    if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['logistic_order']=$res;
			$ret['order_statuses']=$db->getInd("id","select * from logistic_order_status");
			$ret['cars']=$ret_cars;
			$ret['drivers']=$ret_drivers;
			$ret['companys']=$ret_companys;
			$ret['sklads']=$ret_sklads;
			$ret['delivery_addresses']=$ret_delivery_addresses;
			$ret['statuses']=$ret_statuses;
			$ret['msg']="";
			//$ret['cars_logistic_companys']=$cars_logistic_companys;
			if(count((array)$cars_logistic_companys)>0) {

			}
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_order']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_auto_makers($request){
		$db = DB::getInstance();
		$auto_makers=$db->getAll("select * from auto_makers order by name");
		$ret['auto_makers']=$auto_makers;
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		return $ret;
	}

	public static function get_logistic_companys($request){
		$db = DB::getInstance();

	}
}



?>
