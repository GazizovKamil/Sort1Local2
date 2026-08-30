<?php
include "include/db_safe.inc.php";

$db = new SafeMySQL();

echo "========================================\n";
echo "🔍 ПРОВЕРКА ИМПОРТИРОВАННЫХ ДАННЫХ\n";
echo "========================================\n\n";

// 1. Проверяем производителей
$makers = $db->getAll("SELECT id, name FROM auto_makers ORDER BY name");
echo "🏭 ПРОИЗВОДИТЕЛИ (" . count($makers) . "):\n";
foreach (array_slice($makers, 0, 10) as $maker) {
    echo "  • {$maker['name']} (ID: {$maker['id']})\n";
}
if (count($makers) > 10) {
    echo "  • ... и еще " . (count($makers) - 10) . " производителей\n";
}

echo "\n";

// 2. Проверяем модели Ambertruck
$ambertruck = $db->getRow("SELECT id FROM auto_makers WHERE name = 'Ambertruck'");
if ($ambertruck) {
    $models = $db->getAll(
        "SELECT id, model_name FROM auto_models WHERE auto_maker_id = ?i",
        $ambertruck['id']
    );
    echo "🚚 МОДЕЛИ AMBERTRUCK (" . count($models) . "):\n";
    foreach ($models as $model) {
        echo "  • {$model['model_name']} (ID: {$model['id']})\n";
        
        // Считаем модификации для каждой модели
        $mod_count = $db->getOne(
            "SELECT COUNT(*) FROM auto_motors WHERE carId = ?i AND modelId = ?i",
            $ambertruck['id'], $model['id']
        );
        echo "    └─ модификаций: $mod_count\n";
    }
}

echo "\n";

// 3. Общая статистика по модификациям
$total_mods = $db->getOne("SELECT COUNT(*) FROM auto_motors");
$total_with_engine = $db->getOne("SELECT COUNT(*) FROM auto_motors WHERE engineSalesName != ''");
$total_with_code = $db->getOne("SELECT COUNT(*) FROM auto_motors WHERE engineCode != ''");

echo "📊 СТАТИСТИКА МОДИФИКАЦИЙ:\n";
echo "  • Всего модификаций: $total_mods\n";
echo "  • С указанием двигателя: $total_with_engine\n";
echo "  • С кодом двигателя: $total_with_code\n";

echo "\n";

// 4. Последние 5 добавленных модификаций
$last_mods = $db->getAll(
    "SELECT motorId, carId, modelId, make, model, engineSalesName, modification_name, year 
     FROM auto_motors 
     ORDER BY motorId DESC 
     LIMIT 5"
);

echo "🆕 ПОСЛЕДНИЕ ДОБАВЛЕННЫЕ МОДИФИКАЦИИ:\n";
foreach ($last_mods as $mod) {
    $maker_name = $db->getOne("SELECT name FROM auto_makers WHERE id = ?i", $mod['carId']);
    $model_name = $db->getOne("SELECT model_name FROM auto_models WHERE id = ?i", $mod['modelId']);
    
    echo "  • [{$mod['motorId']}] $maker_name $model_name\n";
    echo "    └─ {$mod['modification_name']}\n";
    echo "    └─ Двигатель: {$mod['engineSalesName']}\n";
    echo "    └─ Годы: {$mod['year']}\n\n";
}

echo "========================================\n";
?>