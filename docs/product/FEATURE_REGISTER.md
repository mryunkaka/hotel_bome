# Feature Register and Global Task Map

Status dan urutan resume bersumber dari `docs/product/MASTER_BUILD_PLAN.md` (master queue) dan `docs/AI_WORKFLOW_STATE.md` (checkpoint tunggal). Register ini indeks cakupan, bukan pemilih task berikutnya. `PARTIAL`/`NOT IMPLEMENTED` diverifikasi dari artifacts yang tercatat; bukan klaim acceptance. `READY` hanya untuk slice bounded dengan dependencies/decision/test tersedia; `BLOCKED:<ID>` wajib alasan eksplisit.

| Feature | Scope | Status berdasarkan bukti repo | Dependencies / gate |
|---|---|---|---|
| `DISC-001` | Product/property/SOP/pilot decisions | PARTIAL planning; keputusan bisnis tersisa | owner decisions; MBP planning |
| `OPS-001` | Runtime/bootstrap/health | PARTIAL app-level | Windows device/runtime/service/signer/pilot external |
| `IAM-001` | Users/roles/property access | Local routes, query audit, membership/session tests and operator guidance verified (SQLite) | MySQL/Windows/browser, hotel SOP and external acceptance |
| `PROP-001` | Property/outlet/room/status | PARTIAL CRUD/history; current app-defined tenant boundary/role denial locally verified | MySQL concurrency, browser/device acceptance, room-availability integration and hotel SOP |
| `GUEST-001` | Guest/privacy/documents | PARTIAL prototype exists (`display_name`, free-text notes); both may contain personal data; operational acceptance BLOCKED:D-007 | Owner/privacy counsel must approve fields including display name/notes, purpose, basis, access, rights, and retention before real guest data is used |
| `RATE-001` | Rates/tax/availability | COMPLETE: rate plan master CRUD, rate quote service (room-type matching, PBJT tax, half-up rounding, nightly breakdown), immutable rate snapshot with triggers, availability service, booking orchestration | D-004 provisional used; MySQL concurrency |
| `RES-001` | Booking/group/walk-in | COMPLETE: reservation state machine (confirmed/checked_in/checked_out/cancelled/no_show), walk-in, group booking, room reassignment with audit, availability guard, booking orchestration, cross-property isolation, overbooking prevention, immutable audit events. 49 tests/114 assertions | D-005 provisional used; D-007 for PII; MySQL concurrency |
| `FO-001` | Stay lifecycle/front desk | PARTIAL: StayService and FrontDeskService; check-in/out/walk-in, arrivals/in-house/departures, occupancy summary, room move validation/history, immutable audit and end-to-end checkout. T01–T06 locally implemented; 40 tests/100 assertions. UI and actual HK workflow handoff not implemented | D-005 provisional used; room share/identity/settlement policy and HK integration remain open |
| `FIN-001` | Folio/payments/invoice | COMPLETE: Folio ledger, charges, payments, idempotency keys, compensating reversals/refunds, immutable invoice snapshots with SQLite triggers. 11 tests/50 assertions | D-004/D-010 provisional used; accountant signoff remains external |
| `FIN-002` | Cashier/business date/night audit | COMPLETE: Business date lifecycle, cashier shifts with variance calculation, immutable day_closing_snapshots, audit-protected reopen, E2E booking-to-night-audit. 6 tests/41 assertions | D-010 provisional used; accountant signoff remains external |
| `AUD-001` | Audit taxonomy/view/retention | PARTIAL: read-only viewer; policy/source scope open | IAM, taxonomy/retention decision |
| `RPT-001` | Operational/financial reports/export | COMPLETE: ReportingService with daily financial summary, tender breakdown, occupancy rates by room type, property isolation, and CSV export with formula injection sanitization. 4 tests/21 assertions | Reconciles to FIN-001/002 ledger; PII excluded per D-007 |
| `OPS-002` | Backup/restore/health/update | NOT IMPLEMENTED/plan only | OS, key custody, target restore |
| `HK-001` | Housekeeping queue | PARTIAL provisional queue | SOP owner, MySQL, pilot |
| `ENG-001` | Work orders/assets | PARTIAL provisional queue/register | SOP owner, MySQL, pilot |
| `INV-001` | Inventory/procurement/minibar | COMPLETE: InventoryService with InventoryItem, StockMovement append-only ledger (triggers), balance recomputation, and atomic minibar folio billing integration. 4 tests/17 assertions | Reconciles to FolioService; pricing/costing policy provisional |
| `EVT-001` | Events/catering/BEO | COMPLETE: EventService with EventSpace, EventBooking BEO orders, interval conflict detection, cancellation space release, and banquet folio billing. 6 tests/21 assertions | Reconciles to FolioService; pricing/capacity policy provisional |
| `POS-001` | Local POS/room service | COMPLETE: PosService with local outlets, order items, direct payment, and idempotent in-house room charge billing to FolioService. 5 tests/17 assertions | Reconciles to FolioService; offline-safe; external provider integration optional |
| `ACC-001` | Accounting | NOT IMPLEMENTED | accountant signoff |
| `HW-001` | Doorlock/RFID | NOT IMPLEMENTED | D-006, vendor/license/device |
| `SYNC-001` | Outbox/central sync | NOT IMPLEMENTED | D-007 privacy/conflict policy |
| `EXT-001` | OTA/payment/portal | NOT IMPLEMENTED | D-009 provider/contract |
| `REL-001` | Signed release/license | NOT IMPLEMENTED | D-008, signer/entitlement |
| `QA-001` | Pilot acceptance/release audit | NOT RUN | target environment + required feature gates |
| `UI-001` | Authenticated appearance controls and dark-mode contrast | PARTIAL: controls, tests, and CSS build pass; visual browser acceptance pending | UI-002 page/navigation parity slices follow legacy reference `D:\Project\hotel-bome`; no Filament code is copied |

Queue dan detailed phase acceptance: `MASTER_BUILD_PLAN.md` (MBP-002..004 DONE; lihat queue tersebut untuk status bukti, backlog dan satu-satunya next task). Package map: `FEATURE_SPEC_MAP.md`. Fase/rangka dependency: `ROADMAP.md`. Task implementasi rinci tetap di feature `tasks.md`; tidak ada feature module yang dianggap selesai hanya karena ada spec atau test historis.
