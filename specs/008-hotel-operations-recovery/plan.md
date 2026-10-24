# Implementation Plan: Operations, Reports, Backup, and Update

Feature IDs: AUD-001, HK-001, ENG-001, INV-001, EVT-001, POS-001, RPT-001, OPS-002 | Branch: feat/hotel-operations-recovery

Use modular feature branches/commits per ID; do not implement this umbrella as one broad task. Each module gets its own detailed data model and acceptance. Cross-cutting audit and recovery should precede modules that post money/stock. Reports are derived/read-only with explicit snapshot/live semantics. Backup/update are separate from app CRUD and require a disposable Windows target.

Constitution: least privilege, local-first, append-only history, tested restore, signed update and no unverified production/deployment claims.
