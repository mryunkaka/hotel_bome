# Acceptance Matrix — PROP-001 / GUEST-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| PROP-AC00 | Platform-admin can manage property master; staff and guests are denied | Feature tests for page, create, edit, deactivate and direct mutation | PASS: local SQLite, `PropertyManagementTest` |
| PROP-AC01a | Interim master inventory is scoped by property ID and direct access by staff is denied | `PropertyInventoryTest`, query/view/direct-route tests | PASS: local SQLite; this does not prove per-user property membership |
| PROP-AC01b | Active non-admin members cannot access current admin-only property inventory, including cross-property direct IDs | `PropertyInventoryTest::test_active_non_admin_memberships_cannot_read_or_mutate_property_master_inventory` covers hotel_admin/front_desk/finance/housekeeping/operations | PASS for current deny-by-default policy (SQLite, 1 test/12 assertions); granting property-admin inventory rights remains unapproved and out of scope |
| PROP-AC02a | Housekeeping transitions are validated and append history; property-scoped admin board/history shows current state and transitions | `RoomOperationsTest`, `RoomStatusBoardTest` | PASS: local SQLite; target-DB concurrency not verified |
| PROP-AC02b | Inclusive date blocks overlap safely, retain cancellation events and are queried within property scope | `RoomOperationsTest`, `RoomStatusBoardTest` | PASS: local SQLite; block query only, not reservation availability; target-DB concurrency not verified |
| PROP-AC03 | Referenced master is disabled, not removed | FK/lifecycle tests | PASS for property/outlet/room/type CRUD lifecycle on SQLite; room-type deactivation rejects active room references |
| GUEST-AC01 | PII limited by role/property and no PII in logs | Feature/export/log review | BLOCKED:D-007 |
| GUEST-AC02 | Duplicate review is reversible and does not remove stay links | Integration test | BLOCKED:D-007 |
