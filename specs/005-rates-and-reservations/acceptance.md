# Acceptance Matrix — RATE-001 / RES-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| RATE-AC01 | Tax/rate totals and boundary dates match approved rules | Unit tests + accountant cases | BLOCKED:D-004 |
| RATE-AC02 | Concurrent create cannot silently oversell | Integration race test on target DB | NOT RUN |
| RATE-AC03 | Confirmed booking keeps price/tax snapshot | Update master then compare booking | NOT RUN |
| RES-AC01 | Individual/group/walk-in lifecycle authorized and audited | Feature tests by role | BLOCKED:D-005 |
| RES-AC02 | Cancellation/no-show/waitlist/overbooking obey approved SOP | State transition tests | BLOCKED:D-005 |
