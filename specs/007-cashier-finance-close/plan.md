# Implementation Plan: Folio Payments, Cashier, and Night Audit

Feature IDs: FIN-001, FIN-002 | Branch: feat/finance-close

Do not finalize posting/tax schema until owner/accountant decisions. Model money as fixed precision with explicit currency, append-only ledger and unique idempotency keys. Keep invoices as immutable snapshots. Reconcile cashier expected/count totals to postings. Business date is hotel-controlled and not equivalent to midnight. Use DB transactions/locking; verify on target database as well as SQLite. Build separate feature commits for folio/payment and close if independently ready.

Constitution: no cascade deletion of posted history; correction by compensating record; dual control/least privilege; complete audit trail; local operation.
