# Tasks: Property Inventory and Guest Privacy

**Depends on**: IAM-001 | **Readiness**: interim property/inventory master CRUD, housekeeping-status events, room blocks and admin room board implemented; target-DB concurrency, hotel-user membership, reservation availability and guest privacy remain open.

- [x] PROP-001-T01a Record reversible property name/code/active-disable defaults in `docs/product/DECISION_LOG.md` (PROP-D1); do not infer tax/timezone/address rules.
- [x] PROP-001-T01b1 Record reversible outlet/room-type/room identity and disable rules in `docs/product/DECISION_LOG.md` (PROP-D2); do not infer availability or billing semantics.
- [x] PROP-001-T01b2 Define provisional room status/history/block semantics with testable transitions before adding availability behavior; document invalidation/revision path (PROP-D3).
- [x] PROP-001-T02b2 Add additive room housekeeping history and date-block/cancellation event tables with transactional invariants, migration backfill, and SQLite constraints.
- [x] PROP-001-T03b2 Implement interim platform-admin room status board, filters, housekeeping lifecycle action, property-scoped block create/cancel and paginated operational history.
- [x] PROP-001-T04b2 Verify transitions/history, overlapping blocks, date boundaries, migration backfill/rollback and access on SQLite; reservation conflicts/booking availability remain OUT OF SCOPE.
- [ ] PROP-001-T04b3 Verify locking, simultaneous transitions/cancellations and block write races on approved target DB. BLOCKED_EXTERNAL: target MySQL/pilot DB is unavailable; SQLite tests do not prove `FOR UPDATE` behavior.
- [x] PROP-001-T02a Add additive `properties` identity schema with unique code and active flag; verify SQLite constraints.
- [x] PROP-001-T02b1 Add additive outlet/room-type/room schema with per-property unique codes and composite property/type FK; SQLite tests pass. Room status/history/block tables follow in T02b2.
- [x] PROP-001-T03a Implement platform-admin property list/create/edit/deactivate pages and validation.
- [x] PROP-001-T03b1 Implement interim admin-only inventory list/create/edit/deactivate pages and form validation for outlet/room type/room. Not availability board.
- [x] PROP-001-T04a Test unauthenticated/staff denial, direct mutation, duplicate/invalid code, edit and deactivation retention.
- [x] PROP-001-T04b1 Test inventory direct-ID scope, staff denial, cross-property room type FK/validation, inactive property/type rules, HTML escaping, deactivation retention and active-room reference protection on SQLite.
- [x] PROP-001-T04b4 Test active `hotel_admin`, `front_desk`, `finance`, `housekeeping`, and `operations` memberships cannot read or mutate current admin-only property inventory, including direct cross-property update ID; assert protected rows unchanged. SQLite: 1 test / 12 assertions.
- [x] PROP-001-T04b5 Validate room-board query filters (`q`, housekeeping status, lifecycle) at request boundary; reject unknown status/lifecycle, array search input and overlong search. `PropertyInventoryTest`: 18 tests / 117 assertions total on SQLite.
- [ ] GUEST-001-T01 Obtain owner/privacy counsel decision for field purpose/legal basis, notices/consent where applicable, retention by category, data-subject request, disclosure, erasure/archival conflicts, and staff role matrix. **BLOCKED_EXTERNAL:D-007.** Evidence basis and temporary technical guardrails: `docs/product/DECISION_LOG.md` (GUEST-D7).
- [ ] GUEST-001-T02 Implement only after T01 resolves named fields and processing purpose. Use property-scoped records, deny-by-default access, audit access/change, redacted logs, no cross-property merge, explicit erasure workflow and retention settings; no document upload until separately approved and protected.
- [ ] GUEST-001-T03 Test approved field allowlist, PII role matrix, export denial/allowlist, retention/erasure request workflow, cross-property direct IDs, duplicate review/reversal and audit history.
- [x] GUEST-001-T00 Record local data-flow inventory and provisional guardrails without adding schema, route, guest data, retention duration, upload, export, or sync. Code search found no guest/customer processing in `app/`; PDP evidence is summarized in GUEST-D7. Owner/legal decision remains blocked.

Interim CRUD authority is platform-admin only; do not expose to hotel staff until IAM membership/policy and property isolation tests land.
