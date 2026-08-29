# Shield Job Portal — Complete Backend API

This replaces the earlier auth-only package. It covers every module described
in your README: job postings + ATS, job seeker & freelancer profiles,
freelancer/trainer offerings + hire requests, manpower, contractor
projects/bids, CRM, admin controls, platform pricing, and resume upload.

## Deploy steps (in order)

1. **Import schema files in phpMyAdmin**, in this order, into the
   `wordpress-35303837748d` database:
   - `shield_jobportal_mysql_schema.sql` (from the first package — skip if already imported)
   - `database/02_login_attempts_addon.sql`
   - `database/03_feature_addons.sql`
   *(`03` uses `ADD COLUMN IF NOT EXISTS`, which needs MySQL 8.0.29+/MariaDB. If your host runs older MySQL 5.7 and the import errors, tell me and I'll send a version without that clause.)*
2. **Upload `api/`, `config/`, and `private_storage/`** to your server, same relative layout.
   `private_storage/` should ideally sit **outside** `public_html` — if your hosting only gives you one web-servable folder, leave it where it is; the `.htaccess` inside blocks direct browser access either way, and belt-and-suspenders is fine here.
3. Confirm `database/seed_admin.php` from the first package was already run and deleted. If not, do that first — nothing here creates the admin account.

## Full endpoint reference

| Module | Endpoint | Method | Role |
|---|---|---|---|
| Auth | `/api/register.php` | POST | public |
| | `/api/login.php` | POST | public |
| | `/api/logout.php` | POST | any |
| | `/api/session.php` | GET | any |
| Job seeker profile | `/api/profile/jobseeker_get.php` | GET | jobseeker/admin/employer |
| | `/api/profile/jobseeker_save.php` | POST | jobseeker |
| Freelancer profile | `/api/profile/freelancer_get.php` | GET | any logged-in |
| | `/api/profile/freelancer_save.php` | POST | freelancer |
| Jobs | `/api/jobs/create.php` | POST | employer |
| | `/api/jobs/list.php` | GET | public |
| | `/api/jobs/mine.php` | GET | employer |
| | `/api/jobs/close.php` | POST | employer (own) |
| | `/api/jobs/delete.php` | POST | employer (own) |
| Applications / ATS | `/api/applications/apply.php` | POST | jobseeker |
| | `/api/applications/mine.php` | GET | jobseeker |
| | `/api/applications/for_job.php?job_id=` | GET | employer (own job) |
| | `/api/applications/update_status.php` | POST | employer (own job) — also syncs ATS pipeline |
| Freelancer offerings | `/api/freelancer/offerings_create.php` | POST | freelancer |
| | `/api/freelancer/offerings_mine.php` | GET | freelancer |
| | `/api/freelancer/offerings_delete.php` | POST | freelancer (own) |
| | `/api/freelancer/offerings_browse.php` | GET | any logged-in |
| Trainer courses | `/api/trainer/courses_create.php` | POST | trainer |
| | `/api/trainer/courses_mine.php` | GET | trainer |
| | `/api/trainer/courses_delete.php` | POST | trainer (own) |
| | `/api/trainer/courses_browse.php` | GET | any logged-in |
| Hire requests | `/api/hire_requests/create.php` | POST | employer |
| | `/api/hire_requests/incoming.php` | GET | freelancer/trainer |
| | `/api/hire_requests/mine.php` | GET | employer |
| | `/api/hire_requests/respond.php` | POST | freelancer/trainer (own) |
| Manpower | `/api/manpower/workforce_create.php` | POST | manpower |
| | `/api/manpower/workforce_mine.php` | GET | manpower |
| | `/api/manpower/workforce_delete.php` | POST | manpower (own) |
| | `/api/manpower/locations_save.php` | POST | manpower (replaces full list) |
| | `/api/manpower/browse.php` | GET | employer |
| | `/api/manpower/request_create.php` | POST | employer |
| | `/api/manpower/requests_incoming.php` | GET | manpower |
| | `/api/manpower/requests_update.php` | POST | manpower (own) |
| Contractor | `/api/contractor/projects_create.php` | POST | contractor |
| | `/api/contractor/projects_mine.php` | GET | contractor |
| | `/api/contractor/projects_update_status.php` | POST | contractor (own) |
| | `/api/contractor/project_request_create.php` | POST | employer (creates a "received bid") |
| | `/api/contractor/bids_incoming.php` | GET | contractor |
| | `/api/contractor/bids_respond.php` | POST | contractor (own) |
| Admin — Databases | `/api/admin/users_list.php?role=` | GET | admin |
| | `/api/admin/users_delete.php` | POST | admin |
| Admin — Review queue | `/api/admin/review_update.php` | POST | admin (approve/reject jobs, offerings, courses, profiles) |
| Admin — CRM | `/api/admin/leads_list.php` | GET | admin |
| | `/api/admin/leads_update_status.php` | POST | admin |
| | `/api/admin/notes_add.php` | POST | admin |
| | `/api/admin/notes_list.php?user_id=` | GET | admin |
| | `/api/admin/activity_feed.php` | GET | admin |
| | `/api/admin/dashboard_stats.php` | GET | admin — KPI tiles |
| Admin — Pricing | `/api/admin/pricing_get.php` | GET | any logged-in (read) |
| | `/api/admin/pricing_update.php` | POST | admin (write) |
| Resume | `/api/uploads/resume_upload.php` | POST (multipart) | jobseeker |
| | `/api/uploads/resume_download.php?user_id=` | GET | owner, admin, or employer with an application from them |

Every endpoint returns JSON: `{"success": true, ...}` or `{"success": false, "error": "..."}` with an appropriate HTTP status (400/401/403/404/409/429/500).

## What every endpoint enforces

- **Prepared statements everywhere** — no string-concatenated user input ever reaches SQL.
- **Ownership checks** — an employer can only close/delete/view applicants for *their own* jobs; a freelancer can only delete *their own* offerings; etc. (`require_owned_row()` in `bootstrap.php`).
- **Role gates** — `require_role('employer')` and friends reject any other logged-in role with 403, and reject anonymous requests with 401.
- **Login brute-force lockout** — 5 failed attempts per email+IP locks for 15 minutes.
- **Resume files** are stored outside the download path with random filenames; downloads are permission-checked per request, never served as static files.

## What's still genuinely open

1. **`script.js` isn't wired to any of this yet.** I built the previous auth snippets as an example — happy to now go through `script.js` function by function and replace the in-memory writes with real calls to this API, but that's a careful, incremental job on a 150K-character file I'd want to do live against your actual code (and ideally test against your server) rather than guess blind. Tell me if you want me to start, and which role/tab to begin with.
2. **Payments** (ATS Boost, Premium subscription, custom hiring assistance) still aren't wired to a real gateway — `ats_paid_at` / `jp_subscriptions.plan` exist as fields but nothing sets them from a real transaction yet.
3. **Admin-curated manpower/contractor "contract details"** (`jp_manpower_contract_details`/`jp_manpower_contract_roles`) don't have endpoints yet — lower priority, tell me if you need them.
4. I still can't run a PHP linter in this environment (no PHP available here), so while every file was written carefully and several were re-reviewed line-by-line (and one real bug was caught and fixed during review — a column-name mismatch in `pricing_update.php`), please treat the first real run on your server as the actual test, and send me any error you hit — I'll fix it immediately.
