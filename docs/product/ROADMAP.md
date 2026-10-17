# Roadmap Hotel Offline PMS

**Status:** Urutan awal berbasis discovery; prioritas komersial dan keputusan pemilik tetap perlu disahkan.

| Fase | Hasil | Slice ber-ID | Gate / dependensi |
|---|---|---|---|
| 0. Discovery & product baseline | Keputusan operasi, privacy, tax/accounting, deployment, lisensi dan pilot tercatat | `DISC-001` | Sebelum schema/finance/hardware/sync yang terdampak |
| 1. App runtime & bootstrap | Installable local runtime, health/readiness, first-admin/property initialization | `OPS-001` | Target Windows/runtime dan cara bootstrap disetujui; uji mesin sasaran |
| 2. Access & property foundation | User, roles/approvals, property/outlet/room, tenant isolation | `IAM-001`, `PROP-001` | Cardinality tenant, topologi akses, registrasi/admin diputuskan |
| 3. Guest, rates & inventory calendar | Guest privacy, room types, rates/tax snapshots, availability | `GUEST-001`, `RATE-001` | Tax/rate rules dan data retention disahkan |
| 4. Reservation & front desk | Individual/group booking, walk-in, arrival, stay, room changes, checkout | `RES-001`, `FO-001` | Cancellation/no-show/overbooking/room-share rules disahkan |
| 5. Folio, cashier & close | Charges, deposits, payment/refund, invoice, shift, business date, night audit | `FIN-001`, `FIN-002` | Tax invoice/payment/approval/day-close policy accountant/owner signoff |
| 6. Operasi & reporting | Audit, reports, backup/restore, housekeeping, maintenance, inventory, events/POS | `AUD-001`, `OPS-002`, `HK-001`, `ENG-001`, `INV-001`, `EVT-001`, `POS-001`, `RPT-001` | Scope hotel pilot dan role/report details |
| 7. Accounting & device | AR/AP/GL/budget/assets, RFID/doorlock | `ACC-001`, `HW-001` | Accountant approval; lock model/SDK/license + physical test hardware |
| 8. Online connectors & lifecycle | OTA/payment/guest channel, outbox sync, license, signed updates | `EXT-001`, `SYNC-001`, `REL-001` | Provider, privacy, licensing, conflict and update policy decisions |
| 9. Pilot acceptance & release | Clean install, full E2E, offline, security, recovery, upgrade, operator docs | `QA-001` | Supported Windows target machines and pilot operator access |

## Jalur dependensi

`DISC → OPS-001 → IAM-001 → PROP-001 → GUEST-001 → RATE-001 → RES-001 → FO-001 → FIN-001 → FIN-002 → RPT-001 / QA-001`.

`AUD-001` and `OPS-002` are cross-cutting and begin before posting data. `HK-001`, `ENG-001`, `INV-001`, `EVT-001`, `POS-001` depend on property/identity foundations and may proceed independently after their workflows are approved. `ACC-001`, `HW-001`, `SYNC-001`, `EXT-001`, `REL-001` remain gated by explicit external/business decisions.

## Readiness rule

Spec, plan, tasks, acceptance, safety checks, and test command must exist before implementation. Mark a task `READY` only when dependencies and decisions are satisfied. Otherwise use `BLOCKED:<decision-id>`. Hermes may continue independent READY tasks, then report the blocked queue; it must not silently choose accounting, tax, device or privacy behavior.
