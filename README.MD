# WB Analytics Loader

Laravel-проект для загрузки данных из тестового WB API в MySQL.

Данные загружаются из четырех эндпоинтов:

- `/api/sales` - продажи
- `/api/orders` - заказы
- `/api/stocks` - остатки
- `/api/incomes` - доходы

Пагинация обрабатывается автоматически. По умолчанию за один запрос берется 500 строк.

## Требования

- PHP 8.1+
- Composer
- MySQL 8 или MariaDB 10.6+

## Установка

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

В `.env` нужно указать параметры базы и ключ API:

```env
DB_CONNECTION=mysql
DB_HOST=mysql-3358984e-wb-analytics.c.aivencloud.com
DB_PORT=14282
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=AVNS_Rw8Md-qeiaukayIWbQH
DB_SSL=true

WB_API_BASE_URL=http://109.73.206.144:6969
WB_API_KEY=your_api_key
WB_API_DEFAULT_FROM=2024-01-01
WB_API_LIMIT=500
WB_DB_BATCH=15
WB_STORE_PAYLOAD=false
```

`WB_STORE_PAYLOAD=false` оставляет в таблицах только нормализованные поля эндпоинтов. Поле `payload` остается nullable и может использоваться для хранения сырого JSON, если включить `WB_STORE_PAYLOAD=true`.

## Загрузка данных

Полная загрузка всех эндпоинтов:

```bash
php artisan wb:sync --fresh
```

Загрузка одного эндпоинта:

```bash
php artisan wb:sync --endpoint=sales --fresh
```

Загрузка за период:

```bash
php artisan wb:sync --from=2025-06-01 --to=2026-04-24 --fresh
```

Быстрый тест на одной странице:

```bash
php artisan wb:sync --endpoint=orders --from=2026-01-24 --to=2026-01-24 --max-pages=1
```

Для `stocks` API принимает только `dateFrom`, поэтому `dateTo` для этого эндпоинта не отправляется.

## Доступы к БД

```text
host: mysql-3358984e-wb-analytics.c.aivencloud.com
port: 14282
database: defaultdb
user: avnadmin
password: AVNS_Rw8Md-qeiaukayIWbQH
ssl: required
```

## Таблицы

- `wb_sales` - продажи
- `wb_orders` - заказы
- `wb_stocks` - остатки
- `wb_incomes` - доходы
- `api_sync_logs` - история запусков импорта

В основных таблицах есть поле `source_key`. Оно собирается из ключевых полей записи и используется для повторного запуска импорта без дублей.

## Локальная база через Docker

Если нужна локальная MySQL-база:

```bash
docker compose up -d
php artisan migrate
php artisan wb:sync --fresh
```

Локальные доступы из `docker-compose.yml`:

```text
host: 127.0.0.1
port: 3306
database: wb_analytics
user: wb_user
password: wb_password
root password: root_password
```

## Проверка

Тесты:

```bash
php artisan test
```

Форматирование:

```bash
vendor/bin/pint
```

Статус по строкам можно посмотреть на `/` или `/api/stats`.
