# Tasks: Operations, Reports, Backup, and Update

**Readiness**: Individually gated by FEATURE_REGISTER.md. Do not start all modules together.

- [ ] AUD-001-T01 Define complete event taxonomy and retention policy with owner/privacy approval; implement remaining authorized audit views. Partial: viewer slice `specs/011-audit-foundation` reuses five existing IAM/room event sources; retention, finance events and policy approval remain open.
- [ ] HK-001-T01 Confirm checklist, assignment, room-ready transition and lost-property SOP; then implement/test.
- [ ] ENG-001-T01 Confirm asset/work-order/down-room workflow; then implement/test schedule idempotency.
- [x] INV-001-T01 Confirm costing/units/purchase approval/minibar; implement movement ledger and reconcile. Implemented `InventoryService` with `InventoryItem` and `StockMovement` append-only ledger protected by SQLite triggers (no update/delete). Recomputed stock balances directly from movements; atomic minibar consumption posting (deducts stock and posts food_beverage charge to guest folio via FolioService). Evidence: `InventoryLedgerTest` 4 tests/17 assertions; full suite 368/1553; Pint, PHPStan pass. SQLite only.
- [ ] EVT-001-T01 Confirm resource capacity, quote/BEO, deposit/cancellation; implement/test conflict rules.
- [ ] POS-001-T01 Confirm outlet/order/tender/provider; implement local offline order and idempotent charge.
- [x] RPT-001-T01 Confirm report format/metric/date/permissions; reconcile export to finance/operations source. Implemented `ReportingService`: daily financial report with charges breakdown by item_type and payments by tender_type; occupancy report with breakdown per room type and percentage; strict property isolation; CSV export with formula injection sanitization (`=`, `+`, `-`, `@`) and guest PII exclusion per D-007. Evidence: `ReportingServiceTest` 4 tests/21 assertions; full suite 364/1536; Pint, PHPStan pass. SQLite only.
- [ ] OPS-002-T01 Confirm OS/signer/backup key; prototype encrypted backup, second-machine restore and signed update.

Each child module uses its feature ID/task sequence in FEATURE_REGISTER. Scope and readiness must be separately tracked; no task inherits READY from this umbrella.
