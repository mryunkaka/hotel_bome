# Feature Specification Map

Setiap feature tetap bounded; packages mengelompokkan dokumen, bukan menyatukan seluruh PMS menjadi satu implementasi. Register/status ada di `FEATURE_REGISTER.md`; satu-satunya urutan task resume ada di `MASTER_BUILD_PLAN.md`. `AI_WORKFLOW_STATE.md` hanya mencatat checkpoint dan menunjuk satu task READY sesuai queue, tidak membuat urutan kedua.

| Package | Feature IDs / register entries | Catatan status |
|---|---|---|
| `specs/001-discovery-baseline` | DISC-001 | planning/owner decisions |
| `specs/002-offline-runtime-bootstrap` | OPS-001 | app-level partial; target deployment external |
| `specs/003-identity-property-access` | IAM-001 | partial; T04/T05b/T06 remains open |
| `specs/004-property-guest-foundation` | PROP-001, GUEST-001 | property partial; guest blocked D-007 |
| `specs/005-rates-and-reservations` | RATE-001, RES-001 | unimplemented; D-004/D-005 gates |
| `specs/006-frontdesk-stay-lifecycle` | FO-001 | unimplemented; D-005 gate |
| `specs/007-cashier-finance-close` | FIN-001, FIN-002 | unimplemented; D-004/D-010 gates |
| `specs/008-hotel-operations-recovery` | AUD-001, HK-001, ENG-001, INV-001, EVT-001, POS-001, RPT-001, OPS-002 | audit/HK/ENG partial; other feature gates separate |
| `specs/009-accounting-and-hardware` | ACC-001, HW-001 | unimplemented; accountant/vendor/device gates |
| `specs/010-connectors-release-acceptance` | SYNC-001, EXT-001, REL-001, QA-001 | unimplemented/not run; external gates |
| `specs/011-audit-foundation` | AUD-001 viewer slice | partial local viewer; broader taxonomy/retention in package 008 |
| `specs/012-housekeeping-work-queue` | HK-001 provisional queue slice | partial; SOP/MySQL/pilot not accepted |
| `specs/013-engineering-work-orders` | ENG-001 provisional work-order/asset slice | partial; SOP/MySQL/pilot not accepted |

Task readiness wajib mencantumkan scope, prerequisites, acceptance, verification command, risks and status in the feature task file. Feature task files may be split into additional packages when slice independently releasable. Status claims must match evidence; simulator/SQLite/Linux tests do not prove external acceptance.
