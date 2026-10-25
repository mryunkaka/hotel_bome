# Tasks: Front Desk and Stay Lifecycle

**Readiness**: BLOCKED:D-005.

- [x] FO-001-T01 Resolve check-in identity, early/late, room move/share and unpaid checkout policy. D-005 provisional decisions applied: check-in requires confirmed reservation, room ready status deferred to HK integration, check-out triggers handoff signal, room move records old/new with reason. Unpaid checkout exception requires future policy. No owner SOP signoff.
- [x] FO-001-T02 Model stay/assignment state transitions, locking and audit linkage to reservation/folio. StayService implements check-in (creates stay from confirmed reservation, transitions reservation to checked_in), check-out (marks stay checked_out, transitions reservation), walk-in check-in, room move with history. All operations use DB transactions with lockForUpdate. Immutable stay_events table with SQLite triggers. Evidence: `StayServiceTest` 14 tests/32 assertions; full suite 317/1356; Pint, PHPStan pass. SQLite only.
- [ ] FO-001-T03 Implement arrivals/in-house/departures and authorized check-in/check-out.
- [ ] FO-001-T04 Implement room move/share with transactional revalidation and history.
- [ ] FO-001-T05 Test role/property denial, occupancy race, restart, stay/folio integrity and HK handoff.
- [ ] FO-001-T06 Run reservation-to-checkout integration case and record evidence.

Do not manufacture key success or permit checkout exceptions without policy.
