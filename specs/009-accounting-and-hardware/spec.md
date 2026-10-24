# Feature Specification: Accounting and Doorlock/RFID

**Feature IDs**: ACC-001, HW-001 | **Status**: Planning baseline

## Ringkasan

Menambah accounting yang disahkan akuntan dan integrasi doorlock/RFID lokal yang diverifikasi pada perangkat nyata.

## User Scenarios & Testing

### User Story 1 — Ledger/device operation can be trusted (P1)

Sebagai accountant atau front desk, saya ingin financial entries seimbang dan akses pintu mematuhi identitas/permission lokal.

Acceptance scenarios:
1. Given approved account mapping, when an operational event posts to ledger, then entry balances and links to source.
2. Given a duplicate/replayed device message, when it is received, then it cannot grant repeated access or duplicate event.
3. Given wrong-property, revoked or expired credential, when device acts offline, then access is denied and audited.
4. Given no physical vendor equipment, when mock tests pass, then hardware remains PARTIAL/BLOCKED, never PASS.

## Functional Requirements

- FR-001: Accounting mapping/chart/jurisdiction MUST be approved by hotel accountant.
- FR-002: Posted journal MUST balance and reference origin; corrections MUST reverse with reason.
- FR-003: Device agent MUST use per-install authentication and replay defense.
- FR-004: Server MUST derive user/property authority and allow-list device operations.
- FR-005: Device and finance histories MUST be append-only, auditable and local-first.

## Success Criteria

- SC-001: Debits equal credits and subledger reconciles.
- SC-002: Duplicate/replay/wrong-property device events do not produce unauthorized effects.
- SC-003: Offline valid/invalid card scenarios work on exact physical test hardware.
- SC-004: Mock-only and unapproved finance behavior is never reported PASS.

## Dependencies and readiness

Accounting **BLOCKED** on D-004/D-010 and accountant signoff. Hardware **BLOCKED:D-006** pending manufacturer/model/card/SDK/redistribution and physical pilot device.
