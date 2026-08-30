# B2B Client Portal API — Документация

## Базовый URL
```
POST /api/index.php
```

## Формат запроса
- Метод: `POST`
- `Content-Type: application/json`
- Тело запроса — JSON-объект с полем `action` и параметрами.
- Для авторизованных запросов обязательна сессия (`PHPSESSID` в cookie).
- Для регистрации важен заголовок `Referer` (определяет `main_company` через `company_sites`).

---

## 1. Регистрация юридического лица

### Шаг 1. Получить капчу
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -c cookie.txt \
  -d '{"action":"get_market_captcha"}'
```
**Ответ:**
```json
{"status":"ok","data":"Сколько будет 3 плюс 5?"}
```
Ответ записывается в сессию (`$_SESSION['captcha'] = 8`).

### Шаг 2. Отправить регистрацию
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -H "Referer: http://YOUR_SITE" \
  -b cookie.txt \
  -d '{
    "action": "register_legal_entity",
    "contact_name": "Иванов Иван Иванович",
    "phone": "+7(999)123-45-67",
    "email": "client@example.com",
    "inn": "1234567890",
    "captcha": "8"
  }'
```

**Параметры:**
| Поле | Тип | Обязательное | Описание |
|------|-----|--------------|----------|
| `contact_name` | string | да | Контактное лицо |
| `phone` | string | да | Телефон в формате `+7(XXX)XXX-XX-XX` |
| `email` | string | да | Email |
| `inn` | string | да | ИНН (10 или 12 цифр) |
| `captcha` | string | да | Ответ на капчу |
| `company_card_file` | string | нет | Base64-файл (data:...;base64,...) |

**Успешный ответ:**
```json
{"status":"ok","msg":"Регистрация прошла успешно...","company_id":123,"user_id":456}
```

---

## 2. Финансы
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{"action":"get_client_finance"}'
```

**Опционально:**
```json
{"action":"get_client_finance","company_id":123}
```
(если не передать — берётся из сессии `$_SESSION['company_id']`)

**Ответ:**
```json
{
  "status": "ok",
  "balance": 15000.00,
  "rezerv": 5000.00,
  "credit_limit": 30000.00,
  "sum_trade": 12000.00,
  "company_id": 123
}
```

---

## 3. Мои платежи
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{
    "action": "get_client_payments",
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
  }'
```

**Параметры:**
| Поле | Тип | Обязательное | Описание |
|------|-----|--------------|----------|
| `date_from` | string | нет | Дата от (YYYY-MM-DD), по умолчанию -30 дней |
| `date_to` | string | нет | Дата до (YYYY-MM-DD), по умолчанию сегодня |

**Ответ:**
```json
{
  "status": "ok",
  "payments": [
    {"id":1,"summ":5000.00,"create_date":"2024-05-20 14:30:00","payment_type":1,"payment_direction":1,"payment_num":"","payment_target":"Оплата заказа","is_advance":0}
  ]
}
```

---

## 4. Отгрузки
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{
    "action": "get_client_shipments",
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
  }'
```

**Ответ:**
```json
{
  "status": "ok",
  "shipments": [
    {
      "id": 100,
      "number": "РН-100",
      "document_date": "2024-05-15 10:00:00",
      "zakaz_id": 50,
      "positions_count": 5,
      "summa": 12500.50,
      "chf_number": "",
      "chf_date": "0000-00-00 00:00:00"
    }
  ]
}
```

### Печать счёта
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{"action":"print_client_invoice","document_id":100}'
```
**Ответ:** `html` (base64) + `filename`. Расшифровать base64 и сохранить как `.html`.

### Печать УПД
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{"action":"print_client_upd","document_id":100}'
```
**Ответ:** `file` (base64 xlsx). Расшифровать base64 и сохранить как `.xlsx`.

---

## 5. Возвраты
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{
    "action": "get_client_returns",
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
  }'
```

**Особенность:** фильтр по дате опционален. Если не передавать `date_from`/`date_to` — вернутся все возвраты.

**Ответ:**
```json
{
  "status": "ok",
  "returns": [
    {"id":10,"number":"ВЗ-10","document_date":"2024-04-10","zakaz_id":40,"comment":"","summa":3200.00,"positions_count":2}
  ]
}
```

---

## 6. Акт сверки
```bash
curl -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -b cookie.txt \
  -d '{
    "action": "download_akt_sverki",
    "date_from": "2024-01-01",
    "date_to": "2024-12-31"
  }'
```

**Ответ:**
```json
{"status":"ok","url":"/akt_sverki.php?company_id=123&date_from=2024-01-01&date_to=2024-12-31"}
```
Перейти по ссылке (авторизация через ту же сессию).

---

## Полный пример цепочки теста

```bash
# 1. Получить капчу и сохранить cookie
curl -c cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"get_market_captcha"}'

# 2. Зарегистрироваться (замените 8 на правильный ответ)
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -H "Referer: http://YOUR_SITE" \
  -d '{
    "action": "register_legal_entity",
    "contact_name": "Тест Тестов",
    "phone": "+7(999)111-22-33",
    "email": "test@test.ru",
    "inn": "1234567890",
    "captcha": "8"
  }'

# 3. Авторизоваться (получить рабочую сессию)
curl -c cookie.txt -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"login","username":"test@test.ru","password":"ВАШ_ПАРОЛЬ"}'

# 4. Получить финансы
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"get_client_finance"}'

# 5. Получить платежи
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"get_client_payments"}'

# 6. Получить отгрузки
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"get_client_shipments"}'

# 7. Получить возвраты
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"get_client_returns"}'

# 8. Скачать акт сверки
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"download_akt_sverki"}'

# 9. Напечатать счёт (сохранить HTML)
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"print_client_invoice","document_id":100}' | jq -r '.html' | base64 -d > schet.html

# 10. Напечатать УПД (сохранить XLSX)
curl -b cookie.txt -X POST http://YOUR_SITE/api/index.php \
  -H "Content-Type: application/json" \
  -d '{"action":"print_client_upd","document_id":100}' | jq -r '.file' | base64 -d > upd.xlsx
```

---

## Файлы сервиса
- **Модель:** `Website/api/classes/Components/Models/B2BClientService.php`
- **Контроллер:** добавлены action'ы в `Website/api/classes/Components/Controllers/Controller.php`
- **Frontend:** `Website/b2b_portal.php`
