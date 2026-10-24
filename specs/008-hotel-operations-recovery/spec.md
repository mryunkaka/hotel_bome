# Feature Specification: Operations, Reports, Backup, and Update

**Feature IDs**: AUD-001, HK-001, ENG-001, INV-001, EVT-001, POS-001, RPT-001, OPS-002 | **Status**: Planning baseline

## Ringkasan

Tambah operation modules sebagai sub-slices (housekeeping, maintenance, inventory, event/POS), reports, audit, backup/restore dan signed update sesuai keputusan hotel.

## User Scenarios & Testing

### User Story 1 — Operasi dan pemulihan hotel teruji (P1)

Sebagai manager/admin, saya ingin operasi, reports dan recovery mencerminkan transaksi yang benar serta bekerja saat WAN mati.

Acceptance scenarios:
1. Given room status/task/stock/event/order change, when staff performs it, then role, property, lifecycle and audit rules apply.
2. Given report filters, when report/export runs, then totals reconcile and permissions remain enforced.
3. Given backup, when restored to a separate machine, then counts, totals and snapshot integrity are verified.
4. Given package tampered or schema unsupported, when update begins, then updater refuses before extraction/migration.
5. Given WAN unavailable, when local core flow is used, then operation continues and outbound connector state is explicit.

## Functional Requirements

- FR-001: Each operations module MUST have an independent feature spec, role/lifecycle matrix and acceptance; this umbrella is not one mega implementation.
- FR-002: Stock uses movement ledger; room/asset/event status changes preserve history.
- FR-003: Reports define metric/date basis/property permission and reconcile to source records.
- FR-004: Audit is append-only and excludes unnecessary PII/secrets.
- FR-005: Backup is encrypted and restore-tested separately; update is signed, staged and additive.

## Success Criteria

- SC-001: Module scope cannot be implemented without its hotel SOP gate.
- SC-002: Report totals and exports reconcile and honor tenant access.
- SC-003: Second-machine restore verifies data integrity.
- SC-004: Invalid update is rejected and does not alter active data.

## Dependencies and readiness

Depends on IAM/PROP/FIN foundations. HK/engineering/inventory/events/POS require their separate SOP/vendor requirements. Reports wait D-004/D-010. Backup/update waits target Windows runtime and signing policy. This umbrella groups map only; implement each register ID separately.
