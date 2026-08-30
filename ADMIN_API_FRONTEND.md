# Admin API — документация для фронтенда (Next.js)

> Актуальная версия контракта между фронтендом админки (`shop.sort1.pro`) и legacy API (`nur.sort1.pro/api/index.php`).
> Все запросы идут **через серверный прокси** Next.js, а не напрямую из браузера.

---

## 0. Транспорт и авторизация

```
Браузер (админка)
   │  POST /api/admin/sort1   { action, ...payload }
   ▼
Next.js route  src/app/api/admin/sort1/route.ts
   │  - читает JWT из cookie `shop1_admin`
   │  - подставляет CRM-sesskey из httpOnly cookie `shop1_admin_sesskey`
   │  - пропускает только whitelisted actions
   ▼
POST https://nur.sort1.pro/api/index.php
   Headers: Content-Type: application/json, Referer: http://shop.sort1.pro
   Body:    { action, ...payload, sesskey }
```

### Авторизация (2 шага)

**Шаг 1 — подготовка (`PUT /api/admin/sort1-login`):**
- Next.js генерирует `sesskey`, делает `get_seed`.
- Генерирует капчу (`a + b`), подписывает в httpOnly cookie `shop1_admin_prepare` (HMAC, 5 мин).
- Возвращает `{ seed, captcha: { a, b } }`.

**Шаг 2 — вход (`POST /api/admin/sort1-login`):**
- Браузер хеширует пароль: `SHA256(plain_password + seed)`.
- Шлёт `{ login, password: <hash>, captcha: <число> }`.
- Next.js проверяет cookie `shop1_admin_prepare` (капча / TTL).
- Шлёт на CRM `{ action: "login", login, password: <hash>, sesskey }`.
- При `status: "ok"` — сохраняет `sesskey` в cookie `shop1_admin_sesskey` (httpOnly, 7 дней).

> Капча проверяется **только на стороне Next.js**. Backend получает уже готовый хеш и `sesskey`.

### Cookie

| Cookie | Назначение | Кем управляется |
|--------|-----------|---------------|
| `shop1_admin` | JWT локальной админки | Next.js |
| `shop1_admin_sesskey` | Сессия CRM sort1 (httpOnly, 7 дней) | Next.js |
| `shop1_admin_prepare` | Подписанный seed + ответ капчи (httpOnly, 5 мин) | Next.js |

---

## 1. Универсальное правило `site_id`

Во **всех** запросах, где требуется `site_id`:
- `site_id: 123` — явный ID сайта.
- `site_id: 0` или отсутствие поля — backend сам определит сайт:
  1. По `HTTP_REFERER` (домен публичного сайта).
  2. По сессии CRM (`main_company` авторизованного пользователя).

> В админке достаточно всегда слать `site_id: 0` (у вас один сайт на компанию).

---

## 2. Whitelist action'ов (Next.js)

Прокси должен пропускать **только** этот список. При добавлении новых — расширяйте:

```
logout, get_site_data, get_company_site, get_company_sites,
get_colors_site, save_company_site, save_site_colors, save_site_pages,
save_company_site_header, delete_site_header, get_pwa, save_pwa, get_user_data
```

---

## 3. Новые action'ы

### 3.1 `save_site_colors` — сохранить палитру

**Request:**
```jsonc
{
  "action": "save_site_colors",
  "site_id": 0,
  "sesskey": "<crm session>",
  "palette": {
    "light": {
      "bg": "#f5f5f5", "surface": "#ffffff", "surface2": "#f7f7f7",
      "text": "#2f2e34", "muted": "#6b7280", "border": "#e5e5e5",
      "primary": "#f7a600", "primaryFg": "#ffffff",
      "success": "#16a34a", "danger": "#dc2626"
    },
    "dark": {
      "bg": "#0a0a0a", "surface": "#1a1a1a", "surface2": "#2f2e34",
      "text": "#ffffff", "muted": "#99a1af", "border": "#393939",
      "primary": "#f7a600", "primaryFg": "#ffffff",
      "success": "#22c55e", "danger": "#ef4444"
    }
  }
}
```

**Response:**
```jsonc
{ "status": "ok", "err": "" }
// или
{ "status": "err", "err": "Неверный HEX в light.bg" }
```

> Backend валидирует: обе темы, ровно 10 токенов, формат `#rrggbb`.

---

### 3.2 `save_site_pages` — массовое сохранение разделов

Заменяет поштучный `save_company_site_header`. Отправляется **полный** актуальный список разделов. Отсутствующие в массиве строки в БД будут удалены.

**Request:**
```jsonc
{
  "action": "save_site_pages",
  "site_id": 0,
  "sesskey": "<crm session>",
  "headers": [
    {
      "id": 15,            // id из БД; 0 = создать новую
      "name": "Помощь",
      "uri": "pomoshch",   // транслит (генерируется на фронте)
      "value": "<p>HTML…</p>",
      "enabled": 1
    },
    {
      "id": 0,
      "name": "Доставка",
      "uri": "dostavka",
      "value": "<h2>Сроки</h2><p>…</p>",
      "enabled": 1
    }
  ]
}
```

**Response:**
```jsonc
{
  "status": "ok",
  "err": "",
  "headers": [
    { "id": 15, "name": "Помощь", "uri": "pomoshch", "value": "…", "enabled": 1 },
    { "id": 88, "name": "Доставка", "uri": "dostavka", "value": "…", "enabled": 1 }
  ]
}
```

> Backend санитизирует HTML (разрешены: `p, h2, h3, strong, em, ul, ol, li, a, br`). При коллизии `uri` добавляет суффикс (`-1`, `-2`...).

---

### 3.3 `get_pwa` — получить настройки PWA

**Request:**
```jsonc
{ "action": "get_pwa", "site_id": 0, "sesskey": "<crm session>" }
```

**Response:**
```jsonc
{
  "status": "ok",
  "pwa": {
    "appName": "AutoShop — автозапчасти",
    "shortName": "AutoShop",
    "themeColor": "#f7a600",
    "backgroundColor": "#0a0a0a"
  }
}
```

---

### 3.4 `save_pwa` — сохранить настройки PWA

**Request:**
```jsonc
{
  "action": "save_pwa",
  "site_id": 0,
  "sesskey": "<crm session>",
  "pwa": {
    "appName": "AutoShop — автозапчасти",
    "shortName": "AutoShop",
    "themeColor": "#f7a600",
    "backgroundColor": "#0a0a0a"
  }
}
```

**Response:**
```jsonc
{ "status": "ok", "err": "" }
```

> Backend валидирует HEX цветов, обрезает строки до 255 символов.

---

## 4. Расширенные существующие action'ы

### 4.1 `save_company_site` — теперь сохраняет бренд + VIN

В дополнение к старым полям (`shop_phone`, `shop_logo`, `favicon` и т.д.) теперь принимает:

```jsonc
{
  "action": "save_company_site",
  "site_id": 0,
  "sesskey": "<crm session>",
  "site_name": "sort1-shop.ru",    // домен (как раньше)
  "site_title": "AutoShop",        // НОВОЕ: название бренда
  "vin_enabled": 1,                // НОВОЕ: 1 = показывать VIN/FRAME
  "shop_logo": "data:image/png;base64,…",
  "favicon": "data:image/x-icon;base64,…",
  "shop_phone": "+7 (999) 123-45-67",
  "shop_email": "info@shop.ru",
  "shop_address": "Москва, …",
  "shop_coords": "55.7558,37.6173",
  "shop_telegram": "username",
  "shop_whatsapp": "79991234567",
  "shop_viber": "79991234567"
}
```

> `site_id: 0` — backend найдёт сайт текущей компании и сделает **update** (а не создаст новый).

---

### 4.2 `get_company_site` — теперь отдаёт больше полей

Ответ содержит (внутри `company_site`):
- `site_title` — название бренда (`"AutoShop"`)
- `vin_enabled` — флаг VIN (`1` / `0`)
- `theme_palette` — объект палитры (если сохраняли через `save_site_colors`)
- `pwa` — объект PWA-настроек
- `headers` — массив разделов с полями `id, name, uri, value, enabled`

---

### 4.3 `get_site_data` (публичная часть, `request_data: "all"`) — расширен

Ответ теперь включает:
```jsonc
{
  "status": "ok",
  "data": {
    "site_name": "sort1-shop.ru",     // домен
    "site_title": "AutoShop",         // НОВОЕ: бренд
    "shop_logo": "data:image/png;base64,…",
    "favicon": "data:image/x-icon;base64,…",
    "shop_phone": "…",
    "shop_email": "…",
    "shop_address": "…",
    "shop_telegram": "…",
    "shop_whatsapp": "…",
    "shop_viber": "…",
    "headers": [
      { "name": "Помощь", "uri": "pomoshch", "value": "<p>…</p>", "enabled": 1 }
    ],
    "theme_palette": {                // НОВОЕ
      "light": { "bg": "#f5f5f5", "...": "..." },
      "dark":  { "bg": "#0a0a0a", "...": "..." }
    },
    "pwa": {                          // НОВОЕ (опционально)
      "appName": "AutoShop — автозапчасти",
      "shortName": "AutoShop",
      "themeColor": "#f7a600",
      "backgroundColor": "#0a0a0a"
    },
    "vin_enabled": 1                  // НОВОЕ
  }
}
```

---

## 5. Уже реализованные action'ы (менять не нужно)

| Action | Назначение |
|--------|-----------|
| `get_seed` | Получить seed для хеширования пароля |
| `login` | Вход в CRM (пароль уже `SHA256(hash)`) |
| `logout` | Выход |
| `get_user_data` | Имя / проверка сессии |
| `get_company_site` | Префилл настроек сайта |
| `get_company_sites` | Список сайтов (если несколько) |
| `save_company_site` | Сохранить настройки сайта |
| `save_company_site_header` | Поштучное сохранение раздела (legacy) |
| `delete_site_header` | Удалить раздел (legacy) |
| `get_site_data` | Публичные данные сайта |
| `get_colors_site` | Получить legacy-цвета |
| `save_colors_site` | Сохранить legacy-цвета |

---

## 6. Сводная таблица всех action'ов

| Action | Метод | Payload (ключевое) | Ответ |
|--------|-------|--------------------|-------|
| `save_site_colors` | POST | `site_id`, `palette{light,dark}` | `{status, err}` |
| `save_site_pages` | POST | `site_id`, `headers[]{id,name,uri,value,enabled}` | `{status, err, headers[]}` |
| `save_pwa` | POST | `site_id`, `pwa{appName,shortName,themeColor,backgroundColor}` | `{status, err}` |
| `get_pwa` | POST | `site_id` | `{status, pwa{…}}` |
| `save_company_site` | POST | `site_id`, `site_title`, `vin_enabled`, контакты, logo, favicon | `{status, err}` |
| `get_company_site` | POST | `site_id` | `{status, company_site{…}, headers[]}` |
| `get_site_data` | POST | `request_data: "all"` | `{status, data{…}}` |

---

## 7. Что требуется на фронте

1. **Whitelist** — добавить 4 новых action'а в `ADMIN_ALLOWED_ACTIONS`.
2. **Site Settings** — отправлять `save_company_site` с `site_title` и `vin_enabled`.
3. **Theme** — отправлять `save_site_colors` и читать `theme_palette` из `get_site_data`.
4. **Pages** — использовать `save_site_pages` вместо поштучных вызовов.
5. **PWA** — отправлять `save_pwa` / читать `get_pwa` (или `get_site_data.pwa`).
6. **`site_id: 0`** — можно не хранить ID сайта локально, backend определит сам.
