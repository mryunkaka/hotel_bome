# Tasks: Rates, Availability, and Reservations

**Readiness**: D-004/D-005 keputusan sementara tercatat 2026-10-25; RATE-001-T01 rate plan CRUD implemented; RATE-001-T02 rate quote service implemented; T04 boundary tests partially done (quote service scope). Guest profile is only a technical prototype; D-007 remains open, so reservation use with real guest data is not approved. Snapshot stability tests in T04 require T03.

## RATE-001 Slice 1 — Rate Plan Master Data

- [x] RATE-001-T01a Implement additive migration for `rate_plans` table: property_id FK, name (max 100), code (per-property unique, max 20), base_price_per_night (unsigned integer, IDR), tax_percentage (decimal 5,2, nullable — configurable per D-004), effective_from/effective_until (date, nullable), is_active boolean, timestamps. Audit events in `rate_plan_events` table. No availability/booking logic yet. Evidence: migration, model, controller, views, routes created.
- [x] RATE-001-T01b Implement RatePlan model, RatePlanController (property-scoped, platform-admin only): list with search/filter, create, edit, deactivate. Views follow existing Flux Pro pattern. Add "Rate Plans" link to property index page. Audit create/update/deactivate events. Evidence: code landed and tested.
- [x] RATE-001-T01c Write RatePlanTest: auth denial, admin CRUD, validation (name/code/price/tax/dates), cross-property access denied, inactive property rejected, code uniqueness per property, HTML escaping, deactivation retention, audit events recorded, code uppercase normalization. Evidence: `RatePlanTest` 17 tests/70 assertions; full suite 227/1109; Pint, PHPStan pass. SQLite only.

## RATE-001 Slice 2 — Rate Quote and Snapshot (future)

- [x] RATE-001-T02 Design and implement rate quote service: `RateQuoteService::quote(property, roomTypeId, checkIn, checkOut)` finds applicable active rate plan (room-type-specific preferred, fallback to general), calculates nightly breakdown, applies PBJT tax with half-up IDR rounding per D-004. Returns structured quote or null. Additive migration adds optional `room_type_id` FK to `rate_plans` with composite property constraint. Evidence: `RateQuoteTest` 14 tests/45 assertions; full suite 241/1154; Pint, PHPStan pass. SQLite only.
- [x] RATE-001-T03 Implement rate snapshot: immutable `rate_snapshots` table with property/rate-plan/quote data, captured via `RateSnapshotService::capture()`. SQLite triggers prevent UPDATE/DELETE. Snapshot survives master rate plan changes and deactivation. Additive migration with rollback support. Evidence: `RateSnapshotTest` 7 tests/31 assertions; full suite 254/1210; Pint, PHPStan pass. SQLite only.
- [x] RATE-001-T04 Test date/timezone boundaries, tax rounding, overlapping rate plans, inactive plan exclusion, snapshot stability after master update. Evidence: `RateQuoteTest` boundary tests (effective date inclusive, one-sided, overlapping plans, half-up rounding edge, fractional tax %, single-night stay); `RateSnapshotTest` snapshot stability tests (master change, deactivation, null tax, property scope, immutability triggers). Combined: 27 tests/101 assertions in quote+snapshot tests; full suite 254/1210. IANA timezone per property remains configuration-only (not enforced in query); MySQL concurrency untested. SQLite only.

## RES-001 — Reservation (future, after rate quote)

- [x] RES-001-T01 Implement reservation state machine per D-005 provisional: confirmed → checked_in → checked_out; confirmed → cancelled (requires reason); confirmed → no_show. Walk-in creates confirmed + immediate check-in in single transaction. All transitions audited with immutable events (SQLite triggers prevent UPDATE/DELETE). Cross-property transition rejected. Uses guest ID reference (no PII per D-007). Evidence: `ReservationLifecycleTest` 14 tests/25 assertions; full suite 268/1235; Pint, PHPStan pass. SQLite only.
- [x] RES-001-T02 Model individual/group reservation with guest link, room assignment, rate snapshot, property scope, and change audit. Reservation model with Property/Guest/RateSnapshot/Events relationships. Group reservation via `reservation_groups` table with property-scoped FK. Room reassignment with change audit event (records from/to room_id in JSON changes). Source tracking (walk_in/phone/online). Cross-property group rejected. Terminal-status reassignment rejected. Evidence: `ReservationModelTest` 10 tests/28 assertions; combined reservation tests 24/53; full suite 278/1263; Pint, PHPStan pass. SQLite only.
- [ ] RES-001-T03 Implement authorized booking and room assignment flows after rate quote and availability service.
- [ ] RES-001-T04 Test full lifecycle, concurrent booking, availability, tenant boundaries, historical changes, no-overbooking enforcement.

## Order

RATE-001-T01a → T01b → T01c → RATE-001-T02..T04 → RES-001-T01..T04. Do not implement availability/booking before rate plan CRUD lands.
