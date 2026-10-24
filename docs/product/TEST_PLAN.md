# Master Test Plan

Per-feature `specs/*/tasks.md` dan acceptance matrix mengembangkan plan ini. Command harus dijalankan terhadap test configuration/data saja; tidak pernah terhadap database hotel.

## Lapisan verifikasi

1. **Static & code quality:** `composer lint:check`, `composer types:check`; periksa dependency lock dan secrets/package exclusion.
2. **Unit:** rate math, availability ranges, money rounding, state transitions, idempotency, authorization helpers.
3. **Feature/integration:** Laravel HTTP/Livewire actions, persistence, constraints, policy denials, transactions, audit and outbox atomicity.
4. **Browser/UI:** role-based page/action visibility, validation, empty/loading/error state, pagination/filter and keyboard/error flow.
5. **End-to-end:** reservation → arrival/check-in → folio charge/deposit/payment → invoice → checkout → cashier/day close; restart between selected checkpoints.
6. **Offline/recovery:** WAN/DNS disconnect at login, each core write and reconciliation; backup encryption, second-machine restore, disk-full/service failure, reboot, interrupted additive update.
7. **Security:** cross-property IDOR in direct URL/Livewire/export/job/API; CSRF/session rotation; XSS; mass assignment; upload/export paths; rate limits; secret/log review; firewall reachability; replay/idempotency for devices.
8. **Hardware/vendor:** physical reader/lock/provider only on approved test hardware and vendor sandbox. Mock-only is PARTIAL, never hardware PASS.

## Evidence format

For every run record command, environment/runtime version, exit status, passed/failed/skipped counts, relevant sanitized output, commit SHA, and scenarios not covered. No guest data or credentials in fixtures/logs. A failed or skipped test is not a pass. `composer test` currently runs lint, static analysis, and Artisan test via project scripts; those scripts are inspected but not executed in this planning pass.

## Gates

- Each task: focused tests and applicable static checks.
- Each feature: acceptance list and feature/regression suite.
- Each cross-cutting release: full automated suite, migration compatibility, security checks.
- Pilot release: clean Windows install/reboot, offline E2E, restore on second machine, LAN exposure scan if LAN supported, signed update refusal/acceptance. Mark unavailable target hardware/environment BLOCKED.
