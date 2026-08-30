<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\ExcelToBase;
use Sort1API\Components\Config;
use Sort1API\Components\UploadHandler;
use Sort1API\Components\Document;
use Sort1API\Components\Logger;
use Sort1API\Components\Models\Documents;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\LocalCrosses;
use Sort1API\Components\Functions;
/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class ExcelToBases extends Model {

	public static function upload_file() {
		$upload_handler = new UploadHandler();
		
		foreach($upload_handler->response['files'] as $file_key => $file_val) {
			$base_id = $file_val->base_id;
			switch($file_val->base_type) {
				case "document":
					$base_type = 3;
					break;
				case "sklad":
					$base_type = 1;
					break;
				case "price_list":
					$base_type = 2;
					break;
				default:
					$base_type = 1;
					// throw new InvalidArgumentException("Invalid base type: " . $file_val->base_type);
			}
			
			$file_name = $file_val->realname;
			$local_file_name = $file_val->name;
	
			// Debugging: Check if file exists before proceeding
			// if (!file_exists($local_file_name)) {
			// 	error_log("File does not exist: " . $local_file_name);
			// }
	
			// Create ExcelToBase object and return the first details
			$excel_to_base = new ExcelToBase($base_id, $base_type, $file_name, $local_file_name);
			return $excel_to_base->GetFirstDetails();
		}
	
		// Optional: Return response if necessary
		// return $upload_handler->response;
	}

	public static function get_uploaded_file_page($request){
	    if(isset($request->base_id) && (int)$request->base_id>0) $base_id=(int)$request->base_id;
	    if(isset($request->base_type) && (int)$request->base_type>0) $base_type=(int)$request->base_type;
	    if(isset($request->selected_page) && (int)$request->selected_page>=0) $selected_page=(int)$request->selected_page;
		switch((int)$base_type){
			case 1: $base_type_name="sklad";break;
			case 2: $base_type_name="price_list";break;
			case 3: $base_type_name="document";break;
		}
		if($base_type==2 && $base_id>0){
			$db = DB::getInstance();
			$price_list=$db->getRow("select * from price_list where id=?i",$base_id);
			if($price_list['price_get_type']==3){
				if(!empty($price_list['get_url'])){
					$file=file_get_contents($price_list['get_url']);
					$filename=$price_list['get_url'];
					$localfilename=md5($price_list['get_url'].date("Y-m-d H:i:s"));
					$sql="update ".$base_type_name." set filename='".$filename."',localfilename='".$localfilename."' where id=?i";
		    		$res=$db->query($sql,(int)$base_id);
					//echo dirname(@$_SERVER['SCRIPT_FILENAME']).'/files/'.$localfilename;
					file_put_contents(dirname(@$_SERVER['SCRIPT_FILENAME']).'/files/'.$localfilename,$file);
				}
			}
		}
	    if ($base_id>0 && $base_type>0) {
				$excel_to_base = new ExcelToBase($base_id,$base_type);
				$ret=$excel_to_base->GetFirstDetails($selected_page);
				$ret['selected_page']=$selected_page;
				$ret['status']="ok";
				$ret['msg']="";
				return $ret;
	    }
	    else return array("status"=>"err","err"=>"не знаю где взять данные","msg"=>"не знаю где взять данные");
	}

	public static function SetColAssoc($request){
	    if (isset($request->base_id) && (int)$request->base_id>0) $base_id=(int)$request->base_id;
	    if (isset($request->base_type) && (int)$request->base_type>0) {
				$base_type=(int)$request->base_type;
	    }
	    if (isset($request->columns) && count((array)$request->columns)>0) $columns=$request->columns;
	    if (isset($request->other) && count((array)$request->other)>0) $others=$request->other;
		if (isset($request->put_zero_count) && $request->put_zero_count=="on") $put_zero_count=1;
		else $put_zero_count=0;
		if (!empty($request->cross_delimiter)) $cross_delimiter=$request->cross_delimiter;
		else $cross_delimiter=" ";
	    if (isset($request->selected_page)) $selected_page=(int)$request->selected_page;
	    if(isset($base_id) && isset($base_type)){

				$excel_to_base=new ExcelToBase($base_id,$base_type);
				$col_assoc_save=array(
				    "selected_page" => $selected_page,
				    "columns" => $columns,
					"put_zero_count" => $put_zero_count,
					"cross_delimiter" => (string)$cross_delimiter,
				    "others" => $others
				);
				$excel_to_base->col_assoc=$col_assoc_save;
				$excel_to_base->_selected_page=$selected_page;
				$db = DB::getInstance();
				$sql="update ".$excel_to_base->_base_types[$base_type]." set col_assoc='".json_encode($col_assoc_save)."' where id=?i";
				$res=$db->query($sql,$base_id);
				if($res){
					$job_sql="select status,create_date from excel_loader_jobs where base_id=?i and base_type=?i order by create_date desc";
					$job_status=$db->getRow($job_sql,$base_id,$base_type);
					if((int)$job_status['status']<9 && (int)$job_status['status']>=1 && strtotime($job_status['create_date'])<(time()-20*60)){
						return array("status"=>"err","err"=>"Процесс загрузки файла уже запущен, дождитесь окончания загрузки","job_status"=>(int)$job_status);
					}
					//$is_job="select id,status from excel_loader_jobs where base_id=?i and base_type=?i and status<9 limit 1";
					//$res_is_job=$db->getRow($is_job,$base_id,$base_type);
					//if((int)$res_is_job['status']<9 ){
						// процесс уже запущен, надо дождаться окончания
					//	return array("status"=>"err","err"=>"процесс уже запущен, надо дождаться окончания","msg"=>"");
					//}
					$job_sql="insert into excel_loader_jobs (base_id,base_type,selected_page,status,status_descr,percent,user_id,create_date) values (?i,?i,?i,?i,?s,?i,?i,?s)";
					//on duplicate key update selected_page=values(selected_page),status=values(status),status_descr=values(status_descr),percent=values(percent)";
					$job_res=$db->query($job_sql,$base_id,$base_type,$selected_page,1,"Начало загрузки файла",0,$_SESSION['user_id'],date("Y-m-d H:i:s"));
					$job_id=$db->insertId();
					$cmd="cd ../cron; php ExcelToBasesDirect.php $base_id $base_type $selected_page ".$_SESSION['user_id']." ".$_SESSION['main_company'];
					self::execInBackground($cmd);
					return array("status"=>"ok","err"=>"","msg"=>"<div id='loader_progress_header'>Идет процесс загрузки</div> <div id='loader_progress_bar'></div>","job_status"=>1,"job_id"=>$job_id);
				    //$saved=self::SaveFileDataToBase($excel_to_base,$selected_page);
				    //echo print_r($saved,true);
				    //return $saved;
				    //return array("status"=>"ok","msg"=>"","err"=>"");
				}
				else return array("status"=>"err","msg"=>"","err"=>"Ошибка при обновлении результатов");
	    }
	}

	private static function convert_article($art){
		return Functions::convert_article($art);
	    //$new_art=mb_strtoupper(str_replace(array("[","-","+","=","/","\\","'","\"","]"," ",".","#","$","%","^","&","*","(",")","\.","\t","\n"),"",$art));
	    //return $new_art;
	}

	/*private static function get_local_details($detail){
	    $db = DB::getInstance();
	    //include "/var/www/html1/include/lib.php";
	    $brand_id_arr=$db->getRow("select id,brand_id from local_brands where brand=?s",self::convert_article($detail['brand']));
	    if($brand_id_arr['brand_id']==0) $brand_id=$brand_id_arr['id'];
	    else $brand_id=$brand_id_arr['brand_id'];
	    $art_id=$db->getOne("select id from local_details where article=?s and brand_id=?i",self::convert_article($detail['article']),$brand_id);
	    if ($art_id) { $detail["detail_id"]=-$art_id; $detail["brand_id"]=-$brand_id; }
	    else {
				if ($brand_id) {
				    $sql="insert ignore into local_details set article=?s,article_row=?s,brand_id=?s,create_date=?s,update_date=?s,name=?s";
				    $id=$db->query($sql,self::convert_article($detail['article']),$detail['article'],$brand_id,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$detail['name']);
				    file_put_contents("/var/log/shop/api/local_details.log","$sql,self::convert_article(".$detail['article']."),".$detail['article'].",$brand_id,".date("Y-m-d H:i:s").",".date("Y-m-d H:i:s").",".$detail['name']."\n",FILE_APPEND);
				    $detail['detail_id']=-$db->insertId();
				    $detail['brand_id']=$brand_id;
				}
				else {
				    $get_brands_arr=array("action"=>"get_brand_id","brand"=>self::convert_article($detail['brand']));
				    $res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),json_encode($get_brands_arr));
				    $res_arr=json_decode($res['body'],true);
				    file_put_contents("/var/log/shop/api/get_brand_id.log","send: ".json_encode($get_brands_arr)."\n recieved: ".$res['body']."\n",FILE_APPEND);
				    $brand_id=(int)$res_arr['brand_ids'][self::convert_article($detail['brand'])][0];
				    if($brand_id>0)
							$b_id=$db->query("insert ignore into local_brands set brand_id=?i,brand=?s,brand_row=?s,create_date=?s,update_date=?s",$brand_id,self::convert_article($detail['brand']),$detail['brand'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
				    else {
							$b_id=$db->query("insert ignore into local_brands set brand_id=0,brand=?s,brand_row=?s,create_date=?s,update_date=?s",self::convert_article($detail['brand']),$detail['brand'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
							$brand_id=-$db->insertId();
				    }
				    $det_id=$db->query("insert ignore into local_details set article=?s,article_row=?s,brand_id=?s,create_date=?s,update_date=?s,name=?s",self::convert_article($detail['article']),$detail['article'],$brand_id,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$detail['name']);
				    $detail['detail_id']=-$db->insertId();
				    $detail['brand_id']=$brand_id;
				}
	    }
	    return $detail;
	} */

	public static function SaveFileDataToBase($excel_to_base,$selected_page,$user_id){
	    //echo print_r($excel_to_base,true);
	    //echo "sel_page: $selected_page\n";
		//include "/var/www/html1/include/lib.php";
		$db = DB::getInstance();
		$db->query("update excel_loader_jobs set status=2,status_descr=?s,percent=?i where base_id=?i and base_type=?i and user_id=?i","Загружаем файл в память",2,$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
		$db->query("commit");
		$data_array = $excel_to_base->GetJsonFileData($selected_page);
		//echo "data_array: ".print_r($data_array,true)."\n";
		$db->query("update excel_loader_jobs set status=3,status_descr=?s,percent=?i where base_id=?i and base_type=?i and user_id=?i","Файл загружен",20,$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
		$db->query("commit");
		//{"action":"get_details","brands_aliases":true, "offline":true, "detail":[{"k":"1","a":"'.$article.'","b":"11111"}]}
	    $get_detail_ids=array( 
				"action"=>"get_details",
				"brands_aliases"=>true,
				"offline"=>true,
				"detail"=>array()
	    );
		//$user_id=$_SESSION['user_id'];
		//session_write_close();
	    $i=0;$x=0;
	    $details=array();
		$crosses=array();
	    $art_col=array_search("art",$excel_to_base->col_assoc['columns']);
	    $brand_col=array_search("brand",$excel_to_base->col_assoc['columns']);
	    $name_col=array_search("name",$excel_to_base->col_assoc['columns']);
	    $count_col=array_search("cnt",$excel_to_base->col_assoc['columns']);
		$price_no_nds_col=array_search("cost_no_nds",$excel_to_base->col_assoc['columns']);
	    $price_col=array_search("cost",$excel_to_base->col_assoc['columns']);
		$nds_col=array_search("nds",$excel_to_base->col_assoc['columns']);
		$time_col=array_search("time",$excel_to_base->col_assoc['columns']);
		$my_code_col=array_search("my_code",$excel_to_base->col_assoc['columns']);
		$images_col=array_search("images",$excel_to_base->col_assoc['columns']);
		$place_col=array_search("place",$excel_to_base->col_assoc['columns']);
		$descr_col=array_search("descr",$excel_to_base->col_assoc['columns']);
		if($excel_to_base->_type==3) $sale_price_col=array_search("sale_price",$excel_to_base->col_assoc['columns']);
		$min_count_must_have_col=array_search("min_count_must_have",$excel_to_base->col_assoc['columns']);
		$ean13_col=array_search("ean13",$excel_to_base->col_assoc['columns']);
		$analogs_col=array_search("analogs",$excel_to_base->col_assoc['columns']);
		$detail_size_col=array_search("detail_size",$excel_to_base->col_assoc['columns']);
		$db->query("update excel_loader_jobs set status=4,status_descr=?s,percent=?i where base_id=?i and base_type=?i and user_id=?i","Сопоставление загруженных деталей",30,$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
		$db->query("commit");
		if($excel_to_base->_type==2) { // price details see default_brand in config
			$default_brand=$db->getOne("select default_brand from ".$excel_to_base->_base_types[$excel_to_base->_type]." where id=".$excel_to_base->_base_id);	
		}
		$documents=$db->getCol("select id from document where deleted=0 and main_company in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
		$sklads=$db->getCol("select id from sklad where company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
		foreach($data_array['data'] as $key=>$val){
				//print_r($val);
				//print_r($excel_to_base);
				//echo array_search("art",$excel_to_base->col_assoc['columns']).",".array_search("brand",$excel_to_base->col_assoc['columns'])."\n";
				$article=self::convert_article($val[$art_col]);
				if($brand_col!==false) $brand=self::convert_article($val[$brand_col]);
				else $brand="Unknown";
				if($excel_to_base->_type==2 && !empty($default_brand)) { // price details see default_brand in config
					$brand=$default_brand;
				}
				$get_detail_ids['detail'][$i]['k']=$key+1;
				$get_detail_ids['detail'][$i]['a']=$article;
				if($brand=="Unknown" || $brand=="undefined") {			
					$get_detail_ids['detail'][$i]['b']="";
				}
				else $get_detail_ids['detail'][$i]['b']=$brand;
				$details[$key]['article']=$article;
				$details[$key]['brand']=$brand;
				if($name_col!==false) $details[$key]['name']=$val[$name_col];
				if($descr_col!==false) $details[$key]['descr']=$val[$descr_col];
				if($my_code_col!==false) $details[$key]['my_code']=$val[$my_code_col];
				if($images_col!==false){
					$details[$key]['images']=$val[$images_col];
				}
				if($count_col!==false) $details[$key]['count']=(float)str_replace(array(" ",","),array("","."),$val[$count_col]);
				if($price_no_nds_col!==false){
					if($nds_col!==false) {
						if(preg_match("/(\d+)%/",$val[$nds],$nds_match)){
							$nds=1+(int)$nds_match/100;
						}
						else $nds=1.2;
					}
					else $nds=1.2;
					$details[$key]['price']=(float)str_replace(array(" ",","),array("","."),$val[$price_no_nds_col])*$nds;
				}
				else {
					if($price_col!==false) $details[$key]['price']=(float)str_replace(array(" ",","),array("","."),$val[$price_col]);
				}
				if($place_col!==false) $details[$key]['place']=$val[$place_col];
				if($min_count_must_have_col!==false) $details[$key]['min_count_must_have']=$val[$min_count_must_have_col];
				if($ean13_col!==false) $details[$key]['ean13']=$val[$ean13_col];
				if($analogs_col!==false) {$details[$key]['analogs']=$val[$analogs_col];}
				if($detail_size_col!==false) {$details[$key]['detail_size']=$val[$detail_size_col];}
				if($excel_to_base->_type==3 && $sale_price_col!==false) $details[$key]['sale_price']=$val[$sale_price_col];
				//print_r($details[$key]);
				if($time_col!==false) $details[$key]['time']=(int)$val[$time_col]+$excel_to_base->timeplus;
				else $details[$key]['time']=$excel_to_base->timeplus;
				$i++;$x++;
				if ($x>4000){
				    $x=0;
				    $send=json_encode($get_detail_ids);
					//echo $send;
					//$json_data=json_encode($get_brand_ids);
					//echo $send;
					$context = stream_context_create([
						'http' => [
							'method' => 'POST',
							'header' => "Content-type: application/json\r\n" .
								"Accept: application/json\r\n" .
								"Connection: close\r\n" .
								"Content-length: " . strlen($send) . "\r\n",
							'protocol_version' => 1.1,
							'content' => $send
						],
						'ssl' => [
							'verify_peer' => false,
							'verify_peer_name' => false
						]
					]);
					$url="http://".Config::get("library_ip")."/api/v2/index.php";
					$res=file_get_contents($url,false,$context);
				    //$res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
				    //echo print_r($res['body'],true);
					//$r=json_decode($res['body'],true);
					$r=json_decode($res,true);
				    //file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
				    foreach($r['details'] as $r_key=>$r_val){
						//file_put_contents("/var/log/shop/api/excel_to_base.log",print_r($r_val,true),FILE_APPEND);
							if((int)$r_val['errcode']==0 || (int)$r_val['errcode']==2){
								if(isset($r_val['data'])){
									$details[$r_key-1]['detail_id']=$r_val['data'][0]['detail_id'];
									$details[$r_key-1]['brand_id']=$r_val['data'][0]['brand_id'];
									if($details[$r_key-1]['brand']=="Unknown") {
										$details[$r_key-1]['brand']=$r['brands_aliases'][$details[$r_key-1]['brand_id']]['main']['brand'];
									}
									if((int)$details[$r_key-1]['detail_id']==0) $details[$r_key-1]=LocalDetails::get_local_details($details[$r_key-1]);
								}
								else {
									$details[$r_key-1]=LocalDetails::get_local_details($details[$r_key-1]);
								}
							}
							else {
							    $details[$r_key-1]=LocalDetails::get_local_details($details[$r_key-1]);
							}
		    		}
		    		$get_detail_ids['detail']=array();
				}
	    }
		if ($x<4000){
		    $send=json_encode($get_detail_ids);
			//echo $send;
			$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => "Content-type: application/json\r\n" .
						"Accept: application/json\r\n" .
						"Connection: close\r\n" .
						"Content-length: " . strlen($send) . "\r\n",
					'protocol_version' => 1.1,
					'content' => $send
				],
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false
				]
			]);
			$url="http://".Config::get("library_ip")."/api/v2/index.php";
			$res=file_get_contents($url,false,$context);
		    //$res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
		    //echo print_r($res['body'],true);
			//$r=json_decode($res['body'],true);
			$r=json_decode($res,true);
		    //file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
		    foreach($r['details'] as $r_key=>$r_val){
					if($r_val['errcode']==0 || (int)$r_val['errcode']==2){
						if(isset($r_val['data'])){
							$flagbrandsequal=0;
							foreach($r_val['data'] as $rvalkey=>$rvaldata){
								if($r_val['data'][$rvalkey]['brand']==$details[$r_key-1]['brand']){
									$details[$r_key-1]['detail_id']=$r_val['data'][$rvalkey]['detail_id'];
									$details[$r_key-1]['brand_id']=$r_val['data'][$rvalkey]['brand_id'];
									if($details[$r_key-1]['brand']=="Unknown") {
										$details[$r_key-1]['brand']=$r['brands_aliases'][$details[$r_key-1]['brand_id']]['main']['brand'];
									}
									$flagbrandsequal=1;
									break;
								}
							}
							if($flagbrandsequal==0){
								$details[$r_key-1]=LocalDetails::get_local_details($details[$r_key-1]);
							}
						}
						else {
							$details[$r_key-1]=LocalDetails::get_local_details($details[$r_key-1]);
						}

					}
					else {
					    $details[$r_key-1]=LocalDetails::get_local_details($details[$r_key-1]);
					}
		    }
		}
		$db->query("update excel_loader_jobs set status=5,status_descr=?s,percent=?i where base_id=?i and base_type=?i and user_id=?i","Заливаем в базу",50,$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
		$db->query("commit");
		if($excel_to_base->_type==1) {
		//Sklad Details
	    }
	    if($excel_to_base->_type==3) { 
				//Document Details 
				$document=new Document($excel_to_base->_base_id);
				$not_saved_document_details=array();
				$cross_count=0;
				foreach($details as $s_key=>$s_val){
					if($analogs_col!==false) {
						if(!empty($excel_to_base->col_assoc['cross_delimiter'])) $cross_delimiter=$excel_to_base->col_assoc['cross_delimiter'];
						else $cross_delimiter=" ";
						$cross_split=explode($cross_delimiter,$s_val['analogs']);
						foreach($cross_split as $cross){
							if(trim($cross)!=""){
								$crosses[$cross_count]['oem_article']=trim($s_val['article']);
								$crosses[$cross_count]['oem_brand']=$s_val['brand'];
								$crosses[$cross_count]['cross_name']=$s_val['name'];
								$crosses[$cross_count]['cross_article']=trim($cross);
								$cross_count++;
							}
						}
					}
					$s_val['subaction']="add";
				    $document_detail_data=json_decode(json_encode($s_val));
				    $document_detail_data->sklad_id=$document->sklad_id;
				    $document_detail_data->document_id=$document->id;
					$document_detail_data->documents=$documents;
					$document_detail_data->sklads=$sklads;
				    //file_put_contents("/var/log/shop/api/save_document_details_from_excel.log",date("Y-m-d H:i:s")." ".print_r($document_detail_data,true)."\n",FILE_APPEND);
					if(!preg_match("/\bитого\b/ui",$document_detail_data->article) 
						&& !preg_match("/\bитого\b/ui",$document_detail_data->brand) 
						&& !preg_match("/\bитого\b/ui",$document_detail_data->name) 
						&& ($document_detail_data->brand!="Unknown" || !empty($document_detail_data->article) || !empty($document_detail_data->my_code)) 
						&& (int)$document_detail_data->count<900000
					){
						$saved=DocumentDetails::save_document_detail($document_detail_data);
						if($saved['status']!="ok") { 
							$not_saved_document_details[]=$document_detail_data;
							$not_saved_document_details[count((array)$not_saved_document_details)-1]->error=$saved;
						}
					} 
					else { 
						$not_saved_document_details[]=$document_detail_data;
					}
					//file_put_contents("/var/log/shop/api/save_document_details_from_excel.log",date("Y-m-d H:i:s")." ".print_r($document_detail_data,true)."\nfunction save return this\n".print_r($saved,true)."\n",FILE_APPEND);
				}
				if(count((array)$crosses)>0) { 
					$to_save_crosses=(object)array("local_crosses"=>$crosses);
					$crosses_save=LocalCrosses::save_local_crosses($to_save_crosses);
					//file_put_contents("/var/log/sort1/save_crosses_from_price_load.log","crosses: ".print_r($crosses,true),FILE_APPEND);
					//file_put_contents("/var/log/sort1/save_crosses_from_price_load.log","crosses_save: ".print_r($crosses_save,true),FILE_APPEND);
				}
				if(count((array)$not_saved_document_details)>0) {
				    $ret['status']="err";
				    $ret['not_saved_details']=$not_saved_document_details;
					$ret['err']="Некоторые позиции из файла не удалось добавить в базу";
					Logger::log("Not Saved details: ".print_r($not_saved_document_details ,true)."\n", "document_details_place");
					file_put_contents("/var/log/shop/api/save_document_details_from_excel.log",date("Y-m-d H:i:s")." ".print_r($not_saved_document_details,true)."\n",FILE_APPEND);
				}
				else {
				    $ret['status']="ok";
				    $ret['msg']="Данные успешно добавлены в базу данных";
				}
				//$db->query("update excel_loader_jobs set status=9,status_descr=?s,percent=?i where base_id=?i and base_type=?i","Процесс завершен",100,$excel_to_base->_base_id,$excel_to_base->_type);
				//return $ret;
		}
		if($excel_to_base->_type==2) {
			//price_list details
			$db->query("delete from ".$excel_to_base->_base_types[$excel_to_base->_type]."_details where price_list_id=?i",$excel_to_base->_base_id);
			$sql="insert into ".$excel_to_base->_base_types[$excel_to_base->_type]."_details (detail_id,article,brand_id,brand,name,price,count,time,is_active,".$excel_to_base->_base_types[$excel_to_base->_type]."_id,create_date,update_date,user_id,default_markup,descr) values ";

			$base_data=$db->getRow("select status,default_markup,default_brand from ".$excel_to_base->_base_types[$excel_to_base->_type]." where id=".$excel_to_base->_base_id);
			$is_active=$base_data['status'];
			$default_markup=$base_data['default_markup'];
			$not_saved_document_details=array();
			$saved_count=0;$cross_count=0;

			foreach($details as $s_key=>$s_val){
				if($analogs_col!==false) {
					if(!empty($excel_to_base->col_assoc['cross_delimiter'])) $cross_delimiter=$excel_to_base->col_assoc['cross_delimiter'];
					else $cross_delimiter=" ";
					$cross_split=explode($cross_delimiter,$s_val['analogs']);
					foreach($cross_split as $cross){
						if(trim($cross)!=""){
							$crosses[$cross_count]['oem_article']=trim($s_val['article']);
							$crosses[$cross_count]['oem_brand']=$s_val['brand'];
							$crosses[$cross_count]['cross_name']=$s_val['name'];
							$crosses[$cross_count]['cross_article']=trim($cross);
							$cross_count++;
						}
					}
				}
				//if($s_key>0) $sql.=",";
				//echo $s_val['price']." ";
				$sql1=$sql." (?i,?s,?i,?s,?s,?s,?i,?i,?i,?i,?s,?s,?i,?i,?s) on duplicate key update price=values(price),time=values(time),count=values(count)";
				if($s_val['detail_id']!=NULL && $s_val['detail_id']!="" && (int)$s_val['detail_id']!=0) {
						$res=$db->query($sql1,$s_val['detail_id'],trim($s_val['article']),$s_val['brand_id'],trim($s_val['brand']),trim($s_val['name']),
							round($s_val['price'],2),(int)$s_val['count'],$s_val['time'],(int)$is_active,$excel_to_base->_base_id,date("Y-m-d H:i:s"),
							date("Y-m-d H:i:s"),$user_id,$default_markup,($s_val['descr']===null?"":$s_val['descr']));
						if(!$res) $not_saved_document_details[]=$s_val;
						else $saved_count++;
				}
				//file_put_contents("/var/log/shop/api/to_price_detail.log",$sql1.",".$s_val['detail_id'].",".$s_val['article'].",".$s_val['brand_id'].",".$s_val['brand'].",".$s_val['name'].",".$s_val['price'].",".$s_val['count'].",".$s_val['time'].",".(int)$is_active.",".$excel_to_base->_base_id.",".date("Y-m-d H:i:s").",".date("Y-m-d H:i:s").",".$user_id.",".$default_markup."\n",FILE_APPEND);
				//if($s_val['detail_id']!=NULL)
			    //    $sql.="(".(int)$s_val['detail_id'].",'".self::esc($s_val['article'])."',".(int)$s_val['brand_id'].",'".self::esc($s_val['brand'])."','".self::esc($s_val['name'])."','".(float)$s_val['price']."',".(int)$s_val['count'].",".(int)$s_val['time'].",".(int)$is_active.",".$excel_to_base->_base_id.",'".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."',".$_SESSION['user_id'].")";
			}
			
			if(count((array)$crosses)>0) {
				$to_save_crosses=(object)array("local_crosses"=>$crosses);
				$crosses_save=LocalCrosses::save_local_crosses($to_save_crosses);
				//file_put_contents("/var/log/sort1/save_crosses_from_price_load.log","crosses: ".print_r($crosses,true),FILE_APPEND);
				//file_put_contents("/var/log/sort1/save_crosses_from_price_load.log","crosses_save: ".print_r($crosses_save,true),FILE_APPEND);
			}
			//$sql.=" on duplicate key update price=values(price),time=values(time),count=values(count)";
			//$res=$db->query($sql);
			if(count((array)$not_saved_document_details)>0) { 
				$ret['status']="err";
				$ret['not_saved_details']=$not_saved_document_details;
				$ret['err']="Некоторые позиции из файла не удалось добавить в базу";
				//file_put_contents("/var/log/shop/api/save_price_list_details_from_excel.log",date("Y-m-d H:i:s")." ".print_r($not_saved_document_details,true)."\n",FILE_APPEND);
			}
			else {
			    $ret['status']="ok";
			    $ret['msg']="Данные успешно добавлены в базу данных";
				$ret['saved_count']=$saved_count;
			}
			$counts=$db->getRow("SELECT COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count FROM price_list_details where price_list_id=?i",$excel_to_base->_base_id);
			$db->query("update price_list set update_date=?s,pos_count=?s,positions=?s where id=?i",date("Y-m-d H:i:s"),(int)$counts['pos_count'],(int)$counts['positions'],$excel_to_base->_base_id);
			//file_put_contents("/var/log/shop/api/update_price_detail.log","update price_list set update_date='".date("Y-m-d H:i:s")."' where id=".$excel_to_base->_base_id,FILE_APPEND);
		}
		$job_id=$db->getOne("select id from excel_loader_jobs where base_id=?i and base_type=?i and status<9 and user_id=?i order by create_date desc limit 1",$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
		if($ret['status']=="ok"){
			$db->query("update excel_loader_jobs set status=9,status_descr=?s,percent=?i where base_id=?i and base_type=?i and user_id=?i","Процесс завершен",100,$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
			if($job_id)
				$db->query("insert into system_messages (user_id,message,create_date,status,job_type,job_id) values (?i,?s,?s,?i,?i,?i)",$user_id,"Процесс загрузки деталей завершен без ошибок",date("Y-m-d H:i:s"),1,1,$job_id);
			else {
				echo "ошибка не найдена запись в базе для job_id\n";
			}
				//status 1=unread, job_types: 1 excel_loader
		}
		else {
			$db->query("update excel_loader_jobs set status=9,status_descr=?s,percent=?i,loader_status_message=?s,not_saved_details=?s where base_id=?i and base_type=?i and user_id=?i","Процесс завершен",100,$ret['err'],json_encode($ret['not_saved_details']),$excel_to_base->_base_id,$excel_to_base->_type,$user_id);
			$db->query("insert into system_messages (user_id,message,create_date,status,job_type,job_id) values (?i,?s,?s,?i,?i,?i)",$user_id,"!!! Процесс загрузки деталей завершен с ошибками",date("Y-m-d H:i:s"),1,1,$job_id);
		}
		//$db->query("update "); //Надо добавить системное сообщение о завершении загрузки
	  return $ret;
	}

	public static function esc($str){
	    return str_replace("'","\"",$str);
	}

	protected static function get_server_var($id) {
			return @$_SERVER[$id];
	}

	private static function execInBackground($cmd)
	{
		if (substr(php_uname(), 0, 7) == "Windows")
			{
			pclose(popen("start /B ". $cmd, "r"));
		}
		else
			{
			exec($cmd . " > /dev/null &");
		}
	}

	public static function get_loader_job_status($request){
		if(!empty($request->job_id) && (int)$request->job_id>0){
			$db = DB::getInstance();
			$job=$db->getRow("select * from excel_loader_jobs where id=?i",$request->job_id);
			if($job['not_saved_details']!==null) $job['not_saved_details']=json_decode($job['not_saved_details']);
			return array("status"=>"ok","job"=>$job);
		}
	}


}
?>
