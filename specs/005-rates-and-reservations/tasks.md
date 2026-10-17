# Tasks: Rates, Availability, and Reservations

**Readiness**: BLOCKED:D-004,D-005 (guest PII also D-007).

- [ ] RATE-001-T01 Obtain accountant/owner currency, tax/fee, rounding, occupancy and rate-rule decisions. BLOCKED:D-004.
- [ ] RATE-001-T02 Model rate effective dates, snapshots, tax basis and availability concurrency invariants.
- [ ] RATE-001-T03 Implement rate calendar/quote and shared availability calculation after T01-T02.
- [ ] RATE-001-T04 Test date/timezone boundaries, tax/rounding, blocks, race and snapshot stability.
- [ ] RES-001-T01 Obtain cancellation/no-show/deposit/waitlist/overbooking/group SOP. BLOCKED:D-005.
- [ ] RES-001-T02 Model individual/group/walk-in state machine and audit transitions.
- [ ] RES-001-T03 Implement authorized booking and rooming-list flows after readiness.
- [ ] RES-001-T04 Test full lifecycle, availability, tenant boundaries and historical changes.

## Order

PROP-001 → approved guest fields → D-004 rate design → D-005 reservation rules → implementation/tests. Do not independently invent tax or cancellation behavior.
