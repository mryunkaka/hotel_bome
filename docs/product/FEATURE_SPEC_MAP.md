# Feature Specification Map

This project uses ten bounded Spec Kit packages. The 24 IDs in FEATURE_REGISTER.md remain individual module/task IDs; related user journeys are grouped but must not be implemented as a mega-feature. An owner decision may unblock one child while another stays blocked.

| Package | Feature IDs / register entries |
|---|---|
| specs/001-discovery-baseline | DISC-001 |
| specs/002-offline-runtime-bootstrap | OPS-001 |
| specs/003-identity-property-access | IAM-001 |
| specs/004-property-guest-foundation | PROP-001, GUEST-001 |
| specs/005-rates-and-reservations | RATE-001, RES-001 |
| specs/006-frontdesk-stay-lifecycle | FO-001 |
| specs/007-cashier-finance-close | FIN-001, FIN-002 |
| specs/008-hotel-operations-recovery | AUD-001, HK-001, ENG-001, INV-001, EVT-001, POS-001, RPT-001, OPS-002 |
| specs/009-accounting-and-hardware | ACC-001, HW-001 |
| specs/010-connectors-release-acceptance | SYNC-001, EXT-001, REL-001, QA-001 |

Every package has spec.md, plan.md, tasks.md and acceptance.md. Modules in packages 008–010 retain separate gates in FEATURE_REGISTER.md. If an implementation slice becomes independently releasable, split its spec/tasks before implementation.
