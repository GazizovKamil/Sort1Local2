<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Document;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\ZakazJob;
use Sort1API\Components\DocumentDetail;
use Sort1API\Components\DocumentJob;
use Sort1API\Components\Functions;
//require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Documents extends Model {

	private static $_kontragents=array();
	private static $_sklads=array();
	private static $_nomen=array();
	private static $_realiz=array();
	private static $_rozn_prod=array();
	private static $_schfact=array();
	private static $_kontragent_dogovors_pok=array();
	private static $_kontragent_dogovors_pos=array();
	private static $_my_company=array();
	private static $_gtds=array();

    public static function check_roles($role){
        $main_user=new User((int)$_SESSION['user_id']);
        if ($main_user->roles<=$role) return $role;
        else return $main_user->roles;
    }

    public static function save_document($request) {
    	$db = DB::getInstance();
		$main_data_changed=0;
	    if (isset($request->document_id)) $document_id=(int)$request->document_id;
	    if (isset($document_id) && $document_id>0) {
			$document=new Document($document_id);
			$old_document=$document;
			if($document->type_id==6) return self::_error_arr("Документ создан автоматически, его нельзя редактировать");
	    }
	    else {
		    $document=new Document();
	    }
	    if (isset($request->company_id) && (int)$request->company_id>0) {
			if($document->id>0 && $document->company_id>0 && $document->company_id!=(int)$request->company_id) {
				$document_details_count=$db->getOne("select count(id) from document_details where document_id=?i",$document->id);
				if($document_details_count>0){
					/*switch((int)$document->type_id){
						case 1: 
							return self::_error_arr("Нельзя изменять компанию-поставщика в документе с оприходованными деталями, если вы действительно хотите это сделать, то удалите документ потом создайте новый");
							break;
						case 2: 
							return self::_error_arr("Нельзя изменять компанию-покупателя в документе с проданными деталями, если вы действительно хотите это сделать, то удалите документ потом создайте новый");
							break;
					}*/
					$main_data_changed=1;
				}
			}
    		$companys=$db->getCol("select company_id from user_companys where main_company_id=?i",$_SESSION['main_company']);
    		if (($companys && in_array($request->company_id,$companys)) || $request->company_id==$_SESSION['main_company'])
    		    $document->company_id=(int)$request->company_id;
    		else {
    		    return self::_error_arr("Нельзя добавить документ к чужой компании");
    		}
	    }
	    else {
			if((int)$request->type_id==3){
				$document->company_id=$_SESSION['main_company'];
			}
			else
				return self::_error_arr("Не указана компания");
	    }
			
		if($document->id>0){
			$main_company=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
			if(strtotime($main_company['document_edit_deny_date'])>0){
				if($document->document_date!="0000-00-00") {
					if(strtotime($document->document_date)<=strtotime($main_company['document_edit_deny_date'])){
						return self::_error_arr("Введен запрет редактирования документов, созданных до ".$main_company['document_edit_deny_date']);
					}
				}
				else {
					if(strtotime($document->create_date)<=strtotime($main_company['document_edit_deny_date'])){
						return self::_error_arr("Введен запрет редактирования документов, созданных до ".$main_company['document_edit_deny_date']);
					}
				}
			}
		}

		file_put_contents("/var/lop/api/document_save.log","doc->sklad_id=".$document->sklad_id." req->sklad_id=".$request->sklad_id."\n",FILE_APPEND);
	    if (isset($request->sklad_id) && (int)$request->sklad_id>0) {
			if($document->id>0 && $document->sklad_id!=$request->sklad_id) $main_data_changed=1;
    		$sklads=$db->getCol("select id from sklad where company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
    		if ($sklads && in_array($request->sklad_id,$sklads)){
    		    $document->sklad_id=(int)$request->sklad_id;
    		}
    		else {
    		    return self::_error_arr("Нельзя добавить документ к чужому складу");
    		}
	    }
	    else {
		    return self::_error_arr("Не указан склад");
	    }
	    $document->main_company=$_SESSION['main_company'];
	    $document->user_id=$_SESSION['user_id'];
	    if (isset($request->number)) $document->number=$request->number;
      	if (isset($request->document_date)) $document->document_date=$request->document_date;
		if (isset($request->chf_number)) $document->chf_number=$request->chf_number;
      	if (isset($request->chf_date)) { 
			$document->chf_date=$request->chf_date;
			//echo "chf_date=".$request->chf_date."\n";
			//echo "document chf_date=".$document->chf_date."\n";
		  }
		if (isset($request->obrabotan)){
			if($request->obrabotan=="on") $document->obrabotan=1;
			else $document->obrabotan=0;
		}
		else $document->obrabotan=0;
	    if (isset($request->type_id)) {
			if($document->id>0 && $document->type_id!=$request->type_id) $main_data_changed=1;
			$document->type_id=$request->type_id;
		}
	    if (isset($request->comment)) $document->comment=$request->comment;

		if (isset($request->pay_date) && $pay_date!="0000-00-00") $document->pay_date=$request->pay_date;
		else {
			if (isset($request->company_dogovor_id) && $request->company_dogovor_id>0) {
				$document->dogovor_id=$request->company_dogovor_id;
				$credit_limit_time=$db->getOne("select credit_limit_time from dogovor where id=?i",$document->dogovor_id);
				$document->pay_date=(date("Y-m-d", strtotime("+".$credit_limit_time." day",strtotime($document->create_date=="0000-00-00"?time():$document->create_date))));
			}
		}
		if($document->pay_date=='') $document->pay_date='0000-00-00';
		if (isset($request->return_confirmed) && $request->return_confirmed=="on") $document->return_confirmed=1;
		if (isset($request->upd_vydan) && $request->upd_vydan=="on") $document->upd_vydan=1;
		if (isset($request->return_confirm_date)) $document->return_confirm_date=$request->return_confirm_date;
	    /*if (isset($request->sklad_id)) {
			
			else $document->sklad_id=$request->sklad_id;
		}*/
	    if (isset($request->scan_file)) $document->scan_file=$request->scan_file;
		//file_put_contents("/var/log/shop/api/document_save.log","main_data_changed=$main_data_changed\n",FILE_APPEND);
		if($main_data_changed){
			$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$request->document_id);
			$all_doc_det_deleted=1;
			$deleted_details=array();
			foreach($doc_details as $doc_det_key=>$doc_det_val){
				$doc_det=new DocumentDetail($doc_det_val['id']);
				if(!$doc_det->Delete()) {
					$all_doc_det_deleted=0;
					break;
				}
				else $deleted_details[]=$doc_det->id;
			}
			file_put_contents("/var/log/shop/api/document_save.log","deleted_details=".$deleted_details."\n",FILE_APPEND);
			if($all_doc_det_deleted==0) self::_error_arr("Не могу изменить данные");
		}
	    $err=$document->save();
	    switch($err) {
    		case 10: $status="err"; $msg="Данные не изменились\n"; break;
    		case 1: $znak=$db->getOne("select znak from document_types where id=?i",(int)$document->type_id);
    			//if (isset($request->document_id) && (int)$request->document_id>0){
    				if ($document->id>0)
    				    foreach($request->document_details as $det_key => $det_val){
    						DocumentDetails::save_document_detail($document->id,$znak,$det_val);
    				    }
                    $status="ok"; $msg="";
                //}
                //else {
				//	if ($document->id>0)
				//		foreach($request->document_details as $det_key => $det_val){
				//			DocumentDetails::save_document_detail($document->id,$znak,$det_val);
				//		}
                //    $status="ok"; $msg="";
                //}
				if($main_data_changed){
					if($all_doc_det_deleted){
						foreach($doc_details as $doc_det_key=>$doc_det_val){
							$doc_det=array();
							foreach($doc_det_val as $ddkey=>$ddval){
								if($ddkey=='sklad_id') $doc_det[$ddkey]=$request->sklad_id;
								else {
									if($ddkey=='id') $doc_det[$ddkey]=0;
									else $doc_det[$ddkey]=$ddval;
								}
							}
							$doc_det['subaction']="add";
							file_put_contents("/var/log/shop/api/document_save.log","doc_det=".print_r($doc_det,true)."\n",FILE_APPEND);
							$doc_det_save_res=DocumentDetails::save_document_detail((object)$doc_det);
							if($doc_det_save_res=="err") self::_error_arr("Не могу изменить данные");
						}
					}
				}
    			break;
    		default: $status="err"; $msg="error: ".$err."\n";
	    }
        if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg,"document_id"=>$document->id);
    }

	private static function change_document_sklad($request){
		$db = DB::getInstance();
		$doc_details=$db->getCol("select * from document_details where document_id=?i and deleted=0",$request->document_id);
    	$all_doc_det_deleted=1;
		$deleted_details=array();
    	foreach($doc_details as $doc_det_key=>$doc_det_val){
    	    $doc_det=new DocumentDetail($doc_det_val['id']);
    	    if(!$doc_det->Delete()) {
				$all_doc_det_deleted=0;
				break;
			}
			else $deleted_details[]=$doc_det->id;
    	}
		if($all_doc_det_deleted){
			foreach($doc_details as $doc_det_key=>$doc_det_val){
				$doc_det=array();
				foreach($doc_det_val as $ddkey=>$ddval){
					if($ddkey=='sklad_id') $doc_det[$ddkey]=$request->sklad_id;
					if($ddkey=='sklad_id') $doc_det[$ddkey]=$request->sklad_id;
				}
			}
		}
	}

	public static function get_documents($request) {
	    $db = DB::getInstance();

	    $sql="select d.id,d.type_id,d.number,d.document_date,d.chf_number,d.chf_date,d.company_id,d.comment,d.scan_file,d.sklad_id,d.obrabotan,d.deleted,
			d.return_confirmed,d.return_confirm_date,c.name,c.inn,c.kpp,c.type,
			s.name as sklad_name,d.create_date,d.update_date,d.zakaz_id,d.user_id,u.name as user_name,u.lastname as user_lastname
		    from document d
			left join company c on (d.company_id=c.id)
		    left join sklad s on (s.id=d.sklad_id)
			left join users u on (u.id=d.user_id)
			where d.main_company=?i and d.sklad_id=?i";
		if(empty($request->search_document_show_deleted) || $request->search_document_show_deleted=="off") {
			$sql.=" and d.deleted=0 ";
			$show_deleted=0;
		}
		else $show_deleted=1;
		
			if(!empty($request->search_document_date_from)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime("10 days ago"));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime("1 month ago"));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			if(!empty($request->search_document_date_to)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d");
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d");
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			if(!empty($request->search_document_client_name)){
				$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where main_company_id=?i and btype<>5) and name like ?s";
				$res_cl=$db->getCol($sql_cl,$_SESSION['main_company'],'%'.trim($request->search_document_client_name).'%');
				if($res_cl){
					//$search_companys=array_column($res_cl,"id");
					$sql.=" and (d.company_id in (".implode(",",$res_cl).")";
					$is_our=$db->getAll("select id from company where name like ?s and id=?i",'%'.trim($request->search_document_client_name).'%',$_SESSION['main_company']);
					if($is_our){
						$sql.=" or d.company_id=".(int)$_SESSION['main_company'];
					}
					$sql.=")";
				}
				else {
					// проверим может по основной компании идет поиск
					$is_our=$db->getAll("select id from company where name like ?s and id=?i",'%'.trim($request->search_document_client_name).'%',$_SESSION['main_company']);
					if($is_our){
						$sql.=" and d.company_id=".(int)$_SESSION['main_company'];
					}
					else {
						$sql.=" and d.company_id=0";
					}
				}
				//echo $sql;
				$ret['search_document_client_name']=$request->search_document_client_name;
			}
		switch($request->znak){
			case "+":
			case "-": // || $request->znak=="-")) {
				//$sql_znak="select id from document_types where znak=?s";
				//$document_types=$db->getCol($sql_znak,$request->znak);
				$sql.=" and d.type_id in (?b)";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				if ($request->znak=="+") $res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['my_sklad_id'],array(1,5));//$document_types);
				if ($request->znak=="-") $res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['my_sklad_id'],array(2,3));//$document_types);
				//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
				break;
			case "rtd": // return to dealer 
				//$sql_znak="select id from document_types where znak=?s";
				//$document_types=$db->getCol($sql_znak,$request->znak);
				$sql.=" and d.type_id in (?b)";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['my_sklad_id'],array(7));//$document_types);
				//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
				break;
			case "rfc": // return from client 
				//$sql_znak="select id from document_types where znak=?s";
				//$document_types=$db->getCol($sql_znak,$request->znak);
				$sql.=" and d.type_id in (?b)";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['my_sklad_id'],array(6));//$document_types);
				//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
				break;
			case "exp":
				$sql.=" and type_id in (?b)";
				$parsed="";
				if(isset($request->search_document_orgtype) && (int)$request->search_document_orgtype>0){
					if((int)$request->search_document_orgtype<3)
						$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type<3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
					else {
						$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type=3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
					}
				}
				$sql.=" ?p ";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['company_id'],$_SESSION['my_sklad_id'],$request->type_ids,$parsed);
				break;
			default:
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['main_company'],$_SESSION['my_sklad_id']);
		
		}

		if(!empty($request->search_document_article)){
			//echo Functions::convert_article(trim($request->search_document_article));
			$res_art=$db->getCol("select distinct(document_id) from document_details where document_id in (?b) and deleted=0 and replace(replace(replace(replace(replace(replace(article,',',''),'.',''),' ',''),'-',''),'/',''),'_','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_document_article))."%");
			//$ret['sql_search']=$db->parse("select distinct(document_id) from document_details where document_id in (?b) and deleted=0 and replace(replace(replace(replace(replace(article,',',''),'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_document_article))."%");
			foreach($res as $doc_key => $search_doc){
				if(in_array($search_doc["id"],$res_art)){
					$ret_res[]=$res[$doc_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_document_article']=$request->search_document_article;
		}
		if($show_deleted){
			$deleted_details_in=$db->getCol("select distinct(document_id) from document_details where document_id in (?b) and deleted=1",array_column($res,"id"));
			foreach($res as $doc_key => $doc_val){
				if(in_array($res[$doc_key]['id'],$deleted_details_in)){
					$res[$doc_key]['is_deleted_details']=1;
				}
				else {
					$res[$doc_key]['is_deleted_details']=0;
				}
			}
		}
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['documents']=$ret_res;
			$document_ids_rtd=array();
			$document_ids_other=array();
			foreach($ret_res as $ret_res1){
				if((int)$ret_res1['type_id']==7) array_push($document_ids_rtd,$ret_res1['id']);
				else array_push($document_ids_other,$ret_res1['id']);
			}
        	$ret['document_types']=$db->getInd("id","select id,name,descr from document_types");
			//if($request->znak=="rtd"){
			$document_det_pos_rtd=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(dealer_price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",$document_ids_rtd);
			//}
			//else {
			$document_det_pos_other=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",$document_ids_other);
			//}
			$ret['document_det_pos']=array();
			foreach ($document_det_pos_rtd as $key=>$val) {
				$ret['document_det_pos'][$key]=$val;
			}
			foreach ($document_det_pos_other as $key=>$val) {
				$ret['document_det_pos'][$key]=$val;
			}
			//$ret['document_det_pos']=array_merge($document_det_pos_rtd,$document_det_pos_other);
			$ret['document_job_pos']=$db->getInd("document_id","SELECT document_id,COUNT(job_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_jobs where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			//$ret['sql']=$sql;
			$ret['msg']="";
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['documents']=array();
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_documents_return_to_dealer($request) {
	    $db = DB::getInstance();

	    $sql="select d.id,d.type_id,d.number,d.document_date,d.chf_number,d.chf_date,d.company_id,d.comment,d.scan_file,d.sklad_id,d.obrabotan,c.name,
			s.name as sklad_name,d.create_date,d.update_date,d.zakaz_id,d.user_id,u.name as user_name,u.lastname as user_lastname
		    from document d
			left join company c on (d.company_id=c.id)
		    left join sklad s on (s.id=d.sklad_id)
			left join users u on (u.id=d.user_id)
			where d.main_company=?i and d.deleted=0 ";
		
			if(!empty($request->search_document_date_from)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime("1 month ago"));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime("1 month ago"));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			if(!empty($request->search_document_date_to)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d");
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d");
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			if(!empty($request->search_document_client_name)){
				$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where main_company_id=?i and (btype=1 or btype=0 or btype=2 or btype=3)) and name like ?s";
				$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],'%'.trim($request->search_document_client_name).'%');
				if($res_cl){
					$search_companys=array_column($res_cl,"id");
					$sql.=" and d.company_id in (".implode(",",$search_companys).")";
				}
				$ret['search_document_client_name']=$request->search_document_client_name;
			}
    		$sql.=" and d.type_id = 7 ";
        	if(isset($request->date_type) && $request->date_type=='document_date'){
				$sql.="order by d.document_date desc";
			}
			else {
				$sql.="order by d.create_date desc";
			}
    		$res=$db->getAll($sql,$_SESSION['company_id']);
    		//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";

		if(!empty($request->search_document_article)){
			$res_art=$db->getCol("select distinct(document_id) from document_details where document_id in (?b) and deleted=0 and replace(replace(replace(replace(article,'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_document_article))."%");
			foreach($res as $doc_key => $search_doc){
				if(in_array($search_doc["id"],$res_art)){
					$ret_res[]=$res[$doc_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_document_article']=$request->search_document_article;
		}
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['documents']=$ret_res;
        	$ret['document_types']=$db->getInd("id","select id,name,descr from document_types");
			$ret['document_det_pos']=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			$ret['document_job_pos']=$db->getInd("document_id","SELECT document_id,COUNT(job_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_jobs where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			//$ret['']
			$ret['msg']="";
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['documents']=array();
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_document_types(){
	    $db = DB::getInstance();
	    $sql="select * from document_types";
	    if ($res=$db->getAll($sql)){
    		foreach($res as $key => $val){
    		    $types[$val['id']]=$val['descr'];
    		}
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['document_types']=$res;
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="Невозможно получить данные";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_document($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->document_id)) {$document_id=(int)$request->document_id;}
	    if (isset($document_id) && $document_id>0){
    		$doc_details=$db->getAll("select id,article,brand,detail_id,document_id from document_details where document_id=?i and deleted=0",$document_id);
    		$all_doc_det_deleted=1;
			$cant_delete=array("detail"=>array(),"job"=>array());
    		foreach($doc_details as $doc_det_key=>$doc_det_val){
    		    $doc_det=new DocumentDetail($doc_det_val['id']);
				
    		    if(!$doc_det->Delete()) {
					$all_doc_det_deleted=0;
					array_push($cant_delete['detail'],$doc_det_val); 
					//echo "cant_delete=".print_r($cant_delete,true)."\n";
					break;
				}
    		}
			$doc_jobs=$db->getCol("select id from document_jobs where document_id=?i and deleted=0",$document_id);
    		$all_doc_job_deleted=1;
    		foreach($doc_jobs as $doc_job_key=>$doc_job_val){
    		    $doc_job=new DocumentJob($doc_job_val);
				
    		    if(!$doc_job->Delete()) {
					$all_doc_job_deleted=0;
					$cant_delete['job']=array($doc_job_key,$doc_job_val); 
					//echo "cant_delete=".print_r($cant_delete,true)."\n";
					break;
				}
    		}
    		if($all_doc_det_deleted){ 
    		    $res2=$db->query("update document set deleted=1 where id=?i and (company_id in (select company_id from user_companys where main_company_id=?i) or company_id=?i)",$document_id,$_SESSION['main_company'],$_SESSION['main_company']);
    		    //echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		    if ($res2){
					$ret['status']="ok";
					$ret['msg']="";
    		    }
    		    else {
					$ret['status']="err";
					$ret['err']="не удалось удалить документ";
    		    }
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить документ, невозможно восстановить состояние склада, не все детали документа удалились";
				$ret['cant_delete']=$cant_delete;
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return array("status"=>"err","err"=>$ret['err'],"result"=>$ret);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_document($request) {
	    $fields="";
	    $db = DB::getInstance();
	    //if ((int)$_SESSION['roles']!=1) {
	//	return self::_error_arr("У Вас нет прав для удаления");
	//    }
	    if (isset($request->document_id)) {$document_id=(int)$request->document_id;}
	    if (isset($document_id) && $document_id>0){
    		$res2=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
				$comp_data=$db->getRow("select name,tax_type from company where id=?i",$res2['company_id']);
				$tax_data=$db->getOne("select tax_rate from tax_type where id=?i and is_nds=1",$comp_data['tax_type']);
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		    $ret['document']=$res2;
				$ret['company_name']=str_replace(array("\"","'"),"",$comp_data['name']);
				$ret['company_nds']=((int)$tax_data>0)? (int)$tax_data : 0;
				$sklad_data=$db->getRow("select * from sklad where id=?i",$res2['sklad_id']);
    		    $ret['sklad_name']=$sklad_data['name'];
				if((int)$sklad_data['price_type']>0) {
					$price_type=$db->getRow("select * from dict_price_type where id=?i",$sklad_data['price_type']);
					switch((int)$price_type['type']){ 
						case 2: // фиксированная скидка
							$ret['sklad_markup']=$price_type['proc'];
							break;
						case 4: // дифеернцированная наценка
							$ret['diff_markup']=$db->getAll("select min_sum,max_sum,value from dict_price_type_differencial_values where dict_price_type_id=?i",$sklad_data['price_type']);
							break;
					}

				}
				else
					$ret['sklad_markup']=$sklad_data['default_markup'];
				$ret['sklad_price_type']=
    		    $ret['type_id_name']=$db->getOne("select name from document_types where id=?i",$res2['type_id']);
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось получить документ";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_1c_export_file($request){
		if(empty($request->date_from)) $request->date_from=date("Y-m-d");
		if(empty($request->date_to)) $request->date_to=date("Y-m-d");
		$db = DB::getInstance();
		if(count((array)self::$_my_company)==0) {
			self::$_my_company=$db->getRow("select c.*,tt.is_nds,tt.tax_rate from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		}
		$return_xml=new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ФайлОбмена/>');
		$return_xml->addAttribute("ВерсияФормата","2.0");
		$return_xml->addAttribute("ДатаВыгрузки",str_replace(" ","T",date("Y-m-d H:i:s")));
		$return_xml->addAttribute("НачалоПериодаВыгрузки",$request->date_from."T00:00:00");
		$return_xml->addAttribute("ОкончаниеПериодаВыгрузки",$request->date_to."T23:59:59");
		$return_xml->addAttribute("ИмяКонфигурацииИсточника","БухгалтерияПредприятия");
		$return_xml->addAttribute("ИмяКонфигурацииПриемника","БухгалтерияПредприятия");
		$return_xml->addAttribute("ИдПравилКонвертации","ee8c6f33-fddc-45d2-a9aa-3d94959c172b0002");
		$return_xml->addAttribute("Комментарий","");
		$pravila_obmena=$return_xml->addChild("ПравилаОбмена");
			$pravila_obmena->addChild("ВерсияФормата","2.01"); 
			$pravila_obmena->addChild("Ид","ee8c6f33-fddc-45d2-a9aa-3d94959c172b0002"); 
			$pravila_obmena->addChild("Наименование","БухгалтерияПредприятия --&gt; БухгалтерияПредприятия"); 
			$pravila_obmena->addChild("ДатаВремяСоздания",str_replace(" ","T",date("Y-m-d H:i:s")));
			$pravila_obmena->addChild("Источник","БухгалтерияПредприятия");
			$pravila_obmena->addChild("Приемник","БухгалтерияПредприятия"); 
			$pravila_obmena->addChild("ПередЗагрузкойДанных",'Параметры.Вставить("ОбъектыКПроведению", Новый Массив);'); 
			$pravila_obmena->addChild("ПослеЗагрузкиОбъекта",'Если ИмяТипаОбъекта = "Справочник" ИЛИ ИмяТипаОбъекта = "ПланВидовХарактеристик" Тогда Если НЕ ЗначениеЗаполнено(Объект.Код) Тогда Объект.УстановитьНовыйКод(); УстановитьОбменДаннымиЗагрузка(Объект); Объект.Записать(); КонецЕсли; Если Объект.Метаданные().Реквизиты.Найти("Организация") <> Неопределено И (НЕ ЗначениеЗаполнено(Объект.Организация)) Тогда Объект.Организация = ОбщегоНазначенияБПВызовСервера.ПолучитьЗначениеПоУмолчанию("ОсновнаяОрганизация"); КонецЕсли; ИначеЕсли ИмяТипаОбъекта = "Документ" Тогда Если НЕ ЗначениеЗаполнено(Объект.Номер) Тогда Объект.УстановитьНовыйНомер("00"); УстановитьОбменДаннымиЗагрузка(Объект); Объект.Записать(); КонецЕсли; Если ОбщегоНазначенияБП.ЕстьРеквизитДокумента("Организация", Объект.Метаданные()) И (НЕ ЗначениеЗаполнено(Объект.Организация)) Тогда Объект.Организация = ОбщегоНазначенияБПВызовСервера.ПолучитьЗначениеПоУмолчанию("ОсновнаяОрганизация"); КонецЕсли; КонецЕсли');
			$pravila_obmena->addChild("ПослеЗагрузкиДанных",'ВыборкаКонтрагентов = Справочники.Контрагенты.Выбрать(,,новый Структура("ГоловнойКонтрагент",Справочники.Контрагенты.ПустаяСсылка())); пока ВыборкаКонтрагентов.Следующий() цикл ОбъектК = ВыборкаКонтрагентов.ПолучитьОбъект(); ОбъектК.ГоловнойКонтрагент = ВыборкаКонтрагентов.Ссылка; ОбъектК.Записать(); конецЦикла; Для каждого ОбъектКПроведению ИЗ Параметры.ОбъектыКПроведению Цикл ПроводимыйОбъект = ОбъектКПроведению.ПолучитьОбъект(); ПроводимыйОбъект.Записать(РежимЗаписиДокумента.Проведение,); КонецЦикла;');
	 		$parametry=$pravila_obmena->addChild("Параметры");
			$param=$parametry->addChild("Параметр");
			$param->addAttribute("Имя","ОбъектыКПроведению");
			$param->addAttribute("Наименование","ОбъектыКПроведению");
			$param->addAttribute("ИспользуетсяПриЗагрузке","true");
			$param->addAttribute("УстанавливатьВДиалоге","false");
			$param->addAttribute("ПередаватьПараметрПриВыгрузке","false"); 
			$pravila_obmena->addChild("Обработки"); 
	 		$pravila_konvertacii_obektov=$pravila_obmena->addChild("ПравилаКонвертацииОбъектов");
		if(in_array(2,$request->type_ids)){
			self::pravila_obmena_realizaciya($pravila_konvertacii_obektov);
			self::pravila_obmena_schetfactura_vydan($pravila_konvertacii_obektov);
		}
		if(in_array(1,$request->type_ids)){
			self::pravila_obmena_postuplenie($pravila_konvertacii_obektov);
			self::pravila_obmena_schetfactura_poluch($pravila_konvertacii_obektov);
			//create_object_schetfactura_poluch(&$xml,&$npp,$document_id)
		}
		self::pravila_obmena_kontragent($pravila_konvertacii_obektov);
		if(in_array("ORP",$request->type_ids)){
			self::pravila_obmena_ORP($pravila_konvertacii_obektov);
		}
		if(in_array("PKO",$request->type_ids)){
			self::pravila_obmena_PKO($pravila_konvertacii_obektov);
		}
		if(in_array("RKO",$request->type_ids)){
			self::pravila_obmena_RKO($pravila_konvertacii_obektov);
		}
		
		if(in_array(6,$request->type_ids)){
			self::pravila_obmena_return_client($pravila_konvertacii_obektov);
		}
		if(in_array(7,$request->type_ids)){
			self::pravila_obmena_return_postav($pravila_konvertacii_obektov);
		}
		$pravila_obmena->addChild("ПравилаОчисткиДанных"); 
			$pravila_obmena->addChild("Алгоритмы"); 
			$pravila_obmena->addChild("Запросы");
		$npp=0;
		self::create_object_valuta($return_xml,$npp);
		self::create_object_org($return_xml,$npp,$_SESSION['company_id']);
		self::create_object_edizm($return_xml,$npp);
		
		
		$parsed="";
		if(isset($request->search_document_orgtype) && (int)$request->search_document_orgtype>0){
			if((int)$request->search_document_orgtype<3)
				$parsed=$db->parse(" and company_id in (?b)",$db->getCol("select id from company where type<3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
			else {
				$parsed=$db->parse(" and company_id in (?b)",$db->getCol("select id from company where type=3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
			}
		}
		if(in_array(1,$request->type_ids)){
			if(isset($request->date_type) && $request->date_type=='document_date'){
				$documents=$db->getAll("select id from document where document_date>=?s and document_date<=?s and main_company=?i and type_id=1 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			else {
				$documents=$db->getAll("select id from document where create_date>=?s and create_date<=?s and main_company=?i and type_id=1 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			foreach($documents as $doc_key=>$doc_val){
				self::create_object_prihod($return_xml,$npp,$doc_val['id']);
			}
			foreach($documents as $doc_key=>$doc_val){
				self::create_object_schetfactura_poluch($return_xml,$npp,$doc_val['id']);
			}
		}
		if(in_array(2,$request->type_ids)){
			if(isset($request->date_type) && $request->date_type=='document_date'){
				if(self::$_my_company['is_nds']==1){
					$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
					LEFT JOIN company c ON (c.id=d.company_id)
					where d.document_date>=?s and d.document_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 and c.type<>3 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
				}
				else {
					$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
					LEFT JOIN company c ON (c.id=d.company_id)
					where d.document_date>=?s and d.document_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
				}
			}
			else {
				$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
				LEFT JOIN company c ON (c.id=d.company_id)
				where d.create_date>=?s and d.create_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 ".(self::$_my_company['is_nds']==1?" and c.type<>3 ":"")." ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			foreach($documents as $doc_key=>$doc_val){
				self::create_object_realizaciya($return_xml,$npp,$doc_val['id']);
			}
			if(self::$_my_company['is_nds']==1){
				foreach($documents as $doc_key=>$doc_val){
					self::create_object_schetfactura_vydan($return_xml,$npp,$doc_val['id']);
				}
			}
		}
		if(in_array(6,$request->type_ids)){
			if(isset($request->date_type) && $request->date_type=='document_date'){
				$documents=$db->getAll("select id from document where document_date>=?s and document_date<=?s and main_company=?i and type_id=6 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			else {
				$documents=$db->getAll("select id from document where create_date>=?s and create_date<=?s and main_company=?i and type_id=6 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			foreach($documents as $doc_key=>$doc_val){
				self::create_object_return_client($return_xml,$npp,$doc_val['id']);
			}
		}
		if(in_array(7,$request->type_ids)){
			if(isset($request->date_type) && $request->date_type=='document_date'){
				$documents=$db->getAll("select id from document where document_date>=?s and document_date<=?s and main_company=?i and type_id=7 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			else {
				$documents=$db->getAll("select id from document where create_date>=?s and create_date<=?s and main_company=?i and type_id=7 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$parsed);
			}
			foreach($documents as $doc_key=>$doc_val){
				self::create_object_return_postav($return_xml,$npp,$doc_val['id']);
			}
		}
		
		if(in_array("ORP",$request->type_ids)){
			$current_date=strtotime($request->date_from);
			$time_to=strtotime($request->date_to." 23:59:59");
			while($current_date <= $time_to){
				self::create_object_rozn_prod($return_xml,$npp,date("Y-m-d",$current_date),$request->date_type,$parsed);
				$current_date = strtotime("+1 day",$current_date);
			}
		}
		if(in_array("PKO",$request->type_ids)){
			$payments=$db->getAll("select id from payment 
			where create_date>=?s and create_date<=?s 
			and company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
			LEFT JOIN company c ON (c.id=uc.company_id)
			WHERE uc.main_company_id=?i ".(self::$_my_company['is_nds']==1?" and c.type<>3 ":"")." AND uc.deleted=0)
			and payment_type in (1,2,3,6,7)
			and main_company_id=?i and payment_direction=1 and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$_SESSION['main_company'],$parsed);
			foreach($payments as $pay_key=>$pay_val){
				self::create_object_PKO($return_xml,$npp,$pay_val['id']);
			}
			if(self::$_my_company['is_nds']==1){
				$current_date=strtotime($request->date_from);
				$time_to=strtotime($request->date_to." 23:59:59");
				while($current_date <= $time_to){
					self::create_object_PKO_daily_rozn($return_xml,$npp,date("Y-m-d",$current_date));
					$current_date = strtotime("+1 day",$current_date);
				}
			}
		}
		if(in_array("RKO",$request->type_ids)){
			$payments=$db->getAll("select id from payment 
			where create_date>=?s and create_date<=?s 
			and company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
			LEFT JOIN company c ON (c.id=uc.company_id)
			WHERE uc.main_company_id=?i AND c.type<>3 AND uc.deleted=0)
			and payment_type=1
			and main_company_id=?i and payment_direction in (3,4,5) and deleted=0 ?p",$request->date_from,$request->date_to." 23:59:59",$_SESSION['main_company'],$_SESSION['main_company'],$parsed);
			foreach($payments as $pay_key=>$pay_val){
				self::create_object_RKO($return_xml,$npp,$pay_val['id']);
			}
			$current_date=strtotime($request->date_from);
			$time_to=strtotime($request->date_to." 23:59:59");
			while($current_date <= $time_to){
				self::create_object_RKO_daily_rozn($return_xml,$npp,date("Y-m-d",$current_date));
				$current_date = strtotime("+1 day",$current_date);
			}
		}
		return array("status"=>"ok","msg"=>"","export_file"=>$return_xml->asXML());
	}

	private static function pravila_obmena_realizaciya(&$pravila_konvertacii_obektov){	
	 	$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","РеализацияТоваровУслуг"); 
		// в начале следующего правила убрал  Объект.ВидОперации = Перечисления.ВидыОперацийРеализацияТоваров.ПродажаКомиссия; //Объект.Ответственный = глТекущийПользователь;
		$pravilo->addChild("ПослеЗагрузки",'Если НЕ ЗначениеЗаполнено(Объект.Склад) Тогда Объект.Склад = Константы.УдалитьСкладДляОбменаДаннымиСУТ.Получить(); КонецЕсли; Объект.СпособЗачетаАвансов = Перечисления.СпособыЗачетаАвансов.Автоматически; Документы.РеализацияТоваровУслуг.ЗаполнитьСчетаУчетаРасчетов(Объект); Документы.РеализацияТоваровУслуг.ЗаполнитьСчетаУчетаВТабличнойЧасти(Объект, "Товары"); Документы.РеализацияТоваровУслуг.ЗаполнитьСчетаУчетаВТабличнойЧасти(Объект, "Услуги"); Для каждого СтрокаТЧ Из Объект.Услуги Цикл Если НЕ ЗначениеЗаполнено(СтрокаТЧ.СчетДоходов) тогда СтрокаТЧ.СчетДоходов = ПланыСчетов.Хозрасчетный.ВыручкаНеЕНВД; КонецЕсли; Если НЕ ЗначениеЗаполнено(СтрокаТЧ.СчетРасходов) тогда СтрокаТЧ.СчетРасходов = ПланыСчетов.Хозрасчетный.СебестоимостьПродажНеЕНВД; КонецЕсли; Если НЕ ЗначениеЗаполнено(СтрокаТЧ.СчетУчетаНДСПоРеализации) тогда СтрокаТЧ.СчетУчетаНДСПоРеализации = ПланыСчетов.Хозрасчетный.Продажи_НДС; КонецЕсли; КонецЦикла; Объект.Записать(); РежимЗаписи = "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.РеализацияТоваровУслуг"); 
		$pravilo->addChild("Приемник","ДокументСсылка.РеализацияТоваровУслуг");
	}

	private static function pravila_obmena_schetfactura_vydan(&$pravila_konvertacii_obektov){	
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","СчетФактураВыданный"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.ВидСчетаФактуры = Перечисления.ВидСчетаФактурыВыставленного.НаРеализацию; Объект.КодВидаОперации = "01"; Объект.Выставлен = ИСТИНА; Объект.КодСпособаВыставления = 1;Объект.Записать(); РежимЗаписи = "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.СчетФактураВыданный"); 
		$pravilo->addChild("Приемник","ДокументСсылка.СчетФактураВыданный");
   }

   private static function pravila_obmena_schetfactura_poluch(&$pravila_konvertacii_obektov){	
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","СчетФактураПолученный"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.ВидСчетаФактуры = Перечисления.ВидСчетаФактурыПолученного.НаПоступление; Объект.КодВидаОперации = "01"; Объект.Записать(); РежимЗаписи = "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.СчетФактураПолученный"); 
		$pravilo->addChild("Приемник","ДокументСсылка.СчетФактураПолученный");
	}

	private static function pravila_obmena_postuplenie(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","ПоступлениеТоваровУслуг"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.СпособЗачетаАвансов = Перечисления.СпособыЗачетаАвансов.Автоматически; Документы.ПоступлениеТоваровУслуг.ЗаполнитьСчетаУчетаРасчетов(Объект); Документы.ПоступлениеТоваровУслуг.ЗаполнитьСчетаУчетаВТабличнойЧасти(Объект, "Товары");Объект.Записать(); РежимЗаписи = "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.ПоступлениеТоваровУслуг"); 
		$pravilo->addChild("Приемник","ДокументСсылка.ПоступлениеТоваровУслуг"); 
	}
	
	private static function pravila_obmena_return_client(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","ВозвратТоваровОтПокупателя"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.ВидОперации = Перечисления.ВидыОперацийВозвратТоваровОтПокупателя.Товары; Объект.СчетУчетаРасчетовСКонтрагентом = ПланыСчетов.Хозрасчетный.РасчетыСПокупателями; Объект.СчетУчетаРасчетовПоАвансам = ПланыСчетов.Хозрасчетный.РасчетыПоАвансамПолученным; Документы.ВозвратТоваровОтПокупателя.ЗаполнитьСчетаУчетаВТабличнойЧасти(Объект, "Товары"); Если НЕ ЗначениеЗаполнено(Объект.Склад) Тогда Объект.Склад = Константы.УдалитьСкладДляОбменаДаннымиСУТ.Получить(); КонецЕсли; Объект.Записать(); РежимЗаписи = "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.ВозвратТоваровОтПокупателя"); 
		$pravilo->addChild("Приемник","ДокументСсылка.ВозвратТоваровОтПокупателя"); 
	}

	private static function pravila_obmena_return_postav(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","ВозвратТоваровПоставщику"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.ВидОперации = Перечисления.ВидыОперацийВозвратТоваровПоставщику.ПокупкаКомиссия; //Объект.Ответственный = глТекущийПользователь; Документы.ВозвратТоваровПоставщику.ЗаполнитьСчетаУчетаРасчетов(Объект); Документы.ВозвратТоваровПоставщику.ЗаполнитьСчетаУчетаВТабличнойЧасти(Объект, "Товары"); Если НЕ ЗначениеЗаполнено(Объект.Склад) ТогдаОбъект.Склад = Константы.УдалитьСкладДляОбменаДаннымиСУТ.Получить(); КонецЕсли; Объект.Записать(); РежимЗаписи = "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.ВозвратТоваровПоставщику"); 
		$pravilo->addChild("Приемник","ДокументСсылка.ВозвратТоваровПоставщику"); 
	}

	private static function pravila_obmena_PKO(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","ПриходныйКассовыйОрдер"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.ДополнительныеСвойства.Вставить("ЗаполнитьСчетаУчетаПередЗаписью", true);СчетаУчетаВДокументах.ЗаполнитьПередЗаписью(Объект, РежимЗаписиДокумента.Проведение); Объект.Записать(РежимЗаписиДокумента.Проведение);');
		$pravilo->addChild("Источник","ДокументСсылка.ПриходныйКассовыйОрдер"); 
		$pravilo->addChild("Приемник","ДокументСсылка.ПриходныйКассовыйОрдер"); 
	}

	private static function pravila_obmena_RKO(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","РасходныйКассовыйОрдер"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.ДополнительныеСвойства.Вставить("ЗаполнитьСчетаУчетаПередЗаписью", true);СчетаУчетаВДокументах.ЗаполнитьПередЗаписью(Объект, РежимЗаписиДокумента.Проведение); Объект.Записать(РежимЗаписиДокумента.Проведение);');
		$pravilo->addChild("Источник","ДокументСсылка.РасходныйКассовыйОрдер"); 
		$pravilo->addChild("Приемник","ДокументСсылка.РасходныйКассовыйОрдер"); 
	}

	private static function pravila_obmena_ORP(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","ОтчетОРозничныхПродажах"); 
		$pravilo->addChild("ПослеЗагрузки",'Объект.СчетКасса = ПланыСчетов.Хозрасчетный.КассаОрганизации; Для каждого СтрокаТЧ Из Объект.Товары Цикл Если НЕ ЗначениеЗаполнено(СтрокаТЧ.СчетДоходов) тогда СтрокаТЧ.СчетДоходов = ПланыСчетов.Хозрасчетный.ВыручкаНеЕНВД; КонецЕсли; Если НЕ ЗначениеЗаполнено(СтрокаТЧ.СчетРасходов) тогда СтрокаТЧ.СчетРасходов = ПланыСчетов.Хозрасчетный.СебестоимостьПродажНеЕНВД; КонецЕсли; СтрокаТЧ.СчетУчета = ПланыСчетов.Хозрасчетный.ТоварыНаСкладах; КонецЦикла; Для каждого СтрокаТЧВозвраты Из Объект.Возвраты Цикл Если НЕ ЗначениеЗаполнено(СтрокаТЧВозвраты.СчетДоходов) тогда СтрокаТЧВозвраты.СчетДоходов = ПланыСчетов.Хозрасчетный.ВыручкаНеЕНВД; КонецЕсли; Если НЕ ЗначениеЗаполнено(СтрокаТЧВозвраты.СчетРасходов) тогда СтрокаТЧВозвраты.СчетРасходов = ПланыСчетов.Хозрасчетный.СебестоимостьПродажНеЕНВД; КонецЕсли; СтрокаТЧВозвраты.СчетУчета = ПланыСчетов.Хозрасчетный.ТоварыНаСкладах; КонецЦикла; Объект.Записать(); РежимЗаписи= "Проведение";');
		$pravilo->addChild("Источник","ДокументСсылка.ОтчетОРозничныхПродажах"); 
		$pravilo->addChild("Приемник","ДокументСсылка.ОтчетОРозничныхПродажах"); 
	}

	private static function pravila_obmena_kontragent(&$pravila_konvertacii_obektov){
		$pravilo=$pravila_konvertacii_obektov->addChild("Правило");
		$pravilo->addChild("Код","Контрагенты"); 
		$pravilo->addChild("ПоследовательностьПолейПоиска",'Если НомерВариантаПоиска = 1 Тогда Если СвойстваПоиска["ИНН"] = "0" или СвойстваПоиска["ИНН"] = "" Тогда СтрокаИменСвойствПоиска = "Наименование, ЭтоГруппа"; Иначе СтрокаИменСвойствПоиска = "ИНН, ЭтоГруппа"; КонецЕсли; Иначе СтрокаИменСвойствПоиска = "Наименование, ЭтоГруппа"; КонецЕсли;');
		$pravilo->addChild("СинхронизироватьПоИдентификатору","true"); 
		$pravilo->addChild("ПродолжитьПоискПоПолямПоискаЕслиПоИдентификаторуНеНашли","true");
		$pravilo->addChild("Источник","СправочникСсылка.Контрагенты"); 
		$pravilo->addChild("Приемник","СправочникСсылка.Контрагенты"); 
	}

	private static function create_object_edizm(&$xml,&$npp){
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.КлассификаторЕдиницИзмерения"); $obj->addAttribute("ИмяПравила","");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Код"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","796");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","шт.");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НаименованиеПолное"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Штука");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
  		$svoistva->addChild("Значение","false");  
		return $npp;
	}

	private static function create_object_valuta(&$xml,&$npp){
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.Валюты"); $obj->addAttribute("ИмяПравила","");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Код"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","643");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","RUB");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НаименованиеПолное"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Российский рубль");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
  		$svoistva->addChild("Значение","false");  
		return $npp;
	}

	private static function create_object_org(&$xml,&$npp,$company_id){
		$npp++;
		$db = DB::getInstance();
		$company=$db->getRow("select * from company where id=?i",$company_id);
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.Организации"); $obj->addAttribute("ИмяПравила","");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",$company['inn']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КПП"); $svoistva->addAttribute("Тип","Строка");
		if(!empty($company['kpp'])) $svoistva->addChild("Значение",$company['kpp']);
		else $svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",$company['name']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НаименованиеПолное"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",$company['name']);
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","РайонныйКоэффициент"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ЮридическоеФизическоеЛицо"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ЮридическоеФизическоеЛицо");
		if((int)$company['type']==3) $svoistva->addChild("Значение","ФизическоеЛицо");
		else $svoistva->addChild("Значение","ЮридическоеЛицо");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
  		$svoistva->addChild("Значение","false");  
		return $npp;
	}

	private static function create_object_kontragent(&$xml,&$npp,$company_id){
		
		$db = DB::getInstance();
		$company=$db->getRow("select * from company where id=?i",$company_id);
		if(!isset(self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($company['id'],12,"0",STR_PAD_LEFT)])){
			$npp++;
			$name=str_replace('"','',preg_replace("/(ООО|АО|ИП)\s+(\S+)(.+)/","$2$3 $1",$company['name']));
			$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.Контрагенты"); $obj->addAttribute("ИмяПравила","Контрагенты");
			$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($company['id'],12,"0",STR_PAD_LEFT));
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",$company['inn']);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",$name);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ЭтоГруппа"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",$company['inn']);
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КПП"); $svoistva->addAttribute("Тип","Строка");
			if(!empty($company['kpp'])) $svoistva->addChild("Значение",$company['kpp']);
			else $svoistva->addChild("Пусто");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
			
			$svoistva->addChild("Значение",$name);
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НаименованиеПолное"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",$company['name']);
			//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","РайонныйКоэффициент"); $svoistva->addAttribute("Тип","Число");
			//$svoistva->addChild("Значение","1");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ЮридическоеФизическоеЛицо"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ЮридическоеФизическоеЛицо");
			if((int)$company['type']==3 || (int)$company['type']==1) $svoistva->addChild("Значение","ФизическоеЛицо");
			else $svoistva->addChild("Значение","ЮридическоеЛицо");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false"); 
			self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($company['id'],12,"0",STR_PAD_LEFT)]=$npp;
			return $npp;
		}

	}

	private static function create_object_sklad(&$xml,&$npp,$sklad_id){	
		$db = DB::getInstance();
		
		if(!isset(self::$_sklads["53544f52-4500-0000-0000-".str_pad($sklad_id,12,"0",STR_PAD_LEFT)])){
			$npp++;
			$sklad=$db->getRow("select * from sklad where id=?i",$sklad_id);
			$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.Склады"); $obj->addAttribute("ИмяПравила","");
			$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение","53544f52-4500-0000-0000-".str_pad($sklad['id'],12,"0",STR_PAD_LEFT));
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ЭтоГруппа"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ТипСклада"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ТипыСкладов");
			$svoistva->addChild("Значение","РозничныйМагазин");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",$sklad['name']);
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение","Импортировано из sort1.pro");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");  
			self::$_sklads["53544f52-4500-0000-0000-".str_pad($sklad_id,12,"0",STR_PAD_LEFT)]=$npp;
			return $npp;
		}
	}

	private static function create_object_gtd(&$xml,&$npp,$gtd_id){	
		$db = DB::getInstance();
		
		if(!isset(self::$_gtds["5f5a4f52-4510-0000-0000-".str_pad($gtd_id,12,"0",STR_PAD_LEFT)])){
			$npp++;
			$gtd=$db->getRow("select * from gtd where id=?i",$gtd_id);
			$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.НомераГТД"); $obj->addAttribute("ИмяПравила","");
			$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение","5f5a4f52-4510-0000-0000-".str_pad($gtd['id'],12,"0",STR_PAD_LEFT));
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ЭтоГруппа"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","РегистрационныйНомер"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",$gtd['custom_num']."/".$gtd['doc_date']."/".$gtd['num']);

			//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
			//$svoistva->addChild("Значение",$sklad['name']);
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение","Импортировано из sort1.pro");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");  
			self::$_gtds["5f5a4f52-4510-0000-0000-".str_pad($sklad_id,12,"0",STR_PAD_LEFT)]=$npp;
			return $npp;
		}
	}

	private static function create_object_kontragent_dogovor_pokupatel(&$xml,&$npp,$company_id){
		
		$db = DB::getInstance();
		if(!isset(self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($company_id,12,"0",STR_PAD_LEFT)])){
			
			$company=$db->getRow("select * from company where id=?i",$company_id);
			
			$npp++;
			$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов"); $obj->addAttribute("ИмяПравила","");
			$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
			
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидДоговора"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
			$svoistva->addChild("Значение","СПокупателем");
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

			$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",self::$_my_company['inn']);

			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Владелец"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",$npp-1);
			$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($company['id'],12,"0",STR_PAD_LEFT));
			

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false"); 
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ЭтоГруппа"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаВзаиморасчетов"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
			$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","1");
			$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","643");
			self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($company_id,12,"0",STR_PAD_LEFT)]=$npp;
			return $npp;
		}
	}

	private static function create_object_kontragent_dogovor_postav(&$xml,&$npp,$company_id){
		
		$db = DB::getInstance();
		if(!isset(self::$_kontragent_dogovors_pos["454c4945-4e54-0000-0000-".str_pad($company_id,12,"0",STR_PAD_LEFT)])){
			if(count((array)self::$_my_company)==0) {
				self::$_my_company=$db->getRow("select c.*,tt.is_nds,tt.tax_rate from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
			}
			$company=$db->getRow("select * from company where id=?i",$company_id);
			
			$npp++;
			$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов"); $obj->addAttribute("ИмяПравила","");
			$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
			
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидДоговора"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
			$svoistva->addChild("Значение","СПоставщиком");
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

			$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",self::$_my_company['inn']);

			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Владелец"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",$npp-1);
			$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($company['id'],12,"0",STR_PAD_LEFT));
			

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false"); 
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ЭтоГруппа"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаВзаиморасчетов"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
			$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","1");
			$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","643");
			self::$_kontragent_dogovors_pos["454c4945-4e54-0000-0000-".str_pad($company_id,12,"0",STR_PAD_LEFT)]=$npp;
			return $npp;
		}
	}

	private static function create_object_nomenklatura(&$xml,&$npp,$detail,$usluga=false){
		if(!isset(self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]) 
			&& !isset(self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$detail['detail_id']),12,"0",STR_PAD_LEFT)])
			&& !isset(self::$_nomen["514e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)])){
			$npp++;
			$db = DB::getInstance();
			$gtds=$db->getAll("select * from gtd where id in (select gtd_id from gtd_to_doc_det where document_details_id=?i)",$detail['id']);
			$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","СправочникСсылка.Номенклатура"); $obj->addAttribute("ИмяПравила","");
			$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
			
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if($usluga){
				if((int)$detail['job_id']>0) {
					$svoistva->addChild("Значение","514e4f4d-454e-434c-4154-".str_pad($detail['job_id'],12,"0",STR_PAD_LEFT));
					self::$_nomen["514e4f4d-454e-434c-4154-".str_pad($detail['job_id'],12,"0",STR_PAD_LEFT)]=$npp;
				}
				//if((int)$detail['detail_id']<0) {
				//	$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$detail['detail_id']),12,"0",STR_PAD_LEFT));
				//	self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$detail['detail_id']),12,"0",STR_PAD_LEFT)]=$npp;
				//}
			}
			else {
				if((int)$detail['detail_id']>0) {
					$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT));
					self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
				}
				if((int)$detail['detail_id']<0) {
					$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$detail['detail_id']),12,"0",STR_PAD_LEFT));
					self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$detail['detail_id']),12,"0",STR_PAD_LEFT)]=$npp;
				}
			}
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ЭтоГруппа"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Код"); $svoistva->addAttribute("Тип","Строка");
			//if(!empty($detail['kpp'])) $svoistva->addChild("Значение",$company['kpp']);
			//else 
			$svoistva->addChild("Значение",$detail['article']);

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение","Импортировано из sort1.pro");

			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Наименование"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",str_replace(array("<",">","&"),"",$detail['name']));
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НаименованиеПолное"); $svoistva->addAttribute("Тип","Строка");
			$svoistva->addChild("Значение",str_replace(array("<",">","&"),"",$detail['name']));
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Артикул"); $svoistva->addAttribute("Тип","Строка");
			if(!empty($detail['article'])) $svoistva->addChild("Значение",$detail['article']);
			else $svoistva->addChild("Пусто");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидСтавкиНДС"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыСтавокНДС");
			switch((int)$detail['tax']) {
				case 20: $svoistva->addChild("Значение","Общая"); break;
				case 20.12: $svoistva->addChild("Значение","ОбщаяРасчетная"); break;
				case 10: $svoistva->addChild("Значение","Пониженная"); break;
				case 10.11: $svoistva->addChild("Значение","ПониженнаяРасчетная"); break;
				default: $svoistva->addChild("Значение","БезНДС");
			}
			/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","РайонныйКоэффициент"); $svoistva->addAttribute("Тип","Число");
			$svoistva->addChild("Значение","1");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ЮридическоеФизическоеЛицо"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ЮридическоеФизическоеЛицо");
			if((int)$company['type']==3) $svoistva->addChild("Значение","ФизическоеЛицо");
			else $svoistva->addChild("Значение","ЮридическоеЛицо"); */
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
			$svoistva->addChild("Значение","false");
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Услуга"); $svoistva->addAttribute("Тип","Булево");
			if($usluga) $svoistva->addChild("Значение","true"); 
			else $svoistva->addChild("Значение","false");   
			$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ЕдиницаИзмерения"); $svoistva->addAttribute("Тип","СправочникСсылка.КлассификаторЕдиницИзмерения");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","3");
			$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","796");
			return $npp;
		}
	}

	private static function create_object_schetfactura_vydan(&$xml,&$npp,$document_id){
		$db = DB::getInstance();
		if(isset(self::$_schfact["53647453-6572-7669-4365-".str_pad($document_id,12,"0",STR_PAD_LEFT)])) return $npp;
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		$doc_jobs=$db->getAll("select dj.*,sj.name as name from document_jobs dj 
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.deleted=0 and dj.document_id=?i",$document_id);
		//$document_sum=0;
		foreach($doc_jobs as $dj_key=>$dj_val){
			self::create_object_nomenklatura($xml,$npp,$dj_val,true);
			$document_sum+=$dj_val['count']*$dj_val['price'];
		}

		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.СчетФактураВыданный"); $obj->addAttribute("ИмяПравила","СчетФактураВыданный");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","53647453-6572-7669-4365-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		self::$_schfact["53647453-6572-7669-4365-".str_pad($document_id,12,"0",STR_PAD_LEFT)]=$npp;
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		if($document['chf_date']!="0000-00-00"){
			$svoistva->addChild("Значение",str_replace(" ","T",$document['chf_date']));
		}
		else
			$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВыставления"); $svoistva->addAttribute("Тип","Дата");
		if($document['chf_date']!="0000-00-00"){
			$svoistva->addChild("Значение",str_replace(" ","T",$document['chf_date']));
		}
		else
			$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		if(!empty($document['chf_number'])) 
			$svoistva->addChild("Значение",$document['chf_number']);
		else
			$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));
		
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka2=$svoistva->addChild("Ссылка"); $ssylka2->addAttribute("Нпп","2");
		$svoistva=$ssylka2->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","ДокументыОснования");
		$zapis=$table->addChild("Запись");
		$svoistva1=$zapis->addChild("Свойство"); $svoistva1->addAttribute("Имя","ДокументОснование"); $svoistva1->addAttribute("Тип","ДокументСсылка.РеализацияТоваровУслуг");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_realiz["73647453-6572-7669-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","73647453-6572-7669-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT));

	}

	private static function create_object_schetfactura_poluch(&$xml,&$npp,$document_id){
		$db = DB::getInstance();
		if(isset(self::$_schfact["55647453-6572-7669-4365-".str_pad($document_id,12,"0",STR_PAD_LEFT)])) return $npp;
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		self::create_object_kontragent_dogovor_postav($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		$doc_jobs=$db->getAll("select dj.*,sj.name as name from document_jobs dj 
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.deleted=0 and dj.document_id=?i",$document_id);
		//$document_sum=0;
		foreach($doc_jobs as $dj_key=>$dj_val){
			self::create_object_nomenklatura($xml,$npp,$dj_val,true);
			$document_sum+=$dj_val['count']*$dj_val['price'];
		}

		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.СчетФактураПолученный"); $obj->addAttribute("ИмяПравила","СчетФактураПолученный");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","55647453-6572-7669-4365-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		self::$_schfact["55647453-6572-7669-4365-".str_pad($document_id,12,"0",STR_PAD_LEFT)]=$npp;
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		//if($document['chf_date']!="0000-00-00"){
		//	$svoistva->addChild("Значение",str_replace(" ","T",$document['chf_date']));
		//}
		//else
			$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		//if(!empty($document['chf_number'])) 
		//	$svoistva->addChild("Значение",$document['chf_number']);
		//else
			$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));
		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		if($document['chf_date']!="0000-00-00"){
			$svoistva->addChild("Значение",str_replace(" ","T",$document['chf_date']));
		}
		else {
			if($document['document_date']!="0000-00-00"){
				$svoistva->addChild("Значение",str_replace(" ","T",$document['document_date']));
			}
			else {
				$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
			}
		}
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",$document['chf_number']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["454c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПоставщиком");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka2=$svoistva->addChild("Ссылка"); $ssylka2->addAttribute("Нпп","2");
		$svoistva=$ssylka2->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);

		$svoistva1=$obj->addChild("Свойство"); $svoistva1->addAttribute("Имя","ДокументОснование"); $svoistva1->addAttribute("Тип","ДокументСсылка.ПоступлениеТоваровУслуг");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_realiz["73647444-6f63-756d-656e-".str_pad($document_id,12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","73647444-6f63-756d-656e-".str_pad($document_id,12,"0",STR_PAD_LEFT));
	}

	private static function create_object_realizaciya(&$xml,&$npp,$document_id){		
		$db = DB::getInstance();
		if(isset(self::$_realiz["73647453-6572-7669-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT)])) return $npp;
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		//$sklad_npp=$npp;
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		$doc_jobs=$db->getAll("select dj.*,sj.name as name from document_jobs dj 
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.deleted=0 and dj.document_id=?i",$document_id);
		//$document_sum=0;
		foreach($doc_jobs as $dj_key=>$dj_val){
			self::create_object_nomenklatura($xml,$npp,$dj_val,true);
			$document_sum+=$dj_val['count']*$dj_val['price'];
		}
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.РеализацияТоваровУслуг"); $obj->addAttribute("ИмяПравила","РеализацияТоваровУслуг");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647453-6572-7669-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		self::$_realiz["73647453-6572-7669-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT)]=$npp;
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		if($document['document_date']!="0000-00-00"){
			//$doc_date_create=explode(" ",$document['document_date']);
			$svoistva->addChild("Значение",$document['document_date']."T".date("H:i:s",strtotime($document['create_date'])));
		}
		else
			$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		if(!empty($document['chf_number'])) 
			$svoistva->addChild("Значение",$document['chf_number']);
		else
			$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));
		//$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийРеализацияТоваров");
		$svoistva->addChild("Значение","ПродажаКомиссия");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаВключаетНДС");  $svoistva->addAttribute("Тип","Булево");
		if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		else $svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Товары");
		foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");

			$svoistva=$zapis->addChild("Свойство"); $svoistva->addAttribute("Имя","ЕдиницаИзмерения"); $svoistva->addAttribute("Тип","СправочникСсылка.КлассификаторЕдиницИзмерения");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","3");
			$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","796");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Коэффициент"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']*$dd_val['price']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dd_val['tax']) {
					case 18: $svoistvo->addChild("Значение","НДС18"); break;
					case 18.118: $svoistvo->addChild("Значение","НДС18_118"); break;
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				if((int)$dd_val['tax']>0) 
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['price'])/(100+$dd_val['tax']))*$dd_val['tax'],2));
				else 
				$svoistvo->addChild("Значение","0.00");
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Услуги");
		foreach($doc_jobs as $dj_key=>$dj_val){
			$zapis=$table->addChild("Запись");

			$svoistva=$zapis->addChild("Свойство"); $svoistva->addAttribute("Имя","ЕдиницаИзмерения"); $svoistva->addAttribute("Тип","СправочникСсылка.КлассификаторЕдиницИзмерения");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","3");
			$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","796");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dj_val['count']);
			//$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Коэффициент"); $svoistvo->addAttribute("Тип","Число");
			//$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dj_val['job_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["514e4f4d-454e-434c-4154-".str_pad($dj_val['job_id'],12,"0",STR_PAD_LEFT)]);
			//if((int)$dd_val['detail_id']<0)
			//	$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dj_val['detail_id']>0) {
				$svoistva->addChild("Значение","514e4f4d-454e-434c-4154-".str_pad($dj_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			//if((int)$dd_val['detail_id']<0) {
			//	$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			//}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dj_val['count']*$dj_val['price']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dj_val['tax']) {
					case 18: $svoistvo->addChild("Значение","НДС18"); break;
					case 18.118: $svoistvo->addChild("Значение","НДС18_118"); break;
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				if((int)$dj_val['tax']>0) 
					$svoistvo->addChild("Значение",round((($dj_val['count']*$dj_val['price'])/(100+$dj_val['tax']))*$dj_val['tax'],2));
				else 
				$svoistvo->addChild("Значение","0.00");
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dj_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		return $npp;
	}

	private static function create_object_rozn_prod(&$xml,&$npp,$date,$date_type,$parsed){		
		$db = DB::getInstance();
		if(isset($date_type) && $date_type=='document_date'){
			$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.document_date>=?s and d.document_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		else {
			$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.create_date>=?s and d.create_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		if(isset($date_type) && $date_type=='document_date'){
			$return_documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.document_date>=?s and d.document_date<=?s and d.main_company=?i and d.type_id=6 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		else {
			$return_documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.create_date>=?s and d.create_date<=?s and d.main_company=?i and d.type_id=6 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		if(empty($documents) && empty($return_documents)) return $npp;
		if(isset(self::$_rozn_prod["73146453-6572-7769-6365-".str_pad($documents[0]['id'],12,"0",STR_PAD_LEFT)])) return $npp;
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$documents[0]['id'],$_SESSION['main_company']);
		//self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		//self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$_SESSION['my_sklad_id']);
		//$sklad_npp=$npp;
		$doc_details=$db->getAll("select * from document_details where document_id in (?a) and deleted=0",array_column($documents,"id"));
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		/*$doc_jobs=$db->getAll("select dj.*,sj.name as name from document_jobs dj 
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.deleted=0 and dj.document_id in (?a)",array_column($documents,"id"));
		//$document_sum=0;
		foreach($doc_jobs as $dj_key=>$dj_val){
			self::create_object_nomenklatura($xml,$npp,$dj_val,true);
			$document_sum+=$dj_val['count']*$dj_val['price'];
		}*/
		$return_doc_details=$db->getAll("select * from document_details where document_id in (?a) and deleted=0",array_column($return_documents,"id"));
		foreach($return_doc_details as $rdd_key=>$rdd_val){
			self::create_object_nomenklatura($xml,$npp,$rdd_val);
			$document_sum+=$rdd_val['count']*$rdd_val['price'];
		}
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ОтчетОРозничныхПродажах"); $obj->addAttribute("ИмяПравила","ОтчетОРозничныхПродажах");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73146453-6572-7769-6365-".str_pad($documents[0]['id'],12,"0",STR_PAD_LEFT));
		self::$_rozn_prod["73146453-6572-7769-6365-".str_pad($documents[0]['id'],12,"0",STR_PAD_LEFT)]=$npp;
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		/*if($documents[0]['document_date']!="0000-00-00"){
			//$doc_date_create=explode(" ",$document['document_date']);
			$svoistva->addChild("Значение",$documents[0]['document_date']." ".date("H:i:s",strtotime($documents[0]['create_date'])));
		}
		else*/
			$svoistva->addChild("Значение",$date."T23:59:59");
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		/*if(!empty($documents[0]['chf_number'])) 
			$svoistva->addChild("Значение",$documents[0]['chf_number']);
		else*/
			$svoistva->addChild("Значение","0000-".str_pad($documents[0]['id'],6,"0",STR_PAD_LEFT));
		//$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийОтчетОРозничныхПродажах");
		$svoistva->addChild("Значение","ОтчетККМОПродажах");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","КПП"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['kpp']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($_SESSION['my_sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($_SESSION['my_sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаВключаетНДС");  $svoistva->addAttribute("Тип","Булево");
		if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		else $svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);
		$table=$obj->addChild("ТабличнаяЧасть"); 
			$table->addAttribute("Имя","Оплата"); 
			$table->addAttribute("НеОчищать","true");
		// добавить сюда оплаты по безналу
		$payments_sum_by_card=$db->getOne("SELECT SUM(summ) FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type=2 
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction=1 and deleted=0 GROUP BY main_company_id 
		",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		if($payments_sum_by_card){
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); 
			$svoistvo->addAttribute("Имя","СуммаОплаты");
			$svoistvo->addAttribute("Тип","Число");
			$znachenie=$svoistvo->addChild("Значение",$payments_sum_by_card);
			$svoistvo=$zapis->addChild("Свойство"); 
			$svoistvo->addAttribute("Имя","ВидОплаты");
			$svoistvo->addAttribute("Тип","Строка");
			$znachenie=$svoistvo->addChild("Значение","Платежная карта");
		}

		$payments_sum_by_card_return=$db->getOne("SELECT SUM(summ) FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type in (2,6)
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction in (3,4,5) and deleted=0 GROUP BY main_company_id 
		",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		if((float)$payments_sum_by_card_return>0){
			$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","ВозвратОплаты");
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); 
				$svoistvo->addAttribute("Имя","СуммаОплаты");
				$svoistvo->addAttribute("Тип","Число");
					$znachenie=$svoistvo->addChild("Значение",$payments_sum_by_card_return);
		}

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Товары");
		foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']*$dd_val['price']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СчетУчетаНДСПоРеализации"); $svoistvo->addAttribute("Тип","ПланСчетовСсылка.Хозрасчетный");
			$svoistvo->addChild("Значение","Продажи_НДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dd_val['tax']) {
					case 18: $svoistvo->addChild("Значение","НДС18"); break;
					case 18.118: $svoistvo->addChild("Значение","НДС18_118"); break;
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				if((int)$dd_val['tax']>0) 
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['price'])/(100+$dd_val['tax']))*$dd_val['tax'],2));
				else 
				$svoistvo->addChild("Значение","0.00");
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Возвраты");
		foreach($return_doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']*$dd_val['price']);

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","ДатаРеализации"); $svoistvo->addAttribute("Тип","Дата");
			$sale_date_zd_id=$db->getOne("SELECT zakaz_detail_id FROM document_details WHERE id=?i",$dd_val["id"]);
			$sale_doc=$db->getRow("SELECT dd.tax,d.create_date,d.id FROM document_details dd 
			LEFT JOIN document d ON (d.id=dd.document_id)
			WHERE dd.zakaz_detail_id=?i AND d.type_id=2 limit 1",$sale_date_zd_id);
			
			
			$svoistvo->addChild("Значение",str_replace(" ","T",$sale_doc['create_date']));
			
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СчетУчетаНДСПоРеализации"); $svoistvo->addAttribute("Тип","ПланСчетовСсылка.Хозрасчетный");
			$svoistvo->addChild("Значение","Продажи_НДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$sale_doc['tax']) {
					case 18: $svoistvo->addChild("Значение","НДС18"); break;
					case 18.118: $svoistvo->addChild("Значение","НДС18_118"); break;
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				if((int)$sale_doc['tax']>0) 
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['price'])/(100+$sale_doc['tax']))*$sale_doc['tax'],2));
				else 
				$svoistvo->addChild("Значение","0.00");
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		return $npp;
	}

	/*
	private static function create_object_rozn_prod_return(&$xml,&$npp,$document_id){		
		$db = DB::getInstance();
		if(isset(self::$_rozn_prod["73146453-6572-7769-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT)])) return $npp;
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
		//self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		//self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		//$sklad_npp=$npp;
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		$doc_jobs=$db->getAll("select dj.*,sj.name as name from document_jobs dj 
		left join service_jobs sj on (sj.id=dj.job_id)
		where dj.deleted=0 and dj.document_id=?i",$document_id);
		//$document_sum=0;
		foreach($doc_jobs as $dj_key=>$dj_val){
			self::create_object_nomenklatura($xml,$npp,$dj_val,true);
			$document_sum+=$dj_val['count']*$dj_val['price'];
		}
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ОтчетОРозничныхПродажах"); $obj->addAttribute("ИмяПравила","ОтчетОРозничныхПродажах");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73146453-6572-7769-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		self::$_rozn_prod["73146453-6572-7769-6365-".str_pad($document_id,12,"0",STR_PAD_LEFT)]=$npp;
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		if($document['document_date']!="0000-00-00"){
			//$doc_date_create=explode(" ",$document['document_date']);
			$svoistva->addChild("Значение",$document['document_date']."T".date("H:i:s",strtotime($document['create_date'])));
		}
		else
			$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		if(!empty($document['chf_number'])) 
			$svoistva->addChild("Значение",$document['chf_number']);
		else
			$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));
		//$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийОтчетОРозничныхПродажах");
		$svoistva->addChild("Значение","ОтчетККМОПродажах");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","КПП"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['kpp']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаВключаетНДС");  $svoistva->addAttribute("Тип","Булево");
		if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		else $svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Оплата");
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Оплата"); $table->addAttribute("НеОчищать","true");
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Возвраты");
		foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']*$dd_val['price']);

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","ДатаРеализации"); $svoistvo->addAttribute("Тип","Дата");
			$sale_date_zd_id=$db->getOne("SELECT zakaz_detail_id FROM document_details WHERE id=?i",$dd_val["id"]);
			$sale_date=$db->getOne("SELECT dd.create_date FROM document_details dd 
			LEFT JOIN document d ON (d.id=dd.document_id)
			WHERE dd.zakaz_detail_id=?i AND d.type_id=2 limit 1",$sale_date_zd_id);
			
			$svoistvo->addChild("Значение",str_replace(" ","T",$sale_date));
			
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СчетУчетаНДСПоРеализации"); $svoistvo->addAttribute("Тип","ПланСчетовСсылка.Хозрасчетный");
			$svoistvo->addChild("Значение","НДСпоПриобретеннымМПЗ");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dd_val['tax']) {
					case 18: $svoistvo->addChild("Значение","НДС18"); break;
					case 18.118: $svoistvo->addChild("Значение","НДС18_118"); break;
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				if((int)$dd_val['tax']>0) 
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['price'])/(100+$dd_val['tax']))*$dd_val['tax'],2));
				else 
				$svoistvo->addChild("Значение","0.00");
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		return $npp;
	}
	*/

	private static function create_object_prihod(&$xml,&$npp,$document_id){
		
		$db = DB::getInstance();
		$document=$db->getRow("select * from document where id=?i and main_company=?i and deleted=0",$document_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		self::create_object_kontragent_dogovor_postav($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		//$sklad_npp=$npp;
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ПоступлениеТоваровУслуг"); $obj->addAttribute("ИмяПравила","ПоступлениеТоваровУслуг");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647444-6f63-756d-656e-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		self::$_realiz["73647444-6f63-756d-656e-".str_pad($document_id,12,"0",STR_PAD_LEFT)]=$npp;
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		//$svoistva->addChild("Значение",$document['document_date']);
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		//if(!empty($document['number'])) 
		//	$svoistva->addChild("Значение",$document['number']); 
		//else 
			$svoistva->addChild("Значение","0000-".str_pad($document['id'],6,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийПоступлениеТоваровУслуг");
		$svoistva->addChild("Значение","ПокупкаКомиссия");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pos["454c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПоставщиком");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		if(empty($document['number'])) $svoistva->addChild("Пусто");
		else $svoistva->addChild("Значение",$document['number']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		if(empty($document['document_date'])) $svoistva->addChild("Пусто");
		else $svoistva->addChild("Значение",$document['document_date']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаВключаетНДС");  $svoistva->addAttribute("Тип","Булево");
		if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		else $svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);
		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Товары");
		foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");

			$svoistva=$zapis->addChild("Свойство"); $svoistva->addAttribute("Имя","ЕдиницаИзмерения"); $svoistva->addAttribute("Тип","СправочникСсылка.КлассификаторЕдиницИзмерения");
			$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","3");
			$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
			$svoistva1->addChild("Значение","796");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Коэффициент"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']*$dd_val['price']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dd_val['tax']) {
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				if((int)$dd_val['tax']>0) 
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['price'])/(100+$dd_val['tax']))*$dd_val['tax'],2));
				else 
				$svoistvo->addChild("Значение","0.00");
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		return $npp;
	}

	private static function create_object_return_client(&$xml,&$npp,$document_id){
		
		$db = DB::getInstance();
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		self::create_object_kontragent_dogovor_postav($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		//$realiz_doc=$db->getOne("select id from document where zakaz_id=?i and type_id=2",$document['zakaz_id']);
		//self::create_object_realizaciya($xml,$npp,$realiz_doc);
		//$sklad_npp=$npp;
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['price'];
		}
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ВозвратТоваровОтПокупателя"); $obj->addAttribute("ИмяПравила","ВозвратТоваровОтПокупателя");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647444-6f63-756d-656e-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","В-".$document['id']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийВозвратТоваровОтПокупателя");
		$svoistva->addChild("Значение","Товары");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pos["454c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		if(!empty($document['number'])) $svoistva->addChild("Пусто");
		else $svoistva->addChild("Значение",$document['number']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		if(!empty($document['document_date'])) $svoistva->addChild("Пусто");
		else $svoistva->addChild("Значение",$document['document_date']); */

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаВключаетНДС");  $svoistva->addAttribute("Тип","Булево");
		if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		else $svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДокументОснование"); $svoistva->addAttribute("Тип","ДокументСсылка.РеализацияТоваровУслуг");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_realiz["73647453-6572-7669-6365-".str_pad($realiz_doc,12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","73647453-6572-7669-6365-".str_pad($realiz_doc,12,"0",STR_PAD_LEFT)); */

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Товары");
		foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);
			/*$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Коэффициент"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1"); */

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",round((float)$dd_val['count']*(float)$dd_val['price'],2));
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Себестоимость"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['dealer_price']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dd_val['tax']) {
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				//if((int)$dd_val['tax']>0) 
				if((int)self::$_my_company['is_nds']==1){
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['price'])/(100+$dd_val['tax']))*$dd_val['tax'],2));
				}
				else {
					$svoistvo->addChild("Значение","0.00");
				}
			}
			else $svoistvo->addChild("Значение","БезНДС");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		return $npp;
	}

	private static function create_object_return_postav(&$xml,&$npp,$document_id){
		
		$db = DB::getInstance();
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$document['company_id']);
		//$kontragent_npp=$npp;
		self::create_object_kontragent_dogovor_postav($xml,$npp,$document['company_id']);
		//$kontragent_dogovor_npp=$npp;
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		//$realiz_doc=$db->getOne("select id from document where zakaz_id=?i and type_id=2",$document['zakaz_id']);
		//self::create_object_realizaciya($xml,$npp,$realiz_doc);
		//$sklad_npp=$npp;
		$doc_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$document_id);
		$document_sum=0;
		foreach($doc_details as $dd_key=>$dd_val){
			self::create_object_nomenklatura($xml,$npp,$dd_val);
			$document_sum+=$dd_val['count']*$dd_val['dealer_price'];
		}
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ВозвратТоваровПоставщику"); $obj->addAttribute("ИмяПравила","ВозвратТоваровПоставщику");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647452-6574-7572-6e50-".str_pad($document_id,12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",str_replace(" ","T",$document['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","ВП-".$document['id']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийВозвратТоваровПоставщику");
		$svoistva->addChild("Значение","ПокупкаКомиссия");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pos["454c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПоставщиком");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		if(!empty($document['number'])) $svoistva->addChild("Пусто");
		else $svoistva->addChild("Значение",$document['number']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		if(!empty($document['document_date'])) $svoistva->addChild("Пусто");
		else $svoistva->addChild("Значение",$document['document_date']); */

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($document['company_id'],12,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаВключаетНДС");  $svoistva->addAttribute("Тип","Булево");
		if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		else $svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$document_sum);

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДокументОснование"); $svoistva->addAttribute("Тип","ДокументСсылка.РеализацияТоваровУслуг");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_realiz["73647453-6572-7669-6365-".str_pad($realiz_doc,12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","73647453-6572-7669-6365-".str_pad($realiz_doc,12,"0",STR_PAD_LEFT)); */

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","Товары");
		foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Количество"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Коэффициент"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Номенклатура"); $svoistvo->addAttribute("Тип","СправочникСсылка.Номенклатура");
			if((int)$dd_val['detail_id']>0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT)]);
			if((int)$dd_val['detail_id']<0)
				$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_nomen["314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT)]);
			$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
			if((int)$dd_val['detail_id']>0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-".str_pad($dd_val['detail_id'],12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-".str_pad($detail['detail_id'],12,"0",STR_PAD_LEFT)]=$npp;
			}
			if((int)$dd_val['detail_id']<0) {
				$svoistva->addChild("Значение","314e4f4d-454e-434c-4154-2".str_pad(str_replace("-","",$dd_val['detail_id']),12,"0",STR_PAD_LEFT));
				//self::$_nomen["314e4f4d-454e-434c-4154-1".str_pad($detail['detail_id'],11,"0",STR_PAD_LEFT)]=$npp;
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Сумма"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['count']*$dd_val['daler_price']);
			/*$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Себестоимость"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['dealer_price']);*/
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			if((int)self::$_my_company['is_nds']==1){
				switch((float)$dd_val['tax']) {
					case 20: $svoistvo->addChild("Значение","НДС20"); break;
					case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
					case 10: $svoistvo->addChild("Значение","НДС10"); break;
					case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
					default: $svoistvo->addChild("Значение","БезНДС");
				}
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				//if((int)$dd_val['tax']>0) 
				if((int)self::$_my_company['is_nds']==1){
					$svoistvo->addChild("Значение",round((($dd_val['count']*$dd_val['dealer_price'])/(100+$dd_val['tax']))*$dd_val['tax'],2));
				}
				else {
					$svoistvo->addChild("Значение","0.00");
				}
			}
			else {
				$svoistvo->addChild("Значение","БезНДС");
				$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
				$svoistvo->addChild("Значение","0.00");
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$dd_val['dealer_price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		}
		return $npp;
	}

	private static function create_object_PKO(&$xml,&$npp,$payment_id){
		
		$db = DB::getInstance();
		$payment=$db->getRow("select * from payment where id=?i and main_company_id=?i and deleted=0",$payment_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$payment['company_id']);
		self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$payment['company_id']);
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ПриходныйКассовыйОрдер"); $obj->addAttribute("ИмяПравила","ПриходныйКассовыйОрдер");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647443-7265-6469-7443-".str_pad($payment['id'],12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",str_replace(" ","T",$payment['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","".$payment['id']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийПКО");
		$svoistva->addChild("Значение","ОплатаПокупателя");
		//$svoistva->addChild("Значение","ОплатаОтПокупателя");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		//if(!empty($document['number'])) $svoistva->addChild("Пусто");
		//else $svoistva->addChild("Значение",$document['number']);
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		//if(!empty($document['document_date'])) $svoistva->addChild("Пусто");
		//else $svoistva->addChild("Значение",$document['document_date']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));

		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		//$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		//$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		//$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПринятоОт");  $svoistva->addAttribute("Тип","Строка");
		//if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		//else 
		$company=$db->getRow("select * from company where id=?i",$payment['company_id']);
		$svoistva->addChild("Значение",$company['name']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Основание");  $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Выручка ККМ");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$payment['summ']);

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","РасшифровкаПлатежа");
		//foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","ДоговорКонтрагента"); $svoistvo->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КурсВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаПлатежа"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			//$tax_data=$db->getOne("select tax_rate from tax_type where id=?i and is_nds=1",$_my_company['tax_type']);
			switch((float)self::$_my_company['tax_rate']) {
				case 20: $svoistvo->addChild("Значение","НДС20"); break;
				case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
				case 10: $svoistvo->addChild("Значение","НДС10"); break;
				case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
				default: $svoistvo->addChild("Значение","БезНДС");
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
			if((float)self::$_my_company['tax_rate']>0) 
				$svoistvo->addChild("Значение",round(($payment['summ'])/(100+self::$_my_company['tax_rate'])*self::$_my_company['tax_rate'],2));
			else 
				$svoistvo->addChild("Значение","0.00");
			//$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			//$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		//}
		return $npp;
	}

	private static function create_object_PKO_daily_rozn(&$xml,&$npp,$date){
		
		$db = DB::getInstance();
		self::create_object_sklad($xml,$npp,$_SESSION['my_sklad_id']);
		$payments_sum=$db->getOne("SELECT SUM(summ) FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type IN (1,3,6,7) 
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction=1 and deleted=0 GROUP BY main_company_id 
		",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		// сюда не включаем оплату по карте, только нал в любом виде, перевод в том числе
		/*$payments_sum_by_card=$db->getOne("SELECT SUM(summ) FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type=2 
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction=1 GROUP BY main_company_id 
		",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		*/
		//$payment=$db->getRow("select * from payment where company_id=?i and main_company_id=?i and deleted=0 AND create_date>=?s AND create_date<=?s order by create_date limit 1",471,$_SESSION['main_company'],$date,$date." 23:59:59");
		$payment=$db->getRow("SELECT * FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type IN (1,3,6,7) 
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction=1 and deleted=0 limit 1",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		$payment['summ']=$payments_sum;
		if(empty($payment['id'])) return $npp;
		self::create_object_kontragent($xml,$npp,$payment['company_id']);
		self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$payment['company_id']);
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ПриходныйКассовыйОрдер"); $obj->addAttribute("ИмяПравила","ПриходныйКассовыйОрдер");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647443-7265-6469-7443-".str_pad($payment['id'],12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",$date);
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","".$payment['id']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийПКО");
		$svoistva->addChild("Значение","РозничнаяВыручка");
		//$svoistva->addChild("Значение","ОплатаОтПокупателя");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		//self::create_object_sklad($xml,$npp,$document['sklad_id']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($_SESSION['my_sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($_SESSION['my_sklad_id'],12,"0",STR_PAD_LEFT));
		
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		//if(!empty($document['number'])) $svoistva->addChild("Пусто");
		//else $svoistva->addChild("Значение",$document['number']);
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		//if(!empty($document['document_date'])) $svoistva->addChild("Пусто");
		//else $svoistva->addChild("Значение",$document['document_date']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));

		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		//$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		//$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		//$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПринятоОт");  $svoistva->addAttribute("Тип","Строка");
		//if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		//else 
		$company=$db->getRow("select * from company where id=?i",$payment['company_id']);
		$svoistva->addChild("Значение",$company['name']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Основание");  $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Выручка ККМ");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$payment['summ']);

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","РасшифровкаПлатежа");
		//foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","ДоговорКонтрагента"); $svoistvo->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КурсВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаПлатежа"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			//$tax_data=$db->getOne("select tax_rate from tax_type where id=?i and is_nds=1",$_my_company['tax_type']);
			switch((float)self::$_my_company['tax_rate']) {
				case 20: $svoistvo->addChild("Значение","НДС20"); break;
				case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
				case 10: $svoistvo->addChild("Значение","НДС10"); break;
				case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
				default: $svoistvo->addChild("Значение","БезНДС");
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
			if((float)self::$_my_company['tax_rate']>0) 
				$svoistvo->addChild("Значение",round(($payment['summ'])/(100+self::$_my_company['tax_rate'])*self::$_my_company['tax_rate'],2));
			else 
				$svoistvo->addChild("Значение","0.00");
			//$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			//$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		//}
		return $npp;
	}

	private static function create_object_RKO(&$xml,&$npp,$payment_id){
		
		$db = DB::getInstance();
		$payment=$db->getRow("select * from payment where id=?i and main_company_id=?i and deleted=0",$payment_id,$_SESSION['main_company']);
		self::create_object_kontragent($xml,$npp,$payment['company_id']);
		self::create_object_kontragent_dogovor_pokupatel($xml,$npp,$payment['company_id']);
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.ПриходныйКассовыйОрдер"); $obj->addAttribute("ИмяПравила","ПриходныйКассовыйОрдер");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647443-7265-6469-7443-".str_pad($payment['id'],12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",str_replace(" ","T",$payment['create_date']));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","".$payment['id']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийПКО");
		$svoistva->addChild("Значение","ВозвратПокупателю");
		//$svoistva->addChild("Значение","ОплатаОтПокупателя");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");

		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","НомерВходящегоДокумента"); $svoistva->addAttribute("Тип","Строка");
		//if(!empty($document['number'])) $svoistva->addChild("Пусто");
		//else $svoistva->addChild("Значение",$document['number']);
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДатаВходящегоДокумента"); $svoistva->addAttribute("Тип","Дата");
		//if(!empty($document['document_date'])) $svoistva->addChild("Пусто");
		//else $svoistva->addChild("Значение",$document['document_date']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));

		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");
		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","КурсВзаиморасчетов"); $svoistva->addAttribute("Тип","Число");
		//$svoistva->addChild("Значение","1");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva=$ssylka1->addChild("Свойство"); $svoistva->addAttribute("Имя","ИНН"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение",self::$_my_company['inn']);


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");


		//$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Склад"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		//$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT)]);
		//$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		//$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($document['sklad_id'],12,"0",STR_PAD_LEFT));

		//$my_company=$db->getRow("select c.*,tt.is_nds from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПринятоОт");  $svoistva->addAttribute("Тип","Строка");
		//if((int)self::$_my_company['is_nds']==1) $svoistva->addChild("Значение","true");
		//else 
		$company=$db->getRow("select * from company where id=?i",$payment['company_id']);
		$svoistva->addChild("Значение",$company['name']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Основание");  $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Выручка ККМ");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$payment['summ']);

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","РасшифровкаПлатежа");
		//foreach($doc_details as $dd_key=>$dd_val){
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","ДоговорКонтрагента"); $svoistvo->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КурсВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаПлатежа"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			//$tax_data=$db->getOne("select tax_rate from tax_type where id=?i and is_nds=1",$_my_company['tax_type']);
			switch((float)self::$_my_company['tax_rate']) {
				case 20: $svoistvo->addChild("Значение","НДС20"); break;
				case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
				case 10: $svoistvo->addChild("Значение","НДС10"); break;
				case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
				default: $svoistvo->addChild("Значение","БезНДС");
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
			if((float)self::$_my_company['tax_rate']>0) 
				$svoistvo->addChild("Значение",round(($payment['summ'])/(100+self::$_my_company['tax_rate'])*self::$_my_company['tax_rate'],2));
			else 
				$svoistvo->addChild("Значение","0.00");
			//$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","Цена"); $svoistvo->addAttribute("Тип","Число");
			//$svoistvo->addChild("Значение",$dd_val['price']);
			//$svoistvo->addChild("Значение",$dd_val['tax']);
		//}
		return $npp;
	}

	private static function create_object_RKO_daily_rozn(&$xml,&$npp,$date,$date_type="document_date"){
		
		$db = DB::getInstance();
		self::create_object_sklad($xml,$npp,$_SESSION['my_sklad_id']);
		$payments_sum=$db->getOne("SELECT SUM(summ) FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type=1 
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction in (3,4,5) and deleted=0 GROUP BY main_company_id 
		",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		// сюда не включаем оплату по карте, только нал в любом виде, перевод в том числе
		/*$payments_sum_by_card=$db->getOne("SELECT SUM(summ) FROM payment WHERE company_id IN (SELECT uc.company_id AS company_name FROM user_companys uc 
		LEFT JOIN company c ON (c.id=uc.company_id)
		WHERE uc.main_company_id=?i AND c.type=3 AND uc.deleted=0)
		AND payment_type=2 
		AND main_company_id=?i AND create_date>=?s AND create_date<=?s and payment_direction=1 GROUP BY main_company_id 
		",$_SESSION['main_company'],$_SESSION['main_company'],$date,$date." 23:59:59");
		*/
		$payment=$db->getRow("select * from payment where company_id=?i and main_company_id=?i and deleted=0 AND create_date>=?s AND create_date<=?s order by create_date limit 1",471,$_SESSION['main_company'],$date,$date." 23:59:59");
		$payment['summ']=$payments_sum;
		if((float)$payment['summ']<=0) return;
		self::create_object_kontragent($xml,$npp,471);
		self::create_object_kontragent_dogovor_pokupatel($xml,$npp,471);
		$npp++;
		$obj=$xml->addChild("Объект"); $obj->addAttribute("Нпп","$npp"); $obj->addAttribute("Тип","ДокументСсылка.РасходныйКассовыйОрдер"); $obj->addAttribute("ИмяПравила","РасходныйКассовыйОрдер");
 		$ssylka=$obj->addChild("Ссылка"); $ssylka->addAttribute("Нпп","$npp");
 		
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","{УникальныйИдентификатор}"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","73647443-7265-6469-7443-".str_pad($payment['id'],12,"0",STR_PAD_LEFT));
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Дата"); $svoistva->addAttribute("Тип","Дата");
		$svoistva->addChild("Значение",$date);
		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Номер"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","".$payment['id']);

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВидОперации"); $svoistva->addAttribute("Тип","ПеречислениеСсылка.ВидыОперацийРКО");
		$svoistva->addChild("Значение","ВозвратРозничномуПокупателю");
		//$svoistva->addChild("Значение","ОплатаОтПокупателя");
		//else 
		//$svoistva->addChild("Пусто");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
		//
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ВалютаДокумента"); $svoistva->addAttribute("Тип","СправочникСсылка.Валюты");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","1");
		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","Код"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","643");

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп","2");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
*/
		self::create_object_sklad($xml,$npp,$document['sklad_id']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Склады");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_sklads["53544f52-4500-0000-0000-".str_pad($_SESSION['my_sklad_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","53544f52-4500-0000-0000-".str_pad($_SESSION['my_sklad_id'],12,"0",STR_PAD_LEFT));
		
		if(isset($date_type) && $date_type=='document_date'){
			$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.document_date>=?s and d.document_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		else {
			$documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.create_date>=?s and d.create_date<=?s and d.main_company=?i and d.type_id=2 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		if(isset($date_type) && $date_type=='document_date'){
			$return_documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.document_date>=?s and d.document_date<=?s and d.main_company=?i and d.type_id=6 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		else {
			$return_documents=$db->getAll("SELECT d.id,d.company_id,c.name FROM document d 
			LEFT JOIN company c ON (c.id=d.company_id)
			where d.create_date>=?s and d.create_date<=?s and d.main_company=?i and d.type_id=6 and d.deleted=0 and c.type=3 ?p and sklad_id=?i",$date,$date." 23:59:59",$_SESSION['main_company'],$parsed,$_SESSION['my_sklad_id']);
		}
		$document=$db->getRow("select * from document where id=?i and main_company=?i",$documents[0]['id'],$_SESSION['main_company']);
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДокументОснование"); $svoistva->addAttribute("Тип","ДокументСсылка.ОтчетОРозничныхПродажах");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_rozn_prod["73146453-6572-7769-6365-".str_pad($documents[0]['id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","73146453-6572-7769-6365-".str_pad($documents[0]['id'],12,"0",STR_PAD_LEFT));

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Контрагент"); $svoistva->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","434c4945-4e54-0000-0000-".str_pad(471,12,"0",STR_PAD_LEFT));

		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ДоговорКонтрагента"); $svoistva->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistva->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");
		*/

		/*$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);
*/
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));


		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Комментарий"); $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Импортировано из sort1.pro");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПометкаУдаления"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Проведен"); $svoistva->addAttribute("Тип","Булево");
		$svoistva->addChild("Значение","false");

		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","ПринятоОт");  $svoistva->addAttribute("Тип","Строка");
		$company=$db->getRow("select * from company where id=?i",$payment['company_id']);
		$svoistva->addChild("Значение",$company['name']);
*/
		/*$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","Основание");  $svoistva->addAttribute("Тип","Строка");
		$svoistva->addChild("Значение","Выручка ККМ");*/
		$svoistva=$obj->addChild("Свойство"); $svoistva->addAttribute("Имя","СуммаДокумента"); $svoistva->addAttribute("Тип","Число");
		$svoistva->addChild("Значение",$payment['summ']);

		$table=$obj->addChild("ТабличнаяЧасть"); $table->addAttribute("Имя","РасшифровкаПлатежа");
			$zapis=$table->addChild("Запись");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","ДоговорКонтрагента"); $svoistvo->addAttribute("Тип","СправочникСсылка.ДоговорыКонтрагентов");
		$ssylka=$svoistvo->addChild("Ссылка"); $ssylka->addAttribute("Нпп",self::$_kontragent_dogovors_pok["434c4945-4e54-0000-0000-".str_pad(471,12,"0",STR_PAD_LEFT)]);
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","ВидДоговора"); $svoistva1->addAttribute("Тип","ПеречислениеСсылка.ВидыДоговоровКонтрагентов");
		$svoistva1->addChild("Значение","СПокупателем");
		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Наименование"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение","Основной договор");

		$svoistva=$ssylka->addChild("Свойство"); $svoistva->addAttribute("Имя","Организация"); $svoistva->addAttribute("Тип","СправочникСсылка.Организации");
		$ssylka1=$svoistva->addChild("Ссылка"); $ssylka1->addAttribute("Нпп","2");

		$svoistva1=$ssylka1->addChild("Свойство"); $svoistva1->addAttribute("Имя","ИНН"); $svoistva1->addAttribute("Тип","Строка");
		$svoistva1->addChild("Значение",self::$_my_company['inn']);

		$svoistva1=$ssylka->addChild("Свойство"); $svoistva1->addAttribute("Имя","Владелец"); $svoistva1->addAttribute("Тип","СправочникСсылка.Контрагенты");
		$ssylka1=$svoistva1->addChild("Ссылка"); $ssylka1->addAttribute("Нпп",self::$_kontragents["434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT)]);
		$svoistva2=$ssylka1->addChild("Свойство"); $svoistva2->addAttribute("Имя","{УникальныйИдентификатор}");  $svoistva2->addAttribute("Тип","Строка");
		$svoistva2->addChild("Значение","434c4945-4e54-0000-0000-".str_pad($payment['company_id'],12,"0",STR_PAD_LEFT));
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КратностьВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","КурсВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение","1");

			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаПлатежа"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаВзаиморасчетов"); $svoistvo->addAttribute("Тип","Число");
			$svoistvo->addChild("Значение",$payment['summ']);
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СтавкаНДС"); $svoistvo->addAttribute("Тип","ПеречислениеСсылка.СтавкиНДС");
			//$tax_data=$db->getOne("select tax_rate from tax_type where id=?i and is_nds=1",$_my_company['tax_type']);
			switch((float)self::$_my_company['tax_rate']) {
				case 20: $svoistvo->addChild("Значение","НДС20"); break;
				case 20.12: $svoistvo->addChild("Значение","НДС20_120"); break;
				case 10: $svoistvo->addChild("Значение","НДС10"); break;
				case 10.11: $svoistvo->addChild("Значение","НДС10_110"); break;
				default: $svoistvo->addChild("Значение","БезНДС");
			}
			$svoistvo=$zapis->addChild("Свойство"); $svoistvo->addAttribute("Имя","СуммаНДС"); $svoistvo->addAttribute("Тип","Число");
			if((float)self::$_my_company['tax_rate']>0) 
				$svoistvo->addChild("Значение",round(($payment['summ'])/(100+self::$_my_company['tax_rate'])*self::$_my_company['tax_rate'],2));
			else 
				$svoistvo->addChild("Значение","0.00");
		return $npp;
	}

	public static function get_detail_documents($request){
		if(empty($request->detail_id)) return array("status"=>"err","err"=>"Не указана деталь");
		if(empty($request->sklad_id) || (int)$request->sklad_id==0) return array("status"=>"err","err"=>"Не указан склад");
		$db = DB::getInstance();
		$parsed="";
		if(!empty($request->date_from)) {
			$parsed.=$db->parse(" and create_date>=?s",$request->date_from);
			$date_from=$request->date_from;
		}
		else {
			$parsed.=$db->parse(" and create_date>=?s",date("Y-m-d",strtotime("2 years ago")));
			$date_from=date("Y-m-d",strtotime("2 years ago"));
		}
		if(!empty($request->date_to)) {
			$parsed.=$db->parse(" and create_date<=?s",$request->date_to." 23:59:59");
			$date_to=$request->date_to;
		}
		else {
			$parsed.=$db->parse(" and create_date<=?s",date("Y-m-d")." 23:59:59");
			$date_to=date("Y-m-d");
		}
		if(empty($request->documents)){
			$documents=$db->getInd("id","select * from document where main_company=?i and deleted=0 and sklad_id=?i ?p",$_SESSION['main_company'],$request->sklad_id,$parsed);
		}
		else {
			$documents=(array)$request->documents;
		}
		$document_details=$db->getAll("select * from document_details where detail_id=?i and document_id in (?b) and deleted=0",$request->detail_id,array_keys($documents));
		$ret_documents=array();
		foreach($document_details as $doc_key=>$doc_val){
			$ret_documents[$doc_val['document_id']]=$documents[$doc_val['document_id']];
		}
		$logistic_orders=$db->getInd("id","select * from logistic_orders where main_company_id=?i and (from_sklad_id=?i or to_sklad_id=?i) ?p",$_SESSION['main_company'],$request->sklad_id,$request->sklad_id,$parsed);
		$logistic_order_details=$db->getAll("select * from logistic_order_details where detail_id=?i and logistic_order_id in (?b)",$request->detail_id,array_keys($logistic_orders));
		foreach($logistic_order_details as $doc_key=>$doc_val){
			$ret_logistic_orders[$doc_val['logistic_order_id']]=$logistic_orders[$doc_val['logistic_order_id']];
		}
		$company_ids=array();
		if(is_array(array_column($ret_documents,"company_id"))){
			if(is_array(array_column((array)$ret_logistic_orders,"from_company_id"))){
				$company_ids=array_merge(array_column((array)$ret_documents,"company_id"),array_column((array)$ret_logistic_orders,"from_company_id"));
			}
			else {
				$company_ids=array_column((array)$ret_documents,"company_id");
			}
		}
		return array(
			"status"=>"ok",
			"msg"=>"",
			"err"=>"",
			"date_from"=>$date_from,
			"date_to"=>$date_to,
			"documents"=>$ret_documents,
			"logistic_orders"=>$ret_logistic_orders,
			"logistic_order_details"=>($logistic_order_details?$logistic_order_details:[]),
			"document_details"=>($document_details?$document_details:[]),
			"contragents"=>$db->getInd("id","select id,name from company where id in (?b)",$company_ids)
		);

	}

	public static function get_documents_csv($request){
		$db = DB::getInstance();
		if(isset($request->znak) && ($request->znak=="+" || $request->znak=="-" || $request->znak=="rtd" || $request->znak=="rfc")){
			$sql="select d.id,d.type_id,d.number,d.document_date,d.chf_number,d.chf_date,d.company_id,d.comment,d.scan_file,d.sklad_id,d.obrabotan,c.name,s.name as sklad_name,d.create_date,d.update_date,d.zakaz_id
		    from document d
			left join company c on (d.company_id=c.id)
		    left join sklad s on (s.id=d.sklad_id)
			where d.main_company=?i and d.deleted=0 ";
		
			if(!empty($request->search_document_date_from)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime("10 days ago"));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime("1 month ago"));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			if(!empty($request->search_document_date_to)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d");
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d");
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			if(!empty($request->search_document_client_name)){
				$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where main_company_id=?i and (btype=1 or btype=0 or btype=2 or btype=3)) and name like ?s";
				$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],'%'.trim($request->search_document_client_name).'%');
				if($res_cl){
					$search_companys=array_column($res_cl,"id");
					$sql.=" and d.company_id in (".implode(",",$search_companys).")";
				}
				$ret['search_document_client_name']=$request->search_document_client_name;
			}
			switch($request->znak){
				case "+":
				case "-": // || $request->znak=="-")) {
					//$sql_znak="select id from document_types where znak=?s";
					//$document_types=$db->getCol($sql_znak,$request->znak);
					$sql.=" and d.type_id in (?b)";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					if ($request->znak=="+") $res=$db->getAll($sql,$_SESSION['company_id'],array(1,5));//$document_types);
					if ($request->znak=="-") $res=$db->getAll($sql,$_SESSION['company_id'],array(2,3));//$document_types);
					//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
					break;
				case "rtd": // return to dealer 
					//$sql_znak="select id from document_types where znak=?s";
					//$document_types=$db->getCol($sql_znak,$request->znak);
					$sql.=" and d.type_id in (?b)";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['company_id'],array(7));//$document_types);
					//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
					break;
				case "rfc": // return from client 
					//$sql_znak="select id from document_types where znak=?s";
					//$document_types=$db->getCol($sql_znak,$request->znak);
					$sql.=" and d.type_id in (?b)";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['company_id'],array(6));//$document_types);
					//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
					break;
				case "exp":
					$sql.=" and type_id in (?b)";
					$parsed="";
					if(isset($request->search_document_orgtype) && (int)$request->search_document_orgtype>0){
						if((int)$request->search_document_orgtype<3)
							$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type<3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
						else {
							$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type=3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
						}
					}
					$sql.=" ?p ";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['company_id'],$request->type_ids,$parsed);
					break;
				default:
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['main_company']);
			
			}

		if(!empty($request->search_document_article)){
			$res_art=$db->getCol("select distinct(document_id) from document_details where document_id in (?b) and deleted=0 and replace(replace(replace(replace(article,'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_document_article))."%");
			foreach($res as $doc_key => $search_doc){
				if(in_array($search_doc["id"],$res_art)){
					$ret_res[]=$res[$doc_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_document_article']=$request->search_document_article;
		}
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['documents']=$ret_res;
        	$ret['document_types']=$db->getInd("id","select id,name,descr from document_types");
			$ret['document_det_pos']=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			$ret['document_job_pos']=$db->getInd("document_id","SELECT document_id,COUNT(job_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_jobs where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			//$ret['']
			$ret['msg']="";
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['documents']=array();
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
		$documents=array();
		foreach($ret['documents'] as $doc_key=>$doc_val){
			array_push($documents,array(
				"N"=>$doc_val['id'],
				"номер документа"=>$doc_val['number'],
				"дата документа"=>$doc_val['document_date'],
				"тип документа"=>$ret['document_types'][$doc_val['type_id']]['name'],
				"компания"=>$doc_val['name'],
				"склад"=>$doc_val['sklad_name'],
				"описание"=>$doc_val['comment'],
				"дата создания"=>$doc_val['create_date'],
				"позиций"=>$ret['document_det_pos'][$doc_val['id']]['positions'],
				"кол-во деталей"=>$ret['document_det_pos'][$doc_val['id']]['pos_count'],
				"сумма"=>$ret['document_det_pos'][$doc_val['id']]['pos_sum']
			));
		}
			$csv = implode(",", array_keys(reset($documents))) . PHP_EOL;
			foreach ($documents as $row) {
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
			$file=base64_encode(mb_convert_encoding($csv,"WINDOWS-1251","UTF-8"));
			unlink("/tmp/export_documents.csv");
			
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}
		else {
			return self::_error_arr("Не укзан тип документов для экпорта");
		}
	}

	public static function get_documents_xls($request){
		$db = DB::getInstance();
		if(isset($request->znak) && ($request->znak=="+" || $request->znak=="-" || $request->znak=="rtd" || $request->znak=="rfc")){
			$sql="select d.id,d.type_id,d.number,d.document_date,d.chf_number,d.chf_date,d.company_id,d.comment,d.scan_file,d.sklad_id,d.obrabotan,c.name,s.name as sklad_name,d.create_date,d.update_date,d.zakaz_id
		    from document d
			left join company c on (d.company_id=c.id)
		    left join sklad s on (s.id=d.sklad_id)
			where d.main_company=?i and d.deleted=0 ";
		
			if(!empty($request->search_document_date_from)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_from=date("Y-m-d",strtotime("10 days ago"));
					$sql.=" and d.document_date>='".$date_from."'";
				}
				else {
					$date_from=date("Y-m-d",strtotime("1 month ago"));
					$sql.=" and d.create_date>='".$date_from."'";
				}
			}
			if(!empty($request->search_document_date_to)) {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			else {
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$date_to=date("Y-m-d");
					$sql.=" and d.document_date<='".$date_to." 23:59:59'";
				}
				else {
					$date_to=date("Y-m-d");
					$sql.=" and d.create_date<='".$date_to." 23:59:59'";
				}
			}
			if(!empty($request->search_document_client_name)){
				$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where main_company_id=?i and (btype=1 or btype=0 or btype=2 or btype=3)) and name like ?s";
				$res_cl=$db->getAll($sql_cl,$_SESSION['main_company'],'%'.trim($request->search_document_client_name).'%');
				if($res_cl){
					$search_companys=array_column($res_cl,"id");
					$sql.=" and d.company_id in (".implode(",",$search_companys).")";
				}
				$ret['search_document_client_name']=$request->search_document_client_name;
			}
			switch($request->znak){
				case "+":
				case "-": // || $request->znak=="-")) {
					//$sql_znak="select id from document_types where znak=?s";
					//$document_types=$db->getCol($sql_znak,$request->znak);
					$sql.=" and d.type_id in (?b)";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					if ($request->znak=="+") $res=$db->getAll($sql,$_SESSION['company_id'],array(1,5));//$document_types);
					if ($request->znak=="-") $res=$db->getAll($sql,$_SESSION['company_id'],array(2,3));//$document_types);
					//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
					break;
				case "rtd": // return to dealer 
					//$sql_znak="select id from document_types where znak=?s";
					//$document_types=$db->getCol($sql_znak,$request->znak);
					$sql.=" and d.type_id in (?b)";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['company_id'],array(7));//$document_types);
					//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
					break;
				case "rfc": // return from client 
					//$sql_znak="select id from document_types where znak=?s";
					//$document_types=$db->getCol($sql_znak,$request->znak);
					$sql.=" and d.type_id in (?b)";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['company_id'],array(6));//$document_types);
					//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
					break;
				case "exp":
					$sql.=" and type_id in (?b)";
					$parsed="";
					if(isset($request->search_document_orgtype) && (int)$request->search_document_orgtype>0){
						if((int)$request->search_document_orgtype<3)
							$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type<3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
						else {
							$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type=3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
						}
					}
					$sql.=" ?p ";
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['company_id'],$request->type_ids,$parsed);
					break;
				default:
					if(isset($request->date_type) && $request->date_type=='document_date'){
						$sql.="order by d.document_date desc";
					}
					else {
						$sql.="order by d.create_date desc";
					}
					$res=$db->getAll($sql,$_SESSION['main_company']);
			
			}

		if(!empty($request->search_document_article)){
			$res_art=$db->getCol("select distinct(document_id) from document_details where document_id in (?b) and deleted=0 and replace(replace(replace(replace(article,'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_document_article))."%");
			foreach($res as $doc_key => $search_doc){
				if(in_array($search_doc["id"],$res_art)){
					$ret_res[]=$res[$doc_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_document_article']=$request->search_document_article;
		}
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['documents']=$ret_res;
        	$ret['document_types']=$db->getInd("id","select id,name,descr from document_types");
			$ret['document_det_pos']=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			$ret['document_job_pos']=$db->getInd("document_id","SELECT document_id,COUNT(job_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_jobs where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			//$ret['']
			$ret['msg']="";
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['documents']=array();
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
		$documents=array();
		foreach($ret['documents'] as $doc_key=>$doc_val){
			array_push($documents,array(
				"N"=>$doc_val['id'],
				"номер документа"=>$doc_val['number'],
				"дата документа"=>$doc_val['document_date'],
				"тип документа"=>$ret['document_types'][$doc_val['type_id']]['name'],
				"компания"=>$doc_val['name'],
				"склад"=>$doc_val['sklad_name'],
				"описание"=>$doc_val['comment'],
				"дата создания"=>$doc_val['create_date'],
				"позиций"=>$ret['document_det_pos'][$doc_val['id']]['positions'],
				"кол-во деталей"=>$ret['document_det_pos'][$doc_val['id']]['pos_count'],
				"сумма"=>$ret['document_det_pos'][$doc_val['id']]['pos_sum']
			));
		}
			$csv = implode(",", array_keys(reset($documents))) . PHP_EOL;
			foreach ($documents as $row) {
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
			file_put_contents("/tmp/export_documents_".$_SESSION['user_id'].".csv",$csv);
			//require 'vendor/autoload.php';

			//use PhpOffice\PhpSpreadsheet\Spreadsheet;
			//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

			$spreadsheet = new Spreadsheet();
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

			/* Set CSV parsing options */

			$reader->setDelimiter(',');
			$reader->setEnclosure('"');
			$reader->setSheetIndex(0);

			/* Load a CSV file and save as a XLS */

			$spreadsheet = $reader->load("/tmp/export_documents_".$_SESSION['user_id'].".csv");
			$writer = new Xlsx($spreadsheet);
			$writer->save("/tmp/export_documents_".$_SESSION['user_id'].".xlsx");

			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/export_documents_".$_SESSION['user_id'].".xlsx"));
			unlink("/tmp/export_documents_".$_SESSION['user_id'].".xlsx");
			unlink("/tmp/export_documents_".$_SESSION['user_id'].".csv");
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}
		else {
			return self::_error_arr("Не укзан склад для экпорта");
		}
	}

	public static function get_document_set_price($request){
		if(empty($_SESSION['document_set_price']) || empty($_SESSION['document_details_round'])){
			$db = DB::getInstance();
			$res=$db->getRow("select document_set_price,document_details_round,document_edit_deny_date,document_detail_edit_deny,document_set_category from company where id=?i",$_SESSION['main_company']);
			$_SESSION['document_set_price']=$res['document_set_price'];
			$_SESSION['document_set_category']=$res['document_set_category'];
		  	$_SESSION['document_details_round']=$res['document_details_round'];
			$_SESSION['document_edit_deny_date']=$res['document_edit_deny_date'];
			$_SESSION['document_detail_edit_deny']=$res['document_detail_edit_deny'];
		}
		return array("status"=>"ok","document_set_price"=>$_SESSION['document_set_price'],"msg"=>"",
			"document_set_category"=>$_SESSION['document_set_category'],
			"document_details_round"=>$_SESSION['document_details_round'],
			"document_edit_deny_date"=>$_SESSION['document_edit_deny_date'],
			"document_detail_edit_deny"=>$_SESSION['document_detail_edit_deny']
		);
	}
  
	public static function save_document_set_price($request){
	  $db = DB::getInstance();
	  if(isset($request->document_set_price) && $request->document_set_price==true) $document_set_price=1;
	  else $document_set_price=0;
	  if(isset($request->document_set_category) && $request->document_set_category==true) $document_set_category=1;
	  else $document_set_category=0;
	  if(isset($request->document_details_round)) $document_details_round=(int)$request->document_details_round;
	  else $document_details_round=10;
	  if(isset($request->document_edit_deny_date)) $document_edit_deny_date=date("Y-m-d",strtotime($request->document_edit_deny_date));
	  else $document_edit_deny_date="1970-01-01";
	  if(isset($request->document_detail_edit_deny) && $request->document_detail_edit_deny==true) $document_detail_edit_deny=1;
	  else $document_detail_edit_deny=0;
	  if($db->query("update company set 
	  	document_set_price=?i,
		document_details_round=?i,
		document_edit_deny_date=?s,
		document_detail_edit_deny=?i,
		document_set_category=?i 
		where id=?i",$document_set_price,$request->document_details_round,$document_edit_deny_date,$document_detail_edit_deny,$document_set_category,$_SESSION['main_company'])) {
		  $_SESSION['document_set_price']=$document_set_price;
		  $_SESSION['document_set_category']=$document_set_category;
		  $_SESSION['document_details_round']=$document_details_round;
		  $_SESSION['document_edit_deny_date']=$document_edit_deny_date;
		  $_SESSION['document_detail_edit_deny']=$document_detail_edit_deny;
	  }
	  return array("status"=>"ok","document_set_price"=>$_SESSION['document_set_price'],"document_set_category"=>$_SESSION['document_set_category'],"document_details_round"=>$_SESSION['document_details_round'],"document_edit_deny_date"=>$_SESSION['document_edit_deny_date'],"document_detail_edit_deny"=>$_SESSION['document_detail_edit_deny'],"msg"=>"");
  	}

	public static function get_upd_xls($request){
		$db=DB::getInstance();

		if(isset($request->zakaz_id)){
		$zakaz_id=$request->zakaz_id;
		$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
		$zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
			left join service_jobs sj on (sj.id=zj.job_id)
			where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
		$zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
		}
		if(isset($request->document_id)){
			$zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$request->document_id);
			$zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$request->document_id);
			$zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
			left join service_jobs sj on (sj.id=dj.job_id)
			where dj.document_id=?i and dj.deleted=0",$request->document_id);
			$zakaz_data=$db->getRow("select * from document where id=?i",$request->document_id);
			$zakaz_data['main_company_id']=$zakaz_data['main_company'];
			//$zakaz_data['id']=$zakaz_id;
			//}
		}
		if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
			return array("status"=>"err","err"=>"Выберите свой заказ");
		}
		//echo "select * from zakaz where id=$zakaz_id<br>";
		//echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
		$client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
		if($client_data['main_org_id']>0) $client_main_org_data=$db->getRow("select * from company where id=?i",$client_data['main_org_id']);
		$mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
		$poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
		$pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id'],$zakaz_data['company_id']);
		$mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
		$sklad_data=$db->getRow("select * from sklad where id=(select delivery_type_id from zakaz where id=?i)",$zakaz_id);
		if(isset($request->zakaz_id) && $mainc_taxtype['is_nds']==1 ){
		die("При работе с НДС, распечатка документов возможна только из вкладки документы");
		}
		$ruk_arr=explode(" ",$mainc_data['ruk']);
		//echo print_r($ruk_arr,true)."<br>";
		$ruk_name=mb_substr($ruk_arr[1],0,1);
		//echo print_r($ruk_name,true)."<br>";
		$ruk_otch=mb_substr($ruk_arr[2],0,1);
		$ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";

		$data = array(
			'buyer' => array(
				'name' => $mainc_data['name'],
				'address' => $mainc_data['address'],
				'inn' => $mainc_data['inn'],
				'kpp' => $mainc_data['kpp'],
				'anotherAdress' => $mainc_data['post_address'],
				'recipient' => $client_data['name'].",".$client_data['address'],
			),
			'seller' => array(
				'name' => $client_data['name'],
				'address' => $client_data['address'],
				'inn' => $client_data['inn'],
				'kpp' => $client_data['kpp']
			),
			'goods' => array(),
		);
		
		foreach($zakaz_details as $zd_key=>$zd_val){
			array_push($data['goods'],
				array(
					'id' => $zd_val['article'],
					'name' => $zd_val['name']."(".$zd_val['brand'].")",
					'count' => $zd_val['count'],
					'price' => $zd_val['price'],
					'country' => array(
						'id' => '',
						'title' => ''
					),
					'regNum' => ''
				)
			);
		}

		$arr = [
			'января',
			'февраля',
			'марта',
			'апреля',
			'мая',
			'июня',
			'июля',
			'августа',
			'сентября',
			'октября',
			'ноября',
			'декабря'
		  ];
		  
		  $month = date('n')-1;
		  $currentDate = date('d').' '.$arr[$month].' '.date('Y').'г.';
		
		  $schet = '070122-1266';
		  if(!empty($zakaz_data['number'])){
			$schet = $zakaz_data['number'];
		  }
		  else {
			if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) $schet = $zakaz_id;
			else $schet = "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
		  }

		  if($zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
			$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
		  }
		  else {
			if(isset($request->document_id) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
			$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
			else
			$currentDate = date("d.m.Y",strtotime($zakaz_data['create_date']));
		  }
		
		$inputFileName = dirname(@$_SERVER['SCRIPT_FILENAME']).'/classes/Components/files/xls_templates/upd_template.xlsx';
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load($inputFileName);
		$sheet = $spreadsheet->getActiveSheet();
		
		$sheet-> setCellValue("U1", $schet);
		$sheet-> setCellValue("AE1", $currentDate);
		//$sheet-> setCellValue("AE1", "№ {$schet}            от {$currentDate}");
		
		$sheet-> setCellValue("Z4", $data['buyer']['name']);
		$sheet-> setCellValue("Z5", $data['buyer']['address']);
		$sheet-> setCellValue("Z6", $data['buyer']['inn'] . ' / ' . $data['buyer']['kpp']);
		$sheet-> setCellValue("Z7", isset($sklad_data['address']) ? $mainc_data['name']." ".$sklad_data['address']:"он же");
		$sheet-> setCellValue("Z8", $client_data['name'].', '.$client_data['address']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("Z10", $client_main_org_data['name']);
        else $sheet-> setCellValue("Z10", $client_data['name']);
		//$sheet-> setCellValue("Z10", $data['seller']['name']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("Z11", $client_main_org_data['address']);
        else $sheet-> setCellValue("Z11", $client_data['address']);
		//$sheet-> setCellValue("Z11", $data['seller']['address']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("Z12", (string)$client_main_org_data['inn']."/".$client_main_org_data['kpp']);
        else $sheet-> setCellValue("Z12", (string) $data['seller']['inn']."/".$data['seller']['kpp']);
		
		$sheet-> setCellValue("AE13", "Договор № 102130-26 от 10.11.2020г.");
		$sheet-> setCellValue("A41", "{$data['buyer']['name']}, ИНН.КПП {$data['buyer']['inn']} / {$data['buyer']['kpp']}");
		$sheet-> setCellValue("U41", "{$data['seller']['name']}, ИНН/КПП {$data['seller']['inn']}");
		$sheet-> setCellValue("A45", "Универсальный передаточный документ № {$schet}");
		
		$goods = $data['goods'];
		if(count((array)$goods)>1)
			$spreadsheet->getActiveSheet()->insertNewRowBefore(20, count((array)$goods) - 1);
		
		$arrTotalPrice = [];
		$arrTax = [];
		$arrTotalTax = [];
		
		for ($i=0; $i < count((array)$goods); $i++) { 
			$x = $i + 19;
			$y = $i + 1;
			$spreadsheet->getActiveSheet()->mergeCells("B{$x}:C{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("D{$x}:I{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("J{$x}:V{$x}");
			
			$spreadsheet->getActiveSheet()->mergeCells("W{$x}:AB{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("AC{$x}:AE{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("AG{$x}:AK{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("AL{$x}:AR{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("AS{$x}:AV{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("AW{$x}:AY{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("AZ{$x}:BD{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("BE{$x}:BH{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("BI{$x}:BN{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("BO{$x}:BQ{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("BS{$x}:BX{$x}");
			$totalPrice = (float) $goods[$i]['count'] * (float) $goods[$i]['price'];
			$tax = ($totalPrice / 100) * 20;
			$totalTax = $totalPrice + $tax;
			$sheet-> setCellValue("B{$x}", $y);
			$sheet-> setCellValue("D{$x}", $goods[$i]['id']);
			//$sheet-> setCellValue("E{$x}", $y);
			$sheet-> setCellValue("J{$x}", $goods[$i]['name']);
			//$sheet-> setCellValue("G{$x}", $goods[$i]['name']['2']);
			//$sheet-> setCellValue("H{$x}", $goods[$i]['name']['3']);
			//$sheet-> setCellValue("J{$x}", $goods[$i]['name']['4']);
			
			$sheet-> setCellValue("W{$x}", '-');
			$sheet-> setCellValue("AC{$x}", '796');
			$sheet-> setCellValue("AF{$x}", 'ШТ');
			$sheet-> setCellValue("AG{$x}", $goods[$i]['count']);
			//$sheet-> setCellValue("AL{$x}", $goods[$i]['price']);
			if((int)$mainc_taxtype['is_nds']==1){
                $sheet-> setCellValue("AL{$x}", number_format(round(($goods[$i]['price'])/(1+$mainc_taxtype['tax_rate']/100),2),2,","," "));
              }
            else
				$sheet-> setCellValue("AL{$x}", number_format($goods[$i]['price'],2,","," "));
			if((int)$mainc_taxtype['is_nds']==1)
				$sheet-> setCellValue("AS{$x}", number_format(round(($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,","," "));
            else
				$sheet-> setCellValue("AS{$x}", number_format(round($goods[$i]['price']*$goods[$i]['count'],2),2,","," "));
			//$sheet-> setCellValue("T{$x}", (string) round($totalPrice, 2));
			$sheet-> setCellValue("AW{$x}", 'Без акциза');
			if((int)$mainc_taxtype['is_nds']==1)
				$sheet-> setCellValue("AZ{$x}",$mainc_taxtype['tax_rate']."%");
            else
				$sheet-> setCellValue("AZ{$x}","Без НДС");
			//$sheet-> setCellValue("AZ{$x}", '20%');
			if((int)$mainc_taxtype['is_nds']==1)
				$sheet-> setCellValue("BE{$x}",number_format(round(($goods[$i]['price']*$goods[$i]['count'])-($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,","," "));
			//$sheet-> setCellValue("BE{$x}", (string) round($tax, 2));
			$sheet-> setCellValue("BI{$x}", number_format($goods[$i]['price']*$goods[$i]['count'],2,","," "));
			$sheet-> setCellValue("BO{$x}", $goods[$i]['country']['id']);
			$sheet-> setCellValue("BR{$x}", $goods[$i]['country']['title']);
			$sheet-> setCellValue("BS{$x}", $goods[$i]['regNum']);
			$zakaz_sum+=$goods[$i]['price']*$goods[$i]['count'];
			$zakaz_count_sum+=$goods[$i]['count'];
			$sum_without_nds+=round(($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
			$sum_nds+=round(($goods[$i]['price']*$goods[$i]['count'])-($goods[$i]['price']*$goods[$i]['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
			//array_push($arrTotalPrice, round($totalPrice, 2));
			//array_push($arrTax, round($tax, 2));
			//array_push($arrTotalTax, round($totalTax, 2));    
		}
		
		$indexCell = count((array)$goods) + 19;
		if((int)$mainc_taxtype['is_nds']==1){ $sheet-> setCellValue("AS{$indexCell}", $sum_without_nds);} else $sheet->setCellValue("AS{$indexCell}",number_format($zakaz_sum,2,","," "));
		if((int)$mainc_taxtype['is_nds']==1){$sheet-> setCellValue("BE{$indexCell}", number_format($sum_nds,2,","," "));} else $sheet->setCellValue("BE{$indexCell}", "X");
		$sheet->setCellValue("BI{$indexCell}", number_format($zakaz_sum,2,","," "));
		$indexCell+=2;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==2) $sheet->setCellValue("AJ{$indexCell}",$ruk);
		if(!empty($mainc_data['buh'])) $sheet->setCellValue("BQ{$indexCell}",$mainc_data['buh']);
		$indexCell+=2;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) $sheet->setCellValue("AJ{$indexCell}",$ruk);
		$ipreg_data="";
		if(!empty($mainc_data['ipreg_num']) && $mainc_data['type']==1) $ipreg_data=$mainc_data['ipreg_num'];
    	if(!empty($mainc_data['ipreg_date']) && $mainc_data['type']==1) $ipreg_data.=" от ".$mainc_data['ipreg_date'];
		$sheet->setCellValue("AX{$indexCell}",$ipreg_data);
		$writer = new Xlsx($spreadsheet);
		$writer->save("/tmp/upd_".$_SESSION['user_id'].".xlsx");
			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/upd_".$_SESSION['user_id'].".xlsx"));
			unlink("/tmp/upd_".$_SESSION['user_id'].".xlsx");
			return array("status"=>"ok","msg"=>"","file"=>$file);//,"zakaz_details"=>$zakaz_details,"data"=>$data);
		//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		//header('Content-Disposition: attachment;filename="peredatochny_doc.xls"');
		//$writer->save('php://output');
		
	}

	public static function get_ukd_xls($request){
		$db=DB::getInstance();

		if(isset($request->zakaz_id)){
		$zakaz_id=$request->zakaz_id;
		$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
		$zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
			left join service_jobs sj on (sj.id=zj.job_id)
			where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
		$zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
		}
		if(isset($request->document_id)){
			$zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$request->document_id);
			$zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$request->document_id);
			$zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
			left join service_jobs sj on (sj.id=dj.job_id)
			where dj.document_id=?i and dj.deleted=0",$request->document_id);
			$zakaz_data=$db->getRow("select * from document where id=?i",$request->document_id);
			$zakaz_data['main_company_id']=$zakaz_data['main_company'];
			//$zakaz_data['id']=$zakaz_id;
			//}
		}
		if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
			return array("status"=>"err","err"=>"Выберите свой заказ");
		}
		//echo "select * from zakaz where id=$zakaz_id<br>";
		//echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
		$client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
		if($client_data['main_org_id']>0) $client_main_org_data=$db->getRow("select * from company where id=?i",$client_data['main_org_id']);
		$mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
		$poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
		$pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id'],$zakaz_data['company_id']);
		$mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
		$sklad_data=$db->getRow("select * from sklad where id=(select delivery_type_id from zakaz where id=?i)",$zakaz_id);
		if(isset($request->zakaz_id) && $mainc_taxtype['is_nds']==1 ){
		die("При работе с НДС, распечатка документов возможна только из вкладки документы");
		}
		$ruk_arr=explode(" ",$mainc_data['ruk']);
		//echo print_r($ruk_arr,true)."<br>";
		$ruk_name=mb_substr($ruk_arr[1],0,1);
		//echo print_r($ruk_name,true)."<br>";
		$ruk_otch=mb_substr($ruk_arr[2],0,1);
		$ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";

		$data = array(
			'buyer' => array(
				'name' => $mainc_data['name'],
				'address' => $mainc_data['address'],
				'inn' => $mainc_data['inn'],
				'kpp' => $mainc_data['kpp'],
				'anotherAdress' => $mainc_data['post_address'],
				'recipient' => $client_data['name'].",".$client_data['address'],
			),
			'seller' => array(
				'name' => $client_data['name'],
				'address' => $client_data['address'],
				'inn' => $client_data['inn'],
				'kpp' => $client_data['kpp']
			),
			'goods' => array(),
		);
		
		foreach($zakaz_details as $zd_key=>$zd_val){
			array_push($data['goods'],
				array(
					'id' => $zd_val['article'],
					'name' => $zd_val['name']."(".$zd_val['brand'].")",
					'count' => $zd_val['count'],
					'price' => $zd_val['price'],
					'country' => array(
						'id' => '',
						'title' => ''
					),
					'regNum' => ''
				)
			);
		}

		$arr = [
			'января',
			'февраля',
			'марта',
			'апреля',
			'мая',
			'июня',
			'июля',
			'августа',
			'сентября',
			'октября',
			'ноября',
			'декабря'
		  ];
		  
		  $month = date('n')-1;
		  $currentDate = date('d').' '.$arr[$month].' '.date('Y').'г.';
		
		  $schet = '070122-1266';
		  if(!empty($zakaz_data['number'])){
			$schet = $zakaz_data['number'];
		  }
		  else {
			if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) $schet = $zakaz_id;
			else $schet = "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
		  }

		  if($zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
			$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
		  }
		  else {
			if(isset($request->document_id) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
			$currentDate = date("d.m.Y",strtotime($zakaz_data['document_date']));
			else
			$currentDate = date("d.m.Y",strtotime($zakaz_data['create_date']));
		  }
		
		$inputFileName = dirname(@$_SERVER['SCRIPT_FILENAME']).'/classes/Components/files/xls_templates/ukd_template.xlsx';
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load($inputFileName);
		$sheet = $spreadsheet->getActiveSheet();
		if(!empty($zakaz_data['number'])){
			$sheet-> setCellValue("T2",$zakaz_data['number']);
		  }
		  else {
			if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) $sheet-> setCellValue("T2",$zakaz_id);
			else $sheet-> setCellValue("T2","0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT));
		  } 
		  if(isset($zakaz_data['document_date']) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!=""){
			$sheet-> setCellValue("Y2",date("d.m.Y",strtotime($zakaz_data['document_date'])));
		  }
		  else {
			if(isset($request->document_id) && $zakaz_data['document_date']!="0000-00-00" && $zakaz_data['document_date']!="" ) 
			$sheet-> setCellValue("Y2",date("d.m.Y",strtotime($zakaz_data['document_date'])));
			else
			$sheet-> setCellValue("Y2",date("d.m.Y",strtotime($zakaz_data['create_date'])));
		  }
		$source_document=$db->getRow("SELECT * FROM document WHERE zakaz_id=?i AND type_id=2 and deleted=0",$zakaz_data['zakaz_id']);
		$sheet-> setCellValue("S3","0000-".str_pad($source_document['id'],6,"0",STR_PAD_LEFT));
		$sheet-> setCellValue("Z3",preg_replace("/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/","$3.$2.$1",($source_document['document_date']!="0000-00-00"?$source_document['document_date']:$source_document['create_date'])));
		$sheet-> setCellValue("P5", $mainc_data['name']);
		$sheet-> setCellValue("P6", $mainc_data['address']);
		$sheet-> setCellValue("P7", $mainc_data['inn']." / ".$mainc_data['kpp']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("P8", $client_main_org_data['name']);
        else $sheet-> setCellValue("P8", $client_data['name']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("P9", $client_main_org_data['address']);
        else $sheet-> setCellValue("P9", $client_data['address']);
		if(isset($client_main_org_data)  && $client_data['buyer_in_upd']=="0") $sheet-> setCellValue("P10", $client_main_org_data['inn']." / ".$client_main_org_data['kpp']);
        else $sheet-> setCellValue("P10", $client_data['inn']." / ".$client_data['kpp']);

		$sheet-> setCellValue("Z7", isset($sklad_data['address']) ? $mainc_data['name']." ".$sklad_data['address']:"он же");
		$sheet-> setCellValue("Z8", $client_data['name'].', '.$client_data['address']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("Z10", $client_main_org_data['name']);
        else $sheet-> setCellValue("Z10", $client_data['name']);
		//$sheet-> setCellValue("Z10", $data['seller']['name']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("Z11", $client_main_org_data['address']);
        else $sheet-> setCellValue("Z11", $client_data['address']);
		//$sheet-> setCellValue("Z11", $data['seller']['address']);
		if(isset($client_main_org_data)) $sheet-> setCellValue("Z12", (string)$client_main_org_data['inn']."/".$client_main_org_data['kpp']);
        else $sheet-> setCellValue("Z12", (string) $data['seller']['inn']."/".$data['seller']['kpp']);
				
		$goods = $data['goods'];
		if(count((array)$goods)>1)
			$spreadsheet->getActiveSheet()->insertNewRowBefore(22, (count((array)$goods) - 1)*4);
		
		$arrTotalPrice = [];
		$arrTax = [];
		$arrTotalTax = [];
		$i=0;
		$sum_nonds_v=0;
		$nds_sum_v=0;
		$sum_nds_v=0;
		$sum_nonds_g=0;
		$nds_sum_g=0;
		$sum_nds_g=0;
		foreach($zakaz_details as $zd_key=>$zd_val) { 
			$x = $i*4 + 18;
			$y = $i + 1;
			if($i>0){
				$spreadsheet->getActiveSheet()->mergeCells("B{$x}:B".($x+3)."");
				$spreadsheet->getActiveSheet()->mergeCells("C{$x}:H".($x+3)."");
				$spreadsheet->getActiveSheet()->mergeCells("I{$x}:K".($x+3)."");
				$spreadsheet->getActiveSheet()->mergeCells("L{$x}:R".($x+3)."");

				for($z=0; $z<4; $z++){
					$spreadsheet->getActiveSheet()->mergeCells("S".($x+$z).":AA".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AB".($x+$z).":AC".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AD".($x+$z).":AF".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AH".($x+$z).":AJ".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AK".($x+$z).":AO".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AR".($x+$z).":AU".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AV".($x+$z).":AY".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("AZ".($x+$z).":BC".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("BD".($x+$z).":BE".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("BF".($x+$z).":BM".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("BN".($x+$z).":BP".($x+$z)."");
					$spreadsheet->getActiveSheet()->mergeCells("BS".($x+$z).":BV".($x+$z)."");
				}
				$sheet-> setCellValue("S{$x}", "А (до изменения)");
				$sheet-> setCellValue("S".($x+1)."", "Б (после изменения)");
				$sheet-> setCellValue("S".($x+2)."", "В (увеличение)");
				$sheet-> setCellValue("S".($x+3)."", "Г (уменьшение)");
				$sheet-> setCellValue("AB{$x}", "--");
				$sheet-> setCellValue("AB".($x+1)."", "--");
				$sheet-> setCellValue("AB".($x+2)."", "--");
				$sheet-> setCellValue("AB".($x+3)."", "--");
				$sheet-> setCellValue("AD{$x}", "796");
				$sheet-> setCellValue("AD".($x+1)."", "796");
				$sheet-> setCellValue("AD".($x+2)."", "X");
				$sheet-> setCellValue("AD".($x+3)."", "X");
				$sheet-> setCellValue("AH{$x}", "шт");
				$sheet-> setCellValue("AH".($x+1)."", "шт");
				$sheet-> setCellValue("AH".($x+2)."", "X");
				$sheet-> setCellValue("AH".($x+3)."", "X");
				$sheet-> setCellValue("AZ{$x}", "без акциза");
				$sheet-> setCellValue("AZ".($x+1)."", "без акциза");
				$sheet-> setCellValue("AZ".($x+2)."", "X");
				$sheet-> setCellValue("AZ".($x+3)."", "X");
				if((int)$mainc_taxtype['is_nds']==1){
					$sheet-> setCellValue("BD{$x}", $mainc_taxtype['tax_rate']."%");
					$sheet-> setCellValue("BD".($x+1)."", $mainc_taxtype['tax_rate']."%");
				}
				else {
					$sheet-> setCellValue("BD{$x}", "Без НДС");
					$sheet-> setCellValue("BD".($x+1)."", "Без НДС");
				}
				$sheet-> setCellValue("BD".($x+2)."", "X");
				$sheet-> setCellValue("BD".($x+3)."", "X");
				$sheet-> setCellValue("BQ{$x}", "--");
				$sheet-> setCellValue("BQ".($x+1)."", "--");
				$sheet-> setCellValue("BQ".($x+2)."", "X");
				$sheet-> setCellValue("BQ".($x+3)."", "X");
				$sheet-> setCellValue("BR{$x}", "--");
				$sheet-> setCellValue("BR".($x+1)."", "--");
				$sheet-> setCellValue("BR".($x+2)."", "X");
				$sheet-> setCellValue("BR".($x+3)."", "X");
				$sheet-> setCellValue("BS{$x}", "--");
				$sheet-> setCellValue("BS".($x+1)."", "--");
				$sheet-> setCellValue("BS".($x+2)."", "X");
				$sheet-> setCellValue("BS".($x+3)."", "X");

			}
			if((int)$zd_val['document_detail_id']>0){
                $dd_data=$db->getRow("SELECT * FROM document_details WHERE document_id=?i",$zd_val['document_detail_id']);
            }
            elseif((int)$zd_val['zakaz_detail_id']>0){
                $dd_data=$db->getRow("SELECT * FROM document_details WHERE document_id IN (SELECT id FROM document WHERE zakaz_id=?i AND type_id=2) AND detail_id=?i",$zakaz_data['zakaz_id'],$zd_val['detail_id']);
            }
            $A_data=array();
            $A_data['count']=$dd_data['count'];
            if((int)$mainc_taxtype['is_nds']==1){
                $A_data['price']=round(($dd_data['price']/(1+$mainc_taxtype['tax_rate']/100)),2);
            }
            else
                $A_data['price']=$dd_data['price'];
            $A_data['sum_nonds']=round($A_data['price']*$A_data['count'],2);
            $A_data['sum_nds']=round($zd_val['price']*$A_data['count'],2);
            $A_data['nds']=round(($dd_data['price']*$dd_data['count'])-($dd_data['price']*$dd_data['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
			
			$sheet-> setCellValue("B{$x}", $i+1);
			$sheet-> setCellValue("C{$x}",$zd_val['article']);
			$sheet-> setCellValue("L{$x}",$zd_val['name'].' ('.$zd_val['brand'].' '.$zd_val['article'].')');
			$sheet-> setCellValue("AK{$x}", $A_data['count']);
			$sheet-> setCellValue("AP{$x}", number_format($A_data['price'],2,"."," "));
			$sheet-> setCellValue("AV{$x}", number_format($A_data['sum_nonds'],2,"."," "));
			if((int)$mainc_taxtype['is_nds']==1)
				$sheet-> setCellValue("BF{$x}", number_format($A_data['nds'],2,"."," "));
			$sheet-> setCellValue("BN{$x}", number_format($A_data['sum_nds'],2,"."," "));

			$B_data['count']=($A_data['count']-$zd_val['count']);
			if((int)$mainc_taxtype['is_nds']==1){
			$B_data['price']=round(($zd_val['price']/(1+$mainc_taxtype['tax_rate']/100)),2);
			}
			else
			$B_data['price']=$zd_val['price'];
			$B_data['sum_nonds']=round($B_data['price']*$B_data['count'],2);
			$B_data['sum_nds']=round($zd_val['price']*$B_data['count'],2);
			$B_data['nds']=round(($zd_val['price']*$B_data['count'])-($zd_val['price']*$B_data['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
			$sheet-> setCellValue("AK".($x+1)."", $B_data['count']);
			$sheet-> setCellValue("AP".($x+1)."", number_format($B_data['price'],2,"."," "));
			$sheet-> setCellValue("AV".($x+1)."", number_format($B_data['sum_nonds'],2,"."," "));
			if((int)$mainc_taxtype['is_nds']==1)
				$sheet-> setCellValue("BF".($x+1)."", number_format($B_data['nds'],2,"."," "));
			$sheet-> setCellValue("BN".($x+1)."", number_format($B_data['sum_nds'],2,"."," "));

			if($B_data['sum_nonds']>$A_data['sum_nonds']){
                $sheet-> setCellValue("AV".($x+2)."", number_format($B_data['sum_nonds']-$A_data['sum_nonds'],2,"."," "));
                $sum_nonds_v+=$B_data['sum_nonds']-$A_data['sum_nonds'];
            }
            else
				$sheet-> setCellValue("AV".($x+2)."", number_format(0,2,"."," "));

			if($B_data['nds']>$A_data['nds']){
				$sheet-> setCellValue("BF".($x+2)."", number_format($B_data['nds']-$A_data['nds'],2,"."," "));
				$nds_sum_v+=($B_data['nds']-$A_data['nds']);
			}
			else
				$sheet-> setCellValue("BF".($x+2)."", number_format(0,2,"."," "));
		
			if($B_data['sum_nds']>$A_data['sum_nds']){
				$sheet-> setCellValue("BN".($x+2)."", number_format($B_data['sum_nds']-$A_data['sum_nds'],2,"."," "));
				$sum_nds_v+=($B_data['sum_nds']-$A_data['sum_nds']);
			}
			else
				$sheet-> setCellValue("BN".($x+2)."", number_format(0,2,"."," "));

			if($A_data['sum_nonds']>$B_data['sum_nonds']){
				$sheet-> setCellValue("AV".($x+3)."", number_format($A_data['sum_nonds']-$B_data['sum_nonds'],2,"."," "));
				$sum_nonds_g+=($A_data['sum_nonds']-$B_data['sum_nonds']);
			}
			else
				$sheet-> setCellValue("AV".($x+3)."", number_format(0,2,"."," "));
			if($A_data['nds']>$B_data['nds']){
				$sheet-> setCellValue("BF".($x+3)."", number_format($A_data['nds']-$B_data['nds'],2,"."," "));
				$nds_sum_g+=$A_data['nds']-$B_data['nds'];
				}
			else
				$sheet-> setCellValue("BF".($x+3)."", number_format(0,2,"."," "));
			if($A_data['sum_nds']>$B_data['sum_nds']){
				$sheet-> setCellValue("BN".($x+3)."", number_format($A_data['sum_nds']-$B_data['sum_nds'],2,"."," "));
				$sum_nds_g+=($A_data['sum_nds']-$B_data['sum_nds']);
			}
			else
				$sheet-> setCellValue("BN".($x+3)."", number_format(0,2,"."," "));

			$i++;
		}
		
		$indexCell = (count((array)$goods)-1)*4 + 22;
		$sheet-> setCellValue("AV{$indexCell}", number_format($sum_nonds_v,2,"."," "));
		$sheet-> setCellValue("BF{$indexCell}", $nds_sum_v);
		$sheet-> setCellValue("BN{$indexCell}", number_format($sum_nds_v,2,"."," "));
		$indexCell++;
		$sheet-> setCellValue("AV{$indexCell}", number_format($sum_nonds_g,2,"."," "));
		$sheet-> setCellValue("BF{$indexCell}", $nds_sum_g);
		$sheet-> setCellValue("BN{$indexCell}", number_format($sum_nds_g,2,"."," "));
		$indexCell+=2;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==2) $sheet-> setCellValue("AC{$indexCell}", $ruk);
		if($mainc_data['type']==2){ 
			if(!empty($mainc_data['buh'])) $sheet-> setCellValue("BL{$indexCell}", $mainc_data['buh']); 
			else $sheet-> setCellValue("BL{$indexCell}", $ruk); 
		}
		$indexCell+=2;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) $sheet-> setCellValue("AC{$indexCell}", $ruk);
		if(!empty($mainc_data['ipreg_num']) && $mainc_data['type']==1) 
			$sheet-> setCellValue("AU{$indexCell}", "Свидетельство ".$mainc_data['ipreg_num'].
			((!empty($mainc_data['ipreg_date']) && $mainc_data['type']==1)?" от ".preg_replace("/(\d+)-(\d+)-(\d+)/","$3.$2.$1",$mainc_data['ipreg_date']):""));
		$indexCell+=3;
		$sheet-> setCellValue("Q{$indexCell}", "Универсальный передаточный документ №".$source_document['id']." от ".preg_replace("/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/","$3.$2.$1",($source_document['document_date']!="0000-00-00"?$source_document['document_date']:$source_document['create_date'])));
		$indexCell+=8;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) $sheet-> setCellValue("V{$indexCell}", $ruk);
		$indexCell+=4;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) $sheet-> setCellValue("V{$indexCell}", $ruk);
		$indexCell+=4;
		if(!empty($mainc_data['ruk']) && $mainc_data['type']==1) $sheet-> setCellValue("V{$indexCell}", $ruk);
		$indexCell+=3;
		$sheet-> setCellValue("B{$indexCell}", $mainc_data['name'].", ИНН ".$mainc_data['inn']);
		$sheet-> setCellValue("AQ{$indexCell}", $client_data['name'].", ИНН ".$client_data['inn']." КПП ".$client_data['kpp']);
		//$sheet->setCellValue("AX{$indexCell}",$ipreg_data);
		$writer = new Xlsx($spreadsheet);
		$writer->save("/tmp/upd_".$_SESSION['user_id'].".xlsx");
			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/upd_".$_SESSION['user_id'].".xlsx"));
			unlink("/tmp/upd_".$_SESSION['user_id'].".xlsx");
			return array("status"=>"ok","msg"=>"","file"=>$file);//,"zakaz_details"=>$zakaz_details,"data"=>$data);
		//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		//header('Content-Disposition: attachment;filename="peredatochny_doc.xls"');
		//$writer->save('php://output');
		
	}


	public static function get_akt_sverki_xls($request){
		$db=DB::getInstance();

		if(!empty($request->date_from)) $date_from=$request->date_from;
		else $date_from=date("Y-m-d",strtotime("600 days ago"));
		if(!empty($request->date_to)) $date_to=$request->date_to;
		else $date_to=date("Y-m-d");
		if(isset($request->company_id) && (int)$request->company_id>0) $company_id=$request->company_id;
		else die("Не указан клиент");
		$is_your_client=$db->getOne("select company_id from user_companys where main_company_id=?i and company_id=?i",$_SESSION['main_company'],$request->company_id);
		if((int)$is_your_client==0){
			die("Не ваш клиент");
		}
		$start_saldo=0;
		$start_documents=$db->getAll("SELECT * FROM document WHERE deleted=0 
		and document_date<?s and main_company=?i and company_id=?i",$date_from,$_SESSION['main_company'],$request->company_id);
		$start_payments=$db->getAll("SELECT * FROM payment WHERE deleted=0 
		and create_date<?s and main_company_id=?i and company_id=?i",$date_from,$_SESSION['main_company'],$request->company_id);
		/*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
		left join document d on (d.id=dd.document_id)
		left join payment p on (p.zakaz_id=d.zakaz_id)
		WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
		/*$sql="SELECT document_id,sum(price*count) as document_summ,sum(dealer_price*count) as document_dealer_summ FROM document_details 
		WHERE document_id IN (?a) AND detail_id<>0 and deleted=0 group by document_id";
		$start_document_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));*/
		$sql="SELECT document_id,sum(price*count) as document_details_summ,sum(dealer_price*count) as document_details_dealer_summ FROM document_details 
		WHERE document_id IN (?b) AND detail_id<>0 and deleted=0 group by document_id";
		$start_document_detail_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));
		$sql="SELECT document_id,sum(price*count) as document_jobs_summ, 0 as document_jobs_dealer_summ FROM document_jobs 
		WHERE document_id IN (?b) and deleted=0 group by document_id";
		$start_document_job_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));
		$start_document_sums=array();
		foreach($start_document_detail_sums as $ddskey=>$ddsval){
				$start_document_sums[$ddskey]['document_summ']=$ddsval['document_details_summ'];
				$start_document_sums[$ddskey]['document_dealer_summ']=$ddsval['document_details_dealer_summ'];
				$start_document_sums[$ddskey]['document_id']=$ddsval['document_id'];
		}
		foreach($start_document_job_sums as $ddskey=>$ddsval){
			$start_document_sums[$ddskey]['document_summ']+=$ddsval['document_jobs_summ'];
			$start_document_sums[$ddskey]['document_dealer_summ']+=$ddsval['document_jobs_dealer_summ'];
			if(empty($start_document_sums[$ddskey]['document_id'])) 
					$start_document_sums[$ddskey]['document_id']=$ddsval['document_id'];
		}
		foreach($start_documents as $start_doc_key=>$start_doc_val){
			switch((int)$start_doc_val['type_id']){
				case 1: 
						$start_saldo+=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
						//$start_saldo+=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
						break;
				case 2: 
						$start_saldo-=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
						//$start_saldo-=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
						break;
				case 6: 
						$start_saldo+=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
						//$start_saldo+=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
						break;
				case 7: 
						$start_saldo-=(float)$start_document_sums[$start_doc_val['id']]['document_dealer_summ'];
						//$start_saldo-=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_dealer_summ'];
						break;
			}
		}
		foreach($start_payments as $sp_key=>$sp_val){
			switch((int)$sp_val['payment_direction']){
					case 1: //оплата клиента
							$start_saldo+=(float)$sp_val['summ'];
							break;
					case 2: //оплата поставщику
							$start_saldo-=(float)$sp_val['summ'];
							break;
					case 3: //Возврат оплаты
					case 4:
					case 5:
							$start_saldo-=(float)$sp_val['summ'];
							break;
			}
		}


		$documents=$db->getAll("SELECT * FROM document WHERE deleted=0 AND document_date>=?s 
		and document_date<=?s and main_company=?i and company_id=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$request->company_id);
		$payments=$db->getAll("SELECT * FROM payment WHERE deleted=0 AND create_date>=?s 
		and create_date<=?s and main_company_id=?i and company_id=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$request->company_id);
		/*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
		left join document d on (d.id=dd.document_id)
		left join payment p on (p.zakaz_id=d.zakaz_id)
		WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
		$sql="SELECT document_id,sum(price*count) as document_details_summ,sum(dealer_price*count) as document_details_dealer_summ FROM document_details 
		WHERE document_id IN (?b) AND detail_id<>0 and deleted=0 group by document_id";
		$document_detail_sums=$db->getInd("document_id",$sql,array_column($documents,"id"));
		$sql="SELECT document_id,sum(price*count) as document_jobs_summ, 0 as document_jobs_dealer_summ FROM document_jobs 
		WHERE document_id IN (?b) and deleted=0 group by document_id";
		$document_job_sums=$db->getInd("document_id",$sql,array_column($documents,"id"));
		$document_sums=array();
		foreach($document_detail_sums as $ddskey=>$ddsval){
				$document_sums[$ddskey]['document_summ']=$ddsval['document_details_summ'];
				$document_sums[$ddskey]['document_dealer_summ']=$ddsval['document_details_dealer_summ'];
				$document_sums[$ddskey]['document_id']=$ddsval['document_id'];
		}
		foreach($document_job_sums as $ddskey=>$ddsval){
				$document_sums[$ddskey]['document_summ']+=$ddsval['document_jobs_summ'];
				$document_sums[$ddskey]['document_dealer_summ']=$ddsval['document_jobs_dealer_summ'];
				if(empty($document_sums[$ddskey]['document_id'])) 
						$document_sums[$ddskey]['document_id']=$ddsval['document_id'];
		}
		//$zakazes=array_column($saled_goods,'zakaz_id');

		foreach($documents as $doc_key=>$doc_val){
				$ret['items'][]=array("type"=>"1","date"=>strtotime($doc_val['document_date']),"data"=>$doc_val);
		}
		foreach($payments as $pay_key=>$pay_val){
				$ret['items'][]=array("type"=>"2","date"=>strtotime($pay_val['create_date']),"data"=>$pay_val);
		}
		if(!isset($ret['items'])) $ret['items']=array();
		$dates=array_column($ret['items'],"date");
		array_multisort($dates,$ret['items']);
		//usort($ret['items'],"date");
		$ret['status']="ok";
		$ret['msg']="";
		$ret['document_sums']=$document_sums;
		//echo "select * from zakaz where id=$zakaz_id<br>";
		//echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
		$client_data=$db->getRow("select * from company where id=?i",$request->company_id);
		$mainc_data=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
		$poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$_SESSION['main_company']);
		$pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$request->company_id);
		$mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
		$ruk_arr=explode(" ",$mainc_data['ruk']);
		//echo print_r($ruk_arr,true)."<br>";
		$ruk_name=mb_substr($ruk_arr[1],0,1);
		//echo print_r($ruk_name,true)."<br>";
		$ruk_otch=mb_substr($ruk_arr[2],0,1);
		$ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";
		$dogovor_data=$db->getRow("select * from dogovor where id=(select dogovor_id from zakaz where id=?i)",$zakaz_id);
		
		$inputFileName = dirname(@$_SERVER['SCRIPT_FILENAME']).'/classes/Components/files/xls_templates/akt_sverki_template.xlsx';
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		$spreadsheet = $reader->load($inputFileName);
		$sheet = $spreadsheet->getActiveSheet();
		$shapka="взаимных расчетов за период: ".date("d.m.Y",strtotime($date_from))." - ".date("d.m.Y",strtotime($date_to))." \r\n между ".$mainc_data['name']." (ИНН ".$mainc_data['inn'].") \r\n и ".$client_data['name']." (ИНН ".$client_data['inn'].")";
		$sheet-> setCellValue("B3", $shapka);
		$shapka1='Мы, нижеподписавшиеся, '.$mainc_data['name'].', с одной стороны, и '.$client_data['name'].', 
		с другой стороны, составили настоящий акт сверки в том, что состояние взаимных расчетов по данным учета следующее:';
		$sheet-> setCellValue("B5", $shapka1);
		//$sheet-> setCellValue("AE1", "№ {$schet}            от {$currentDate}");
		
		$sheet-> setCellValue("B7", 'По данным '.$mainc_data['name'].' (ИНН '.$mainc_data['inn'].'),RUB)');
		$sheet-> setCellValue("J7", 'По данным '.$client_data['name'].' (ИНН '.$client_data['inn'].'),RUB)');
		$sheet-> setCellValue("E9", $start_saldo<0?(number_format(-$start_saldo,2,"."," ")):"");
		$sheet-> setCellValue("G9", $start_saldo>0?number_format($start_saldo,2,"."," "):"");
		
		$goods = $ret['items'];
		if(count((array)$goods)>1)
			$spreadsheet->getActiveSheet()->insertNewRowBefore(10, count((array)$goods) - 1);
		
		$arrTotalPrice = [];
		$arrTax = [];
		$arrTotalTax = [];
		$oborots=0; $oborots_kred=0; $oborots_deb=0;
		for ($i=0; $i < count((array)$goods); $i++) { 
			$x = $i + 10;
			$y = $i + 1;
			$spreadsheet->getActiveSheet()->mergeCells("C{$x}:D{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("E{$x}:F{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("G{$x}:I{$x}");
			
			$spreadsheet->getActiveSheet()->mergeCells("K{$x}:L{$x}");
			$spreadsheet->getActiveSheet()->mergeCells("M{$x}:N{$x}");

			$debet=0;
			$kredit=0;
			$name="";
			$item=$goods[$i];
			$sheet-> setCellValue("B{$x}", date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))));
			//echo $item['type'];
			if($item['type']==1){
				switch((int)$item['data']['type_id']){
					case 1:
						$name="Поступление".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
						$oborots+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						$oborots_kred+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						$kredit=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						break;
					case 2:
						$name="Продажа".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
						$oborots-=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						$oborots_deb+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						$debet=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						break;
					case 6:
						$name="Возврат товара".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
						$oborots+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						$oborots_kred+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						$kredit=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
						break;
					case 7:
						$name="Возврат товара".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
						$oborots-=(float)$ret['document_sums'][$item['data']['id']]['document_dealer_summ'];
						$oborots_deb+=(float)$ret['document_sums'][$item['data']['id']]['document_dealer_summ'];
						$debet=(float)$ret['document_sums'][$item['data']['id']]['document_dealer_summ'];
						break;
					//case 6:
					//    echo "Возврат ".'('.$item['data']['id'].' от '.$item['data']['create_date'].")";
						//break;
						//.
				}
			}
			if($item['type']==2){
				//(int)$item['data']['payment_direction'];
				switch((int)$item['data']['payment_direction']){
					case 1:
						$name="Оплата".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime($item['data']['create_date'])).")".($item['data']['payment_type']==8?" Оплата бонусами":"");//Оплата клиента
						$oborots+=(float)$item['data']['summ'];
						$oborots_kred+=(float)$item['data']['summ'];
						$kredit=(float)$item['data']['summ'];
						break;
					case 2:
						$name="Оплата".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime($item['data']['create_date'])).")";// оплата поставщику
						$oborots-=(float)$item['data']['summ'];
						$oborots_deb+=(float)$item['data']['summ'];
						$debet=(float)$item['data']['summ'];
						break;
					case 3:
					case 4:
					case 5:
						$name="Возврат оплаты".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime($item['data']['create_date'])).")";
						$oborots-=(float)$item['data']['summ'];
						$oborots_deb+=(float)$item['data']['summ'];
						$debet=(float)$item['data']['summ'];
						break;
				}
			}

			$sheet-> setCellValue("C{$x}", $name);
			$sheet-> setCellValue("E{$x}", ($debet>0?number_format($debet,2,"."," "):""));
			$sheet-> setCellValue("G{$x}", ($kredit>0?number_format($kredit,2,"."," "):""));

  
		}
		
		$indexCell = count((array)$goods) + 11;

		$sheet-> setCellValue("E{$indexCell}",$oborots_deb>0?(number_format($oborots_deb,2,"."," ")):"");
		$sheet-> setCellValue("G{$indexCell}",$oborots_kred>0?number_format($oborots_kred,2,"."," "):"");
		$indexCell += 1;
		$sheet-> setCellValue("E{$indexCell}",round($oborots+$start_saldo,2)<0?number_format(-($oborots+$start_saldo),2,"."," "):"");
		$sheet-> setCellValue("G{$indexCell}",round($oborots+$start_saldo,2)>0?number_format($oborots+$start_saldo,2,"."," "):"");

		$indexCell +=2;

		$sheet-> setCellValue("B{$indexCell}",'По данным '.$mainc_data['name']);
		$sheet-> setCellValue("I{$indexCell}",'По данным '.$client_data['name']);

		$indexCell += 1;
		$b16='на '.date("d.m.Y",strtotime($date_to));
		if(round($oborots+$start_saldo,2)==0) $b16.=" Задолженность отсутствует";
		if(round($oborots+$start_saldo,2)>0) {
			$b16.=" Задолженность в пользу ".$client_data['name']." ".number_format($oborots+$start_saldo,2,"."," ")." RUB";
		}
		if(round($oborots+$start_saldo,2)<0) {
			$b16.=" Задолженность в пользу ".$mainc_data['name']." ".number_format(-($oborots+$start_saldo),2,"."," ")." RUB";
		}
		$sheet-> setCellValue("B{$indexCell}",$b16);
		$indexCell +=2;
		$sheet-> setCellValue("B{$indexCell}",'От '.$mainc_data['name']);
		$sheet-> setCellValue("I{$indexCell}",'От '.$client_data['name']);

		$writer = new Xlsx($spreadsheet);
		$writer->save("/tmp/akt_sverki_".$_SESSION['user_id'].".xlsx");
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);
		$file=base64_encode(file_get_contents("/tmp/akt_sverki_".$_SESSION['user_id'].".xlsx"));
		unlink("/tmp/akt_sverki_".$_SESSION['user_id'].".xlsx");
		return array("status"=>"ok","msg"=>"","file"=>$file);
		
	}

	public static function get_documents_by_pay_date($request) {
	    $db = DB::getInstance();

	    $sql="select d.id,d.type_id,d.number,d.document_date,d.pay_date,d.chf_number,d.chf_date,d.company_id,d.comment,d.scan_file,d.sklad_id,d.obrabotan,d.deleted,
			d.return_confirmed,d.return_confirm_date,c.name,c.inn,c.kpp,c.type,
			s.name as sklad_name,d.create_date,d.update_date,d.zakaz_id,d.user_id,u.name as user_name,u.lastname as user_lastname
		    from document d
			left join company c on (d.company_id=c.id)
		    left join sklad s on (s.id=d.sklad_id)
			left join users u on (u.id=d.user_id)
			where d.main_company=?i";
		if(empty($request->search_document_show_deleted) || $request->search_document_show_deleted=="off") $sql.=" and d.deleted=0 ";
		//else $sql.=" and d.deleted=1 ";
		if(!empty($request->search_document_date_from)) {
			$date_from=date("Y-m-d",strtotime($request->search_document_date_from));
			$sql.=" and (d.pay_date>='".$date_from."' or (d.pay_date='0000-00-00' and d.create_date>='".$date_from."'))";
		}
		else {
			$date_from=date("Y-m-d",strtotime("1 month ago"));
			$sql.=" and (d.pay_date>='".$date_from."' or (d.pay_date='0000-00-00' and d.create_date>='".$date_from."'))";
		}
		if(!empty($request->search_document_date_to)) {
			$date_to=date("Y-m-d",strtotime($request->search_document_date_to));
			$sql.=" and (d.pay_date<='".$date_to." 23:59:59' or (d.pay_date='0000-00-00' and d.create_date<='".$date_to." 23:59:59'))";
		}
		else {
			$date_to=date("Y-m-d");
			$sql.=" and (d.pay_date<='".$date_to." 23:59:59' or (d.pay_date='0000-00-00' and d.create_date<='".$date_to." 23:59:59'))";
		}
		if(!empty($request->search_document_client_name)){
			$sql_cl="select id from company where id in (select distinct(company_id) from user_companys where main_company_id=?i and btype<>5) and name like ?s";
			$res_cl=$db->getCol($sql_cl,$_SESSION['main_company'],'%'.trim($request->search_document_client_name).'%');
			if($res_cl){
				//$search_companys=array_column($res_cl,"id");
				$sql.=" and (d.company_id in (".implode(",",$res_cl).")";
				$is_our=$db->getAll("select id from company where name like ?s and id=?i",'%'.trim($request->search_document_client_name).'%',$_SESSION['main_company']);
				if($is_our){
					$sql.=" or d.company_id=".(int)$_SESSION['main_company'];
				}
				$sql.=")";
			}
			else {
				// проверим может по основной компании идет поиск
				$is_our=$db->getAll("select id from company where name like ?s and id=?i",'%'.trim($request->search_document_client_name).'%',$_SESSION['main_company']);
				if($is_our){
					$sql.=" and d.company_id=".(int)$_SESSION['main_company'];
				}
				else {
					$sql.=" and d.company_id=0";
				}
			}
			//echo $sql;
			$ret['search_document_client_name']=$request->search_document_client_name;
		}
		switch($request->znak){
			case "+":
			case "-": // || $request->znak=="-")) {
				//$sql_znak="select id from document_types where znak=?s";
				//$document_types=$db->getCol($sql_znak,$request->znak);
				$sql.=" and d.type_id in (?b)";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				if ($request->znak=="+") $res=$db->getAll($sql,$_SESSION['company_id'],array(1));//$document_types);
				if ($request->znak=="-") $res=$db->getAll($sql,$_SESSION['company_id'],array(2));//$document_types);
				//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
				break;
			case "rtd": // return to dealer 
				//$sql_znak="select id from document_types where znak=?s";
				//$document_types=$db->getCol($sql_znak,$request->znak);
				$sql.=" and d.type_id in (?b)";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['company_id'],array(7));//$document_types);
				//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
				break;
			case "rfc": // return from client 
				//$sql_znak="select id from document_types where znak=?s";
				//$document_types=$db->getCol($sql_znak,$request->znak);
				$sql.=" and d.type_id in (?b)";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['company_id'],array(6));//$document_types);
				//echo "db->getAll($sql,".$_SESSION['main_company'].",".print_r($document_types,true).");\n";
				break;
			case "exp":
				$sql.=" and type_id in (?b)";
				$parsed="";
				if(isset($request->search_document_orgtype) && (int)$request->search_document_orgtype>0){
					if((int)$request->search_document_orgtype<3)
						$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type<3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
					else {
						$parsed=$db->parse(" and d.company_id in (?b)",$db->getCol("select id from company where type=3 and id in (select company_id from user_companys where main_company_id=?i)",$_SESSION['main_company']));
					}
				}
				$sql.=" ?p ";
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['company_id'],$request->type_ids,$parsed);
				break;
			default:
				if(isset($request->date_type) && $request->date_type=='document_date'){
					$sql.="order by d.document_date desc";
				}
				else {
					$sql.="order by d.create_date desc";
				}
				$res=$db->getAll($sql,$_SESSION['main_company']);
		
		}

		if(!empty($request->search_document_article)){
			//echo Functions::convert_article(trim($request->search_document_article));
			$res_art=$db->getCol("select distinct(document_id) from document_details where document_id in (?b) and deleted=0 and replace(replace(replace(replace(replace(article,',',''),'.',''),' ',''),'-',''),'/','') like ?s",array_column($res,"id"),Functions::convert_article(trim($request->search_document_article))."%");
			foreach($res as $doc_key => $search_doc){
				if(in_array($search_doc["id"],$res_art)){
					$ret_res[]=$res[$doc_key];
				}
			}
			if(!isset($ret_res)) $ret_res=array();
			$ret['search_document_article']=$request->search_document_article;
		}
		if(!isset($ret_res)) $ret_res=$res;
	    if (is_array($res) && count((array)$res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['documents']=$ret_res;
			$document_ids_rtd=array();
			$document_ids_other=array();
			foreach($ret_res as $ret_res1){
				if((int)$ret_res1['type_id']==7) array_push($document_ids_rtd,$ret_res1['id']);
				else array_push($document_ids_other,$ret_res1['id']);
			}
        	$ret['document_types']=$db->getInd("id","select id,name,descr from document_types");
			//if($request->znak=="rtd"){
			$document_det_pos_rtd=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(dealer_price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",$document_ids_rtd);
			//}
			//else {
			$document_det_pos_other=$db->getInd("document_id","SELECT document_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_details where document_id in (?b) and deleted=0 GROUP BY document_id",$document_ids_other);
			//}
			$ret['document_det_pos']=array();
			foreach ($document_det_pos_rtd as $key=>$val) {
				$ret['document_det_pos'][$key]=$val;
			}
			foreach ($document_det_pos_other as $key=>$val) {
				$ret['document_det_pos'][$key]=$val;
			}
			//$ret['document_det_pos']=array_merge($document_det_pos_rtd,$document_det_pos_other);
			$ret['document_job_pos']=$db->getInd("document_id","SELECT document_id,COUNT(job_id) AS positions,SUM(COUNT) AS pos_count,sum(price*count) as pos_sum FROM document_jobs where document_id in (?b) and deleted=0 GROUP BY document_id",array_column($ret_res,"id"));
			$ret['sql']=$sql;
			$ret['msg']="";
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    else {
    		$ret['status']="ok";
    		$ret['msg']="";
			$ret['documents']=array();
			$ret['search_document_date_to']=$date_to;
			$ret['search_document_date_from']=$date_from;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function set_document_obrabotan($request) {
	    $db = DB::getInstance();
		if($db->query("update document set obrabotan=?i where id=?i and main_company=?i",$request->obrabotan,$request->document_id,$_SESSION['main_company'])){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return array("status"=>"err","err"=>"Не удалось обновить статус документа");
		}
	}

	public static function join_documents($request) {
	    $db = DB::getInstance();
		//print_r($request->documents);
		if(!isset($request->documents) || !is_array($request->documents) || count($request->documents)==0){
			return ["status"=>"err","err"=>"Не указаны документы для объединения"];
		}
		$is_one_company=$db->getOne("select count(distinct(company_id)) from document where id in (?b) and main_company=?i",$request->documents,$_SESSION['main_company']);
		if($is_one_company!=1){
			return ["status"=>"err",
					"err"=>"Документы выписаны разным организациям",
					//"sql"=>$db->parse("select count(distinct(company_id)) from document where id in (?b) and main_company=?i",$request->documents,$_SESSION['main_company']),
					//"is_one_company"=>$is_one_company
		];

		}
		else {
			//return ["status"=>"ok","err"=>"", "msg"=>"документы принадлежат одной организации"];
		}
		$documents=$db->getAll("select * from document where id in (?b) and main_company=?i",$request->documents,$_SESSION['main_company']);
		$max_doc=max($request->documents);
		$max_zakaz=max(array_column($documents,"zakaz_id"));
		foreach($documents as $dk=>$dv){
			if(!$db->query("update zakaz_details set zakaz_id=?i where zakaz_id=?i",$max_zakaz,$dv['zakaz_id'])){
				return ["status"=>"err","err"=>"Ошибка при перемещении деталей из заказа №".$dv['zakaz_id']];
			}
			if(!$db->query("update document_details set document_id=?i where document_id=?i",$max_doc,$dv['id'])){
				return ["status"=>"err","err"=>"Ошибка при перемещении деталей из документа №".$dv['id']];
			}
			if(!$db->query("update zakaz_jobs set zakaz_id=?i where zakaz_id=?i",$max_zakaz,$dv['zakaz_id'])){
				return ["status"=>"err","err"=>"Ошибка при перемещении услуг из заказа №".$dv['zakaz_id']];
			}
			if(!$db->query("update document_jobs set document_id=?i where document_id=?i",$max_doc,$dv['id'])){
				return ["status"=>"err","err"=>"Ошибка при перемещении услуг из документа №".$dv['id']];
			}
			if($dv['id']!=$max_doc){
				if(!$db->query("update document set deleted=1 where id=?i",$dv['id'])){
					return ["status"=>"err","err"=>"Ошибка при удалении документа №".$dv['id']];
				}
			}
			if($dv['zakaz_id']!=$max_zakaz){
				if(!$db->query("update zakaz set deleted=1 where id=?i",$dv['zakaz_id'])){
					return ["status"=>"err","err"=>"Ошибка при удалении zakaza №".$dv['zakaz_id']];
				}
				if(!$db->query("update payment set zakaz_id=?i where zakaz_id=?i",$max_zakaz,$dv['zakaz_id'])){
					return ["status"=>"err","err"=>"Ошибка при изменении платежей заказа №".$dv['zakaz_id']];
				}
			}
		}
		$last_zakaz_detail_id=$db->getOne("select max(id) from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199) and status<>201",$max_zakaz);
		if($last_zakaz_detail_id){
			$zak_det=new ZakazDetail($last_zakaz_detail_id);
			$zak_det->recalculate_pozition_count();
		}

		$last_zakaz_job_id=$db->getOne("select max(id) from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$max_zakaz);
		if($last_zakaz_job_id){
			$zak_job=new ZakazJob($last_zakaz_job_id);
			$zak_job->recalculate_pozition_count();
		}

		if(!$db->query("update document set zakaz_id=?i where id=?i",$max_zakaz,$max_doc)){
				return ["status"=>"err","err"=>"Ошибка при перемещении деталей из заказа №".$dv['zakaz_id']];
		}
		else {
			return ["status"=>"ok","err"=>"", "msg"=>"документы успешно объединены"];
		}
	}

}




?>
