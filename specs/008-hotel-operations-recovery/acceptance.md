# Acceptance Matrix — Operations / Recovery

| ID | Criterion | Verification | Status |
|---|---|---|---|
| OPS-AC01 | Each module has approved SOP and isolated acceptance | Child spec/task review | NOT RUN |
| AUD-AC01 | Audit entries immutable, scoped and secret-free | Feature/security/log test | NOT RUN |
| INV-AC01 | Stock balance reconciles movement ledger | Ledger integration test | BLOCKED: costing/SOP |
| RPT-AC01 | Totals/export reconcile and stay property-scoped | Report/IDOR tests | BLOCKED:D-004,D-010 |
| OPS-AC02 | Backup restores on second machine | Target Windows restore exercise | BLOCKED: target OS/key |
| OPS-AC03 | Tampered update refused without data change | Signature negative test | BLOCKED: signer/target |
