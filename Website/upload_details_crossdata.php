<?php
require 'vendor/autoload.php'; // Автозагрузка через Composer

use PhpOffice\PhpSpreadsheet\IOFactory;

include "include/db_safe.inc.php";

$db = new SafeMySQL(
    array(
        'host' => '192.168.35.25',
        'user' => 'nur',
        'pass' => 'vD9DB7ds',
        'db' => 'crosses'
    )
);

$logFilePath = __DIR__ . '/debug_log.txt';

// Функция для логирования в файл
function logMessage($message, $clear = false)
{
    global $logFilePath;
    if ($clear) {
        file_put_contents($logFilePath, ""); // Очистка файла
    }
    file_put_contents($logFilePath, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}


// Логирование пути к лог-файлу
logMessage('Log file path: ' . $logFilePath);

// Функция для преобразования числового индекса в буквенный
function indexToLetter($index)
{
    $letter = '';
    $index++; // Индексы в Excel начинаются с 1
    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $letter = chr(65 + $mod) . $letter;
        $index = floor(($index - $mod) / 26);
    }
    return $letter;
}

// Обработка данных в зависимости от действия
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'upload':
            importData($db, $logFilePath);
            break;
        case 'add_cross':
            importCrossData2($db, $logFilePath);
            break;
        case 'delete':
            blacklistCrossData($db, $logFilePath);
            break;
        case 'add_params':
            importParameters($db, $logFilePath);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Отсутствует действие']);
}

// Функция для импорта данных
function importData($db, $logFilePath)
{
    if (!isset($_FILES['file']) || !isset($_POST['selected_columns']) || !isset($_POST['category_mappings']) || !isset($_POST['brand_id'])) {
        $error = 'Отсутствуют необходимые данные';
        logMessage($error);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }

    $file = $_FILES['file'];
    $selectedColumns = json_decode($_POST['selected_columns'], true);
    $skipValues = json_decode($_POST['skip_values'], true);
    $categoryMappings = json_decode($_POST['category_mappings'], true);
    $brandId = $_POST['brand_id'];
    $importBatch = time(); // Уникальный идентификатор импорта

    logMessage("Начало импорта: batch_id = $importBatch");
    
    $inputFileName = $file['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        $requiredColumns = ['name', 'article'];
        foreach ($requiredColumns as $column) {
            if (!isset($selectedColumns[$column])) {
                $error = "Не выбрана обязательная колонка: $column";
                logMessage($error);
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }

        $values = [];
        $logValues = [];
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) continue;

            $name = $row[indexToLetter($selectedColumns['name'])] ?? '';
            $category = $row[indexToLetter($selectedColumns['category'])] ?? '';
            $article_raw = $row[indexToLetter($selectedColumns['article'])] ?? '';

            if ($name === '' || $article_raw === '') continue;

            $article = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $article_raw);
            $category = $categoryMappings[$category] ?? 0;

            $values[] = $db->parse("(?s,?i,?s,?s,?i)", $name, $category, $article, $article_raw, $brandId);
            $logValues[] = $db->parse("(?i,?s,?i,?s,?s,?i)", $importBatch, $name, $category, $article, $article_raw, $brandId);
        }

        if (!empty($values)) {
            $query = "INSERT IGNORE INTO details (name, categoryId, article, article_raw, brand_id) VALUES " . implode(', ', $values);
            $db->query($query);
            $addedCount = $db->affectedRows();
            
            $logQuery = "INSERT INTO import_log (import_batch, name, categoryId, article, article_raw, brand_id) VALUES " . implode(', ', $logValues);
            $db->query($logQuery);
            
            logMessage("Добавлено записей: $addedCount");
        }

        echo json_encode(['success' => true, 'added' => $addedCount, 'import_batch' => $importBatch]);
    } catch (Exception $e) {
        logMessage('Ошибка: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function importCrossData($db, $logFilePath)
{
    if (!isset($_FILES['file']) || !isset($_POST['selected_columns']) || !isset($_POST['brand_id'])) {
        $error = 'Отсутствуют необходимые данные';
        logMessage($error);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }

    $file = $_FILES['file'];
    $selectedColumns = json_decode($_POST['selected_columns'], true);
    $skipValues = json_decode($_POST['skip_values'], true);
    $brandId = $_POST['brand_id'];
    $importBatch = time(); // Уникальный идентификатор импорта

    logMessage("Начало импорта cross: batch_id = $importBatch", true);

    $inputFileName = $file['tmp_name'];
    logMessage('Input File Name: ' . $inputFileName);

    // try {
        $spreadsheet = IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        $requiredColumns = ['article', "brandOem", "articleOem"];
        foreach ($requiredColumns as $column) {
            if (!isset($selectedColumns[$column])) {
                $error = "Не выбрана обязательная колонка: $column";
                logMessage($error);
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }

        $values = [];
        $logValues = [];
        $blacklist = [];

        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) {
                continue;
            }

            // logMessage('Spreadsheet Data: ' . print_r($row, true));

            $brand = $row[indexToLetter($selectedColumns['brand'])] ?? '';
            $article = $row[indexToLetter($selectedColumns['article'])] ?? '';
            $brandOem = $row[indexToLetter($selectedColumns['brandOem'])] ?? '';
            $articleOem = $row[indexToLetter($selectedColumns['articleOem'])] ?? '';
            $name = isset($selectedColumns['name']) ? ($row[indexToLetter($selectedColumns['name'])] ?? '') : '';
            $delete = isset($selectedColumns['delete']) ? ($row[indexToLetter($selectedColumns['delete'])] ?? '') : '';

            $article = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $article);
            $articleOem = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $articleOem);
            $brandOem = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $brandOem);

            if ($brand === '' || $article === '' || $brandOem === '' || $articleOem === '') {
                continue;
            }

            $queryBrands = "SELECT brand_id FROM brands WHERE MATCH(brand,brand_raw) AGAINST (?s IN BOOLEAN MODE)";
            $brandOemSearch = $brandOem . '*';

            $detailId = $db->getOne("SELECT id FROM details WHERE brand_id = ?i AND article = ?s", (int)$brandId, $article);
            $brandOemId = $db->getOne($queryBrands, $brandOemSearch);

            logMessage("Not Exist: " . print_r($brandId, true) . " " . print_r($article, true));
            if(!$detailId){
                continue;
            }

            if (!$brandOemId) {
                $mbrid = $db->getOne("SELECT max(brand_id) FROM brands");

                $brandOemId = (int)$mbrid + 1;
                
                // logMessage("New brandOemId: " . print_r($brandOemId, true));
                $db->query("INSERT IGNORE INTO brands (brand_id, brand, brand_raw) VALUES (?i, ?s, ?s)", $brandOemId, $brandOem, $brandOem);
            }
            
            $detailOemId = $db->getOne("SELECT id FROM details WHERE brand_id = ?i AND article = ?s", (int)$brandOemId, $articleOem);
            
            if (!$detailOemId) {
                $db->query("INSERT INTO details (brand_id, article, name) VALUES (?i, ?s, ?s)", (int)$brandOemId, $articleOem, $name);
            }

            $rowData = $db->parse("(?i,?s,?s,?i,?s)", (int)$detailId, $articleOem, $brandOem, (int)$brandOemId, $name);
            $logRowData = $db->parse("(?i, ?i, ?s, ?s, ?i, ?s)", (int)$importBatch, (int)$detailId, $articleOem, $brandOem, (int)$brandOemId, $name);

            if (empty($delete)) {
                $values[] = $rowData;
                $logValues[] = $logRowData;
            } else {
                $blacklist[] = $rowData;
            }
        }
        logMessage("New brandOemId: " . print_r($values, true));

        if (!empty($values)) {
            $query = "INSERT IGNORE INTO crosses (detail_id, cross_article, cross_brand, cross_brand_id, cross_name) VALUES " . implode(', ', $values);
            $db->query($query);
            $addedCount = $db->affectedRows();

            $logQuery = "INSERT INTO import_log_crosses (import_batch, detail_id, cross_article, cross_brand, cross_brand_id, cross_name) VALUES " . implode(', ', $logValues);
            $db->query($logQuery);
        } else {
            $addedCount = 0;
        }

        if (!empty($blacklist)) {
            $query = "INSERT IGNORE INTO crossesBlacklist (detail_id, cross_article, cross_brand, cross_brand_id, cross_name) VALUES " . implode(', ', $blacklist);
            $db->query($query);
        }

        logMessage("Добавлено записей в crosses: $addedCount");
        logMessage("Добавлено записей в blacklist: " . count($blacklist));

        if (!empty($values)) {
            echo json_encode(['success' => true, 'added' => $addedCount, 'remove' => count($blacklist), 'import_batch' => $importBatch]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Нет данных для добавления']);
        }
    // } catch (Exception $e) {
    //     logMessage('Error: ' . $e->getMessage());
    //     echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    // }
}


function blacklistCrossData($db, $logFilePath)
{
    if (!isset($_FILES['file']) || !isset($_POST['selected_columns']) || !isset($_POST['brand_id'])) {
        $error = 'Отсутствуют необходимые данные';
        logMessage($error);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }

    $file = $_FILES['file'];
    $selectedColumns = json_decode($_POST['selected_columns'], true);
    $skipValues = json_decode($_POST['skip_values'], true);
    $brandId = $_POST['brand_id'];

    logMessage('Selected Columns: ' . print_r($selectedColumns, true));
    logMessage('Skip Values: ' . print_r($skipValues, true));
    logMessage('Brand ID: ' . $brandId);

    $inputFileName = $file['tmp_name'];
    logMessage('Input File Name: ' . $inputFileName);

    try {
        $spreadsheet = IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        // logMessage('Spreadsheet Data: ' . print_r($data, true));

        $requiredColumns = ['name', 'article', "brandOem", "articleOem"];
        foreach ($requiredColumns as $column) {
            if (!isset($selectedColumns[$column])) {
                $error = "Не выбрана обязательная колонка: $column";
                logMessage($error);
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }

        $values = [];
        $updateColumns = [];

        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) {
                continue;
            }

            $brand = isset($row[indexToLetter($selectedColumns['brand'])]) ? $row[indexToLetter($selectedColumns['brand'])] : '';
            $article = isset($row[indexToLetter($selectedColumns['article'])]) ? $row[indexToLetter($selectedColumns['article'])] : '';
            $brandOem = isset($row[indexToLetter($selectedColumns['brandOem'])]) ? $row[indexToLetter($selectedColumns['brandOem'])] : '';
            $articleOem = isset($row[indexToLetter($selectedColumns['articleOem'])]) ? $row[indexToLetter($selectedColumns['articleOem'])] : '';
            $delete = isset($row[indexToLetter($selectedColumns['delete'])]) ? $row[indexToLetter($selectedColumns['delete'])] : '';

            $article = preg_replace('/[^a-zA-Z0-9]/', '', $article);
            $articleOem = preg_replace('/[^a-zA-Z0-9]/', '', $articleOem);
            $brandOem = preg_replace('/[^a-zA-Z0-9]/', '', $brandOem);

            if ($brand === '' || $article === '' || $brandOem === '' || $articleOem === '') {
                continue;
            }

            $queryBrands = "SELECT id FROM brands WHERE MATCH(brand) AGAINST (?s IN BOOLEAN MODE)";
            $brandOemSearch = $brandOem . '*'; 

            $detailId = $db->getOne("SELECT id FROM details WHERE brand_id = ?i AND article = ?s", (int) $brandId, $article);
            $brandOemId = $db->getOne($queryBrands, $brandOemSearch);

            if (!$detailId || !$brandOemId) {
                continue;
            }

            $values[] = $db->parse("(?i,?s,?s,?i)", $detailId, $articleOem, $brandOem, $brandOemId);

            // Логирование строки
            // logMessage('Preparing row: ' . json_encode([
            //     'name' => $name,
            //     'category' => $category,
            //     'article' => $article,
            //     'brand' => $brand
            // ]));
        }

        $query = "INSERT IGNORE INTO crossesBlacklist (detail_id, cross_article, cross_brand, cross_brand_id) VALUES " . implode(', ', $values);
        $result = $db->query($query);
        $addedCount = $db->affectedRows();

        logMessage('Query: ' . $query);
        
        if ($result) {
            echo json_encode( ['success' => true, 'added' => $addedCount]);
        } else {
            echo json_encode( ['success' => false, 'error' => 'Ошибка при добавлении кроссов']);
        }
    } catch (Exception $e) {
        logMessage('Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function importParameters($db, $logFilePath) {
    if (!isset($_FILES['file'])) {
        logMessage('Файл не загружен');
        echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
        exit;
    }

    $brandId = $_POST['brand_id'];
    $file = $_FILES['file']['tmp_name'];

    logMessage('Загружаем файл: ' . $file, true);
    
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        logMessage('Spreadsheet Data: ' . print_r($data, true));

        $header = array_shift($data);
        $parameters = array_slice($header, 3); // Параметры начинаются с 4-го столбца
        logMessage('Парметры: ' . print_r($parameters, true));

        foreach ($parameters as $paramName) {
            logMessage("параметр: " . $paramName);

            if($paramName != null){
                $paramId = $db->getOne("SELECT parameter_id FROM cats_param WHERE name = ?s", $paramName);
                if (!$paramId) {
                    $db->query("INSERT INTO cats_param (name) VALUES (?s)", $paramName);
                    $paramId = $db->insertId();
                    logMessage("Добавлен новый параметр: $paramName с ID $paramId");
                }
                $paramIds[$paramName] = $paramId;
            }
        }
        
        foreach ($data as $row) {
            $article = $row['B'];
            $article = preg_replace('/[^a-zA-Z0-9]/', '', $article);

            $detailId = $db->getOne("SELECT id FROM details WHERE article = ?s and brand_id = ?i", $article, $brandId);
            if (!$detailId) continue;
            
            foreach ($parameters as $paramIndex => $paramName) {
                $columnIndex = $paramIndex + 3; // Смещение из-за первых 3 колонок (бренд, артикул, описание)
                $value = $row[$paramIndex];
        
                if (!empty($value)) {
                    logMessage("Добавляем параметр: " . $paramName . " = " . $value . " ПарамId = ". $paramIds[$paramName]);
                    $db->query(
                        "INSERT INTO details_new (detail_id, parameter_id, value) VALUES (?i, ?i, ?s) 
                        ON DUPLICATE KEY UPDATE value = VALUES(value)",
                        $detailId, $paramIds[$paramName], $value
                    );
                }
            }
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        logMessage('Ошибка: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function importApplicability($db)
{
    if (!isset($_FILES['file'])) {
        $error = 'Файл не загружен';
        logMessage($error);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }

    $brandId = $_POST['brand_id'];
    $file = $_FILES['file'];
    $inputFileName = $file['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        // logMessage('Spreadsheet Data: ' . print_r($data, true));

        $values = [];
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) continue;

            $article_raw = $row['A'] ?? '';
            $mark = $row['B'] ?? '';
            $model = $row['C'] ?? '';
            $modif = $row['D'] ?? '';
            $engine = $row['E'] ?? '';
            $kw = $row['F'] ?? '';
            $ls = $row['G'] ?? '';

            $article = preg_replace('/[^a-zA-Z0-9]/', '', $article_raw);

            $detail = $db->getRow("SELECT id FROM details WHERE brand_id = ?i AND article = ?s", (int) $brandId, $article);

            if (!$detail) {
                logMessage("Деталь с артикулом $article не найдена", true);
                continue;
            }

            $detail_id = $detail['id'];

            $mark_id = $db->getOne("SELECT mark_id FROM auto_makers WHERE marka = ?s", $mark);
            if (!$mark_id) {
                $next_id = $db->getOne("SELECT MAX(mark_id) + 1 FROM auto_makers");
                $db->query("INSERT  INTO auto_makers (marka, mark_id) VALUES (?s, ?i)", $mark, (int)$next_id);
                $mark_id = $db->insertId();
            }

            $model_id = $db->getOne("SELECT model_id FROM auto_models WHERE model_name = ?s", $model);
            if (!$model_id) {
                $db->query("INSERT  INTO auto_models (model_name, mark_id) VALUES (?s, ?i)", $model, $mark_id);
                $model_id = $db->insertId();
            }

            $modif_name = $modif;
            if (!empty($engine)) {
                $modif_name .= " (" . $engine . ")";
            }

            $modif_id = $db->getOne("SELECT id FROM car_modifs WHERE modif = ?s", $modif_name);

            logMessage("Запрос: SELECT id FROM car_modifs WHERE modif = ?s", true);
            logMessage("Параметр запроса: $modif");
            logMessage("Результат запроса: " . ($modif_id ? $modif_id : 'Нет результатов'));

            if (!$modif_id) {
                $date = date("Y-m-d H:i:s");
                $raw = $modif_name . "_" . $date;
                $hash = substr(hash('sha256', $raw), 0, 30);

                if (!is_null($kw) && !is_null($ls)) {
                    $db->query("INSERT INTO car_modifs (modif, KW, HP, hash) VALUES (?s, ?i, ?i, ?s)", $modif_name, (int)$kw, (int)$ls, $hash);
                    $modif_id = $db->insertId();
                }
                else{
                    $db->query("INSERT INTO car_modifs (modif, hash) VALUES (?s, ?s)", $modif_name, $hash);
                    $modif_id = $db->insertId();
                }
            }

            $values[] = "(" . (int)$detail_id . ", " . (int)$mark_id . ", " . (int)$model_id . ", " . (int)$modif_id . ")";
        }

        if (!empty($values)) {
            $query = "INSERT  INTO details_applicability (detail_id, mark_id, model_id, modif_id) VALUES " . implode(', ', $values) . " ON DUPLICATE KEY UPDATE model_id = VALUES(model_id), modif_id = VALUES(modif_id)";
            $db->query($query);
        }

        echo json_encode(['success' => true, 'imported' => count($values)]);
    } catch (Exception $e) {
        logMessage('Ошибка: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function importCrossData2($db, $logFilePath)
{
    if (!isset($_FILES['file']) || !isset($_POST['selected_columns']) || !isset($_POST['brand_id'])) {
        $error = 'Отсутствуют необходимые данные';
        logMessage($error);
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }

    $file = $_FILES['file'];
    $selectedColumns = json_decode($_POST['selected_columns'], true);
    $skipValues = json_decode($_POST['skip_values'] ?? '[]', true); // безопасное значение по умолчанию
    $brandId = $_POST['brand_id'];
    $importBatch = time();

    logMessage("Начало импорта cross: batch_id = $importBatch", true);

    $inputFileName = $file['tmp_name'];
    logMessage('Input File Name: ' . $inputFileName);

    try {
        $spreadsheet = IOFactory::load($inputFileName);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        $requiredColumns = ['article', "brandOem", "articleOem"];
        foreach ($requiredColumns as $column) {
            if (!isset($selectedColumns[$column])) {
                $error = "Не выбрана обязательная колонка: $column";
                logMessage($error);
                echo json_encode(['success' => false, 'error' => $error]);
                exit;
            }
        }

        $values = [];
        $logValues = [];
        $blacklist = [];

        // Вспомогательная функция: проверка существования и полноты detail
        $validateDetailExistsAndComplete = function($brandId, $article) use ($db) {
            // Ищем detail по brand_id и article
            $detail = $db->getRow("SELECT id, brand_id, article, name FROM details WHERE brand_id = ?i AND article = ?s", (int)$brandId, $article);
            if (!$detail) {
                return false;
            }
            // Дополнительная проверка: article и brand_id не пустые (name может быть опциональным)
            if (empty($detail['article']) || empty($detail['brand_id'])) {
                return false;
            }
            return (int)$detail['id'];
        };

        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) continue; // пропускаем заголовок

            $brand = $row[indexToLetter($selectedColumns['brand'])] ?? '';
            $article = $row[indexToLetter($selectedColumns['article'])] ?? '';
            $brandOem = $row[indexToLetter($selectedColumns['brandOem'])] ?? '';
            $articleOem = $row[indexToLetter($selectedColumns['articleOem'])] ?? '';
            $name = isset($selectedColumns['name']) ? ($row[indexToLetter($selectedColumns['name'])] ?? '') : '';
            $delete = isset($selectedColumns['delete']) ? ($row[indexToLetter($selectedColumns['delete'])] ?? '') : '';

            // Очистка артикулов от спецсимволов
            $article = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $article);
            $articleOem = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $articleOem);
            $brandOem = preg_replace('/[^a-zA-Z0-9А-Яа-яЁё]/u', '', $brandOem);

            if ($brand === '' || $article === '' || $brandOem === '' || $articleOem === '') {
                continue;
            }

            // 🔍 Проверяем, существует ли целевой detail и полный ли он
            $detailId = $validateDetailExistsAndComplete((int)$brandId, $article);
            if (!$detailId) {
                logMessage("Пропущен cross: detail не найден или неполный — brand_id=$brandId, article=$article");
                continue;
            }

            // Получаем или создаём brandOemId
            $queryBrands = "SELECT brand_id FROM brands WHERE MATCH(brand,brand_raw) AGAINST (?s IN BOOLEAN MODE)";
            $brandOemSearch = $brandOem . '*';
            $brandOemId = $db->getOne($queryBrands, $brandOemSearch);

            if (!$brandOemId) {
                logMessage("Пропущен бренд: brandOemId=$brandOemId");
            }

            $detailOemId = $db->getOne("SELECT id FROM details WHERE brand_id = ?i AND article = ?s", (int)$brandOemId, $articleOem);
            if (!$detailOemId) {
                logMessage("Пропущен cross: detailOemId не найден или неполный — detailOemId=$detailOemId");
            }
        }

        $addedCount = 0;

        if (!empty($values) || !empty($blacklist)) {
            echo json_encode([
                'success' => true,
                'added' => $addedCount,
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Нет данных для добавления или удаления']);
        }

    } catch (Exception $e) {
        logMessage('Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}