<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\Zakaz;
use Sort1API\Components\BasketDetail;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\Payments;

//require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class ZakazDetails extends Model {

        public static function check_roles($role){
			$main_user=new User((int)$_SESSION['user_id']);
			if ($main_user->roles<=$role) return $role;
			else return $main_user->roles;
		}

		public static function save_zakaz_order($request){
			$db = DB::getInstance();

			if(isset($request->zakaz_details_id)) $zakaz_details_id=$request->zakaz_details_id;
			if(isset($zakaz_details_id) && $zakaz_details_id>0)
				$zakaz_det=new ZakazDetail($zakaz_details_id);
			else self::_error_arr("Выберите деталь из заказа");

			if(isset($request->zakaz_order)) $zakaz_det->zakaz_order=$request->zakaz_order;

			$err=$zakaz_det->save();
			switch($err) {
				case 10: $status="err"; $msg="Данные не изменились\n"; break;
				case 1:
					if (isset($request->zakaz_details_id) && (int)$request->zakaz_details_id>0){
								$status="ok";
								$msg="";
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

        public static function save_zakaz_detail($request) {
            $db = DB::getInstance();
	           //$zakaz=new Zakaz();
      	    if (isset($request->zakaz_id)) $zakaz_id=$request->zakaz_id;
      	    else {
      		      return self::_error_arr("Не могу добавить деталь без номера заказа");
			}
			if($_SESSION['roles']<10){
				$zakaz_data=$db->getRow("select id,delivery_type,delivery_type_id,fullfilment_id,company_id from zakaz where id=?i and main_company_id=?i",$zakaz_id,$_SESSION['main_company']);
				$is_your=$zakaz_data['id'];
				if(!$is_your){
					return self::_error_arr("Нельзя менять чужие данные");
				}
			}
			else {
				$zakaz_data=$db->getRow("select id,delivery_type,delivery_type_id,fullfilment_id,company_id from zakaz where id=?i and main_company_id=?i and company_id=?i",$zakaz_id,$_SESSION['main_company'],$_SESSION['company_id']);
				$is_your=$zakaz_data['id'];
				if((int)$is_your==0){
					return self::_error_arr("Не ваши данные");
				}
			}  
      	    if (isset($request->detail_id)) $detail_id=(int)$request->detail_id;
      	    if (isset($request->deliverer_id)) $deliverer_id=(int)$request->deliverer_id;
			if (isset($request->deliverer_type)) $deliverer_type=(int)$request->deliverer_type;
			if(isset($request->zakaz_details_id)) $zakaz_details_id=$request->zakaz_details_id;
			if(isset($zakaz_details_id) && $zakaz_details_id>0)
				$zakaz_det=new ZakazDetail($zakaz_details_id);
			else
				if (isset($zakaz_id) && $zakaz_id>0 && isset($detail_id)) {
					$zakaz_det=new ZakazDetail(0,$zakaz_id,$detail_id,$deliverer_type,$deliverer_id);
				}
      	    if (isset($request->article)) $zakaz_det->article=$request->article;
      	    if (isset($request->brand)) $zakaz_det->brand=$request->brand;
      	    if (isset($request->brand_id)) $zakaz_det->brand_id=$request->brand_id;
			if (isset($request->name)) $zakaz_det->name=$request->name;
			$zakaz_detail_old_price=$zakaz_det->price;
			$zakaz_detail_old_count=$zakaz_det->count; 
			if($zakaz_det->detail_id!=0 && $zakaz_det->is_excise==0 && ((int)$zakaz_data['delivery_type']==1?(int)$zakaz_data['delivery_type_id']:(int)$zakaz_data['fullfilment_id'])>0 && $zakaz_det->id==0){
				$sd_is_excise=$db->getOne("select is_excise from sklad_details where detail_id=?i and sklad_id=?i",(int)$zakaz_det->detail_id,(int)$zakaz_data['delivery_type']==1?(int)$zakaz_data['delivery_type_id']:(int)$zakaz_data['fullfilment_id']);
				if($sd_is_excise!==null) $zakaz_det->is_excise=(int)$sd_is_excise;
			}
      	    if (isset($request->sale_price)) $zakaz_det->price=(float)$request->sale_price;
			else {
				if(isset($request->price))
					$zakaz_det->price=(float)$request->price;
			}
			$zakaz_det->first_price=(float)$zakaz_det->price;
			if($zakaz_det->status>=2 && $zakaz_det->status<70){
				// заказ зарезервирован но не выдан, надо изменить резерв у покупателя
				$price_delta=(($zakaz_det->price*$zakaz_det->count)-($zakaz_detail_old_price*$zakaz_detail_old_count));
				//echo "price_delta=$price_delta\n";
				if($price_delta!=0){
					if($_SESSION['roles']>9) return self::_error_arr("Не хватает прав для изменения стоимости уже подтвержденного заказа");
					$company_balance=new CompanyBalance($zakaz_data['company_id']);
					$company_balance->rezerv+=$price_delta;
					if($company_balance->rezerv<0) $company_balance->rezerv=0;
					$company_balance->Save();
				}
			} 
			if (isset($request->dealer_price)) $zakaz_det->dealer_price=$request->dealer_price;
			if($zakaz_det->dealer_price>$zakaz_det->price && $_SESSION['roles']>2 && $_SESSION['roles'] != 20 && $_SESSION['roles'] != 10 && empty($request->detail_discount_from_cashback)) 
				return self::_error_arr("Не стоит продавать дешевле себестоимости, данное действие доступно только администратору
				
				");
			
			if (isset($request->count)) {
				//echo "status=".$zakaz_det->status." ".(int)$zakaz_det->count."!=".(int)$request->count."\n";
				if($zakaz_det->status>1 && $zakaz_det->status<70 && (int)$zakaz_det->count!=(int)$request->count){
					$sklad_det=new SkladDetail($_SESSION['my_sklad_id'],$zakaz_det->detail_id);
					$sklad_det->reserved_count+=($request->count-$zakaz_det->count);
					//echo print_r($sklad_det,true);
					$sklad_det->save();
				}
				$zakaz_det->count=$request->count;

			}
			if (isset($request->max_count)) $zakaz_det->max_count=$request->max_count;
			if (isset($request->min_count)) $zakaz_det->min_count=$request->min_count;
			if (isset($request->multiplicity)) $zakaz_det->multiplicity=$request->multiplicity;
      	    if (isset($request->time)) $zakaz_det->time=(int)$request->time;
			if (isset($request->cashback) && (float)$request->cashback>0 && isset($request->is_cashback) && (int)$request->is_cashback==1) $zakaz_det->cashback=(float)$request->cashback*$zakaz_det->count;
      	    if (isset($request->comment)) $zakaz_det->comment=$request->comment;
      	    if (isset($request->sort1_id)) $zakaz_det->sort1_id=$request->sort1_id;
            if (isset($request->sort1_sreqid)) $zakaz_det->sort1_sreqid=$request->sort1_sreqid;
      	    if (isset($request->detail_id)) $zakaz_det->detail_id=$request->detail_id;
			if (isset($request->ean13)) $zakaz_det->ean13=$request->ean13;
			if (isset($request->my_code)) $zakaz_det->my_code=$request->my_code;
			if (isset($request->is_excise)) $zakaz_det->is_excise=(int)$request->is_excise;
			if (isset($request->document_detail_id)) $zakaz_det->document_detail_id=$request->document_detail_id;
      	    if (isset($request->status)){ 
				if($zakaz_det->deliverer_type==1 && ($request->status==2 || $request->status==3)){
					$zakaz_det->status=40;
				}
				else
      		    $zakaz_det->status=(int)$request->status;
      	    }
      	    if(isset($deliverer_type)) $zakaz_det->deliverer_type=$deliverer_type;
      	    if(isset($deliverer_id)) $zakaz_det->deliverer_id=$deliverer_id;
            if (isset($request->deliverer_online_profile_id)) $zakaz_det->deliverer_online_profile_id=(int)$request->deliverer_online_profile_id;

			if(isset($request->zakaz_order)){
				$zakaz_order = $db->getOne('select MAX(zakaz_order) from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and status<>100 and status<>102',$zakaz_det->zakaz_id);
				$zakaz_det->zakaz_order = $zakaz_order+1;
			}
			if($zakaz_details_id == 0){
				$zakaz_order = $db->getOne('select MAX(zakaz_order) from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and status<>100 and status<>102',$zakaz_det->zakaz_id);
				$zakaz_det->zakaz_order = $zakaz_order+1;
			}
			if($request->status==2 && $zakaz_det->status==40){
				$zakaz_det->status=2;
				$err=$zakaz_det->save();
				$zakaz_det->status=40;
				$err=$zakaz_det->save();
			}
			else{
      	    	$err=$zakaz_det->save();
			}
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1:
          			if(isset($request->id)) { // $request->id - Это id basket_details
          			    $db->query("delete from basket_details where id=?i",$request->id);
          			}
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

	public static function get_zakaz_detail($request) {
	    $db = DB::getInstance();
	    $zakaz=new Zakaz((int)$request->zakaz_id);
		$is_your=$db->getOne("select id from zakaz where id=?i and main_company_id=?i",$zakaz->id,$_SESSION['main_company']);
		if(!$is_your){
			return self::_error_arr("Не ваши данные");
		}
	    $sql="select * from zakaz_details where zakaz_id=?i and detail_id=?i and deliverer_type=?i and deliverer_id=?i";
	    $res=$db->getAll($sql,(int)$request->zakaz_id,(int)$request->detail_id,(int)$request->deliverer_type,(int)$request->deliverer_id);
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

	public static function get_zakaz_details($request) {
	    $db = DB::getInstance();
	    $zakaz=new Zakaz((int)$request->zakaz_id);
		if(empty($request->zakaz_id)) self::_error_arr("Не указан номер заказа");
		$is_your=$db->getOne("select id from zakaz where id=?i and main_company_id=?i",(int)$request->zakaz_id,$_SESSION['main_company']);
		if(!$is_your) self::_error_arr("Не правильно указан номер заказа");
	    $sql_count="select count(detail_id) from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and status<>100 and status<>102 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->zakaz_id,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$request->zakaz_id);
		// ,dz.document_details_id,dz.document_id  .... left join doc_detail_to_zakaz_detail dz on (dz.zakaz_details_id=zd.id)
		$sql="select zd.*,z.delivery_type_id,sd.count as sklad_count from zakaz_details zd 
			left join zakaz z on (zd.zakaz_id=z.id)
			left join sklad_details sd on (sd.detail_id=zd.detail_id and sklad_id=?i and sd.deleted=0)
			where zd.zakaz_id=?i and zd.reorder_detail_id=0 "; //and zd.status<>100 and zd.status<>102 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (zd.article like ?s or zd.name like ?s)";
	    $sql.=" order by zd.zakaz_order";
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
			//$ret['sql']=$db->parse($sql,$_SESSION['my_sklad_id'],(int)$request->zakaz_id,'%'.trim($request->search).'%','%'.trim($request->search).'%');
    		$res=$db->getAll($sql,$_SESSION['my_sklad_id'],(int)$request->zakaz_id,'%'.trim($request->search).'%','%'.trim($request->search).'%');
    		$ret['search']=$request->search;
	    }
	    else{
			//$ret['sql']=$db->parse($sql,$_SESSION['my_sklad_id'],(int)$request->zakaz_id);
		    $res=$db->getAll($sql,$_SESSION['my_sklad_id'],(int)$request->zakaz_id);
		}
	    $deliverers=array();
	    $deliverers_id=array();
		$zakaz_details_ids=array();
	    foreach($res as $res_key=>$res_val){
		      $deliverers_id[$res_val['deliverer_type']][]=$res_val['deliverer_id'];
			  $zakaz_details_ids[]=$res_val['id'];
			  $sdl=$db->getAll("select location,count from sklad_detail_locations where sklad_id=?i and detail_id=?i",(int)$_SESSION['my_sklad_id'],$res_val['detail_id']);
			  if($sdl) $res[$res_key]['sklad_detail_locations']=$sdl;
			  else $res[$res_key]['sklad_detail_locations']=array(); 
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
			$returned_to_dealer_count=$db->getOne("select returned_to_dealer_count from document_details where id=?i",$doc_det_val['document_details_id']);
			$oprih_count[$doc_det_val['zakaz_details_id']]+=($doc_det_val['count']-$returned_to_dealer_count);
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
		if(!empty($request->target) && $request->target=="to_sklad"){
			$zakaz_parsed.=$db->parse(" and zd.company_id=?i",$_SESSION['main_company']);
		}
		else {
			if (!empty($request->search_zakaz_client_name) && $request->search_zakaz_client_name!="undefined"){
				$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where (main_company_id=?i or company_id=?i) and btype<=4) and name like ?s";
				$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],$_SESSION['main_company'],'%'.$request->search_zakaz_client_name.'%');
				$zakaz_parsed.=$db->parse(" and zd.company_id in (?b)",array_column($res_cl,"id"));
			}
		}
		if(isset($request->show_archive) && $request->show_archive==true){
			$parsed_status='';
		}
		else {
			$parsed_status=" and zd.status<>100 and zd.status<>102 ";
		}
		$sql_zakaz="select id from zakaz zd where ?p and zd.main_company_id=?i";
		$zakazes=$db->getCol($sql_zakaz,$sql_parsed.$zakaz_parsed,$_SESSION['main_company']);
		$sql_count="select count(detail_id) from zakaz_details zd where ?p";
		
		if (!empty($request->search_zakaz_article) && $request->search_zakaz_article!="undefined") $sql_parsed.=$db->parse(" and zd.article like ?s ","%".$request->search_zakaz_article."%");
		
		$sql_count.=" and zd.reorder_detail_id=0 and zd.zakaz_id in (?b) ?p ";
		$details_count=$db->getOne($sql_count,$sql_parsed,$zakazes,$parsed_status);
		
		$sql="select zd.*,dz.document_details_id,dz.document_id,z.delivery_type_id,z.delivery_type from zakaz_details zd 
			left join doc_detail_to_zakaz_detail dz on (dz.zakaz_details_id=zd.id)
			left join zakaz z on (zd.zakaz_id=z.id)
			where ?p";
	    $sql.=" and zd.reorder_detail_id=0  and zd.zakaz_id in (?b) ?p order by zd.create_date desc";
	    if(isset($request->page_size)) $page_size=$request->page_size;
		else $page_size=250;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
		      $sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		     $sql.=" limit 0,".$page_size;
		$res=$db->getAll($sql,$sql_parsed,$zakazes,$parsed_status);
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

	public static function delete_zakaz_detail_by_manager($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->zakaz_id)) {
			$zakaz=new Zakaz($request->zakaz_id);
			if (isset($request->id)) {
				$id=(int)$request->id;
			}
      	}
	    if ($zakaz->id>0 && isset($id) && $id>0){
			$zakaz_detail=new ZakazDetail($id);
			//$updated=0;
			if($zakaz_detail->is_exist==1){
			$zakaz_detail->status=102;
			$updated=$zakaz_detail->save(); 
			}
    		if ($updated==1){
				$sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i";
                $db->query($sql_zakaz_status,$zakaz_detail->zakaz_id,$zakaz_detail->zakaz_id);
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']=$updated;
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function delete_zakaz_detail_by_client($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->zakaz_id)) {
			$zakaz=new Zakaz($request->zakaz_id);
			if (isset($request->id)) {
				$id=(int)$request->id;
			}
      	}
	    if ($zakaz->id>0 && isset($id) && $id>0){
			$zakaz_detail=new ZakazDetail($id);
			//$updated=0;
			if($zakaz_detail->is_exist==1){
			$zakaz_detail->status=100;
			$updated=$zakaz_detail->save(); 
			}
    		if ($updated==1){
				$sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i";
                $db->query($sql_zakaz_status,$zakaz_detail->zakaz_id,$zakaz_detail->zakaz_id);
    		    $ret['status']="ok";
    		    $ret['msg']="Деталь успешно удалена";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']=$updated;
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_ext_not_commited_zakaz_details($request){
	    $db = DB::getInstance();
      $zakazes=$db->getCol("select id from zakaz where main_company_id=?i",$_SESSION['main_company']);
	    if(isset($request->deliverer_type)) $deliverer_type=(int)$request->deliverer_type;
	    $sql_count="select count(detail_id) from zakaz_details where deliverer_type=?i and ext_zakaz=0 and status<5 and zakaz_id in (?b)";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->deliverer_type,$zakazes,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$deliverer_type,$zakazes);
	    if ($deliverer_type==2){
    		$sql="select zd.*,z.delivery_type,z.delivery_address,c.id as deliverer_company_id,c.name as deliverer_company_name from zakaz_details zd
    		left join zakaz z on (z.id=zd.zakaz_id)
    		left join company c on (c.id=(select company_id from price_list where id=zd.deliverer_id))
    		where zd.deliverer_type=?i and zd.ext_zakaz=0 and zd.status>=2 and zd.status<=12 and zakaz_id in (?b)";
	    }
	    else if ($deliverer_type==3){
    		$sql="select zd.*,z.delivery_type,z.delivery_address,c.plugin_id as deliverer_company_id,c.name as deliverer_company_name from zakaz_details zd
    		left join zakaz z on (z.id=zd.zakaz_id)
    		left join user_api_config c on (c.plugin_id=zd.deliverer_id)
    		where zd.deliverer_type=?i and zd.ext_zakaz=0 and zd.status>=2 and zd.status<=14 and zakaz_id in (?b)";
    		// Здесь нужно будет добавить проверку user_api_config_values на предмет принадлежности компании с помощью которой заказали
	    }
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (zd.article like ?s or zd.name like ?s)";
	    $sql.=" order by name";
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
		      $sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		    $sql.=" limit 0,".$page_size;
	    if (!empty($request->search) && $request->search!="undefined") {
    		$res=$db->getAll($sql,$deliverer_type,$zakazes,'%'.$request->search.'%','%'.$request->search.'%');
    		$ret['search']=$request->search;
	    }
	    else
		    $res=$db->getAll($sql,$deliverer_type,$zakazes);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['zakaz_id']=(int)$request->zakaz_id;
		$ret['zakaz_details']=$res;
		$ret['zakaz_pages']=$pages;
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

	public static function get_ext_commited_zakaz_details($request){
	    $db = DB::getInstance();
	    if(isset($request->deliverer_type)) $deliverer_type=(int)$request->deliverer_type;
	    $sql_count="select count(detail_id) from zakaz_details where deliverer_type=?i and ext_zakaz!=0  and status>=5 and status <100";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->zakaz_id,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$deliverer_type);
	    $sql="select * from zakaz_details where deliverer_type=?i and ext_zakaz!=0 and status>=5 and status <100";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (article like ?s or name like ?s)";
	    $sql.=" order by name";
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
		$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		$sql.=" limit 0,".$page_size;
	    if (!empty($request->search) && $request->search!="undefined") {
		$res=$db->getAll($sql,$deliverer_type,'%'.$request->search.'%','%'.$request->search.'%');
		$ret['search']=$request->search;
	    }
	    else
		$res=$db->getAll($sql,$deliverer_type);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['zakaz_id']=(int)$request->zakaz_id;
		$ret['zakaz_details']=$res;
		$ret['zakaz_pages']=$pages;
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

	public static function get_zakaz_details_by_document_id($request){
	    $db = DB::getInstance();
		$local_detail_ids=$db->getCol("SELECT DISTINCT(-detail_id) FROM zakaz_details WHERE detail_id<0 AND create_date>?s AND STATUS<70 
					AND zakaz_id IN (SELECT id FROM zakaz WHERE main_company_id=?i AND STATUS<70)",date("Y-m-d H:i:s",strtotime("1 month ago")),$_SESSION['main_company']);
		$local_details=$db->getAll("SELECT * FROM local_details where id in (?b) and detail_id<>0",$local_detail_ids);
		foreach($local_details as $lkey=>$lval){
			$update_zakaz_details=$db->query("update zakaz_details set detail_id=?i where detail_id=?i 
			and zakaz_id IN (SELECT id FROM zakaz WHERE main_company_id=?i AND STATUS<70)",$lval['detail_id'],-$lval['id'],$_SESSION['main_company']);
		}	
		if(isset($request->document_id) && (int)$request->document_id>0) $document_id=(int)$request->document_id;
		else return self::_error_arr('Не указан номер документа');
	    $sql="SELECT zd.*,z.delivery_address,z.delivery_type,z.delivery_type_id,z.fullfilment_id,s.name as sklad_name,
		sd.my_code as sklad_my_code,sd.ean13 as sklad_ean13, sd.detail_markup_price as sklad_sale_price,c.name as company_name,
		c.id as company_id, z.main_company_id as main_company_id, c.self_zakaz_sale_price, c.document_set_price, dg.group_name as detail_group_name,
		dg.id as detail_group_id
		FROM zakaz_details zd 
			left join zakaz z on (zd.zakaz_id=z.id)
			left join company c on (c.id=z.company_id)
			left join sklad s on ((s.id=z.delivery_type_id and z.delivery_type=1) or (z.delivery_type=2 and s.id=z.fullfilment_id))
			left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=s.id and sd.deleted=0)
			left join detail_group_details dgd on (dgd.detail_id=sd.detail_id and dgd.main_company_id=s.company_id)
			left join detail_group dg on (dgd.detail_group_id=dg.id)
		 	WHERE (
				 	(zd.deliverer_type=3 AND zd.deliverer_id IN 
					 	(SELECT plugin_id FROM user_api_config_values WHERE deliverer_company_id IN 
						 	(SELECT company_id FROM document WHERE id=?i) and enabled=1
						)
					) or
					(zd.deliverer_type=2 AND zd.deliverer_id IN 
					 	(SELECT id FROM price_list WHERE main_company=?i and deleted=0 and status=1  AND company_id IN 
						 	(SELECT company_id FROM document WHERE id=?i))
					)
				)
			 AND (zd.STATUS<=35 and zd.status<>14 and zd.status>=2) 
			 and zd.create_date>?s
			 and zd.zakaz_id in (select id from zakaz where main_company_id=?i and deleted=0 
			 and (delivery_type_id=(SELECT sklad_id FROM document WHERE id=?i) or fullfilment_id=(SELECT sklad_id FROM document WHERE id=?i)) )";
		$res=$db->getAll($sql,$document_id,$_SESSION['main_company'],$document_id,date("Y-m-d",strtotime("45 days ago")),$_SESSION['main_company'],$document_id,$document_id);
		// print_r($db->getStats());
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			//$ret['sql']=$db->parse($sql,$document_id,$_SESSION['main_company'],$document_id,date("Y-m-d",strtotime("45 days ago")),$_SESSION['main_company'],$document_id,$document_id);
			$ret['zakaz_details']=$res;
			$ret['msg']="";
	    }
	    else {
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			$ret['zakaz_details']=[];
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_zakaz_detail_statuses(){
	    $db = DB::getInstance();
		$ret=$db->getInd("id","select id,descr,color from user_zakaz_detail_statuses where user_id=?i",$_SESSION['user_id']);
		if(!$ret) $ret=$db->getInd("id","select id,descr,color from user_zakaz_detail_statuses where company_id=?i",$_SESSION['main_company']);
	    if(!$ret) $ret=$db->getInd("id","select id,descr,client_descr,color from zakaz_detail_statuses");
		foreach($ret as $key=>$val){
			if($ret[$key]['color']===null) $ret[$key]['color']="#FFFFFF";
		}
	    return $ret;
	}

	public static function save_user_zakaz_detail_statuses(){
	    $db = DB::getInstance();
		if(count((array)$request->user_statuses)>0){
			$ret=$db->query("delete from user_zakaz_detail_statuses where company_id=?i and user_id=?i",$_SESSION['main_company'],$_SESSION['user_id']);
			foreach($request->user_statuses as $key=>$val){
				$db->query("insert into user_zakaz_detail_statuses values(?i,?s,?s,?i,?i,?s,?s,?i)",$val['id'],$val['descr'],$val['color'],$_SESSION['user_id'],$_SESSION['main_company'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),0);
			}
		}
	    return ["status"=>"ok","msg"=>"","err"=>""];
	}

	public static function get_zakaz_detail_status_history($request){
		$db = DB::getInstance();
		if(isset($request->zakaz_detail_id) && (int)$request->zakaz_detail_id>0)
	    	$ret=$db->getAll("select * from zakaz_detail_status_log where zakaz_detail_id=?i order by id desc",(int)$request->zakaz_detail_id);
	    return array("status"=>"ok","msg"=>"","status_history"=>$ret);
	}

  public static function save_reorder_detail($request){
    $db = DB::getInstance();
    if(isset($request->change_zakaz_detail_id) && (int)$request->change_zakaz_detail_id>0)
      $detail_to_change=new ZakazDetail($request->change_zakaz_detail_id);
    if (isset($request->deliverer_type)) {
      switch($request->deliverer_type){
          case "price_list": $deliverer_type=2;break;
          case "sklad": $deliverer_type=1;break;
          case "sort1": $deliverer_type=3;break;
      }
    } 

    if($request->change_zakaz_id>0){
		$zakaz=new Zakaz((int)$request->change_zakaz_id);
      if(isset($request->detail_id) && $request->detail_id!=0){
        $zakaz_det=new ZakazDetail(0,$request->change_zakaz_id,$request->detail_id,$deliverer_type,$request->deliverer_id);
      }
      else {
        //include "/var/www/html1/include/lib.php";
        $get_detail_ids=array(
            "action"=>"get_details",
            "brands_aliases"=>true, 
            "offline"=>true,
            "detail"=>array()
        );
        $get_detail_ids['detail'][$i]['k']=1;
        $get_detail_ids['detail'][$i]['a']=$request->article;
        $get_detail_ids['detail'][$i]['b']=$request->brand;
        $send=json_encode($get_detail_ids);
        //echo $send;
        $res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
        //echo print_r($res['body'],true);
        $r=json_decode($res['body'],true);
        //file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
        foreach($r['details'] as $r_key=>$r_val){
          if($r_val['errcode']==0){
              $detail_id=$r_val['data'][0]['detail_id'];
              $request->brand_id=$r_val['data'][0]['brand_id'];
          }
          else {
              $local_detail=array("article"=>$request->article,"brand"=>$request->brand);
              $details=LocalDetails::get_local_details($local_detail);
              $detail_id=$details['detail_id'];
              $request->brand_id=$details['brand_id'];
          }
        }
        //file_put_contents("/var/log/shop/api/reorder_detail.log","",FILE_APPEND);
        $zakaz_det=new ZakazDetail(0,$request->change_zakaz_id,$detail_id,$deliverer_type,$request->deliverer_id);
        //return self::_error_arr("Не указан id детали");
      }
    }
    else
      return self::_error_arr("Не указан номер заказа");

    if (isset($request->article)) $zakaz_det->article=$request->article;
    if (isset($request->brand)) $zakaz_det->brand=$request->brand;
    if (isset($request->brand_id)) $zakaz_det->brand_id=$request->brand_id;
    if (isset($request->name)) $zakaz_det->name=$request->name;
    if(isset($detail_to_change)) {
      if (isset($request->cost)) $zakaz_det->price=(float)$request->cost; //-((float)$request->cost/100)*$detail_to_change->discount;
    }
    else {
      if (isset($request->cost)) $zakaz_det->price=(float)$request->cost-((float)$request->cost/100)*$detail_to_change->discount;
    }
    if (isset($request->count)) $zakaz_det->count=$request->to_cart_count;
    if (isset($request->time)) $zakaz_det->time=(int)$request->time;
    if (isset($request->comment)) $zakaz_det->comment=$request->comment;
    if (isset($request->sort1_id)) $zakaz_det->sort1_id=$request->sort1_id;
	if (isset($request->sort1_sreqid)) $zakaz_det->sort1_sreqid=$request->sort1_sreqid;
	if (isset($request->price)) $zakaz_det->dealer_price=$request->price;
	if($zakaz->company_id==$_SESSION['main_company']){
		//$zakaz_det->price=$request->price;
	}
    if (isset($request->deliverer_online_profile_id)) $zakaz_det->deliverer_online_profile_id=$request->deliverer_online_profile_id;
    //echo "request->sort1_id=".$request->sort1_id.";\n zakaz_det->sort1_id=".$zakaz_det->sort1_id."\n id=".$zakaz_det->id."\nzakaz_det->deliverer_online_profile_id=".$zakaz_det->deliverer_online_profile_id."\n";
    if(isset($request->change_zakaz_detail_id) && (int)$request->change_zakaz_detail_id>0) $zakaz_det->status=2;
	$zakaz_data=$db->getRow("select id,delivery_type_id,delivery_type,fullfilment_id from zakaz where id=?i and main_company_id=?i",$zakaz_det->zakaz_id,$_SESSION['main_company']);
	if($zakaz_det->detail_id!=0 
	&& $zakaz_det->is_excise==0 
	&& ((int)$zakaz_data['delivery_type']==1?(int)$zakaz_data['delivery_type_id']:(int)$zakaz_data['fullfilment_id'])>0 
	&& $zakaz_det->id==0){
		$sd_is_excise=$db->getOne("select is_excise from sklad_details where detail_id=?i and sklad_id=?i",(int)$zakaz_det->detail_id,
		((int)$zakaz_data['delivery_type']==1?(int)$zakaz_data['delivery_type_id']:(int)$zakaz_data['fullfilment_id']));
		if($sd_is_excise!==null) $zakaz_det->is_excise=(int)$sd_is_excise;
	}
	$zakaz_order = $db->getOne('select MAX(zakaz_order) from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and status<>100 and status<>102',$zakaz_det->zakaz_id);
	$zakaz_det->zakaz_order = $zakaz_order+1;
	//print_r($zakaz_det);
    $updated=$zakaz_det->save();
    if(isset($detail_to_change)){
		if((int)$detail_to_change->id!=(int)$zakaz_det->id) {
			$detail_to_change->reorder_detail_id=$zakaz_det->id;
			$detail_to_change->save(); 
		}
    }
    if ($updated==1){
        $ret['status']="ok";
        $ret['msg']="Деталь успешно помещена в заказ, не забудьте заказать данную деталь у поставщика";
    }
    else {
        $ret['status']="err";
        $ret['err']=$updated;
    }
    if ($ret['status']=="err") return self::_error_arr($ret['err']);
    else return $ret;
  }

  public static function get_sort1_order($request){
    if(isset($request->sort1_order_id) && (int)$request->sort1_order_id>0) {
      $db = DB::getInstance();
      $sql="select zakaz_num,article,brand,name, qty,orderQty,deliveryQty,suppliedQty,rejectedQty,price,sum,comment,status,warehouse,create_date,delivery_date,status_state
        from sort1_orders where orderid=?i";
      $res=$db->getRow($sql,(int)$request->sort1_order_id);
      if($res){
        $ret['status']="ok";
        $ret['msg']="";
        $ret['order']=$res;
      }
      else {
        $ret['status']="err";
        $ret['err']="Невозможно получить данные";
      }
    }
    else {
      $ret['status']="err";
      $ret['err']="Недостаточно параметров";
    }
    return $ret;
  }

  public static function make_zakaz_detail_return($request){
    // оформить возврат
    $db = DB::getInstance();
    if(!isset($request->zakaz_detail_return_reason) || empty($request->zakaz_detail_return_reason)) {
      //return self::_error_arr("Не указали причину возврата"); // убрал, потому что в случае возврата по карте, деньги вернет но не запишет здесь.
	}
	if(!isset($request->return_count) || empty($request->return_count) || (int)$request->return_count<=0) {
		return self::_error_arr("Не указали количество возвращаемых товаров");
	}
    if(!isset($request->zakaz_detail_id) || empty($request->zakaz_detail_id) || (int)$request->zakaz_detail_id<=0) {
      return self::_error_arr("Не указан id детали");
    }
    if(!isset($request->sklad_id) || empty($request->sklad_id) || (int)$request->sklad_id<=0) {
      return self::_error_arr("Не указан склад на который приходовать деталь");
	}
	if(!isset($request->payment_type) || empty($request->payment_type) || (int)$request->payment_type<=0) {
		return self::_error_arr("Не указан тип оплаты");
	}
	$zakaz_detail=new ZakazDetail((int)$request->zakaz_detail_id);
	$zakaz_data=$db->getRow("select * from zakaz where id=?i and main_company_id=?i",$zakaz_detail->zakaz_id,$_SESSION['main_company']);
	if(!$zakaz_data) return self::_error_arr("Деталь не из вашего заказа");
     //$db->getRow("select * from zakaz_details where id=?i",(int)$request->zakaz_detail_id);
	//$sklad_detail=new SkladDetail
	$zakaz_detail->return_reason=$request->zakaz_detail_return_reason;
	$zakaz_detail->returned_count+=(int)$request->return_count;
	if((int)$zakaz_detail->count<(int)$request->return_count) 
		return self::_error_arr("Вы не можете вернуть больше чем выдали");
	$zakaz_detail->count-=(int)$request->return_count;
	$zakaz_detail->status=200;
	$zd_st=$zakaz_detail->save($request);
	if($zd_st==1){
		if($request->zakaz_detail_return_to_dealer=="on"){
			$is_returned_to_dealer=self::make_zakaz_detail_return_to_dealer($request);
			if($is_returned_to_dealer['status']=="err"){
				return array("status"=>"err","err"=>"Не удалось оформить возврат поставщику","return_to_dealer_err"=>$is_returned_to_dealer);
			}
		}
		if($request->zakaz_detail_return_payment=="on"){
			//оформить возвратный платеж
			if($request->payment_type==1 || $request->payment_type==2 || $request->payment_type==7){
				$req=array(
					"company_id"=>$zakaz_data['company_id'],
					"from_inn"=>0,
					"from_kpp"=>0,
					"payment_direction"=>3,
					"payment_type"=>$request->payment_type,
					"payment_target"=>"Возврат ".mb_strimwidth(trim($zakaz_detail->name),0,150,"..")." в кол-ве ".$zakaz_detail->returned_count."шт. из заказа №".$zakaz_detail->zakaz_id,
					"summ"=>(float)$zakaz_detail->price*(float)$request->return_count,
					"zakaz_id"=>$zakaz_detail->zakaz_id,
					"zakaz_detail_id"=>$zakaz_detail->id,
				);
				if($request->zakaz_detail_return_payment_dont_fiscalize=="on") $req['dont_fiscalize']="on";
				$return_payment=Payments::save_payment((object)$req);
				if($return_payment['status']=="err") return self::_error_arr($return_payment['err']);
				else return $return_payment;
			}
			else {
				if($request->payment_type==6){ //оплата картой через эквайринг сайта
					$refund_req=array(
						"zakaz_detail_id"=>$zakaz_detail->id,
						"return_count"=>(int)$request->return_count
					);
					$return_refund=Payments::do_sber_refund_zakaz_detail((object)$refund_req);
					if($return_refund['status']=="ok"){
						$req=array(
							"company_id"=>$zakaz_data['company_id'],
							"from_inn"=>0,
							"from_kpp"=>0,
							"payment_direction"=>4,
							"payment_type"=>6,
							"payment_target"=>"Возврат ".$zakaz_detail->name." в кол-ве ".$zakaz_detail->returned_count."шт. из заказа №".$zakaz_detail->zakaz_id,
							"summ"=>$zakaz_detail->price*(int)$request->return_count,
							"zakaz_id"=>$zakaz_detail->zakaz_id,
							"zakaz_detail_id"=>$zakaz_detail->id,
						);
						if($request->zakaz_detail_return_payment_dont_fiscalize=="on") $req['dont_fiscalize']="on";
						$return_payment=Payments::save_payment((object)$req);
						if($return_payment['status']=="err") return self::_error_arr($return_payment['err']);
						else return $return_payment;
					}
					else return self::_error_arr($return_refund['err']);
				}
			}
		}

		
		//$sklad_detail=new SkladDetail((int)$request->sklad_id,$zakaz_detail->detail_id));
		//$sklad_detail->count+=(int)$request->return_count;
		//$sklad_detail->save();
		return array("status"=>"ok", "msg"=>"", "err"=>"");
	}
	else return self::_error_arr($zd_st);
  }

  public static function cancel_zakaz_detail_return_money($request){
    // оформить возврат
    $db = DB::getInstance();
    if(!isset($request->zakaz_detail_return_reason) || empty($request->zakaz_detail_return_reason)) {
      //return self::_error_arr("Не указали причину возврата");
	}
	if(!isset($request->return_count) || empty($request->return_count) || (int)$request->return_count<=0) {
		//return self::_error_arr("Не указали количество возвращаемых товаров");
	}
    if(!isset($request->zakaz_detail_id) || empty($request->zakaz_detail_id) || (int)$request->zakaz_detail_id<=0) {
      return self::_error_arr("Не указан id детали");
    }
    if(!isset($request->sklad_id) || empty($request->sklad_id) || (int)$request->sklad_id<=0) {
      //return self::_error_arr("Не указан склад на который приходовать деталь");
	}
	if(!isset($request->payment_type) || empty($request->payment_type) || (int)$request->payment_type<=0) {
		return self::_error_arr("Не указан тип оплаты");
	}
	$zakaz_detail=new ZakazDetail((int)$request->zakaz_detail_id);
	$request->return_count=$zakaz_detail->count;
	$zakaz_data=$db->getRow("select * from zakaz where id=?i and main_company_id=?i",$zakaz_detail->zakaz_id,$_SESSION['main_company']);
	if(!$zakaz_data) return self::_error_arr("Деталь не из вашего заказа");
     //$db->getRow("select * from zakaz_details where id=?i",(int)$request->zakaz_detail_id);
	//$sklad_detail=new SkladDetail
	$zakaz_detail->return_reason="Отказ клиента";
	$zakaz_detail->returned_count+=(int)$request->return_count;
	//if((int)$zakaz_detail->count<(int)$request->return_count) 
	//	return self::_error_arr("Вы не можете вернуть больше чем выдали");
	$zakaz_detail->count-=(int)$request->return_count;
	$zakaz_detail->status=100;
	$zd_st=$zakaz_detail->save($request);
	if($zd_st==1){
		//if($request->zakaz_detail_return_payment=="on"){
			//оформить возвратный платеж
			if($zakaz_data['payment_type']==1 || $zakaz_data['payment_type']==2){
				$req=array(
					"company_id"=>$zakaz_data['company_id'],
					"from_inn"=>0,
					"from_kpp"=>0,
					"payment_direction"=>3,
					"payment_type"=>$request->payment_type,
					"payment_target"=>"Возврат ".$zakaz_detail->name." в кол-ве ".$request->return_count."шт. из заказа №".$zakaz_detail->zakaz_id,
					"summ"=>(float)$zakaz_detail->price*(float)$request->return_count,
					"zakaz_id"=>$zakaz_detail->zakaz_id,
					"zakaz_detail_id"=>$zakaz_detail->id,
				);
				$return_payment=Payments::save_payment((object)$req);
				if($return_payment['status']=="err") return self::_error_arr($return_payment['err']);
				else {
					$return_payment['send_data']=$req;
					return $return_payment;
				}
			}
			else {
				if($zakaz_data['payment_type']==6){ //оплата картой через эквайринг сайта
					$refund_req=array(
						"zakaz_detail_id"=>$zakaz_detail->id,
						"return_count"=>(int)$request->return_count
					);
					$return_refund=Payments::do_sber_refund_zakaz_detail((object)$refund_req);
					if($return_refund['status']=="ok"){
						$req=array(
							"company_id"=>$zakaz_data['company_id'],
							"from_inn"=>0,
							"from_kpp"=>0, 
							"payment_direction"=>4,
							"payment_type"=>6,
							"payment_target"=>"Возврат ".$zakaz_detail->name." в кол-ве ".$request->return_count."шт. из заказа №".$zakaz_detail->zakaz_id,
							"summ"=>(float)$zakaz_detail->price*(float)$request->return_count,
							"zakaz_id"=>$zakaz_detail->zakaz_id,
							"zakaz_detail_id"=>$zakaz_detail->id,
						);
						$return_payment=Payments::save_payment((object)$req);
						if($return_payment['status']=="err") return array("status"=>"err","err"=>$return_payment['err'],"refund_return"=>$return_refund);
						else {
							$return_payment['refund_return']=$return_refund;
							return $return_payment;
						}
					}
					else return array("status"=>"err","err"=>$return_payment['err'],"refund_return"=>$return_refund);
				}
			}
		//}
		//$sklad_detail=new SkladDetail((int)$request->sklad_id,$zakaz_detail->detail_id));
		//$sklad_detail->count+=(int)$request->return_count;
		//$sklad_detail->save();
		return array("status"=>"ok", "msg"=>"", "err"=>"");
	}
	else return self::_error_arr($zd_st);
  }


  public static function make_zakaz_detail_return_to_dealer($request){
    // оформить возврат
    $db = DB::getInstance();
    /*if(!isset($request->zakaz_detail_return_reason) || empty($request->zakaz_detail_return_reason)) {
      return self::_error_arr("Не указали причину возврата");
	}*/
	if(!isset($request->return_count) || empty($request->return_count) || (int)$request->return_count<=0) {
		return self::_error_arr("Не указали количество возвращаемых товаров");
	}
    if(!isset($request->zakaz_detail_id) || empty($request->zakaz_detail_id) || (int)$request->zakaz_detail_id<=0) {
      return self::_error_arr("Не указан id детали");
    }
    /*if(!isset($request->sklad_id) || empty($request->sklad_id) || (int)$request->sklad_id<=0) {
      return self::_error_arr("Не указан склад на который приходовать деталь");
	}*/
	$zakaz_detail=new ZakazDetail((int)$request->zakaz_detail_id);
	if($zakaz_detail->deliverer_type==1) {
		return self::_error_arr("Деталь продана со склада, не могу вернуть поставщику");
	}
	$zakaz_data=$db->getRow("select * from zakaz where id=?i and main_company_id=?i",$zakaz_detail->zakaz_id,$_SESSION['main_company']);
	if(!$zakaz_data) return self::_error_arr("Деталь не из вашего заказа");
     //$db->getRow("select * from zakaz_details where id=?i",(int)$request->zakaz_detail_id);
	//$sklad_detail=new SkladDetail
	//$zakaz_detail->return_reason=$request->zakaz_detail_return_reason;
	$zakaz_detail->returned_to_dealer_count+=(int)$request->return_count;
	if((int)$zakaz_detail->status==37){
		if((int)$zakaz_detail->count<(int)$request->return_count) 
			return self::_error_arr("Вы не можете вернуть больше чем приняли от поставщика");
		$zakaz_detail->count-=(int)$request->return_count;
	}
	elseif((int)$zakaz_detail->status==200){
		if((int)$zakaz_detail->returned_count<(int)$request->return_count) 
			return self::_error_arr("Вы не можете вернуть больше чем вам вернули");
		//$zakaz_detail->return_count-=(int)$request->return_count;
	}
	
	$zakaz_detail->status=201;
	$zd_st=$zakaz_detail->save($request);
	if((is_array($zd_st) && $zd_st['status']=="ok") || $zd_st==1){
		return array("status"=>"ok", "msg"=>"", "err"=>"");
	} 
	else return self::_error_arr($zd_st);
  }

  public static function get_client_zakaz_details($request){
	if(empty($request->company_id)) return array("status"=>"err","err"=>"не указан клиент");
	$db = DB::getInstance();
	$res=$db->getAll("select * from zakaz_details where zakaz_id in (select id from zakaz where company_id=?i and main_company_id=?i)",$request->company_id,$_SESSION['main_company']);
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

  	public static function set_excise_in_zakaz_detail($request){
		$db = DB::getInstance();
		if((int)$request->is_excise==1) $is_excise=1;
		else $is_excise=0;
		$res=$db->query("update zakaz_details set is_excise=?i where id=?i",(int)$is_excise,(int)$request->zakaz_detail_id);
		$res1=$db->query("update sklad_details set is_excise=?i where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",(int)$is_excise,(int)$request->detail_id,$_SESSION['main_company']);
		$res2=$db->query("update document_details set is_excise=?i where detail_id=?i and document_id in (select id from document where main_company=?i)",(int)$is_excise,(int)$request->detail_id,$_SESSION['main_company']);
		if($res && $res1 && $res2){
			return array("status"=>"ok","msg"=>"");
		}
		else return array("status"=>"err","err"=>"Не удалось изменить данные");
	}

	public static function set_marking_in_zakaz_detail($request){
		$db = DB::getInstance();
		if((int)$request->is_marking==1) $is_marking=1;
		else $is_marking=0;
		$res=$db->query("update zakaz_details set is_marking=?i where id=?i",(int)$is_marking,(int)$request->zakaz_detail_id);
		$res1=$db->query("update sklad_details set is_marking=?i where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",(int)$is_marking,(int)$request->detail_id,$_SESSION['main_company']);
		$res2=$db->query("update document_details set is_marking=?i where detail_id=?i and document_id in (select id from document where main_company=?i)",(int)$is_marking,(int)$request->detail_id,$_SESSION['main_company']);
		if($res && $res1 && $res2){
			return array("status"=>"ok","msg"=>"");
		}
		else return array("status"=>"err","err"=>"Не удалось изменить данные");
	}

	public static function save_zakaz_detail_name($request){
		if(empty($request->zakaz_detail_id)) return array("status"=>"err","err"=>"Не указан номер детали в заказе");
		$db = DB::getInstance();
		$is_your=$db->getOne("select id from zakaz where id=(select zakaz_id from zakaz_details where id=?i) and main_company_id=?i",(int)$request->zakaz_detail_id,$_SESSION['main_company']);
		if($is_your){
			$db->query("update zakaz_details set name=?s where id=?i",$request->name,(int)$request->zakaz_detail_id);
			if($db->affectedRows()>0) return array("status"=>"ok","msg"=>"");
		}
		else {
			return array("status"=>"err","err"=>"Не ваш заказ");
		}
	}

	public static function set_zd_status_to_20($request){
		if(empty($request->zakaz_detail_id)) return array("status"=>"err","err"=>"Не указан номер детали в заказе");
		$db = DB::getInstance();
		$is_your=$db->getOne("select id from zakaz where id=(select zakaz_id from zakaz_details where id=?i) and main_company_id=?i",(int)$request->zakaz_detail_id,$_SESSION['main_company']);
		if($is_your){
			//$db->query("update zakaz_details set status=20 where id=?i",(int)$request->zakaz_detail_id);
			$zd=new ZakazDetail((int)$request->zakaz_detail_id);
			$zd->status=21;
			$err=$zd->save();
			if($err==1 || $err==10) return array("status"=>"ok","msg"=>"");
			else return array("status"=>"err","err"=>"Не удалось поменять статус детали в заказе");
		}
		else {
			return array("status"=>"err","err"=>"Не ваш заказ");
		}
	}

	public static function set_zakaz_detail_dealer($request){
		if(empty($request->zakaz_detail_id)) return array("status"=>"err","err"=>"Не указан номер детали в заказе");
		if(empty($request->deliverer_id) || (int)$request->deliverer_id<=0) return array("status"=>"err","err"=>"Не указан поставщик");
		if(empty($request->deliverer_type) || (int)$request->deliverer_type<=0) return array("status"=>"err","err"=>"Не указан тип поставщика");
		$db = DB::getInstance();
		$is_your=$db->getOne("select id from zakaz where id=(select zakaz_id from zakaz_details where id=?i) and main_company_id=?i",(int)$request->zakaz_detail_id,$_SESSION['main_company']);
		if($is_your){
			$zd=new ZakazDetail((int)$request->zakaz_detail_id);
			if($zd->status>=10) return array("status"=>"err","err"=>"Нельзя изменить поставщика, деталь уже на оформлении у поставщика");
			if($request->deliverer_type==2){
				$detail_in_price=$db->getRow("select * from price_list_details where price_list_id=?i and detail_id=?i",$request->deliverer_id,$zd->detail_id);
				if($detail_in_price){

				}
				else {
					$db->query("insert into price_list_details (detail_id,article,brand_id,brand,name,price,tax,count,reserved_count,time,is_active,price_list_id,create_date,update_date,user_id,default_markup,detail_markup,detail_markup_price)
					values (?i,?s,?i,?s,?s,?s,?s,?s,0,0,1,?i,?s,?s,?i,?s,?s,?s)",
					$zd->detail_id,$zd->article,$zd->brnad_id,$zd->brand,$zd->name,$zd->dealer_price,0,$zd->count,$request->deliverer_id,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$_SESSION['user_id'],0,0,$zd->price);
				}
			}
			$zd->deliverer_type=$request->deliverer_type;
			$zd->deliverer_id=$request->deliverer_id;
			$err=$zd->save();
			if($err==1 || $err==10) return array("status"=>"ok","msg"=>"","zakaz_id"=>$zd->zakaz_id);
			else return array("status"=>"err","err"=>"Не удалось поменять поставщика детали в заказе");
		}
		else {
			return array("status"=>"err","err"=>"Не ваш заказ");
		}
	}

}
?>
