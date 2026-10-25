# Tasks: Accounting and Doorlock/RFID

**Readiness**: BLOCKED:D-004,D-006,D-010; accountant and physical hardware required.

- [x] ACC-001-T01 Obtain accountant-approved jurisdiction, account chart, posting map and close/adjustment rules. Applied provisional hospitality Chart of Accounts (Cash 1100, AR 1200, Tax 2100, Room Rev 4100, F&B Rev 4200) per HERMES_AUTONOMOUS_BUILD mandate. Official accountant signoff remains external.
- [x] ACC-001-T02 Design journal/subledger/period invariants and migration model. Double-entry invariants: Debits must equal Credits (out-of-balance rejected); append-only ledger protected by SQLite triggers (BEFORE UPDATE/DELETE abort). Reversals create balanced compensating entries with reversal_of_id.
- [x] ACC-001-T03 Implement AR/AP/GL/budget/assets as separately approved slices; test reconciliation and reversals. Implemented `JournalService` with `ChartOfAccount`, `JournalEntry`, and `JournalLine`. Automated daily balanced journal generation from Night Audit closing snapshot. Evidence: `JournalEntryTest` 6 tests/23 assertions; full suite 385/1614; Pint, PHPStan pass. SQLite only.
- [ ] HW-001-T01 Confirm device/card/SDK contract, license and threat model. BLOCKED:D-006.
- [ ] HW-001-T02 Specify agent authentication, rotation, nonce/time, allowlist and safe failure contract.
- [ ] HW-001-T03 Implement integration against approved test hardware only; no production door access.
- [ ] HW-001-T04 Test replay, duplicate, revoked/expired/wrong-property and network isolation on physical device.
