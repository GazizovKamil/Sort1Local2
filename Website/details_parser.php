<?php
include "include/db_safe.inc.php";

$ret = array(
    'host' => '192.168.35.25',
    'user' => 'nur',
    'pass' => 'vD9DB7ds',
    'db' => 'crosses',
    'charset' => 'utf8',
);

$db=new SafeMySQL($ret);

$htmlString = '<div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67011" previewlistener="true"><span class="desktop-on2sml">Масла и автохимия</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67012" previewlistener="true"><span class="desktop-on2sml">GPS-навигаторы</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67013" previewlistener="true"><span class="desktop-on2sml">Прицепы</span></a></div>
<div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67014" previewlistener="true"><span class="desktop-on2sml">Экипировка</span></a></div><div class="desktop-1j2ovdc"><svg role="button" tabindex="0" aria-hidden="false" data-icon="chevron-narrow" viewBox="0 0 24 24" name="chevron-narrow" class="desktop-yhny54"><path d="M12 10.4142L16.2929 14.7071C16.6834 15.0976 17.3166 15.0976 17.7071 14.7071C18.0976 14.3166 18.0976 13.6834 17.7071 13.2929L12.7071 8.29289C12.3166 7.90237 11.6834 7.90237 11.2929 8.29289L6.29289 13.2929C5.90237 13.6834 5.90237 14.3166 6.29289 14.7071C6.68342 15.0976 7.31658 15.0976 7.70711 14.7071L12 10.4142Z"></path></svg><span class="desktop-on2sml">Шины, диски и колёса</span></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67016" previewlistener="true"><span class="desktop-13r6e3t">Легковые шины</span></a></div>
<div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67017" previewlistener="true"><span class="desktop-on2sml">Колпаки</span></a></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67018" previewlistener="true"><span class="desktop-on2sml">Диски</span></a></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67019" previewlistener="true"><span class="desktop-on2sml">Колёса</span></a></div>
<div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67020" previewlistener="true"><span class="desktop-on2sml">Мотошины</span></a></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67021" previewlistener="true"><span class="desktop-on2sml">Шины для грузовиков и спецтехники</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67022" previewlistener="true"><span class="desktop-on2sml">Аудио- и видеотехника</span></a></div>
<div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67023" previewlistener="true"><span class="desktop-on2sml">Аксессуары</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67024" previewlistener="true"><span class="desktop-on2sml">Инструменты</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67025" previewlistener="true"><span class="desktop-on2sml">Багажники и фаркопы</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67026" previewlistener="true"><span class="desktop-on2sml">Тюнинг</span></a></div>
<div class="desktop-1j2ovdc"><svg role="button" tabindex="0" aria-hidden="false" data-icon="chevron-narrow" viewBox="0 0 24 24" name="chevron-narrow" class="desktop-yhny54"><path d="M12 10.4142L16.2929 14.7071C16.6834 15.0976 17.3166 15.0976 17.7071 14.7071C18.0976 14.3166 18.0976 13.6834 17.7071 13.2929L12.7071 8.29289C12.3166 7.90237 11.6834 7.90237 11.2929 8.29289L6.29289 13.2929C5.90237 13.6834 5.90237 14.3166 6.29289 14.7071C6.68342 15.0976 7.31658 15.0976 7.70711 14.7071L12 10.4142Z"></path></svg><span class="desktop-on2sml">Запчасти</span></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67028" previewlistener="true"><span class="desktop-on2sml">Для мототехники</span></a></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67029" previewlistener="true"><span class="desktop-on2sml">Для автомобилей</span></a></div><div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67030" previewlistener="true"><span class="desktop-on2sml">Для грузовиков и спецтехники</span></a></div>
<div class="desktop-gng7vz"><a href="/autoload/documentation/templates/67031" previewlistener="true"><span class="desktop-on2sml">Для водного транспорта</span></a></div><div class="desktop-1j2ovdc"><a href="/autoload/documentation/templates/67032" previewlistener="true"><span class="desktop-on2sml">Противоугонные устройства</span></a></div>';

// Преобразование строки в объект DOM
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="UTF-8">' . $htmlString, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();

// Получение всех элементов div с классами 'desktop-1j2ovdc' и 'desktop-gng7vz'

$elements = $dom->getElementsByTagName('div');

$parentCategoryId = null;

foreach ($elements as $element) {
    $class = $element->getAttribute('class');
    $anchor = $element->getElementsByTagName('span')->item(0);

    if (strpos($class, 'desktop-1j2ovdc') !== false) {
        $category = trim($anchor->nodeValue);

        // Вставка данных в таблицу avito_categorys для родительской категории
        $db->query("INSERT INTO avito_categorys (name, sapCatId) VALUES (?s, ?i)", $category, 0);
        $parentCategoryId = $db->insertId();
    } else {
        $subCategory = trim($anchor->nodeValue);

        // Вставка данных в таблицу avito_categorys для подкатегории
        $data = [
            'name' => $subCategory,
            'sapCatId' => $parentCategoryId, // Используем родительский ID
        ];

        $db->query("INSERT INTO avito_categorys SET ?u", $data);
    }
}


// $sql = "SELECT * FROM cats";
// $categories = $db->getAll($sql);

// // Создаем пустой массив для хранения имен деталей
// $detailNames = array();

// foreach ($categories as $category) {
//     $categoryId = $category['id'];

//     // Напишите SQL-запрос для выборки деталей для текущей категории (замените 'details' и 'category_id' на ваши реальные названия таблиц и полей)
//     $sql = "SELECT name FROM details_parsed WHERE category_id = ?i AND name NOT LIKE '%Масса%' AND name NOT LIKE '%Вес%' AND name NOT LIKE '%Объем%' AND name NOT LIKE '%Длина%' AND name NOT LIKE '%Высота%' 
//     AND name NOT LIKE '%Ширина%' AND name NOT LIKE '%Упаковка%'";
//     $details = $db->getAll($sql, $categoryId);

//     $json = json_encode($details);

//     $updateSql = "UPDATE cats SET filter_config = ?s WHERE id = ?i";
//     $db->query($updateSql, $filterConfig, $categoryId);
// }

// $data = $db->getAll("SELECT di.id AS detail_info_id, di.detail_id, d.categoryId, di.name, di.value
//                      FROM details_info di
//                      INNER JOIN details d ON di.detail_id = d.id");

// $result = [];

// // Функция для обработки JSON-данных
// function processJsonData($jsonString) {
//     $jsonData = json_decode($jsonString, true);
//     $processedData = [];

//     if ($jsonData) {
//         foreach ($jsonData as $key => $value) {
//             if ($value !== null) { // Проверяем на null
//                 $processedData[] = [
//                     'name' => $key,
//                     'value' => $value,
//                 ];
//             }
//         }
//     }

//     return $processedData;
// }

// function processJsonDataSapParams($jsonString) {
//     $jsonData = json_decode($jsonString, true);
//     $processedData = [];

//     if ($jsonData) {
//         foreach ($jsonData as $item) {
//             foreach ($item['VALUES'] as $value) {
//                 if ($value !== null) { // Проверяем на null
//                     $processedData[] = [
//                         'name' => $item['ANAME'],
//                         'value' => $value,
//                     ];
//                 }
//             }
//         }
//     }

//     return $processedData;
// }

// foreach ($data as $row) {
//     $id = $row['detail_info_id'];
//     $detail_id = $row['detail_id'];
//     $category_id = $row['categoryId'];
//     $name = $row['name'];
//     $value = $row['value'];

//     if (is_string($value) && is_array(json_decode($value, true)) && $name != "sapParams") {
//         $jsonData = processJsonData($value);

//         foreach ($jsonData as $jsonRow) {
//             $result[] = [
//                 'id' => $id,
//                 'detail_id' => $detail_id,
//                 'category_id' => $category_id,
//                 'name' => $jsonRow['name'],
//                 'value' => $jsonRow['value'],
//             ];
//         }
//     }else if (is_string($value) && is_array(json_decode($value, true)) && $name == "sapParams") {
//         $jsonData = processJsonDataSapParams($value);

//         foreach ($jsonData as $jsonRow) {
//             $result[] = [
//                 'id' => $id,
//                 'detail_id' => $detail_id,
//                 'category_id' => $category_id,
//                 'name' => $jsonRow['name'],
//                 'value' => $jsonRow['value'],
//             ];
//         }
//     } else {
//         $result[] = [
//             'id' => $id,
//             'detail_id' => $detail_id,
//             'category_id' => $category_id,
//             'name' => $name,
//             'value' => $value,
//         ];
//     }
// }

// foreach ($result as $row) {
//     // echo "ID: {$row['id']}, Detail ID: {$row['detail_id']}, Category ID: {$row['category_id']}, Name: {$row['name']}, Value: {$row['value']}\n";
//     $sql = "INSERT INTO details_parsed (detail_id, category_id, name, value) VALUES (?i, ?i, ?s, ?s)";
//     $db->query($sql, $row['detail_id'], $row['category_id'], $row['name'], $row['value']);
// }

?>
