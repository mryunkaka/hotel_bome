# Acceptance: HK-001 Housekeeping Work Queue

## Local acceptance

- [x] Task schema is additive, tenant-consistent for rooms, and prevents event update/delete.
- [x] Queue CRUD actions are authorized by active account and property-role middleware.
- [x] Create/assign/start/complete/cancel lifecycle records immutable history and enforces allowed transitions.
- [x] Cross-property room/task IDs and non-housekeeping assignees are rejected.
- [x] Completing a task does not alter room cleanliness or availability.
- [x] Queue supports status filter, pagination, responsive layouts, validation, visible property-scoped task history and escaped user text.
- [x] `HousekeepingTaskTest`: 10 tests / 27 assertions; regressions verify rendered actor/event history, invalid filter rejection and empty “All” filter behavior. Full suite: 189 tests / 947 assertions.
- [x] Pint: 102 files pass. PHPStan: zero errors. Vite not rerun after server-side validation changes; no frontend source changed. `git diff --check` pending final handoff check; Composer emitted dependency deprecation notices (exit 0).

## External / not accepted yet

- [ ] Property-manager/owner confirms provisional HK-D1 workflow for hotel operations.
- [ ] MySQL row locking and concurrent task transition behavior.
- [ ] Browser click-through on supported browser/device and Windows/LAN deployment.
- [ ] Pilot acceptance and housekeeping training.

Local checks do not imply external production acceptance.
