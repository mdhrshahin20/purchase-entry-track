# Purchase Entry & Reporting System

Plain PHP **custom MVC** + MySQL + jQuery purchase-receipt entry and reporting tool.  
**No** Laravel, CodeIgniter, or other PHP frameworks.

**You do not need Composer to run this project.** Prefer **Docker** (one command), or unzip → Apache/MySQL → browser setup wizard.  
`composer install` is **optional** and only needed if you want to run PHPUnit / PHPCS.

---

## Quick start with Docker (recommended for developers)

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose v2).

```bash
cd purchase-entry-track
docker compose down
docker compose up --build
```

Use **`up --build`** (not only `start`) after pulling updates so images rebuild.

Then open:

| Page | URL |
|------|-----|
| Entry form | http://localhost:8080/ |
| Report | http://localhost:8080/report |

What Compose starts:

- **app** — PHP 8.2 + Apache (`mod_rewrite`); code is **copied into the image** (no host bind-mount)
- **db** — MySQL 8 image with `database/purchase_entry.sql` baked in for first-boot import

This avoids Docker Desktop “mounts denied” errors when the project lives under `/Applications/MAMP/htdocs` (macOS often does not share `/Applications`).

No `/install` wizard is needed: the app entrypoint waits for MySQL, writes a container-local DB config, and creates `config/installed.lock`. Your local `config/database.php` (MAMP/XAMPP) is left alone.

Optional: copy `.env.example` → `.env` to change ports/passwords.

```bash
docker compose down          # stop
docker compose down -v       # stop + wipe MySQL volume (re-seed on next up)
```

Host MySQL is published on **3307** by default (avoids clashing with MAMP/XAMPP).

### Live-edit source from the host (optional)

Default Compose does **not** mount the project folder (portable under MAMP’s `/Applications` path).

For live reload, put the project under a shared path such as `~/Projects/…`, then:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

Or keep it in MAMP and add **`/Applications`** in Docker Desktop → Settings → Resources → File Sharing, then use the same `docker-compose.dev.yml` command.
---

## Prerequisites (without Docker)

| Requirement | Suggested version |
|-------------|-------------------|
| PHP | **7.4+** (8.0–8.4 recommended). PHP 8 string helpers are polyfilled for 7.4 |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache / LiteSpeed | PHP module or PHP-FPM. `mod_rewrite` optional (PATH_INFO works without it) |
| Stack | XAMPP, WAMP, LAMP, **MAMP**, or **Docker** |
| Browser | Any modern browser |
| Composer | **Not required** for the app. Optional for unit tests / code style only |

---

## Quick start (MAMP / XAMPP — no Composer)

1. Place the folder in your web root (step 1 below).
2. Start Apache + MySQL (step 2).
3. Open `http://localhost:8888/purchase-entry-track/` — you are redirected to the **Setup** page.
4. Click **MAMP** or **XAMPP / WAMP** to auto-fill defaults (or type your own), keep “import SQL” checked, click **Save & install**.
5. Click **Open application** and use the form / report.

No PHP file editing is required for database credentials. Skip Composer unless you want tests.

---

## 1. Place the project files

Copy the project folder into your web server document root:

| Stack | Typical document root |
|-------|------------------------|
| **MAMP (macOS)** | `/Applications/MAMP/htdocs/` |
| **XAMPP (Windows)** | `C:\xampp\htdocs\` |
| **XAMPP (macOS/Linux)** | `/opt/lampp/htdocs/` or `/Applications/XAMPP/htdocs/` |
| **WAMP** | `C:\wamp64\www\` |
| **LAMP** | `/var/www/html/` |

Example (MAMP):

```text
/Applications/MAMP/htdocs/purchase-entry-track/
```

---

## 2. Start Apache and MySQL

Start Apache and MySQL from your stack’s control panel.

**MAMP**

- Apache port often **8888**
- MySQL port often **8889**
- User / password often `root` / `root`

**XAMPP / WAMP**

- Apache often port **80**
- MySQL often port **3306**
- User `root`, password usually **empty**

---

## 3. Run the setup wizard (recommended)

Open the project URL (or go straight to setup):

| Stack | Setup URL |
|-------|-----------|
| MAMP (8888) | http://localhost:8888/purchase-entry-track/install |
| Apache :80 | http://localhost/purchase-entry-track/install |

Until setup finishes, visiting the home page automatically redirects here.

On the form:

1. Choose **MAMP** or **XAMPP / WAMP** quick-fill (or enter host/port/user/password yourself).
2. Leave **Create database and import …purchase_entry.sql** checked.
3. Click **Save & install**.
4. Click **Open application**.

The wizard will:

- Create the MySQL database if it does not exist
- Import the schema + 5 sample rows
- Write `config/database.php`
- Create `config/installed.lock` so setup is not required again

### Re-run setup later

Delete `config/installed.lock`, then open `/install` again (optional **Force reinstall**).

Ensure the web server can write inside `config/` (normal for local MAMP/XAMPP).

### Optional: manual SQL / config (advanced)

If you prefer not to use the wizard:

1. Import `database/purchase_entry.sql` via phpMyAdmin or the MySQL CLI.
2. Edit `config/database.php` credentials by hand.
3. Create an empty file `config/installed.lock` (or run the wizard once with import unchecked).

CLI import examples:

```bash
# MAMP
/Applications/MAMP/Library/bin/mysql -u root -proot < database/purchase_entry.sql

# XAMPP / port 3306
mysql -u root < database/purchase_entry.sql
```

---

## 4. Optional app settings

Timezone, hash salt, and the 24h submit cookie live in `config/app.php` (defaults are fine for local review).

---

## 5. Open and test under localhost

| Stack | Entry form | Report |
|-------|------------|--------|
| MAMP (8888) | http://localhost:8888/purchase-entry-track/ | http://localhost:8888/purchase-entry-track/report |
| Apache :80 | http://localhost/purchase-entry-track/ | http://localhost/purchase-entry-track/report |

Setup: `…/install` · Store: `POST …/store`  
Prefer **no trailing slash** (`/report`, not `/report/`).

Routing is global via the root `.htaccess` + `routes/web.php`.

### Smoke-test checklist

1. Entry form loads.
2. Add items via **Add Item**, fill required fields, submit — AJAX success (no full page reload).
3. Phone shows locked **880** prefix; local digits only in the input.
4. CSRF token is sent with the AJAX POST (`_token` / `X-CSRF-TOKEN`).
5. Second submit within 24 hours is blocked by cookie `purchase_submitted`.
6. Report shows seed rows + new submissions.
7. Filter by date range and/or user ID (`entry_by`).
8. Change **Per page** at the **bottom** of the report (5 / 10 / 25 / 50).
9. Report table: serial `#` on the left; **Details** on the right expands email / phone / note / IP.

### Sample valid form values

| Field | Example |
|-------|---------|
| Amount | `1500` |
| Buyer | `Test User` |
| Receipt ID | `NEWID` (letters only) |
| Items | `Laptop`, `Mouse` (add via UI) |
| Email | `test@example.com` |
| Note | `Short note under thirty words.` |
| City | `Dhaka` |
| Phone | `1712345678` → stored as `8801712345678` |
| Entry By | `1` |

---

## Architecture overview

Custom MVC + OOP (plain PHP), **one front controller**, **one `.htaccess`**:

```text
Request → index.php → bootstrap.php → Application → routes/web.php → Router
        → Controller → Model (PDO) → View
```

To add a feature: create a controller action and register it in **`routes/web.php` only**.  
Do **not** add root folders like `report/` or extra `.htaccess` files under `app/`.

### Folder structure

```text
purchase-entry-track/
├── index.php            front controller (only HTTP entry)
├── .htaccess            ONLY htaccess — routing + block app/config/…
├── bootstrap.php
├── docker-compose.yml   PHP + MySQL stack
├── Dockerfile
├── docker/              Apache vhost + app entrypoint
├── routes/
│   └── web.php          all GET/POST routes
├── app/                 Controllers, Models, Views, Core (not web-routed)
├── config/
├── database/
│   └── purchase_entry.sql
├── public/
│   └── assets/          css + js
├── tests/
├── README.md
├── CLAUDE.md
└── AI_USAGE.md
```

| Layer | Responsibility |
|-------|----------------|
| **routes/web.php** | Maps URLs → controller actions |
| **Application** | Loads routes and dispatches |
| **Controller** | HTTP / CSRF / cookies / JSON/HTML |
| **Model** | PDO prepared statements |
| **View** | Templates |
| **Core** | Router, Url, Database, Validator, Csrf, Installer, SubmitLock |
| **`.htaccess`** | Clean URLs + deny direct PHP under `app/` etc. |

---

## Behaviour notes (design decisions)

| Topic | Decision |
|-------|----------|
| `buyer_ip` | From `$_SERVER['REMOTE_ADDR']` only — never from POST |
| `hash_key` | `hash('sha512', receipt_id . salt)` via `config/app.php` |
| `entry_at` | Current date in `Asia/Dhaka` (configurable) |
| CSRF | Session token; required on `POST /purchase/store` |
| 24h lock | HttpOnly cookie stores unlock timestamp; form shows next available time + live countdown |
| Items | Comma-separated string in `items` (varchar 255) |
| Receipt ID | Letters only (“text only”) |
| Phone | JS prepends `880`; backend requires leading `880` |
| Report | Filters + pagination; serial `#` left, **Details** right; email/phone/note/IP in expand panel. Query omits unused `hash_key`. |
| Frontend UX | Realtime form validation; report accordion Details / Hide + Esc to close |

---

## Required credentials (defaults)

| Item | Default |
|------|---------|
| Setup wizard | `/install` (UI — preferred) |
| DB name | `purchase_entry` |
| DB user | `root` |
| DB password | `root` (MAMP) or empty (XAMPP/WAMP) |
| DB host/port | `127.0.0.1:8889` (MAMP) or `:3306` (XAMPP/WAMP) |

There is no application login; `entry_by` is a numeric user id on the form.

---

## Optional only: PHPUnit & PHPCS (Composer)

**Skip this section to run the project.** The browser app does not load `vendor/` and does not call Composer.

Use Composer only if you want automated tests or PSR-12 checks on a machine that has Composer installed:

```bash
cd purchase-entry-track
composer install          # installs PHPUnit + PHPCS into vendor/ (dev only)
composer test             # PHPUnit (Validator, Csrf, Router)
composer phpcs            # PSR-12 via phpcs.xml
composer phpcbf           # auto-fix some style issues
```

If you never run `composer install`, the project still runs normally in the browser.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| 500 on `/report/` (trailing slash) | Fixed: app redirects `/report/` → `/report`. Also enable **mod_rewrite** in MAMP (`LoadModule rewrite_module` in `httpd.conf`) then restart Apache |
| Clean URLs 404 but `/index.php` works | `mod_rewrite` is disabled. Uncomment `LoadModule rewrite_module modules/mod_rewrite.so` in MAMP Apache config and restart |
| 403 Forbidden on `/purchase-entry-track/` | Ensure latest `.htaccess` (do not blanket-deny all `*.php`). Restart Apache in MAMP if needed |
| Docker: mounts denied / `/Applications/...` not shared | Default Compose no longer bind-mounts the project. Run `docker compose down && docker compose up --build`. Or add `/Applications` under Docker Desktop → File Sharing |
| Docker: port 8080 in use | Set `APP_PORT=8081` in `.env` and re-run `docker compose up` |
| Docker: app stuck “Waiting for MySQL” | `docker compose logs db`; first boot can take ~30s; try `docker compose down -v && docker compose up --build` |
| Docker: empty report / no tables | Wipe volume and re-import: `docker compose down -v && docker compose up --build` |
| Database connection failed | Re-open `/install`, fix credentials, Save again (or check MySQL is running). Under Docker, ensure `DB_HOST=db` is set by Compose |
| Redirected to `/install` forever | Finish the wizard; ensure `config/` is writable so `installed.lock` can be created |
| Setup cannot write config | Give the web server write permission on `config/` |
| 404 on report or store | Open `…/report` and `POST …/store` (front controller + `routes/web.php`) |
| 500 mentioning `RewriteEngine` | App does not require rewrite; ignore or leave `IfModule` wrappers as-is |
| CSS/JS missing | Hard-refresh; assets are under `/purchase-entry-track/public/assets/` |
| CSRF token mismatch | Refresh the form page so a new session token loads |
| Cannot resubmit for testing | Delete cookie `purchase_submitted` (and optionally clear session) |
| Blank page / `str_starts_with` undefined | Use PHP **7.4+**. Update XAMPP/WAMP PHP, or switch MAMP to PHP 8.x. Polyfills load from `bootstrap.php`. |
| Do I need `composer install`? | **No** — only for optional `composer test` / `composer phpcs` |
| `composer test` fails | Run `composer install` first (tests only; app still runs without it) |

---

## Deliverable ZIP

Zip the project folder including:

- Full source (`app/`, `public/`, `config/`, `database/`, `tests/`, `docker/`)
- `database/purchase_entry.sql`
- `docker-compose.yml`, `Dockerfile`, `.env.example`
- `README.md`, `CLAUDE.md`, `AI_USAGE.md`
- `composer.json`, `phpunit.xml`, `phpcs.xml`

You may omit `vendor/` from the ZIP. Reviewers **do not** need `composer install` to run the app; that command is only for optional tests. With Docker: `docker compose up --build`.
