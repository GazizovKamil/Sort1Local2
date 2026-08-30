<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\SkladPrice;
use Sort1API\Components\SkladPriceUser;
use Sort1API\Components\SkladPriceDetail;
use Sort1API\Components\Models\Search;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class SkladPrices extends Model {

	public static function save_sklad_price($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->sklad_price_id) && (int)$request->sklad_price_id>0) {$sklad_price_id=(int)$request->sklad_price_id;}
	    if(isset($sklad_price_id) && $sklad_price_id>0) {
				$sklad_price=new SkladPrice($sklad_price_id);
	    }
	    else {
				$sklad_price=new SkladPrice();
		}
		//echo "sklad_price=".print_r($sklad_price,true);
		if(!isset($request->sklad_id) || (int)$request->sklad_id==0){
			return self::_error_arr("Не указан склад инвентаризации");
		}
	    if (isset($request->descr)) {$sklad_price->descr=$request->descr;}
		if (isset($request->sklad_id)) {$sklad_price->sklad_id=$request->sklad_id;}
		if (isset($request->sklad_price_type)) {$sklad_price->type=$request->sklad_price_type;}
		//echo "sklad_price=".print_r($sklad_price,true);
	    //print_r($_GET);
	    //echo $company->kpp;
		$sklad_price_saved=$sklad_price->save();

        if($sklad_price_saved){
					$ret['status']="ok";
					$ret['msg']="Данные успешно изменены";
		    }
		    else {
					$ret['status']="err";
					$ret['err']="Данные не менялись";
		    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	} // action_save_company


	public static function delete_sklad_price($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->sklad_price_id) && (int)$request->sklad_price_id>0){
			$sklad_price=new SkladPrice($request->sklad_price_id);
			if($sklad_price->status>1) return array("status"=>"err","err"=>"Нельзя удалить инвентаризацию, которая в работе или завершена");
			$res1=$db->query("delete from sklad_price_details where sklad_price_id=?i",(int)$request->sklad_price_id);
			$res2=$db->query("delete from sklad_price where id=?i and company_id=?i",(int)$request->sklad_price_id,(int)$_SESSION['main_company']);
				if ($res2 && $res1){
				    $ret['status']="ok";
				    //$res3=$db->query("update sklad_prices set deleted=1 where id=?i and main_company=?i",(int)$request->sklad_price_id,(int)$_SESSION['main_company']);
				    $ret['msg']="";
				}
				else {
				    $ret['status']="err";
				    $ret['err']="не удалось удалить инвентаризацию";
				}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_sklad_prices($request) {
	    $db = DB::getInstance();
	    $sql="select i.id,i.status,i.sklad_id,i.descr,i.create_date,i.update_date,i.type,s.name as sklad_name,ddk.sklad_price_positions,ddk.sklad_price_pos_count,ddk.sklad_price_pos_sum
		    from sklad_price i
			LEFT JOIN (SELECT sklad_price_id,COUNT(detail_id) AS sklad_price_positions,SUM(count_sklad) AS sklad_price_pos_count,sum(price*count_sklad)
				as sklad_price_pos_sum FROM sklad_price_details where deleted=0 GROUP BY sklad_price_id) AS ddk ON (ddk.sklad_price_id=i.id)
		    left join sklad s on (s.id=i.sklad_id)
			where i.company_id=?i and i.deleted=0 ";
		
			if(!empty($request->search_sklad_price_date_from)) {
				$date_from=date("Y-m-d",strtotime($request->search_sklad_price_date_from));
				$sql.=" and i.create_date>='".$date_from."'";
			}
			else {
				$date_from=date("Y-m-d",strtotime("1 month ago"));
				$sql.=" and i.create_date>='".$date_from."'";
			}
			if(!empty($request->search_sklad_price_date_to)) {
				$date_to=date("Y-m-d",strtotime($request->search_sklad_price_date_to));
				$sql.=" and i.create_date<='".$date_to." 23:59:59'";
			}
			else { 
				$date_to=date("Y-m-d");
				$sql.=" and i.create_date<='".$date_to." 23:59:59'";
			}
			if(!empty($request->search_sklad_price_sklad_name)){
				$sql_cl="select id from sklad where company_id=?i and name like ?s";
				$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],'%'.$request->search_sklad_price_sklad_name.'%');
				if($res_cl){
					$search_sklads=array_column($res_cl,"id");
					$sql.=" and i.sklad_id in (".implode($search_sklads).")";
				}
				$ret['search_sklad_price_sklad_name']=$request->search_sklad_price_sklad_name;
			}
			$sql.=" order by i.create_date desc";
			$res=$db->getAll($sql,$_SESSION['main_company']);
		
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklad_prices']=$ret_res;
			$ret['msg']="";
			$ret['search_sklad_price_date_to']=$date_to;
			$ret['search_sklad_price_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['sklad_prices']=array();
			$ret['search_sklad_price_date_to']=$date_to;
			$ret['search_sklad_price_date_from']=$date_from;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad_price($request) {
	    $db = DB::getInstance();
			if(empty($request->sklad_price_id) || (int)$request->sklad_price_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select i.descr,s.id as sklad_id,s.name as sklad_name,i.type from sklad_price i left join sklad s on (s.id=i.sklad_id) where i.id=?i and i.company_id=?i and i.deleted=0";
	    $res=$db->getRow($sql,(int)$request->sklad_price_id,$_SESSION['main_company']);
	    if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['sklad_price']=$res;
			//$ret['sklad_price_users']=$db->getAll("select iu.id,iu.is_header,u.id as user_id,u.name,u.lastname,u.middlename from sklad_price_users iu
			//	left join users u on (u.id=iu.user_id)
			//	where iu.sklad_price_id=?i",(int)$request->sklad_price_id);
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['sklad_price']=[];
			$ret['sklad_price_users']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function add_sklad_price_user($request){
		$db = DB::getInstance();
		if(empty($request->sklad_price_id) || empty($request->user_id)){
			return self::_error_arr("Недостаточно параметров");
		}
		$is_my_sklad_price=$db->getOne("select id from sklad_price where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->sklad_price_id);
		if((int)$is_my_sklad_price==0){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		}
		$user_ids=$db->getCol("select user_id from user_companys where company_id=?i",$_SESSION['main_company']);
		if(!in_array($request->user_id,$user_ids)){
			return self::_error_arr("Неправильно заданы параметры, не ваш пользователь");
		}
	    //$sql="select id,username,roles,create_date,company_id,name,middlename,lastname,email,phone,mphone from users where id in (?a)";
	    //$comp_users=$db->getAll($sql,$user_ids);
		$is_user_id=$db->getOne("select id from sklad_price_users where user_id=?i and sklad_price_id=?i",$request->user_id,$request->sklad_price_id);
		if((int)$is_user_id>0){
			$sklad_price_user=new SkladPriceUser((int)$is_user_id);
		}
		else {
			$sklad_price_user=new SkladPriceUser();
		}
		$sklad_price_user->sklad_price_id=(int)$request->sklad_price_id;
		$sklad_price_user->user_id=(int)$request->user_id;
		$sklad_price_user->save();
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		return $ret;
	}

	public static function get_sklad_price_details($request){
		$db = DB::getInstance();
		if(empty($request->sklad_price_id) || (int)$request->sklad_price_id==0) {
			return self::_error_arr("Недостаточно параметров");
		}
		$sklad_price=new SkladPrice((int)$request->sklad_price_id);
		//$sklad_price_details_count=$db->getOne("select count(id) from sklad_price_details where sklad_price_id=?i",(int)$request->sklad_price_id);
		//$is_my_sklad_price=$db->getOne("select id from sklad_price where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->sklad_price_id);
		if((int)$sklad_price->company_id!=$_SESSION['main_company']){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		}
		$sql="select * from sklad_price_details where sklad_price_id=?i ?p";
		$parsed="";
		if(!empty($request->search_article)){
			if((int)$sklad_price->status==20 || (int)$sklad_price->status==30) $parsed.=$db->parse(" and (article like ?s or my_code=?s or ean13=?s) and status=?i",'%'.$request->search_article.'%',$request->search_article,$request->search_article,$sklad_price->status);
			else $parsed.=$db->parse(" and (article like ?s or my_code=?s or ean13=?s)",'%'.$request->search_article.'%',$request->search_article,$request->search_article);
		}
		else {
			if((int)$sklad_price->status==20 || (int)$sklad_price->status==30) 
				$parsed.=$db->parse(" and status=?i",$sklad_price->status);
			
		}
		if(!empty($request->search_name)){
			$parsed.=$db->parse(" and name like ?s","%".$request->search_name."%");
		}
		if(!empty($request->search_brand)){
			$parsed.=$db->parse(" and brand like ?s","%".mb_strtoupper(trim($request->search_brand))."%");
		}
		$sklad_price_details_count=$db->getOne("select count(id) from sklad_price_details where sklad_price_id=?i ?p",(int)$request->sklad_price_id,$parsed);

		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$sklad_price_details_count;
	    $pages=ceil($sklad_price_details_count/$page_size);
	    if(isset($request->selected_page) && (int)$request->selected_page<=(int)$pages) {
			$parsed.=$db->parse(" limit ?i,?i",$page_size*((int)$request->selected_page-1),$page_size);
	    }
	    else
		$parsed.=" limit 0,".$page_size;

		if(empty($request->selected_page)) $request->selected_page=1;
		
		$sklad_price_details=$db->getAll($sql,(int)$request->sklad_price_id,$parsed);
		if(count($sklad_price_details)>0){
			return array("status"=>"ok","sklad_price_details"=>$sklad_price_details,"msg"=>"","search_article"=>$request->search_article,"search_name"=>$request->search_name,"search_name"=>$request->search_name,"search_brand"=>$request->search_brand,"sklad_price_pages"=>$pages,"details_count"=>(int)$sklad_price_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
		else {
			if($sklad_price_details_count==0 && empty($request->search_name) && empty($request->search_article)){
				// необходимо создать детали со склада
				if((int)$sklad_price->type==1) $status=0;
				if((int)$sklad_price->type==2) $status=1;
				if(isset($request->show_zero) && $request->show_zero){
					$sklad_details=$db->getAll("select sd.*,s.price_type from sklad_details sd 
                	left join sklad s on (s.id=sd.sklad_id)
                	where sd.sklad_id=(select sklad_id from sklad_price where id=?i)",$request->sklad_price_id);
				}
				else {
					$sklad_details=$db->getAll("select sd.*,s.price_type from sklad_details sd 
					left join sklad s on (s.id=sd.sklad_id)
					where sd.sklad_id=(select sklad_id from sklad_price where id=?i) and sd.count>0",$request->sklad_price_id);
				}
                $sklad_details=Search::get_sale_price($sklad_details,0,"",array(),$db,1);
				$sklad_price_details_sql="insert into sklad_price_details (sklad_price_id,article,brand,detail_id,brand_id,name,count_sklad,price,sale_price,status,my_code,ean13) values ?p";
				$i=0;
				$parsed_part="";
				foreach($sklad_details as $sd_key=>$sd_val){
					if($i>0) $parsed_part.=",";
					//$sklad_price_details_sql.="(".$request->sklad_price_id.",
					//'".$sd_val['article']."','".$sd_val['brand']."',".$sd_val['detail_id'].",".$sd_val['brand_id'].",
					//'".$sd_val['name']."',".$sd_val['count'].",'".$sd_val['price']."')";
					$parsed_part.=$db->parse("(?i,?s,?s,?i,?i,?s,?i,?s,?s,?i,?s,?s)",$request->sklad_price_id,$sd_val['article'],$sd_val['brand'],$sd_val['detail_id'],$sd_val['brand_id'],$sd_val['name'],$sd_val['count'],$sd_val['price'],$sd_val['sale_price'],$status,$sd_val['my_code'],$sd_val['ean13']);
					$i++;
				}
				//file_put_contents("/var/log/shop/api/get_sklad_price_details.log",$sklad_price_details_sql.$parsed_part,FILE_APPEND);
				//return array("status"=>"ok","msg"=>"","sql"=>$sklad_price_details_sql,"parsed"=>$parsed_part);
				if(count($sklad_details)>0){
					$res=$db->query($sklad_price_details_sql,$parsed_part);
				}
				$sklad_price_details=$db->getAll("select * from sklad_price_details where sklad_price_id=?i limit 0,".(int)$page_size,(int)$request->sklad_price_id);
				$sklad_price_details_count=$db->getOne("select count(id) from sklad_price_details where sklad_price_id=?i ?p",(int)$request->sklad_price_id,$parsed);
				$pages=ceil($sklad_price_details_count/$page_size);
				if(count($sklad_price_details)>0){
					return array("status"=>"ok","sklad_price_details"=>$sklad_price_details,"msg"=>"","sklad_price_pages"=>$pages,"details_count"=>(int)$sklad_price_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
				}
				else {
					return self::_error_arr("На складе нет деталей");
				}
			}
			else return array("status"=>"ok","sklad_price_details"=>$sklad_price_details,"msg"=>"","search_article"=>$request->search_article,"search_name"=>$request->search_name,"search_brand"=>$request->search_brand,"sklad_price_pages"=>$pages,"details_count"=>(int)$sklad_price_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
	}

	public static function save_sklad_price_detail($request){
		$db = DB::getInstance();
		if(empty($request->sklad_price_detail_id) || (int)$request->sklad_price_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		$sql="update sklad_price_details set sale_price=?i where id=?i";
		$res=$db->query($sql,$request->sale_price,$request->sklad_price_detail_id);
		if($db->affectedRows()<1){
			return self::_error_arr("Не удалось сохранить данные");
		}
		else {
			return array("status"=>"ok","msg"=>"");
		}
	}

	public static function add_sklad_price_detail_to_start($request){
		$db = DB::getInstance();
		if(empty($request->sklad_price_detail_id) || (int)$request->sklad_price_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		$sklad_price=$db->getRow("select i.status,id.status as sklad_price_detail_status
		 from sklad_price i left join sklad_price_details id on (id.id=?i) 
		 where i.id=(select sklad_price_id from sklad_price_details where id=?i)", (int)$request->sklad_price_detail_id,(int)$request->sklad_price_detail_id);
		if((int)$sklad_price['status']>1){
			return self::_error_arr("Инвентаризация уже идет, нельзя в нее добавлять детали, завершите инвентаризацию, после создайте новую, в которую включите данную деталь со склада");
		}
		if((int)$sklad_price['sklad_price_detail_status']==0) $status=1;
		else $status=0;
		if($db->query("update sklad_price_details set status=?i where id=?i",$status,(int)$request->sklad_price_detail_id)){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return self::_error_arr("не удалось включить деталь в инвентаризацию");
		}
	}

	public static function start_sklad_price($request){
		$db = DB::getInstance();
		if(empty($request->sklad_price_id) || (int)$request->sklad_price_id==0){
			return self::_error_arr("Не указан номер инвентаризации");
		}
		$sklad_price=new SkladPrice((int)$request->sklad_price_id);
		if((int)$sklad_price->company_id!=$_SESSION['main_company']){
			return self::_error_arr("Не хватает прав, не ваш список");
		}
		$sklad_price->status=20;
		$sklad_price->save();
		return array("status"=>"ok","msg"=>"");
	}

	public static function sklad_price_submit($request){
		$db = DB::getInstance();
		if(empty($request->sklad_price_id)) {
			return self::_error_arr("Недостаточно параметров");
		}
		/*$is_my_sklad_price=$db->getRow("select id,sklad_id from sklad_price where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->sklad_price_id);
		if((int)$is_my_sklad_price['id']==0){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		} */
		$sklad_price=new SkladPrice((int)$request->sklad_price_id);
		if((int)$sklad_price->company_id!=$_SESSION['main_company']){
			return self::_error_arr("Не хватает прав, не ваша инвентаризация");
		}
		$sklad_price_details=$db->getAll("select * from sklad_price_details where sklad_price_id=?i and status=20",(int)$request->sklad_price_id);
		$det_to_block=array();
		$dets_status=array();
		foreach($sklad_price_details as $id_key=>$id_val){
			$det_to_block[]=$id_val['detail_id'];
			$dets_status[]=$id_val['id'];
			if($id_val['otklonenie']<0){
				$spisanie_details[]=$id_val;
			}
			else {
				if($id_val['otklonenie']>0)
					$oprihod_details[]=$id_val;
			}
		}
		$res1=$db->query("update sklad_details set sklad_price_blocked=0 where sklad_id=?i and detail_id in (?a)",$sklad_price->sklad_id,$det_to_block);
		if(!$res1){
			return self::_error_arr("произошла ошибка при работе с базой данных");
		}
		$res2=$db->query("update sklad_price_details set status=30 where id in (?a)",$dets_status);
		if(!$res2){
			return self::_error_arr("произошла ошибка при работе с базой данных");
		}
		if(count($spisanie_details)>0){
			$doc_id_s=$db->getOne("select id from document where id=(select document_id_s from sklad_price where id=?i) and deleted=0 and type_id=3",(int)$request->sklad_price_id);
			if((int)$doc_id_s==0){
				$new_doc_s=array();
				$new_doc_s['company_id']=$_SESSION['main_company'];
				$new_doc_s['type_id']=3;
				$new_doc_s['sklad_id']=$sklad_price->sklad_id;
				$new_doc_s['document_date']=date("Y-m-d H:i:s");
				$new_doc_s['comment']="Сформировано из инвентаризационной описи №".(int)$sklad_price->id;
				$res_doc=Documents::save_document((object)$new_doc_s);
				if($res_doc['status']=="ok" && (int)$res_doc['document_id']>0) {
					$doc_id_s=(int)$res_doc['document_id'];
					$db->query("update sklad_price set document_id_s=?i where id=?i",$doc_id_s,(int)$request->sklad_price_id);
				}
				else return self::_error_arr("Не удалось сформировать документ списания");
			}
		}
		if(count($oprihod_details)>0){
			$doc_id_o=$db->getOne("select id from document where id=(select document_id_o from sklad_price where id=?i) and deleted=0 and type_id=5",(int)$request->sklad_price_id);
			if((int)$doc_id_o==0){
				$new_doc_o=array();
				$new_doc_o['company_id']=$_SESSION['main_company'];
				$new_doc_o['type_id']=5;
				$new_doc_o['sklad_id']=$sklad_price->sklad_id;
				$new_doc_o['document_date']=date("Y-m-d H:i:s");
				$new_doc_o['comment']="Сформировано из инвентаризационной описи №".(int)$sklad_price->id;
				$res_doc=Documents::save_document((object)$new_doc_o);
				if($res_doc['status']=="ok" && (int)$res_doc['document_id']>0){ 
					$doc_id_o=(int)$res_doc['document_id'];
					$db->query("update sklad_price set document_id_o=?i where id=?i",$doc_id_o,(int)$request->sklad_price_id);
				}
				else return self::_error_arr("Не удалось сформировать документ списания");
			}
		}
		foreach($spisanie_details as $sd_key=>$sd_val){
			if((int)$sd_val['document_id']==0){
				$new_doc_det=array();
				$new_doc_det['article']=$sd_val['article'];
				$new_doc_det['brand']=$sd_val['brand'];
				$new_doc_det['count']=(-$sd_val['otklonenie']);
				$new_doc_det['detail_id']=$sd_val['detail_id'];
				$new_doc_det['brand_id']=$sd_val['brand_id'];
				$new_doc_det['document_id']=$doc_id_s;
				$new_doc_det['name']=$sd_val['name'];
				$new_doc_det['price']=$sd_val['price'];
				$new_doc_det['sklad_price_detail_id']=$sd_val['id'];
				$new_doc_det['sklad_id']=$sklad_price->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return self::_error_arr($res_doc_det['err']);
				}
				$db->query("update sklad_price_details set document_id=?i where id=?i",$doc_id_s,$sd_val['id']);
			}
			else {
				//Документ у этой детали уже сформирован необходимо, отредактировать деталь в этом документе если изменилось отклонение
				$document_data=$db->getRow("select type_id from document where id=?i",$sd_val['document_id']);
				if((int)$document_data['type_id']==5){
					// было оприходование, надо удалить из старого документа деталь и создать новый документ или выбрать из уже созданных, связанный с этой инвентаризацией
					$detail_id_to_del=$db->getOne("select id from document_details where document_id=?i and detail_id=?i",$sd_val['document_id'],$sd_val['detail_id']);
					$res_doc_det_del=DocumentDetails::delete_document_detail((object)array("document_detail_id"=>$detail_id_to_del));
				}
				$new_doc_det=array();
				$new_doc_det['article']=$sd_val['article'];
				$new_doc_det['brand']=$sd_val['brand'];
				$new_doc_det['count']=(-$sd_val['otklonenie']);
				$new_doc_det['detail_id']=$sd_val['detail_id'];
				$new_doc_det['brand_id']=$sd_val['brand_id'];
				$new_doc_det['document_id']=$doc_id_s;
				$new_doc_det['name']=$sd_val['name'];
				$new_doc_det['price']=$sd_val['price'];
				$new_doc_det['sklad_id']=$sklad_price->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return self::_error_arr($res_doc_det['err']);
				}
				$db->query("update sklad_price_details set document_id=?i where id=?i",$doc_id_s,$sd_val['id']);
			}
		}

		foreach($oprihod_details as $od_key=>$od_val){
			if((int)$od_val['document_id']==0){
				$new_doc_det=array();
				$new_doc_det['article']=$od_val['article'];
				$new_doc_det['brand']=$od_val['brand'];
				$new_doc_det['count']=$od_val['otklonenie'];
				$new_doc_det['detail_id']=$od_val['detail_id'];
				$new_doc_det['brand_id']=$od_val['brand_id'];
				$new_doc_det['document_id']=$doc_id_o;
				$new_doc_det['name']=$od_val['name'];
				$new_doc_det['price']=$od_val['price'];
				$new_doc_det['sklad_price_detail_id']=$od_val['id'];
				$new_doc_det['sklad_id']=$sklad_price->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return self::_error_arr($res_doc_det['err']." ".print_r($new_doc_det,true));
				}
				$db->query("update sklad_price_details set document_id=?i where id=?i",$doc_id_o,$od_val['id']);
			}
			else {
				//Документ у этой детали уже сформирован необходимо, отредактировать деталь в этом документе если изменилось отклонение
				//проверим не изменился ли тип документа (было списание стало оприходование или наоборот)
				$document_data=$db->getRow("select type_id from document where id=?i",$od_val['document_id']);
				if((int)$document_data['type_id']==3){
					// было списание, надо удалить из старого документа деталь и создать новый документ или выбрать из уже созданных, связанный с этой инвентаризацией
					$detail_id_to_del=$db->getOne("select id from document_details where document_id=?i and detail_id=?i",$od_val['document_id'],$od_val['detail_id']);
					$res_doc_det_del=DocumentDetails::delete_document_detail((object)array("document_detail_id"=>$detail_id_to_del));
					$db->query("update sklad_price_details set document_id=?i where id=?i",$doc_id_o,$od_val['id']);
					//$od_val['document_id']=$doc_id_o;
				}
				$new_doc_det=array();
				$new_doc_det['article']=$od_val['article'];
				$new_doc_det['brand']=$od_val['brand'];
				$new_doc_det['count']=$od_val['otklonenie'];
				$new_doc_det['detail_id']=$od_val['detail_id'];
				$new_doc_det['brand_id']=$od_val['brand_id'];
				$new_doc_det['document_id']=$doc_id_o;
				$new_doc_det['name']=$od_val['name'];
				$new_doc_det['price']=$od_val['price'];
				$new_doc_det['sklad_id']=$sklad_price->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return self::_error_arr($res_doc_det['err']);
				}
				$db->query("update sklad_price_details set document_id=?i where id=?i",$doc_id_o,$od_val['id']);
			}
		}
		$sklad_price->status=30;
		$sklad_price->save();
		return array("status"=>"ok","msg"=>"","spisanie_details"=>$spisanie_details,"oprihod_details"=>$oprihod_details);
	}

}



?>
