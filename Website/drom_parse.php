<?php
include "include/db_safe.inc.php";

class DromDataImporter {
    private $db;
    private $stats;
    private $input_dir;
    private $last_motor_id;
    
    public function __construct($input_dir = "parsed_modifications_auto") {
        $this->db = new SafeMySQL();
        $this->stats = [
            'processed_files' => 0,
            'processed_makers' => 0,
            'processed_models' => 0,
            'processed_generations' => 0,
            'processed_modifications' => 0,
            'errors' => []
        ];
        $this->input_dir = $input_dir;
        
        // Получаем последний motorId при инициализации
        $this->getLastMotorId();
        
        // Проверяем структуру таблиц
        $this->checkTableStructure();
    }
    
    /**
     * Проверка структуры таблиц
     */
    private function checkTableStructure() {
        echo "🔍 Проверка структуры таблиц...\n";
        
        // Проверяем auto_makers
        $makers_cols = $this->db->getAll("SHOW COLUMNS FROM auto_makers");
        foreach ($makers_cols as $col) {
            if ($col['Field'] == 'id') {
                echo "  • auto_makers.id: " . $col['Extra'] . "\n";
            }
        }
        
        // Проверяем auto_models
        $models_cols = $this->db->getAll("SHOW COLUMNS FROM auto_models");
        foreach ($models_cols as $col) {
            if ($col['Field'] == 'id') {
                echo "  • auto_models.id: " . $col['Extra'] . "\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Получение последнего motorId из таблицы
     */
    private function getLastMotorId() {
        $result = $this->db->getRow("SELECT MAX(motorId) as max_id FROM auto_motors");
        $this->last_motor_id = $result['max_id'] ?? 0;
        echo "📊 Последний motorId в базе: " . $this->last_motor_id . "\n\n";
    }
    
    /**
     * Получение следующего motorId
     */
    private function getNextMotorId() {
        $this->last_motor_id++;
        return $this->last_motor_id;
    }
    
    /**
     * Основной метод для запуска импорта
     */
    public function run() {
        echo "========================================\n";
        echo "🚀 ЗАПУСК ИМПОРТА ДАННЫХ ИЗ DROM.RU\n";
        echo "========================================\n\n";
        
        // Получаем все JSON файлы из папки
        $files = glob($this->input_dir . "/*.json");
        echo "📂 Найдено файлов брендов: " . count($files) . "\n\n";
        
        foreach ($files as $file) {
            $this->processFile($file);
        }
        
        $this->printStats();
    }
    
    /**
     * Обработка одного файла бренда
     */
    private function processFile($file) {
        $filename = basename($file);
        echo "📄 Обработка файла: $filename\n";
        
        try {
            $content = file_get_contents($file);
            $brand_data = json_decode($content, true);
            
            if (!$brand_data) {
                throw new Exception("Ошибка парсинга JSON");
            }
            
            $this->stats['processed_files']++;
            
            // Для каждой модели в файле
            foreach ($brand_data as $model_name => $model_info) {
                $this->processModel($model_info, $model_name);
            }
            
            echo "✅ Файл обработан\n\n";
            
        } catch (Exception $e) {
            $this->stats['errors'][] = "Ошибка в файле $filename: " . $e->getMessage();
            echo "❌ Ошибка: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Обработка одной модели
     */
    private function processModel($model_info, $model_name) {
        $brand_name = $model_info['brand'] ?? '';
        $model_url = $model_info['model_url'] ?? '';
        
        if (empty($brand_name) || empty($model_name)) {
            return;
        }
        
        echo "  🔧 Обработка: $brand_name $model_name\n";
        
        // 1. Получаем или создаем запись в auto_makers
        $maker_id = $this->getOrCreateMaker($brand_name);
        
        if (!$maker_id) {
            $this->stats['errors'][] = "Не удалось создать производителя: $brand_name";
            return;
        }
        
        // 2. Получаем или создаем запись в auto_models
        $model_id = $this->getOrCreateModel($maker_id, $brand_name, $model_name);
        
        if (!$model_id) {
            $this->stats['errors'][] = "Не удалось создать модель: $brand_name $model_name";
            return;
        }
        
        $this->stats['processed_models']++;
        
        // 3. Обрабатываем поколения и модификации
        $generations = $model_info['generations'] ?? [];
        
        foreach ($generations as $generation) {
            $this->processGeneration($maker_id, $model_id, $brand_name, $model_name, $generation);
        }
    }
    
    /**
     * Получение или создание производителя (регистронезависимо)
     */
    private function getOrCreateMaker($brand_name) {
        // Регистронезависимый поиск через LOWER()
        $maker = $this->db->getRow(
            "SELECT id, name FROM auto_makers WHERE LOWER(name) = LOWER(?s)", 
            $brand_name
        );
        
        if ($maker) {
            // Если нашли, но регистр отличается, обновляем на оригинальный
            if ($maker['name'] !== $brand_name) {
                $this->db->query(
                    "UPDATE auto_makers SET name = ?s WHERE id = ?i",
                    $brand_name, $maker['id']
                );
                echo "    🔄 Обновлено название производителя: {$maker['name']} -> $brand_name\n";
            }
            return $maker['id'];
        }
        
        // Проверяем по make_name_seo
        $make_name_seo = $this->translitIt($brand_name);
        $make_name_seo = $this->translitUrl($make_name_seo);
        $make_name_seo = str_replace('--', '-', $make_name_seo);
        
        $maker_by_seo = $this->db->getRow(
            "SELECT id FROM auto_makers WHERE LOWER(make_name_seo) = LOWER(?s)",
            $make_name_seo
        );
        
        if ($maker_by_seo) {
            // Обновляем имя если нашли по seo
            $this->db->query(
                "UPDATE auto_makers SET name = ?s WHERE id = ?i",
                $brand_name, $maker_by_seo['id']
            );
            echo "    🔄 Обновлено название производителя (по SEO): $brand_name\n";
            return $maker_by_seo['id'];
        }
        
        // Проверяем структуру таблицы и получаем следующий ID
        $next_id = $this->getNextMakerId();
        
        try {
            if ($next_id === null) {
                // Используем AUTO_INCREMENT
                $sql = "INSERT INTO auto_makers (name, make_name_seo) VALUES (?s, ?s)";
                $this->db->query($sql, $brand_name, $make_name_seo);
                $maker_id = $this->db->insertId();
            } else {
                // Явно указываем ID
                $sql = "INSERT INTO auto_makers (id, name, make_name_seo) VALUES (?i, ?s, ?s)";
                $this->db->query($sql, $next_id, $brand_name, $make_name_seo);
                $maker_id = $next_id;
            }
            
            if ($maker_id) {
                $this->stats['processed_makers']++;
                echo "    ✅ Создан новый производитель: $brand_name (ID: $maker_id)\n";
            }
            
            return $maker_id;
            
        } catch (Exception $e) {
            $this->stats['errors'][] = "Ошибка создания производителя $brand_name: " . $e->getMessage();
            return null;
        }
    }
    
    /**
     * Получение следующего ID для таблицы auto_makers
     */
    private function getNextMakerId() {
        // Проверяем, есть ли AUTO_INCREMENT
        $check = $this->db->getRow("SHOW COLUMNS FROM auto_makers WHERE Field = 'id'");
        
        if ($check && strpos($check['Extra'], 'auto_increment') !== false) {
            return null; // Используем AUTO_INCREMENT
        }
        
        // Получаем максимальный ID
        $result = $this->db->getRow("SELECT MAX(id) as max_id FROM auto_makers");
        return ($result['max_id'] ?? 0) + 1;
    }
    
    /**
     * Получение или создание модели (регистронезависимо)
     */
    private function getOrCreateModel($maker_id, $brand_name, $model_name) {
        // Регистронезависимый поиск модели
        $model = $this->db->getRow(
            "SELECT id, model_name FROM auto_models 
             WHERE auto_maker_id = ?i AND LOWER(model_name) = LOWER(?s)", 
            $maker_id, $model_name
        );
        
        if ($model) {
            // Обновляем название модели если регистр отличается
            if ($model['model_name'] !== $model_name) {
                $this->db->query(
                    "UPDATE auto_models SET model_name = ?s WHERE id = ?i",
                    $model_name, $model['id']
                );
                echo "      🔄 Обновлено название модели: {$model['model_name']} -> $model_name\n";
            }
            return $model['id'];
        }
        
        // Проверяем структуру таблицы и получаем следующий ID
        $next_id = $this->getNextModelId();
        
        try {
            if ($next_id === null) {
                // Используем AUTO_INCREMENT
                $sql = "INSERT INTO auto_models (auto_maker_id, model_name, auto_maker_name) VALUES (?i, ?s, ?s)";
                $this->db->query($sql, $maker_id, $model_name, $brand_name);
                $model_id = $this->db->insertId();
            } else {
                // Явно указываем ID
                $sql = "INSERT INTO auto_models (id, auto_maker_id, model_name, auto_maker_name) VALUES (?i, ?i, ?s, ?s)";
                $this->db->query($sql, $next_id, $maker_id, $model_name, $brand_name);
                $model_id = $next_id;
            }
            
            return $model_id;
            
        } catch (Exception $e) {
            $this->stats['errors'][] = "Ошибка создания модели $model_name: " . $e->getMessage();
            return null;
        }
    }
    
    /**
     * Получение следующего ID для таблицы auto_models
     */
    private function getNextModelId() {
        // Проверяем, есть ли AUTO_INCREMENT
        $check = $this->db->getRow("SHOW COLUMNS FROM auto_models WHERE Field = 'id'");
        
        if ($check && strpos($check['Extra'], 'auto_increment') !== false) {
            return null; // Используем AUTO_INCREMENT
        }
        
        // Получаем максимальный ID
        $result = $this->db->getRow("SELECT MAX(id) as max_id FROM auto_models");
        return ($result['max_id'] ?? 0) + 1;
    }
    
    /**
     * Обработка поколения и его модификаций
     */
    private function processGeneration($maker_id, $model_id, $brand_name, $model_name, $generation) {
        $gen_title = $generation['title'] ?? '';
        $gen_info = $generation['info'] ?? [];
        $modifications = $generation['modifications'] ?? [];
        
        if (empty($modifications)) {
            return;
        }
        
        // Извлекаем информацию о поколении
        $generation_text = $gen_info['generation'] ?? '';
        $type_text = $gen_info['type'] ?? '';
        $period_start = $gen_info['period_start'] ?? '';
        $period_end = $gen_info['period_end'] ?? '';
        
        // Формируем строку годов
        $years = '';
        if ($period_start && $period_end) {
            $years = $period_start . ' - ' . $period_end;
        } elseif ($period_start) {
            $years = $period_start . ' - н.в.';
        }
        
        echo "    📌 Поколение: $gen_title\n";
        echo "      Модификаций: " . count($modifications) . "\n";
        
        $processed_count = 0;
        
        foreach ($modifications as $mod) {
            $result = $this->processModification(
                $maker_id, 
                $model_id, 
                $brand_name, 
                $model_name, 
                $mod,
                $generation_text,
                $type_text,
                $years
            );
            
            if ($result) {
                $processed_count++;
            }
        }
        
        $this->stats['processed_generations']++;
        $this->stats['processed_modifications'] += $processed_count;
        
        echo "      ✅ Добавлено модификаций: $processed_count\n";
    }
    
    /**
     * Обработка одной модификации с автоинкрементом motorId
     */
    private function processModification($maker_id, $model_id, $brand_name, $model_name, $mod, $generation_text, $type_text, $years) {
        $group_specs = $mod['group_specs'] ?? '';
        $engine = $mod['engine'] ?? '';
        $engine_link = $mod['engine_link'] ?? '';
        $mod_name = $mod['name'] ?? '';
        $period = $mod['period'] ?? '';
        $price = $mod['price'] ?? '';
        $url = $mod['url'] ?? '';
        
        if (empty($mod_name)) {
            return false;
        }
        
        // Парсим характеристики из group_specs
        $specs = $this->parseSpecs($group_specs);
        $litres = $specs['litres'] ?? '';
        $fuel = $specs['fuel'] ?? '';
        $silovoyAgregat = $specs['silovoyAgregat'] ?? '';
        
        // Если не удалось получить из group_specs, пробуем из engine
        if (empty($litres) && !empty($engine)) {
            if (preg_match('/(\d+\.?\d*)\s*л/i', $engine, $matches)) {
                $litres = $matches[0];
            } elseif (preg_match('/(\d+\.?\d+)/', $engine, $matches)) {
                $litres = $matches[1] . ' л';
            }
        }
        
        // Определяем тип топлива если не найден
        if (empty($fuel) && !empty($engine)) {
            if (stripos($engine, 'diesel') !== false || stripos($engine, 'дизель') !== false) {
                $fuel = 'дизель';
            } elseif (stripos($engine, 'petrol') !== false || stripos($engine, 'бензин') !== false) {
                $fuel = 'бензин';
            } elseif (stripos($engine, 'electric') !== false || stripos($engine, 'электро') !== false) {
                $fuel = 'электро';
            }
        }
        
        // Извлекаем код двигателя
        $engineCode = '';
        if (!empty($engine_link)) {
            $parts = explode('/', rtrim($engine_link, '/'));
            $engineCode = end($parts);
        }
        
        // Определяем тип двигателя
        $engineType = $generation_text;
        
        // Проверяем существование модификации по составному ключу
        $existing = $this->db->getRow(
            "SELECT motorId, modification_name FROM auto_motors 
             WHERE carId = ?i AND modelId = ?i 
             AND LOWER(modification_name) = LOWER(?s)",
            $maker_id, $model_id, $mod_name
        );
        
        if ($existing) {
            // Обновляем существующую запись
            $this->db->query(
                "UPDATE auto_motors SET 
                 litres = ?s,
                 fuel = ?s,
                 silovoyAgregat = ?s,
                 engineCode = ?s,
                 year = ?s,
                 engineSalesName = ?s,
                 engineType = ?s
                 WHERE motorId = ?i",
                $litres,
                $fuel,
                $silovoyAgregat,
                $engineCode,
                $period ?: $years,
                $engine,
                $engineType,
                $existing['motorId']
            );
            
            return true;
        }
        
        // Получаем следующий motorId
        $next_motor_id = $this->getNextMotorId();
        
        // Вставляем новую модификацию с явным указанием motorId
        $sql = "INSERT INTO auto_motors 
                (motorId, carId, modelId, typeId, make, model, litres, fuel, silovoyAgregat, 
                 module, engineSalesName, engineType, engineCode, modification_name, year) 
                VALUES 
                (?i, ?i, ?i, 0, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s, ?s)";
        
        try {
            $this->db->query(
                $sql,
                $next_motor_id,
                $maker_id,
                $model_id,
                $brand_name,
                $model_name,
                $litres,
                $fuel,
                $silovoyAgregat,
                '', // module
                $engine,
                $engineType,
                $engineCode,
                $mod_name,
                $period ?: $years
            );
            return true;
        } catch (Exception $e) {
            $this->stats['errors'][] = "Ошибка вставки модификации $mod_name: " . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Парсинг строки характеристик
     */
    private function parseSpecs($specs) {
        $result = [
            'litres' => '',
            'fuel' => '',
            'silovoyAgregat' => ''
        ];
        
        if (empty($specs)) {
            return $result;
        }
        
        // Извлекаем объем двигателя
        if (preg_match('/(\d+\.?\d*)\s*л/i', $specs, $matches)) {
            $result['litres'] = $matches[0];
        }
        
        // Извлекаем тип топлива
        if (preg_match('/(дизель|бензин|электро|гибрид)/ui', $specs, $matches)) {
            $result['fuel'] = $matches[1];
        }
        
        // Извлекаем тип КПП
        if (preg_match('/(МКПП|АКПП|робот|вариатор)/ui', $specs, $matches)) {
            $result['silovoyAgregat'] = $matches[1];
        }
        
        return $result;
    }
    
    /**
     * Вывод статистики
     */
    private function printStats() {
        echo "\n========================================\n";
        echo "📊 СТАТИСТИКА ИМПОРТА\n";
        echo "========================================\n";
        echo "✅ Обработано файлов: " . $this->stats['processed_files'] . "\n";
        echo "✅ Добавлено производителей: " . $this->stats['processed_makers'] . "\n";
        echo "✅ Добавлено моделей: " . $this->stats['processed_models'] . "\n";
        echo "✅ Добавлено поколений: " . $this->stats['processed_generations'] . "\n";
        echo "✅ Добавлено модификаций: " . $this->stats['processed_modifications'] . "\n";
        echo "📊 Последний motorId: " . $this->last_motor_id . "\n";
        
        if (!empty($this->stats['errors'])) {
            echo "\n⚠️ ОШИБКИ (" . count($this->stats['errors']) . "):\n";
            $error_count = 0;
            foreach ($this->stats['errors'] as $error) {
                $error_count++;
                if ($error_count <= 20) {
                    echo "  • $error\n";
                }
            }
            if (count($this->stats['errors']) > 20) {
                echo "  • ... и еще " . (count($this->stats['errors']) - 20) . " ошибок\n";
            }
        }
        
        echo "========================================\n";
    }
    
    /**
     * Транслитерация
     */
    private function translitIt($str) {
        $tr = array(
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
            "Д"=>"D","Е"=>"E","Ё"=>"Yo","Ж"=>"Zh","З"=>"Z","И"=>"I",
            "Й"=>"J","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
            "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
            "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"C","Ч"=>"Ch",
            "Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ь"=>"","Ы"=>"Yi",
            "Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e",
            "ё"=>"yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"j","к"=>"k",
            "л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
            "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h","ц"=>"c",
            "ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"","ь"=>"","ы"=>"y",
            "э"=>"e","ю"=>"yu","я"=>"ya"
        );
        return strtr($str, $tr);
    }
    
    private function translitUrl($str) {
        $tr = array(
            " "=> "-",
            "."=> "",
            "/"=> "_",
            ","=> "",
            "!"=> "",
            "@"=> "",
            "#"=> "",
            "?"=> "",
            "("=> "",
            ")"=> "",
            "%"=> "",
            "$"=> "",
            "^"=> "",
            "&"=> "",
            "*"=> "",
            "{"=> "",
            "}"=> "",
        );
        return strtr($str, $tr);
    }
}

// Запуск импорта
$importer = new DromDataImporter("parsed_modifications_auto");
$importer->run();
?>