# Feature Specification: Front Desk and Stay Lifecycle

**Feature ID**: FO-001 | **Status**: Planning baseline

## Ringkasan

Mengelola arrivals, check-in, room assignment/move/share, in-house stay, folio routing dan checkout.

## User Scenarios & Testing

### User Story 1 — Mengoperasikan stay dari arrival sampai checkout (P1)

Sebagai resepsionis, saya ingin memproses stay tanpa memutus hubungan guest, kamar, booking dan charges.

Acceptance scenarios:
1. Given reservation valid dan room siap, when authorized staff check in, then stay dan occupancy tercatat dengan waktu/actor.
2. Given room belum siap atau occupied, when assignment diminta, then server menolak kecuali override sesuai policy.
3. Given room move/share disetujui, when diproses, then old/new assignment dan folio history tetap terjaga.
4. Given checkout memiliki outstanding item, when checkout dicoba, then configured exception/approval policy diterapkan.

## Functional Requirements

- FR-001: Arrival/departure/in-house views MUST scoped by hotel and business date.
- FR-002: Check-in MUST verify reservation/guest identity/room readiness and permissions.
- FR-003: Room move/share MUST record old/new mapping, reason, actor and time.
- FR-004: Checkout MUST update occupancy and housekeeping handoff without deleting stay/folio history.
- FR-005: Key issuance/reference MUST remain separate from unverified lock hardware functionality.

## Key Entities

Reservation guest, stay, room assignment history, arrival/departure, folio, handoff.

## Success Criteria

- SC-001: Invalid room/role/tenant assignment is rejected.
- SC-002: Room move preserves financial linkage and historical assignment.
- SC-003: Checkout status and HK handoff are consistent and audited.
- SC-004: Core flow continues without WAN.

## Dependencies and readiness

Depends on RES-001/RATE-001. **BLOCKED:D-005** early/late arrival, identity checks, move/share and unpaid checkout rules. Hardware card access depends on HW-001.
