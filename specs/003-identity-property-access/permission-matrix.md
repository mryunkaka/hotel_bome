# IAM-001 provisional route permission matrix

**Status:** current route/middleware baseline includes platform-admin, housekeeping and operations groups; provisional OPS-D5/IAM-D3/ENG-D1. Not final hotel SOP or production acceptance.
**Inventory source:** `php artisan route:list --json` (114 registered routes, 98 named; 36 gated by `EnsurePlatformAdmin`, 6 by `EnsurePropertyRole:housekeeping`, 9 by `EnsurePropertyRole:operations`, checked 2026-10-23 after adding work-order detail route). Includes Laravel/Flux/Livewire support routes.
**Policy:** named accounts; active-user check; default deny; explicit property membership; unlisted routes/actions denied. This document does not create new permissions.

## Role definitions

| Role | Scope | Current meaning |
|---|---|---|
| `platform-admin` | Installation-wide | Bootstrap/support role. Current property master-data routes are platform-admin-only; not intended as a routine hotel front-desk account. |
| `housekeeping` | Explicit active property membership | May use assigned property's housekeeping board/task queue and permitted status/task actions only. No access to engineering routes. |
| `operations` | Explicit active property membership | May use assigned property's work orders and engineering asset register only. No housekeeping, guest, finance or master-data access inferred. |
| `front-desk` | No route grant in this slice | Provisional hotel role; no reservation routes currently exist. |
| `finance` | No route grant in this slice | Provisional hotel role; no folio/payment routes currently exist. |
| `property-manager` | No route grant in this slice | Provisional hotel role; no manager override routes currently exist. |
| `active-member` | Explicit active membership | Membership alone grants no hotel data access. Only the explicitly implemented `housekeeping` role may see its assigned active properties at `/my/properties`; other roles remain denied until a feature-specific permission is implemented and tested. |
| `pending` / `suspended` | None | Login/protected access denied; pending approval and suspension flows are separately tested. |

## Route groups

| Routes (from `routes/web.php`) | Current server gate | Property scope / allowed operation | Default decision and tests required |
|---|---|---|---|
| `GET/POST /setup/first-admin` | `loopback`; one-time bootstrap state | Local peer only; creates first platform admin once | Keep loopback-only, one-time, unauthenticated wizard. Evidence: `FirstAdminBootstrapTest` covers non-loopback/forwarded peer denial, one-time replay lock and validation. Concurrency semantics remain DB-driver dependent. |
| `GET /operator/health` | `auth`, `active`, `platform-admin` | No tenant data; minimal app/DB/storage/schema status | Keep admin-only and secret-free; guest/staff/failure response tests exist in `HealthControllerTest`. |
| `GET /admin/audit` | `auth`, `active`, `platform-admin` | Cross-source audit viewer; read-only | Keep platform-admin-only; source/property filters and no source mutation covered by `AuditHistoryTest`. Review privacy/retention separately. |
| `/admin/users*` approval, membership, account lifecycle | `auth`, `active`, `platform-admin` | Installation-wide identity administration | Keep platform-admin-only; approval/member/lifecycle tests cover actor, self-escalation, reason, audit, rollback, session/remember-token invalidation. |
| `/properties*` property, outlet, room-type, room CRUD/deactivate, room history, room status, room blocks | `auth`, `active`, `platform-admin` | Installation-wide master-data/admin operations; each nested room/outlet/type must belong to route property | No hotel role inherits access. Existing coverage includes `PropertyManagementTest::test_only_platform_admins_can_manage_properties`, `PropertyManagementTest::test_staff_cannot_mutate_properties`, `PropertyInventoryTest::test_staff_cannot_update_inventory_by_direct_id`, `PropertyInventoryTest::test_staff_cannot_view_or_mutate_another_property_inventory`, `RoomStatusBoardTest::test_staff_cannot_view_room_history`, and `RoomStatusBoardTest::test_staff_and_cross_property_room_block_creation_are_denied`. These are bounded tests, not exhaustive per route/method. If property managers need access, grant individual capabilities only after tests. |
| `/properties/{property}/housekeeping*` and `/housekeeping/tasks*` | `auth`, `active`, `property-role:housekeeping` | Assigned-property room board/status and task CRUD/actions; platform-admin bypass follows `EnsurePropertyRole`. | `PropertyRoleAuthorizationTest` and `HousekeepingTaskTest` cover role/property/user eligibility, direct IDs, task lifecycle and minimized task data; SQLite only. |
| `/properties/{property}/assets*`, `/work-orders*` including `GET /work-orders/{workOrder}` detail/history | `auth`, `active`, `property-role:operations` | Assigned-property asset registry and work-order lifecycle/detail; platform-admin bypass follows middleware. Assets/rooms/assignees and order history are same-property scoped. | `EngineeringAssetTest`/`WorkOrderTest` cover direct-ID read/create/update/deactivate/assign/transition/detail, other-property detail 404, same-property active asset linking, operations membership and audit. `PropertyRoleAuthorizationTest::test_housekeeping_membership_does_not_grant_engineering_asset_or_work_order_routes` verifies role separation. SQLite only. |
| `GET /my/properties` | `auth`, `active` | Current user's active properties with `housekeeping` membership only | Other membership roles return no property data; `PropertyRoleAuthorizationTest` covers role/property/active filtering. |
| `GET /my/operations-properties` | `auth`, `active` | Platform admin sees active property list; other users see only active `operations` membership properties | `WorkOrderController::properties`; role/property filter present. `WorkOrderTest::test_operations_selector_lists_only_active_assigned_properties` and `test_platform_admin_operations_selector_lists_only_active_properties` cover membership and inactive-property filtering. |
| `GET /dashboard` and settings routes | `auth`, `active` (settings in `routes/settings.php`) | Personal account/preferences only unless separately scoped | No operational hotel data inferred. `UserAccountLifecycleTest::test_inactive_user_cannot_access_dashboard_or_personal_settings` verifies pending and inactive users are redirected from dashboard and profile settings. Appearance/security settings share `auth`, `active`; broader page-by-page active-session coverage remains. |
| `POST /register` | `guest`, `throttle:5,1` | Creates pending account; no property membership or protected access | Preserve pending state and throttling; `RegistrationTest`/approval tests. |
| `/login`, password reset, passkey endpoints | Auth package/routes | Authentication only | Verify active/pending/suspended credential and recovery paths separately; never grant property access by authentication alone. |

## Action boundaries

- Front desk: no current route grant. Future bounded default may cover reservation lookup/create/edit and ordinary check-in/out only after reservation spec, state-transition tests, property authorization and guest-data minimization. No oversell/force-sell, post-check-in cancellation, refund, adjustment, cross-property transfer or role administration by default.
- Housekeeping: assigned-property board plus status-only transition; no folio, payment, full guest profile, room/rate override, account administration, or another property's data.
- Finance: no current route grant. Future financial routes require separate finance policy, audit, transaction/authorization tests and accountant approval for tax/invoice/payment semantics.
- Property manager: no current route grant. Do not infer manager access from title; add explicit property-scoped actions and review workflow only after tests.
- Platform admin: keep as technical support/global bootstrap role. Do not use as routine hotel operator or imply separation-of-duties approval for financial actions.

## Local test evidence and remaining IAM scope

- Route inventory: `php artisan route:list --json` reports 114 registered routes/98 named; middleware counts are 36 `EnsurePlatformAdmin`, 6 `EnsurePropertyRole:housekeeping`, 9 `EnsurePropertyRole:operations`. Counts include framework/package routes; app property-access routes are summarized above.
- `PropertyAdminRouteAuthorizationTest::test_all_platform_admin_routes_deny_active_non_admin_for_all_methods` enumerates all 36 platform-admin routes and verifies active non-admin receives 404. Includes property master data, audit, health and user lifecycle.
- Engineering: `EngineeringAssetTest`/`WorkOrderTest` cover active-property/user/role gates, membership revoke, selector scoping, cross-property list/create/update/deactivate/assign/transition and asset linkage; `PropertyRoleAuthorizationTest` verifies housekeeping cannot access engineering routes. SQLite only.
- Property-query inventory: `PropertyInventoryController` scopes operational room/outlet/type/history/block queries and joins by route-bound `$property->id`; `MyPropertiesController` uses active housekeeping memberships; housekeeping/engineering controllers and services scope list, history, room, asset, assignee and mutation lookups by property. Installation-wide `PropertyController::index`, audit property selector, approval/membership property lists, and admin identity queues are intentionally platform-admin-only.
- Dynamic UI review: app Blade `wire:*`/Volt actions are limited to authenticated personal settings, profile, passkey, two-factor and account-delete flows; no property operational data action or app-defined queued job/import/export/API endpoint was found. Framework/package routes remain in inventory and are not custom tenant-data endpoints.
- IAM-001-T04 current app-defined route/query/UI inventory is complete for this tree. MySQL constraints/concurrency, Windows acceptance, and future surface re-audit remain external/future gates; do not infer full production IAM acceptance.
- Bootstrap: `FirstAdminBootstrapTest` covers non-loopback/forwarded peer denial, validation and replay lock; MySQL/Windows concurrency and proxy acceptance remain for OPS pilot.
- Public passkey discovery: `PasskeyDiscoveryTest::test_public_passkey_discovery_exposes_only_security_endpoint_urls` verifies only two fixed security URLs are returned.
- Housekeeping: assigned-property read/write, revoke denial on next GET/PATCH, cross-property room ID rejection without event mutation, inactive property GET/PATCH denial without event mutation, pending/inactive user denial, transition validation and minimized data are covered by `PropertyRoleAuthorizationTest` (SQLite).
- Dashboard/settings: `UserAccountLifecycleTest::test_inactive_user_cannot_access_dashboard_or_personal_settings` tests pending/inactive accounts; `SecurityTest` covers password confirmation/update. Appearance and profile are inside auth+active middleware.
- Audit: `AuditHistoryTest::test_audit_history_requires_active_platform_admin` and source/property/date/escaping/pagination/unchanged-source tests.
- Property/inventory direct ID and cross-property mismatch cases are covered in `PropertyInventoryTest`, `PropertyManagementTest` and `RoomStatusBoardTest`; staff cannot access admin-only read/write routes.

Local matrix/testing task IAM-001-T01b is complete for current app-defined routes on SQLite. It does not prove MySQL behavior, Windows acceptance, physical session-row revocation, role approval by hotel owner, or global IAM SC-002/IAM-AC03. Continue those separately under IAM-001-T04/T05b/T06 and OPS pilot; keep all unlisted routes/actions denied. Do not add front-desk/finance/property-manager permissions until their feature specs are ready.

Audit source: independent read-only review on branch `work/hermes-full-build`, commit `9fb3239`, 2026-10-23; no repository changes by reviewer.
## Source references

- Provisional defaults and research caveats: `docs/product/DECISION_LOG.md` OPS-D5.
- Existing bounded housekeeping default: `docs/product/DECISION_LOG.md` IAM-D3.
- Product roles/tasks demonstrate patterns, not universal SOP: Oracle OPERA Cloud booking/inventory/housekeeping/change-log docs and HTNG/AHLA framework linked from OPS-D5.
