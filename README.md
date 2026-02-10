# api-ruslan

Laravel 12 API middleware для обмена данными между двумя e-commerce системами:

- **OpenCart** (`/api/open-cart/*`)
- **CosmoShop** (`/api/cosmo-shop/*`)

В проекте также есть:

- HTML документация API через **Scribe**: `/docs` (и `/docs.postman`, `/docs.openapi`)
- Web-интерфейс для внутренних операций (массовое удаление, смена цен, картинки, SQL): `routes/web.php`
- Админка на **Filament** (по умолчанию обычно `/admin`, если не переопределено)

## Требования

- PHP `^8.2`
- Composer
- Node.js + npm (для Vite, если нужен фронтенд/ассеты)
- БД для самого приложения (пользователи, токены, очередь): по умолчанию `sqlite` (см. `.env.example`)
- Redis (опционально, но используется для прогресса задач и некоторых эндпоинтов)

## Быстрый старт (локально)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate

npm install
composer run dev
```

`composer run dev` запускает:

- `php artisan serve`, `php artisan queue:listen`, `php artisan pail`, `npm run dev` (Vite)

## Документация API

Если `config/scribe.php` не изменяли, документация доступна по:

- `GET /docs`
- `GET /docs.postman`
- `GET /docs.openapi`

Генерация документации:

```bash
php artisan scribe:generate
```

## Аутентификация

API использует **Laravel Sanctum** (Bearer token).

Публичные эндпоинты:

- `POST /api/login`
- `POST /api/refresh-token`
- `POST /api/register`

Пример логина:

```bash
curl -s -X POST "$APP_URL/api/login" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"user@example.com","password":"secret"}'
```

Дальше для защищенных эндпоинтов добавляйте:

- `Authorization: Bearer <access_token>`

`access_token` в этом проекте имеет срок жизни 60 минут, после истечения токен удаляется middleware `CheckAccessTokenExpiration`.

## Выбор магазина/БД (header `database`)

Большинство бизнес-эндпоинтов требуют заголовок:

- `database: <connection_name>`

Значение должно входить в список `DB_ARRAY` (JSON-массив строк) и соответствовать именованному подключению в `config/database.php`.

Пример:

```bash
curl -s "$APP_URL/api/open-cart/language" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -H "database: xl.de"
```

Ограничения по доменам:

- `OpenCart` эндпоинты ожидают `database`, в котором **нет** подстроки `jv`
- `CosmoShop` эндпоинты ожидают `database`, в котором **есть** подстрока `jv`

## Rate limit и логирование API

- Rate limit: `50 req/sec` на IP (лимитер `request-limit`)
- На API-запросы ставится `X-Request-ID` (если не передан, генерируется)
- Логи API пишутся в `storage/logs/api/*.log`
- `storage/logs/api/general.log`
- `storage/logs/api/creation.log`
- `storage/logs/api/update.log`
- `storage/logs/api/deletion.log`
- `storage/logs/api/errors.log`

## Фоновые задачи (queue)

Очередь по умолчанию `database` (см. `QUEUE_CONNECTION` в `.env.example`), поэтому важны миграции (`jobs`, `failed_jobs` и т.д.).

Ручной запуск воркера:

```bash
php artisan queue:listen --tries=1
```

Статусы некоторых фоновых задач читаются из Redis:

- `GET /api/mass-delete`
- `GET /api/change-price`
- `GET /api/images/{ean}`

## Отправка логов в Telegram

Эндпоинт:

- `GET /api/send-logs`

Ограничение: отправка возможна один раз в день (фиксируется в таблице `logs`).

Нужно настроить переменные окружения:

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_CHAT_ID`

## Переменные окружения (помимо стандартных Laravel)

- `DB_ARRAY`: JSON-массив допустимых имен подключений к магазинам, например `["xl.de","jv.de"]`
- `SCRIBE_AUTH_TOKEN`: токен, который Scribe будет подставлять в примеры запросов
- `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID`: для `/api/send-logs`
- `API_TOKEN`: статический токен для middleware `CheckAPIToken` (сейчас в `routes/api.php` он не включен)
- `XLDE_TOKEN`: используется в `ShopDatabaseMiddleware` для некоторых обходных HTTP-вызовов под `xl.de`

## Полезные ссылки внутри проекта

- API роуты: `routes/api.php`, `routes/api/open-cart.php`, `routes/api/cosmo-shop.php`
- Web роуты: `routes/web.php`
- Конфиг Scribe: `config/scribe.php`
- Миграции: `database/migrations/`
