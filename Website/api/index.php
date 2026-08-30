<?php
// Подключаем глобальную эмуляцию MySQLi функций
require_once __DIR__ . '/global_mysqli.php';

// Подключаем файл совместимости (если есть)
if (file_exists(__DIR__ . '/mysqli_compat.php')) {
    require_once __DIR__ . '/mysqli_compat.php';
}

// Прямое подключение всех необходимых классов
require_once __DIR__ . '/classes/Components/Request.php';
require_once __DIR__ . '/classes/Components/Response.php';
require_once __DIR__ . '/classes/Components/Auth.php';
require_once __DIR__ . '/classes/Components/Config.php';
require_once __DIR__ . '/classes/Components/DB.php';
require_once __DIR__ . '/classes/Components/Logger.php';
require_once __DIR__ . '/classes/Components/User.php';
require_once __DIR__ . '/classes/Components/SafeMySQL.php';
require_once __DIR__ . '/classes/Components/Controllers/Controller.php';
require_once __DIR__ . '/classes/Components/Controllers/BaseController.php';
require_once __DIR__ . '/classes/Components/Routers/Router.php';
require_once __DIR__ . '/classes/App.php';

$autoload_api = __DIR__ . '/autoload.php';
$autoload_api_legacy = __DIR__ . '/vendor/autoload.php';
$autoload_root = __DIR__ . '/../vendor/autoload.php';

if (file_exists($autoload_api)) {
    require_once $autoload_api;
} elseif (file_exists($autoload_api_legacy)) {
    require_once $autoload_api_legacy;
} elseif (file_exists($autoload_root)) {
    require_once $autoload_root;
}

use Sort1API\App;

// Устанавливаем правильный ROOT
App::$ROOT = __DIR__ . '/';

// Запускаем приложение
App::run();