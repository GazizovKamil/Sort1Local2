<?php
require 'vendor/autoload.php';
include "include/db_safe.inc.php";

$db = new SafeMySQL([
    'host' => '192.168.39.150',
    'user' => 'nur',
    'pass' => 'vD9DB7ds',
    'db'   => 'shop'
]);

$base_upload_dir = '/var/www/library_images/';
$sklad_id = 717;

$details = $db->getAll("SELECT detail_id, article, brand FROM sklad_details WHERE sklad_id = ?i", $sklad_id);

foreach ($details as $detail) {
    $article = strtoupper($detail['article']);
    $brand = strtoupper($detail['brand']);

    $query = $article . ' ' . $brand;
    $imageUrls = fetchImagesFromGoogle($query);

    foreach ($imageUrls as $url) {
        echo $url . "<br>";
    }
    
    // $image_count = 0;

    // foreach ($imageUrls as $image_url) {
    //     $image_data = @file_get_contents($image_url);
    //     if (!$image_data) continue;

    //     // $contains_watermark = checkForWatermark($image_data);
    //     // if ($contains_watermark) continue;

    //     $article_folder1 = preg_match("/^[а-яА-Я]+$/u", $article) ? substr($article, 0, 1) : substr($article, 0, 2);
    //     $upload_dir = $base_upload_dir . $article_folder1 . '/';
    //     if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    //     $image_index = $image_count + 1;
    //     $image_type = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
    //     $image_type = $image_type ?: 'jpg'; // fallback

    //     $unique_name = $article . '_' . $brand . '_' . $image_index . '.' . $image_type;
    //     $target_path = $upload_dir . $unique_name;

    //     file_put_contents($target_path, $image_data);

    //     $db->query(
    //         "INSERT INTO detail_photos (detail_id, company_id, filename, is_public) 
    //          VALUES (?i, ?i, ?s, ?i)",
    //         $detail['detail_id'], $_SESSION['main_company'], $article_folder1 . "/" . $unique_name, 1
    //     );

    //     $image_count++;
    //     if ($image_count >= 3) break;
    // }
}

function fetchImagesFromGoogle($query) {
    $encodedQuery = urlencode($query);
    $url = "https://www.google.com/search?q={$encodedQuery}&tbm=isch";

    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/123 Safari/537.36',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    curl_close($ch);
    file_put_contents('google_images.html', '');
    file_put_contents('google_images.html', $html);

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // Ищем <img> теги, у которых есть data-iurl (это прямая ссылка на изображение)
    $imageNodes = $xpath->query('//img[@data-iurl]');

    $imageUrls = [];
    foreach ($imageNodes as $img) {
        $imageUrls[] = $img->getAttribute('data-iurl');
        if (count($imageUrls) >= 10) break;
    }

    return $imageUrls;
}


function checkForWatermark($image_data) {
    $im = @imagecreatefromstring($image_data);
    if (!$im) return true;

    $width = imagesx($im);
    $height = imagesy($im);
    $corner_sample = imagecrop($im, ['x' => $width - 100, 'y' => $height - 50, 'width' => 100, 'height' => 50]);

    ob_start();
    imagepng($corner_sample);
    ob_end_clean();

    imagedestroy($im);
    imagedestroy($corner_sample);

    return false; // пока всегда возвращает false (доработка требуется)
}

echo "Загрузка завершена\n";
