<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Detailcategory;
use Sort1API\Components\DetailcategoryDetail;
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


class DetailCategorys extends Model {

	public static function save_detail_category($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->category_id) && (int)$request->category_id>0) {$detail_category_id=(int)$request->category_id;}
	    if(isset($detail_category_id) && $detail_category_id>0) {
				$detail_category=new Detailcategory($detail_category_id);
	    }
	    else {
				$detail_category=new Detailcategory();
		}
		//echo "detail_category=".print_r($detail_category,true);
	    if (isset($request->category_name)) {$detail_category->category_name=$request->category_name;}
		if (isset($request->markup)) {$detail_category->markup=$request->markup;}
		if (isset($request->parentId_id)) {$detail_category->parentId=$request->parentId_id;}
		//echo "detail_category=".print_r($detail_category,true);
	    //print_r($_GET);
	    //echo $company->kpp;
		$detail_category_saved=$detail_category->save();

        if($detail_category_saved){
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


	public static function delete_detail_category($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->category_id) && (int)$request->category_id>0){
			$detail_category=new Detailcategory($request->detail_category_id);
			if($detail_category->status>1) return array("status"=>"err","err"=>"Нельзя удалить инвентаризацию, которая в работе или завершена");
			$res1=$db->query("delete from detail_category_details where detail_category_id=?i",(int)$request->category_id);
			$res2=$db->query("delete from detail_category where id=?i and main_company_id=?i",(int)$request->category_id,(int)$_SESSION['main_company']);
				if ($res2 && $res1){
				    $ret['status']="ok";
				    //$res3=$db->query("update detail_categorys set deleted=1 where id=?i and main_company=?i",(int)$request->detail_category_id,(int)$_SESSION['main_company']);
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

    public static function get_uri_data($request){
		preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);

		if(empty($request->uri)) return self::_error_arr("Не указан uri");
        $db = DB::getInstance("libr");
		$db1 = DB::getInstance();
		$sqlConfig = "SELECT use_catalog_sort1 FROM company_sites WHERE site_name = ?s";
		$ret_data = $db1->getRow($sqlConfig, str_replace("www.", "", $origin[1]));
		//$ret_data['use_catalog_sort1']=1;
		$dbToUse = ($ret_data['use_catalog_sort1'] == 0) ? $db1 : $db;

		$self_cats = ($ret_data['use_catalog_sort1'] == 0)
			? $dbToUse->getRow("select * from detail_group where uri=?s and main_company_id = ?i", $request->uri, $_SESSION['main_company'])
			: $dbToUse->getRow("select * from cats where uri=?s", $request->uri);
		if (!$self_cats['id'] && $ret_data['use_catalog_sort1'] == 0){
			$self_cats = $db->getRow("select * from cats where uri=?s", $request->uri);
			$ret_data['use_catalog_sort1']=1;
		}
		if (!$self_cats['id'] || (int)$self_cats['id'] <= 0) {
			return array("status" => "ok", "msg" => "", "detail_categorys" => array(), "parents" => array());
		}

		$childrens = self::get_detail_categorys((object)array(
			"parentId" => $self_cats['id'],
			"page" => $request->page,
			"page_size" => $request->page_size,
			"brand" => $request->brand,
			"use_catalog_sort1" => $ret_data['use_catalog_sort1']
		));

        unset($childrens['msg']);
        unset($childrens['status']);
        unset($childrens['err']);
        if($ret_data['use_catalog_sort1'] == 1){
        	$parents=self::get_category_parents((object)array("id"=>$self_cats['id']));
		}else{
			$parents=self::get_group_parents((object)array("id"=>$self_cats['id']));
			$self_cats['isProductView'] = 1;
			$self_cats['name'] = $self_cats['group_name'];
		}
        return array("status"=>"ok","msg"=>"","childrens"=>$childrens,"parents"=>$parents,"self"=>$self_cats);
    }

	public static function get_uri_data_market($request){
		if(empty($request->uri)) return self::_error_arr("Не указан uri");
        $db = DB::getInstance("libr");
        $self_cats=$db->getRow("select * from cats where uri=?s",$request->uri);
        if(!$self_cats['id'] || (int)$self_cats['id']<=0) return array("status"=>"ok","msg"=>"","detail_categorys"=>array(),"parents"=>array());
        $childrens=self::get_detail_categorys_market((object)array("parentId"=>$self_cats['id'],"page"=>$request->page,"page_size"=>$request->page_size,"brand"=>$request->brand));
        unset($childrens['msg']);
        unset($childrens['status']);
        unset($childrens['err']);
		$parents=self::get_category_parents((object)array("id"=>$self_cats['id']));
        return array("status"=>"ok","msg"=>"","childrens"=>$childrens,"parents"=>$parents,"self"=>$self_cats);
	}

	public static function get_detail_categorys($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
		mysqli_autocommit($db1->get_conn(), FALSE);

		if($request->use_catalog_sort1 == 0){
			$group_id = $request->parentId;
			$parentId = $db1->getOne("SELECT library_category_id FROM detail_group WHERE id=?i",$request->parentId);
			if($parentId == 0){
				$use_only_group_details = 1;
			}
			else{
				$use_only_group_details = 0;
			}
		}else{
			$parentId = (!empty($request->parentId) && (int)$request->parentId > 1) ? $request->parentId : ($request->use_catalog_sort1 == 0 ? 0 : 1);
		}

		$page_size = !empty($request->page_size) ? $request->page_size : 15;
		$brand = !empty($request->brand) ? $request->brand : null;

		$sql = ($request->use_catalog_sort1 == 0) ?
			"SELECT * FROM detail_group WHERE in_group=?i and main_company_id = ?i ORDER BY detail_group_order" :
			"SELECT * FROM cats WHERE parentId=?i ORDER BY cSort";

		$res = ($request->use_catalog_sort1 == 0) ? $db1->getAll($sql, $group_id, $_SESSION['main_company']) : $db->getAll($sql, $parentId);

		if (!$res) {
			if($request->use_catalog_sort1 == 0){
				$group_details = $db1->getAll("SELECT detail_id as id FROM detail_group_details WHERE detail_group_id = ?i and main_company_id = ?i", $group_id, $_SESSION['main_company']);
				$groupDetailsIds = array_column($group_details, 'id');
				$groupDetailsIds = array_map('intval', $groupDetailsIds);
				if($use_only_group_details == 1){
					$det_info_query = "SELECT subquery.id, subquery.article, subquery.article_raw, subquery.brand, subquery.name
					FROM (
						SELECT d.id, d.article, d.article_raw, b.brand, d.name
						FROM details d
						LEFT JOIN brands b ON b.brand_id = d.brand_id
						WHERE d.id in (?b) ?p
						GROUP BY d.id
						ORDER BY d.id";

					$filter = ' ';
					if(isset($brand)){
						$filter = $db->parse(" and b.brand =?s", $brand);
					}

					if (!empty($request->page)) {
						$page = $request->page;
						$offset = $page_size * ($page - 1);
						$det_info_query .= " LIMIT " . $offset . "," . $page_size ." ) AS subquery;";
					} else {
						$det_info_query .= " LIMIT 0," . $page_size." ) AS subquery;";
					}

					$det_info_result = $db->getAll($det_info_query, $groupDetailsIds, $filter);

					$details_count_sql = "SELECT COUNT(*) AS total_count
					FROM (
						SELECT d.id
						FROM details d
						LEFT JOIN brands b ON b.brand_id = d.brand_id
						WHERE d.id in (?b) ?p
						GROUP BY d.id, d.article, d.article_raw, d.name
						ORDER BY d.id
					) AS subquery;";
					$details_count = $db->getOne($details_count_sql, $groupDetailsIds, $filter);
				}
				else{
					$det_info_query = "SELECT subquery.id, subquery.article, subquery.article_raw, subquery.brand, subquery.name
					FROM (
						SELECT d.id, d.article, d.article_raw, b.brand, d.name
						FROM details d
						LEFT JOIN brands b ON b.brand_id = d.brand_id
						WHERE d.categoryId = ?i || d.id in (?b) ?p
						GROUP BY d.id
						ORDER BY d.id";

					$filter = ' ';
					if(isset($brand)){
						$filter = $db->parse(" and b.brand =?s", $brand);
					}

					if (!empty($request->page)) {
						$page = $request->page;
						$offset = $page_size * ($page - 1);
						$det_info_query .= " LIMIT " . $offset . "," . $page_size ." ) AS subquery;";
					} else {
						$det_info_query .= " LIMIT 0," . $page_size." ) AS subquery;";
					}

					$det_info_result = $db->getAll($det_info_query, $parentId, $groupDetailsIds, $filter);
					$details_count_sql = "SELECT COUNT(*) AS total_count
					FROM (
						SELECT d.id
						FROM details d
						LEFT JOIN brands b ON b.brand_id = d.brand_id
						WHERE d.categoryId = ?i || d.id in (?b) ?p
						GROUP BY d.id, d.article, d.article_raw, d.name
						ORDER BY d.id
					) AS subquery;";
					$details_count = $db->getOne($details_count_sql, $parentId, $groupDetailsIds, $filter);
				}
			}else{
				$det_info_query = "SELECT subquery.id, subquery.article, subquery.article_raw, subquery.brand, subquery.name
				FROM (
					SELECT d.id, d.article, d.article_raw, b.brand, d.name
					FROM details d
					LEFT JOIN brands b ON b.brand_id = d.brand_id
					WHERE d.categoryId = ?i ?p
					GROUP BY d.id
					ORDER BY d.id";

				$filter = ' ';
				if(isset($brand)){
					$filter = $db->parse(" and b.brand =?s", $brand);
				}

				if (!empty($request->page)) {
					$page = $request->page;
					$offset = $page_size * ($page - 1);
					$det_info_query .= " LIMIT " . $offset . "," . $page_size ." ) AS subquery;";
				} else {
					$det_info_query .= " LIMIT 0," . $page_size." ) AS subquery;";
				}

				$det_info_result = $db->getAll($det_info_query, $parentId, $filter);
				$details_count_sql = "SELECT COUNT(*) AS total_count
				FROM (
					SELECT d.id
					FROM details d
					LEFT JOIN brands b ON b.brand_id = d.brand_id
					WHERE d.categoryId = ?i ?p
					GROUP BY d.id, d.article, d.article_raw, d.name
					ORDER BY d.id
				) AS subquery;";
				$details_count = $db->getOne($details_count_sql, $parentId, $filter);
			}

			// $det_info_result = $db->getAll($det_info_query, $parentId, $filter);

			$detailIds = array_column($det_info_result, 'id');
			$detailIds = array_map('intval', $detailIds);

			$detailsInfo = $db->getAll("select * from details_info where detail_id in (?b)", $detailIds);

			$tempResults = [];
			foreach ($det_info_result as $info) {
				$detail_id = $info['id'];

				if (!isset($tempResults[$detail_id])) {
					$tempResults[$detail_id] = [
						"id" => $detail_id,
						"name" => ["name" => "name", "value" => $info['name']],
						"article" => ["name" => "article", "value" => $info['article']],
						"article_raw" => ["name" => "article_raw", "value" => $info['article_raw']],
						"brand" => ["name" => "brand", "value" => $info['brand']],
						"images" => [],
					];
					$detail_images = Search::get_detail_images((object) array("article" => $tempResults[$detail_id]['article']['value'], "brand" => $tempResults[$detail_id]['brand']['value']));
					$tempResults[$detail_id]['images'] = $detail_images['images'];
				}
			}
			
			foreach ($detailsInfo as $info) {
				$detail_id = $info['detail_id'];

				if (isset($tempResults[$detail_id])) {
					$tempResults[$detail_id][$info['name']]['value'] = $info['value'];
				}
			}

			if(empty($_SESSION['my_sklad_id'])){
				$sklad_details = $db1->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type FROM sklad_details sd
				LEFT JOIN sklad s ON (s.id=sd.sklad_id)
				WHERE sd.detail_id IN (?b)
				AND sd.sklad_id IN (SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0) AND sd.invent_blocked=0 AND sd.deleted=0 AND (sd.count-sd.reserved_count)>0", $detailIds, $_SESSION['main_company']);	
			}
			else{
				$sklad_details = $db1->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type FROM sklad_details sd
				LEFT JOIN sklad s ON (s.id=sd.sklad_id)
				WHERE sd.detail_id IN (?b)
				AND sd.sklad_id = ?i AND sd.invent_blocked=0 AND sd.deleted=0 AND (sd.count-sd.reserved_count)>0", $detailIds, $_SESSION['my_sklad_id']);	
			}

			if($request->use_catalog_sort1 == 1){
				$brands = $db->getAll("SELECT DISTINCT b.brand AS brand
				FROM details d
				LEFT JOIN brands b ON b.brand_id = d.brand_id
				WHERE d.categoryId = ?i
				GROUP BY d.id, d.article, d.article_raw, d.name
				ORDER BY d.id;", $parentId);
			}else{
				$brands = $db->getAll("SELECT DISTINCT b.brand AS brand
				FROM details d
				LEFT JOIN brands b ON b.brand_id = d.brand_id
				WHERE d.id in (?b)
				GROUP BY d.id, d.article, d.article_raw, d.name
				ORDER BY d.id;", $detailIds);
			}
			$brands = array_reduce($brands, function($carry, $item) {
				$carry[] = $item['brand'];
				return $carry;
			}, []);


        	$sklad_details = Search::get_sale_price($sklad_details,0,'',array(),$db1,0);

			foreach ($tempResults as &$cat_det) {

				foreach ($sklad_details as $sklad_det) {
					if ($cat_det['id'] == $sklad_det['detail_id']) {
						$cat_det['has_sklad'][] = $sklad_det;
					}
				}
			}

			usort($tempResults, function ($a, $b) {
				if (isset($a['has_sklad']) && !isset($b['has_sklad'])) {
					return -1; // $a с наличием has_sklad должно быть первым
				} elseif (!isset($a['has_sklad']) && isset($b['has_sklad'])) {
					return 1; // $b с наличием has_sklad должно быть первым
				} else {
					return 0; // сохраняем порядок для деталей без has_sklad или с has_sklad
				}
			});

			$pages=ceil($details_count/$page_size);
			// print_r($db->getStats());
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['detail_categorys'] = array();
			$ret['category_details'] = array_values($tempResults);
			$ret['brands'] = $brands;
			$ret['pages'] = $pages;
			if (!empty($request->page)) $ret['selected_page']=$request->page;
			return $ret;
		}

		$ret = array();

		if (is_array($res) && count($res) > 0) {
			$ret['status'] = "ok";
			$ret['err'] = "";
			$ret['detail_categorys'] = $res;
			$ret['msg'] = "";
		} else {
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['detail_categorys'] = array();
		}

	    return $ret;
	}

	public static function get_all_categorys($request) {
		preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);
	
		$db1 = DB::getInstance();

		$sqlConfig = "SELECT use_catalog_sort1,disabled_categorys,company_id FROM company_sites WHERE site_name = ?s";
		$ret_data = $db1->getRow($sqlConfig, str_replace("www.", "", $origin[1]));

		if(!empty($ret_data) && (int)$ret_data['use_catalog_sort1'] == 0){
			return self::get_detail_groups($db1, $ret_data['company_id']);
		}

		$file = '../cache/jetparts/catalog/catalog.json';
		$jsonData = file_get_contents($file);
		$res = json_decode($jsonData, true);
		if($res['create_date'] != date('d-m-Y')){
			self::get_all_categorys_db();
			$jsonData = file_get_contents($file);
			$res = json_decode($jsonData, true);
			$categories = $res['categories'];
		}
		else{
			$categories = $res['categories'];
		}
		// echo($categories);
		$disabled_categorys = json_decode($ret_data['disabled_categorys'],true);

		if(!empty($disabled_categorys)){
			$categories = self::filterCategories($categories, $disabled_categorys);
		}
	
		$res['categories'] = $categories;
		
		return $res;
	}

	public static function get_all_categorys_market($request) {
		preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);
	
		$db1 = DB::getInstance();

		$sqlConfig = "SELECT use_catalog_sort1,disabled_categorys,company_id FROM company_sites WHERE site_name = ?s";
		$ret_data = $db1->getRow($sqlConfig, str_replace("www.", "", $origin[1]));

		if(!empty($ret_data) && (int)$ret_data['use_catalog_sort1'] == 0){
			return self::get_detail_groups($db1, $ret_data['company_id']);
		}
		
		$file = '../cache/jetparts/catalog/catalog.json';
		$jsonData = file_get_contents($file);
		$res = json_decode($jsonData, true);
		if($res['create_date'] != date('d-m-Y')){
			self::get_all_categorys_db();
			$jsonData = file_get_contents($file);
			$res = json_decode($jsonData, true);
			$categories = $res['categories'];
		}
		else{
			$categories = $res['categories'];
		}

		$disabled_categorys = json_decode($ret_data['disabled_categorys'],true);
		$filteredCategories = self::filterCategoriesMarket($categories, $disabled_categorys);
	
		$res['categories'] = $filteredCategories;
		
		return $res;
	}

	public static function get_detail_groups($db, $company_id, &$result = []) {
		$query = "SELECT id, group_name as name, uri FROM detail_group WHERE in_group = 0 and main_company_id = ?i";
		$res['categories'] = $db->getAll($query, $company_id);
		if (count((array)$res['categories']) > 0) {
			foreach ($res['categories'] as &$item) {
				$parentId = $item['id'];
				$item['subcategories'] = self::get_detail_groups_subcategories($parentId, $db);
				if(empty($item['subcategories'])){
					$item['isProductView'] = 1;
				}
				else{
					$item['isProductView'] = 0;
				}
			}
		}
		return $res;
	}
	
	private static function get_detail_groups_subcategories($parentId, $db) {
		$subRes = $db->getAll("SELECT id, group_name as name, uri FROM detail_group WHERE in_group=?i", $parentId);
	
		if (!empty($subRes)) {
			foreach ($subRes as &$subcategory) {
				$subcategory['subcategories'] = self::get_detail_groups_subcategories($subcategory['id'], $db);
				if(empty($subcategory['subcategories'])){
					$subcategory['isProductView'] = 1;
				}
				else{
					$item['isProductView'] = 0;
				}
			}
		}
	
		return $subRes;
	}
	
	private static function filterCategories($categories, $disabled_categorys) {
		$filteredCategories = [];

		foreach ($categories as $category) {
			if (!in_array($category['id'], $disabled_categorys)) {
				if (isset($category['subcategories']) && !empty($category['subcategories'])) {
					$filteredSubcategories = self::filterCategories($category['subcategories'], $disabled_categorys);
					$category['subcategories'] = $filteredSubcategories;
				}

				$filteredCategories[] = $category;
			}
		}

		return $filteredCategories;
	}

	private static function filterCategoriesMarket($categories, $disabled_categorys) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
		mysqli_autocommit($db1->get_conn(), FALSE);

		$filteredCategories = [];
		foreach ($categories as $category) {
			if (!in_array($category['id'], $disabled_categorys)) {
				$includeCategory = false;
				// Проверяем наличие подкатегорий
				if (isset($category['subcategories']) && !empty($category['subcategories'])) {
					// Рекурсивно вызываем функцию filterCategories() для подкатегорий
					$filteredSubcategories = self::filterCategories($category['subcategories'], $disabled_categorys);
					// Если после фильтрации остались подкатегории, добавляем их в отфильтрованные категории
					if (!empty($filteredSubcategories)) {
						$category['subcategories'] = $filteredSubcategories;
						$includeCategory = true;
					}
				} 
				else if((int)$category['isProductView'] == 1){
					$hasProductsQuery = "SELECT id FROM details WHERE categoryId = ?i";
	
					$det_info_result = $db->getAll($hasProductsQuery, $category['id']);
	
					$detailIds = array_column($det_info_result, 'id');
					$detailIds = array_map('intval', $detailIds);
	
					$companyQuery = "SELECT u.main_company_id from users u where u.is_main = 1 and u.use_jetparts = 1 and u.admin_disabled = 0 and u.finance_disabled = 0";
					$company_ids = $db1->getCol($companyQuery);
					$company_ids = array_map('intval', $company_ids);
	
					$sklad_details_query = "select COUNT(sd.detail_id) from sklad_details sd
					LEFT JOIN sklad s ON s.id = sd.sklad_id
					Where sd.detail_id in (?b) and s.sklad_use_in_jetparts = 1 and sd.price != 0
					and s.company_id in (?b)
					and sd.count - sd.reserved_count > 0
					GROUP BY sd.detail_id ";
					
					$sklad_details_result = $db1->getOne($sklad_details_query, $detailIds, $company_ids);
					// Если в категории есть товары, включаем ее в отфильтрованный список
					if ($sklad_details_result > 0) {
						$includeCategory = true;
					}
				}
				// Если категория имеет действительные подкатегории или товары, добавляем ее в отфильтрованный список
				if ($includeCategory) {
					$filteredCategories[] = $category;
				}
			}
		}
		return $filteredCategories;
	}	

	public static function get_all_categorys_db() {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
	
		$sql = "SELECT *
		FROM cats
		WHERE parentId=1 ORDER BY cSort";
		$res['categories'] = $db->getAll($sql);
	
		foreach ($res['categories'] as &$item) {
			$parentId = $item['id'];
			$item['subcategories'] = self::getSubcategories($parentId, $db);
		}
		
		$res['create_date'] = date('d-m-Y');
		$file = '../cache/jetparts/catalog/catalog.json';
		$jsonData = json_encode($res);
		file_put_contents($file, $jsonData);
	}
	
	private static function getSubcategories($parentId, $db) {
		$subRes = $db->getAll("SELECT * FROM cats WHERE parentId=?i ORDER BY cSort", $parentId);
		
		foreach ($subRes as &$subcategory) {
			$subcategory['subcategories'] = self::getSubcategories($subcategory['id'], $db);
		}
		
		return $subRes;
	}
	

    private static function get_last_child($child,$i){
        if(isset($child['child'])){
            return self::get_last_child($child['child'],++$i);
        }
        else {
            return $i;
        }
    }

    public static function get_category_parents($request){
        if((int)$request->id<=0) return "error";
        //if((int)$request->id==1) return "0";
        $db = DB::getInstance("libr");
        $category=$db->getRow("select * from cats where id=?i",$request->id);
        //print_r($category);
        //file_put_contents("/var/log/shop/api/get_category_parents.log",print_r($category,true)."\n",FILE_APPEND);
        if((int)$category['parentId']==1 || !$category) {
            //file_put_contents("/var/log/shop/api/get_category_parents.log","root:".print_r($category,true)."\n",FILE_APPEND);
            return $category;
        }
        else {
            if((int)$category['parentId']>0){
                $parent=self::get_category_parents((object)array("id"=>$category['parentId']));
                $child_i=self::get_last_child($parent,0);
                $child_i_str="";
                for($i=0; $i<=$child_i; $i++){
                    $child_i_str.="['child']";
                }
                eval("\$parent$child_i_str=\$category;");
                //file_put_contents("/var/log/shop/api/get_category_parents.log","child_i:".$child_i."\n",FILE_APPEND);
                //$child['child']=$category;
                //file_put_contents("/var/log/shop/api/get_category_parents.log","parent:".print_r($parent,true)."\nchild_i_str:".$child_i_str."\n",FILE_APPEND);
                return $parent;
            }
        }
        //$parent['child']=$category;
        return $ret=array("status"=>"ok","msg"=>"","parent"=>$parent);
    }     
	
	public static function get_group_parents($request){
        if((int)$request->id<=0) return "error";
        //if((int)$request->id==1) return "0";
        $db = DB::getInstance();
        $category=$db->getRow("select *, group_name as name from detail_group where id=?i and main_company_id = ?i",$request->id, $_SESSION['main_company']);
        //print_r($category);
		$category['isProductView'] = 0;
        //file_put_contents("/var/log/shop/api/get_category_parents.log",print_r($category,true)."\n",FILE_APPEND);
        if((int)$category['in_group']==0 || !$category) {
            //file_put_contents("/var/log/shop/api/get_category_parents.log","root:".print_r($category,true)."\n",FILE_APPEND);
            return $category;
        }
        else {
            if((int)$category['in_group']>0){
                $parent=self::get_group_parents((object)array("id"=>$category['in_group']));
                $child_i=self::get_last_child($parent,0);
                $child_i_str="";
                for($i=0; $i<=$child_i; $i++){
                    $child_i_str.="['child']";
                }
                eval("\$parent$child_i_str=\$category;");
                //file_put_contents("/var/log/shop/api/get_category_parents.log","child_i:".$child_i."\n",FILE_APPEND);
                //$child['child']=$category;
                //file_put_contents("/var/log/shop/api/get_category_parents.log","parent:".print_r($parent,true)."\nchild_i_str:".$child_i_str."\n",FILE_APPEND);
                return $parent;
            }
        }
        //$parent['child']=$category;
        return $ret=array("status"=>"ok","msg"=>"","parent"=>$parent);
    }  

	// public static function get_category_parents($request){
	// 	if ((int)$request->id <= 0) return "error";
		
	// 	$db = DB::getInstance("libr");
	// 	$category = $db->getRow("SELECT * FROM cats WHERE id=?i", $request->id);
		
	// 	if ((int)$category['parentId'] == 1 || !$category) {
	// 		return $category;
	// 	} else {
	// 		$parent = $category;
	// 		while ((int)$parent['parentId'] > 0) {
	// 			$parentId = $parent['parentId'];
	// 			$parent = $db->getRow("SELECT * FROM cats WHERE id=?i", $parentId);
	// 			$parent['child'] = $category;
	// 			$category = $parent;
	// 		}
	// 		return $parent['child'];
	// 	}
	// }

	public static function get_detail_category($request) {
	    $db = DB::getInstance();
			if(empty($request->category_id) || (int)$request->category_id==0){
				return self::_error_arr('нет данных');
			}
	    $sql="select i.id,i.name,i.markup from detail_category i where i.id=?i and i.main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->category_id,$_SESSION['main_company']);
	    if ($res){
			$ret['status']="ok";
			$ret['err']="";
			$ret['detail_category']=$res;
			$ret['msg']="";
	    }
		else {
			$ret['status']="ok";
			$ret['err']="";
			$ret['detail_category']=[];
			$ret['detail_category_users']=[];
			$ret['msg']="";
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_detail_categorys_market($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
		// $_SESSION['main_company']=35;
		mysqli_autocommit($db1->get_conn(), FALSE);

		if (!empty($request->parentId) && (int)$request->parentId > 1) {
			$parentId = $request->parentId;
		} else {
			$parentId = 1;
		}

		if (!empty($request->page_size)) {
			$page_size = $request->page_size;
		} else {
			$page_size = 15;
		}

		
		if (!empty($request->brand)) {
			$brand = $request->brand;
		}

		$sql = "SELECT *
				FROM cats
				WHERE parentId=?i ORDER BY cSort";

		$res = $db->getAll($sql, $parentId);

		if (!$res) {
			$det_info_query = "SELECT subquery.id
			FROM (
				SELECT d.id
				FROM details d
				LEFT JOIN brands b ON b.brand_id = d.brand_id
				WHERE d.categoryId = ?i ?p
				GROUP BY d.id
				ORDER BY d.id ) 
			AS subquery;";

			$filter = ' ';
			if(isset($brand)){
				$filter = $db->parse(" and b.brand =?s", $brand);
			}

			$det_info_result = $db->getAll($det_info_query, $parentId, $filter);

			$detailIds = array_column($det_info_result, 'id');
			$detailIds = array_map('intval', $detailIds);

			$companyQuery = "SELECT u.main_company_id from users u where u.is_main = 1 and u.use_jetparts = 1 and u.admin_disabled = 0 and u.finance_disabled = 0";
			$company_ids = $db1->getCol($companyQuery);
			$company_ids = array_map('intval', $company_ids);

			$sklad_details_query = "select sd.detail_id as id from sklad_details sd
			LEFT JOIN sklad s ON s.id = sd.sklad_id
			Where sd.detail_id in (?b) and s.sklad_use_in_jetparts = 1 and sd.price != 0
			and s.company_id in (?b)
			and sd.count - sd.reserved_count > 0
			GROUP BY sd.detail_id ";
			
			if (!empty($request->page)) {
				$page = $request->page;
				$offset = $page_size * ($page - 1);
				$sklad_details_query .= " LIMIT " . $offset . "," . $page_size ."";
			} else {
				$sklad_details_query .= " LIMIT 0," . $page_size."";
			}

			$sklad_details_result = $db1->getAll($sklad_details_query, $detailIds, $company_ids);
			$sklad_details_id = array_column($sklad_details_result, 'id');
			$sklad_details_id = array_map('intval', $sklad_details_id);
			// print_r($db1->getStats());

			$detailsInfo = $db->getAll("select * from details_info where detail_id in (?b)", $sklad_details_id);

			$det_info_query = "SELECT subquery.id, subquery.article, subquery.article_raw, subquery.brand, subquery.name
			FROM (
				SELECT d.id, d.article, d.article_raw, b.brand, d.name
				FROM details d
				LEFT JOIN brands b ON b.brand_id = d.brand_id
				WHERE d.id in (?b)
				GROUP BY d.id
				ORDER BY d.id 
			) AS subquery;";

			$det_info_result = $db->getAll($det_info_query, $sklad_details_id);
			
			$tempResults = [];
			foreach ($det_info_result as $info) {
				$detail_id = $info['id'];

				if (!isset($tempResults[$detail_id])) {
					$tempResults[$detail_id] = [
						"id" => $detail_id,
						"name" => ["name" => "name", "value" => $info['name']],
						"article" => ["name" => "article", "value" => $info['article']],
						"article_raw" => ["name" => "article_raw", "value" => $info['article_raw']],
						"brand" => ["name" => "brand", "value" => $info['brand']],
						"images" => [],
					];
					$detail_images = Search::get_detail_images((object) array("article" => $tempResults[$detail_id]['article']['value'], "brand" => $tempResults[$detail_id]['brand']['value']));
					$tempResults[$detail_id]['images'] = $detail_images['images'];
				}
			}
			
			foreach ($detailsInfo as $info) {
				$detail_id = $info['detail_id'];

				if (isset($tempResults[$detail_id])) {
					$tempResults[$detail_id][$info['name']]['value'] = $info['value'];
				}
			}

			// if($request->use_catalog_sort1 == 1){
				$brands = $db->getAll("SELECT DISTINCT b.brand AS brand
				FROM details d
				LEFT JOIN brands b ON b.brand_id = d.brand_id
				WHERE d.categoryId = ?i
				GROUP BY d.id, d.article, d.article_raw, d.name
				ORDER BY d.id;", $parentId);

				$brands = array_reduce($brands, function($carry, $item) {
					$carry[] = $item['brand'];
					return $carry;
				}, []);
			// }else{

			// }

			$pages=ceil(count((array)$detailsInfo)/$page_size);
			// print_r($db->getStats());
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['detail_categorys'] = array();
			$ret['category_details'] = array_values($tempResults);
			$ret['brands'] = $brands;
			$ret['pages'] = $pages;
			if (!empty($request->page)) $ret['selected_page']=$request->page;
			return $ret;
		}

		$ret = array();

		if (is_array($res) && count($res) > 0) {
			$ret['status'] = "ok";
			$ret['err'] = "";
			$ret['detail_categorys'] = $res;
			$ret['msg'] = "";
		} else {
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['detail_categorys'] = array();
		}

	    return $ret;
	}
}



?>
