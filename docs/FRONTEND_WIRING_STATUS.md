# script.js — Auth wiring (pass 1 of several)

## What changed in this file
- Removed the hardcoded admin email + **plaintext password** that used to sit
  in `let users = [...]` at the top of the file — that shipped to every
  visitor's browser via view-source. Admin login now goes only through
  `/api/login.php`; the account itself is created once, server-side, via
  `database/seed_admin.php` (see the earlier packages).
- `doRegister()` now calls `POST /api/register.php` instead of pushing into
  an in-memory array. On success it logs the user straight into their
  dashboard (the API also starts their session), instead of bouncing them
  back to the login form.
- `doLogin()` now calls `POST /api/login.php`.
- `logout()` now calls `POST /api/logout.php` to actually end the server
  session (best-effort — the UI clears either way even if that call fails).
- Added `restoreSession()`, which calls `GET /api/session.php` on page load
  so a browser refresh doesn't silently log the user out.
- `currentUser` is now `{user_id, name, email, role}` from the server,
  rather than a full object with a plaintext password sitting in memory.

## What did NOT change yet
Everything past login — job posting, applications, profiles, offerings,
manpower, contractor, admin panels, CRM, pricing — is **still using the
old in-memory arrays** (`profiles`, `jobPostings`, `applications`, etc.).
They still work for the current browser session exactly as before, but
none of that data is saved to the database yet, and none of the 50+
other endpoints from the backend package are called yet.

I did auth first, on its own, because it's the one piece every other
role's dashboard depends on, and because I wanted it working and reviewed
before touching the bigger panels.

## Next step
Tell me which panel to wire next — natural order would be:
1. Employer → Job Postings + Applications (core hiring flow)
2. Job Seeker → Profile + Job Search + My Applications
3. Freelancer/Trainer → Profile + Offerings + Hire Requests
4. Manpower / Contractor panels
5. Admin panels (Databases, CRM, Pricing, Review queue)

Each of those is a similarly-sized, reviewable chunk — I'd rather do them
one at a time and confirm each works than batch-edit the whole file at once.
