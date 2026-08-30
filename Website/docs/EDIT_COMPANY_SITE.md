# Документация: Настройка сайта (`edit_company_site`)

> Для frontend-разработчиков. Описание работы раздела **«Мои сайты» → Редактирование сайта** в личном кабинете Sort1.

---

## 1. Общее описание

Функционал позволяет пользователю (владельцу компании) создавать и настраивать собственные сайты-магазины (B2B/B2C порталы), привязанные к его организации. Все настройки хранятся в таблице `company_sites` и связанных с ней таблицах.

**Основной файл frontend-логики:** `Website/js/sites.js`  
**Страница интерфейса:** `Website/account/profile.php` (вкладка «Мои сайты»)

---

## 2. Структура таблиц БД

### 2.1. `company_sites` — основные настройки сайта

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | int | PK |
| `company_id` | int | ID компании-владельца |
| `site_name` | varchar | Домен сайта (например, `my.shop.ru`) |
| `shop_logo` | text | Base64 логотипа |
| `favicon` | text | Base64 favicon |
| `id_site_color` | int | FK → `sites_colors.id` |
| `about` | text | HTML «О нас» (устарело, теперь в `company_sites_header`) |
| `delivery` | text | HTML «Доставка» (устарело) |
| `payment` | text | HTML «Оплата» (устарело) |
| `return_garant` | text | HTML «Возврат и гарантия» (устарело) |
| `oferta` | text | HTML «Оферта» (устарело) |
| `privacy` | text | HTML «Политика конфиденциальности» |
| `text_on_main` | text | HTML текст на главной |
| `shop_coords` | varchar | Координаты магазина (широта,долгота) |
| `shop_address` | varchar | Адрес магазина |
| `shop_telegram` | varchar | Telegram |
| `shop_whatsapp` | varchar | WhatsApp |
| `shop_viber` | varchar | Viber |
| `shop_phone` | varchar | Телефон |
| `shop_email` | varchar | Email |
| `shop_verify_phone` | tinyint | Проверять телефон при регистрации (1/0) |
| `shop_sms_apikey` | varchar | Ключ API sms.ru |
| `tg_chat_id` | varchar | Chat ID Telegram для запросов по VIN |
| `yandex_rating_value` | varchar | Ссылка на виджет Яндекс.Рейтинга |
| `laximo_login` | varchar | Логин Laximo |
| `laximo_key` | varchar | Ключ Laximo |
| `id_catalog` | int | FK → `site_catalogs.id` |
| `catalog_config` | json | JSON-конфиг выбранного каталога |
| `find_to_vin_config_id` | int | FK → `find_to_vin_config.id` |
| `use_catalog_sort1` | tinyint | Использовать каталог Sort1 (1/0) |
| `disabled_categorys` | text | Отключённые категории |
| `popular_parts_enabled` | tinyint | Популярные запчасти (1/0) |
| `parts_by_categorys_enabled` | tinyint | Запчасти по категориям (1/0) |
| `popular_goods_enabled` | tinyint | Популярные товары (1/0) |
| `popular_categories` | tinyint | Популярные категории (1/0) |
| `find_to_vin_enabled` | tinyint | Поиск по VIN (1/0) |
| `request_vin_enabled` | tinyint | Запрос по VIN (1/0) |
| `yandex_rating_enabled` | tinyint | Яндекс рейтинг (1/0) |
| `laximo_enabled` | tinyint | Laximo (1/0) |
| `privacy_enabled` | tinyint | Политика конфиденциальности (1/0) |
| `text_on_main_enabled` | tinyint | Текст на главной (1/0) |

### 2.2. `company_sites_header` — динамические заголовки/страницы

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | int | PK |
| `site_id` | int | FK → `company_sites.id` |
| `name` | varchar | Название заголовка (например, "О нас") |
| `uri` | varchar | URI-транслит (например, `o-nas`) |
| `value` | text | HTML-содержимое страницы |
| `enabled` | tinyint | Включён (1/0) |

### 2.3. `sites_colors` — цветовая палитра

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | int | PK |
| `color` | varchar | Основной фон (по умолчанию `#fff`) |
| `color_dark` | varchar | Тёмный акцент (по умолчанию `#4377FD`) |
| `text_in_color_dark` | varchar | Текст на тёмном фоне (по умолчанию `#fff`) |
| `color_button` | varchar | Цвет кнопки (по умолчанию `#515466`) |
| `text_color_in_button` | varchar | Текст в кнопке (по умолчанию `#fff`) |
| `color_links` | varchar | Цвет ссылок (по умолчанию `#000`) |
| `color_links_analog` | varchar | Цвет аналогов (по умолчанию `#000`) |
| `color_footer` | varchar | Фон подвала (по умолчанию `#f2f5f9`) |

### 2.4. `site_catalogs` — доступные каталоги

| Поле | Тип | Описание |
|------|-----|----------|
| `id` | int | PK |
| `name_catalog` | varchar | Название каталога |
| `catalog_config` | json | JSON-шаблон полей конфигурации |

### 2.5. `find_to_vin` / `find_to_vin_config` — поиск по VIN

| Таблица | Описание |
|---------|----------|
| `find_to_vin` | Справочник провайдеров поиска по VIN |
| `find_to_vin_config` | Сохранённые конфигурации конкретного сайта |

---

## 3. API Endpoints

Все запросы идут на `POST /api/index.php` с JSON-телом или form-data.

### 3.1. Получить список сайтов

```
Action: get_company_sites
Auth: required (session)
Response:
{
  "status": "ok",
  "company_sites": [...],
  "my_companys": { "1": { "name": "..." } }
}
```

### 3.2. Получить данные одного сайта

```
Action: get_company_site
Params: { site_id: 123 }
Response:
{
  "status": "ok",
  "company_site": { ... },
  "headers": [ ... ]
}
```

### 3.3. Сохранить сайт (создать / обновить)

```
Action: save_company_site
Method: POST (form-data через serializeJSON)
Params:
  site_id            int
  site_name          string
  shop_logo          string (base64)
  favicon            string (base64)
  shop_coords        string
  shop_address       string
  shop_telegram      string
  shop_whatsapp      string
  shop_viber         string
  shop_phone         string
  shop_email         string
  shop_verify_phone  on/off
  shop_sms_apikey    string
  tg_chat_id         string
  yandex_rating_value string
  privacy_enabled    on/off
  text_on_main_enabled on/off
  popular_parts_enabled on/off
  parts_by_categorys_enabled on/off
  popular_goods_enabled on/off
  popular_categories on/off
  find_to_vin_enabled on/off
  request_vin_enabled on/off
  yandex_rating_enabled on/off
  laximo_enabled     on/off
  use_catalog_sort1  on/off
  select_catalog     int (0 = отключить)
  catalog_config     object
  site_privacy       string (HTML)
  site_text_on_main  string (HTML)
  headers            array [{id, name, uri, value, enabled}]
```

**Важно:** поле `headers` передаётся как массив объектов. Каждый заголовок содержит актуальный HTML из textarea.

### 3.4. Удалить сайт

```
Action: delete_company_site
Params: { site_id: 123 }
```

### 3.5. Заголовки сайта

```
Action: save_company_site_header
Params: { site_id, header_name, header_id (0 для нового) }
```

```
Action: delete_site_header
Params: { header_id }
```

### 3.6. Цветовая палитра

```
Action: get_colors_site
Params: { site_id } (опционально, определяется по Referer)
Response:
{
  "status": "ok",
  "colors": {
    "color": "#fff",
    "color_dark": "#4377FD",
    ...
  },
  "id_colors": 123
}
```

```
Action: save_colors_site
Params: {
  id_color_site,
  color, color_dark, text_in_color_dark,
  color_button, text_color_in_button,
  color_links, color_links_analog, color_footer
}
```

### 3.7. Laximo

```
Action: get_laximo_data
Params: { site_id }
Response: { laximo_data: { laximo_login, laximo_key } }
```

```
Action: save_laximo_data
Params: { site_id, laximo_login, laximo_key }
```

### 3.8. Поиск по VIN (FindToVin)

```
Action: get_ftv
Params: { site_id, ftv_config_id } (для редактирования)
Response: { ftv: [...], ftv_find_id }
```

```
Action: get_ftv_id
Params: { site_id }
Response: { ftv_config_id }
```

```
Action: save_ftv_config
Params: {
  site_id,
  find_to_vin_id,
  ftv_id (0 для нового),
  find_to_vin_config: { key: value, ... }
}
```

### 3.9. Каталоги

```
Action: get_catalogs
Response: { catalogs: [{ id, name_catalog }] }
```

```
Action: get_config_catalog
Params: { id }
Response: { config: [{ name, dscr, type, value }] }
```

### 3.10. Получить данные сайта для публичной части

```
Action: get_site_data
Params: { request_data: "about" | "delivery" | "payment" | "return_garant" | "oferta" | "privacy" | "id_catalog" | "all" }
Auth: не требуется (определяется по HTTP Referer)
```

---

## 4. Frontend логика (`Website/js/sites.js`)

### 4.1. Открытие редактора

```javascript
function edit_company_site(site_name, site_id)
```

- Сбрасывает глобальный массив `site_headers = []`
- Вызывает `api_query_array("/api/index.php", {site_id}, "get_company_site")`
- Формирует HTML-форму шириной `1000px` через строковую конкатенацию
- Открывает модальное окно через `create_window_centered_blue(...)`

### 4.2. Редактор контента (Summernote)

```javascript
function edit_site_html(id)
```

- Переключает видимость textarea с `display:none` → WYSIWYG-редактор [Summernote](https://summernote.org/)
- Toolbar: стили, шрифты, цвета, списки, таблицы, ссылки, картинки, видео, fullscreen, codeview
- ID элемента совпадает с `uri` заголовка (например, `o-nas`, `dostavka`)

### 4.3. Сохранение

```javascript
async function save_company_site_with_JSON()
```

1. Сериализует форму через `serializeJSON()` → объект `postdata`
2. Собирает `catalog_config` из полей каталога (если выбран)
3. Обновляет массив `site_headers` значениями из формы:
   - `value` = HTML из textarea
   - `enabled` = 1 если checkbox `uri_enabled` = `on`
4. Прикрепляет `postdata.headers = site_headers`
5. Отправляет `api_query_array("/api/index.php", postdata, "save_company_site")`
6. При успехе закрывает окно и обновляет список `get_company_sites()`

### 4.4. Цветовая палитра

```javascript
function edit_color_site(site_id)
```

- Загружает текущие цвета через `get_colors_site`
- Строит `<input type="color">` для каждого цвета
- Встраивает iframe с `https://shop.sort1.pro` для live-preview
- При изменении цвета вызывает `updateColor()`, который шлёт `postMessage` во фрейм

```javascript
function updateColor()
```

```javascript
iframeWindow.postMessage({
  color, color_dark, text_in_color_dark,
  color_button, text_color_in_button,
  color_links, color_links_analog, color_footer
}, 'https://shop.sort1.pro/');
```

### 4.5. Загрузка изображений

**Логотип:**
```javascript
function convert_logo_to_base64(input)
```
- Читает `FileReader.readAsBinaryString()`
- Конвертирует в Base64 Data URI: `data:image/png;base64,...`
- Сохраняет в скрытый input `#shop_logo`
- Показывает превью `#site_logo_img` (width: 150px)

**Favicon:**
```javascript
function convert_favicon_to_base64(input)
```
- Аналогично, превью 16px

### 4.6. Динамические заголовки

```javascript
function add_site_header(site_id)       // добавить
function edit_header_name(id, site_id, name) // редактировать название
function delete_site_header(site_id, header_id) // удалить
```

При сохранении нового сайта (`site_id == 0`) backend автоматически создаёт 6 стандартных заголовков:
1. О нас
2. Доставка
3. Оплата
4. Возврат и гарантия
5. Оферта
6. Контакты

---

## 5. Поток данных (Data Flow)

```
Пользователь
    ↓
account/profile.php → вкладка «Мои сайты»
    ↓
get_company_sites() → /api/index.php (action=get_company_sites)
    ↓
Отрисовка таблицы сайтов
    ↓
Клик «Редактировать» → edit_company_site(site_name, site_id)
    ↓
get_company_site → /api/index.php (action=get_company_site)
    ↓
Отрисовка формы настроек (HTML-строка)
    ↓
Пользователь редактирует поля / Summernote / цвета / каталоги
    ↓
Клик «Сохранить» → save_company_site_with_JSON()
    ↓
serializeJSON() + сбор headers + catalog_config
    ↓
save_company_site → /api/index.php
    ↓
Обновление company_sites + company_sites_header
    ↓
get_company_sites() → обновление списка
```

---

## 6. Пример JSON для сохранения сайта

```json
{
  "action": "save_company_site",
  "site_id": 42,
  "site_name": "myshop.ru",
  "shop_logo": "data:image/png;base64,iVBORw0KGgo...",
  "favicon": "data:image/x-icon;base64,AAABAA...",
  "shop_coords": "55.7558,37.6173",
  "shop_address": "Москва, ул. Примерная, 1",
  "shop_phone": "+7 (999) 123-45-67",
  "shop_email": "info@myshop.ru",
  "privacy_enabled": "on",
  "text_on_main_enabled": "on",
  "popular_parts_enabled": "on",
  "use_catalog_sort1": "on",
  "select_catalog": 3,
  "catalog_config": {
    "api_key": "xxx",
    "region_id": "77"
  },
  "site_privacy": "<p>Политика конфиденциальности...</p>",
  "site_text_on_main": "<p>Добро пожаловать...</p>",
  "headers": [
    {
      "id": 15,
      "name": "О нас",
      "uri": "o-nas",
      "value": "<p>Мы лучшие...</p>",
      "enabled": 1
    }
  ]
}
```

---

## 7. Важные нюансы для frontend

### 7.1. Отключённые (закомментированные) поля

В `edit_company_site()` часть блоков закомментирована:
- `site_about`, `site_delivery`, `site_payment`, `site_return_garant`, `site_oferta`, `site_contacts`

Они перенесены в динамические `company_sites_header`. Не добавляйте их обратно в основную форму — используйте механизм заголовков.

### 7.2. Checkbox → tinyint

Backend принимает `on` / `off` (или отсутствие поля) и конвертирует в `1` / `0`.

### 7.3. Каталоги

- Чекбокс `catalog_active` вызывает `change_active_catalog(checked)`
- Подгружает список из `site_catalogs`
- При выборе каталога подгружаются его поля конфигурации через `get_config_catalog`
- Значения полей сохраняются в `catalog_config` как JSON

### 7.4. Безопасность

- Все actions проверяют сессию
- `save_company_site` проверяет, что `site_name` не занят другой компанией
- Удаление сайта (`delete_company_site`) проверяет `company_id` из сессии

### 7.5. Summernote

Подключён глобально на странице (в `header.php` или через модуль). Если редактор не инициализируется, проверьте наличие:
```html
<link href="/vendor/summernote/summernote.css" rel="stylesheet">
<script src="/vendor/summernote/summernote.js"></script>
```

---

## 8. Связь с публичным сайтом (shop)

Публичная часть сайта (например, `shop.sort1.pro`) получает настройки через:

```
/api/index.php?action=get_site_data&request_data=all
```

Ответ содержит:
- `text_on_main`, `shop_coords`, `shop_address`, контакты
- `shop_logo`, `favicon`
- `headers` — массив страниц для меню
- флаги включения модулей (`popular_parts_enabled`, `find_to_vin_enabled` и т.д.)
- `id_catalog` + `catalog_config` для подключения внешнего каталога

Цвета загружаются отдельно:
```
/api/index.php?action=get_colors_site
```

---

## 9. Файлы, задействованные в функционале

| Файл | Назначение |
|------|-----------|
| `Website/js/sites.js` | Основная frontend-логика |
| `Website/account/profile.php` | Вкладка «Мои сайты» |
| `Website/api/index.php` | Точка входа API |
| `Website/api/classes/App.php` | Роутинг, авторизация |
| `Website/api/classes/Components/Controllers/Controller.php` | Action-контроллеры |
| `Website/api/classes/Components/Models/Companys.php` | Бизнес-логика сайтов |
| `Website/api/classes/Components/Models/Catalogs.php` | Каталоги |
| `Website/api/classes/Components/Models/FindToVinConfigs.php` | Поиск по VIN |

---

## 10. Быстрый старт для разработчика

1. Авторизуйтесь в системе
2. Перейдите в **Профиль → Мои сайты**
3. Нажмите «Добавить новый сайт» → `add_company_site()`
4. Введите домен → `save_company_site()`
5. Кликните «Редактировать» → `edit_company_site(site_name, id)`
6. Измените настройки, загрузите логотип, настройте цвета
7. Нажмите «Сохранить» → `save_company_site_with_JSON()`
8. Проверьте результат на публичном домене

---

*Документация актуальна на момент ревизии кода. При изменении структуры БД или API обновляйте данный файл.*
