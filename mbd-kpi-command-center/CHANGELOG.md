# Changelog

All notable changes to **MBD KPI Command Center** are documented here.
This project adheres to [Semantic Versioning](https://semver.org/) (pre-1.0:
the API and schema may still change between minor versions).

## [0.2.0] — Audit & stabilization

### Changed
- Version corrected from `1.0.0` to `0.2.0` to reflect MVP / in-development
  status (plugin header + `MBD_KPI_VERSION` constant + admin status).
- Demo seed data is now **opt-in and disabled by default** (production-safe).
  Controlled by the `MBD_KPI_ENABLE_DEMO_SEED` constant or the
  `enable_demo_seed` setting; seeding still only runs when no KPIs exist and
  never deletes existing data.

### Added
- `mbd_kpi_view_own` capability (granted to all roles) and explicit `self`
  scope handling, completing the canonical capability set.
- `mbd_kpi_demo_seed_enabled()` helper.
- Admin settings: Demo Seed toggle and expanded System Status
  (demo-seed state, development-status note).
- Project docs/tooling: `README.md`, `CHANGELOG.md`, `QA-CHECKLIST.md`,
  `.gitignore`.

### Notes
- No tables were dropped or renamed in this pass. Schema remains
  migration-safe; foundation tables intentionally exceed current UI coverage.

## [0.1.0] — Foundation

### Added
- Initial plugin: `/kpi` front-end router with auth gate and nonce-protected
  POST handling; 23 custom tables; six roles + capability matrix; score
  engine and data-health scoring; KPI registry/dictionary/targets/actuals;
  action plans, evidence, OKR, review room, root cause, escalation, period
  locks, snapshots and audit log.
- Centralised asset class and split layout partials
  (`header` / `sidebar` / `topbar`); standalone `app/access-denied.php`.
- Foundation table schema alignment (`divisions`, `employees`, `audit_logs`,
  `settings`) including `employees.employee_status`, `phone`, and
  `audit_logs.user_agent`.
