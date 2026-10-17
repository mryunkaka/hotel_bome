# Acceptance Matrix — PROP-001 / GUEST-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| PROP-AC01 | Property A cannot access property B inventories | Policy/query/direct URL tests | NOT RUN |
| PROP-AC02 | Room status/block affects availability and retains history | Feature/integration tests | NOT RUN |
| PROP-AC03 | Referenced master is disabled, not removed | FK/lifecycle tests | NOT RUN |
| GUEST-AC01 | PII limited by role/property and no PII in logs | Feature/export/log review | BLOCKED:D-007 |
| GUEST-AC02 | Duplicate review is reversible and does not remove stay links | Integration test | BLOCKED:D-007 |
