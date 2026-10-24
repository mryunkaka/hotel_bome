# Roadmap Hotel Offline PMS

Status: urutan target produk; task global pertama ditentukan di [MASTER_BUILD_PLAN.md](MASTER_BUILD_PLAN.md); `docs/AI_WORKFLOW_STATE.md` menunjuk task resume/checkpoint dan tidak boleh memilih task berbeda. Roadmap ini tidak menyatakan fitur selesai. Fase berurutan, modul independen dapat berjalan setelah prereq dan acceptance terdefinisi.

| Fase | Modul / feature IDs | Bukti status repo | Dependency / gate |
|---|---|---|---|
| 0. Planning baseline | `DISC-001`, `MBP-*` | DISC artifact ada; MBP plan queue operasional | Owner decisions dicatat; MBP-002→003→004 dulu |
| 1. Runtime/bootstrap | `OPS-001` | PARTIAL: first-admin/health app slices | Windows target/service/signer/pilot external |
| 2. Access/property/audit | `IAM-001`, `PROP-001`, `AUD-001` | PARTIAL: IAM, property/room operations, audit viewer | IAM-001-T04/T05b, MySQL/concurrency, acceptance |
| 3. Guest/rates | `GUEST-001`, `RATE-001` | NOT IMPLEMENTED; specs/tasks exist | D-007 privacy; D-004 currency/tax/rounding |
| 4. Reservation/front desk | `RES-001`, `FO-001` | NOT IMPLEMENTED | D-005 SOP and guest/rate prerequisites |
| 5. Folio/cashier/close | `FIN-001`, `FIN-002` | NOT IMPLEMENTED | D-004/D-010 accountant/owner policy |
| 6. Operations/report/recovery | `HK-001`, `ENG-001`, `INV-001`, `EVT-001`, `POS-001`, `RPT-001`, `OPS-002` | HK/ENG PARTIAL; others NOT IMPLEMENTED | SOP, finance reconciliation, target backup/OS |
| 7. Accounting/device | `ACC-001`, `HW-001` | NOT IMPLEMENTED | accountant, vendor/license, physical device |
| 8. Connectors/release | `SYNC-001`, `EXT-001`, `REL-001` | NOT IMPLEMENTED | privacy, provider contracts, signer/license |
| 9. Pilot acceptance | `QA-001` | NOT RUN | target Windows/MySQL, operator, all required flows |

Critical path: `DISC/MBP → OPS → IAM/PROP/AUD → GUEST → RATE → RES → FO → FIN-001 → FIN-002 → RPT/QA`. HK/ENG/INV/EVT/POS/OPS-002 may proceed independently after their own prereqs. ACC/HW/SYNC/EXT/REL require named external gates. Port 8083 stays on legacy live app until separate acceptance and authorization.

Readiness: task-level spec/plan/tasks/acceptance, prerequisites, measurable checks and test command must exist. `READY` means safe bounded work; `BLOCKED:<ID>` names decision/resource. Resume order is exclusively the `MBP-*` queue/checkpoint in MASTER_BUILD_PLAN and AI_WORKFLOW_STATE; never infer next task from this summary.
