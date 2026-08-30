<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\PriceList;

//require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class PriceLists extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_price_list($request) {
            $db = DB::getInstance();
			if (isset($request->price_list_id)) $price_list_id=(int)$request->price_list_id;
			if (isset($price_list_id) && $price_list_id>0) {
			$price_list=new PriceList($price_list_id);
			}
			else 
			$price_list=new PriceList();
			if (isset($request->company_id) && (int)$request->company_id>0) {
			$companys=$db->getCol("select company_id from user_companys where main_company_id=?i and company_id=?i and deleted=0",$_SESSION['main_company'],(int)$request->company_id);
			if ($companys && in_array($request->company_id,$companys))
				$price_list->company_id=(int)$request->company_id;
			else {
				return self::_error_arr("Неправильно выбран поставщик, сначала добавьте поставщика");
			}
			}
			else return self::_error_arr("Не выбран поставщик, сначала добавьте поставщика");
			$price_list->main_company=$_SESSION['main_company'];
			if (isset($request->default_markup)) {
			$price_list->default_markup=$request->default_markup;
			if((int)$request->default_markup>0){
				$sql="update price_list_details set default_markup=?i where price_list_id=?i";
				$res=$db->query($sql,(int)$price_list->default_markup,$price_list->id);
			}
			}
			if (isset($request->timeplus)){
				if(isset($price_list_id) && $price_list->timeplus!=$request->timeplus){
					$db->query("update price_list_details set time=time+?i where price_list_id=?i",(int)$request->timeplus,$price_list_id);
			}
			$price_list->timeplus=(int)$request->timeplus;
			}
			if (isset($request->name)) $price_list->name=$request->name;
			if (isset($request->price_type)) {
				$price_type_type=$db->getOne("select type from dict_price_type where id=?i",$request->price_type);
				if((int)$price_type_type==4){
					// Посколько добавляем дифференцированную наценку необходимо обнулить default_markup
					$db->query("update price_list_details set default_markup=0 where price_list_id=?i",$price_list->id);
				}
				$price_list->price_type=$request->price_type;
			}
			if (isset($request->filename)) $price_list->filename=$request->filename;
			if (isset($request->price_get_type)) $price_list->price_get_type=$request->price_get_type;
			if (isset($request->email_config_id)) $price_list->email_config_id=(int)$request->email_config_id;
			if (isset($request->default_brand)) $price_list->default_brand=$request->default_brand;
			if (isset($request->get_url)) $price_list->get_url=$request->get_url;
			if (isset($request->filename_part)) $price_list->filename_part=$request->filename_part;
			if (isset($request->file_delimiter)) $price_list->file_delimiter=$request->file_delimiter;
			if (isset($request->status)) $price_list->status=$request->status;
			if (isset($request->currency)) $price_list->currency=$request->currency;
			if (isset($request->city_id)) $price_list->city_id=(int)$request->city_id;
			if (isset($request->city_name)) $price_list->city_name=$request->city_name;
			if (isset($request->update_email_to)) $price_list->update_email_to=$request->update_email_to;
			if (isset($request->update_email_from)) $price_list->update_email_from=$request->update_email_from;
			if (isset($request->update_email_subj)) $price_list->update_email_subj=$request->update_email_subj;
			if (isset($request->send_zakaz_to_email)) $price_list->send_zakaz_to_email=$request->send_zakaz_to_email;
			//if (isset($request->default_time)) $price_list->default_time=$request->default_time;
			$price_list->user_id=$_SESSION['user_id'];
			$err=$price_list->save();
			switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: if (isset($request->price_list_id) && (int)$request->price_list_id>0){
							$status="ok"; $msg="Данные успешно изменены";
						}
						else {
							$status="ok"; $msg="Новый прайслист добавлен";
						}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"price_list_id"=>$price_list->id);
        }

	public static function get_price_list($request) {
	    $db = DB::getInstance();
	    if (isset($request->price_list_id) && (int)$request->price_list_id>0) $price_list_id=(int)$request->price_list_id;
	    else return self::_error_arr("Не указан прайслист");
	    $sql="select * from price_list where id=?i and main_company=?i and deleted=0";
	    $res=$db->getRow($sql,$price_list_id,$_SESSION['main_company']);
	    if ($res['id']>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_list']=$res;
		$ret['price_list']['company_name']=$db->getOne("select name from company where id=?i",$res['company_id']);
		if($res['currency']=="1") $ret['price_list']['currency_name']="Российский рубль";
		else $ret['price_list']['currency_name']=$db->getOne("select Name from currency_kurs where NumCode=?i",$res['currency']);
		if ($res['status']==0) {$ret['price_list']['status_name']="Неактивен";}
		if ($res['status']==1) {$ret['price_list']['status_name']="Активен";}
		$ret['msg']="";
	    }
	    $pr_types=$db->getAll("select * from dict_price_type where (type=2 or type=4) and main_company=?i and deleted=0",$_SESSION['main_company']);
	    $ret['price_types']=$pr_types;
		$ret['email_configs']=$db->getAll("select * from email_configs where main_company_id=?i and deleted=0 and tested=1",$_SESSION['main_company']);
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_price_lists($request) {
	    $db = DB::getInstance();
	    $sql="select pl.*,c.name as company_name from price_list pl left join company c on (c.id=pl.company_id) where main_company=?i and deleted=0";
	    $res=$db->getAll($sql,$_SESSION['company_id']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_lists']=$res;
		$ret['price_det_pos']=array();
		//$db->getInd("price_list_id","SELECT price_list_id,COUNT(detail_id) AS positions,SUM(COUNT) AS pos_count FROM price_list_details where price_list_id in (?a) GROUP BY price_list_id",array_column($res,"id"));
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['price_lists']=array();
		$ret['msg']="";
		$ret['price_det_pos']=array();
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_price_list($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']>2) {
			return self::_error_arr("У Вас нет прав для удаления ");
	    }
	    if (isset($request->price_list_id)) {$price_list_id=(int)$request->price_list_id;}
	    if (isset($price_list_id) && $price_list_id>0){
			$res2=$db->query("delete from price_list where id=?i and main_company in (select company_id from user_companys where main_company_id=0 and user_id=?i and deleted=0)",$price_list_id,$_SESSION['user_id']);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$res3=$db->query("delete from price_list_details where price_list_id=?i",$price_list_id);
				$res4=$db->query("delete from price_list_cron where price_list_id=?i",$price_list_id);
				$ret['status']="ok";
				$ret['msg']="Прайслист успешно удален ";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить Прайслист";
			}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_price_list_csv($request){
		$db = DB::getInstance(); 
		if(!empty($request->add_markup) && $request->add_markup) $add_markup=true; else $add_markup=false;
		if(!empty($request->use_reserv) && $request->use_reserv) $use_reserv=true; else $use_reserv=false;
		if((int)$request->price_list_id>0){
			$pre_price_details=$db->getAll("select pld.*,pl.price_type from price_list_details pld 
			left join price_list pl on (pl.id=pld.price_list_id)
			where pld.price_list_id in (select id from price_list where main_company=?i) and pld.price_list_id=?i and pld.count>0",$_SESSION['main_company'],$request->price_list_id);
			if($add_markup) $pre_price_details=Search::get_sale_price($pre_price_details,0,"",array(),$db,$use_reserv);
			$price_details=array();
			foreach($pre_price_details as $pr_det_key=>$price_detail){
				$price_details[$pr_det_key]['article']=$price_detail['article'];
				$price_details[$pr_det_key]['brand']=$price_detail['brand'];
				$price_details[$pr_det_key]['name']=preg_replace("/^=/","",$price_detail['name']);
				$price_details[$pr_det_key]['count']=$price_detail['count'];
				if($add_markup) $price_details[$pr_det_key]['price']=$price_detail['sale_price'];
				else $price_details[$pr_det_key]['price']=$price_detail['price'];
			}
			$csv = implode(",", array_keys(reset($price_details))) . PHP_EOL;
			foreach ($price_details as $row) {
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
			unlink("/tmp/export_sklad_".$request->price_list_id.".csv");
			
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}
		else {
			return self::_error_arr("Не укзан прайс-лист для экпорта");
		}
	}

	public static function get_price_list_xls($request){
		$db = DB::getInstance();
		if(!empty($request->add_markup) && $request->add_markup) $add_markup=true; else $add_markup=false;
		if(!empty($request->use_reserv) && $request->use_reserv) $use_reserv=true; else $use_reserv=false;
		if((int)$request->price_list_id>0){
			$pre_price_details=$db->getAll("select pld.*,pl.price_type from price_list_details pld 
			left join price_list pl on (pl.id=pld.price_list_id)
			where pld.price_list_id in (select id from price_list where main_company=?i) and pld.price_list_id=?i and pld.count>0",$_SESSION['main_company'],$request->price_list_id);
			if($add_markup) $pre_price_details=Search::get_sale_price($pre_price_details,0,"",array(),$db,$use_reserv);
			$price_details=array();
			foreach($pre_price_details as $pr_det_key=>$price_detail){
				$price_details[$pr_det_key]['article']=$price_detail['article'];
				$price_details[$pr_det_key]['brand']=$price_detail['brand'];
				$price_details[$pr_det_key]['name']=preg_replace("/^=/","",$price_detail['name']);
				$price_details[$pr_det_key]['count']=$price_detail['count'];
				if($add_markup) $price_details[$pr_det_key]['price']=$price_detail['sale_price'];
				else $price_details[$pr_det_key]['price']=$price_detail['price'];
			}
			$csv = implode(",", array_keys(reset($price_details))) . PHP_EOL;
			foreach ($price_details as $row) {
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
			file_put_contents("/tmp/export_price_".$request->price_list_id.".csv",$csv);
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

			$spreadsheet = $reader->load("/tmp/export_price_".$request->price_list_id.".csv");
			$writer = new Xlsx($spreadsheet);
			$writer->save("/tmp/export_price_".$request->price_list_id.".xlsx");

			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/export_price_".$request->price_list_id.".xlsx"));
			unlink("/tmp/export_price_".$request->price_list_id.".xlsx");
			unlink("/tmp/export_price_".$request->price_list_id.".csv");
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}
		else {
			return self::_error_arr("Не укзан прайс-лист для экпорта");
		}
	}

	public static function get_price_list_cron($request){
		$db = DB::getInstance();
		if(empty($request->price_list_id) || (int)$request->price_list_id<=0){
			return array("status"=>"err","err"=>"Не указан номер прайса");
		}
		$price_list_cron=$db->getRow("select hours,days,months,years from price_list_cron where price_list_id=?i",(int)$request->price_list_id);
		if(!$price_list_cron){
			$db->query("insert into price_list_cron (price_list_id,create_date,user_id) values (?i,?s,?i)",(int)$request->price_list_id,date("Y-m-d H;i:s"),$_SESSION['user_id']);
			$price_list_cron=$db->getRow("select hours,days,months,years from price_list_cron where price_list_id=?i",(int)$request->price_list_id);
			//return array("status"=>"err","err"=>"Выгрузка не существует");
		}
		$price_list_cron['hours']=explode(",",$price_list_cron['hours']);
		$price_list_cron['days']=explode(",",$price_list_cron['days']);
		$price_list_cron['months']=explode(",",$price_list_cron['months']);
		return array("status"=>"ok","price_list_cron"=>$price_list_cron);
	}

	public static function save_price_list_cron($request){
		$db = DB::getInstance();
		if(empty($request->price_list_id) || (int)$request->price_list_id<=0){
			return array("status"=>"err","err"=>"Не указан номер выгрузки");
		}
		/*$price_list_cron=$db->getRow("select hours,days,months,years from price_list_cron where price_list_id=?i",(int)$request->price_list_id);
		if(!$price_list_cron){
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
		$db->query("update price_list_cron set hours=?s,days=?s,months=?s where price_list_id=?i",$hours,$days,$months,$request->price_list_id);
		return array("status"=>"ok","msg"=>"");
	}

}



?>