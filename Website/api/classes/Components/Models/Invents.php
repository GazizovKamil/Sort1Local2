<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Invent;
use Sort1API\Components\InventUser;
use Sort1API\Components\InventDetail;
use Sort1API\Components\Models\Documents;
use Sort1API\Components\Models\DocumentDetails;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Invents extends Model {

	public static function save_invent($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->invent_id) && (int)$request->invent_id>0) {$invent_id=(int)$request->invent_id;}
	    if(isset($invent_id) && $invent_id>0) {
				$invent=new Invent($invent_id);
	    }
	    else {
				$invent=new Invent();
		}
		//echo "invent=".print_r($invent,true);
		if(!isset($request->sklad_id) || (int)$request->sklad_id==0){
			return self::_error_arr("Не указан склад инвентаризации");
		}
		$opened_invents=$db->getAll("select id from invent where sklad_id=?i and status=20",$request->sklad_id);
		if(count((array)$opened_invents)>0 && $invent_id==0){
			return self::_error_arr("Не могу создать новую инвентаризацию. На данном складе уже идет инвентаризация, необходимо закрыть уже открытую инвентаризацию");
		}
	    if (isset($request->descr)) {$invent->descr=$request->descr;}
		if (isset($request->sklad_id)) {$invent->sklad_id=$request->sklad_id;}
		if (isset($request->invent_type)) {$invent->type=$request->invent_type;}
		//echo "invent=".print_r($invent,true);
	    //print_r($_GET);
	    //echo $company->kpp;
		$invent_saved=$invent->save();
		if(!empty($request->is_header)){
			//назначить председателя
			$db->query("update invent_users set is_header=0 where invent_id=?i",(int)$invent->id);
			$db->query("update invent_users set is_header=1 where invent_id=?i and user_id=?i",(int)$invent->id,(int)$request->is_header);
		}
		    if($invent_saved){
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


	public static function delete_invent($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) { 
				return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->invent_id) && (int)$request->invent_id>0){
			$invent=new Invent($request->invent_id);
			//if($invent->status>1) return array("status"=>"err","err"=>"Нельзя удалить инвентаризацию, которая в работе или завершена");
			$res0=$db->query("update sklad_details set invent_blocked=0 where sklad_id in (select sklad_id from invent where id=?i) 
			and detail_id in (select detail_id from invent_details where invent_id=?i and status>19)",(int)$request->invent_id,(int)$request->invent_id);
			$res1=$db->query("delete from invent_details where invent_id=?i",(int)$request->invent_id);
			$res2=$db->query("delete from invent where id=?i and company_id=?i",(int)$request->invent_id,(int)$_SESSION['main_company']);
				if ($res2 && $res1){
				    $ret['status']="ok";
				    //$res3=$db->query("update invents set deleted=1 where id=?i and main_company=?i",(int)$request->invent_id,(int)$_SESSION['main_company']);
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

	public static function get_invents($request) {
	    $db = DB::getInstance();
	    $sql="select i.id,i.status,i.sklad_id,i.descr,i.create_date,i.update_date,i.type,s.name as sklad_name,ddk.invent_positions,ddk.invent_pos_count,ddk.invent_pos_sum
		    from invent i
			LEFT JOIN (SELECT invent_id,COUNT(detail_id) AS invent_positions,SUM(count_sklad) AS invent_pos_count,sum(price*count_sklad)
				as invent_pos_sum FROM invent_details where deleted=0 GROUP BY invent_id) AS ddk ON (ddk.invent_id=i.id)
		    left join sklad s on (s.id=i.sklad_id)
			where i.company_id=?i and i.deleted=0 ";
		
			if(!empty($request->search_invent_date_from)) {
				$date_from=date("Y-m-d",strtotime($request->search_invent_date_from));
				$sql.=" and i.create_date>='".$date_from."'";
			}
			else {
				$date_from=date("Y-m-d",strtotime("1 month ago"));
				$sql.=" and i.create_date>='".$date_from."'";
			}
			if(!empty($request->search_invent_date_to)) {
				$date_to=date("Y-m-d",strtotime($request->search_invent_date_to));
				$sql.=" and i.create_date<='".$date_to." 23:59:59'";
			}
			else { 
				$date_to=date("Y-m-d");
				$sql.=" and i.create_date<='".$date_to." 23:59:59'";
			}
			if(!empty($request->search_invent_sklad_name)){
				$sql_cl="select id from sklad where company_id=?i and name like ?s";
				$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],'%'.$request->search_invent_sklad_name.'%');
				if($res_cl){
					$search_sklads=array_column($res_cl,"id");
					$sql.=" and i.sklad_id in (".implode($search_sklads).")";
				}
				$ret['search_invent_sklad_name']=$request->search_invent_sklad_name;
			}
			$sql.=" order by i.create_date desc";
			$res=$db->getAll($sql,$_SESSION['main_company']);
		
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['invents']=$ret_res;
			$ret['msg']="";
			$ret['search_invent_date_to']=$date_to;
			$ret['search_invent_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['invents']=array();
			$ret['search_invent_date_to']=$date_to;
			$ret['search_invent_date_from']=$date_from;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_invent($request) {
	    $db = DB::getInstance();
			if(empty($request->invent_id) || (int)$request->invent_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select i.descr,s.id as sklad_id,s.name as sklad_name,i.type from invent i left join sklad s on (s.id=i.sklad_id) where i.id=?i and i.company_id=?i and i.deleted=0";
	    $res=$db->getRow($sql,(int)$request->invent_id,$_SESSION['main_company']);
	    if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['invent']=$res;
			$ret['invent_users']=$db->getAll("select iu.id,iu.is_header,u.id as user_id,u.name,u.lastname,u.middlename from invent_users iu
				left join users u on (u.id=iu.user_id)
				where iu.invent_id=?i",(int)$request->invent_id);
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['invent']=[];
			$ret['invent_users']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function add_invent_user($request){
		$db = DB::getInstance();
		if(empty($request->invent_id) || empty($request->user_id)){
			return self::_error_arr("Недостаточно параметров");
		}
		$is_my_invent=$db->getOne("select id from invent where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->invent_id);
		if((int)$is_my_invent==0){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		}
		$user_ids=$db->getCol("select user_id from user_companys where company_id=?i",$_SESSION['main_company']);
		if(!in_array($request->user_id,$user_ids)){
			return self::_error_arr("Неправильно заданы параметры, не ваш пользователь");
		}
	    //$sql="select id,username,roles,create_date,company_id,name,middlename,lastname,email,phone,mphone from users where id in (?a)";
	    //$comp_users=$db->getAll($sql,$user_ids);
		$is_user_id=$db->getOne("select id from invent_users where user_id=?i and invent_id=?i",$request->user_id,$request->invent_id);
		if((int)$is_user_id>0){
			$invent_user=new InventUser((int)$is_user_id);
		}
		else {
			$invent_user=new InventUser();
		}
		$invent_user->invent_id=(int)$request->invent_id;
		$invent_user->user_id=(int)$request->user_id;
		$invent_user->save();
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		return $ret;
	}

	public static function get_invent_details($request){
		$db = DB::getInstance();
		if(empty($request->invent_id) || (int)$request->invent_id==0) {
			return self::_error_arr("Недостаточно параметров");
		}
		$invent=new Invent((int)$request->invent_id);
		//$invent_details_count=$db->getOne("select count(id) from invent_details where invent_id=?i",(int)$request->invent_id);
		//$is_my_invent=$db->getOne("select id from invent where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->invent_id);
		if((int)$invent->company_id!=$_SESSION['main_company']){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		}
		$sql="select * from invent_details where invent_id=?i ?p";
		$parsed="";
		if(!empty($request->search_article)){
			if((int)$invent->status==20 || (int)$invent->status==30) $parsed.=$db->parse(" and article like ?s and status=?i",'%'.$request->search_article.'%',$invent->status);
			else $parsed.=$db->parse(" and article like ?s",'%'.$request->search_article.'%');
		}
		else {
			if((int)$invent->status==20 || (int)$invent->status==30) 
				$parsed.=$db->parse(" and status=?i",$invent->status);
			
		}
		if(!empty($request->search_brand)){
			$parsed.=$db->parse(" and brand like ?s","%".$request->search_brand."%");
		}
		if(!isset($request->show_zero)){
			// первый запрос по умолчанию 0 остатки включены но непередается параметр
			$request->show_zero=1;
		}
		if(!empty($request->show_zero) && $request->show_zero){
			$parsed.=$db->parse(" and count_sklad>=0");
		}
		else {
			$parsed.=$db->parse(" and count_sklad>0");
		}
		if(!empty($request->search_name)){
			$parsed.=$db->parse(" and name like ?s","%".$request->search_name."%");
		}
		if(!empty($request->search_code)){
			$sklad_detail_id=$db->getOne("select detail_id from sklad_details where sklad_id in (select sklad_id from invent where id=?i) and (ean13=?s or my_code=?s)",(int)$request->invent_id,$request->search_code,$request->search_code);
			if($sklad_detail_id){
				$parsed.=$db->parse(" and detail_id=?i",$sklad_detail_id);
			}
			else
				$parsed.=$db->parse(" and (ean13=?s or my_code=?s)",$request->search_code,$request->search_code);
		}
		$parsed.=" order by brand";
		$invent_details_count=$db->getOne("select count(id) from invent_details where invent_id=?i ?p",(int)$request->invent_id,$parsed);

		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$invent_details_count;
	    $pages=ceil($invent_details_count/$page_size);
	    if(isset($request->selected_page) && (int)$request->selected_page<=(int)$pages) {
			$parsed.=$db->parse(" limit ?i,?i",$page_size*((int)$request->selected_page-1),$page_size);
	    }
	    else
			$parsed.=" limit 0,".$page_size;

		if(empty($request->selected_page)) $request->selected_page=1;
		//echo $sql." ".$parsed."\n";
		$invent_details=$db->getAll($sql,(int)$request->invent_id,$parsed);
		if(count((array)$invent_details)>0){
			return array("status"=>"ok","invent_details"=>$invent_details,"msg"=>"","search_code"=>$request->search_code,"search_article"=>$request->search_article,"search_name"=>$request->search_name,"search_brand"=>$request->search_brand,"invent_pages"=>$pages,"details_count"=>(int)$invent_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
		else {
			//echo $sql." ".$parsed."\n";
			if($invent_details_count==0 && empty($request->search_name) && empty($request->search_article) && empty($request->search_brand) && empty($request->search_code)){
				// необходимо создать детали со склада
				if((int)$invent->type==1) $status=0;
				if((int)$invent->type==2) $status=1;
				$sklad_details=$db->getAll("select * from sklad_details where sklad_id=(select sklad_id from invent where id=?i) and deleted=0",$request->invent_id);
				$invent_details_sql="insert into invent_details (invent_id,article,brand,detail_id,brand_id,name,count_sklad,price,status,detail_markup_price,ean13,my_code) values ?p";
				$i=0;
				$parsed_part="";
				foreach($sklad_details as $sd_key=>$sd_val){
					if($i>0) $parsed_part.=",";
					//$invent_details_sql.="(".$request->invent_id.",
					//'".$sd_val['article']."','".$sd_val['brand']."',".$sd_val['detail_id'].",".$sd_val['brand_id'].",
					//'".$sd_val['name']."',".$sd_val['count'].",'".$sd_val['price']."')";
					$parsed_part.=$db->parse("(?i,?s,?s,?i,?i,?s,?i,?s,?i,?s,?s,?s)",$request->invent_id,$sd_val['article'],$sd_val['brand'],$sd_val['detail_id'],$sd_val['brand_id'],$sd_val['name'],$sd_val['count'],$sd_val['price'],$status,$sd_val['detail_markup_price'],$sd_val['ean13'],$sd_val['my_code']);
					$i++;
				}
				//file_put_contents("/var/log/shop/api/get_invent_details.log",$invent_details_sql.$parsed_part,FILE_APPEND);
				//return array("status"=>"ok","msg"=>"","sql"=>$invent_details_sql,"parsed"=>$parsed_part);
				if(count((array)$sklad_details)>0){
					$res=$db->query($invent_details_sql,$parsed_part);
				}
				$invent_details=$db->getAll("select * from invent_details where invent_id=?i limit 0,".(int)$page_size,(int)$request->invent_id);
				$invent_details_count=$db->getOne("select count(id) from invent_details where invent_id=?i ?p",(int)$request->invent_id,$parsed);
				$pages=ceil($invent_details_count/$page_size);
				if(count((array)$invent_details)>0){
					return array("status"=>"ok","invent_details"=>$invent_details,"msg"=>"","invent_pages"=>$pages,"details_count"=>(int)$invent_details_count,"selected_page"=>(int)$request->selected_page,"search_code"=>$request->search_code,"search_article"=>$request->search_article,"search_brand"=>$request->search_brand,"search_name"=>$request->search_name,"invent_pages"=>$pages,"details_count"=>(int)$invent_details_count,"show_zero"=>(int)$request->show_zero);
				}
				else {
					return self::_error_arr("На складе нет деталей");
				}
			}
			else return array("status"=>"ok","invent_details"=>$invent_details,"search_code"=>$request->search_code,"msg"=>"","search_article"=>$request->search_article,"search_brand"=>$request->search_brand,"search_name"=>$request->search_name,"invent_pages"=>$pages,"details_count"=>(int)$invent_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
	}

	public static function get_invent_details_xls($request){
		$db = DB::getInstance(); 
		
		if((int)$request->invent_id>0){
			$pre_invent_details=$db->getAll("select article, brand, name, count_sklad from invent_details where invent_id=?i and status=20",$request->invent_id);
			$invent_details=array();
			foreach($pre_invent_details as $inv_det_key=>$invent_detail){
				$invent_details[$inv_det_key]['article']=$invent_detail['article'];
				$invent_details[$inv_det_key]['brand']=$invent_detail['brand'];
				$invent_details[$inv_det_key]['name']=$invent_detail['name'];
				$invent_details[$inv_det_key]['count_fact']="";
				$invent_details[$inv_det_key]['count_sklad']=$invent_detail['count_sklad'];
			}
			$csv = implode(",", array_keys(reset($invent_details))) . PHP_EOL;
			foreach ($invent_details as $row) {
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
			file_put_contents("/tmp/export_invent_".$request->invent_id.".csv",$csv);
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

			$spreadsheet = $reader->load("/tmp/export_invent_".$request->invent_id.".csv");
			$writer = new Xlsx($spreadsheet);
			$writer->save("/tmp/export_invent_".$request->invent_id.".xlsx");

			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/export_invent_".$request->invent_id.".xlsx"));
			unlink("/tmp/export_invent_".$request->invent_id.".xlsx");
			unlink("/tmp/export_invent_".$request->invent_id.".csv");
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}
		else {
			return self::_error_arr("Не указан инвентаризационна опись для экспорта");
		}
	}

	public static function save_invent_detail($request){
		$db = DB::getInstance();
		if(empty($request->invent_detail_id) || (int)$request->invent_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		$sql="update invent_details set count_fact=?i,otklonenie=?i where id=?i";
		$res=$db->query($sql,$request->fact_count,$request->otklonenie,$request->invent_detail_id);
		if($db->affectedRows()<1){
			return self::_error_arr("Не удалось сохранить данные");
		}
		else {
			return array("status"=>"ok","msg"=>"");
		}
	}

	public static function add_invent_detail_to_start($request){
		$db = DB::getInstance();
		if(empty($request->invent_detail_id) || (int)$request->invent_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		$invent=$db->getRow("select i.status,id.status as invent_detail_status
		 from invent i left join invent_details id on (id.id=?i) 
		 where i.id=(select invent_id from invent_details where id=?i)", (int)$request->invent_detail_id,(int)$request->invent_detail_id);
		if((int)$invent['status']>1){
			return self::_error_arr("Инвентаризация уже идет, нельзя в нее добавлять детали, завершите инвентаризацию, после создайте новую, в которую включите данную деталь со склада");
		}
		if((int)$invent['invent_detail_status']==0) $status=1;
		else $status=0;
		if($db->query("update invent_details set status=?i where id=?i",$status,(int)$request->invent_detail_id)){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return self::_error_arr("не удалось включить деталь в инвентаризацию");
		}
	}

	public static function invent_detail_processed($request){
		$db = DB::getInstance();
		if(empty($request->invent_detail_id) || (int)$request->invent_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		$processed=$db->getOne("select processed from invent_details where id=?i", (int)$request->invent_detail_id);
		if($processed){
			$res=$db->query("update invent_details set processed=0 where id=?i",(int)$request->invent_detail_id);
		}
		else {
			$res=$db->query("update invent_details set processed=1 where id=?i",(int)$request->invent_detail_id);
		}
		if($res){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return self::_error_arr("Ошибка при выполнении запроса");
		}
	}

	public static function start_invent($request){
		$db = DB::getInstance();
		if(empty($request->invent_id) || (int)$request->invent_id==0){
			return self::_error_arr("Не указан номер инвентаризации");
		}
		$invent=new Invent((int)$request->invent_id);
		if((int)$invent->company_id!=$_SESSION['main_company']){
			return self::_error_arr("Не хватает прав, не ваша инвентаризация");
		}
		$details=$db->getAll("select * from invent_details where invent_id=?i and status=1",(int)$request->invent_id);
		if($invent->type==1 && count((array)$details)<1){
			return self::_error_arr("Поскольку инвентаризация является частичной, необходимо выбрать детали для проведения инвентаризации");
		}
		$det_to_block=array();
		$dets_status=array();
		foreach($details as $det_key => $det_val){
			$det_to_block[]=$det_val['detail_id'];
			$dets_status[]=$det_val['id'];
		}
		$res1=$db->query("update sklad_details set invent_blocked=1 where sklad_id=?i and detail_id in (?b)",$invent->sklad_id,$det_to_block);
		if(!$res1){
			return self::_error_arr("произошла ошибка при работе с базой данных");
		}
		$res2=$db->query("update invent_details set status=20 where id in (?b)",$dets_status);
		if(!$res2){
			return self::_error_arr("произошла ошибка при работе с базой данных");
		}
		$res3=$db->query("delete from invent_details where status=0 and invent_id=?i",(int)$request->invent_id);
		$invent->status=20;
		$invent->save();
		return array("status"=>"ok","msg"=>"");
	}

	public static function invent_submit($request){
		$db = DB::getInstance();
		if(empty($request->invent_id)) {
			return self::_error_arr("Недостаточно параметров");
		}
		/*$is_my_invent=$db->getRow("select id,sklad_id from invent where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->invent_id);
		if((int)$is_my_invent['id']==0){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		} */
		$invent=new Invent((int)$request->invent_id);
		if((int)$invent->company_id!=$_SESSION['main_company']){
			return self::_error_arr("Не хватает прав, не ваша инвентаризация");
		}
		$invent_details=$db->getAll("select * from invent_details where invent_id=?i and status=20",(int)$request->invent_id);
		$det_to_block=array();
		$dets_status=array();
		foreach($invent_details as $id_key=>$id_val){
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
		$res1=$db->query("update sklad_details set invent_blocked=0 where sklad_id=?i and detail_id in (?b)",$invent->sklad_id,$det_to_block);
		if(!$res1){
			return self::_error_arr("произошла ошибка при работе с базой данных");
		}
		$res2=$db->query("update invent_details set status=30 where id in (?b)",$dets_status);
		if(!$res2){
			return self::_error_arr("произошла ошибка при работе с базой данных");
		}
		if(count((array)$spisanie_details)>0){
			$doc_id_s=$db->getOne("select id from document where id=(select document_id_s from invent where id=?i) and deleted=0 and type_id=3",(int)$request->invent_id);
			if((int)$doc_id_s==0){
				$new_doc_s=array();
				$new_doc_s['company_id']=$_SESSION['main_company'];
				$new_doc_s['type_id']=3;
				$new_doc_s['sklad_id']=$invent->sklad_id;
				$new_doc_s['document_date']=date("Y-m-d H:i:s");
				$new_doc_s['comment']="Сформировано из инвентаризационной описи №".(int)$invent->id;
				$res_doc=Documents::save_document((object)$new_doc_s);
				if($res_doc['status']=="ok" && (int)$res_doc['document_id']>0) {
					$doc_id_s=(int)$res_doc['document_id'];
					$db->query("update invent set document_id_s=?i where id=?i",$doc_id_s,(int)$request->invent_id);
				}
				else return self::_error_arr("Не удалось сформировать документ списания");
			}
		}
		if(count((array)$oprihod_details)>0){
			$doc_id_o=$db->getOne("select id from document where id=(select document_id_o from invent where id=?i) and deleted=0 and type_id=5",(int)$request->invent_id);
			if((int)$doc_id_o==0){
				$new_doc_o=array();
				$new_doc_o['company_id']=$_SESSION['main_company'];
				$new_doc_o['type_id']=5;
				$new_doc_o['sklad_id']=$invent->sklad_id;
				$new_doc_o['document_date']=date("Y-m-d H:i:s");
				$new_doc_o['comment']="Сформировано из инвентаризационной описи №".(int)$invent->id;
				$res_doc=Documents::save_document((object)$new_doc_o);
				if($res_doc['status']=="ok" && (int)$res_doc['document_id']>0){ 
					$doc_id_o=(int)$res_doc['document_id'];
					$db->query("update invent set document_id_o=?i where id=?i",$doc_id_o,(int)$request->invent_id);
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
				$new_doc_det['invent_detail_id']=$sd_val['id'];
				$new_doc_det['sklad_id']=$invent->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					if($res_doc_det['err']=="Такой детали нет на складе"){
						$sklad_detail=$db->getRow("select * from sklad_details where article=?s and brand=?s and sklad_id=?i",$sd_val['article'],$sd_val['brand'],$invent->sklad_id);
						if($sklad_detail){
							$new_doc_det['detail_id']=$sklad_detail['detail_id'];
							$db->query("update invent_details set detail_id=?i where id=?i",$new_doc_det['detail_id'],$sd_val['id']);
							$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
							if($res_doc_det['status']=="err"){
								return array("status"=>"err","err"=>$res_doc_det['err'],"err_detail"=>$sd_val);
							}
							else {
								$res1=$db->query("update sklad_details set invent_blocked=0 where sklad_id=?i and detail_id=?i",$invent->sklad_id,$new_doc_det['detail_id']);
							}
						}
					}
					else {
						return array("status"=>"err","err"=>$res_doc_det['err'],"err_detail"=>$sd_val);
					}
				}
				$db->query("update invent_details set document_id=?i where id=?i",$doc_id_s,$sd_val['id']);
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
				$new_doc_det['sklad_id']=$invent->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return array("status"=>"err","err"=>$res_doc_det['err'],"err_detail"=>$sd_val);
				}
				$db->query("update invent_details set document_id=?i where id=?i",$doc_id_s,$sd_val['id']);
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
				$new_doc_det['invent_detail_id']=$od_val['id'];
				$new_doc_det['sklad_id']=$invent->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return array("status"=>"err","err"=>$res_doc_det['err'],"err_detail"=>$od_val);
					//return self::_error_arr($res_doc_det['err']." ".print_r($new_doc_det,true));
				}
				$db->query("update invent_details set document_id=?i where id=?i",$doc_id_o,$od_val['id']);
			}
			else {
				//Документ у этой детали уже сформирован необходимо, отредактировать деталь в этом документе если изменилось отклонение
				//проверим не изменился ли тип документа (было списание стало оприходование или наоборот)
				$document_data=$db->getRow("select type_id from document where id=?i",$od_val['document_id']);
				if((int)$document_data['type_id']==3){
					// было списание, надо удалить из старого документа деталь и создать новый документ или выбрать из уже созданных, связанный с этой инвентаризацией
					$detail_id_to_del=$db->getOne("select id from document_details where document_id=?i and detail_id=?i",$od_val['document_id'],$od_val['detail_id']);
					$res_doc_det_del=DocumentDetails::delete_document_detail((object)array("document_detail_id"=>$detail_id_to_del));
					$db->query("update invent_details set document_id=?i where id=?i",$doc_id_o,$od_val['id']);
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
				$new_doc_det['sklad_id']=$invent->sklad_id;
				$res_doc_det=DocumentDetails::save_document_detail((object)$new_doc_det);
				if($res_doc_det['status']=="err"){
					return array("status"=>"err","err"=>$res_doc_det['err'],"err_detail"=>$od_val);
				}
				$db->query("update invent_details set document_id=?i where id=?i",$doc_id_o,$od_val['id']);
			}
		}
		$invent->status=30;
		$invent->save();
		return array("status"=>"ok","msg"=>"","spisanie_details"=>$spisanie_details,"oprihod_details"=>$oprihod_details);
	}

}



?>
