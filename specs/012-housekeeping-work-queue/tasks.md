# Tasks: Housekeeping Work Queue

**Feature ID**: HK-001 | **Depends on**: IAM-001, PROP-001 | **Status**: local implementation slice complete; provisional SOP defaults are in `spec.md`; target-DB and property-manager acceptance remain external.

- [x] HK-001-T01 Additive task/event schema with same-property room FK, status/assignee indexes, immutable event triggers; SQLite schema, cross-property FK, update/delete triggers and `HousekeepingTaskMigrationTest` rollback verified.
- [x] HK-001-T02 Transactional queue service: create/assign/start/complete/cancel; active property/room and same-property active housekeeping membership checks; immutable actor/status/assignment history; no room-status side effect. Focused service tests pass.
- [x] HK-001-T03 Role/property-protected routes and responsive queue UI: pagination, status filter, create/assign/actions, validation, history and empty state. Housekeeping membership route tests pass; platform-admin access relies on existing middleware bypass and is guarded by active-property check.
- [x] HK-001-T04 Focused tests cover lifecycle, invalid transitions/terminal state, cross-property room/task IDs, unqualified assignee, route denial, escaping/history, no room-status side effect, unknown status rejection and empty “All” filter.
- [x] HK-001-T05 Fresh `sudo -u saidadam28 composer test --no-interaction`: 189 tests/947 assertions; Pint 102 files; PHPStan 0 errors. Composer dependency deprecation notices; exit 0. Vite not rerun; no frontend source changed.
- [x] HK-001-T06 Render history regression: `HousekeepingTaskController::index` now passes property-scoped `$eventsByTask` to Blade; actor/event assertion RED→GREEN.
- [x] HK-001-T07 Filter validation regression: unknown `status=archived` rejected; UI `status=` “All” remains accepted. `HousekeepingTaskTest`: 10 tests/27 assertions; Pint six changed PHP files pass.
External acceptance remains incomplete: MySQL locking, browser/Windows and property-manager SOP.

No physical delete. SQLite success does not prove MySQL locking, Windows/LAN acceptance, or hotel SOP approval.
