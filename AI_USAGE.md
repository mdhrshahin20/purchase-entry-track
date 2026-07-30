# AI_USAGE.md

This project was built with AI coding assistance (Cursor) under human direction. Below is an honest account of how AI was used, what worked, and what needed correction.

## What AI was used for

1. **Laravel-inspired architecture** — `Foundation` (Application/Container/Request/Response/Router), `Http` (Controllers, Middleware, FormRequests), `Services`, `routes/web.php`, `bootstrap/`, and `resources/views` — without installing Laravel.
2. **Scaffolding** — purchase store flow, report filters + pagination, dual validation, cookie lock.
3. **SQL + seed data** — schema and sample rows with matching SHA-512 `hash_key` values.
4. **Documentation** — README, CLAUDE.md, and this file.
## One prompt / approach that worked well

Giving the agent the **full assignment text** and asking it to implement against an explicit checklist (MVC + PDO, dual validation, cookie lock, server-only IP/hash/date, report filters, SQL seed, README/CLAUDE/AI_USAGE) produced a coherent first pass instead of piecemeal files that disagreed on field rules.

A useful follow-up pattern was: *“Align JS and PHP validators field-by-field with the brief; phone must prepend 880 in JS and be enforced on the server.”* That kept the two layers from drifting.

## Where AI was wrong or suboptimal — and how it was fixed

**Issue 1 — `mod_rewrite` assumed always on:** The first `.htaccess` used bare `RewriteEngine On`. On this MAMP install `mod_rewrite` is not loaded, so Apache returned **500** for every request under `public/` (including `index.php`).

**How it was caught:** Hitting `http://localhost:8888/purchase-entry-track/public/` and reading `apache_error.log` (`Invalid command 'RewriteEngine'`).

**Fix:** Wrap rewrite rules in `<IfModule mod_rewrite.c>`, route via **PATH_INFO** (`/public/index.php/report`), and generate links with `appUrl` pointing at `index.php` so the project runs without rewrite. Document both URL styles in the README.

**Issue 2 — seed `hash_key` consistency:** Seed hashes must match `hash('sha512', receipt_id . salt)` from `config/app.php`. Values were recomputed with a PHP one-liner and pasted into the SQL so report seed data stays consistent with runtime hashing.
