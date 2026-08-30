<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DetailGroup;
use Sort1API\Components\DetailGroupDetail;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Models\DetailCategorys;
use Sort1API\Components\SafeMySQL;
use Sort1API\Components\Config;
use Sort1API\Components\Functions;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DetailGroups extends Model {

	public static function save_detail_group($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->group_id) && (int)$request->group_id>0) {$detail_group_id=(int)$request->group_id;}
	    if(isset($detail_group_id) && $detail_group_id>0) {
				$detail_group=new DetailGroup($detail_group_id);
	    }
	    else {
				$detail_group=new DetailGroup();
		}
		//echo "detail_group=".print_r($detail_group,true);
	    if (isset($request->group_name)) {$detail_group->group_name=$request->group_name;}
		if (isset($request->markup)) {$detail_group->markup=$request->markup;}
		if (isset($request->in_group_id)) {$detail_group->in_group=$request->in_group_id;}
		$detail_group_order = $db->getOne('SELECT MAX(detail_group_order) FROM detail_group WHERE in_group = ?i AND main_company_id = ?i', (int)$request->in_group_id, (int)$_SESSION['main_company']);
		$detail_group->detail_group_order = (int)$detail_group_order + 1;
		$uri = Functions::translitIt($detail_group->group_name);
		$uri = Functions::translitUrl($uri);
		$uri = str_replace('--', '-', $uri);
		$detail_group->uri = $uri;
		//echo "detail_group=".print_r($detail_group,true);
	    //print_r($_GET);
	    //echo $company->kpp;
		$detail_group_saved=$detail_group->save();

        if($detail_group_saved){
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


	public static function delete_detail_group($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
			//return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->group_id) && (int)$request->group_id>0){
			$childrens=$db->getCol("select id from detail_group where in_group=?i and main_company_id=?i",(int)$request->group_id,(int)$_SESSION['main_company']);
			if($childrens && count($childrens)>0){
				foreach($childrens as $child){
					$res=DetailGroups::delete_detail_group((object)array("group_id"=>$child));
				}
			}
			$detail_group=new DetailGroup($request->detail_group_id);
			if($detail_group->status>1) return array("status"=>"err","err"=>"Нельзя удалить инвентаризацию, которая в работе или завершена");
			$res1=$db->query("delete from detail_group_details where detail_group_id=?i",(int)$request->group_id);
			$res2=$db->query("delete from detail_group where id=?i and main_company_id=?i",(int)$request->group_id,(int)$_SESSION['main_company']);
			//$res3=$db->query("delete from detail_group where in_group=?i and main_company_id=?i",(int)$request->group_id,(int)$_SESSION['main_company']);
				if ($res2 && $res1){
				    $ret['status']="ok";
				    //$res3=$db->query("update detail_groups set deleted=1 where id=?i and main_company=?i",(int)$request->detail_group_id,(int)$_SESSION['main_company']);
				    $ret['msg']="";
				}
				else {
				    $ret['status']="err";
				    $ret['err']="не удалось удалить товарную группу";
				}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_detail_groups($request) {
	    $db = DB::getInstance();
		if (!empty($request->in_group) && (int)$request->in_group > 0) {
			$in_group = $request->in_group;
		} else {
			$in_group = 0;
		}

		if (empty($_SESSION['my_sklad_id']) || (int)$_SESSION['my_sklad_id'] == 0) {
			return self::_error_arr("Не заведен склад, создайте склад");
		}

	    $sql = "SELECT 
                i.id, 
                i.create_date, 
                i.update_date, 
                i.in_group, 
                i.markup, 
                i.group_name,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM detail_group dg 
                        WHERE dg.in_group = i.id AND dg.main_company_id = ?i
                    ) THEN '1'
                    ELSE '0'
                END AS has_in_group  -- Проверка на наличие деталей в in_group
            FROM detail_group i
            LEFT JOIN (
                SELECT detail_group_id, COUNT(detail_id) AS detail_group_positions
                FROM detail_group_details 
                WHERE deleted = 0 
                GROUP BY detail_group_id
            ) AS ddk ON ddk.detail_group_id = i.id
            LEFT JOIN sklad s ON s.id = ?i
            WHERE i.main_company_id = ?i AND i.in_group = ?i";

		if (!empty($request->search_detail_group_date_from)) {
			$date_from = date("Y-m-d", strtotime($request->search_detail_group_date_from));
			$sql .= " AND i.create_date >= '" . $date_from . "'";
		}

		if (!empty($request->search_detail_group_date_to)) {
			$date_to = date("Y-m-d", strtotime($request->search_detail_group_date_to));
			$sql .= " AND i.create_date <= '" . $date_to . " 23:59:59'";
		}

		if (!empty($request->search_detail_group_sklad_name)) {
			$sql_cl = "SELECT id FROM sklad WHERE company_id = ?i AND name LIKE ?s";
			$res_cl = $db->getAll($sql_cl, $_SESSION['main_company'], '%' . $request->search_detail_group_sklad_name . '%');
			if ($res_cl) {
				$search_sklads = array_column($res_cl, "id");
				$sql .= " AND i.sklad_id IN (" . implode(',', $search_sklads) . ")";
			}
			$ret['search_detail_group_sklad_name'] = $request->search_detail_group_sklad_name;
		}

		$sql .= " ORDER BY i.detail_group_order ASC, i.create_date DESC";
		$res = $db->getAll($sql, $_SESSION['main_company'], $_SESSION['my_sklad_id'], $_SESSION['main_company'], $in_group);

		if (!isset($ret_res)) {
			$ret_res = $res;
		}

		if (is_array($res) && count($res) > 0) {
			$ret['status'] = "ok";
			$ret['err'] = "";
			$ret['detail_groups'] = $ret_res;
			$ret['msg'] = "";
			$ret['search_detail_group_date_to'] = $date_to ?? null;
			$ret['search_detail_group_date_from'] = $date_from ?? null;
		} else {
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['detail_groups'] = array();
			$ret['search_detail_group_date_to'] = $date_to ?? null;
			$ret['search_detail_group_date_from'] = $date_from ?? null;
		}

		$ret['sql'] = $sql;
		if ($ret['status'] == "err") {
			return self::_error_arr($ret['err']);
		} else {
			return $ret;
		}
	}

	public static function get_detail_group($request) {
	    $db = DB::getInstance();
			if(empty($request->group_id) || (int)$request->group_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select i.id,i.name,i.markup from detail_group i where i.id=?i and i.main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->group_id,$_SESSION['main_company']);
	    if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['detail_group']=$res;
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['detail_group']=[];
			$ret['detail_group_users']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}


	public static function get_detail_group_details($request){
		$db = DB::getInstance();
		if(empty($request->group_id) || (int)$request->group_id==0) {
			return self::_error_arr("Недостаточно параметров");
		}
		$detail_group=new DetailGroup((int)$request->group_id);
		//$detail_group_details_count=$db->getOne("select count(id) from detail_group_details where detail_group_id=?i",(int)$request->detail_group_id);
		//$is_my_detail_group=$db->getOne("select id from detail_group where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->detail_group_id);
		if((int)$detail_group->main_company_id!=$_SESSION['main_company']){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		}
		$sql="select * from detail_group_details where detail_group_id=?i ?p";
		$parsed="";
		if(!empty($request->search_article)){
			if((int)$detail_group->status==20 || (int)$detail_group->status==30) $parsed.=$db->parse(" and article like ?s and status=?i",'%'.$request->search_article.'%',$detail_group->status);
			else $parsed.=$db->parse(" and article like ?s",'%'.$request->search_article.'%');
		}
		else {
			if((int)$detail_group->status==20 || (int)$detail_group->status==30) 
				$parsed.=$db->parse(" and status=?i",$detail_group->status);
			
		}
		if(!empty($request->search_name)){
			$parsed.=$db->parse(" and name like ?s","%".$request->search_name."%");
		}
		$detail_group_details_count=$db->getOne("select count(id) from detail_group_details where detail_group_id=?i ?p",(int)$request->group_id,$parsed);

		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$detail_group_details_count;
	    $pages=ceil($detail_group_details_count/$page_size);
	    if(isset($request->selected_page) && (int)$request->selected_page<=(int)$pages) {
			$parsed.=$db->parse(" limit ?i,?i",$page_size*((int)$request->selected_page-1),$page_size);
	    }
	    else
		$parsed.=" limit 0,".$page_size;

		if(empty($request->selected_page)) $request->selected_page=1;
		
		$detail_group_details=$db->getAll($sql,(int)$request->group_id,$parsed);
		if($detail_group_details && count((array)$detail_group_details)>0)	{
			return array(
				"status"=>"ok",
				"detail_group_details"=>$detail_group_details,
				"msg"=>"","search_article"=>$request->search_article,
				"search_name"=>$request->search_name,
				"detail_group_pages"=>$pages,
				"details_count"=>(int)$detail_group_details_count
				,"selected_page"=>(int)$request->selected_page,
				"show_zero"=>(int)$request->show_zero
			);
		}
		else {
			return array(
				"status"=>"ok",
				"detail_group_details"=>array(),
				"msg"=>"",
				"search_article"=>$request->search_article,
				"search_name"=>$request->search_name,
				"detail_group_pages"=>$pages,
				"details_count"=>(int)$detail_group_details_count,
				"selected_page"=>(int)$request->selected_page,
				"show_zero"=>(int)$request->show_zero
			);			
		}
		
	}

	public static function get_detail_group_details_from_sklad($request){
		$db = DB::getInstance();
		if(empty($request->group_id) || (int)$request->group_id==0) {
			return self::_error_arr("Недостаточно параметров");
		}
		$detail_group=new DetailGroup((int)$request->group_id);
		//$detail_group_details_count=$db->getOne("select count(id) from detail_group_details where detail_group_id=?i",(int)$request->detail_group_id);
		//$is_my_detail_group=$db->getOne("select id from detail_group where company_id=?i and id=?i",$_SESSION['main_company'],(int)$request->detail_group_id);
		if((int)$detail_group->main_company_id!=$_SESSION['main_company']){
			return self::_error_arr("Неправильно заданы параметры, не ваш документ");
		}
		$parsed="";
		if(!empty($request->search_article)){
			$parsed.=$db->parse(" and sd.article like ?s",'%'.$request->search_article.'%');
		}
		if(!empty($request->search_name)){
			$parsed.=$db->parse(" and sd.name like ?s","%".$request->search_name."%");
		}
		if(!isset($request->show_zero) || !$request->show_zero){
			$parsed.=$db->parse(" and sd.count>0");
		}
		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$detail_group_details_count;
		$detail_group_details_count=$db->getOne("select count(detail_id) from sklad_details sd
                	where sd.sklad_id=?i ?p",$_SESSION['my_sklad_id'],$parsed);
	    $pages=ceil($detail_group_details_count/$page_size);
	    if(isset($request->selected_page) && (int)$request->selected_page<=(int)$pages) {
			$parsed.=$db->parse(" limit ?i,?i",$page_size*((int)$request->selected_page-1),$page_size);
	    }
	    else {
			$parsed.=" limit 0,".$page_size;
		}

		if(empty($request->selected_page)) $request->selected_page=1;
		
		$status=0;
		$sklad_details=$db->getAll("select sd.* from sklad_details sd 
		where sd.sklad_id=?i ?p",$_SESSION['my_sklad_id'],$parsed);
		//$sklad_details=Search::get_sale_price($sklad_details,0,"",array(),$db,1);
		$in_detail_group=$db->getCol("select detail_id from detail_group_details where detail_id in (?a) and detail_group_id=?i",array_column($sklad_details,"detail_id"),(int)$request->group_id);
		foreach($sklad_details as $skl_det_key=>$skl_det){
			if(in_array($skl_det['detail_id'],$in_detail_group)){
				$sklad_details[$skl_det_key]['checked']=true;
			}
			else {
				$sklad_details[$skl_det_key]['checked']=false;
			}
		}
		if(count((array)$sklad_details)>0){
			return array("status"=>"ok","detail_group_details"=>$sklad_details,"msg"=>"","detail_group_pages"=>$pages,"details_count"=>(int)$detail_group_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
		else {
			return self::_error_arr("На складе нет деталей");
		}
	}

	public static function get_detail_group_details_from_sklad_binding($request){
		$db = DB::getInstance();

		$parsed="";
		if(!empty($request->search_article)){
			$parsed.=$db->parse(" and sd.article like ?s",'%'.$request->search_article.'%');
		}
		if(!empty($request->search_name)){
			$parsed.=$db->parse(" and sd.name like ?s","%".$request->search_name."%");
		}
		if(!isset($request->show_zero) || !$request->show_zero){
			$parsed.=$db->parse(" and sd.count>0");
		}
		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$detail_group_details_count;
		$detail_group_details_count=$db->getOne("select count(detail_id) from sklad_details sd
                	where sd.sklad_id=?i 
					AND sd.detail_id NOT IN
					(SELECT detail_id FROM detail_group_details WHERE main_company_id = ?i)
					?p",$_SESSION['my_sklad_id'], $_SESSION['main_company'],$parsed);
	    $pages=ceil($detail_group_details_count/$page_size);
	    if(isset($request->selected_page) && (int)$request->selected_page<=(int)$pages) {
			$parsed.=$db->parse(" limit ?i,?i",$page_size*((int)$request->selected_page-1),$page_size);
	    }
	    else {
			$parsed.=" limit 0,".$page_size;
		}

		if(empty($request->selected_page)) $request->selected_page=1;
		
		$status=0;
		$sklad_details = $db->getAll("SELECT sd.*
			FROM sklad_details sd
			WHERE sd.sklad_id = ?i AND sd.detail_id NOT IN
				(SELECT detail_id FROM detail_group_details WHERE main_company_id = ?i) ?p", $_SESSION['my_sklad_id'], $_SESSION['main_company'], $parsed);

		if(count((array)$sklad_details)>0){
			return array("status"=>"ok","detail_group_details"=>$sklad_details,"msg"=>"","detail_group_pages"=>$pages,"details_count"=>(int)$detail_group_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
		else {
			return array("status"=>"ok","detail_group_details"=>array(),"msg"=>"","detail_group_pages"=>1,"details_count"=>0,"selected_page"=>1,"show_zero"=>(int)$request->show_zero);
		}
	}

	public static function get_detail_group_details_from_price_list_binding($request){
		$db = DB::getInstance();

		$parsed="";
		if(!empty($request->search_article)){
			$parsed.=$db->parse(" and pd.article like ?s",'%'.$request->search_article.'%');
		}
		if(!empty($request->search_name)){
			$parsed.=$db->parse(" and pd.name like ?s","%".$request->search_name."%");
		}
		if(!isset($request->show_zero) || !$request->show_zero){
			$parsed.=$db->parse(" and pd.count>0");
		}
		if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$detail_group_details_count;
		$price_list_ids=$db->getCol("select id from price_list where main_company=?i and deleted=0 and status=1",$_SESSION['main_company']);
		$detail_group_details_count=$db->getOne("select count(detail_id) from price_list_details pd
                	where pd.price_list_id in (?b) 
					AND pd.detail_id NOT IN
					(SELECT detail_id FROM detail_group_details WHERE main_company_id = ?i)
					?p",$price_list_ids, $_SESSION['main_company'],$parsed);
	    $pages=ceil($detail_group_details_count/$page_size);
	    if(isset($request->selected_page) && (int)$request->selected_page<=(int)$pages) {
			$parsed.=$db->parse(" limit ?i,?i",$page_size*((int)$request->selected_page-1),$page_size);
	    }
	    else {
			$parsed.=" limit 0,".$page_size;
		}

		if(empty($request->selected_page)) $request->selected_page=1;
		
		$status=0;
		$sklad_details = $db->getAll("SELECT pd.*
			FROM price_list_details pd
			WHERE pd.price_list_id in (?b) AND pd.detail_id NOT IN
				(SELECT detail_id FROM detail_group_details WHERE main_company_id = ?i) ?p", $price_list_ids, $_SESSION['main_company'], $parsed);

		if(count((array)$sklad_details)>0){
			return array("status"=>"ok","detail_group_details"=>$sklad_details,"msg"=>"","detail_group_pages"=>$pages,"details_count"=>(int)$detail_group_details_count,"selected_page"=>(int)$request->selected_page,"show_zero"=>(int)$request->show_zero);
		}
		else {
			return array("status"=>"ok","detail_group_details"=>array(),"msg"=>"","detail_group_pages"=>1,"details_count"=>0,"selected_page"=>1,"show_zero"=>(int)$request->show_zero);
		}
	}

	public static function add_detail_group_detail_to_start($request){
		$db = DB::getInstance();

		if(empty($request->detail_group_detail_id) || (int)$request->detail_group_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		if(empty($request->detail_group_id) || (int)$request->detail_group_id==0){
			return self::_error_arr("Не указан id группы товаров");
		}
		$detail=$db->getRow("select *
		 from sklad_details  
		 where detail_id=?i and sklad_id=?i", (int)$request->detail_group_detail_id,(int)$request->sklad_id);
		 
		$ins_det=$db->query("insert ignore into detail_group_details (detail_group_id,article,brand,name,detail_id,brand_id,main_company_id) 
			values (?i,?s,?s,?s,?i,?i,?i)",
			(int)$request->detail_group_id,$detail['article'],$detail['brand'],$detail['name'],$detail['detail_id'],$detail['brand_id'],$_SESSION['main_company']);
		if($db->affectedRows()>0){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			//$del_det=$db->query("delete from detail_group_details where detail_id=?i and detail_group_id=?i and main_company_id = ?i",(int)$request->detail_group_detail_id,(int)$request->detail_group_id, $_SESSION['main_company']);
			//if($del_det) return array("status"=>"ok","msg"=>"");
			//else return self::_error_arr("не удалось удалить деталь из товарной группы");
		}
	}

	public static function delete_detail_group_detail($request){
		$db = DB::getInstance();

		if(empty($request->detail_group_detail_id) || (int)$request->detail_group_detail_id==0){
			return self::_error_arr("Не указан id детали");
		}
		if(empty($request->detail_group_id) || (int)$request->detail_group_id==0){
			return self::_error_arr("Не указан id группы товаров");
		}
		$del_det=$db->query("delete from detail_group_details where detail_group_id=?i and detail_id=?i and main_company_id=?i",
			(int)$request->detail_group_id,$request->detail_group_detail_id,$_SESSION['main_company']);
		if($db->affectedRows()>0){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			//$del_det=$db->query("delete from detail_group_details where detail_id=?i and detail_group_id=?i and main_company_id = ?i",(int)$request->detail_group_detail_id,(int)$request->detail_group_id, $_SESSION['main_company']);
			//if($del_det) return array("status"=>"ok","msg"=>"");
			//else return self::_error_arr("не удалось удалить деталь из товарной группы");
		}
	}

	public static function add_detail_group_library($request){
		$db = new SafeMySQL(Config::get_section('mysql-', true));
		$db_lib = DB::getInstance("libr");
		
		$file = '../cache/jetparts/catalog/catalog.json';
		$jsonData = file_get_contents($file);
		$res = json_decode($jsonData, true);
		if($res['create_date'] != date('d-m-Y')){
			DetailCategorys::get_all_categorys_db();
			$jsonData = file_get_contents($file);
			$res = json_decode($jsonData, true);
			$categories = $res['categories'];
		}
		else{
			$categories = $res['categories'];
		}

		self::processCategory($db,$db_lib, $categories);
		return array("status"=>"ok","msg"=>"");
	}

	private static function processCategory(&$db, &$db_lib, $categories, $parentId = 0) {
		self::update_zero_detail_group($db, $parentId);

		foreach ($categories as $category) {
			$existingCategory = $db->getOne('SELECT id FROM detail_group WHERE library_category_id = ?i AND in_group = ?i  AND main_company_id = ?i LIMIT 1', (int)$category['id'], $parentId, (int)$_SESSION['main_company']);
			if (!empty($existingCategory)) {
				$newCategoryId = $existingCategory;
			} else {
				// Получить максимальное значение detail_group_order для текущей группы
				$maxOrder = $db->getOne('SELECT MAX(detail_group_order) FROM detail_group WHERE in_group = ?i AND main_company_id = ?i', $parentId, (int)$_SESSION['main_company']);
				$maxOrder = (!empty($maxOrder) ? $maxOrder : 0);
				$data = [
					'group_name'  => $category['name'],
					'in_group'    => $parentId,
					'create_date' => date('Y-m-d H:i:s'),
					'update_date' => date('Y-m-d H:i:s'),
					'main_company_id' => $_SESSION['main_company'],
					'library_category_id' =>  $category['id'],
					// Установить новое значение detail_group_order как следующее от максимального
					'detail_group_order' => (int) $maxOrder + 1,
					'uri' => $category['uri']
				];
				$db->query('INSERT INTO detail_group SET ?u', $data);
	
				$newCategoryId = $db->insertId();
			}
			// $det_info_query = "SELECT subquery.id
			// FROM (
			// 	SELECT d.id
			// 	FROM details d
			// 	WHERE d.categoryId = (?i)
			// 	GROUP BY d.id, d.article, d.article_raw, d.name
			// 	ORDER BY d.id
			// ) AS subquery;";

			// $details = $db_lib->getAll($det_info_query, (int)$category['id']);
			// $detailIds = array_column($details, 'id');
			// $detailIds = array_map('intval', $detailIds);
			
			// $cross_detail = $db->getAll("SELECT oem_detail_id AS detail_id
			// FROM local_cross
			// WHERE oem_detail_id IN (?b) AND main_company_id=?i
			// UNION
			// SELECT cross_detail_id AS detail_id
			// FROM local_cross
			// WHERE cross_detail_id IN (?b) AND main_company_id=?i", $detailIds, (int)$_SESSION['main_company'], $detailIds, (int)$_SESSION['main_company']);

			// $cross_detailIds = array_column($cross_detail, 'detail_id');
			
			// $cross_detailIds = array_map('intval', $cross_detailIds);

			// $detailIds = array_merge($detailIds, $cross_detailIds);
			// $detailIds = array_unique($detailIds);

			// $sklad_details=$db->getAll("select *
			// from sklad_details  
			// where detail_id in (?b) and sklad_id=?i", $detailIds,(int)$_SESSION['my_sklad_id']);

			// foreach ($combined_array as $detail) {
			// 	$data = [
			// 		'detail_group_id' => (int)$newCategoryId,
			// 		'article' => $detail['article'],
			// 		'brand' => $detail['brand'],
			// 		'name' => $detail['name'],
			// 		'detail_id' => (int)$detail['detail_id'],
			// 		'brand_id' => (int)$detail['brand_id'],
			// 		'main_company_id' => (int)$_SESSION['main_company']
			// 	];

			// 	// $db->query("INSERT IGNORE INTO detail_group_details SET ?u", $data);
			// }
			if (!empty($category['subcategories'])) {
				self::processCategory($db, $db_lib, $category['subcategories'], $newCategoryId);
			}
		}
	}

	private static function update_zero_detail_group(&$db, $parentId) {
		$zeroOrderGroups = $db->getAll('SELECT id FROM detail_group WHERE detail_group_order = 0 AND in_group = ?i AND main_company_id = ?i', $parentId, (int)$_SESSION['main_company']);
	
		if (!empty($zeroOrderGroups)) {
			$currentOrder = $db->getOne('SELECT MAX(detail_group_order) FROM detail_group WHERE in_group = ?i AND main_company_id = ?i', $parentId, (int)$_SESSION['main_company']);
			$currentOrder = (!empty($currentOrder) ? $currentOrder : 0);
			
			foreach ($zeroOrderGroups as $group) {
				$currentOrder += 1;
				$db->query('UPDATE detail_group SET detail_group_order = ?i WHERE id = ?i', $currentOrder, $group['id']);
				self::update_zero_detail_group($db, $group['id']);
			}
		}
	}

	public static function move_detail_group($request){
		$db = DB::getInstance();

		if((empty($request->group_id) || (int)$request->group_id==0) && empty($request->direction)){
			return self::_error_arr('нет данных');
		}
		else {
			$group_id = $request->group_id;
			$direction = $request->direction;
		}

		$currentOrderSql = "SELECT in_group,detail_group_order FROM detail_group WHERE id = ?i AND main_company_id = ?i";
		$currentOrder = $db->getRow($currentOrderSql,(int)$group_id,(int)$_SESSION['main_company']);

		if ($direction == "up") {
			$newOrderSql = "SELECT id, detail_group_order FROM detail_group WHERE in_group = ?i AND detail_group_order < ?i AND main_company_id = ?i ORDER BY detail_group_order DESC LIMIT 1";
		} else {
			$newOrderSql = "SELECT id, detail_group_order FROM detail_group WHERE in_group = ?i AND detail_group_order > ?i AND main_company_id = ?i ORDER BY detail_group_order ASC LIMIT 1";
		}

		$newOrder = $db->getRow($newOrderSql,(int)$currentOrder['in_group'],(int)$currentOrder['detail_group_order'],(int)$_SESSION['main_company']);
		if ($newOrder) {
			// Обновление строк с новыми значениями порядка
			$updateOrderSql1 = "UPDATE detail_group SET detail_group_order = ?i WHERE id = ?i AND main_company_id = ?i";
			$updateOrderSql2 = "UPDATE detail_group SET detail_group_order = ?i WHERE id = ?i AND main_company_id = ?i";
			$db->query($updateOrderSql1, $newOrder['detail_group_order'], $group_id, (int)$_SESSION['main_company']);
			$db->query($updateOrderSql2, $currentOrder['detail_group_order'], $newOrder['id'], (int)$_SESSION['main_company']);
		}
		// print_r($db->getStats());

		return array("status"=>"ok","msg"=>"");
	}

	public static function set_undefined_details_group($request){
		$db = DB::getInstance();

		if((empty($request->group_id) || (int)$request->group_id==0) && empty($request->details)){
			return self::_error_arr('нет данных');
		}
		else {
			$group_id = $request->group_id;
			$details = $request->details;
		}

		$sklad_ids = $db->getCol("SELECT id FROM sklad WHERE company_id = ?i", $_SESSION['main_company']);

		$price_ids = $db->getCol("SELECT id FROM price_list WHERE main_company = ?i", $_SESSION['main_company']);

		foreach($details as $detail_id){
			$detail = $db->getRow("SELECT * FROM sklad_details WHERE sklad_id IN (?a) AND detail_id = ?i", $sklad_ids, $detail_id);
			
			if(!$detail){
				$detail = $db->getRow("SELECT * FROM price_list_details WHERE price_list_id IN (?a) AND detail_id = ?i", $price_ids, (int)$detail_id);
			}
			
			if($detail){
				$ins_det=$db->query("insert ignore into detail_group_details (detail_group_id,article,brand,name,detail_id,brand_id,main_company_id) 
				values (?i,?s,?s,?s,?i,?i,?i)",
				(int)$group_id,$detail['article'],$detail['brand'],$detail['name'],$detail['detail_id'],$detail['brand_id'],$_SESSION['main_company']);
			}

			$user_detail = $db->getRow("SELECT * FROM user_details_without_category WHERE detail_id = ?i AND main_company_id = ?i AND user_id = ?i", (int)$detail['detail_id'], $_SESSION['main_company'], $_SESSION['user_id']);

            if ($user_detail) {
                $db->query("DELETE FROM user_details_without_category WHERE detail_id = ?i AND main_company_id = ?i AND user_id = ?i", (int)$detail['detail_id'], $_SESSION['main_company'], $_SESSION['user_id']);
            }
		}

		return array("status"=>"ok","msg"=>"");
	}
}



?>
