# Purchase Entry & Reporting System

Plain PHP (custom MVC) + MySQL + jQuery purchase-receipt entry and reporting tool. No Laravel/CodeIgniter or other PHP frameworks.

## Prerequisites

| Requirement | Suggested version |
|-------------|-------------------|
| PHP | 8.0+ (tested with 8.1–8.4; needs `str_starts_with`) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | Any recent Apache (PHP module or PHP-FPM). `mod_rewrite` is **optional** |
| Stack | XAMPP, WAMP, LAMP, or **MAMP** |

Also needed: a browser and (optionally) phpMyAdmin to import the SQL file.

## 1. Place the project files

Copy the project folder into your web server document root:

| Stack | Typical document root |
|-------|------------------------|
| **MAMP (macOS)** | `/Applications/MAMP/htdocs/` |
| **XAMPP (Windows)** | `C:\xampp\htdocs\` |
| **XAMPP (macOS/Linux)** | `/opt/lampp/htdocs/` or `/Applications/XAMPP/htdocs/` |
| **WAMP** | `C:\wamp64\www\` |
| **LAMP** | `/var/www/html/` |

Final path example (MAMP):

```text
/Applications/MAMP/htdocs/purchase-entry-track/
```

## 2. Start Apache and MySQL

Start Apache and MySQL from your stack’s control panel (MAMP / XAMPP / WAMP).

**MAMP notes**

- Default Apache port is often **8888**.
- Default MySQL port is often **8889**.
- Default MySQL user/password is often `root` / `root`.

**XAMPP / WAMP notes**

- Apache is usually port **80**.
- MySQL is usually port **3306**.
- MySQL user is usually `root` with an **empty** password.

## 3. Import the database

Import `database/purchase_entry.sql`. This creates the `purchase_entry` database, the `purchases` table, and five sample rows.

### Option A — phpMyAdmin

1. Open phpMyAdmin (MAMP: `http://localhost:8888/phpMyAdmin/`).
2. Click **Import**.
3. Choose `database/purchase_entry.sql`.
4. Click **Go**.

### Option B — MySQL CLI (MAMP example)

```bash
/Applications/MAMP/Library/bin/mysql -u root -proot < /Applications/MAMP/htdocs/purchase-entry-track/database/purchase_entry.sql
```

### Option C — MySQL CLI (XAMPP / default port 3306)

```bash
mysql -u root < /path/to/purchase-entry-track/database/purchase_entry.sql
```

## 4. Configure database credentials

Edit **only** `config/database.php` if your MySQL settings differ from the defaults:

```php
return [
    'host' => '127.0.0.1',
    'port' => '8889',      // MAMP default; use 3306 for XAMPP/WAMP/LAMP
    'dbname' => 'purchase_entry',
    'username' => 'root',
    'password' => 'root',  // empty string '' for typical XAMPP/WAMP
    'charset' => 'utf8mb4',
];
```

No other code changes should be required.

Optional: timezone and hash salt live in `config/app.php` (`Asia/Dhaka` by default).

## 5. Open and test under localhost

Point your browser at the **public** front controller (`index.php`).  
Links use `/index.php/...` so the app works even when Apache `mod_rewrite` is disabled (common on some MAMP setups). If rewrite is enabled, the optional `.htaccess` rules still apply.

| Stack | Entry form | Report |
|-------|------------|--------|
| MAMP (port 8888) | http://localhost:8888/purchase-entry-track/public/index.php | http://localhost:8888/purchase-entry-track/public/index.php/report |
| Apache port 80 | http://localhost/purchase-entry-track/public/index.php | http://localhost/purchase-entry-track/public/index.php/report |

### Smoke-test checklist

1. **Entry form** loads at `.../public/index.php`.
2. Add one or more **items** with the “Add Item” control, fill required fields, submit — success message via AJAX (no full page reload).
3. Phone shows a locked **880** prefix; digits only in the input.
4. Reload and try submitting again within 24 hours — blocked by cookie (“already submitted”).
5. **Report** page shows seed rows plus any new submission.
6. Filter report by date range and/or user ID (`entry_by`).

### Sample form values (valid)

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

## Architecture overview

Custom **Laravel-inspired MVC** (plain PHP — no Laravel/Composer framework):

```text
public/index.php
  → bootstrap/autoload.php + bootstrap/app.php
  → routes/web.php
  → Middleware → FormRequest → Controller → Service → Model (PDO)
  → Response / resources/views
```

### Folder structure

```text
purchase-entry-track/
├── app/
│   ├── Foundation/      Application, Container, Request, Response, Router, View, DB, Validator
│   ├── Http/
│   │   ├── Controllers/ PurchaseController, ReportController
│   │   ├── Middleware/  PreventDuplicateSubmission
│   │   └── Requests/    StorePurchaseRequest
│   ├── Models/          Purchase
│   ├── Services/        PurchaseService
│   └── Support/         helpers (app, config, view, response)
├── bootstrap/           autoload.php, app.php
├── routes/web.php       route definitions
├── resources/views/     layouts + pages
├── config/              app.php, database.php
├── database/            purchase_entry.sql
├── public/              front controller + assets
├── README.md
├── CLAUDE.md
└── AI_USAGE.md
```

| Layer | Responsibility |
|-------|----------------|
| **Middleware** | Cross-cutting HTTP rules (24h cookie lock) |
| **FormRequest** | Backend validation rules for store |
| **Controller** | HTTP in/out only |
| **Service** | Hash, cookie, pagination orchestration |
| **Model** | PDO queries only |
## Behaviour notes (design decisions)

- **`buyer_ip`** — taken from `$_SERVER['REMOTE_ADDR']` only; never from POST.
- **`hash_key`** — `hash('sha512', receipt_id . salt)` using `config/app.php` salt.
- **`entry_at`** — current date in `Asia/Dhaka` (configurable).
- **24-hour lock** — HttpOnly cookie `purchase_submitted` (TTL 86400s); also checked on the server before insert.
- **Items** — stored as a comma-separated string in `items` (varchar 255), matching the schema.
- **Receipt ID** — letters only (interpreted as “text only”).
- **Phone** — JS prepends `880`; backend requires the value to start with `880`.

## Required credentials (defaults)

| Item | Default |
|------|---------|
| DB name | `purchase_entry` |
| DB user | `root` |
| DB password | `root` (MAMP) or empty (XAMPP/WAMP) |
| DB host/port | `127.0.0.1:8889` (MAMP) or `:3306` (XAMPP/WAMP) |

There is no application login; `entry_by` is a numeric user id entered on the form.

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Database connection failed | Fix `config/database.php` host/port/user/password. |
| 404 on report or store | Use `.../public/index.php/report` and `.../public/index.php/purchase/store` (PATH_INFO). Enable `mod_rewrite` only if you want shorter URLs. |
| 500 mentioning `RewriteEngine` | Your Apache build lacks `mod_rewrite`. This project wraps rewrite rules in `<IfModule>`; use the `index.php/...` URLs above. |
| Blank page | Check PHP error log; require PHP 8.0+. |
| Cannot resubmit for testing | Clear site cookies for localhost, or delete the `purchase_submitted` cookie. |

## Deliverable ZIP

Zip the entire `purchase-entry-track` folder (including `database/purchase_entry.sql` and the three markdown docs) for submission.
