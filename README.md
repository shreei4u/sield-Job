# Shield Job Portal

End-to-end job, freelance, training, manpower and contractual-project platform —
front end (six user roles, ATS, CRM) plus a real MySQL/PHP backend.

- **Install it:** see [`INSTALL.md`](INSTALL.md) — Docker or GitHub + shared hosting.
- **API reference:** see [`docs/API_REFERENCE.md`](docs/API_REFERENCE.md) — all 57 endpoints.
- **Security review notes:** see [`docs/SECURITY_NOTES.md`](docs/SECURITY_NOTES.md).
- **Frontend wiring status:** see [`docs/FRONTEND_WIRING_STATUS.md`](docs/FRONTEND_WIRING_STATUS.md) — what's connected to the API vs. still in-memory.

## Repo layout
```
shield-job-portal/
├── index.html, assets/            ← front end
├── api/                           ← PHP backend (57 endpoints)
├── config/                        ← DB connection (env-var or shared-hosting modes)
├── database/                      ← MySQL schema, imported in numeric order
├── docker-compose.yml, docker/    ← Docker path
├── install.php                    ← run once, then delete
└── docs/                          ← reference docs (linked above)
```

## Status
Backend: complete for every module in the original feature list.
Frontend: only the auth flow (register/login/logout/session) is wired to
the real API so far — everything else is still the original in-memory
prototype behavior. See `docs/FRONTEND_WIRING_STATUS.md`.
