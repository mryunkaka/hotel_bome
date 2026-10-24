# Tasks: Engineering Work Orders

**Feature ID**: ENG-001 | **Depends on**: IAM-001, PROP-001 | **Status**: implementation slice complete; ENG-D1/ENG-D2 provisional; property-manager/pilot acceptance external.

- [x] ENG-001-T01 Additive order/event schema, same-property room/task constraints, immutable triggers; migration rollback test passes.
- [x] ENG-001-T02 Transactional create/assign/start/resolve/cancel; active operations membership/property/room checks and event audit; no room status side effect.
- [x] ENG-001-T03 Property-role protected routes, operations workspace selector, responsive queue forms, filters, pagination and history.
- [x] ENG-001-T04 Tests cover lifecycle, cross-property IDs, nonmember assignee, XSS, audit immutability, no room status writes, IDOR on direct list/create/update/deactivate/assign/transition paths, and invalid asset/work-order filter rejection. Focused WorkOrder/asset tests: 23 tests/96 assertions.
- [x] ENG-001-T05 Fresh `sudo -u saidadam28 composer test --no-interaction`: 190 tests/955 assertions; Pint 102 files; PHPStan 0 errors. Composer dependency deprecation notices remain; exit 0.
- [x] ENG-001-T07 Add property-scoped read-only work-order detail page with history and navigation from mobile/desktop list; direct cross-property detail returns 404. `WorkOrderTest`: 15 tests/58 assertions. Scratch Vite build passed 22 modules.
- [x] ENG-001-T06 Basic property-scoped asset register CRUD, deactivate/history, active/inactive filter and responsive UI; work orders can reference active same-property asset; cross-property reads and asset links are rejected. GET `status` now validates `active|inactive`; unknown filter returns validation error instead of silently selecting active assets.

External: MySQL concurrency, owner/property-manager SOP, Windows/LAN/browser and pilot acceptance remain open.

No asset costing, purchasing, preventive maintenance schedule, financial posting, or implicit out-of-order transition in this slice.
