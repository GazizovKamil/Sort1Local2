<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
* 
*/


class BilingGuides extends Model {

	private static function build_tree_structure($items, $parentId) {
		$tree = [];
	
		foreach ($items as $item) {
			if ($item['parentId'] == $parentId) {
				$children = self::build_tree_structure($items, $item['id']);
				if ($children) {
					$item['children'] = $children;
				}
				$tree[] = $item;
			}
		}
	
		return $tree;
	}

	public static function form_guide_sort1($request) {
		$db = DB::getInstance("main");
		session_id($request->sesskey);

		$sql = "SELECT * FROM guide_blocks";
		$results = $db->getAll($sql);
	
		if (!$results) {
			return self::_error_arr("Ошибка при получении данных из базы данных.");
		}

		$tree = self::build_tree_structure($results, 0);
		// echo print_r($tree);
		$html = self::build_html($tree);
		
		$filename = '../site_ruk_new/instruction.html';

		try {
			file_put_contents($filename, $html);

			$ret['status'] = "ok";
			$ret['err'] = "";
			$ret['msg'] = "HTML содержимое успешно записано в файл";
			return $ret;
		} catch (Exception $e) {
			return self::_error_arr("Ошибка при записи данных в файл: " . $e->getMessage());
		}
	}

	private static function build_html($node) {
		$html = '';
	
		$html .= '<!DOCTYPE html>
		<html lang="ru">
			<head>
				<title>Руководство пользователя. Инструкции</title>
				<meta charset="utf-8">
				<meta name="viewport" content="width=device-width">
				<link rel="shortcut icon" href="images/favicon.ico">
				<link rel="apple-touch-icon" href="images/apple-touch-icon.png">
				<link href="css/style.css" type="text/css" rel="stylesheet">	
			</head>
				<body>
					<div id="page">
						<div id="menu2">';
	
			foreach ($node as $child) {
				$html .= '<ul class="ulMenu2 ' . ((int)$child['parentId'] == 0 ? 'mainBlock' : 'subBlocks') . '">';
				$html .= '<li>' . $child['block_name'] . '</li>';
				$html .= '</ul>';
				if (isset($child['children'])) {
					$html .= '<ul class="ulMenu2 ' . ((int)$child['parentId'] == 0 ? 'subBlocks' : 'mainBlock') . '" style="display: none;" id="Components">';
					foreach ($child['children'] as $nodeChild) {
						$html .= '<li><a href="#'.$nodeChild['id'].'">' . $nodeChild['block_name'] . '</a></li>';
					}
					$html .= '</ul>';
				}
			}
		
		$html .= '</div>
			<div id="box-1-w">
				<div id="box-1">';
			
			$divs = implode(array_map(function($item) {
				return implode(array_map(function($child) {
					$description = $child['description'];
					
					// Используем регулярное выражение для поиска тегов img и вставки контента в блок imgFull
					$description = preg_replace('/<img\b[^>]*>/', '<div class="imgFull">
						<u>$0</u>
						<div class="imagesInt">
							$0
						</div>
					</div>', $description);
					
					// $description = preg_replace('/<span[^>]*>.*?<\/span>/i', '', $description);
					// $description = preg_replace('/<p[^>]*>.*?<\/p>/i', '', $description);

					return '<div id="'.$child['id'].'">'.$description.'</div>';
				}, $item['children']));
			}, $node));
				
			$html = $html . " " . $divs;
			$html .= '<div class="clear"></div></div></div>
			<script src="js/jquery-1.8.2.min.js"></script>
			<script src="js/main.js"></script>
			<script src="js/href.js"></script>
			<script src="js/window.js"></script>
			<script src="js/list.js"></script>
		</body>
		</html>';
		
		return $html;
	}
}
?>