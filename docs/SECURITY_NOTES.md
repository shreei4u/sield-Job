# Shield Job Portal — Database Deployment Package

## What's in this zip

```
shield-job-portal-database/
├── database/
│   ├── shield_jobportal_mysql_schema.sql   ← import this in phpMyAdmin
│   └── seed_admin.php                      ← run once, then delete
├── config/
│   ├── db_config.php                       ← PHP connects to MySQL through this
│   └── .htaccess                           ← blocks browser access to the config folder
└── DEPLOY_README.md                        ← this file
```

## Deployment steps

1. **Import the schema**
   Log in to phpMyAdmin → select the `wordpress-35303837748d` database (do **not** create a new one) → **Import** tab → choose `database/shield_jobportal_mysql_schema.sql` → Go.
   Afterward, check the table list: you should see 22 new tables, all starting with `jp_`, and nothing existing (`wp_...`) should have changed.

2. **Upload `config/` and `database/seed_admin.php` to your server**
   Preserve the folder structure so `require_once __DIR__ . '/../config/db_config.php';` in `seed_admin.php` resolves correctly.

3. **Create the admin account**
   Visit `https://ems.shieldinfrasolutions.in/database/seed_admin.php` once in your browser, confirm the success message, then **delete `seed_admin.php` from the server immediately.**

4. **Rotate the MySQL password**
   This password has now passed through a shared document and this chat. Change it in your hosting panel and update `config/db_config.php` to match, before this goes anywhere near production traffic.

## Security / bug / loophole review — what I checked

| Area | Finding | Fixed in this package |
|---|---|---|
| Password storage | README/docx had the admin password in **plain text** | `seed_admin.php` hashes it with bcrypt (`password_hash`, cost 12) before it ever touches the database |
| SQL injection | N/A yet — no backend existed | `db_config.php` uses PDO **prepared statements** with `EMULATE_PREPARES` off, so any future query code is protected by default |
| Config exposure | A `db_config.php` sitting in a web-servable folder can be requested directly and, if PHP isn't executing (misconfigured server), served as plain text | Added `.htaccess` denying all direct access to `/config/` |
| Error leakage | Default PDO exceptions can print host/db/user details to the browser on failure | Connection errors are logged server-side only; the browser gets a generic message |
| Database collision | Reusing an existing WordPress DB risks table-name clashes with WP core or plugins now or later | Every table is prefixed `jp_`; nothing references or touches `wp_*` tables |
| Orphaned data | Original design implied cascading relationships (delete a user → their profile/applications should go too) | Real `FOREIGN KEY ... ON DELETE CASCADE` / `SET NULL` constraints enforce this at the database level, not just in app code |
| File uploads | Prototype "stores" resumes only as an in-memory browser `File` object — nothing is ever actually uploaded anywhere | `resume_file_url` is ready for a real file (local disk outside web root, or S3/GCS) once an upload endpoint exists — flagged as unfinished below, not silently pretended-fixed |
| Payments | ATS Boost / subscriptions are marked "paid" with no real gateway | Left as-is structurally (`ats_paid_at`, `plan` fields exist) — wiring Razorpay/Stripe is a separate task, noted so it isn't forgotten |

## The one thing this package does **not** do

Your `script.js` has zero `fetch`/API calls anywhere — the live site currently keeps every user, job, and application only in browser memory for that one session. This package gives you a correct, secure, ready-to-use MySQL database and connection layer, but **nothing on the front end talks to it yet**. To actually make registrations, job posts, applications, etc. persist, you'll need PHP (or another backend) API endpoints — e.g. `api/register.php`, `api/jobs.php`, `api/apply.php` — that use `getDbConnection()` from `config/db_config.php` and that `script.js` is rewired to call instead of writing to its in-memory arrays.

Happy to build that API layer next, endpoint by endpoint, whenever you're ready — it's a bigger job than this database package, so I kept it separate rather than bolt on rushed, unreviewed code.
