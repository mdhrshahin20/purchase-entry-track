# AI_USAGE.md

This project was built with AI coding assistance (Cursor) under human direction. Below is an honest account of how AI was used, what worked, and what needed correction.

## What AI was used for

1. **Custom MVC scaffolding** — `app/Controllers`, `app/Models`, `app/Views`, and `app/Core` (Router, Database, Validator, Csrf) without installing Laravel or any application framework.
2. **Validation parity** — matching frontend (jQuery) and backend (`Validator`) rules from the assignment brief.
3. **Security** — CSRF session tokens, 24-hour submit cookie, server-only `buyer_ip` / `hash_key` / `entry_at`.
4. **Report features** — date/user filters, pagination, per-page size (bottom only), serial `#`, Details accordion on the right.
5. **SQL + seed data** — schema matching required columns and five sample rows with consistent SHA-512 `hash_key` values.
6. **Quality tooling** — PHPUnit unit tests, PHPCS (PSR-12), PHPDoc on Core/Controllers/Models; Composer used only as optional dev tooling.
7. **Documentation** — README installation guide, CLAUDE.md agent context, and this file.
8. **Form UX** — realtime field validation and a clear error summary on submit.

Human decisions included: treating `receipt_id` as letters-only; storing multiple items as a comma-separated `varchar`; default timezone `Asia/Dhaka`; credentials only in `config/database.php`; and keeping a **classic MVC** layout (not a Laravel-lookalike) so the submission matches “your own MVC / no framework.”

## One prompt / approach that worked well

Providing the **full assignment text** and asking for implementation against an explicit checklist (MVC + PDO, dual validation, cookie lock, CSRF, server-only IP/hash/date, report filters, SQL seed, README / CLAUDE / AI_USAGE) produced a coherent first pass instead of piecemeal files with drifting field rules.

A useful follow-up: *“Align JS and PHP validators field-by-field; phone must prepend 880 in JS and be enforced on the server.”*

## Where AI was wrong or suboptimal — and how it was fixed

**Issue 1 — routing architecture**  
Tried `mod_rewrite`, then `FallbackResource`, then per-route root folders + many `.htaccess` files. Folders-per-route and nested Deny files do not scale.  
**Fix:** One front controller, one root `.htaccess` (route + block `*.php` except `index.php`), all endpoints in `routes/web.php` only.

**Issue 2 — overly framework-like folders**  
An intermediate layout mimicked Laravel (`Http/`, FormRequest, container, `routes/web.php`). Still not Laravel, but risky for a “no framework” brief.  
**Fix:** Restructured to classic **Controllers / Models / Views / Core**.

**Issue 3 — seed `hash_key` drift**  
Seed hashes must match `hash('sha512', receipt_id . salt)`.  
**Fix:** Recomputed with a PHP one-liner and pasted into the SQL.

**Issue 4 — report “Per page” placement**  
An early UI put per-page controls at top and bottom; the product preference was bottom only.  
**Fix:** Single **Per page** control in the bottom pagination bar.

**Issue 5 — Details column placement**  
First pass put the Details toggle on the left of the table.  
**Fix:** Moved serial `#` to the left and Details to the right (standard list + actions layout), with accordion UX in `report.js`.

## What was verified locally

- Form and report load on MAMP (`localhost:8888`)
- AJAX store with CSRF; reject without token
- Backend validation when JS is bypassed
- Cookie blocks second submit within 24 hours
- `composer test` (21 tests) and `composer phpcs` (PSR-12) pass after `composer install`
