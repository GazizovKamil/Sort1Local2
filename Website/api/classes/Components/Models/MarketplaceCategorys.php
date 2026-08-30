<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\SafeMySQL;
use Sort1API\Components\Config;

//require 'vendor/autoload.php';

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class MarketplaceCategorys extends Model {

	public static function get_settings_avito($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
		
		if(!empty($request->in_group) && (int)$request->in_group > 0) {
			$in_group = $request->in_group;
		} else {
			// $in_group = 1;
			$in_group = 0;
		}
	
		// $sql = "SELECT c.id ,c.name, ac.name AS category_marketplaces_name
		// 		FROM cats AS c
		// 		LEFT JOIN category_marketplaces AS cm ON cm.category_id = c.id
		// 		LEFT JOIN avito_categorys AS ac ON cm.marketplace_category_id = ac.id
		// 		WHERE c.parentId = ?i;";
	
		// $resGlobal = $db->getAll($sql, $in_group);

		// if ($_SESSION['main_company'] != 35) {
			// $catsId = array_map('intval', array_column($resGlobal, 'id'));

			// $sql = "SELECT cm.category_id, cm.marketplace_category_id AS category_avito_id
			// FROM category_marketplaces_user AS cm
			// WHERE cm.category_id in (?b) and cm.main_company_id = ?i";
	
			$sql = "SELECT 
            c.id, 
            c.group_name as name, 
            cm.category_id, 
            c.library_category_id, 
            cm.marketplace_category_id AS category_avito_id,
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM detail_group dg 
                    WHERE dg.in_group = c.id AND dg.main_company_id = ?i
                ) THEN '1'
                ELSE '0'
            END AS has_in_group  -- Устанавливаем переменную в зависимости от наличия деталей в in_group
        FROM detail_group AS c
        LEFT JOIN category_marketplaces_user AS cm 
        ON c.id = cm.category_id AND cm.main_company_id = ?i
        WHERE c.in_group = ?i AND c.main_company_id = ?i";

$resLocal = $db1->getAll($sql, $_SESSION['main_company'], $_SESSION['main_company'], $in_group, $_SESSION['main_company']);

			$catsAvitoId = array_map('intval', array_column($resLocal, 'category_avito_id'));
			$catsLibrId = array_map('intval', array_column($resLocal, 'library_category_id'));
			
			$sqlConfig = "SELECT ac.name AS category_marketplaces_name, 
								ac.id AS category_avito_id
						FROM avito_categorys AS ac
						WHERE ac.id IN (?b)";

			$resConfig = $db->getAll($sqlConfig, $catsAvitoId);		

			$sqlView = "SELECT ac.isProductView AS view, 
							ac.id
						FROM cats AS ac
						WHERE ac.id IN (?b)";

			$resView = $db->getAll($sqlView, $catsLibrId);				

			$resConfigIndexed = array_column($resConfig, 'category_marketplaces_name', 'category_avito_id');
			$resViewIndexed = array_column($resView, 'view', 'id');

			foreach ($resLocal as &$localItem) {
				$avitoId = $localItem['category_avito_id'];
				$libId = $localItem['library_category_id'];

				if (isset($resConfigIndexed[$avitoId])) {
					$localItem['category_marketplaces_name'] = $resConfigIndexed[$avitoId];
				} else {
					$localItem['category_marketplaces_name'] = null;
				}

				if (isset($resViewIndexed[$libId])) {
					$localItem['view'] = $resViewIndexed[$libId];
				} else {
					$localItem['view'] = "1";
				}
			}
			// print_r($resLocal);
			// print_r($resGlobal);
			
			// $localMapping = array_column($resLocal, 'category_marketplaces_name', 'category_id');

			// foreach ($resGlobal as &$globalItem) {
			// 	$categoryId = $globalItem['id'];

			// 	if (isset($localMapping[$categoryId])) {
			// 		$globalItem['category_marketplaces_name'] = $localMapping[$categoryId];
			// 	}
			// }
		// }
		
		if (is_array($resLocal) && count($resLocal) > 0) {
			$ret['status'] = "ok";
			$ret['err'] = "";
			// $ret['avito_categorys'] = $resGlobal;
			$ret['avito_categorys'] = $resLocal;
			$ret['msg'] = "";
		} else {
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['avito_categorys'] = array();
		}
		
		if ($ret['status'] == "err") {
			return self::_error_arr($ret['err']);
		} else {
			return $ret;
		}
	}

	public static function toggle_marketplace_unbinding($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
	
		// if ($_SESSION['main_company'] == 35) {
		// 	$result = $db->query("DELETE FROM category_marketplaces WHERE category_id = ?i AND marketplace_id = 2", $request->category_id);
			
		// 	if ($db->affectedRows()) {
		// 		mysqli_commit($db->get_conn());

		// 		$sql = "SELECT c.id,c.name, ac.name AS category_marketplaces_name
		// 		FROM cats AS c
		// 		LEFT JOIN category_marketplaces AS cm ON cm.category_id = c.id
		// 		LEFT JOIN avito_categorys AS ac ON cm.marketplace_category_id = ac.id
		// 		WHERE c.id = ?i;";
	
		// 		$res = $db->getRow($sql, $request->category_id);

		// 		$ret['status'] = "ok";
		// 		$ret['err'] = "";
		// 		$ret['msg'] = "";
		// 		$ret['category'] = $res;
		// 	} else {
		// 		$ret['status'] = "err";
		// 		$ret['err'] = "Не получилось отвязать";
		// 	}
		// }
		// else{
			$result = $db1->query("DELETE FROM category_marketplaces_user WHERE category_id = ?i AND marketplace_id = 2 AND main_company_id = ?i", $request->category_id, $_SESSION['main_company']);
			
			if ($db1->affectedRows()) {
				// $sql = "SELECT c.id,c.name
				// FROM cats AS c
				// WHERE c.id = ?i;";

				$sql = "SELECT c.id,c.group_name as name
				FROM detail_group AS c
				WHERE c.id = ?i;";

				// $sqlConfig = "SELECT cm.category_id, ac.name AS category_marketplaces_name
				// FROM category_marketplaces_user AS cm
				// LEFT JOIN avito_categorys_user AS ac ON cm.marketplace_category_id = ac.id
				// WHERE cm.category_id = ?i and cm.main_company_id = ?i";
	
				$res = $db1->getRow($sql, $request->category_id);
				// $resConfig = $db1->getRow($sqlConfig, $request->category_id, $_SESSION['main_company']);
				$res['category_marketplaces_name'] = null;

				$ret['status'] = "ok";
				$ret['err'] = "";
				$ret['msg'] = "";
				$ret['category'] = $res;
			} else {
				$ret['status'] = "err";
				$ret['err'] = "Не получилось отвязать";
			}
		// }
	
		if ($ret['status'] == "err") {
			return self::_error_arr($ret['err']);
		} else {
			return $ret;
		}
	}

	public static function get_avito_categorys($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));

		if(!empty($request->in_group) && (int)$request->in_group > 0) {
			$in_group = $request->in_group;
		} else {
			$in_group = 0;
		}

		$sql = "SELECT 
					ac.id, 
					ac.name, 
					ac.parentId, 
					EXISTS (SELECT 1 FROM avito_categorys sub_ac WHERE sub_ac.parentId = ac.id) AS has_children
				FROM 
					avito_categorys ac
				WHERE 
					ac.parentId = ?i;";
	
		$resGlobal = $db->getAll($sql, $in_group);

		// if ($_SESSION['main_company'] != 35) {
			// $catsId = array_map('intval', array_column($resGlobal, 'id'));

			// $sql = "SELECT cm.marketplace_category_id
			// 		FROM category_marketplaces_user cm
			// 		WHERE cm.marketplace_id = 2 and cm.main_company_id = ?i and cm.marketplace_category_id in (?b);";
	
			// $resLocal = $db1->getAll($sql, $_SESSION['main_company'], $catsId);
			
			// $resLocalIndexed = array_column($resLocal, null, 'marketplace_category_id');
			
			// foreach ($resGlobal as &$globalItem) {
			// 	$id = $globalItem['id'];
		
			// 	if (isset($resLocalIndexed[$id])) {
			// 		$globalItem['is_bind'] = 1;
			// 	}
			// }
		// }
		
		if (is_array($resGlobal) && count($resGlobal) > 0) {
			$ret['status'] = "ok";
			$ret['err'] = "";
			$ret['avito_categorys'] = $resGlobal;
			$ret['msg'] = "";
		} else {
			$ret['status'] = "ok";
			$ret['msg'] = "";
			$ret['avito_categorys'] = array();
		}
		
		if ($ret['status'] == "err") {
			return self::_error_arr($ret['err']);
		} else {
			return $ret;
		}
	}

	public static function toggle_marketplace_binding($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
	
		// if ($_SESSION['main_company'] == 35) {
		// 	$result = $db->query("INSERT INTO category_marketplaces (category_id, marketplace_id, marketplace_category_id) VALUES (?i, ?i, ?i)", $request->category_id, 2, $request->avito_category_id);
		// 	mysqli_commit($db->get_conn());
		// 	if ($result) {
		// 		$sql = "SELECT c.id,c.name, ac.name AS category_marketplaces_name
		// 		FROM cats AS c
		// 		LEFT JOIN category_marketplaces AS cm ON cm.category_id = c.id
		// 		LEFT JOIN avito_categorys AS ac ON cm.marketplace_category_id = ac.id
		// 		WHERE c.id = ?i;";
	
		// 		$res = $db->getRow($sql, $request->category_id);

		// 		$ret['status'] = "ok";
		// 		$ret['err'] = "";
		// 		$ret['msg'] = "";
		// 		$ret['category'] = $res;
		// 	} else {
		// 		$ret['status'] = "err";
		// 		$ret['err'] = "Не получилось привязать";
		// 	}
		// }
		// else{
			$result = $db1->query("INSERT INTO category_marketplaces_user (category_id, marketplace_id, marketplace_category_id, main_company_id) VALUES (?i, ?i, ?i, ?i)", $request->category_id, 2, $request->avito_category_id, $_SESSION['main_company']);
			
			$res_del=$db1->query("DELETE FROM unlinked_categories_avito_export WHERE category_id = ?i AND main_company_id = ?i", $request->category_id, $_SESSION['main_company']);

			if ($res_del) {
				// $sql = "SELECT c.id,c.name
				// FROM detail_group AS c
				// WHERE c.id = ?i;";

				$sql = "SELECT c.id,c.group_name as name
				FROM detail_group AS c
				WHERE c.id = ?i;";

				$sqlConfig = "SELECT ac.name AS category_marketplaces_name
				FROM avito_categorys AS ac
				WHERE ac.id = ?i";
	
				$res = $db1->getRow($sql, $request->category_id);
				$resConfig = $db->getRow($sqlConfig, $request->avito_category_id);

				$res['category_marketplaces_name'] = $resConfig['category_marketplaces_name'];
				
				$ret['status'] = "ok";
				$ret['err'] = "";
				$ret['msg'] = "";
				$ret['category'] = $res;
			} else {
				$ret['status'] = "err";
				$ret['err'] = "Не получилось отвязать";
			}
		// }
	
		if ($ret['status'] == "err") {
			return self::_error_arr($ret['err']);
		} else {
			return $ret;
		}
	}

	public static function save_category_avito($request) {
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
	
		if (isset($request->category_id) && (int)$request->category_id > 0) {
			$category_id = (int)$request->category_id;
		}
		if (isset($request->name)) {
			$name = $request->name;
		}
		if (isset($request->parent_id)) {
			$parent_id = $request->parent_id;
		}
	
		if($_SESSION['main_company'] == 35){
			if ($category_id == 0) {
				$result = $db->query("INSERT INTO avito_categorys (name, parentId) VALUES (?s, ?i)", $name, $parent_id);

				if ($result) {
					$lastInsertId = $db->insertId();
					$createdCategory = $db->getRow("SELECT * FROM avito_categorys WHERE id=?i", $lastInsertId);
					mysqli_commit($db->get_conn());

					$ret['status'] = "ok";
					$ret['err'] = "";
					$ret['msg'] = "";
					$ret['category'] = $createdCategory;
				} else {
					$ret['status'] = "err";
					$ret['err'] = "Не удалось добавить";
				}
			} else {
				$result = $db->query("UPDATE avito_categorys SET name = ?s WHERE id=?i", $name, $category_id);

				if ($result) {
					$updatedCategory = $db->getRow("SELECT * FROM avito_categorys WHERE id=?i", $category_id);
					mysqli_commit($db->get_conn());

					$ret['status'] = "ok";
					$ret['err'] = "";
					$ret['msg'] = "";
					$ret['category'] = $updatedCategory;
				} else {
					$ret['status'] = "err";
					$ret['err'] = "Не удалось изменить";
				}
			}
		}
		else{
			if ($category_id == 0) {
				$result = $db1->query("INSERT INTO avito_categorys_user (name, parentId, main_company_id) VALUES (?s, ?i, ?i)", $name, $parent_id, $_SESSION['main_company']);

				if ($result) {
					$lastInsertId = $db1->insertId();
					$createdCategory = $db1->getRow("SELECT * FROM avito_categorys_user WHERE id=?i and main_company_id = ?i", $lastInsertId,  $_SESSION['main_company']);

					$ret['status'] = "ok";
					$ret['err'] = "";
					$ret['msg'] = "";
					$ret['category'] = $createdCategory;
				} else {
					$ret['status'] = "err";
					$ret['err'] = "Не удалось добавить";
				}
			} else {
				$result = $db1->query("UPDATE avito_categorys_user SET name = ?s WHERE id=?i and main_company_id=?i", $name, $category_id, $_SESSION['main_company']);

				if ($result) {
					$updatedCategory = $db1->getRow("SELECT * FROM avito_categorys_user WHERE id=?i and main_company_id=?i", $category_id, $_SESSION['main_company']);

					$ret['status'] = "ok";
					$ret['err'] = "";
					$ret['msg'] = "";
					$ret['category'] = $updatedCategory;
				} else {
					$ret['status'] = "err";
					$ret['err'] = "Не удалось изменить";
				}
			}
		}

		if ($ret['status'] == "err") return self::_error_arr($ret['err']);
		else return $ret;
	}

	public static function get_unlinked_avito_categorys($request) {
		$db = DB::getInstance();

		if (empty($request->price_export_id)) {
			$ret['status'] = "err";
			$ret['err'] = "Не указан файл экспорта!";
			return self::_error_arr($ret['err']);
		}

		$categories = $db->getAll("
			SELECT ucae.category_id, dg.id as group_id, dg.group_name
			FROM unlinked_categories_avito_export ucae
			JOIN detail_group dg ON dg.id = ucae.category_id
			WHERE ucae.user_id = ?i
			AND ucae.main_company_id = ?i AND ucae.price_export_id = ?i", 
			$_SESSION['user_id'], 
			$_SESSION['main_company'],
			$request->price_export_id
		);

		if (!empty($categories)) {
			return array(
				"status" => "ok",
				"avito_categories" => $categories
			);
		} else {
			return array(
				"status" => "ok",
				"avito_categories" => array()
			);
		}
	}
}



?>
