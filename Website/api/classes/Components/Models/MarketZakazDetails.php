<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\MarketZakazDetail;
use Sort1API\Components\MarketZakaz;
use Sort1API\Components\BasketDetail;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\Payments;
use Sort1API\Components\ZzapApi;

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class MarketZakazDetails extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_market_zakaz_detail($request) {
            $db = DB::getInstance();
	           //$zakaz=new Zakaz();
      	    if (isset($request->zakaz_id)) $zakaz_id=$request->zakaz_id;
      	    else {
      		      return self::_error_arr("Не могу добавить деталь без номера заказа");
			}
			if($_SESSION['roles']<10){
				$zakaz_data=$db->getRow("select id,delivery_type,delivery_type_id,fullfilment_id from market_zakaz where id=?i and main_company_id=?i",$zakaz_id,$_SESSION['main_company']);
				$is_your=$zakaz_data['id'];
				if(!$is_your){
					return self::_error_arr("Нельзя менять чужие данные");
				}
			}
			else {
				$zakaz_data=$db->getOne("select id,delivery_type,delivery_type_id,fullfilment_id from market_zakaz where id=?i and main_company_id=?i and company_id=?i",$zakaz_id,$_SESSION['main_company'],$_SESSION['company_id']);
				$is_your=$zakaz_data['id'];
				if((int)$is_your==0){
					return self::_error_arr("Не ваши данные");
				}
			}  

			if(isset($zakaz_id) && $zakaz_id>0)
				$zakaz_det=new MarketZakazDetail($zakaz_id);
			else
				if (isset($zakaz_id) && $zakaz_id>0 && isset($detail_id)) {
					$zakaz_det=new MarketZakazDetail($zakaz_id);
				}

			if (isset($request->comment)) $zakaz_det->comment=$request->comment;
			if (isset($request->article)) $zakaz_det->article=$request->article;
			if (isset($request->brand)) $zakaz_det->brand=$request->brand;
      	    if (isset($request->status)){ 
      		    $zakaz_det->status=(int)$request->status;
      	    }
      	    if(isset($deliverer_type)) $zakaz_det->deliverer_type=$deliverer_type;
      	    if(isset($deliverer_id)) $zakaz_det->deliverer_id=$deliverer_id;
            if (isset($request->deliverer_online_profile_id)) $zakaz_det->deliverer_online_profile_id=(int)$request->deliverer_online_profile_id;
      	    $err=$zakaz_det->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1:
          			if (isset($request->zakaz_detail_id) && (int)$request->zakaz_detail_id>0){
                          		$status="ok"; $msg="Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok";
          			    if(empty($msg)) $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"");
        }

	public static function get_market_zakaz_detail($request) {
	    $db = DB::getInstance();

	    $sql="select * from market_zakaz_details where market_zakaz_id=?i";
	    $res=$db->getAll($sql,(int)$request->zakaz_id);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakaz_id']=(int)$request->zakaz_id;
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
    		$ret['zakaz_details']=$res;
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_zakaz_detail_by_id($request) {
	    $db = DB::getInstance();
		if(empty($request->zakaz_details_id) || (int)$request->zakaz_details_id==0) return self::_error_arr("Не указан id детали");
	    $sql="select * from zakaz_details where id=?i";
	    $res=$db->getRow($sql,(int)$request->zakaz_details_id);
		$is_your=$db->getOne("select id from zakaz where id=?i and main_company_id=?i",$res['zakaz_id'],$_SESSION['main_company']);
		if(!$is_your){
			return self::_error_arr("Не ваши данные");
		}
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakaz_details']=$res;
			$ret['payment']=$db->getRow("select * from payment where zakaz_id=?i",$res['zakaz_id']);
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_market_zakaz_details($request) {
	    $db = DB::getInstance();
	    $zakaz=new MarketZakaz((int)$request->zakaz_id);
		if(empty($request->zakaz_id)) self::_error_arr("Не указан номер заказа");
		$is_your=$db->getOne("select id from market_zakaz where id=?i and main_company_id=?i",(int)$request->zakaz_id,$_SESSION['main_company']);
		if(!$is_your) self::_error_arr("Не правильно указан номер заказа");
	    $sql_count="select count(market_zakaz_detail_id) from market_zakaz_details where market_zakaz_id=?i and reorder_detail_id=0 and status<>100 and status<>102 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->zakaz_id,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$request->zakaz_id);
		// ,dz.document_details_id,dz.document_id  .... left join doc_detail_to_zakaz_detail dz on (dz.zakaz_details_id=zd.id)
		$sql="select zd.*,z.delivery_type_id from market_zakaz_details zd 
			left join market_zakaz z on (zd.market_zakaz_id=z.id)
			where zd.market_zakaz_id=?i and zd.reorder_detail_id=0 "; //and zd.status<>100 and zd.status<>102 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (zd.article like ?s or zd.name like ?s)";
	    $sql.=" order by zd.create_date,zd.deliverer_id,zd.name";
	    if(isset($request->page_size)) $page_size=$request->page_size;
		else $page_size=250;
	    $pages=ceil($details_count/$page_size);
		if(!isset($request->format)){
			if(isset($request->page)) {
				$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
			}
			else
				$sql.=" limit 0,".$page_size;
		}
		
	    if (!empty($request->search) && $request->search!="undefined") {
    		$res=$db->getAll($sql,(int)$request->zakaz_id,'%'.trim($request->search).'%','%'.trim($request->search).'%');
    		$ret['search']=$request->search;
	    }
	    else
		    $res=$db->getAll($sql,(int)$request->zakaz_id);
			
	    $deliverers=array();
	    $deliverers_id=array();
		$zakaz_details_ids=array();
	    foreach($res as $res_key=>$res_val){
		      $deliverers_id[$res_val['deliverer_type']][]=$res_val['deliverer_id'];
			  $zakaz_details_ids[]=$res_val['id'];
	    }
		$document_details=$db->getAll("select document_id,document_details_id,zakaz_details_id,count from doc_detail_to_zakaz_detail where zakaz_details_id in (?b)",$zakaz_details_ids);
		$doc_dets=array();
		$docs=array();
		$oprih_count=array();
		foreach($document_details as $doc_det_key=>$doc_det_val){
			if(!isset($doc_dets[$doc_det_val['zakaz_details_id']])) $doc_dets[$doc_det_val['zakaz_details_id']]=array();
			if(!isset($docs[$doc_det_val['zakaz_details_id']])) $docs[$doc_det_val['zakaz_details_id']]=array();
			if(!isset($oprih_count[$doc_det_val['zakaz_details_id']])) $oprih_count[$doc_det_val['zakaz_details_id']]=0;
			$doc_dets[$doc_det_val['zakaz_details_id']][]=$doc_det_val['document_details_id'];
			$docs[$doc_det_val['zakaz_details_id']][]=$doc_det_val['document_id'];
			$oprih_count[$doc_det_val['zakaz_details_id']]+=$doc_det_val['count'];
		}
		//echo print_r($deliverers_id,true);
		$sql="";
	    foreach($deliverers_id as $deliv_type=>$deliv_val){
    		switch($deliv_type){
    		    case 1: $sel_table="sklad"; $sql="select id,name from sklad where id in (?b)"; break;
    		    case 2: $sel_table="price_list"; $sql="select id,name from price_list where id in (?b)"; break;
    		    case 3: $sel_table="user_api_config"; $sql="select plugin_id,name from user_api_config where plugin_id in (?b)"; break;
    		}
    		//echo $sql.print_r($deliv_val,true)."\n";
    		if($sql!="") $pre_deliverers=$db->getAll($sql,$deliv_val);
    		//echo print_r($pre_deliverers,true);
    		foreach($pre_deliverers as $pre_del_key=>$pre_del_val){
    		    if($deliv_type==3) $deliverers[$deliv_type][$pre_del_val['plugin_id']]=$pre_del_val['name'];
    		    else $deliverers[$deliv_type][$pre_del_val['id']]=$pre_del_val['name'];
    		}
	    }
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakaz_id']=(int)$request->zakaz_id;
    		$ret['zakaz_details']=$res;
    		$ret['zakaz_pages']=$pages;
			$ret['deliverers']=$deliverers;
			$ret['linked_documents']=$docs;
			$ret['linked_document_details']=$doc_dets;
			$ret['oprih_count']=$oprih_count;
			$ret['zakaz_delivery_type']=$zakaz->delivery_type;
    		$ret['details_count']=(int)$details_count;
    		if (isset($request->page)) $ret['selected_page']=$request->page;
    		$ret['msg']="";
			$count_service=$db->getOne("select count(id) from service_notes where zakaz_id=?i and zakaz_id<>0",$request->zakaz_id);
			if((int)$count_service>0) {
				$ret['is_service']=1;
			}
			else {
				$count_jobs=$db->getOne("select count(id) from zakaz_jobs where zakaz_id=?i and zakaz_id<>0",$request->zakaz_id);
				if((int)$count_jobs>0)
					$ret['is_service']=1;
				else
					$ret['is_service']=0;
			}
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['err']="";
    		$ret['zakaz_id']=(int)$request->zakaz_id;
    		$ret['zakaz_details']=[];
    		$ret['zakaz_pages']=1;
    		$ret['details_count']=0;
	    }
		if(isset($request->format)){
			$export_details=array();$i=0;
			$statuses=$db->getInd("id","select * from zakaz_detail_statuses");
			$deliv_type=array("1"=>"Склад","2"=>"Прайс-лист","3"=>"Онлайн");
			foreach($ret['zakaz_details'] as $zd_key=>$zd_val){
				$i++;
				array_push($export_details,array(
					"N"=>$i,
					"Артикул"=>$zd_val['article'],
					"Бренд"=>$zd_val['brand'],
					"Наименование"=>$zd_val['name'],
					"Цена"=>$zd_val['price'],
					"В заказе"=>$zd_val['count'],
					"Выдано"=>$zd_val['supplied_count'],
					"Отказано"=>$zd_val['rejected_count'],
					"Возврат"=>$zd_val['returned_count'],
					"Оприходовано"=>(isset($ret['oprih_count'][$zd_val['id']])?$ret['oprih_count'][$zd_val['id']]:0),
					"Сумма"=>$zd_val['count']*$zd_val['price'],
					"Срок доставки"=>$zd_val['time'],
					"Статус"=>$statuses[$zd_val['status']]['descr'],
					"Тип поставщика"=>$deliv_type[$zd_val['deliverer_type']],
					"Поставщик"=>$ret['deliverers'][$zd_val['deliverer_type']][$zd_val['deliverer_id']],
					"Акцизный товар"=>((int)$zd_val['is_excise']==1?"да":"нет"),
					"Комментарий"=>$zd_val['comment'],
				));
			}
			$csv = implode(",", array_keys(reset($export_details))) . PHP_EOL;
			foreach ($export_details as $row) {
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
			file_put_contents("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".csv1",$csv);
			$file=base64_encode(mb_convert_encoding($csv,"WINDOWS-1251","UTF-8"));
			//unlink("/tmp/export_documents.csv");
			
			if($request->format=="csv"){
				return array("status"=>"ok","msg"=>"","file"=>$file);
			}
			elseif ($request->format=="xlsx"){
				file_put_contents("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".csv",$csv);
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

				$spreadsheet = $reader->load("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".csv");
				$writer = new Xlsx($spreadsheet);
				$writer->save("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".xlsx");

				$spreadsheet->disconnectWorksheets();
				unset($spreadsheet);
				$file=base64_encode(file_get_contents("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".xlsx"));
				//unlink("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".csv");
				//unlink("/tmp/export_zakaz_".$_SESSION['user_id']."_".$request->zakaz_id.".xlsx");
				return array("status"=>"ok","msg"=>"","file"=>$file);
			}
		}
		else {
			if ($ret['status']=="err") return self::_error_arr($ret['err']);
			else return $ret;
		}
	}

	public static function get_all_zakaz_details($request) {
		$db = DB::getInstance();
		$sql_parsed="";
		$zakaz_parsed="";
		if (!empty($request->search_zakaz_date_from) && $request->search_zakaz_date_from!="undefined") $sql_parsed.=$db->parse(" zd.create_date>=?s ",$request->search_zakaz_date_from);
		else $sql_parsed.=$db->parse(" zd.create_date>=?s ",date("Y-m-d",strtotime("2 days ago")));
		if (!empty($request->search_zakaz_date_to) && $request->search_zakaz_date_to!="undefined") $sql_parsed.=$db->parse(" and zd.create_date<?s ",$request->search_zakaz_date_to." 23:59:59");
		else $sql_parsed.=$db->parse(" and zd.create_date>=?s ",date("Y-m-d H:i:s"));
		if (!empty($request->search_zakaz_client_name) && $request->search_zakaz_client_name!="undefined"){
			$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where (main_company_id=?i or company_id=?i) and btype<=4) and name like ?s";
			$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_zakaz_client_name.'%');
			$zakaz_parsed.=$db->parse(" and zd.company_id in (?b)",array_column($res_cl,"id"));
		}

		$sql_zakaz="select id from zakaz zd where ?p and zd.main_company_id=?i";
		$zakazes=$db->getCol($sql_zakaz,$sql_parsed.$zakaz_parsed,$_SESSION['main_company']);
		$sql_count="select count(detail_id) from zakaz_details zd where ?p";
		
		if (!empty($request->search_zakaz_article) && $request->search_zakaz_article!="undefined") $sql_parsed.=$db->parse(" and zd.article like ?s ","%".$request->search_zakaz_article."%");
		
		$sql_count.=" and zd.reorder_detail_id=0 and zd.zakaz_id in (?b) and zd.status<>100 and zd.status<>102 ";
		$details_count=$db->getOne($sql_count,$sql_parsed,$zakazes);

		$sql="select zd.*,dz.document_details_id,dz.document_id,z.delivery_type_id,z.delivery_type from market_zakaz_details zd 
			left join doc_detail_to_zakaz_detail dz on (dz.zakaz_details_id=zd.id)
			left join market_zakaz z on (zd.zakaz_id=z.id)
			where ?p";
	    $sql.=" and zd.reorder_detail_id=0  and zd.zakaz_id in (?b) and zd.status<>100 and zd.status<>102 order by zd.create_date desc";
	    if(isset($request->page_size)) $page_size=$request->page_size;
		else $page_size=250;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
		      $sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		     $sql.=" limit 0,".$page_size;
		$res=$db->getAll($sql,$sql_parsed,$zakazes);
	    $deliverers=array();
	    $deliverers_id=array();
	    $zakaz_details_ids=array();
	    foreach($res as $res_key=>$res_val){
		      $deliverers_id[$res_val['deliverer_type']][]=$res_val['deliverer_id'];
			  $zakaz_details_ids[]=$res_val['id'];
	    }
		$document_details=$db->getAll("select document_id,document_details_id,zakaz_details_id,count from doc_detail_to_zakaz_detail where zakaz_details_id in (?b)",$zakaz_details_ids);
		$doc_dets=array();
		$docs=array();
		$oprih_count=array();
		foreach($document_details as $doc_det_key=>$doc_det_val){
			if(!isset($doc_dets[$doc_det_val['zakaz_details_id']])) $doc_dets[$doc_det_val['zakaz_details_id']]=array();
			if(!isset($docs[$doc_det_val['zakaz_details_id']])) $docs[$doc_det_val['zakaz_details_id']]=array();
			if(!isset($oprih_count[$doc_det_val['zakaz_details_id']])) $oprih_count[$doc_det_val['zakaz_details_id']]=0;
			$doc_dets[$doc_det_val['zakaz_details_id']][]=$doc_det_val['document_details_id'];
			$docs[$doc_det_val['zakaz_details_id']][]=$doc_det_val['document_id'];
			$oprih_count[$doc_det_val['zakaz_details_id']]+=$doc_det_val['count'];
		}
		$sql="";
	    foreach($deliverers_id as $deliv_type=>$deliv_val){
    		switch($deliv_type){
    		    case 1: $sel_table="sklad"; $sql="select id,name from sklad where id in (?b)"; break;
    		    case 2: $sel_table="price_list"; $sql="select id,name from price_list where id in (?b)"; break;
    		    case 3: $sel_table="user_api_config"; $sql="select plugin_id,name from user_api_config where plugin_id in (?b)"; break;
    		}
    		//echo $sql.print_r($deliv_val,true)."\n";
    		if($sql!="") $pre_deliverers=$db->getAll($sql,$deliv_val);
    		//echo print_r($pre_deliverers,true);
    		foreach($pre_deliverers as $pre_del_key=>$pre_del_val){
    		    if($deliv_type==3) $deliverers[$deliv_type][$pre_del_val['plugin_id']]=$pre_del_val['name'];
    		    else $deliverers[$deliv_type][$pre_del_val['id']]=$pre_del_val['name'];
    		}
	    }
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['zakaz_id']=(int)$request->zakaz_id;
    		$ret['zakaz_details']=$res;
    		$ret['zakaz_pages']=$pages;
			$ret['deliverers']=$deliverers;
			$ret['linked_documents']=$docs;
			$ret['linked_document_details']=$doc_dets;
			$ret['zakaz_delivery_type']=$zakaz->delivery_type;
    		$ret['details_count']=(int)$details_count;
    		if (isset($request->page)) $ret['selected_page']=$request->page;
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['err']="";
    		$ret['zakaz_id']=(int)$request->zakaz_id;
    		$ret['zakaz_details']=[];
    		$ret['zakaz_pages']=1;
    		$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_market_zakaz_detail_by_manager($request) {
	    $fields="";
	    $db = DB::getInstance();
		// echo($request);
	    if (isset($request->market_zakaz_id)) {
			$zakaz=new MarketZakaz($request->market_zakaz_id);
			if (isset($request->market_zakaz_id)) {
				$id=(int)$request->market_zakaz_id;
			}
      	}
	    if ($zakaz->id>0 && isset($id) && $id>0){
			$zakaz_detail=new MarketZakazDetail($zakaz->id);
			$updated=0;
			$zakaz_detail->status=142;
			$updated=$zakaz_detail->save(); 

    		if ($updated==1){
				$sql_zakaz_status="update market_zakaz set status=142 where id=?i";
                $db->query($sql_zakaz_status,$zakaz_detail->market_zakaz_id);
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']=$updated;
    		}

			$market_delete = new stdClass();
			$market_delete->zakaz_id = $zakaz_detail->market_zakaz_id;
			$market_delete->status = $zakaz_detail->status;
			$market_delete->comment = $zakaz_detail->status;
			$delete_marketplace = ZzapApi::set_order_status($market_delete);
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_zakaz_detail_statuses(){
	    $db = DB::getInstance();
	    $ret=$db->getAll("select id,descr,client_descr from zakaz_detail_statuses");
	    return $ret;
	}
}
?>
