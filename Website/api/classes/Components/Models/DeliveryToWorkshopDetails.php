<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DeliveryToWorkshopDetail;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DeliveryToWorkshopDetails extends Model {

	public static function save_delivery_to_workshop_detail($request) {
	    $fields="";
	    $db = DB::getInstance(); 
	    if (isset($request->delivery_to_workshop_detail_id) && (int)$request->delivery_to_workshop_detail_id>0) {$delivery_to_workshop_detail_id=(int)$request->delivery_to_workshop_detail_id;}
	    if(isset($delivery_to_workshop_detail_id) && $delivery_to_workshop_detail_id>0) {
				$delivery_to_workshop_detail=new DeliveryToWorkshopDetail($delivery_to_workshop_detail_id);
	    }
	    else {
				$delivery_to_workshop_detail=new DeliveryToWorkshopDetail();
		}
		$delivery_to_workshop=$db->getRow("select * from delivery_to_workshop where id=?i",(int)$request->delivery_to_workshop_id);
		if($delivery_to_workshop['status']==30 || $delivery_to_workshop['status']==50){
			if (isset($request->delivery_to_workshop_id)) { $delivery_to_workshop_detail->delivery_to_workshop_id=(int)$request->delivery_to_workshop_id; }
			if (isset($request->zakaz_detail_id) && (int)$request->zakaz_detail_id>0) { $delivery_to_workshop_detail->zakaz_detail_id=$request->zakaz_detail_id; }
			if (isset($request->zakaz_id) && (int)$request->zakaz_id>0) { $delivery_to_workshop_detail->zakaz_id=$request->zakaz_id; }
			if (isset($request->status)) {$delivery_to_workshop_detail->status=$request->status;}
			if (isset($request->detail_id)) {$delivery_to_workshop_detail->detail_id=$request->detail_id;}
			if (isset($request->count)) {$delivery_to_workshop_detail->count=$request->count;}
			//print_r($_GET);
			//echo $company->kpp;
			$delivery_to_workshop_detail_saved=$delivery_to_workshop_detail->save();
			if($delivery_to_workshop_detail_saved['status']=="ok"){
				$ret['status']="ok";
				$ret['msg']="Данные успешно изменены";
			}
			else {
				$ret['status']="err";
				$ret['err']=$delivery_to_workshop_detail_saved['err'];
			}
		}
		else {
			$ret['status']="err";
			$ret['err']="Нельзя изменять данные детали, если деталь учтановлена в автомобиль или возвращена на склад";
		}
	    return $ret;
	} 


	public static function delete_delivery_to_workshop_detail($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
		}
	    if (isset($request->delivery_to_workshop_detail_id) && (int)$request->delivery_to_workshop_detail_id>0){
			$delivery_to_workshop=$db->getRow("select * from delivery_to_workshop where 
				id=(select delivery_to_workshop_id from delivery_to_workshop_details where id=?i)
				and main_company_id=?i",(int)$request->delivery_to_workshop_detail_id,$_SESSION['main_company']);
			if($delivery_to_workshop['status']==1 || $delivery_to_workshop['status']==10 || $delivery_to_workshop['status']==50){
				$res2=$db->query("delete from delivery_to_workshop_details where id=?i",(int)$request->delivery_to_workshop_detail_id);
				if ($res2){
					$ret['status']="ok";
					$ret['msg']="Деталь успешно удалена из выдачи";
				}
				else {
					$ret['status']="err";
					$ret['err']="не удалось удалить деталь из выдачи";
				}
			}
			else {
				$ret['status']="err";
				$ret['err']="Нельзя удалить детали из выполненной выдачи";
			}
	    }
	    else {
			$ret['status']="err";
			$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_delivery_to_workshop_details($request) {
		$db = DB::getInstance(); 
		$is_your=$db->getOne("select id from delivery_to_workshop where id=?i and main_company_id=?i",(int)$request->delivery_to_workshop_id,$_SESSION['main_company']);
		if(!$is_your){
			return array("status"=>"err","err"=>"Эта запись не принадлежит вам");
		}
        if(!empty($request->delivery_to_workshop_id) && (int)$request->delivery_to_workshop_id>0){
            $sklad_id=$db->getOne("select sklad_id from delivery_to_workshop where id=?i",$request->delivery_to_workshop_id);
            $sql="select l.*,sd.article,sd.brand,sd.name from delivery_to_workshop_details l 
                left join sklad_details sd on (sd.sklad_id=?i and sd.detail_id=l.detail_id) 
                where l.delivery_to_workshop_id=?i";
            $res=$db->getAll($sql,$sklad_id,$request->delivery_to_workshop_id);
        }
        else {
            return self::_error_arr('Не указан номер выдачи');
        }
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['delivery_to_workshop_details']=$res;
			$ret['statuses']=$db->getInd("id","select * from delivery_to_workshop_statuses");
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok"; 
			$ret['err']="";
			$ret['delivery_to_workshop_details']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_delivery_to_workshop_detail($request) {
	    $db = DB::getInstance();
		if(empty($request->delivery_to_workshop_detail_id) || (int)$request->delivery_to_workshop_detail_id==0){
			return self::_error_arr('нет данных');
		}
		$sklad_id=$db->getOne("select sklad_id from delivery_to_workshop where id=(select delivery_to_workshop_id from delivery_to_workshop_details where id=?i)",$request->delivery_to_workshop_detail_id);
	    $sql="select l.*,sd.article,sd.brand,sd.name from delivery_to_workshop_details l
			left join sklad_details sd on (sd.sklad_id=?i and sd.detail_id=l.detail_id)
			where l.id=?i and l.delivery_to_workshop_id in (select id from delivery_to_workshop where main_company_id=?i)";
	    $res=$db->getRow($sql,$sklad_id,(int)$request->delivery_to_workshop_detail_id,$_SESSION['main_company']);
	    if ($res){ 
				$ret['status']="ok";
				$ret['err']="";
				$ret['delivery_to_workshop_detail']=$res;
				$ret['statuses']=$db->getInd("id","select * from delivery_to_workshop_statuses");
				$ret['msg']="";
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['delivery_to_workshop_detail']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function return_delivery_to_workshop_detail($request) {
	    $db = DB::getInstance();
		if(empty($request->delivery_to_workshop_detail_id) || (int)$request->delivery_to_workshop_detail_id==0){
			return self::_error_arr('нет данных');
		}
		if(empty($request->return_count) || (int)$request->return_count==0){
			return self::_error_arr('нет указано количество возвращаемых деталей');
		}
		$delivery_to_workshop_detail=new DeliveryToWorkshopDetail((int)$request->delivery_to_workshop_detail_id);
		$delivery_to_workshop_detail->status=50;
		$delivery_to_workshop_detail->returned_count=(int)$request->return_count;
		$res=$delivery_to_workshop_detail->save();
	    //$sql="select * from delivery_to_workshop_details where id=?i and delivery_to_workshop_id in (select id from delivery_to_workshop where main_company_id=?i)";
	    //$res=$db->getRow($sql,(int)$request->delivery_to_workshop_detail_id,$_SESSION['main_company']);
	    if ($res['status']=="ok"){ 
			$ret['status']="ok";
			$ret['err']="";
			$ret['msg']="";
	    }
		else {
			$ret['status']="err";
			$ret['err']=$res['err'];
			$ret['delivery_to_workshop_detail']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}


}



?>
