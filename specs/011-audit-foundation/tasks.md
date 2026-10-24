# Tasks: Audit History Viewer

**Feature ID**: AUD-001 slice 1 | **Readiness**: Ready for local implementation; external retention approval remains open.

- [x] AUD-001-T01 Add feature tests for active platform-admin authorization, five event sources, source/property/date filters, bounded pagination, invalid filters, escaping, secret minimization and unchanged source rows. Evidence: `php artisan test --filter=AuditHistoryTest` passes 6 tests/75 assertions including source labels, IDs and tie-break order.
- [x] AUD-001-T02 Implement normalized read-only query, controller, view and platform-admin navigation; no schema changes or export. Evidence: route, controller, Blade view, sidebar links; filter predicates are applied per-source before `UNION ALL`.
- [x] AUD-001-T03 Run focused and full tests, Pint, PHPStan, Composer validation, Vite build and `git diff --check`; record SQLite/Linux limits. Evidence: focused 6/75; `composer test --no-interaction` 141/667, Pint/PHPStan pass; Composer validation, 22-module Vite build and diff check exit 0. SQLite/Linux only.
- [x] AUD-001-T04 Independent review found no Critical/Important issue. Minor exact-count concern fixed with `simplePaginate()`; source/event IDs are rendered and tested. Remaining full-sort cost is documented pending representative volume measurement. Reverification: focused 6/75, full 141/667, Pint, PHPStan, Composer validation, Vite and diff check pass; SQLite only.

Retention policy, Windows/MySQL acceptance, hotel SOP, financial-source events and exports are explicitly out of this slice.