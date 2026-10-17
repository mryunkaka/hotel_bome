# Tasks: Operations, Reports, Backup, and Update

**Readiness**: Individually gated by FEATURE_REGISTER.md. Do not start all modules together.

- [ ] AUD-001-T01 Define event taxonomy, retention and authorized audit views; implement/test before financial modules.
- [ ] HK-001-T01 Confirm checklist, assignment, room-ready transition and lost-property SOP; then implement/test.
- [ ] ENG-001-T01 Confirm asset/work-order/down-room workflow; then implement/test schedule idempotency.
- [ ] INV-001-T01 Confirm costing/units/purchase approval/minibar; implement movement ledger and reconcile.
- [ ] EVT-001-T01 Confirm resource capacity, quote/BEO, deposit/cancellation; implement/test conflict rules.
- [ ] POS-001-T01 Confirm outlet/order/tender/provider; implement local offline order and idempotent charge.
- [ ] RPT-001-T01 Confirm report format/metric/date/permissions; reconcile export to finance/operations source.
- [ ] OPS-002-T01 Confirm OS/signer/backup key; prototype encrypted backup, second-machine restore and signed update.

Each child module uses its feature ID/task sequence in FEATURE_REGISTER. Scope and readiness must be separately tracked; no task inherits READY from this umbrella.
