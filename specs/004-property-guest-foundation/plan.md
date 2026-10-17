# Implementation Plan: Property Inventory and Guest Privacy

Feature IDs: PROP-001, GUEST-001 | Branch: feat/property-guest-foundation

Design property inventory first, then guest records; do not expose guest documents until D-007 policy is approved. Enforce hotel membership across queries, Livewire actions, exports, jobs and file paths. Use additive schema, restrictive history FKs, disabled masters and explicit room-state events. Verify in PHPUnit and approved target DB because SQLite constraint/locking behavior may differ.

Constitution gates: least privilege, PII minimization, local-first, history retention, tests with fake data only. Separate PROP-001 and GUEST-001 implementation commits if their readiness differs.
