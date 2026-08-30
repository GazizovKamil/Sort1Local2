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

class Crossdatas extends Model
{
	public static function search_brand_crossdata($request)
	{
		$db = DB::getInstance("libr");

		$brand = $request->brand;

		if (empty($brand)) {
			return array('status' => 'error', 'message' => 'Отсутствуют данные');
		}

		$queryBrands = "SELECT brand_id, brand, brand_raw FROM brands WHERE MATCH(brand, brand_raw) AGAINST (?s IN BOOLEAN MODE) Group by brand_id LIMIT 20";
		$brand = $brand . '*';
		$result = $db->getAll($queryBrands, $brand);

		if (!empty($result)) {
			return array('status' => 'ok', 'brands' => $result);
		} else {
			return array('status' => 'error', 'message' => 'Произошла ошибка при обновлении данных');
		}
	}

	public static function search_categorys_crossdata($request)
	{
		$db = DB::getInstance("libr");

		$name = $request->name;

		if (empty($name)) {
			return array('status' => 'error', 'message' => 'Отсутствуют данные');
		}

		$queryCategorys = "SELECT id, name FROM cats WHERE MATCH(name) AGAINST (?s IN BOOLEAN MODE) and isProductView = 1 LIMIT 20";
		$name = $name . '*';
		$result = $db->getAll($queryCategorys, $name);

		if (!empty($result)) {
			return array('status' => 'ok', 'categorys' => $result);
		} else {
			return array('status' => 'error', 'message' => 'Произошла ошибка при обновлении данных');
		}
	}

	public static function upload_images_crossdata($request)
	{
		// Считываем данные из запроса
		$link = $request->link;
		$brandName = $request->brand_name;

		if (empty($link)) {
			throw new Exception('Ссылка на Яндекс.Диск не может быть пустой.');
		}

		// OAuth токен для доступа к API Яндекс.Диск
		$token = 'y0_AgAEA7qjL-D9AAxAfAAAAAENdjD7AADbkwG5hI9Hj6pxWVz9H2PAWQSs-g';

		// Сообщение клиенту о начале обработки
		$response = array('status' => 'ok', 'message' => 'Запрос обрабатывается. Результаты будут доступны позже.');

		// Отправляем немедленный ответ клиенту
		ignore_user_abort(true); // Позволяет продолжить выполнение скрипта, даже если клиент разорвал соединение

		header('Content-Type: application/json');
		echo json_encode($response);

		if (function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} else {
			header('Connection: close');
			header('Content-Length: ' . ob_get_length());
			flush();
		}

		// Основная обработка начинается здесь
		$fields = '_embedded.items.name,_embedded.items.type,_embedded.items.path,_embedded.items.mime_type';
		$limit = 1000;
		$offset = 0;
		$allImages = [];
		$failedImages = [];

		do {
			// 	// Выполняем запрос к API Яндекс.Диск для получения метаинформации
			$ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources?path=' . urlencode($link) . '&fields=' . $fields . '&limit=' . $limit . '&offset=' . $offset);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			$res = curl_exec($ch);
			curl_close($ch);

			$res = json_decode($res, true);

			if (!empty($res['_embedded']['items'])) {
				foreach ($res['_embedded']['items'] as $item) {
					if ($item['type'] === 'file' && strpos($item['mime_type'], 'image/') === 0) {
						$allImages[] = [
							'name' => $item['name'],
							'path' => $item['path'],
							'mime_type' => $item['mime_type']
						];
					}
				}
			}
			$offset += $limit;

		} while (!empty($res['_embedded']['items']) && count($res['_embedded']['items']) === $limit);


		// Создаём локальную директорию, если она не существует
		$localFolder = '/var/www/library_images/';
		if (!is_dir($localFolder)) {
			mkdir($localFolder, 0777, true);
		}

		// Обрабатываем каждое изображение
		foreach ($allImages as $image) {
			$imageUrl = $image['path'];
			$extension = pathinfo($image['name'], PATHINFO_EXTENSION); // Получаем расширение файла

			if (!empty($imageUrl)) {
				$result = self::GetPreview($imageUrl, $localFolder, $token, $brandName, $extension);

				if ($result === false) {
					$failedImages[] = $image['name'];
				}
			}
		}
		// Запись результатов в логи или любая другая необходимая обработка после выполнения
		if (!empty($failedImages)) {
			error_log('Произошла ошибка при обновлении данных: ' . implode(', ', $failedImages));
		}
	}

	public static function GetPreview($path, $localFolder, $oauthToken, $brandName, $extension)
	{
		try {
			if (strpos($path, 'disk:/') === 0) {
				$path = substr($path, 6);
			}

			$url = "https://webdav.yandex.ru/" . $path . "?preview&size=1920x";
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: OAuth ' . $oauthToken
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($httpCode == 200) {
				$originalName = pathinfo($path, PATHINFO_FILENAME);
				$parts = explode('_', $originalName);
				$article = preg_replace('/[^a-zA-Z0-9]/', '', $parts[0]);
				$brand = $brandName;

				if (preg_match("/^[а-яА-Я]+$/u", $article)) {
					$article_folder1 = strtoupper(substr($article, 0, 1));
				} else if (preg_match("/^[a-zA-Z0-9]+$/u", $article)) {
					$article_folder1 = strtoupper(substr($article, 0, 2));
				} else {
					curl_close($ch);
					return false; // Некорректный артикул
				}

				$upload_dir = $localFolder . $article_folder1 . '/';
				if (!file_exists($upload_dir)) {
					mkdir($upload_dir, 0777, true);
				}

				$files_in_folder = scandir($upload_dir);

				$image_files = array_filter($files_in_folder, function ($file) use ($article, $brand) {
					return preg_match('/^' . preg_quote($article . '_' . $brand) . '_\d+\.(jpg|jpeg|png|gif)$/i', $file);
				});

				$image_count = count($image_files);

				$image_index = $image_count + 1;

				$unique_name = $article . '_' . $brand . '_' . $image_index . '.' . $extension;
				$target_path = $upload_dir . $unique_name;

				if (file_put_contents($target_path, $response) === false) {
					curl_close($ch);
					return false; // Ошибка записи файла
				}
			} else {
				curl_close($ch);
				return false; // Ошибка получения изображения
			}

			curl_close($ch);
			return true; // Успешно
		} catch (Exception $ex) {
			return false; // Обработка исключений
		}
	}
}
?>