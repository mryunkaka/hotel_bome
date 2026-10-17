# Tasks: Property Inventory and Guest Privacy

**Depends on**: IAM-001 | **Readiness**: PROP design READY; GUEST PII BLOCKED:D-007.

- [ ] PROP-001-T01 Confirm room/outlet/type/status vocabulary and disable rules with hotel SOP.
- [ ] PROP-001-T02 Model property-scoped inventory, blocks, status history, FK/index and concurrency invariants.
- [ ] PROP-001-T03 Implement room/type/outlet CRUD and board with policy, validation and disable lifecycle.
- [ ] PROP-001-T04 Test cross-property IDOR, availability blocks, concurrency and referenced-master retention.
- [ ] GUEST-001-T01 Resolve D-007 field minimization, consent, document access, retention/export and deletion policy. BLOCKED.
- [ ] GUEST-001-T02 After D-007, model and implement profiles/documents/duplicate review with policy and audit.
- [ ] GUEST-001-T03 Test PII visibility, export, retention, cross-property access, duplicate handling and history.

Do not implement or seed real guest data before GUEST tasks become ready.
