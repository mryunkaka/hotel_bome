# Feature Specification: Rates, Availability, and Reservations

**Feature IDs**: RATE-001, RES-001 | **Status**: Planning baseline

## Ringkasan

Menawarkan harga/ketersediaan dan menerima reservasi individu, grup, walk-in serta lifecycle perubahan tanpa oversell.

## User Scenarios & Testing

### User Story 1 — Membuat booking yang dapat dipercaya (P1)

Sebagai reservation staff, saya ingin memilih rate dan kamar tersedia sehingga konfirmasi booking menyimpan harga yang disepakati.

Acceptance scenarios:
1. Given rate/date/occupancy sesuai aturan approved, when user membuat reservation, then total dan rate basis tersimpan sebagai snapshot.
2. Given dua request paralel untuk kapasitas terakhir, when keduanya submit, then hanya policy-valid booking yang dikonfirmasi.
3. Given group booking, when rooming list diubah, then guest/room links and change history terjaga.
4. Given cancellation/no-show, when status berubah, then policy approved menentukan fee/refund dan audit.

## Functional Requirements

- FR-001: Rate rules MUST define effective dates, occupancy, currency, tax and rounding after owner/accountant approval.
- FR-002: Availability MUST account for room inventory, blocks, stays and concurrent reservation requests.
- FR-003: Confirmed reservation MUST store immutable rate/tax snapshot and source.
- FR-004: Group rooming list MUST maintain guest/room relationship and change history.
- FR-005: Walk-in, waitlist, cancellation, no-show and overbooking MUST follow documented hotel rules.

## Key Entities

Rate plan/rule, tax/fee, availability, room block, reservation/group, guest link, room assignment, source, change event.

## Success Criteria

- SC-001: Concurrent booking does not oversell unless an authorized audited exception is approved.
- SC-002: Rate/tax snapshot remains unchanged after master updates.
- SC-003: Individual/group/walk-in/cancel flows satisfy role and property policies.
- SC-004: Unapproved tax/cancellation rules never become production behavior.

## Dependencies and readiness

Depends on PROP-001/GUEST-001. **BLOCKED:D-004** tax, currency, invoice basis and rate rules; **BLOCKED:D-005** cancellation/no-show/overbooking/waitlist/deposit SOP. Guest implementation also waits D-007.
