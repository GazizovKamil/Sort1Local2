<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\MarketZakaz;
use Sort1API\Components\MarkeZakazDetail;
use Sort1API\Components\ZakazJob;
use Sort1API\Components\Basket;
use Sort1API\Components\DeliveryAddress;
use Sort1API\Components\Models\ZakazDetails;
use Sort1API\Components\Models\ZakazJobs;
use Sort1API\Components\Models\Payments;
use Sort1API\Components\LogisticOrderDetail;
use Sort1API\Components\LogisticOrder;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Company;
use Sort1API\Components\Functions;
use stdClass;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class MarketZakazs extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        // public static function save_zakaz($request) {
		// 	$db = DB::getInstance();
		// 	if (isset($request->zakaz_id)) $zakaz_id=(int)$request->zakaz_id;
      	//     if (isset($zakaz_id) && $zakaz_id>0) {
		// 			$zakaz=new Zakaz($zakaz_id);
		// 			$zakaz_is_new=0;
        //     }
        //     else {
		// 		$zakaz=new Zakaz();
		// 		$zakaz_is_new=1;
		// 	}
        // }


	public static function get_market_zakazes($request) {
		$db = DB::getInstance();
		$filter="";
	    if($_SESSION['roles']<10){
		    $sql="select z.id,z.zakaz_id_in_marketplace,z.zakaz_id_in_sort1,z.marketplaces_config_id,z.create_date,z.update_date,z.pozition_count,z.zakaz_sum,z.status,z.oplachen,z.user_id,z.chat_id,u.lastname as user_lastname,u.name as user_name,u.middlename as user_middlename,
					   u.roles as user_roles,z.comment,z.payment_type,z.delivery_type,z.delivery_address,z.delivery_type_id,if(z.delivery_type=1,s.name,fs.name) as delivery_type_name,z.fullfilment_id,
					   z.company_id,c.name as company_name,c.mphone as company_phone,c.email as company_email,c.address as company_address,cb.balance as company_balance,cb.rezerv as company_rezerv, zd.id as rejected_details
		            from market_zakaz z
				left join marketplace_company c on(z.company_id=c.id)
				left join company_balance cb on (cb.company_id=z.company_id and cb.main_company_id=z.main_company_id)
				LEFT JOIN market_zakaz_details zd ON (zd.id=(SELECT id FROM zakaz_details WHERE market_zakaz_id=z.id AND status=101 and reorder_detail_id=0 LIMIT 1))
				left join sklad s on (s.id=z.delivery_type_id)
				left join sklad fs on (fs.id=z.fullfilment_id)
				left join users u on (u.id=z.user_id)
				where z.main_company_id=?i and z.deleted=0";
			if(!isset($request->show_archive) || $request->show_archive!="on") $sql.=" and z.status<>102 && z.status<>100 and z.deleted=0";
		    if(isset($request->company_id)) $sql.=" and z.company_id=".(int)$request->company_id;
			if(isset($request->sklad_id)) $sql.=" and z.delivery_type=1 and z.delivery_type_id=".(int)$request->sklad_id;
	    }
	    else
		    $sql="select z.id,z.create_date,z.update_date,z.pozition_count,z.zakaz_sum,z.status,z.oplachen,
                    z.comment,z.payment_type,z.delivery_type,z.delivery_address,z.delivery_type_id,c.name as company_name
              from market_zakaz z
              left join company c on(z.company_id=c.id)
			  where z.company_id=?i and z.deleted=0";
		if(!empty($request->search_market_zakaz_date_from)) {
			$date_from=date("Y-m-d",strtotime($request->search_market_zakaz_date_from));
			$filter.=$db->parse(" and z.create_date>=?s",$date_from);
		}
		else {
			$date_from=date("Y-m-d",strtotime("7 day ago"));
			$filter.=$db->parse(" and z.create_date>=?s",$date_from);
		}
		if(!empty($request->search_market_zakaz_date_to)) {
			$date_to=date("Y-m-d",strtotime($request->search_market_zakaz_date_to));
			$filter.=$db->parse(" and z.create_date<=?s",$date_to." 23:59:59");
		}
		else {
			$date_to=date("Y-m-d");
			$filter.=$db->parse(" and z.create_date<=?s",$date_to." 23:59:59");
		}
		if(!empty($request->search_marketplaces_configs_id)) {
			$marketplaces_configs_id=$request->search_marketplaces_configs_id;
			$filter.=$db->parse(" and z.marketplaces_config_id=?i",$marketplaces_configs_id);
			$ret['search_marketplaces_configs_id']=$request->search_marketplaces_configs_id;
		}
		if(!empty($request->search_market_zakaz_client_name)){
			$sql_cl="select id from marketplace_company where id in (select distinct(company_id) from marketplace_user_companys where (main_company_id=?i or company_id=?i) and btype<=4) and (name like ?s or mphone like ?s)";
			$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_market_zakaz_client_name.'%','%'.$request->search_market_zakaz_client_name.'%');
			if($res_cl){
				$search_companys=array_column($res_cl,"id");
				$filter.=$db->parse(" and z.company_id in (?b)",$search_companys);
			}
			else {
				// поисковая строка не пустая а компаний нет
				return self::_error_arr("Ничего не найдено");
			}
			$ret['search_zakaz_client_name']=$request->search_market_zakaz_client_name;
		}
      	$sql.=" ?p order by z.create_date desc";
		if($_SESSION['roles']<10){
			$res=$db->getAll($sql,$_SESSION['main_company'],$filter);
		}
		else {
			$res=$db->getAll($sql,$_SESSION['company_id'],$filter);
		}
		if(!empty($request->search_market_zakaz_article)){
			$res_art=$db->getCol("select distinct(market_zakaz_id) from market_zakaz_details where market_zakaz_id in (?b) and replace(replace(replace(replace(article,'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_market_zakaz_article))."%");
			foreach($res as $zakaz_key => $search_zakaz){
				if(in_array($search_zakaz["id"],$res_art)){
					$ret_res[]=$res[$zakaz_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_zakaz_article']=$request->search_market_zakaz_article;
		}
		if(!isset($ret_res)) $ret_res=$res;
		if(!empty($request->search_market_zakaz_article)){
			
		}
		
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakazs']=$ret_res;
			//$ret['payments']=$payments;
			$ret['msg']="";
			$ret['search_zakaz_date_to']=$date_to;
			$ret['search_zakaz_date_from']=$date_from;
	    }
      	else {
        	$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakazs']=[];
			//$ret['payments']=[];
			$ret['msg']="";
			$ret['search_zakaz_date_to']=$date_to;
			$ret['search_zakaz_date_from']=$date_from;
      	}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_market_zakaz($request) {
	    $db = DB::getInstance();
	    if($_SESSION['roles']<10){
			$sql="select z.id,z.create_date,z.update_date,z.user_id,z.pozition_count,z.zakaz_sum,z.status,z.oplachen,z.comment,z.payment_type,
			z.delivery_type,z.delivery_address,z.delivery_type_id,z.company_id,c.name as company_name,z.fullfilment_id,z.dogovor_id,z.marketing_channel_name,z.marketing_channel_id
		    from market_zakaz z left join company c on(z.company_id=c.id) where z.id=?i and z.main_company_id in (select company_id from user_companys where user_id=".(int)$_SESSION['user_id']." and main_company_id=0)";
	    }
	    else {
			$sql="select id,create_date,update_date,pozition_count,zakaz_sum,status,oplachen,comment,payment_type,delivery_type,
			delivery_address,delivery_type_id,fullfilment_id from market_zakaz where id=?i and company_id=".(int)$_SESSION['company_id']." and main_company_id=".(int)$_SESSION['main_company']." and deleted=0";
	    }
	    if (isset($request->zakaz_id) && (int)$request->zakaz_id>0){
    		$zakaz_id=(int)$request->zakaz_id;
    		$res=$db->getRow($sql,$zakaz_id);
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
			$ret['status']="ok";
			$ret['err']="";
			$ret['zakaz']=$res;
			$ret['delivery_types']=$delivery_types;
			$ret['users']=$db->getInd("id","select id,name,lastname,middlename from users where id in (select user_id from user_companys where main_company_id=0 and company_id=?i and deleted=0)",$_SESSION['main_company']);
			$fullfilments=$db->getAll("select id,name from sklad where company_id=?i and fullfilment=1",$_SESSION['main_company']);
			if($fullfilments) $ret['fullfilments']=$fullfilments;
			else $ret['fullfilments']=array();
				$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_market_zakaz($request) {
		$fields="";
		$res2=false;
		$db = DB::getInstance();
		$can_do_it=1;
		
	    if (isset($request->zakaz_id)) {$zakaz_id=(int)$request->zakaz_id;}
	    if (isset($zakaz_id) && $zakaz_id>0){
			$zakaz=new MarketZakaz($zakaz_id);
			if($zakaz->status>=19 && $zakaz->status<=63){
				$can_do_it=0;
				//return self::_error_arr("Нельзя удалить заказ уже оформленный у поставщика");
			}
			if ((int)$_SESSION['roles']==10 && $can_do_it && $zakaz->status!=15) {
				$can_do_it=0;
			}
			if ((int)$_SESSION['roles']>2 && (int)$_SESSION['roles']<10) {
				$can_do_it=0;
				//return self::_error_arr("У Вас нет прав для удаления");
			}
			if((int)$zakaz->user_id==$_SESSION['user_id'] && (int)$zakaz->status<153) 
				$can_do_it=1;
			if(!$can_do_it){
				return self::_error_arr("У Вас нет прав для удаления");
			}
			$sql="select market_zakaz_detail_id from market_zakaz_details where market_zakaz_detail_id=?i";
			$market_zakaz_details=$db->getCol($sql,$zakaz_id);
			// print_r($db->getStats());
			$det_flag=0;
			foreach($market_zakaz_details as $market_zakaz_detail_id){
				$req = new stdClass();
				$req->market_zakaz_id=$zakaz_id;
				$req->id=$market_zakaz_detail_id;
				if($_SESSION['roles']<10)
					$zak_ret=MarketZakazDetails::delete_market_zakaz_detail_by_manager($req);
				if($zak_ret['status']=="err") $det_flag=1;
			}
			if(!$det_flag)
				$res2=$db->query("update market_zakaz set deleted=1,status=142 where id=?i and (company_id in (select company_id from marketplace_user_companys where main_company_id=?i) or company_id=0)",$zakaz_id,$_SESSION['main_company']);
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

	public static function get_market_zakaz_statuses(){
	    $db = DB::getInstance();
	    $ret=$db->getAll("select market_status_id as id,descr,color from market_zakaz_statuses");
	    return $ret;
	}
}



?>
