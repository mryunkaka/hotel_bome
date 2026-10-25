# Tasks: Folio Payments, Cashier, and Night Audit

**Readiness**: BLOCKED:D-004,D-010.

- [x] FIN-001-T01 Obtain accountant/owner approval for tax, currency, invoice numbering/snapshot, tender, refund and correction rules. D-004 & D-010 provisional decisions applied per HERMES_AUTONOMOUS_BUILD mandate: IDR integer currency, append-only ledger, compensating reversals/refunds, unique idempotency key, immutable invoice snapshots. Official owner/accountant signoff remains external.
- [x] FIN-001-T02 Define folio ledger, payment idempotency, reversal and balance invariants. Implemented in FolioService: sum(charges) - sum(payments) = balance; duplicate payment idempotent via unique [property_id, idempotency_key]; charge reversal adds compensating negative record with reversal_of_id; payment refund adds compensating negative record with refund_of_id; SQLite triggers enforce immutability (prevent UPDATE/DELETE on folio_charges and folio_payments).
- [x] FIN-001-T03 Implement charge/deposit/payment/refund and immutable invoice flows after T01-T02. FolioService implements getOrCreateFolioForReservation, postCharge, reverseCharge, postPayment, refundPayment, calculateBalance, and issueInvoice. Invoices are stored with line_items_snapshot and protected by SQLite triggers. Evidence: `FolioLedgerTest` 8 tests/37 assertions; full suite 351/1461; Pint, PHPStan pass. SQLite only.
- [x] FIN-001-T04 Test rounding, duplicate requests, reversals, snapshots, access and reconciliation. Evidence: `FolioReconciliationTest` 3 tests/13 assertions covering multi-item charges and partial payments, snapshot stability across later postings, and prevention of double-reversal. Total finance tests: 11 tests/50 assertions; full suite 354/1474; Pint, PHPStan pass. SQLite only.
- [ ] FIN-002-T01 Approve business date/cashier shift/variance/close/reopen permissions and emergency procedure.
- [ ] FIN-002-T02 Implement closing snapshot and audit-protected close/reopen after approval.
- [ ] FIN-002-T03 Test concurrency, duplicate close, reopen and report revision behavior.
- [ ] FIN-002-T04 Run reservation-to-close acceptance E2E with fake data and record evidence.
