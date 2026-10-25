# Tasks: Rates, Availability, and Reservations

**Readiness**: D-004/D-005 keputusan sementara tercatat 2026-10-25; RATE-001-T01 rate plan CRUD implemented. Guest profile is only a technical prototype; D-007 remains open, so reservation use with real guest data is not approved. RATE-001-T02 remains the next design task; full quote/availability/reservation acceptance is not complete.

## RATE-001 Slice 1 — Rate Plan Master Data

- [x] RATE-001-T01a Implement additive migration for `rate_plans` table: property_id FK, name (max 100), code (per-property unique, max 20), base_price_per_night (unsigned integer, IDR), tax_percentage (decimal 5,2, nullable — configurable per D-004), effective_from/effective_until (date, nullable), is_active boolean, timestamps. Audit events in `rate_plan_events` table. No availability/booking logic yet. Evidence: migration, model, controller, views, routes created.
- [x] RATE-001-T01b Implement RatePlan model, RatePlanController (property-scoped, platform-admin only): list with search/filter, create, edit, deactivate. Views follow existing Flux Pro pattern. Add "Rate Plans" link to property index page. Audit create/update/deactivate events. Evidence: code landed and tested.
- [x] RATE-001-T01c Write RatePlanTest: auth denial, admin CRUD, validation (name/code/price/tax/dates), cross-property access denied, inactive property rejected, code uniqueness per property, HTML escaping, deactivation retention, audit events recorded, code uppercase normalization. Evidence: `RatePlanTest` 17 tests/70 assertions; full suite 227/1109; Pint, PHPStan pass. SQLite only.

## RATE-001 Slice 2 — Rate Quote and Snapshot (future)

- [ ] RATE-001-T02 Design rate quote service: given property, room type, dates, occupancy → find applicable rate plan, calculate nightly rates, apply tax. Return quote with breakdown. No booking yet.
- [ ] RATE-001-T03 Implement rate quote and confirmed-booking snapshot: immutable rate/tax at time of booking. Snapshot must survive rate plan master changes.
- [ ] RATE-001-T04 Test date/timezone boundaries (IANA per property), tax rounding (half-up to IDR), overlapping rate plans, inactive plan exclusion, snapshot stability after master update.

## RES-001 — Reservation (future, after rate quote)

- [ ] RES-001-T01 Implement reservation state machine per D-005: confirmed → checked_in → checked_out; confirmed → cancelled; confirmed → no_show. Walk-in creates confirmed + immediate check-in. Waitlisted is non-confirming queue.
- [ ] RES-001-T02 Model individual/group reservation with guest link, room assignment, rate snapshot, property scope, and change audit.
- [ ] RES-001-T03 Implement authorized booking and room assignment flows after rate quote and availability service.
- [ ] RES-001-T04 Test full lifecycle, concurrent booking, availability, tenant boundaries, historical changes, no-overbooking enforcement.

## Order

RATE-001-T01a → T01b → T01c → RATE-001-T02..T04 → RES-001-T01..T04. Do not implement availability/booking before rate plan CRUD lands.
