<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\LogisticOrderDetail;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class LogisticOrderDetails extends Model {

	public static function save_logistic_order_detail($request) {
	    $fields="";
	    $db = DB::getInstance(); 
	    if (isset($request->logistic_order_detail_id) && (int)$request->logistic_order_detail_id>0) {$logistic_order_detail_id=(int)$request->logistic_order_detail_id;}
	    if(isset($logistic_order_detail_id) && $logistic_order_detail_id>0) {
				$logistic_order_detail=new LogisticOrderDetail($logistic_order_detail_id);
	    }
	    else {
				$logistic_order_detail=new LogisticOrderDetail();
		}
		$logistic_order=$db->getRow("select * from logistic_orders where id=?i",(int)$request->logistic_order_id);
		if($logistic_order['status']==1 || $logistic_order['status']==50){
			if (isset($request->logistic_order_id)) { $logistic_order_detail->logistic_order_id=(int)$request->logistic_order_id; }
			if (isset($request->zakaz_detail_id) && (int)$request->zakaz_detail_id>0) { $logistic_order_detail->zakaz_detail_id=$request->zakaz_detail_id; }
			if (isset($request->zakaz_id) && (int)$request->zakaz_id>0) { $logistic_order_detail->zakaz_id=$request->zakaz_id; }
			if (isset($request->status)) {$logistic_order_detail->status=$request->status;}
			if (isset($request->detail_id)) {$logistic_order_detail->detail_id=$request->detail_id;}
			if (isset($request->count)) {$logistic_order_detail->count=$request->count;}
			//print_r($_GET);
			//echo $company->kpp;
			$logistic_order_detail_saved=$logistic_order_detail->save();
			if($logistic_order_detail_saved){
				$ret['status']="ok";
				$ret['msg']="";//"Данные успешно изменены";
			}
			else {
				$ret['status']="err";
				$ret['err']="";//"Данные не менялись";
			}
		}
		else {
			$ret['status']="err";
			$ret['err']="Нельзя добавлять детали в принятой или выполненной заявке";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	} // action_save_company


	public static function delete_logistic_order_detail($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
		}
	    if (isset($request->logistic_order_detail_id) && (int)$request->logistic_order_detail_id>0){
			$logistic_order=$db->getRow("select * from logistic_orders where id=(select logistic_order_id from logistic_order_details where id=?i)",(int)$request->logistic_order_detail_id);
			if($logistic_order['status']==1 || $logistic_order['status']==10 || $logistic_order['status']==50){
				$res2=$db->query("delete from logistic_order_details where id=?i",(int)$request->logistic_order_detail_id);
				if ($res2){
					$ret['status']="ok";
					$ret['msg']="Деталь успешно удалена из заявки";
				}
				else {
					$ret['status']="err";
					$ret['err']="не удалось удалить деталь из заявки";
				}
			}
			else {
				$ret['status']="err";
				$ret['err']="Нельзя удалить детали из принятой или выполненной заявки";
			}
	    }
	    else {
			$ret['status']="err";
			$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_logistic_order_details($request) {
		$db = DB::getInstance();
		$logistic_order_type=$db->getRow("select logistic_order_type,from_sklad_id,to_sklad_id from logistic_orders where id=?i",$request->logistic_order_id);
		if($logistic_order_type['logistic_order_type']==1 || $logistic_order_type['logistic_order_type']==2 || $logistic_order_type['logistic_order_type']==4){
			$sql="select l.*,zd.article,zd.brand,zd.count,zd.name from logistic_order_details l left join zakaz_details zd on (zd.id=l.zakaz_detail_id) where l.logistic_order_id=?i";
			$res=$db->getAll($sql,$request->logistic_order_id);
		}
		else {
			$sql="select l.*,sd.article,sd.brand,sd.name from logistic_order_details l left join sklad_details sd on (sd.sklad_id=?i and sd.detail_id=l.detail_id) where l.logistic_order_id=?i";
			$res=$db->getAll($sql,$logistic_order_type['from_sklad_id'],$request->logistic_order_id);
		}
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_order_details']=$res;
				$ret['msg']="";
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_order_details']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_logistic_order_detail($request) {
	    $db = DB::getInstance();
			if(empty($request->logistic_order_id) || (int)$request->logistic_order_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select * from logistic_orders where id=?i and main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->logistic_order_id,$_SESSION['main_company']);
		$sklads=array();
		$companys=array();
		$cars=array();
		if($res['from_company_id']>0) $companys[]=$res['from_company_id'];
		if($res['to_company_id']>0) $companys[]=$res['to_company_id'];
		if($res['from_sklad_id']>0) $sklads[]=$res['from_sklad_id'];
		if($res['to_sklad_id']>0) $sklads[]=$res['to_sklad_id'];
		if($res['logistic_car_id']>0) $cars[]=$res['logistic_car_id'];
		$ret_cars=$db->getInd("id","select * from logistic_cars where id in (?b)",$cars);
		$ret_companys=$db->getInd("id","select * from company where id in (?b)",$companys);
		$ret_sklads=$db->getInd("id","select * from sklad where id in (?b)",$sklads);
	    if ($res){ 
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_order']=$res;
				$ret['cars']=$ret_cars;
				$ret['companys']=$ret_companys;
				$ret['sklads']=$ret_sklads;
				$ret['msg']="";
				//$ret['cars_logistic_companys']=$cars_logistic_companys;
				if(count((array)$cars_logistic_companys)>0) {

				}
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['logistic_order']=[];
				$ret['msg']="Список автомобилей пуст";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}


}



?>
