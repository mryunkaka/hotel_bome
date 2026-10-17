# Acceptance Matrix — SYNC-001 / EXT-001 / REL-001 / QA-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| EXT-AC01 | Core operations continue with WAN/provider unavailable | Offline E2E | NOT RUN |
| SYNC-AC01 | Approved event scope is idempotent and privacy constrained | Retry/duplicate/privacy tests | BLOCKED:D-007 |
| EXT-AC02 | Timeout never marks unconfirmed external payment success | Sandbox failure test | BLOCKED:D-009 |
| REL-AC01 | Tampered package rejected before extraction/migration | Negative signature test | BLOCKED:D-008 |
| QA-AC01 | Clean install, E2E, backup restore and security pass on pilot | Evidence bundle | BLOCKED: scope/target |
| QA-AC02 | Final status only PASS when all mandatory rows have evidence | Final audit | NOT RUN |
