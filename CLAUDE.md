# CLAUDE.md — Agent / developer context

Quick orientation for humans and AI coding agents working on this repo.

## Tech stack

- **PHP 7.4+** custom MVC / OOP (no Laravel, CodeIgniter, or other frameworks). PHP 8 string helpers are polyfilled in `bootstrap.php` for 7.4 hosts.
- **MySQL** via **PDO** prepared statements
- **jQuery 3.7** (CDN) — AJAX submit, realtime form validation, multi-item UI
- Front controller: project-root `index.php` (short URL) or `public/index.php`
- Runs on XAMPP / WAMP / LAMP / MAMP
- **Optional Composer** — PHPUnit + PHPCS only (app does not need `vendor/`)

## Architecture

```text
Request → entry script (/, /report, /purchase/store/) → Controller → Model (PDO) → View
```

Clean URLs use folder `index.php` files so they work on MAMP/XAMPP **without** `mod_rewrite` (often disabled by default).

## Folder map

| Path | Role |
|------|------|
| `index.php` | Home / purchase form (`/purchase-entry-track/`) |
| `install/index.php` | First-run DB setup UI (`/install/`) |
| `report/index.php` | Report page (`/purchase-entry-track/report`) |
| `purchase/store/index.php` | JSON store endpoint (`POST …/purchase/store/`) |
| `bootstrap.php` | Shared autoload, timezone, CSRF session, install redirect |
| `app/Core/Installer.php` | Writes `config/database.php` + imports SQL |
| `app/Core/SubmitLock.php` | 24h cookie lock + next-available time helpers |
| `config/installed.lock` | Created by wizard when setup is done |
| `public/index.php` | Optional PATH_INFO front controller |
| `public/assets/js/form.js` | Client validation, items UI, AJAX + CSRF headers |
| `public/assets/js/report.js` | Report Details accordion (one open, Esc / Close) |
| `public/assets/css/style.css` | UI styles |
| `app/Core/Router.php` | Method + path → controller action |
| `app/Core/Database.php` | PDO singleton |
| `app/Core/Validator.php` | Server-side field rules (standalone) |
| `app/Core/Controller.php` | `view()` / `json()` / URL helpers |
| `app/Core/Model.php` | Base model with PDO |
| `app/Core/Csrf.php` | Session CSRF token generate / validate |
| `app/Models/Purchase.php` | `create()`, `countFiltered()`, `findFiltered()` |
| `app/Controllers/PurchaseController.php` | Form + store (CSRF, cookie, IP, hash) |
| `app/Controllers/ReportController.php` | Report filters + pagination + per_page |
| `app/Views/` | PHP templates |
| `config/database.php` | DB credentials (**written by `/install/` wizard**; manual edit still OK) |
| `config/app.php` | Timezone, hash salt, cookie name/TTL |
| `database/purchase_entry.sql` | Schema + 5 seed rows |
| `tests/Unit/` | PHPUnit tests (`Validator`, `Csrf`, `Router`) |
| `phpunit.xml` / `phpcs.xml` | Test + PSR-12 config |
| `composer.json` | Dev deps: phpunit, phpcs |

## Routes

| Method | Path | Action |
|--------|------|--------|
| GET | `/` | `PurchaseController@index` |
| POST | `/purchase/store` | `PurchaseController@store` (JSON) |
| GET | `/report` | `ReportController@index` |

Prefer clean URLs (no `index.php` in the path):

- `http://localhost:8888/purchase-entry-track/`
- `http://localhost:8888/purchase-entry-track/report`
- `POST …/purchase-entry-track/purchase/store/`

Views use `appUrl` (project root, e.g. `/purchase-entry-track`) for links and `baseUrl` (`…/public`) for assets.

## Coding conventions

- Namespace `App\...` → `app/...` via autoloader in `public/index.php` (runtime) or Composer PSR-4 (tests).
- Controllers: CSRF / cookie / validate → server-only fields → model → JSON/HTML.
- All SQL uses named placeholders; never interpolate user input into SQL.
- Escape view output with `htmlspecialchars()`.
- Keep JS and PHP validation aligned; never remove backend checks.
- Prefer small classes in `app/Core`.
- PHPDoc on public/protected APIs in Core, Controllers, Models.
- Style: PSR-12 (`composer phpcs`).

## Validation rules (both layers)

| Field | Rule |
|-------|------|
| amount | digits only |
| buyer | letters, spaces, digits; max 20 |
| receipt_id | letters only; max 20 |
| items | letters/spaces per item; ≥1; comma-joined in DB |
| buyer_email | valid email; max 50 |
| note | Unicode OK; max 30 words; required |
| city | letters and spaces; max 20 |
| phone | digits only; must start with `880` |
| entry_by | digits only |

Server-only: `buyer_ip`, `hash_key` (SHA-512 of `receipt_id` + salt), `entry_at` (local date).

## Store security checklist

1. CSRF (`_token` or `X-CSRF-TOKEN`) via `App\Core\Csrf`
2. 24h cookie `purchase_submitted`
3. `Validator` on all form fields
4. `buyer_ip` / `hash_key` / `entry_at` never taken from client

## Report behaviour

- Filters: `date_from`, `date_to`, `entry_by`
- Pagination: page links + **Per page** at **bottom only** (`5|10|25|50`, default `5`)
- Columns: serial `#` (left, continues across pages), then primary fields, then **Details** (right)
- Email, phone, note, IP, id open via Details accordion (`report.js`)
- Query selects only columns needed for the UI (no `hash_key` in the list query)

## Run / test the app

1. Start Apache + MySQL.
2. Open `http://localhost:8888/purchase-entry-track/` → complete `/install/` (MAMP or XAMPP preset + import SQL).
3. Submit once → JSON success → row on report.
4. Second submit within 24h → rejected (cookie).
5. Re-test submit: delete cookie `purchase_submitted`.
6. Re-run DB setup: delete `config/installed.lock` and visit `/install/`.

### Quick CLI import (MAMP)

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot < database/purchase_entry.sql
```

### PHP lint

```bash
find app public config tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

### PHPUnit + PHPCS (optional)

```bash
composer install
composer test
composer phpcs
```

## Do not

- Add application frameworks (Laravel, etc.) unless explicitly requested.
- Trust `buyer_ip` or `hash_key` from the client.
- Concatenate user input into SQL.
- Require Composer/`vendor` for the app to run in the browser.
