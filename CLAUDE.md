# CLAUDE.md — Agent / developer context

Quick orientation for humans and AI coding agents working on this repo.

## Tech stack

- **PHP 8+** custom MVC inspired by Laravel conventions (**not** Laravel / no Composer framework)
- **MySQL** via **PDO** prepared statements
- **jQuery 3.7** (CDN) for AJAX + form UX
- Apache front controller under `public/`
- Runs on XAMPP / WAMP / LAMP / MAMP localhost

## High-level architecture (Laravel-like)

```text
Request
  → public/index.php
  → bootstrap (Application + Container)
  → routes/web.php
  → Middleware pipeline
  → FormRequest validation (when type-hinted)
  → Http\Controller
  → Service
  → Model (PDO)
  → Response / View (resources/views)
```

## Folder map

| Path | Role |
|------|------|
| `public/index.php` | Front controller |
| `bootstrap/autoload.php` | PSR-4 `App\` autoload + helpers |
| `bootstrap/app.php` | Application + service bindings |
| `routes/web.php` | HTTP routes + middleware |
| `app/Foundation/` | Application, Container, Request, Response, Router, View, Database, Validator, Model |
| `app/Http/Controllers/` | Thin HTTP controllers |
| `app/Http/Middleware/` | e.g. `VerifyCsrfToken`, `PreventDuplicateSubmission` |
| `app/Http/Requests/` | Form requests (`StorePurchaseRequest`) |
| `app/Services/` | Business orchestration (`PurchaseService`) |
| `app/Models/` | Persistence (`Purchase`) |
| `app/Support/helpers.php` | `app()`, `config()`, `view()`, `response()` |
| `resources/views/` | PHP view templates |
| `config/database.php` | **Only file to edit for DB credentials** |
| `config/app.php` | Timezone, hash salt, cookie name/TTL |
| `database/purchase_entry.sql` | Schema + seed rows |

## Routes

| Method | Path | Action | Middleware |
|--------|------|--------|------------|
| GET | `/` | `PurchaseController@index` | — |
| POST | `/purchase/store` | `PurchaseController@store` | `VerifyCsrfToken`, `PreventDuplicateSubmission` |
| GET | `/report` | `ReportController@index` | — |

Prefer PATH_INFO URLs so routing works without `mod_rewrite`:

- `http://localhost:8888/purchase-entry-track/public/index.php`
- `http://localhost:8888/purchase-entry-track/public/index.php/report`
- `POST .../public/index.php/purchase/store`

## Coding conventions

- Controllers stay thin: authorize/validate via FormRequest + middleware → Service → Model → Response.
- All SQL uses named placeholders; never concatenate user input into SQL.
- Escape output in views with `htmlspecialchars()`.
- Keep frontend (`public/assets/js/form.js`) and `StorePurchaseRequest` rules aligned.
- Bind new services/controllers in `bootstrap/app.php` when they need constructor injection.
- Register routes only in `routes/web.php`.

## Validation rules (both layers)

| Field | Rule |
|-------|------|
| amount | digits only |
| buyer | letters, spaces, digits; max 20 chars |
| receipt_id | letters only; max 20 |
| items | letters/spaces per item; ≥1 item; comma-joined in DB |
| buyer_email | valid email; max 50 |
| note | any Unicode; max 30 words |
| city | letters and spaces; max 20 |
| phone | digits only; must start with `880` |
| entry_by | digits only |

Server-only: `buyer_ip`, `hash_key` (SHA-512 of `receipt_id` + salt), `entry_at` (local date).

## Report pagination

- `ReportController` uses 5 rows per page.
- `PurchaseService::paginateReport()` + model `countFiltered` / `findFiltered(limit, offset)`.
- Page links preserve active filters.

## Run / test locally

1. Import `database/purchase_entry.sql`.
2. Adjust `config/database.php` if needed (MAMP often port `8889`, user/pass `root`/`root`).
3. Open `http://localhost:8888/purchase-entry-track/public/index.php` (adjust host/port to your stack).
4. Submit a valid form once; confirm JSON success and row on `index.php/report`.
5. Confirm second submit within 24h is rejected (middleware + cookie).
6. To re-test submit: delete cookie `purchase_submitted`.

### Quick CLI import (MAMP)

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot < database/purchase_entry.sql
```

### Optional PHP syntax check

```bash
find app bootstrap public config routes -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Do not

- Add Composer frameworks (Laravel, etc.) unless explicitly requested.
- Trust `buyer_ip` or `hash_key` from the client.
- Use raw SQL string interpolation for filters or inserts.
- Put business logic in views or fat controllers — prefer Services.
