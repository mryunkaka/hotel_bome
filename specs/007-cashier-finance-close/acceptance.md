# Acceptance Matrix — FIN-001 / FIN-002

| ID | Criterion | Verification | Status |
|---|---|---|---|
| FIN-AC01 | Folio total reconciles to immutable postings | Unit + integration recomputation | NOT RUN |
| FIN-AC02 | Duplicate payment/close cannot duplicate effect | Idempotency/concurrency tests | NOT RUN |
| FIN-AC03 | Posted mutation rejected; reversal is auditable | Direct action/API tests | NOT RUN |
| FIN-AC04 | Invoice snapshot survives master changes | Snapshot regression test | BLOCKED:D-004 |
| FIN-AC05 | Cash/day close and reopen match approved SOP | Acceptance with owner/accountant | BLOCKED:D-010 |
