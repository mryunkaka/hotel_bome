# Feature Register and Global Task Map

Status dan urutan resume bersumber dari `docs/product/MASTER_BUILD_PLAN.md` (master queue) dan `docs/AI_WORKFLOW_STATE.md` (checkpoint tunggal). Register ini indeks cakupan, bukan pemilih task berikutnya. `PARTIAL`/`NOT IMPLEMENTED` diverifikasi dari artifacts yang tercatat; bukan klaim acceptance. `READY` hanya untuk slice bounded dengan dependencies/decision/test tersedia; `BLOCKED:<ID>` wajib alasan eksplisit.

| Feature | Scope | Status berdasarkan bukti repo | Dependencies / gate |
|---|---|---|---|
| `DISC-001` | Product/property/SOP/pilot decisions | PARTIAL planning; keputusan bisnis tersisa | owner decisions; MBP planning |
| `OPS-001` | Runtime/bootstrap/health | PARTIAL app-level | Windows device/runtime/service/signer/pilot external |
| `IAM-001` | Users/roles/property access | Local routes, query audit, membership/session tests and operator guidance verified (SQLite) | MySQL/Windows/browser, hotel SOP and external acceptance |
| `PROP-001` | Property/outlet/room/status | PARTIAL CRUD/history; current app-defined tenant boundary/role denial locally verified | MySQL concurrency, browser/device acceptance, room-availability integration and hotel SOP |
| `GUEST-001` | Guest/privacy/documents | PARTIAL prototype exists (`display_name`, free-text notes); both may contain personal data; operational acceptance BLOCKED:D-007 | Owner/privacy counsel must approve fields including display name/notes, purpose, basis, access, rights, and retention before real guest data is used |
| `RATE-001` | Rates/tax/availability | PARTIAL: rate plan master CRUD implemented (IDR price, configurable tax, effective dates); rate quote service implemented (room-type matching, PBJT tax, half-up rounding, nightly breakdown); snapshot/availability/booking NOT IMPLEMENTED | D-004 confirmed sementara; snapshot, availability engine, MySQL concurrency |
| `RES-001` | Booking/group/walk-in | NOT IMPLEMENTED | D-005 SOP + guest/rate |
| `FO-001` | Stay lifecycle/front desk | NOT IMPLEMENTED | D-005 + reservation |
| `FIN-001` | Folio/payments/invoice | NOT IMPLEMENTED | D-004/D-010 |
| `FIN-002` | Cashier/business date/night audit | NOT IMPLEMENTED | D-010 + FIN-001 |
| `AUD-001` | Audit taxonomy/view/retention | PARTIAL: read-only viewer; policy/source scope open | IAM, taxonomy/retention decision |
| `RPT-001` | Operational/financial reports/export | NOT IMPLEMENTED | FIN-002, D-004/D-010, report policy |
| `OPS-002` | Backup/restore/health/update | NOT IMPLEMENTED/plan only | OS, key custody, target restore |
| `HK-001` | Housekeeping queue | PARTIAL provisional queue | SOP owner, MySQL, pilot |
| `ENG-001` | Work orders/assets | PARTIAL provisional queue/register | SOP owner, MySQL, pilot |
| `INV-001` | Inventory/procurement/minibar | NOT IMPLEMENTED | valuation/units/approval policy |
| `EVT-001` | Events/catering/BEO | NOT IMPLEMENTED | commercial workflow |
| `POS-001` | Local POS/room service | NOT IMPLEMENTED | D-009/D-010/provider/tender |
| `ACC-001` | Accounting | NOT IMPLEMENTED | accountant signoff |
| `HW-001` | Doorlock/RFID | NOT IMPLEMENTED | D-006, vendor/license/device |
| `SYNC-001` | Outbox/central sync | NOT IMPLEMENTED | D-007 privacy/conflict policy |
| `EXT-001` | OTA/payment/portal | NOT IMPLEMENTED | D-009 provider/contract |
| `REL-001` | Signed release/license | NOT IMPLEMENTED | D-008, signer/entitlement |
| `QA-001` | Pilot acceptance/release audit | NOT RUN | target environment + required feature gates |
| `UI-001` | Authenticated appearance controls and dark-mode contrast | PARTIAL: controls, tests, and CSS build pass; visual browser acceptance pending | UI-002 page/navigation parity slices follow legacy reference `D:\Project\hotel-bome`; no Filament code is copied |

Queue dan detailed phase acceptance: `MASTER_BUILD_PLAN.md` (MBP-002..004 DONE; lihat queue tersebut untuk status bukti, backlog dan satu-satunya next task). Package map: `FEATURE_SPEC_MAP.md`. Fase/rangka dependency: `ROADMAP.md`. Task implementasi rinci tetap di feature `tasks.md`; tidak ada feature module yang dianggap selesai hanya karena ada spec atau test historis.
