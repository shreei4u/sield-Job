# Shield Job Portal — Installation

This repo now works two ways from the exact same code — pick whichever fits.

---

## Option A — Docker (fastest, works anywhere Docker runs)

```bash
git clone <your-repo-url> shield-job-portal
cd shield-job-portal
cp .env.example .env
# edit .env — set real passwords, don't leave the defaults
docker compose up -d --build
```

This starts three containers:
- **app** — PHP 8.2 + Apache, serving the site at **http://localhost:8080**
- **db** — MySQL 8, with `database/*.sql` auto-imported on first boot (via `docker-entrypoint-initdb.d`)
- **phpmyadmin** — at **http://localhost:8081**, host `db`, using the credentials from `.env`

Then visit **http://localhost:8080/install.php** — it detects Docker automatically (env vars are already set), so it only asks you for the admin account name/email/password. Submit it, then **delete `install.php`** from the container (or rebuild without it — see below) and you're live.

To remove `install.php` after installing in Docker:
```bash
docker compose exec app rm /var/www/html/install.php
```

**Rebuilding after code changes:** this Dockerfile copies the code in at build time (not a live-mounted volume), so after editing `script.js`, an API file, etc., run `docker compose up -d --build` again to pick up the change.

---

## Option B — GitHub + your existing shared hosting (StackCP, cPanel, etc.)

```bash
git clone <your-repo-url>
```
Upload everything to your host (FTP/SFTP, or your host's Git deploy feature if it has one) — same relative folder structure.

Then visit **`https://yourdomain.com/install.php`** in a browser. Since no environment variables exist on shared hosting, it'll ask for:
- Database host, port, name, user, password (from your hosting panel)
- Admin name, email, password

Submitting the form:
1. Tests the DB connection
2. Runs all three schema files against your database (safe to re-run — uses `IF NOT EXISTS`)
3. Creates the admin account with a properly hashed password
4. Writes `config/db_config.local.php` with your connection details (gitignored — never gets committed)

**Delete `install.php` from the server immediately after it succeeds.**

*(You can still do the manual phpMyAdmin import instead if you prefer — the same `database/*.sql` files work either way, imported in numeric order. `install.php` is just faster and also handles the admin account for you.)*

---

## Either way, afterward

- Confirm `config/db_config.local.php` (shared hosting) or your `.env` (Docker) never gets committed — `.gitignore` already excludes both, don't override that.
- `private_storage/resumes/` holds uploaded resumes — back it up separately from your code; it's gitignored on purpose (real user files, not source).
- Rotate any credentials that passed through this chat or an uploaded doc before this ever handles real traffic.

## What's still open (same as before — unchanged by this packaging work)

- Only the **auth** flow (register/login/logout/session) in `script.js` is wired to the backend. Every other panel (jobs, applications, profiles, offerings, manpower, contractor, admin/CRM) still runs on in-memory arrays and needs the same wiring, module by module.
- No real payment gateway is connected yet (ATS Boost, Premium subscription).
- `.github/workflows/php-lint.yml` only checks PHP syntax on push — it's not a deployment pipeline. If you want actual auto-deploy-on-push (to a VPS or via FTP), that's a separate, host-specific setup — tell me which host/target and I'll build that workflow next.
