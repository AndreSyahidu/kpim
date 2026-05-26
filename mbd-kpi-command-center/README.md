# MBD KPI Command Center

Internal WordPress performance-management system for **MBD Kontraktor**.
It is a *performance operating system* — not just a dashboard — connecting
strategy, OKR, KPI, action plans, evidence, review cadence and management
decisions. The daily application is served from the front-end route **`/kpi`**.

> ⚠️ **Status: 0.2.0 — MVP / in development. This is NOT a production 1.0 release.**
> The data model (foundation) is intentionally ahead of the UI for several
> modules. Treat schemas, scoring and flows as stabilising, not final.

---

## Purpose

Provide a private, role-aware system where MBD Kontraktor can manage and
review performance against KPIs and objectives, with built-in anti-cosmetic
controls (missing data is never green, evidence-gated action-plan closure,
audited target/formula changes, period locks with immutable snapshots).

## Requirements

- WordPress **6.0+**
- PHP **7.4+** (tested against PHP 8.4 syntax)
- MySQL/MariaDB with `dbDelta`-compatible privileges

## Installation

1. Copy the `mbd-kpi-command-center/` folder into `wp-content/plugins/`.
2. In **wp-admin → Plugins**, activate **MBD KPI Command Center**.
3. On activation the plugin will:
   - create its custom database tables (via `dbDelta`),
   - register roles and capabilities,
   - grant all plugin capabilities to the WordPress `administrator` role,
   - flush rewrite rules so `/kpi` resolves.
4. If `/kpi` returns a 404, go to **Settings → Permalinks** and click **Save**
   to re-flush rewrite rules.

## Accessing the app

- Daily users: **`https://your-site/kpi`**
- Admin configuration only: **wp-admin → MBD KPI**
  (`wp-admin/admin.php?page=mbd-kpi-settings`)

Access rules:
- Not logged in → redirected to the WordPress login, returned to `/kpi` after.
- Logged in without `mbd_kpi_access` → access-denied screen.
- Authorized → the KPI Command Center.

> The system never uses `/erp` or `/erp/kpi`. The only app route is `/kpi`.

## Roles & capabilities

Roles created on activation:

| Role | Key |
| --- | --- |
| MBD Owner | `mbd_owner` |
| MBD Director | `mbd_director` |
| MBD Division Head | `mbd_division_head` |
| MBD Supervisor | `mbd_supervisor` |
| MBD Staff | `mbd_staff` |
| MBD KPI Admin | `mbd_kpi_admin` |

Capabilities:

`mbd_kpi_access`, `mbd_kpi_view_all`, `mbd_kpi_view_division`,
`mbd_kpi_view_team`, `mbd_kpi_view_own`, `mbd_kpi_input_actual`,
`mbd_kpi_verify`, `mbd_kpi_manage_registry`, `mbd_kpi_manage_okr`,
`mbd_kpi_manage_action`, `mbd_kpi_manage_review`, `mbd_kpi_manage_settings`,
`mbd_kpi_view_audit`.

Data scope is derived from the highest view capability:
`view_all → company`, `view_division → division`, `view_team → team`,
otherwise `view_own → self`.

## Database tables

All tables are prefixed with `{$wpdb->prefix}mbd_kpi_`:

`divisions`, `employees`, `objectives`, `key_results`, `dictionary`,
`registry`, `targets`, `actuals`, `scores`, `scorecards`, `weights`,
`action_plans`, `action_updates`, `evidence`, `reviews`, `review_items`,
`review_decisions`, `root_causes`, `escalations`, `period_locks`,
`snapshots`, `audit_logs`, `settings`.

> **Foundation ahead of UI:** the schema provisions all 23 tables, while the
> current UI fully exercises a subset. This is intentional so later modules
> can be added without migrations.

## Routes

| Route | Page |
| --- | --- |
| `/kpi` | Executive Command Center |
| `/kpi/scorecard` | Balanced Scorecard |
| `/kpi/okr` | OKR |
| `/kpi/kpi-registry` | KPI Registry + Dictionary |
| `/kpi/kpi-actual` | KPI Targets & Actuals |
| `/kpi/action-plan` | Action Plans |
| `/kpi/evidence` | Evidence Center |
| `/kpi/review` | Review Room |
| `/kpi/exception` | Exception Dashboard |
| `/kpi/my` | My KPI |
| `/kpi/team` | Team & Divisions |
| `/kpi/settings` | Settings & Administration |

## Implemented modules (0.2.0)

- KPI Dictionary, KPI Registry, Targets, Actuals
- Score Engine (positive/negative formulas, configurable cap, status
  classification) and Data Health Score
- Action Plans + progress updates, Evidence upload/verification
- OKR (objectives + key results), Review Room (sessions + decisions)
- Root cause tracking, Escalation (incl. consecutive-red auto-escalation)
- Period locks + immutable snapshots
- Executive / Division / My-KPI / Exception dashboards
- Role-based access and Audit Log

## Demo seed data

Example KPIs are **opt-in** and **disabled by default** to keep production
clean. Enable either way, then (re)activate the plugin:

- Constant: `define( 'MBD_KPI_ENABLE_DEMO_SEED', true );` in `wp-config.php`
- Setting: **wp-admin → MBD KPI → Demo Seed Data**

Seeding only runs when enabled **and** no KPIs exist yet. Existing data is
never deleted.

## Known limitations

- Foundation tables exceed current UI coverage (see above).
- Actuals are manual entry only (no automated data feeds yet).
- Renaming a column via `dbDelta` is not possible; on databases created
  before 0.2.0 the legacy `employees.status` column may remain orphaned
  alongside `employee_status` (harmless).
- No automated test suite yet; QA is manual (see `QA-CHECKLIST.md`).

## Next recommended task

**Task 2.1 — KPI Core QA and Data Flow Completion:** harden and complete the
KPI Dictionary → Registry → Target → Actual → Score Engine → Data Health →
Period Lock → Exception Dashboard data flow end to end.

## License

GPL-2.0-or-later.
