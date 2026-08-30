<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DeliveryToWorkshop;
use Sort1API\Components\DeliveryToWorkshopDetail;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DeliveryToWorkshops extends Model {

	public static function save_delivery_to_workshop($request) {
	    $fields="";
		$db = DB::getInstance();
	    if (isset($request->delivery_to_workshop_id) && (int)$request->delivery_to_workshop_id>0) {$delivery_to_workshop_id=(int)$request->delivery_to_workshop_id;}
	    if(isset($delivery_to_workshop_id) && $delivery_to_workshop_id>0) {
				$delivery_to_workshop=new DeliveryToWorkshop($delivery_to_workshop_id);
	    }
	    else {
				$delivery_to_workshop=new DeliveryToWorkshop();
		}
	    if (isset($request->zakaz_id)) { $delivery_to_workshop->zakaz_id=(int)$request->zakaz_id; }
		if (isset($request->sklad_id) && (int)$request->sklad_id>0) { $delivery_to_workshop->sklad_id=$request->sklad_id; }
	    if (isset($request->employee_id)) {$delivery_to_workshop->employee_id=$request->employee_id;}
	    if (isset($request->service_id)) {$delivery_to_workshop->service_id=$request->service_id;}
		if (isset($request->status)) {$delivery_to_workshop->status=$request->status; }
	    $delivery_to_workshop_saved=$delivery_to_workshop->save();
		    if($delivery_to_workshop_saved['status']>=1){
					$ret['status']="ok";
					$ret['msg']="";
					if((int)$request->delivery_to_workshop_id==0)
						$ret['delivery_to_workshop_id']=$delivery_to_workshop->id;
		    }
		    else {
					$ret['status']="err"; 
					$ret['err']=$delivery_to_workshop_saved['msg'];
					$ret['ret']=$delivery_to_workshop_saved;
		    }
	    if ($ret['status']=="err") return $ret;
	    else return $ret;
	} // action_save_company

	public static function save_deliverys_to_workshop($request) {
	    $fields="";
		$db = DB::getInstance();
		if(empty($request->zakaz_id) || (int)$request->zakaz_id<1){
			return array("status"=>"err","err"=>"Не указан заказ");
		}
		$sklad_id=$db->getOne("select delivery_type_id from zakaz where id=?i and delivery_type=1",$request->zakaz_id);
		if((int)$sklad_id<1){
			return array("status"=>"err","err"=>"Заказ не привязан к складу");
		}
		if(!empty($request->deliverys_to_workshop) && count((array)$request->deliverys_to_workshop)>0){
			foreach($request->deliverys_to_workshop as $deliv_ind=>$deliv_val){
				$delivery_to_workshop=new DeliveryToWorkshop(0,$sklad_id,$request->zakaz_id,$deliv_val['zakaz_detail_employee_id']);
				//echo "new DeliveryToWorkshop(0,$sklad_id,".$request->zakaz_id.",".$deliv_val['zakaz_detail_employee_id'].");\n";
				if($delivery_to_workshop->id>0){
					
				}
				else {
					$saved=$delivery_to_workshop->save();
					//echo print_r($saved,true)."\n";
					if($saved['status']<1) return array("status"=>"err","err"=>"Не удается создать запись выдачи в ремзону");
				}
				if((int)$delivery_to_workshop->id>0 && (int)$deliv_val['zakaz_detail_id']>0){
					$delivery_to_workshop_detail=new DeliveryToWorkshopDetail(0,(int)$delivery_to_workshop->id,(int)$deliv_val['zakaz_detail_id']);
				}
				else  
					$delivery_to_workshop_detail=new DeliveryToWorkshopDetail();
				$delivery_to_workshop_detail->delivery_to_workshop_id=(int)$delivery_to_workshop->id;
				$delivery_to_workshop_detail->zakaz_detail_id=(int)$deliv_val['zakaz_detail_id'];
				$delivery_to_workshop_detail->zakaz_detail_job_id=(int)$deliv_val['zakaz_detail_job_id'];
				$delivery_to_workshop_detail->zakaz_id=(int)$request->zakaz_id;
				$delivery_to_workshop_detail->detail_id=(int)$deliv_val['detail_id'];
				$delivery_to_workshop_detail->count=(int)$deliv_val['count'];
				$delivery_to_workshop_detail->delivered_count=(int)$deliv_val['count'];
				$delivery_to_workshop_detail->status=10;
				$save_ret=$delivery_to_workshop_detail->save();
				if($save_ret['status']=="err") return $save_ret;
				$delivery_to_workshop_detail->status=20;
				$save_ret=$delivery_to_workshop_detail->save();
				if($save_ret['status']=="err") return $save_ret;
			}
		}
		$ret['status']="ok";
		$ret['msg']="";
	    return $ret;
	}

	public static function delete_delivery_to_workshop($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
		}
		
	    if (isset($request->delivery_to_workshop_id) && (int)$request->delivery_to_workshop_id>0){
			$delivery_to_workshop=$db->getRow("select * from delivery_to_workshop where id=?i and main_company_id=?i",(int)$request->delivery_to_workshop_id,(int)$_SESSION['main_company']);
			if($delivery_to_workshop['status']!=1 && $delivery_to_workshop['status']!=10){
				return self::_error_arr("Данную выдачу нельзя удалить, она находится в работе");
			}
			if((int)$delivery_to_workshop['id']>0){
				$res2=$db->query("delete from delivery_to_workshop where id=?i and main_company_id=?i",(int)$delivery_to_workshop['id'],(int)$_SESSION['main_company']);
				$res3=$db->query("delete from delivery_to_workshop_details where delivery_to_workshop_id=?i",(int)$delivery_to_workshop['id']);
				if ($res2 && $res3){
					$ret['status']="ok";
					$ret['msg']="";
				}
				else {
					$ret['status']="err";
					$ret['err']="не удалось удалить выдачу";
				}
			}
			else {
				return self::_error_arr("Выдача не существует");
			}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_delivery_to_workshops($request) {
		$db = DB::getInstance();
		$filter="";
		if(!empty($request->search_delivery_to_workshop_date_from)){
			//if(empty($filter)) $filter.=$db->parse(" create_date>=?s",$request->search_delivery_to_workshop_date_from);
			//else 
			$filter.=$db->parse(" and dtw.create_date>=?s",$request->search_delivery_to_workshop_date_from);
		}
		if(!empty($request->search_delivery_to_workshop_date_to)){
			//echo $request->search_delivery_to_workshop_date_to;
			//if(empty($filter)) $filter.=$db->parse(" create_date<=?s",$request->search_delivery_to_workshop_date_to);
			//else 
			$filter.=$db->parse(" and dtw.create_date<=?s",$request->search_delivery_to_workshop_date_to);
			//echo $filter;
		}
		/*if(!empty($request->search_delivery_to_workshop_employee_name)){
			$sql_cl="select id from service_employees where main_company_id=?i and (lastname like ?s or name like %s)";
			$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_delivery_to_workshop_employee_name.'%','%'.$request->search_delivery_to_workshop_employee_name.'%');
		}*/
	    $sql="select dtw.*,concat(e.lastname,' ',e.name) as employee_name,s.name as sklad_name from delivery_to_workshop dtw 
		left join service_employees e on (e.id=dtw.employee_id)
		left join sklad s on (dtw.sklad_id=s.id)
		where dtw.main_company_id=?i ?p order by dtw.create_date desc";
	    $res=$db->getAll($sql,$_SESSION['main_company'],$filter);
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['delivery_to_workshops']=$res;
			$ret['statuses']=$db->getInd("id","select * from delivery_to_workshop_statuses");
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['delivery_to_workshops']=[];
			$ret['statuses']=$db->getInd("id","select * from delivery_to_workshop_statuses");
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_delivery_to_workshop($request) {
	    $db = DB::getInstance();
		if(empty($request->delivery_to_workshop_id) || (int)$request->delivery_to_workshop_id==0){
			return array("status"=>"err","err"=>"Не указан номер выдачи");
		}	
	    $sql="select dtw.*,concat(e.lastname,' ',e.name) as employee_name,s.name as sklad_name from delivery_to_workshop dtw 
		left join service_employees e on (e.id=dtw.employee_id)
		left join sklad s on (dtw.sklad_id=s.id) where dtw.id=?i and dtw.main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->delivery_to_workshop_id,$_SESSION['main_company']);
	    if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['delivery_to_workshop']=$res;
			$ret['statuses']=$db->getInd("id","select * from delivery_to_workshop_statuses");
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['delivery_to_workshop']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}



?>
