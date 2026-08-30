<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Auth;
use Sort1API\Components\Sklad;
//require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Models\Users;
use Sort1API\Components\Models\Documents;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Sklads extends Model {

        public static function check_roles($role){
			$main_user=new User((int)$_SESSION['user_id']);
			if ($main_user->roles<=$role) return $role;
			else return $main_user->roles;
		}

        public static function save_sklad($request) {
          $db = DB::getInstance();
    	    if (isset($request->sklad_id)) $sklad_id=(int)$request->sklad_id;
    	    if (isset($sklad_id) && $sklad_id>0) {
    		      $sklad=new Sklad($sklad_id);
    	    }
    	    else
    		    $sklad=new Sklad();
    	    if (isset($request->company_id) && (int)$request->company_id>0) {
        		$companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
        		if ($companys && in_array($request->company_id,$companys))
        		    $sklad->company_id=(int)$request->company_id;
        		else {
        		    return self::_error_arr("Нельзя добавить склад к чужой компании");
        		}
    	    }
    	    else $sklad->company_id=$_SESSION['company_id'];
    	    if (isset($request->address)) $sklad->address=$request->address;
    	    if (isset($request->descr)) $sklad->descr=$request->descr;
    	    if (isset($request->name)) $sklad->name=$request->name;
			if (isset($request->sklad_phone)) $sklad->phone=$request->sklad_phone;
    	    if (isset($request->status)) $sklad->status=$request->status;
          	if (isset($request->topology_id)) $sklad->topology_id=(int)$request->topology_id;
    	    if (isset($request->price_type)) {
				$price_type_type=$db->getOne("select type from dict_price_type where id=?i",$request->price_type);
				if((int)$price_type_type==4){
					// Посколько добавляем дифференцированную наценку необходимо обнулить default_markup
					$db->query("update sklad_details set default_markup=0 where sklad_id=?i",$sklad->id);
				}
				$sklad->price_type=$request->price_type;
			}
    	    if (isset($request->city_id)) $sklad->city_id=(int)$request->city_id;
    	    if (isset($request->city_name)) $sklad->city_name=$request->city_name;
    	    if (isset($request->sklad_coordinate)) $sklad->coordinate=$request->sklad_coordinate;
    	    if (isset($request->sklad_work_time)) $sklad->work_time=$request->sklad_work_time;
			if (isset($request->punkt_vydachi) && $request->punkt_vydachi=="on") $sklad->punkt_vydachi=1;
			else $sklad->punkt_vydachi=0;
			if (isset($request->search_in_shop) && $request->search_in_shop=="on") $sklad->search_in_shop=1;
			else $sklad->search_in_shop=0;
			if (isset($request->sklad_use_in_search) && $request->sklad_use_in_search=="on") $sklad->sklad_use_in_search=1;
			else $sklad->sklad_use_in_search=0;
			if (isset($request->sklad_use_in_jetparts) && $request->sklad_use_in_jetparts=="on") $sklad->sklad_use_in_jetparts=1;
			else $sklad->sklad_use_in_jetparts=0;
		  	if (isset($request->fullfilment) && $request->fullfilment=="on") $sklad->fullfilment=1;
		  	else $sklad->fullfilment=0;
    	    if (isset($request->default_markup)) { 
        		if ((int)$sklad->default_markup!=(int)$request->default_markup){
        		    $sql="update sklad_details set default_markup=?i where sklad_id=?i";
        		    $res=$db->query($sql,(empty($request->default_markup)?0:$request->default_markup),$sklad->id);
        		    $sklad->default_markup=(int)$request->default_markup;
        		    // poka ne znau
        		}
      		  //$db->query("update sklad_details set default_markup=?i where sklad_id=?i",$request->default_markup,$sklad->id);
      		  //$sklad->default_markup=$request->default_markup;
	       }
      	    if ($sklad->name=="") return self::_error_arr("Укажите наименование склада");
      	    $err=$sklad->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: 
                    $status="ok"; $msg="";
					Auth::check_my_sklad();
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg);
        }


	public static function get_sklads($request) {
	    $db = DB::getInstance();
	    $sql="SELECT s.*
            FROM sklad s
            WHERE s.company_id=?i AND s.deleted=0";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
		foreach($res as $key=>$sklad){
			$pos=$db->getInd("sklad_id","SELECT sklad_id,COUNT(detail_id) AS sklad_positions,SUM(COUNT) AS sklad_pos_count,sum(price*count) as sklad_sum 
				FROM sklad_details where count>0 and sklad_id=?i GROUP BY sklad_id",$sklad['id']);
			$res[$key]['sklad_positions']=$pos[$sklad['id']]['sklad_positions'];
			$res[$key]['sklad_pos_count']=$pos[$sklad['id']]['sklad_pos_count'];
			if($sklad['id']==237) $res[$key]['sklad_sum']=$pos[$sklad['id']]['sklad_sum']-1500000;
			else $res[$key]['sklad_sum']=$pos[$sklad['id']]['sklad_sum'];
		}
		
		if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklads']=$res;
    		$ret['msg']="";
		}
		else {
			$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklads']=array();
    		$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_delivery_sklads($request) {
	    $db = DB::getInstance();
	    $sql="select id,descr,address,name,city_name,coordinate,work_time from sklad where company_id=?i and deleted=0 and punkt_vydachi=1";
	    $res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['sklads']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad($request) {
	    $db = DB::getInstance();
	    $sql="select * from sklad where id=?i and company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) and deleted=0";
	    if (isset($request->sklad_id) && (int)$request->sklad_id>0){
		$sklad_id=(int)$request->sklad_id;
		$sklad=new Sklad($sklad_id);
	    }
	    else {
		return self::_error_arr("не указан id склада");
	    }
	    $res=$db->getRow($sql,$sklad_id,(int)$_SESSION['user_id']);
	    if ($res['id']>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklad']=$res;
    		$ret['msg']="";
    		$ret['price_types']=$db->getAll("select * from dict_price_type where (type=2 or type=4) and main_company=?i and deleted=0",$_SESSION['main_company']);
        $ret['topologys']=$db->getAll("select id,name from sklad_topology where main_company_id=?i",$_SESSION['main_company']);
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad_by_session($request) {
		if (!isset($_SESSION['my_sklad_id']) || (int)$_SESSION['my_sklad_id'] <= 0) {
			return self::_error_arr("не указан id склада");
		}

		$sklad_id = (int)$_SESSION['my_sklad_id'];
		$user_id = (int)$_SESSION['user_id'];
		$main_company = (int)$_SESSION['main_company'];

		$db = DB::getInstance();
		$sql = "SELECT * FROM sklad 
				WHERE id = ?i 
				AND company_id IN (
					SELECT company_id 
					FROM user_companys 
					WHERE user_id = ?i AND main_company_id = 0
				) 
				AND deleted = 0";

		$res = $db->getRow($sql, $sklad_id, $user_id);

		if (!$res || $res['id'] <= 0) {
			return self::_error_arr("склад не найден или недоступен");
		}

		return [
			'status' => 'ok',
			'err' => '',
			'msg' => '',
			'sklad' => $res,
			'price_types' => $db->getAll(
				"SELECT * FROM dict_price_type 
				WHERE (type = 2 OR type = 4) AND main_company = ?i AND deleted = 0",
				$main_company
			),
			'topologys' => $db->getAll(
				"SELECT id, name FROM sklad_topology 
				WHERE main_company_id = ?i",
				$main_company
			)
		];
	}

	public static function delete_sklad($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->sklad_id)) {$sklad_id=(int)$request->sklad_id;}
	    if (isset($sklad_id) && $sklad_id>0){
    		$res2=$db->query("update sklad set deleted=1 where id=?i and company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$sklad_id,$_SESSION['user_id']);
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
				if((int)$_SESSION['my_sklad_id']==(int)$request->sklad_id){
					Auth::check_my_sklad();
				}
    		    $ret['status']="ok";
    		    $ret['msg']="";
    		}
    		else {
    		    $ret['status']="err";
    		    $ret['err']="не удалось удалить Склад";
    		}
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_sklad_csv($request){
		$db = DB::getInstance();
		if(!empty($request->add_markup) && $request->add_markup) $add_markup=true; else $add_markup=false;
		if(!empty($request->use_reserv) && $request->use_reserv) $use_reserv=true; else $use_reserv=false;
		if(!empty($request->get_zero_count) && $request->get_zero_count) $get_zero_count=true; else $get_zero_count=false;
		if((int)$request->sklad_id>0){
			$parsed="";
			if(!$get_zero_count) $parsed.=$db->parse(" and sd.count>0");
			$pre_sklad_details=$db->getAll("select sd.*,sdl.location,s.name as sklad_name,s.city_name,s.city_id,s.price_type from sklad_details sd 
			left join sklad s on (s.id=sd.sklad_id)
			left join sklad_detail_locations sdl on (sdl.detail_id=sd.detail_id and sdl.sklad_id=sd.sklad_id)
			where sd.sklad_id in (select id from sklad where company_id=?i) and sd.sklad_id=?i ?p",$_SESSION['main_company'],$request->sklad_id,$parsed);
			if($add_markup) $pre_sklad_details=Search::get_sale_price($pre_sklad_details,0,"",array(),$db,$use_reserv);
			$roles=Users::get_my_role();
			if(isset($roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']) 
				&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['show']==1) 
					$show_dealer_price=1;
			else $show_dealer_price=0;
			foreach($pre_sklad_details as $res_key=>$res_val){
				if($show_dealer_price==0){
					$pre_sklad_details[$res_key]['price']="???";
				}
			}
			$sklad_details=array();
			foreach($pre_sklad_details as $skl_det_key=>$sklad_detail){
				$sklad_details[$skl_det_key]['article']=$sklad_detail['article'];
				$sklad_details[$skl_det_key]['brand']=$sklad_detail['brand'];
				$sklad_details[$skl_det_key]['name']=str_replace("\\","|",$sklad_detail['name']);
				$sklad_details[$skl_det_key]['count']=$sklad_detail['count'];
				if($add_markup) $sklad_details[$skl_det_key]['price']=$sklad_detail['sale_price'];
				else $sklad_details[$skl_det_key]['price']=$sklad_detail['price'];
				$sklad_details[$skl_det_key]['location']=$sklad_detail['location'];
				$sklad_details[$skl_det_key]['my_code']=$sklad_detail['my_code'];
				$sklad_details[$skl_det_key]['min_count_must_have']=$sklad_detail['min_count_must_have'];
				$sklad_details[$skl_det_key]['default_markup']=$sklad_detail['default_markup'];
				$sklad_details[$skl_det_key]['detail_markup']=$sklad_detail['detail_markup'];
				$sklad_details[$skl_det_key]['detail_markup_price']=$sklad_detail['detail_markup_price'];
				$sklad_details[$skl_det_key]['ean13']=$sklad_detail['ean13'];
				$sklad_details[$skl_det_key]['is_excise']=$sklad_detail['is_excise'];
			}
			
			$csv = implode(",", array_keys(reset($sklad_details))) . PHP_EOL;
			foreach ($sklad_details as $row) {
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
			unlink("/tmp/export_sklad_".$sklad_id.".csv");
			
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}
		else {
			return self::_error_arr("Не укзан склад для экпорта");
		}
	}

	public static function get_sklad_xls($request){
		$db = DB::getInstance(); 
		if(!empty($request->add_markup) && $request->add_markup) $add_markup=true; else $add_markup=false;
		if(!empty($request->use_reserv) && $request->use_reserv) $use_reserv=true; else $use_reserv=false;
		if(!empty($request->get_zero_count) && $request->get_zero_count) $get_zero_count=true; else $get_zero_count=false;
		if(!empty($request->get_only_zero_count) && $request->get_only_zero_count) $get_only_zero_count=true; else $get_only_zero_count=false;
		//if(!$get_zero_count) $parsed.=$db->parse(" and sd.count>0");
		if((int)$request->sklad_id>0){
			$parsed="";
			if(!$get_zero_count && !$get_only_zero_count) $parsed.=$db->parse(" and sd.count>0");
			else {
				if($get_only_zero_count) $parsed.=$db->parse(" and sd.count=0");
			}
			$pre_sklad_details=$db->getAll("select sd.*,sdl.location,s.city_name,s.city_id,s.price_type,sd.min_count_must_have from sklad_details sd 
			left join sklad s on (s.id=sd.sklad_id)
			left join sklad_detail_locations sdl on (sdl.detail_id=sd.detail_id and sdl.sklad_id=sd.sklad_id)
			where sd.sklad_id in (select id from sklad where company_id=?i) and sd.sklad_id=?i ?p",$_SESSION['main_company'],$request->sklad_id,$parsed);
			if($add_markup) $pre_sklad_details=Search::get_sale_price($pre_sklad_details,0,"",array(),$db,$use_reserv);
			$roles=Users::get_my_role();
			if(isset($roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']) 
				&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['show']==1) 
					$show_dealer_price=1;
			else $show_dealer_price=0;
			foreach($pre_sklad_details as $res_key=>$res_val){
				if($show_dealer_price==0){
					$pre_sklad_details[$res_key]['price']="???";
				}
			}
			$sklad_details=array();
			foreach($pre_sklad_details as $skl_det_key=>$sklad_detail){
				$sklad_details[$skl_det_key]['article']=$sklad_detail['article'];
				$sklad_details[$skl_det_key]['brand']=$sklad_detail['brand'];
				$sklad_details[$skl_det_key]['name']=str_replace("=","",str_replace("\\","|",$sklad_detail['name']));
				$sklad_details[$skl_det_key]['count']=$sklad_detail['count'];
				if($add_markup) $sklad_details[$skl_det_key]['price']=$sklad_detail['sale_price'];
				else $sklad_details[$skl_det_key]['price']=$sklad_detail['price'];
				$sklad_details[$skl_det_key]['location']=$sklad_detail['location'];
				$sklad_details[$skl_det_key]['my_code']=str_replace("=","",$sklad_detail['my_code']);
				$sklad_details[$skl_det_key]['min_count_must_have']=$sklad_detail['min_count_must_have'];
				$sklad_details[$skl_det_key]['default_markup']=$sklad_detail['default_markup'];
				$sklad_details[$skl_det_key]['detail_markup']=$sklad_detail['detail_markup'];
				$sklad_details[$skl_det_key]['detail_markup_price']=$sklad_detail['detail_markup_price'];
				$sklad_details[$skl_det_key]['ean13']=str_replace("=","",$sklad_detail['ean13']);
				$sklad_details[$skl_det_key]['is_excise']=$sklad_detail['is_excise'];
			}
			$tmp_dir = rtrim(sys_get_temp_dir(), "\\/");
			$xls_file = $tmp_dir . DIRECTORY_SEPARATOR . "export_sklad_".$request->sklad_id.".xls";

			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			$columnToLetters = function($columnIndex) {
				$letters = '';
				while ($columnIndex > 0) {
					$mod = ($columnIndex - 1) % 26;
					$letters = chr(65 + $mod) . $letters;
					$columnIndex = (int)(($columnIndex - $mod - 1) / 26);
				}
				return $letters;
			};

			if (!empty($sklad_details)) {
				$headers = array_keys(reset($sklad_details));
			} else {
				$headers = array('article', 'brand', 'name', 'count', 'price', 'location', 'my_code', 'min_count_must_have', 'default_markup', 'detail_markup', 'detail_markup_price', 'ean13', 'is_excise');
			}

			$col_num = 1;
			foreach ($headers as $header) {
				$sheet->setCellValue($columnToLetters($col_num) . '1', (string)$header);
				$col_num++;
			}

			$row_num = 2;
			foreach ($sklad_details as $row) {
				$col_num = 1;
				foreach (array_values($row) as $value) {
					$sheet->setCellValue($columnToLetters($col_num) . (string)$row_num, (string)$value);
					$col_num++;
				}
				$row_num++;
			}

			$writer = new Xls($spreadsheet);
			$writer->save($xls_file);

			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents($xls_file));
			unlink($xls_file);
			return array("status"=>"ok","msg"=>"","file"=>$file,"filename"=>"export.xls");
		}
		else {
			return self::_error_arr("Не указан склад для экпорта");
		}
	}

	public static function get_stock_balances($request){
		$db = DB::getInstance();
		if(empty($request->sklad_id) || (int)$request->sklad_id<=0){
			return self::_error_arr("Не указан склад");
		}
		if(!empty($request->date_from)) $date_from=date("Y-m-d",strtotime($request->date_from));
		else $date_from=date("Y-m-d",strtotime("1 month ago"));
		if(!empty($request->date_to)) $date_to=date("Y-m-d",strtotime($request->date_to));
		else $date_to=date("Y-m-d");
		$sql="select * from stock_balances where date>=?s and date<=?s and sklad_id=?i and company_id=?i";
		$sb=$db->getAll($sql,$date_from,$date_to." 23:59:59",(int)$request->sklad_id,$_SESSION['main_company']);
		if($sb){
			return array("status"=>"ok","err"=>"","msg"=>"","stock_balances"=>$sb,"date_from"=>$date_from,"date_to"=>$date_to);
		}
		else {
			return array("status"=>"ok","err"=>"","msg"=>"","stock_balances"=>array(),"date_from"=>$date_from,"date_to"=>$date_to);
		}
	}

	public static function clear_unused_details($request){
		if(empty($request->sklad_id) || (int)$request->sklad_id<=0) return array("status"=>"err","err"=>"Не указан склад");
		$db = DB::getInstance();
		$is_your=$db->getOne("select id from sklad where id=?i and company_id=?i",(int)$request->sklad_id,$_SESSION['main_company']);
		if(!$is_your) return array("status"=>"err","err"=>"Не ваш склад");
		
		$db->query("DELETE FROM document_details WHERE deleted=1 and document_id in (select id from document where main_company=?i)",$_SESSION['main_company']);
		$db->query("delete from document where main_company=?i and deleted=1",$_SESSION['main_company']);
		$db->query("DELETE FROM sklad_details 
			WHERE detail_id NOT IN (SELECT detail_id FROM document_details WHERE document_id IN (SELECT id FROM document WHERE main_company=?i))
			 AND sklad_id=?i",$_SESSION['main_company'],(int)$request->sklad_id);
		return array("status"=>"ok","err"=>"","msg"=>"","deleted"=>$db->affectedRows());
	}

	public static function invent_sklad($request){
		if(empty($request->sklad_id) || (int)$request->sklad_id<=0) return array("status"=>"err","err"=>"Не указан склад");
		$db = DB::getInstance();
		$is_your=$db->getOne("select id from sklad where id=?i and company_id=?i",(int)$request->sklad_id,$_SESSION['main_company']);
		if(!$is_your) return array("status"=>"err","err"=>"Не ваш склад");
		$sklad_details=$db->getInd("detail_id","select * from sklad_details where deleted=0 and count>0 and sklad_id=?i",(int)$request->sklad_id);
		$ret=array();
		$sess=$_SESSION;
		session_write_close();
		$_SESSION=$sess;
		$db->query("insert into sklad_invent_results (main_company_id,sklad_id,percent) values (?i,?i,?i)",$_SESSION['main_company'],(int)$request->sklad_id,0);
		$res_id=$db->insertId();
		$write_percent=0;$i=0;
		$parsed.=$db->parse(" and create_date>='0000-00-00' and create_date<=?s",date("Y-m-d")." 23:59:59");
		$documents=$db->getInd("id","select * from document where main_company=?i and deleted=0 and sklad_id=?i ?p",$_SESSION['main_company'],$request->sklad_id,$parsed);
		foreach($sklad_details as $sd_key=>$sd_val){
			$i++;
			$percent=round($i/(count($sklad_details))*100);
			//echo count($sklad_details)."/".$sd_key." ".$percent."\n";
			$sklad_details[$sd_val['detail_id']]['calculated_count']=0;
			$detail_movements=Documents::get_detail_documents((object)array(
				"sklad_id"=>$sd_val['sklad_id'],
				"detail_id"=>$sd_val['detail_id'],
				"date_from"=>"0000-00-00 00:00:00",
				"documents"=>$documents));
			foreach($detail_movements['document_details'] as $dm_key=>$dm_val){
				switch((int)$detail_movements['documents'][$dm_val['document_id']]['type_id']){
					case 1: 
						$sklad_details[$sd_val['detail_id']]['calculated_count']+=$dm_val['count'];
						 break;
					case 2: 
						$sklad_details[$sd_val['detail_id']]['calculated_count']-=$dm_val['count']; 
						break;
					case 5: 
						$sklad_details[$sd_val['detail_id']]['calculated_count']+=$dm_val['count']; 
						break;
					case 3: 
						$sklad_details[$sd_val['detail_id']]['calculated_count']-=$dm_val['count']; 
						break;
					case 6: 
						$sklad_details[$sd_val['detail_id']]['calculated_count']+=$dm_val['count'];
						 break;
					case 7: 
						$sklad_details[$sd_val['detail_id']]['calculated_count']-=$dm_val['count'];
						break;
				}

			}
			if($write_percent<$percent){
				$write_percent=$percent;
				$db->query("update sklad_invent_results set percent=?i where id=?i",$percent,$res_id);
			}
			if($sklad_details[$sd_val['detail_id']]['calculated_count']!=$sklad_details[$sd_val['detail_id']]['count']){
				if(!isset($ret['sklad_details'])) $ret['sklad_details']=array();
				$ret['sklad_details'][]=$sklad_details[$sd_val['detail_id']];

			}
			mysqli_commit($db->get_conn());
		}
		$db->query("update sklad_invent_results set result=?s where id=?i",json_encode(array("status"=>"ok","msg"=>"","err"=>"","sklad_details"=>(array)$ret['sklad_details'])),$res_id);
		return array("status"=>"ok","msg"=>"","err"=>"","sklad_details"=>(array)$ret['sklad_details']);
	}
}



?>
