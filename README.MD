# WB Analytics Loader

Laravel-приложение для загрузки данных из WB API в MySQL.

Загружаются эндпоинты:

- `/api/sales`
- `/api/orders`
- `/api/stocks`
- `/api/incomes`

## Запуск через Docker

```bash
cp .env.example .env
docker compose build
docker compose run --rm php composer install
docker compose run --rm php php artisan key:generate
docker compose up -d
```

В `docker-compose.yml` два сервиса: `php` и `mysql`. MySQL доступен с хоста на порту `3307`.

## Настройка доступа

Минимальный вариант - указать ключ в `.env`:

```env
WB_API_KEY=your_api_key
```

Для работы с несколькими аккаунтами данные лучше добавить через консольные команды:

```bash
docker compose exec php php artisan app:company:create "Company name" --code=company
docker compose exec php php artisan app:account:create "Main account" --company=company --external-id=main
docker compose exec php php artisan app:api-service:create wildberries --name="Wildberries API" --base-url=http://109.73.206.144:6969
docker compose exec php php artisan app:token-type:create api-key --name="API key"
docker compose exec php php artisan app:api-service:attach-token-type wildberries api-key
docker compose exec php php artisan app:api-token:create --account=main --service=wildberries --type=api-key
```

Для `bearer` используется `--type=bearer`, для логина и пароля - `--type=login-password --login=user`.

## Импорт данных

Импорт по одному аккаунту:

```bash
docker compose exec php php artisan wb:sync --account=main --fresh-only
```

Импорт по всем активным аккаунтам:

```bash
docker compose exec php php artisan wb:sync --all-accounts --fresh-only
```

Импорт одного раздела:

```bash
docker compose exec php php artisan wb:sync --account=main --endpoint=sales --from=2026-06-01 --to=2026-06-26
```

Отладочный вывод:

```bash
docker compose exec php php artisan wb:sync --account=main --endpoint=orders --max-pages=1 -v
```

## Автообновление

Scheduler запускается в `php`-контейнере командой `schedule:work`. Расписание берется из `WB_SYNC_TIMES`:

```env
WB_SYNC_TIMES=06:00,18:00
```

По расписанию выполняется:

```bash
php artisan wb:sync --all-accounts --fresh-only
```

## Таблицы

Справочники:

- `companies`
- `accounts`
- `api_services`
- `token_types`
- `api_service_token_types`
- `api_tokens`

Данные:

- `wb_sales`
- `wb_orders`
- `wb_stocks`
- `wb_incomes`
- `api_sync_logs`

В таблицах данных есть `account_id`, а уникальность построена по `account_id + source_key`, поэтому записи разных аккаунтов не перетирают друг друга.

## Проверка

```bash
php artisan test
vendor/bin/pint
```
