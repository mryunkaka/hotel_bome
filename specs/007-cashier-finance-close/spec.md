# Feature Specification: Folio Payments, Cashier, and Night Audit

**Feature IDs**: FIN-001, FIN-002 | **Status**: Planning baseline

## Ringkasan

Mengelola folio posting, deposit/payment/refund, invoice snapshot, cashier reconciliation, business date, close dan reopen.

## User Scenarios & Testing

### User Story 1 — Posting dan close dapat direkonsiliasi (P1)

Sebagai cashier/night auditor, saya ingin menagih, menerima payment dan menutup tanggal bisnis dengan histori yang tidak berubah diam-diam.

Acceptance scenarios:
1. Given folio aktif, when charge/payment diposting, then ledger dan saldo konsisten serta request idempotent.
2. Given transaksi sudah posted, when user mencoba edit/delete, then request ditolak dan correction perlu reversal/adjustment beralasan.
3. Given business date masih memiliki pending postings/shift, when close diminta, then close mengikuti gate yang dikonfigurasi.
4. Given invoice sudah diterbitkan dan master rate/tax berubah, when invoice dibaca ulang, then snapshot penerbitan tetap utuh.

## Functional Requirements

- FR-001: Posted amounts, payments, refunds, invoices dan closing MUST append-only.
- FR-002: Correction MUST berupa reversal/adjustment yang mereferensikan original, reason, actor dan time.
- FR-003: Payment/invoice/close MUST idempotent terhadap double submit/retry.
- FR-004: Invoice MUST snapshot customer, line, currency, rate, tax dan nomor sesuai policy approved.
- FR-005: Business date eksplisit; cashier variance, close dan reopen wajib permission/audit.

## Key Entities

Folio, posting, payment, refund, invoice snapshot, cashier shift, tender, business day, closing snapshot.

## Success Criteria

- SC-001: Saldo folio dan totals dapat dihitung ulang dari ledger.
- SC-002: Duplicate request tidak menimbulkan duplicate financial effect.
- SC-003: Posted history tidak dapat dimutasi diam-diam.
- SC-004: Tax/invoice/tender/close acceptance cocok keputusan accountant/owner.

## Dependencies and readiness

Depends on FO-001 and RATE-001. **BLOCKED:D-004,D-010** untuk jurisdiction/tax/invoice/currency, tender, correction approval, cash variance, day-close/reopen dan accountant signoff.
