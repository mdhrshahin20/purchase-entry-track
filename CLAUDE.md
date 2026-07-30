# CLAUDE.md — Agent / developer context

Quick orientation for humans and AI coding agents working on this repo.

## Tech stack

- **PHP 7.4+** custom MVC / OOP (no Laravel / CodeIgniter). PHP 8 string helpers polyfilled in `bootstrap.php`.
- **MySQL** via **PDO** prepared statements
- **jQuery 3.7** (CDN)
- **Single front controller** `index.php` + **`routes/web.php`**
- **One** root `.htaccess` (routing + security). No nested `.htaccess`, no per-route root folders.
- XAMPP / WAMP / LAMP / MAMP, or **Docker Compose** (`docker compose up --build` → http://localhost:8080/)
- Composer optional (PHPUnit / PHPCS only)

## Architecture

```text
Request → index.php → Application → routes/web.php → Router → Controller → Model → View
```

### Adding a new page / endpoint

1. Add a controller action under `app/Controllers/`
2. Register it in **`routes/web.php`**
3. Stop. Do **not** create `something/` at project root or new `.htaccess` files.

## Folder map

| Path | Role |
|------|------|
| `index.php` | Front controller (only HTTP entry) |
| `.htaccess` | Optional pretty URLs; PATH_INFO works without rewrite |
| `bootstrap.php` | Autoload, session, install gate |
| `routes/web.php` | **All** route definitions |
| `app/Core/Config.php` | Cached app/database config (`Config::app()`, `Config::database()`) |
| `app/Controllers/*` | Purchase, Report, Install |
| `app/Models/Purchase.php` | Persistence |
| `app/Views/` | Templates |
| `config/` | app + database config (not publicly executable) |
| `public/assets/` | CSS / JS |
| `database/purchase_entry.sql` | Schema + seeds |
| `docker-compose.yml` / `Dockerfile` | Local Docker stack (PHP Apache + MySQL) |
| `docker/` | Apache vhost + entrypoint (wait for DB, create `installed.lock`) |

### Docker notes

- App DocumentRoot is the project root → URLs are `/`, `/report`, `/store`.
- Default Compose copies the app into the image (no bind-mount) so `/Applications/MAMP/...` works without Docker File Sharing. Optional live mount: `docker-compose.dev.yml`.
- MySQL image is built from `docker/mysql/Dockerfile` with `purchase_entry.sql` baked into `/docker-entrypoint-initdb.d/` (first volume boot only).
- Entrypoint writes DB credentials to `/var/www/docker-config/database.php` (outside any bind mount).

## Routes (`routes/web.php`)

| Method | Path | Action |
|--------|------|--------|
| GET | `/` | `PurchaseController@index` |
| POST | `/store` | `PurchaseController@store` |
| POST | `/purchase/store` | alias of store |
| GET | `/report` | `ReportController@index` |
| GET/POST | `/install` | `InstallController@index` |

Clean URLs optional. Default `pretty_urls` => false (works on LiteSpeed):

| Host | Report | Store |
|------|--------|-------|
| Domain root | `/index.php/report` | `POST /index.php/store` |
| Subfolder | `/purchase-entry-track/index.php/report` | `POST …/index.php/store` |

Set `'pretty_urls' => true` only if `/report` works on the host. Never add `report/` / `store/` folders.

## Coding conventions

- Namespace `App\...` → `app/...`
- Named PDO placeholders only; escape views with `htmlspecialchars()`
- Keep JS/PHP validation aligned; never drop backend checks
- PSR-12 / PHPDoc on Core, Controllers, Models

## Validation (both layers)

| Field | Rule |
|-------|------|
| amount | digits |
| buyer | letters/spaces/digits; max 20 |
| receipt_id | letters; max 20 |
| items | letters/spaces; ≥1; comma-joined |
| buyer_email | email; max 50 |
| note | Unicode; max 30 words; required |
| city | letters/spaces; max 20 |
| phone | digits; starts with `880` |
| entry_by | digits |

Server-only: `buyer_ip`, `hash_key`, `entry_at`.

## Run / test

1. Start Apache + MySQL
2. Open `/purchase-entry-track/` → finish `/install`
3. Submit form → see report
4. Optional: `composer install && composer test`

## Do not

- Add root folders per module (`report/`, `store/`, …)
- Add `.htaccess` under `app/`, `config/`, etc.
- Trust client `buyer_ip` / `hash_key`
- Require Composer to run the app in the browser
