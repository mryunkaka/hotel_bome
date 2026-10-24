# Acceptance Matrix — AUD-001 slice 1

| ID | Criterion | Verification | Status |
|---|---|---|---|
| AUD-AC01 | Only active platform-admin can view audit history | HTTP direct-route role tests | PASS: `AuditHistoryTest` |
| AUD-AC02 | Five existing sources appear with minimized fields and distinguishable source IDs | Feature tests seed source records and inspect rendered values | PASS: all five source/reference pairs; secret fields absent |
| AUD-AC03 | Source/property/date filters, paired date-range validation, deterministic 50-row pagination work | Query/HTTP feature tests | PASS: partial/invalid/reversed/overlong inputs, inclusive dates, 50-row/next-page boundary, tie-break test |
| AUD-AC04 | Untrusted event/name/reason values are escaped; credentials/secrets absent | XSS/data-minimization assertions | PASS: escaped name/property/reason and absent token fields |
| AUD-AC05 | Reads do not mutate event source tables | Before/after row-count and row-value assertions | PASS: normalized full-row snapshots |
| AUD-AC06 | SQLite tests pass | `php artisan test --filter=AuditHistoryTest`; full `composer test` | PASS: focused 6 tests/75 assertions; full 141 tests/667 assertions; Pint, PHPStan, Composer validation pass |
| AUD-AC07 | MySQL and Windows deployment acceptance | Selected Windows/MySQL pilot | BLOCKED_EXTERNAL |
| AUD-AC08 | Hotel/legal retention policy approved | Owner/privacy decision | BLOCKED_EXTERNAL; no purge implemented |
