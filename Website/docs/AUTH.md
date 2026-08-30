# Документация: Авторизация и аутентификация

> Для frontend-разработчиков. Полное описание системы авторизации в проекте Sort1.

---

## 1. Общая архитектура

Система использует **сессионную авторизацию** (PHP sessions) с дополнительной поддержкой API-ключей. Все запросы к API проходят через единую точку входа `Website/api/index.php`.

### Типы пользователей по ролям (`roles`):

| Роль | ID | Описание |
|------|-----|----------|
| Супер-админ | 1 | Полный доступ |
| Админ компании | < 10 | Сотрудник компании (офис, склад) |
| Клиент интернет-магазина | 10 | Пользователь B2B/B2C портала |
| Маркетплейс | 20 | Пользователь маркетплейса |

---

## 2. Таблицы БД

### 2.1. `users` — пользователи

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | int | PK |
| `username` | varchar | Логин (обычно email) |
| `password` | varchar | Пароль в открытом виде (**внимание: не хешируется!**) |
| `roles` | int | Роль пользователя |
| `company_id` | int | Текущая компания |
| `main_company_id` | int | Основная компания |
| `my_sklad_id` | int | Текущий склад |
| `my_service_id` | int | Текущий сервис (СТО) |
| `name` | varchar | Имя |
| `lastname` | varchar | Фамилия |
| `middlename` | varchar | Отчество |
| `email` | varchar | Email |
| `phone` | varchar | Телефон |
| `mphone` | varchar | Мобильный телефон |
| `inn` | varchar | ИНН |
| `avatar` | text | Аватар |
| `api_key` | varchar | Ключ для API-доступа |
| `mphone_confirmed` | tinyint | Телефон подтверждён (1/0) |
| `email_confirmed` | tinyint | Email подтверждён (1/0) |
| `admin_disabled` | tinyint | Блокировка админом (1/0) |
| `finance_disabled` | tinyint | Блокировка за неуплату (1/0) |
| `fired` | tinyint | Уволен (1/0) |
| `search_in_all_sklad` | tinyint | Поиск по всем складам (1/0) |
| `create_date` | datetime | Дата создания |

### 2.2. `user_sessions` — активные сессии

| Поле | Тип | Описание |
|------|-----|----------|
| `session_id` | varchar | ID PHP-сессии |
| `session_start` | datetime | Время начала |
| `user_id` | int | ID пользователя |
| `user_ip` | varchar | IP-адрес |

### 2.3. `user_companys` — связь пользователей и компаний

| Поле | Тип | Описание |
|------|-----|----------|
| `user_id` | int | Пользователь |
| `main_company_id` | int | Основная компания (0 = владелец) |
| `company_id` | int | Компания |
| `btype` | int | Тип связи (1-клиент, 2-дилер, 3-своя, 4-гибрид, 5-логист) |
| `deleted` | tinyint | Удалена (1/0) |

---

## 3. Точка входа API

### 3.1. `Website/api/index.php`

```php
require 'classes/App.php';
Sort1API\App::run();
```

### 3.2. `Website/api/classes/App.php` — основной поток

```
1. Регистрация автозагрузчика классов
2. Создание Request (парсинг JSON из php://input)
3. Создание Response
4. Проверка HTTP-метода (Auth::check_method)
5. Проверка API-ключа (Auth::api_login) — если передан token/api_key
6. CORS-заголовки для OPTIONS-запросов
7. Проверка авторизации (Auth::login)
8. White-list actions (без авторизации)
9. Проверка специальных режимов:
   - check_internet_shop() — B2B/B2C портал
   - check_jetparts() — маркетплейс
10. Вызов Controller::action_{action}($request)
11. Вывод Response
```

### 3.3. White-list — действия без авторизации

```php
$request_actions_white_list = [
    "login", "user_login", "login_wiki",
    "register_user", "register_user_wiki",
    "register_callback",
    "get_site_data",
    "get_seed",
    "kick_user",
    "get_all_categorys",
    "get_brands_wiki", "get_categorys_wiki",
    "get_marks_wiki", "get_brand_details_wiki",
    "get_detail_info_wiki", "get_crosses_wiki",
    "get_category_data_wiki", "search_details_wiki",
    "add_favorite_detail", "get_favorite_details",
    "delete_favorite_detail",
    "get_reviews_wiki",
    "get_market_captcha",
    "form_guide_sort1",
    "get_detail_info_market",
    // ... и другие
];
```

---

## 4. Frontend авторизация

### 4.1. Основной скрипт — `Website/js/lib.js`

#### Функция `authorize()` — вход в CRM

```javascript
function authorize() {
    api_query_array("/api/index.php", [], "get_seed").then(function(data1){
        var send = new Array();
        send['login'] = $('#login').val();
        var pass = $('#password').val();
        var seed = data1.seed;
        send['password'] = sha256(pass + seed);
        api_query_array("/api/index.php", send, "login").then(function(data){
            if (data.status == 'ok') {
                location.href = '/modules/1';
            } else {
                $('#login_alert').html(data.err);
                $('#login_alert').show();
            }
        });
    })
}
```

**Алгоритм:**
1. Получаем `seed` с сервера (SHA256 от MD5 текущей даты)
2. На клиенте хешируем пароль: `SHA256(password + seed)`
3. Отправляем `login` и `password` (уже захешированный)
4. При успехе редирект на `/modules/1`

#### Функция `register_user()` — регистрация

```javascript
function register_user() {
    var send = new Array();
    send['lastname'] = $('#lastname').val();
    send['name'] = $('#name').val();
    send['middlename'] = $('#middlename').val();
    send['inn'] = $('#inn').val();
    send['email'] = $('#email').val();
    send['mphone'] = $('#mphone').val();
    api_query_array("/api/index.php", send, "register_user").then(function(data){
        if (data.status == 'ok') {
            $("#content_reg").html('Данные для продолжения регистрации отправлены на вашу почту');
        } else {
            $('#login_alert').html(data.err);
            $('#login_alert').show();
        }
    });
}
```

#### Функция `logout()` — выход

```javascript
function logout() {
    var postdata = { action: 'logout' };
    $.ajax({
        url: '/api/index.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(postdata)
    }).done(function(data){
        if (data.status == 'ok') {
            location.href = '/account/login';
        }
    })
}
```

### 4.2. Landing page авторизация — `Website/landing/js/ajax.js`

Используется `fetch()` вместо jQuery:

```javascript
// Авторизация
$('#login_button').click(async function(event){
    authorize(); // вызывает глобальную функцию из lib.js
});

// Регистрация
$('#reg_btn').click(async function(event){
    let send = {
        lastname: $("#lastname").val(),
        name: $("#reg_name").val(),
        middlename: $("#middlename").val(),
        inn: $("#inn").val(),
        email: $("#email").val(),
        mphone: $("#mphone").val(),
        action: "register_user"
    };
    let response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json;charset=utf-8' },
        body: JSON.stringify(send)
    });
    let result = await response.json();
    // ...
});
```

### 4.3. Утилиты запросов — `Website/js/lib.js`

#### `api_query_array(api_url, arr, action)`

```javascript
function api_query_array(api_url, arr, action) {
    var defer = $.Deferred();
    arr['action'] = action;
    api_url = resolve_api_url(api_url);

    $.ajax({
        url: api_url,
        data: JSON.stringify(Object.assign({}, arr)),
        contentType: "application/json",
        type: "POST"
    }).done(function(data){
        defer.resolve(data);
        if (data.status == "ok") {
            if (typeof(data.msg) != "undefined" && data.msg != "") {
                bootbox.alert({ message: data.msg });
            }
        } else {
            if (data.err != "" && typeof(data.err) != "undefined") {
                $.unblockUI();
                bootbox.alert({ title: "<font color='red'>Ошибка</font>", message: data.err });
            }
        }
    }).fail(function(xhr, textStatus) {
        defer.reject();
    });
    return defer.promise();
}
```

#### `api_query(api_url, form_id, action)`

Сериализует форму через `serializeJSON()` и отправляет как JSON.

#### `api_query_obj(api_url, arr, action)`

Аналогично `api_query_array`, но принимает объект.

---

## 5. Backend авторизация

### 5.1. `Auth::login()` — CRM вход (сотрудники)

**Файл:** `Website/api/classes/Components/Auth.php`

```php
public static function login($request) {
    // Восстановление сессии по sesskey
    if (isset($request->sesskey) && $request->sesskey != "") {
        session_id($request->sesskey);
    }
    session_start();

    // Уже авторизован?
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
        self::$_is_auth = true;
        self::$_client = (int)$_SESSION['user_id'];
        self::check_my_sklad();
        return true;
    }

    // Проверка логина/пароля
    $login = $request->login;
    $pass = $request->password;

    if (!isset($login, $pass)) return false;

    $db = DB::getInstance();
    $client = $db->getRow("select * from users where username=?s and roles<10", $request->login);

    // Проверки блокировок
    if ((int)$client['admin_disabled'] == 1) {
        self::$_err_msg = "Вход заблокирован. Свяжитесь info@sort1.ru";
        return false;
    }
    if ((int)$client['finance_disabled'] == 1) {
        self::$_err_msg = "Доступ отключен из-за неоплаты...";
        return false;
    }
    if ((int)$client['fired'] == 1) return false;

    // Проверка пароля
    $seed = hash("sha256", md5(date("Y-m-d")));
    $passfb = hash("sha256", $client['password'] . $seed);

    if ($passfb === $pass && !empty($login) && !empty($pass)) {
        self::$_is_auth = true;
        self::$_client = $client;

        $_SESSION['user_id'] = $client['id'];
        // Определение company_id и main_company
        $client_main_companys = $db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0", $_SESSION['user_id']);
        if (in_array($client['company_id'], $client_main_companys)) {
            $_SESSION['company_id'] = $client['company_id'];
        } else {
            $_SESSION['company_id'] = $client_main_companys[0];
        }
        if (in_array($client['main_company_id'], $client_main_companys)) {
            $_SESSION['main_company'] = $client['main_company_id'];
        } else {
            $_SESSION['main_company'] = $client_main_companys[0];
        }

        // Загрузка настроек компании
        $zakaz_commit = $db->getRow("select zakaz_commit, document_set_price, ... from company where id=?i", $client['main_company_id']);
        if ($zakaz_commit) {
            $_SESSION['zakaz_commit'] = $zakaz_commit['zakaz_commit'];
            // ... другие поля
        }

        $_SESSION['roles'] = $client['roles'];
        $_SESSION['my_service_id'] = $client['my_service_id'];

        // Запись сессии
        $db->query("insert into user_sessions (session_id, session_start, user_id, user_ip) 
            values(?s,?s,?i,?s) 
            on duplicate key update session_start=?s, user_id=?i, user_ip=?s",
            session_id(), date("Y-m-d H:i:s"), $_SESSION['user_id'], $_SERVER['HTTP_X_REAL_IP'],
            date("Y-m-d H:i:s"), $_SESSION['user_id'], $_SERVER['HTTP_X_REAL_IP']
        );

        self::check_my_sklad();
        self::check_my_service();
        Sort1s::activate();
        Sort1s::register($db, 0);
        Sort1s::register($db, 1);
        Sort1s::param_sync($db, 0);
        return true;
    }
    return false;
}
```

### 5.2. `Auth::user_login()` — вход клиента интернет-магазина

```php
public static function user_login($request) {
    if (isset($request->sesskey) && $request->sesskey != "") {
        session_id($request->sesskey);
    }
    session_start();

    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
        return true;
    }

    $login = $request->login;
    $pass = $request->password;

    $db = DB::getInstance();
    // Определение компании по домену (Referer)
    preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);
    $site_data = $db->getRow("select company_id, shop_verify_phone, shop_sms_apikey from company_sites where site_name=?s", str_replace("www.", "", $origin[1]));
    $main_company_id = $site_data['company_id'];

    if (!isset($login, $pass)) {
        if ((int)$main_company_id > 0) {
            $_SESSION['main_company'] = $main_company_id;
            Sort1s::activate();
            // ...
        }
        return false;
    }

    // Поиск пользователя с roles=10 (клиент) в контексте компании
    $client = $db->getRow("select * from users where username=?s and main_company_id=?i and roles=10", $request->login, $main_company_id);

    // Пароль сравнивается в открытом виде (!)
    $passfb = $client['password'];
    if ($passfb === $pass && !empty($login) && !empty($pass)) {
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['company_id'] = $client['company_id'];
        $_SESSION['main_company'] = $client['main_company_id'];
        $_SESSION['roles'] = $client['roles'];

        // Перенос временной корзины
        $temp_basket = $db->getOne("select id from basket where session_id=?s and user_id=0", session_id());
        $db->query("update favorite_details set user_id=?i where session_id=?s", (int)$_SESSION['user_id'], session_id());
        $basket = new Basket();
        if ($temp_basket) {
            $db->query("update basket_details set basket_id=?i where basket_id=?i", $basket->id, (int)$temp_basket);
        }
        Sort1s::activate();
        return true;
    }
    return false;
}
```

### 5.3. `Auth::user_login_market()` — вход маркетплейса

Аналогично `user_login()`, но:
- `roles = 20`
- `main_company_id = 35` (хардкод)
- Сливает корзины с проверкой max_count

### 5.4. `Auth::api_login()` — авторизация по API-ключу

```php
public static function api_login($request) {
    $db = DB::getInstance();
    if (isset($request->api_key) && $request->api_key != "") {
        $sessid = $db->getOne("select session_id from user_sessions where user_id in (select id from users where api_key=?s)", $request->api_key);
        if ($sessid != "") {
            session_id($sessid);
            session_start();
        }
    }

    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
        return true;
    } else {
        $client = $db->getRow("select * from users where api_key=?s", $request->api_key);
        if (!empty($client['id']) && (int)$client['id'] > 0) {
            if (!isset($sessid) || $sessid == "") session_start();
            $_SESSION['user_id'] = $client['id'];
            $_SESSION['company_id'] = $client['company_id'];
            $_SESSION['main_company'] = $client['main_company_id'];
            $_SESSION['roles'] = $client['roles'];
            $db->query("insert ignore into user_sessions ...", ...);
            return true;
        }
        return false;
    }
}
```

### 5.5. `Auth::logout()` — выход

```php
public static function logout($request) {
    session_unset();
    self::$_is_auth = false;
    self::$_client = null;
    // Очистка временных файлов
    foreach (glob("../open_cart/temp/".session_id()."*") as $filename) {
        unlink($filename);
    }
    return array("status" => "ok", "err" => "");
}
```

### 5.6. `Auth::kick_user()` — принудительный выход пользователя

```php
public static function kick_user($request) {
    if ($request->key != "Sdlkmtdfsl94mdk4965mkfd95") {
        return array("status" => "err", "err" => "Неправильный ключ");
    }
    for ($i = 0; $i < count((array)$request->users); $i++) {
        $sess = $db->getAll("select session_id from user_sessions where user_id=?i", $request->users[$i]['id']);
        foreach ($sess as $value) {
            unlink("/var/lib/php/sessions/sess_".$value['session_id']);
        }
        $db->query("DELETE FROM user_sessions WHERE user_id=?i", $request->users[$i]['id']);
    }
    return array("status" => "ok", "msg" => "");
}
```

---

## 6. Специальные режимы авторизации

### 6.1. `App::check_internet_shop()` — B2B/B2C портал

```php
private static function check_internet_shop() {
    $actions = ["get_brands", "search_by_article", "save_basket_detail", ...];
    preg_match("/https*:\/\/([^\/]+)\/*/", $_SERVER['HTTP_REFERER'], $origin);
    $main_company_id = $db->getOne("select company_id from company_sites where site_name=?s", str_replace("www.", "", $origin[1]));
    if ((int)$main_company_id > 0 && in_array($request->action, $actions)) {
        $_SESSION['main_company'] = (int)$main_company_id;
        $_SESSION['roles'] = 10;
        return true;
    }
    return false;
}
```

При запросе с домена, привязанного к `company_sites`, определённые публичные actions работают без полной авторизации, устанавливая `main_company` и `roles=10`.

### 6.2. `App::check_jetparts()` — маркетплейс

Аналогично, но `roles = 20` и другой набор actions.

---

## 7. Session переменные

После успешной авторизации в `$_SESSION` доступны:

| Ключ | Описание |
|------|----------|
| `user_id` | ID пользователя |
| `company_id` | Текущая компания |
| `main_company` | Основная компания |
| `roles` | Роль |
| `my_sklad_id` | Текущий склад |
| `my_service_id` | Текущий сервис |
| `zakaz_commit` | Настройка подтверждения заказа |
| `document_set_price` | Настройка установки цен |
| `document_edit_deny_date` | Запрет редактирования документов |
| `document_detail_edit_deny` | Запрет редактирования деталей |
| `document_details_round` | Округление |
| `self_zakaz_sale_price` | Себестоимость |
| `zakaz_marketing_channel` | Канал маркетинга |

---

## 8. API Endpoints

### 8.1. Получить seed

```
POST /api/index.php
{ "action": "get_seed" }

Response:
{ "status": "ok", "seed": "SHA256(MD5(YYYY-MM-DD))" }
```

### 8.2. Вход в CRM

```
POST /api/index.php
{
  "action": "login",
  "login": "user@example.com",
  "password": "SHA256(plain_password + seed)",
  "sesskey": ""  // опционально, для восстановления сессии
}

Response:
{ "status": "ok", "err": "", "sesskey": "PHPSESSID_xxx" }
```

### 8.3. Вход клиента (интернет-магазин)

```
POST /api/index.php
{
  "action": "user_login",
  "login": "user@example.com",
  "password": "plain_password",  // без хеширования!
  "sesskey": ""
}

Response:
{ "status": "ok", "err": "", "sesskey": "..." }
```

### 8.4. Вход маркетплейса

```
POST /api/index.php
{
  "action": "user_login_market",
  "login": "...",
  "password": "plain_password"
}
```

### 8.5. Выход

```
POST /api/index.php
{ "action": "logout" }

Response:
{ "status": "ok", "err": "" }
```

### 8.6. Регистрация

```
POST /api/index.php
{
  "action": "register_user",
  "lastname": "Иванов",
  "name": "Иван",
  "middlename": "Иванович",
  "inn": "1234567890",
  "email": "user@example.com",
  "mphone": "+7(999)123-45-67"
}
```

### 8.7. Авторизация по API-ключу

```
POST /api/index.php
{
  "action": "any_action",
  "api_key": "xxx..."
}
```

Или:
```
POST /api/index.php
{
  "action": "any_action",
  "token": "xxx..."
}
```

---

## 9. Проверка прав доступа

### 9.1. `Controller::check_role()`

```php
private static function check_role($module_id, $rights_name, $rw) {
    $role_id = $_SESSION['roles'];
    $roles = Users::get_my_role();
    if (isset($roles['roles']['modules_rights']['modules']['m'.$module_id]['rights'][$rights_name])) {
        if ($roles['roles']['modules_rights']['modules']['m'.$module_id]['rights'][$rights_name][$rw] == 1)
            return 1;
        else return 0;
    }
    else return 1; // по умолчанию разрешено
}
```

Проверяет права из JSON-структуры ролей (`roles` таблица).

### 9.2. Примеры проверок в контроллере

```php
if (self::check_role(2, "clients", "read"))
    return Companys::get_clients($request);
else return self::_error("Недостаточно прав");
```

---

## 10. Файлы, задействованные в авторизации

| Файл | Назначение |
|------|-----------|
| `Website/js/lib.js` | `authorize()`, `register_user()`, `logout()`, `api_query*()` |
| `Website/landing/js/ajax.js` | Регистрация на landing page |
| `Website/landing/js/script.js` | UI landing page (табы, формы) |
| `Website/landing/index.php` | Landing page |
| `Website/account/login.php` | Форма входа |
| `Website/account/reg.php` | Форма регистрации |
| `Website/api/index.php` | Точка входа API |
| `Website/api/classes/App.php` | Главный роутер, white-list, check_internet_shop |
| `Website/api/classes/Components/Auth.php` | Ядро авторизации |
| `Website/api/classes/Components/Request.php` | Парсинг входящих запросов |
| `Website/api/classes/Components/User.php` | Модель пользователя |
| `Website/api/classes/Components/Controllers/Controller.php` | Action-контроллеры |
| `Website/api/classes/Components/Models/Users.php` | Регистрация, смена пароля |

---

## 11. Безопасность — важные замечания

### ⚠️ Пароли хранятся в открытом виде

В таблице `users` поле `password` содержит пароль **без хеширования**. Это критическая уязвимость.

```php
// В Auth::login()
$passfb = hash("sha256", $client['password'] . $seed);
// Сравнивается с frontend-хешем
```

### ⚠️ Разные механизмы для CRM и магазина

- **CRM (`login`)**: пароль хешируется на клиенте `SHA256(password + seed)`
- **Магазин (`user_login`)**: пароль передаётся и сравнивается в открытом виде

### ⚠️ Seed основан на текущей дате

```php
$seed = hash("sha256", md5(date("Y-m-d")));
```

Это предсказуемое значение. Хотя пароль не передаётся в открытом виде, seed легко вычислить.

### ✅ API-ключи

`api_key` в таблице `users` позволяет авторизовываться без логина/пароля. При первом запросе с `api_key` создаётся сессия, которая записывается в `user_sessions`.

### ✅ Принудительный выход

`kick_user` удаляет файлы сессий с диска и записи из `user_sessions`, позволяя администратору выбрасывать пользователей из системы.

---

## 12. Быстрый старт

### 12.1. Вход в CRM (frontend)

```javascript
// 1. Получить seed
api_query_array("/api/index.php", [], "get_seed").then(function(data1){
    // 2. Захешировать пароль
    var hashedPass = sha256(plainPassword + data1.seed);
    // 3. Отправить логин
    api_query_array("/api/index.php", {
        login: email,
        password: hashedPass
    }, "login").then(function(data){
        if (data.status === 'ok') {
            document.cookie = "PHPSESSID=" + data.sesskey;
            location.href = '/modules/1';
        }
    });
});
```

### 12.2. Проверка авторизации

```javascript
// Любой защищённый action вернёт ошибку, если пользователь не авторизован
api_query_array("/api/index.php", {}, "get_my_company").then(function(data){
    if (data.status === 'err' && data.err === 'Auth need') {
        location.href = '/account/login';
    }
});
```

### 12.3. Смена компании

```javascript
api_query_array("/api/index.php", {
    company_id: 123
}, "change_company").then(function(data){
    if (data.status === 'ok') {
        location.reload();
    }
});
```

---

*Документация актуальна на момент ревизии кода. При изменении механизмов авторизации обновляйте данный файл.*
