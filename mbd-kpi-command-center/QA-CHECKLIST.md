# Manual QA Checklist — MBD KPI Command Center

Run these on a clean WordPress 6.x + PHP 7.4+ install. Tick each item.
Versions/routes assume the plugin folder is `mbd-kpi-command-center/`.

## 1. Activation

- [ ] Plugin activates with no fatal error or PHP warning.
- [ ] All 23 `*_mbd_kpi_*` tables are created (wp-admin → MBD KPI → System
      Status shows "23 of 23 tables present").
- [ ] Roles created: `mbd_owner`, `mbd_director`, `mbd_division_head`,
      `mbd_supervisor`, `mbd_staff`, `mbd_kpi_admin`.
- [ ] `administrator` has all `mbd_kpi_*` capabilities.
- [ ] Rewrite rules work: `/kpi` resolves (re-save Permalinks if 404).
- [ ] With demo seed **disabled** (default), no example KPIs are created.
- [ ] Enabling demo seed (constant or setting) + re-activate seeds 10 example
      KPIs once; re-activating again does not duplicate them.

## 2. Access control

- [ ] Logged-out user visiting `/kpi` is redirected to login and returned to
      `/kpi` after authenticating.
- [ ] Logged-in user **without** `mbd_kpi_access` sees the access-denied page.
- [ ] Authorized user sees the Executive Command Center.

## 3. Routes (each renders without error / no undefined variable notices)

- [ ] `/kpi`
- [ ] `/kpi/scorecard`
- [ ] `/kpi/okr`
- [ ] `/kpi/kpi-registry`
- [ ] `/kpi/kpi-actual`
- [ ] `/kpi/action-plan`
- [ ] `/kpi/evidence`
- [ ] `/kpi/review`
- [ ] `/kpi/team`
- [ ] `/kpi/my`
- [ ] `/kpi/exception`
- [ ] `/kpi/settings`
- [ ] Unknown route (e.g. `/kpi/does-not-exist`) falls back to the dashboard.

## 4. CRUD & data flow

- [ ] Division: create, edit, change status; appears in Team & Divisions.
- [ ] Employee: create with phone + status; appears in Employees list.
- [ ] KPI Dictionary: add a term; it appears and is selectable in the registry.
- [ ] KPI Registry: create and edit a KPI; thresholds/formula save correctly.
- [ ] Target: set a target for a KPI + period.
- [ ] Actual: input an actual; it is flagged **manual** and verification resets
      to pending.
- [ ] Score: performance score, data-health score and status compute and show.
- [ ] Status logic: a KPI with no actual shows **missing** (never green).
- [ ] Action plan: create from a red KPI.
- [ ] Action plan gating: cannot mark **done** without evidence; cannot mark
      **verified effective** without approved evidence.
- [ ] Evidence: upload a file and add a link; verify (approve/reject).
- [ ] Review: create a session and record a decision with owner + due date.
- [ ] Period lock: lock a period → actuals become read-only and a snapshot is
      saved; locked target change requires an adjustment note.

## 5. Security

- [ ] Submitting a form with a bad/missing nonce shows "Security check failed".
- [ ] A user lacking the relevant capability cannot perform the POST action
      (permission error, no data change).
- [ ] Accessing an invalid/foreign record id does not fatal and does not leak
      data outside the user's scope.
- [ ] XSS attempt (e.g. `<script>` in a KPI name) is escaped on output.
- [ ] SQL injection attempt in inputs/filters is neutralised (prepared SQL).
- [ ] Evidence upload rejects disallowed file types (only image/PDF/office).

## 6. Audit log

- [ ] Division create/update, registry/formula/threshold changes, target
      changes, actual create/verify, evidence verify, review decisions and
      period lock/unlock all appear in the audit log with user + timestamp.

## 7. UX

- [ ] Sidebar links match real routes; active item is highlighted.
- [ ] Topbar shows the current user; logout works.
- [ ] Layout is usable on tablet width (≤900px nav collapses).
- [ ] Empty states guide the user to the next action.
- [ ] No `/erp` naming anywhere; all links use `/kpi`.
