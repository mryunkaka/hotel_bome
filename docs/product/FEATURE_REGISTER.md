# Feature Register and Global Task Map

Task IDs are stable cross-session references. Detailed implementation tasks live in the linked feature `tasks.md`. `READY` means decisions and dependencies are satisfied; `BLOCKED` must cite an open decision or unavailable resource. The initial status is planning-only until the owner resolves required gates.

| Feature | Scope | Depends on | Initial gate |
|---|---|---|---|
| `DISC-001` | Product, property topology, SOP and pilot decisions | — | READY for owner discovery; business answers pending |
| `OPS-001` | Local runtime, health, first-run bootstrap | D-002, D-003, target Windows | BLOCKED:D-002,D-003,OS |
| `IAM-001` | Local users, roles, property membership, approvals | OPS-001, D-001..D-003 | BLOCKED:D-001,D-003 |
| `PROP-001` | Properties, outlets, room types, rooms/status board | IAM-001, D-001 | BLOCKED:D-001 |
| `GUEST-001` | Guest profile, privacy, identity docs, duplicate handling | IAM-001, PROP-001, D-007 | BLOCKED:D-007 |
| `RATE-001` | Room rates, tax/fees, snapshot and availability | PROP-001, D-004 | BLOCKED:D-004 |
| `RES-001` | Individual/group/walk-in booking, cancellation/no-show | GUEST-001, RATE-001, D-005 | BLOCKED:D-005 |
| `FO-001` | Arrival, stay, room changes, checkout, folio routing | RES-001, D-005 | BLOCKED:D-005 |
| `FIN-001` | Folio postings, deposits, payments, refunds, invoice snapshots | FO-001, RATE-001, D-004,D-010 | BLOCKED:D-004,D-010 |
| `FIN-002` | Cashier shifts, business day, night audit, reopen controls | FIN-001, D-010 | BLOCKED:D-010 |
| `AUD-001` | Audit trail, event retention and review | IAM-001 | READY after IAM policy; acceptance detail in feature |
| `RPT-001` | Operational and financial report slices/exports | FIN-002, D-004,D-010 | BLOCKED:D-004,D-010 |
| `OPS-002` | Backup, restore, health, update/release lifecycle | OPS-001, target OS | BLOCKED:OS and signing policy |
| `HK-001` | Housekeeping tasks/status/lost-and-found | PROP-001,IAM-001 | BLOCKED:role/SOP review |
| `ENG-001` | Assets, maintenance schedule/work orders | PROP-001,IAM-001 | BLOCKED:pilot workflow confirmation |
| `INV-001` | Stock movement, procurement, receiving, minibar | PROP-001,IAM-001, D-010 | BLOCKED:valuation/approval rules |
| `EVT-001` | Facilities, events, catering/BEO/charges | PROP-001,FIN-001 | BLOCKED:commercial flow confirmation |
| `POS-001` | Local POS/room service and cashier reconciliation | PROP-001,FIN-001,D-009,D-010 | BLOCKED:D-009,D-010 |
| `ACC-001` | AR/AP, journal, GL, budget, fixed assets | FIN-001,D-004,D-010 | BLOCKED:accountant signoff |
| `HW-001` | Secure local doorlock/RFID adapter and audit | IAM-001,PROP-001,D-006 | BLOCKED:D-006 + physical device |
| `SYNC-001` | Privacy-scoped outbox and center reconciliation | Core ledgers,D-007 | BLOCKED:D-007 |
| `EXT-001` | OTA, online payment, portal and provider adapters | RES-001,FIN-001,D-009 | BLOCKED:D-009 + provider |
| `REL-001` | Signed update/license catalog/customer lifecycle | OPS-002,D-008 | BLOCKED:D-008 + signing ownership |
| `QA-001` | Pilot acceptance, security, offline, recovery and release audit | Required selected release slices | BLOCKED until scope and target set |

Initial phase sequencing and whole-project DoD: `ROADMAP.md`, `TEST_PLAN.md`, `DEPLOYMENT_PLAN.md`, `DEFINITION_OF_DONE.md`.
