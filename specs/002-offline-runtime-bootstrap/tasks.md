# Tasks: Offline Runtime and First Run

**Feature ID**: OPS-001
**Depends on**: DISC-001
**Readiness**: APP SLICES IMPLEMENTED; Windows deployment prototype can target Windows 11 Pro GAC under provisional OPS-D4. Acceptance remains blocked on exact pilot machine, runtime/service wrapper, licensing/signing custody and physical test evidence.

- [ ] **OPS-001-T01** Confirm exact pilot device/edition entitlement, local runtime/service wrapper, signing-key custody and operator. Provisional OS baseline is Windows 11 Pro GAC per OPS-D4; Windows 10 is not a new-deployment baseline. Exact device, runtime/service and signer custody remain BLOCKED_EXTERNAL.
- [ ] **OPS-001-T02** Prototype offline bundle on disposable Windows 11 Pro GAC pilot; inventory extension/service/asset compatibility. BLOCKED_EXTERNAL on pilot device and runtime selection.
- [x] **OPS-001-T03a** Implement local one-time first-admin wizard core, peer loopback gate, transaction lock, validation and replay protection. Linux/SQLite feature tests pass; provisional decision OPS-D1.
- [x] **OPS-001-T03b** Implement secret-free health/readiness reporting at `/operator/health`; requires active platform-admin authentication, returns generic 200/503 statuses for app/database/storage/schema. Reboot/service-manager acceptance remains external.
- [x] **OPS-001-T03c** Test DB probe success with a required core schema table missing: generic 503, `database=ok`, `schema=unavailable`; evidence `HealthControllerTest::test_database_probe_can_succeed_while_missing_core_schema_still_returns_503` and `HealthControllerTest::test_database_sessions_table_is_required_for_readiness`. `/operator/health` now checks tables required by installed app migrations (users/bootstrap, properties/inventory/room operations, identity/audit, passkeys) plus configured database-backed session table, without returning table names. SQLite/Linux only; no Windows/runtime acceptance.
- [ ] **OPS-001-T04** Configure least-privilege service, loopback DB and approved LAN firewall. BLOCKED_EXTERNAL on OS/runtime target; continue testable app-level security slices meanwhile.
- [ ] **OPS-001-T05** Test install/reboot/WAN-down/bootstrap replay/network denial/tampered package. Bootstrap replay/peer denial unit slice tested locally; full acceptance BLOCKED_EXTERNAL on T01-T04.
- [ ] **OPS-001-T06** Record verified operator installation and recovery runbook. Depends on actual pilot acceptance T05.

Do not implement or claim target Windows compatibility until T01 is resolved.
