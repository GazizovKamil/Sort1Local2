<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Notify;
use Sort1API\Components\PriceExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sort1API\Components\Models\Search;
use Sort1API\Components\SafeMySQL;
use Sort1API\Components\Config;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/
//require '../vendor/autoload.php';

class PriceExports extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        private static function add_brands($pe,$brands){
            $db = DB::getInstance();
            $db->query("delete from price_exports_brands where price_export_id=?i",$pe->id);
            
            foreach($brands as $key=>$brand){
                $db->query("insert ignore into price_exports_brands values(?i,?i,?s,?s,?s)",$pe->id,$brand['brand_id'],$brand['brand_name'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
            }
        }

        private static function add_export_from($pe,$from_export){
            $db = DB::getInstance();
            $db->query("delete from price_exports_from where price_export_id=?i",$pe->id);
            
            foreach($from_export as $key=>$fe){
                $db->query("insert ignore into price_exports_from values(?i,?i,?i,?s,?s)",$pe->id,$fe['export_from_type'],$fe['export_from_id'],date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
            }
        }

		public static function set_price_export_show_price_name($request){
			$db = DB::getInstance();
      	    if (isset($request->price_export_id) && $request->price_export_id>0) {
          		$price_export=new PriceExport($request->price_export_id);
          		$price_export->update_date=date("Y-m-d H:i:s");
      	    }
			else {
				return self::_error_arr("не указан номер выгрузки");
			}
			if(isset($request->show_price_name)){
				$price_export->show_price_name=(int)$request->show_price_name;
				if($price_export->save()) return array("status"=>"ok","msg"=>"");
				else return array("status"=>"err","err"=>"не удалось сохранить");
			}

		}

        public static function save_price_export($request) {
            $db = DB::getInstance();
      	    if (isset($request->price_export['id'])) $price_export_id=(int)$request->price_export['id'];
      	    if (isset($price_export_id) && $price_export_id>0) {
          		$price_export=new PriceExport($price_export_id);
          		$price_export->update_date=date("Y-m-d H:i:s");
      	    }
      	    else {
          		$price_export=new PriceExport();
          		$price_export->create_date=date("Y-m-d H:i:s");
      	    }
			// echo print_r($request,true);
      	    if (isset($request->price_export['name'])) $price_export->name=$request->price_export['name'];
            if (isset($request->price_export['filename'])) $price_export->filename=$request->price_export['filename'];
            if (isset($request->price_export['price_type_id'])) $price_export->price_type_id=(int)$request->price_export['price_type_id'];
			if (isset($request->price_export['discount_price_type_id'])) $price_export->discount_price_type_id=(int)$request->price_export['discount_price_type_id'];
			if (isset($request->price_export['send_to_email'])) $price_export->send_to_email=$request->price_export['send_to_email'];
			if (isset($request->price_export['format'])) $price_export->format=$request->price_export['format'];
			if (isset($request->price_export['csv_delimiter'])) $price_export->csv_delimiter=$request->price_export['csv_delimiter'];
			if (isset($request->price_export['export_nelikvid'])) $price_export->export_nelikvid=$request->price_export['export_nelikvid'];
			if (isset($request->price_export['enable_export'])) $price_export->enable_export=$request->price_export['enable_export'];
			if (isset($request->price_export['periodically_send_to_email'])) $price_export->periodically_send_to_email=$request->price_export['periodically_send_to_email'];
			if (isset($request->price_export['export_nelikvid_date_from'])) $price_export->export_nelikvid_date_from=$request->price_export['export_nelikvid_date_from'];
			if (isset($request->price_export['export_nelikvid_date_to'])) $price_export->export_nelikvid_date_to=$request->price_export['export_nelikvid_date_to'];
			if (isset($request->price_export['show_price_name'])) $price_export->show_price_name=$request->price_export['show_price_name'];
			if (isset($request->price_export['price_from'])) $price_export->price_from=(float)$request->price_export['price_from'];
			if (isset($request->price_export['price_to'])) $price_export->price_to=(float)$request->price_export['price_to'];
			if (isset($request->price_export['description'])) $price_export->description=$request->price_export['description'];
			if (isset($request->price_export['originality'])) $price_export->originality=$request->price_export['originality'];
			if (isset($request->price_export['email_config_id'])) $price_export->email_config_id=$request->price_export['email_config_id'];
			if (isset($request->price_export['number_format'])) $price_export->number_format=$request->price_export['number_format'];
			if (isset($request->price_export['send_from_my_email'])) $price_export->send_from_my_email=$request->price_export['send_from_my_email'];
			if (isset($request->price_export['manager_name'])) $price_export->manager_name=$request->price_export['manager_name'];
			if (isset($request->price_export['manager_phone'])) $price_export->manager_phone=$request->price_export['manager_phone'];
			if (isset($request->price_export['photo_on'])) $price_export->photo_on=(int)$request->price_export['photo_on'];
			if (isset($request->price_export['selected_cols'])) 
				$price_export->selected_cols=json_encode($request->price_export['selected_cols']);
			if (isset($request->price_export['show_field_names'])) 
				$price_export->show_field_names=($request->price_export['show_field_names']==true?1:0);
			//$json_encoded=json_encode($request->price_export['selected_cols']);
			//var_dump($price_export);
			//echo "price_export->selected_cols=".$price_export->selected_cols."\n json_encoded=".$json_encoded;
			if (isset($request->price_export['address'])) $price_export->address=$request->price_export['address'];
			$price_export->main_company_id=(int)$_SESSION['main_company'];
      	    $err=$price_export->save();
			if((int)$price_export->periodically_send_to_email==1){
				$is_exist_price_export_cron=$db->getRow("select * from price_export_cron where price_export_id=?i",$price_export->id);
				if(!$is_exist_price_export_cron){
					$db->query("insert into price_export_cron (price_export_id,create_date,user_id) values (?i,?s,?i)",$price_export->id,date("Y-m-d H;i:s"),$_SESSION['user_id']);
					//$db->insertId();
				}
			}
      	    // echo print_r($price_export,true);
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->price_export_id) && (int)$request->price_export_id>0){                                
                          		$status="ok"; $msg="Данные успешно изменены";
						}
						else {
							$status="ok"; $msg="";
						}
						//if(count($request->price_export['brands'])>0) 
							self::add_brands($price_export,$request->price_export['brands']);
						//if(count($request->price_export['export_from'])>0) 
							self::add_export_from($price_export,$request->price_export['export_from']);
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"price_export_id"=>$price_export->id);
        }


	public static function get_price_exports($request) {
	    $db = DB::getInstance();
	    $sql="select * from price_exports where main_company_id=?i and deleted=0";
	    $res=$db->getAll($sql,$_SESSION['company_id']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['price_exports']=$res;
            $export_from=$db->getAll("select * from price_exports_from where price_export_id in (?b)",array_column($res,"id"));
            $brands=$db->getAll("select * from price_exports_brands where price_export_id in (?b)",array_column($res,"id"));
			$ret['export_from']=array();
			foreach($export_from as $key=>$val){
				$ret['export_from'][$val['price_export_id']][]=$val;
				if($val['export_from_type']==1) $sklads[]=$val['export_from_id'];
				if($val['export_from_type']==2) $price_lists[]=$val['export_from_id'];		
			}
			if($brands){
				foreach($brands as $key=>$val){
					$ret['brands'][$val['price_export_id']][]=$val;			
				}
			}
			else $ret['brands']=array();
			if(is_array($sklads)) $ret['sklads']=$db->getInd("id","select id,name,status,sklad_use_in_search from sklad where id in(?b)",$sklads);
			else $ret['sklads']=array();
			if(is_array($price_lists)) $ret['price_lists']=$db->getInd("id","select id,name,status from price_list where id in(?b)",$price_lists);
			else $ret['price_lists']=array();
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['price_exports']=array();
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_price_export($request) {
	    $db = DB::getInstance();
		if(isset($request->price_export_id) && (int)$request->price_export_id>0) $price_export_id=$request->price_export_id;
		$sql="select * from price_exports where main_company_id=?i and deleted=0 and id=?i";
	    $res=$db->getAll($sql,$_SESSION['company_id'],$price_export_id);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['price_exports']=$res;
			$ret['brands']=array();
			$ret['export_from']=array();
            $export_from=$db->getAll("select * from price_exports_from where price_export_id in (?b)",array_column($res,"id"));
            $brands=$db->getAll("select * from price_exports_brands where price_export_id in (?b)",array_column($res,"id"));
			foreach($export_from as $key=>$val){
				$ret['export_from'][$val['price_export_id']][]=$val;
				if($val['export_from_type']==1) $sklads[]=$val['export_from_id'];
				if($val['export_from_type']==2) $price_lists[]=$val['export_from_id'];		
			}
			foreach($brands as $key=>$val){
				$ret['brands'][$val['price_export_id']][]=$val;			
			}
			if(count((array)$sklads)>0) $ret['sklads']=$db->getInd("id","select id,name,status,sklad_use_in_search from sklad where id in(?b)",$sklads);
			else $ret['sklads']=array();
			if(count((array)$price_lists)>0) $ret['price_lists']=$db->getInd("id","select id,name,status from price_list where id in(?b)",$price_lists);
			else $ret['price_lists']=array();
			$ret['price_export_cron']=$db->getRow("select * from price_export_cron where price_export_id=?i",$price_export_id);
    		$ret['msg']="";
	    }
	    else {
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['price_exports']=array();
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_price_export($request) {
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->price_export_id)) {$pe_id=(int)$request->price_export_id;}
	    if (isset($pe_id) && $pe_id>0){
		$res2=$db->query("update price_exports set deleted=1 where id=?i and main_company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$pe_id,$_SESSION['user_id']);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="";//"экспорт цен успешно удален";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось удалить экспорт цен";
		}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_price_export_cron($request){
		$db = DB::getInstance();
		if(empty($request->price_export_id) || (int)$request->price_export_id<=0){
			return array("status"=>"err","err"=>"Не указан номер выгрузки");
		}
		$price_export_cron=$db->getRow("select hours,days,months,years from price_export_cron where price_export_id=?i",(int)$request->price_export_id);
		if(!$price_export_cron){
			$db->query("insert into price_export_cron (price_export_id,create_date,user_id) values (?i,?s,?i)",(int)$request->price_export_id,date("Y-m-d H;i:s"),$_SESSION['user_id']);
			$price_export_cron=$db->getRow("select hours,days,months,years from price_export_cron where price_export_id=?i",(int)$request->price_export_id);
			//return array("status"=>"err","err"=>"Выгрузка не существует");
		}
		$price_export_cron['hours']=explode(",",$price_export_cron['hours']);
		$price_export_cron['days']=explode(",",$price_export_cron['days']);
		$price_export_cron['months']=explode(",",$price_export_cron['months']);
		return array("status"=>"ok","price_export_cron"=>$price_export_cron);
	}

	public static function save_price_export_cron($request){
		$db = DB::getInstance();
		if(empty($request->price_export_id) || (int)$request->price_export_id<=0){
			return array("status"=>"err","err"=>"Не указан номер выгрузки");
		}
		/*$price_export_cron=$db->getRow("select hours,days,months,years from price_export_cron where price_export_id=?i",(int)$request->price_export_id);
		if(!$price_export_cron){
			return array("status"=>"err","err"=>"Выгрузка не существует");
		}*/
		if(isset($request->hours)){
			$hours=implode(",",$request->hours);
		}
		if(isset($request->days)){
			$days=implode(",",$request->days);
		}
		if(isset($request->months)){
			$months=implode(",",$request->months);
		}
		$db->query("update price_export_cron set hours=?s,days=?s,months=?s where price_export_id=?i",$hours,$days,$months,$request->price_export_id);
		return array("status"=>"ok","msg"=>"");
	}

	public static function get_export_data($request){
		$db = DB::getInstance();

		$ret=array();
		$select_from_sklads=array();
		$select_from_prices=array();
		$price_export=$db->getRow("select * from price_exports where id=?i and main_company_id=?i",(int)$request->price_export_id,$_SESSION['main_company']);
		$price_export_from=$db->getAll("select * from price_exports_from where price_export_id=?i",(int)$request->price_export_id);
		$price_export_brands=$db->getAll("select * from price_exports_brands where price_export_id=?i",(int)$request->price_export_id);
		if(is_array($price_export_from) && count($price_export_from)>0){
			foreach($price_export_from as $key=>$from){
				switch((int)$from['export_from_type']) {
					case 1: $select_from_sklads[]=$from['export_from_id']; break;
					case 2: $select_from_prices[]=$from['export_from_id']; break;
				}
			}
		}
		else {
			return array("status"=>"err","err"=>"не указаны источники выгрузки");
		}
		$select_from_prices=$db->getCol("select id from price_list where id in (?b) and status=1",$select_from_prices);
		if(!$select_from_prices) $select_from_prices=array();
		if(is_array($price_export_brands) && count($price_export_brands)>0){
			foreach($price_export_brands as $bkey=>$bval){
				$select_brands[]=$bval['brand_id'];
			}
		}
		else {
			$select_brands=array();
		}
		//echo "export_brands=".print_r($price_export_brands,true)."\n";
		//echo "select_brands=".print_r($select_brands,true)."\n";
		$parsed="";
		if($price_export['export_nelikvid']=="1"){
			if($price_export['export_nelikvid_date_from']!="0000-00-00"){
				$export_nelikvid_date_from=$price_export['export_nelikvid_date_from'];
			}
			else {
				$export_nelikvid_date_from=date("Y-m-d",strtotime("3 month ago"));
			}
			if($price_export['export_nelikvid_date_to']!="0000-00-00"){
				$export_nelikvid_date_to=$price_export['export_nelikvid_date_to'];
			}
			else {
				$export_nelikvid_date_to=date("Y-m-d");
			}
			if(count((array)$select_from_sklads)>0) {
				$sold_details=$db->getAll("SELECT COUNT(detail_id) AS sold_count,detail_id,article,`name` FROM zakaz_details 
                        WHERE 
                        detail_id IN (SELECT detail_id FROM sklad_details WHERE sklad_id in (?b) AND `count`>0) 
                        AND (`status`=70 OR `status`=200 OR `status`=201) AND create_date>=?s AND create_date<=?s 
                        AND zakaz_id IN (SELECT id FROM zakaz WHERE main_company_id=?i)
                        GROUP BY detail_id ORDER BY 1",
                        $select_from_sklads,$export_nelikvid_date_from,$export_nelikvid_date_to,$_SESSION['main_company']);
				$parsed.=$db->parse(" and sd.detail_id not in (?b) and sd.create_date<?s",array_column($sold_details,"detail_id"),$export_nelikvid_date_from);
			}
		}
		if(count((array)$select_brands)>0){
			if(count((array)$select_from_sklads)>0) {
				$sklad_details=$db->getAll("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type,sdl.location from sklad_details sd
				left join sklad s on (s.id=sd.sklad_id)
				left join sklad_detail_locations sdl on (sdl.sklad_id = sd.sklad_id and sdl.detail_id=sd.detail_id)
				where sd.count>0 and sd.brand_id in (?b)
				and sd.sklad_id in (?b) ?p order by sd.brand,sd.article",$select_brands,$select_from_sklads,$parsed);
			}
			if(count((array)$select_from_prices)>0) {
				$price_details=$db->getAll("select pd.*,p.name as price_list_name,p.city_name,p.city_id,p.price_type from price_list_details pd
				left join price_list p on (pd.price_list_id=p.id)
				where pd.count>0 and pd.brand_id in (?b) 
				and pd.price_list_id in (?b)",$select_brands,$select_from_prices);
			}
		}
		else {
			if(count((array)$select_from_sklads)>0) { 
				$sklad_details=$db->getAll("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type,sdl.location from sklad_details sd
				left join sklad s on (s.id=sd.sklad_id)
				left join sklad_detail_locations sdl on (sdl.sklad_id = sd.sklad_id and sdl.detail_id=sd.detail_id)
				where sd.count>0
				and sd.sklad_id in (?b) ?p order by sd.brand,sd.article",$select_from_sklads,$parsed);
			}
			if(count((array)$select_from_prices)>0) {
				$price_details=$db->getAll("select pd.*,p.name as price_list_name,p.city_name,p.city_id,p.price_type from price_list_details pd
				left join price_list p on (pd.price_list_id=p.id)
				where  pd.count>0
				and pd.price_list_id in (?b)",$select_from_prices);
			}
		}
		if((int)$price_export['price_type_id']>0){
			foreach($sklad_details as $sd_key=>$sklad_detail){
				$sklad_details[$sd_key]['price_type']=(int)$price_export['price_type_id'];
				$sklad_details[$sd_key]['detail_markup_price']=0;
				$sklad_details[$sd_key]['default_markup']=0;
			}
			foreach($price_details as $pd_key=>$price_detail){
				$price_details[$pd_key]['price_type']=(int)$price_export['price_type_id'];
				$price_details[$pd_key]['detail_markup_price']=0;
				$price_details[$pd_key]['default_markup']=0;
			}
		}
		if(count((array)$sklad_details)>0) 
			$sklad_details_sale=Search::get_sale_price($sklad_details,1,"",array(),$db,0,(int)$price_export['discount_price_type_id']);
		if(count((array)$price_details)>0)
			$price_details_sale=Search::get_sale_price($price_details,1,"",array(),$db,0,(int)$price_export['discount_price_type_id']);
		if(empty($sklad_details)) $sklad_details=array();
		$names=array_keys((array)(reset($sklad_details)));
		if(count((array)$names)==0){
			$names=array_keys(reset($price_details));
		}
		$selected_names=array();
		if($price_export['selected_cols']){
			$selected_cols=json_decode($price_export['selected_cols'],true);
			foreach($selected_cols as $selcol_key=>$selcol_val){
				if($selcol_val['selected']==1) $selected_names[]=$selcol_val['name'];
			}
		}
		else {
			$selected_names=array("article","brand","name","count","sale_price");
			if(isset($request->show_price_name) && $request->show_price_name) {
				$selected_names[]="price_list_name";
				$selected_names[]="sklad_name";
			}
		}
		
		// сложим 2 массива заообно проверив попадают ли по цене price_from->price_to
		$ret['sklad_details']=array();
		$ret['price_details']=array();
		function processRow($row, $price_export, &$result_array, $db) {
			$row['name'] = str_replace(array("<", ">", "&", "="), array("", "", "", "-"), $row['name']);
			$row['category_id'] = $db->getOne("SELECT detail_group_id FROM detail_group_details WHERE detail_id = ?i and main_company_id = ?i", $row['detail_id'], $_SESSION['main_company']);
			$sale_price = (float)$row['sale_price'];
			$price_from = (float)$price_export['price_from'];
			$price_to = (float)$price_export['price_to'];
		
			if (($price_from > 0 && $sale_price >= $price_from) && ($price_to > 0 && $sale_price <= $price_to)) {
				$result_array[] = $row;
			} elseif ($price_to > 0 && $sale_price <= $price_to) {
				$result_array[] = $row;
			} elseif ($price_from > 0 && $sale_price >= $price_from) {
				$result_array[] = $row;
			} elseif ($price_from == 0 && $price_to == 0) {
				$result_array[] = $row;
			}
		}
		
		foreach ($sklad_details_sale as $row) {
			processRow($row, $price_export, $ret['sklad_details'], $db);
		}
		
		foreach ($price_details_sale as $row) {
			processRow($row, $price_export, $ret['price_details'], $db);
		}

		if((int)$price_export['originality']>0){
			switch($price_export['originality']){
				case 1:
					$temp_price=array();
					$temp_price_details=$ret['price_details'];
					foreach($ret['price_details'] as $price_det_key=>$price_det_val){
						if(!isset($temp_price[$price_det_val['article']])){
							$temp_price[$price_det_val['article']]['sale_price']=(float)$price_det_val['sale_price'];
							$temp_price[$price_det_val['article']]['index']=$price_det_key;
						}
						else {
							if($temp_price[$price_det_val['article']]['sale_price']>(float)$price_det_val['sale_price']){
								$temp_price[$price_det_val['article']]['sale_price']=(float)$price_det_val['sale_price'];
								$temp_price[$price_det_val['article']]['index']=$price_det_key;
							}
						}
					}
					$ret['price_details']=array();
					foreach($temp_price as $tp_article=>$tp_val){
						$ret['price_details'][]=$temp_price_details[$tp_val['index']];
					}
					break;
				case 2:
					$temp_price=array();
					$temp_price_details=$ret['price_details'];
					foreach($ret['price_details'] as $price_det_key=>$price_det_val){
						if(!isset($temp_price[$price_det_val['article']])){
							$temp_price[$price_det_val['article']]['sale_price']=(float)$price_det_val['sale_price'];
							$temp_price[$price_det_val['article']]['index']=$price_det_key;
						}
						else {
							if($temp_price[$price_det_val['article']]['sale_price']<(float)$price_det_val['sale_price']){
								$temp_price[$price_det_val['article']]['sale_price']=(float)$price_det_val['sale_price'];
								$temp_price[$price_det_val['article']]['index']=$price_det_key;
							}
						}
					}
					$ret['price_details']=array();
					foreach($temp_price as $tp_article=>$tp_val){
						$ret['price_details'][]=$temp_price_details[$tp_val['index']];
					}
					break;
				case 3:
					$temp_price=array();
					$temp_price_details=$ret['price_details'];
					foreach($ret['price_details'] as $price_det_key=>$price_det_val){
						if(!isset($temp_price[$price_det_val['article']])){
							$temp_price[$price_det_val['article']]['count']=(float)$price_det_val['count'];
							$temp_price[$price_det_val['article']]['index']=$price_det_key;
						}
						else {
							if($temp_price[$price_det_val['article']]['count']<(float)$price_det_val['count']){
								$temp_price[$price_det_val['article']]['count']=(float)$price_det_val['count'];
								$temp_price[$price_det_val['article']]['index']=$price_det_key;
							}
						}
					}
					$ret['price_details']=array();
					foreach($temp_price as $tp_article=>$tp_val){
						$ret['price_details'][]=$temp_price_details[$tp_val['index']];
					}
					break;
				default:
					
			}
		}
		$ret['names']=$names;
		$ret['selected_names']=$selected_names;
		if($price_export['csv_delimiter']=='') $price_export['csv_delimiter']='	';
		$ret['price_export']=$price_export;
		return $ret;
	}

	public static function get_export_file($request){
		$db = DB::getInstance();
		if(empty($request->price_export_id) || (int)$request->price_export_id <= 0){
			return array("status" => "err", "err" => "Не указан номер выгрузки");
		}
		$price_export = $db->getRow("select * from price_exports where id=?i and main_company_id=?i", (int)$request->price_export_id, $_SESSION['main_company']);
		if(!$price_export){
			return array("status" => "err", "err" => "Выгрузка не существует");
		}
		$ret = self::get_export_data($request);
	
		$cols=array(
			'article'=>'Артикул',
			'brand'=>'Бренд',
			'name'=>'Наименование',
			'count'=>'Количество (остаток)',
			'sale_price'=>'Цена продажи',
			'price_list_name'=>'Наименование прайс-листа',
			'sklad_name'=>'Наименование склада',
			'uuid'=>'Наш идентификатор',
			'location'=>'Местоположение'
		);

		// Формирование основного CSV
		$i = 0;
		$csv = "";
		if($ret['price_export']['show_field_names']==1){
			foreach($ret['selected_names'] as $nkey => $nval){
				if(in_array($nval, $ret['names']) || $nval=="uuid" || $nval=="sale_price"){    
					if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
					$csv .= str_replace('"', '\'', $cols[$nval]);
					$i++;
				}
			}
			$csv .= PHP_EOL;
		}
		
		
		
		foreach ($ret['sklad_details'] as $row) {
			switch((int)$ret['price_export']['number_format']){
				case 1: $row['sale_price']=number_format($row['sale_price'],2,'.',''); break;
				case 2: $row['sale_price']=number_format($row['sale_price'],2,',',''); break;
				case 3: $row['sale_price']=round($row['sale_price']); break;
			}
			$i = 0;
			if((float)$row['reserved_count'] > 0){
				$row['count'] = (float)$row['count'] - (float)$row['reserved_count'];
			}
			if($row['count'] > 0){
				/*foreach($row as $row_key => $row_val){ 
					if(in_array($row_key, $ret['selected_names'])){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= '"'.str_replace('"', '\'', $row_val).'"';
						$i++;
					}
				}*/
				foreach($ret['selected_names'] as $row_key => $row_val){ 
					if(isset($row[$row_val])){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= str_replace('"', '\'', $row[$row_val]);
						$i++;
					}
					if($row_val=="uuid"){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= '1-'.$row['sklad_id'].'-'.($row['detail_id']<0?'0'.$row['detail_id']:$row['detail_id']).'';
					}
				}
				
				$csv .= PHP_EOL; 
			}
		}
	
		foreach ($ret['price_details'] as $row) {
			switch((int)$ret['price_export']['number_format']){
				case 1: $row['sale_price']=number_format($row['sale_price'],2,'.',''); break;
				case 2: $row['sale_price']=number_format($row['sale_price'],2,',',''); break;
			}
			$i = 0;
			if(!isset($row['nacenka_exist']) || (int)$row['nacenka_exist'] == 1){
				/*foreach($row as $row_key => $row_val){ 
					if(in_array($row_key, $ret['selected_names'])){
						if($i > 0) $csv .= $price_export['csv_delimiter'];
						$csv .= '"'.str_replace('"', '\'', $row_val).'"';
						$i++;
					}
				}*/
				foreach($ret['selected_names'] as $row_key => $row_val){ 
					if(isset($row[$row_val])){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= str_replace('"', '\'', $row[$row_val]);
						$i++;
					}
					if($row_val=="uuid"){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= '2-'.$row['price_list_id'].'-'.($row['detail_id']<0?'0'.$row['detail_id']:$row['detail_id']).'';
					}
				}
				$csv .= PHP_EOL; 
			}
		}
	
		// Проверка на формат файла "Авито"
		if($request->file_type == "Авито"){
			$sklad_details_without_category = [];
			$price_details_without_category = [];
			$non_brand = [];

			// Сбор деталей без категории
			foreach ($ret['sklad_details'] as $row) {
				if(empty($row['category_id'])) {
					$sklad_details_without_category[] = $row;
				}
				if (!empty($row['brand']) && $row['brand'] != 'Unknown') {
					$non_brand[] = $row;
				}
			}
	
			foreach ($ret['price_details'] as $row) {
				if(empty($row['category_id'])) {
					$price_details_without_category[] = $row;
				}
				if (!empty($row['brand']) && $row['brand'] != 'Unknown') {
					$non_brand[] = $row;
				}
			}

			$unlinked_categories = [];

			foreach (array_merge($ret['sklad_details'], $ret['price_details']) as $row) {
				$category_id = $row['category_id'];
				
				if((int)$category_id != 0){
					// Проверка привязки категории к категории Авито
					$is_linked = $db->getOne("SELECT 1 FROM category_marketplaces_user WHERE category_id = ?i AND marketplace_id = ?i AND main_company_id = ?i", 
						(int)$category_id, 2, $_SESSION['main_company']);

					if (!$is_linked) {
						$unlinked_categories[] = $category_id;
					}
				}
			}
		
			$db->query("DELETE FROM unlinked_categories_avito_export WHERE user_id = ?i AND main_company_id = ?i AND price_export_id = ?i", $_SESSION['user_id'], $_SESSION['main_company'], $request->price_export_id);
		
			foreach ($unlinked_categories as $category_id) {
				$db->query("INSERT IGNORE INTO unlinked_categories_avito_export (category_id, user_id, main_company_id, price_export_id) VALUES (?i, ?i, ?i, ?i)", 
					(int)$category_id, 
					$_SESSION['user_id'], 
					$_SESSION['main_company'], 
					$request->price_export_id
				);
			}

			if (!empty($sklad_details_without_category) || !empty($price_details_without_category)) {
				$db->query("DELETE FROM user_details_without_category WHERE user_id = ?i AND main_company_id = ?i AND price_export_id = ?i", $_SESSION['user_id'], $_SESSION['main_company'], $request->price_export_id);

				if (!empty($sklad_details_without_category)) {
					foreach ($sklad_details_without_category as $row) {
						$db->query("INSERT INTO user_details_without_category (article, brand, name, detail_id, detail_type, user_id, main_company_id, price_export_id) VALUES (?s, ?s, ?s, ?s, 'sklad', ?i, ?i, ?i)", 
							$row['article'], 
							$row['brand'], 
							$row['name'], 
							$row['detail_id'], 
							(int)$_SESSION['user_id'],
							(int)$_SESSION['main_company'], 
							$request->price_export_id
						);
					}
				}
			
				if (!empty($price_details_without_category)) {
					foreach ($price_details_without_category as $row) {
						$db->query("INSERT INTO user_details_without_category (article, brand, name, detail_id, detail_type, user_id, main_company_id, price_export_id) VALUES (?s, ?s, ?s, ?s, 'price', ?i, ?i, ?i)", 
							$row['article'], 
							$row['brand'], 
							$row['name'], 
							$row['detail_id'], 
							(int)$_SESSION['user_id'],
							(int)$_SESSION['main_company'],
							$request->price_export_id
						);
					}
				}

				return array(
					"status" => "ok",
					"message" => "Прикрепите к деталям категорию!",
					"category" => true,
					"price_export_id" => (int)$request->price_export_id,
				);
			} 
			else if(!empty($unlinked_categories)){
				return array(
					"status" => "ok",
					"message" => "Привяжите категории к категориям авито!",
					"avitoCategory" => true,
					"price_export_id" => (int)$request->price_export_id,
				);
			}else {
				$message = "";
				if(!empty($non_brand)){
					$message = "Проверьте склад и выставите всем деталям бренд!";
				}
				else{
					$message = "XML файл сформирован!";
				}
				
				$file = base64_encode(self::generate_avito_xml($ret));

				return array(
					"status" => "ok",
					"message" => $message,
					"names" => $ret['names'],
					"file_type" => 'xml',
					"selected_names" => $ret['selected_names'],
					"file" => $file,
					"filename" => $ret['price_export']['filename']
				);
			}
		}
	
		if($request->file_type == "yandex"){
			$sklad_details_without_category = [];
			$price_details_without_category = [];
			$non_brand = [];

			// Сбор деталей без категории
			foreach ($ret['sklad_details'] as $row) {
				if(empty($row['category_id'])) {
					$sklad_details_without_category[] = $row;
				}
				if (!empty($row['brand']) && $row['brand'] != 'Unknown') {
					$non_brand[] = $row;
				}
			}
	
			foreach ($ret['price_details'] as $row) {
				if(empty($row['category_id'])) {
					$price_details_without_category[] = $row;
				}
				if (!empty($row['brand']) && $row['brand'] != 'Unknown') {
					$non_brand[] = $row;
				}
			}

				$message = "XML файл сформирован!";
				$file = base64_encode(self::generate_yandex_xml($ret));

				return array(
					"status" => "ok",
					"message" => $message,
					"names" => $ret['names'],
					"file_type" => 'xml',
					"selected_names" => $ret['selected_names'],
					"file" => $file,
					"filename" => "yandex.xml"
				);
			
		}

		if($request->file_type == "xlsx" || $request->file_type == "xls"){
			file_put_contents("/tmp/price_export_" . (int)$request->price_export_id . ".csv", $csv);
			$spreadsheet = new Spreadsheet();
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
			$reader->setDelimiter($ret['price_export']['csv_delimiter']);
			$reader->setEnclosure('"');
			$reader->setSheetIndex(0);
	
			$spreadsheet = $reader->load("/tmp/price_export_" . (int)$request->price_export_id . ".csv");
			if($request->file_type == "xlsx"){
				$writer = new Xlsx($spreadsheet);
				$writer->save("/tmp/price_export_" . (int)$request->price_export_id . ".xlsx");
				$ext = "xlsx";
			}
			elseif ($request->file_type == "xls") {
				$writer = new Xls($spreadsheet);
				$writer->save("/tmp/price_export_" . (int)$request->price_export_id . ".xls");
				$ext = "xls";
			}
			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file = base64_encode(file_get_contents("/tmp/price_export_" . (int)$request->price_export_id . "." . $ext));
			unlink("/tmp/price_export_" . (int)$request->price_export_id . "." . $ext);
			unlink("/tmp/price_export_" . (int)$request->price_export_id . ".csv");
			return array("status" => "ok", "msg" => "", "file" => $file, "filename" => $ret['price_export']['filename'], "disc_price_type_id" => (int)$ret['price_export']['discount_price_type_id']);
		}
	
		if($request->file_type == "csv"){
			$file = base64_encode(mb_convert_encoding($csv, "WINDOWS-1251", "UTF-8"));
			return array("status" => "ok", "names" => $ret['names'], "selected_names" => $ret['selected_names'], "file" => $file, "filename" => $ret['price_export']['filename']);
		}
	
		return array("status" => "err", "err" => "Формат файла не поддерживается");
	}
	

	public static function send_export_file_to_email($request){
		$db = DB::getInstance();
		if(empty($request->price_export_id) || (int)$request->price_export_id<=0){
			return array("status"=>"err","err"=>"Не указан номер выгрузки");
		}
		$price_export=$db->getRow("select * from price_exports where id=?i and main_company_id=?i",(int)$request->price_export_id,$_SESSION['main_company']);
		if(!$price_export){
			return array("status"=>"err","err"=>"Выгрузка не существует");
		}
		if($price_export['send_from_my_email']==1 && $price_export['email_config_id']>0){
			$price_export_email_config=$db->getRow("select * from email_configs where id=?i and deleted=0",$price_export['email_config_id']);
		}
		$ret=self::get_export_data($request);
		$csv="";
		if($ret['price_export']['show_field_names']==1){
			foreach($ret['selected_names'] as $nkey => $nval){
				if(in_array($nval, $ret['names']) || $nval=="uuid"){    
					if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
					$csv .= str_replace('"', '\'', $nval);
					$i++;
				}
			}
			$csv .= PHP_EOL;
		}
	
		foreach ($ret['sklad_details'] as $row) {
			switch((int)$ret['price_export']['number_format']){
				case 1: $row['sale_price']=number_format($row['sale_price'],2,'.',''); break;
				case 2: $row['sale_price']=number_format($row['sale_price'],2,',',''); break;
				case 3: $row['sale_price']=round($row['sale_price']); break;
			}
			$i = 0;
			if((float)$row['reserved_count'] > 0){
				$row['count'] = (float)$row['count'] - (float)$row['reserved_count'];
			}
			if($row['count'] > 0){
				/*foreach($row as $row_key => $row_val){ 
					if(in_array($row_key, $ret['selected_names'])){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= '"'.str_replace('"', '\'', $row_val).'"';
						$i++;
					}
				}*/
				foreach($ret['selected_names'] as $row_key => $row_val){ 
					if(isset($row[$row_val])){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= str_replace('"', '\'', $row[$row_val]);
						$i++;
					}
					if($row_val=="uuid"){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= '1-'.$row['sklad_id'].'-'.($row['detail_id']<0?'0'.$row['detail_id']:$row['detail_id']).'';
					}
				}
				
				$csv .= PHP_EOL; 
			}
		}
	
		foreach ($ret['price_details'] as $row) {
			switch((int)$ret['price_export']['number_format']){
				case 1: $row['sale_price']=number_format($row['sale_price'],2,'.',''); break;
				case 2: $row['sale_price']=number_format($row['sale_price'],2,',',''); break;
			}
			$i = 0;
			if(!isset($row['nacenka_exist']) || (int)$row['nacenka_exist'] == 1){
				/*foreach($row as $row_key => $row_val){ 
					if(in_array($row_key, $ret['selected_names'])){
						if($i > 0) $csv .= $price_export['csv_delimiter'];
						$csv .= '"'.str_replace('"', '\'', $row_val).'"';
						$i++;
					}
				}*/
				foreach($ret['selected_names'] as $row_key => $row_val){ 
					if(isset($row[$row_val])){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= str_replace('"', '\'', $row[$row_val]);
						$i++;
					}
					if($row_val=="uuid"){
						if($i > 0) $csv .= $ret['price_export']['csv_delimiter'];
						$csv .= '2-'.$row['price_list_id'].'-'.($row['detail_id']<0?'0'.$row['detail_id']:$row['detail_id']).'';
					}
				}
				$csv .= PHP_EOL; 
			}
		}
		if($ret['price_export']['format']==1){
			file_put_contents("/tmp/price_export_".(int)$request->price_export_id.".csv",mb_convert_encoding($csv,"WINDOWS-1251","UTF-8"));
			if(!empty($price_export_email_config)){
				$ret=Notify::mail_from_config($price_export_email_config,"Прайс ","Прайс ".$ret['price_export']['filename'].".csv",$ret['price_export']['send_to_email'],"/tmp/price_export_".(int)$request->price_export_id.".csv",$ret['price_export']['filename'].".csv");
			}
			else {
				$ret=Notify::mail("Прайс ","Прайс ".$ret['price_export']['filename'].".csv",$ret['price_export']['send_to_email'],"/tmp/price_export_".(int)$request->price_export_id.".csv",$ret['price_export']['filename'].".csv");
			}
			unlink("/tmp/price_export_".(int)$request->price_export_id.".csv");
			if($ret){
				return array("status"=>"ok","msg"=>"");
			}
			else {
				return array("status"=>"err","err"=>"Не удалось отправить");
			}
		}
		if($ret['price_export']['format']==2){
			file_put_contents("/tmp/price_export_".(int)$request->price_export_id.".csv",$csv);
			require 'vendor/autoload.php';

			//use PhpOffice\PhpSpreadsheet\Spreadsheet;
			//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

			$spreadsheet = new Spreadsheet();
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

			/* Set CSV parsing options */

			$reader->setDelimiter($ret['price_export']['csv_delimiter']);
			$reader->setEnclosure('"');
			$reader->setSheetIndex(0);

			/* Load a CSV file and save as a XLS */

			$spreadsheet = $reader->load("/tmp/price_export_".(int)$request->price_export_id.".csv");
			$writer = new Xlsx($spreadsheet);
			$writer->save("/tmp/price_export_".(int)$request->price_export_id.".xlsx");
			$ext="xlsx";
			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			if(!empty($price_export_email_config)){
				$ret=Notify::mail_from_config($price_export_email_config,"Прайс ","Прайс ".$ret['price_export']['filename'].".xlsx",$ret['price_export']['send_to_email'],"/tmp/price_export_".(int)$request->price_export_id.".xlsx",$ret['price_export']['filename'].".xlsx");
			}
			else{
				$ret=Notify::mail("Прайс ","Прайс ".$ret['price_export']['filename'].".xlsx",$ret['price_export']['send_to_email'],"/tmp/price_export_".(int)$request->price_export_id.".xlsx",$ret['price_export']['filename'].".xlsx");
			}
			//$file=base64_encode(file_get_contents("/tmp/price_export_".(int)$request->price_export_id.".".$ext));
			unlink("/tmp/price_export_".(int)$request->price_export_id.".".$ext);
			unlink("/tmp/price_export_".(int)$request->price_export_id.".csv");
			if($ret){
				return array("status"=>"ok","msg"=>"");
			}
			else {
				return array("status"=>"err","err"=>"Не удалось отправить");
			}
		}
		if ($ret['price_export']['format'] == 3) { // Формат XML (Авито)
			$xml_content = self::generate_avito_xml($ret);
			$file_path = "/tmp/price_export_" . (int)$request->price_export_id . ".xml";
			file_put_contents($file_path, $xml_content);
			$file = $file_path;
			$filename = $ret['price_export']['filename'] . ".xml";
			if(!empty($price_export_email_config)){
				$ret=Notify::mail_from_config($price_export_email_config,"Прайс ","Прайс ".$filename.".xlsx",$ret['price_export']['send_to_email'],$file_path,$filename);
			}
			else {	
				$ret=Notify::mail("Прайс ","Прайс ".$filename.".xlsx",$ret['price_export']['send_to_email'],$file_path,$filename);
			}
			//$file=base64_encode(file_get_contents("/tmp/price_export_".(int)$request->price_export_id.".".$ext));
			unlink($file_path);
			if($ret){
				return array("status"=>"ok","msg"=>"");
			}
			else {
				return array("status"=>"err","err"=>"Не удалось отправить");
			}
		}
		return array("status"=>"err","err"=>"формат файла не поддерживается");
	}

	private static function get_detail_photos($detail_id, $company_id) {
		$db_libr = DB::getInstance("libr");
		$db = DB::getInstance();
	
		if (!isset($detail_id)) {
			return array("status" => "error", "msg" => "detail_id not provided", "data" => array());
		}
	
		$detail_id = (int)$detail_id;
		$company_id = $company_id;
	
		$libr_images = $db_libr->getAll("SELECT image_url FROM detail_images WHERE detail_id = ?i", $detail_id);
	
		$user_images = $db->getCol("SELECT filename FROM detail_photos WHERE detail_id = ?i AND company_id = ?i", $detail_id, $company_id);
	
		foreach ($libr_images as $img) {
			$filename = $img['image_url'];
	
			if (!in_array($filename, $user_images)) {
				$db->query(
					"INSERT INTO detail_photos (detail_id, company_id, filename, is_sort1, is_public) VALUES (?i, ?i, ?s, 1, 1)",
					$detail_id, $company_id, $filename
				);
			}
		}
	
		return true;
	}

	public static function generate_avito_xml($request){
		$db1 = DB::getInstance("libr");
		$db = new SafeMySQL(Config::get_section('mysql-', true));

		$items = $request;
		$ads = ''; 
		$manager = $db->getRow('SELECT name, mphone FROM users WHERE main_company_id = ?i and is_main = 1', (int)$items['price_export']['main_company_id']);
		$detailsArray = isset($items['price_details']) ? array_merge($items['sklad_details'], $items['price_details']) : $items['sklad_details'];

		function getCategoryHierarchy($db, $categoryId) {
			$hierarchy = [];
			$currentCategoryId = $categoryId;
			while ($currentCategoryId) {
				$category = $db->getRow("SELECT id, name, param, parentId FROM avito_categorys WHERE id = ?i", $currentCategoryId);
				if ($category) {
					$hierarchy[] = $category;
					$currentCategoryId = $category['parentId'];
				} else {
					break;
				}
			}
			return array_reverse($hierarchy);
		}

   		foreach ($detailsArray as $row) {
			// Пропускаем если нет бренда или категории
			if(empty($row['brand']) || $row['brand'] == "Unknown" || empty($row['category_id'])){
				continue;
			}

			$main_company = $db->getOne("select company_id from user_companys where main_company_id=0 and user_id=?i and deleted=0", $row['user_id']);

			self::get_detail_photos($row['detail_id'], $main_company);

			// Получаем фотографии детали
			$detail_images = $db->getAll("SELECT filename FROM detail_photos WHERE detail_id = ?i AND company_id = ?i and is_active = 1 and is_deleted = 0", $row['detail_id'], $main_company);
			
			// Если включена настройка "только с фото" и у детали нет фото - пропускаем
			if(isset($items['price_export']['photo_on']) && $items['price_export']['photo_on'] == 1 && empty($detail_images)) {
				continue;
			}

			$ads .= '<Ad>' . "\r\n";
			$ads .= '<CompanyId>' . $main_company . '</CompanyId>' . "\r\n";

			$encoded_Id = (isset($row['price_list_id']) ? "2_" : "1_") . (isset($row['price_list_id']) ? $row['price_list_id'] : $row['sklad_id']). "_" . $row['detail_id'];
			$ads .= '<Id>' . $encoded_Id . '</Id>' . "\r\n";
			$ads .= '<AdStatus>Free</AdStatus>' . "\r\n";
			$ads .= '<ContactMethod>По телефону и в сообщениях</ContactMethod>' . "\r\n";
			$ads .= '<ManagerName>'. (!empty($row['manager_name']) ? $row['manager_name'] : $manager['name']) .'</ManagerName>' . "\r\n";
			$ads .= '<ContactPhone>'. (!empty($row['manager_phone']) ? $row['manager_phone'] : $manager['mphone']) .'</ContactPhone>' . "\r\n";
			$ads .= '<Address>'. $items['price_export']['address'] .'</Address>' . "\r\n";    
			$ads .= '<Category>Запчасти и аксессуары</Category>' . "\r\n";    
			
			$category = $db->getOne("SELECT marketplace_category_id FROM category_marketplaces_user WHERE category_id = ?i and main_company_id = ?i", $row['category_id'], $main_company);

			$categories = getCategoryHierarchy($db1, $category);

			if (!empty($categories)) {
				foreach ($categories as $cat) {
					$ads .= '<' . $cat['param'] . '>' . htmlspecialchars($cat['name'], ENT_XML1, 'UTF-8') . '</' . $cat['param'] . '>' . "\r\n";
				}
			}
			
			$ads .= '<AdType>Товар приобретен на продажу</AdType>' . "\r\n";    
			$ads .= '<Title>' . $row['name'] . '</Title>' . "\r\n";
			$ads .= '<Description>' .  strip_tags($items['price_export']['description']) . '</Description>' . "\r\n"; 
			$ads .= '<Price>' . $row['sale_price'] . '</Price>' . "\r\n";
			
			// Добавляем блок с фотографиями только если они есть
			// if(!empty($detail_images)) {
				$ads .= '<Images>'. "\r\n";
				foreach ($detail_images as $img) {
					$parts = explode("/", $img['filename']);
					$ads .= '<Image url="https://crossdata.pro/api/get_image?image='. trim($parts[1]) . '"/>'. "\r\n";
				}
				$ads .= '</Images>'. "\r\n";
			// }
			
			$ads .= '<Condition>Новое</Condition>' . "\r\n";
			$ads .= '<OEM>'. $row['article'] .'</OEM>' . "\r\n";
			$ads .= '<Brand>'. $row['brand'] .'</Brand>' . "\r\n";
			$ads .= '<Availability>В наличии</Availability>' . "\r\n";
			$ads .= '</Ad>' . "\r\n";    
		}
		
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<Ads formatVersion="3" target="Avito.ru">
			' . $ads . '
		</Ads>';

		return $xml;
	}

	public static function generate_yandex_xml($request){
		$db1 = DB::getInstance("libr");
		$db = new SafeMySQL(Config::get_section('mysql-', true));

		$items = $request;
		$return_xml=new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><yml_catalog/>');
		$shop=$return_xml->addChild("shop");
		$categories=$shop->addChild("categories");
		$offers=$shop->addChild("offers");
		// $sklad=$db->getRow("select * from sklad where id=?i",$_SESSION['my_sklad_id']);
		$detailsArray = isset($items['price_details']) ? array_merge($items['sklad_details'], $items['price_details']) : $items['sklad_details'];
		$categorys_arr=array();
		foreach ($detailsArray as $row) {
			if(empty($row['category_id']) || $row['brand'] == "Unknown"){
				continue;
			}
			$offer=$offers->addChild("offer");
			$offer->addAttribute("id",$row['detail_id']);
			$name=$offer->addChild("name",$row['name']);
			$vendor=$offer->addChild("vendor",$row['brand']);
			$model=$offer->addChild("model",$row['article']);
			$price=$offer->addChild("price",$row['sale_price']);
			$currencyId=$offer->addChild("currencyId","RUB");
			$categoryId=$offer->addChild("categoryId",$row['category_id']);
			$category_name=$db->getOne("select group_name from detail_group where id=?i",$row['category_id']);
			if(!in_array($row['category_id'],$categorys_arr)) {
				$categorys_arr[]=$row['category_id'];
				$category=$categories->addChild("category",$category_name);
				$category->addAttribute("id",$row['category_id']);
			}
			
			
		}
		
		return $return_xml->asXML();
	}

	/*public static function generate_yandex_xml($request){
		$db1 = DB::getInstance("libr");
		$db = new SafeMySQL(Config::get_section('mysql-', true));

		$items = $request;
		$ads = ''; 
		// $sklad=$db->getRow("select * from sklad where id=?i",$_SESSION['my_sklad_id']);
		$manager = $db->getRow('SELECT name, mphone FROM users WHERE main_company_id = ?i and is_main = 1', (int)$items['price_export']['main_company_id']);
		$detailsArray = isset($items['price_details']) ? array_merge($items['sklad_details'], $items['price_details']) : $items['sklad_details'];

		function getCategoryHierarchy($db, $categoryId) {
			$hierarchy = [];
			$currentCategoryId = $categoryId;
			while ($currentCategoryId) {
				$category = $db->getRow("SELECT id, name, param, parentId FROM avito_categorys WHERE id = ?i", $currentCategoryId);
				if ($category) {
					$hierarchy[] = $category;
					$currentCategoryId = $category['parentId'];
				} else {
					break;
				}
			}
			return array_reverse($hierarchy); // Возвращаем в порядке от корня до текущей категории
		}

		foreach ($detailsArray as $row) {
			if(empty($row['brand']) || $row['brand'] == "Unknown"){
				continue;
			}

			$ads .= '<Ad>' . "\r\n";
			$encoded_Id = base64_encode('detail_id=' . $row['detail_id'] . (isset($row['price_list_id']) ? '&price_list_id=' . $row['price_list_id'] : '&sklad_id=' . $row['sklad_id']));
			$ads .= '<Id>' . $encoded_Id . '</Id>' . "\r\n";
			$ads .= '<AdStatus>Free</AdStatus>' . "\r\n";
			$ads .= '<ContactMethod>По телефону и в сообщениях</ContactMethod>' . "\r\n";
			$ads .= '<ManagerName>'. $manager['name'] .'</ManagerName>' . "\r\n";
			$ads .= '<ContactPhone>'. $manager['mphone'] .'</ContactPhone>' . "\r\n";
			// Полный адрес объекта — строка до 256 символов
			//$skald_address = $db->getOne('SELECT address FROM sklad WHERE id = ?i',$row['sklad_id']);
			$ads .= '<Address>'. $items['price_export']['address'] .'</Address>' . "\r\n";	
			
			// Категория товара — одно из значений списка
			$ads .= '<Category>Запчасти и аксессуары</Category>' . "\r\n";	
			
			$category = $db->getOne("SELECT marketplace_category_id FROM category_marketplaces_user WHERE category_id = ?i and main_company_id = ?i", $row['category_id'], $_SESSION['main_company']);

			$categories = getCategoryHierarchy($db1, $category);

			if (!empty($categories)) {
				foreach ($categories as $cat) { // Изменение имени переменной
					$ads .= '<' . $cat['param'] . '>' . htmlspecialchars($cat['name'], ENT_XML1, 'UTF-8') . '</' . $cat['param'] . '>' . "\r\n";
				}
			} else {
				// Обработка случая, если категории не найдены
				$ads .= '<Error>No categories found</Error>' . "\r\n";
			}
			
			$ads .= '<AdType>Товар приобретен на продажу</AdType>' . "\r\n";	
					
			// Название объявления — строка до 50 символов
			$ads .= '<Title>' . $row['name'] . '</Title>' . "\r\n";
			
			// Текстовое описание объявления — строка не более 7500 символов
			$ads .= '<Description>' .  strip_tags($items['price_export']['description']) . '</Description>' . "\r\n"; 
		
			// Цена в рублях — целое число
			$ads .= '<Price>' . $row['sale_price'] . '</Price>' . "\r\n";
			
			// Состояние вещи в категории
			$ads .= '<Condition>Новое</Condition>' . "\r\n";
			$ads .= '<OEM>'. $row['article'] .'</OEM>' . "\r\n";
			$ads .= '<Brand>'. $row['brand'] .'</Brand>' . "\r\n";
			$ads .= '<Availability>В наличии</Availability>' . "\r\n";
			
			// Фотографии 
			// $ads .= '<Images>' . "\r\n";
			// $ads .= '<Image url="" />' . "\r\n";
			// $ads .= '</Images>' . "\r\n";
				
			$ads .= '</Ad>' . "\r\n";	
		}
		
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<Ads formatVersion="3" target="Avito.ru">
			' . $ads . '
		</Ads>';

		return $xml;
	}*/

	public static function get_details_from_without_category($request) {
		$db = DB::getInstance();
	
		$parsed = "";
		$page_parsed = "";
		
		// Фильтр по article
		if (!empty($request->search_article)) {
			$parsed .= $db->parse(" AND ud.article LIKE ?s", '%' . $request->search_article . '%');
		}
	
		// Фильтр по name
		if (!empty($request->search_name)) {
			$parsed .= $db->parse(" AND ud.name LIKE ?s", "%" . $request->search_name . "%");
		}
	
		if (!empty($request->price_export_id)) {
			$parsed .= $db->parse(" AND ud.price_export_id = ?i", $request->price_export_id);
		}
		// if(!isset($request->show_zero) || !$request->show_zero){
		// 	$parsed.=$db->parse(" and pd.count>0");
		// }

		$page_size = isset($request->page_size) ? $request->page_size : 20;
		
		$detail_group_details_count = $db->getOne("SELECT COUNT(DISTINCT ud.detail_id)
			FROM user_details_without_category ud
			LEFT JOIN sklad_details sd ON ud.detail_id = sd.detail_id AND ud.detail_type = 'sklad'
			LEFT JOIN price_list_details pd ON ud.detail_id = pd.detail_id AND ud.detail_type = 'price'
			WHERE ud.user_id = ?i AND ud.main_company_id = ?i ?p", 
			$_SESSION['user_id'], 
			$_SESSION['main_company'], 
			$parsed);
	
		$pages = ceil($detail_group_details_count / $page_size);
		
		if (isset($request->selected_page) && (int)$request->selected_page <= (int)$pages) {
			$page_parsed .= $db->parse(" LIMIT ?i, ?i", $page_size * ((int)$request->selected_page - 1), $page_size);
		} else {
			$page_parsed .= " LIMIT 0, " . $page_size;
		}
	
		if (empty($request->selected_page)) {
			$request->selected_page = 1;
		}
	
		// Получение данных из user_details_without_category с уникальными detail_id
		$sklad_details = $db->getAll("SELECT distinct(ud.detail_id), ud.article, ud.name, ud.brand
			FROM user_details_without_category ud
			WHERE ud.user_id = ?i AND ud.main_company_id = ?i ?p
			?p;", 
		$_SESSION['user_id'], 
		$_SESSION['main_company'], 
		$parsed, $page_parsed);

		// print_r($db->getStats());
	
		if (count((array)$sklad_details) > 0) {
			return array(
				"status" => "ok",
				"detail_group_details" => $sklad_details,
				"msg" => "",
				"price_export_id" => $request->price_export_id,
				"detail_group_pages" => $pages,
				"details_count" => (int)$detail_group_details_count,
				"selected_page" => (int)$request->selected_page,
				"show_zero" => (int)$request->show_zero
			);
		} else {
			return array(
				"status" => "ok",
				"detail_group_details" => array(),
				"msg" => "",
				"price_export_id" => $request->price_export_id,
				"detail_group_pages" => 1,
				"details_count" => 0,
				"selected_page" => 1,
				"show_zero" => (int)$request->show_zero
			);
		}
	}
	
}



?>
