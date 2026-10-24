# Acceptance Matrix — IAM-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| IAM-AC01 | Pending self-registration cannot login/access property | `RegistrationTest`, `UserApprovalTest`, `PropertyRoleAuthorizationTest` | PASS for current app routes (SQLite); external platform acceptance remains |
| IAM-AC02 | Admin approval and per-property membership work | `UserApprovalTest`, `UserMembershipManagementTest`, `PropertyRoleAuthorizationTest` | PASS for current SQLite flows; target MySQL concurrency remains unverified |
| IAM-AC03 | Cross-property access denied across current app route/action/query paths | `permission-matrix.md`; `PropertyAdminRouteAuthorizationTest`, property/inventory/history, housekeeping, engineering and audit tests | PASS for current app-defined surfaces (SQLite). No export/import/job/API surface exists yet; re-audit when added. |
| IAM-AC04 | First-admin bootstrap replay denied | `FirstAdminBootstrapTest::test_existing_platform_admin_locks_first_admin_bootstrap` | PASS (SQLite feature test) |
| IAM-AC05 | Access revoke/suspension invalidates prior sessions logically | `UserAccountLifecycleTest`, `UserMembershipManagementTest` (remember-cookie and stale-session tests), migration tests | PASS for next protected request and remember-token rotation (SQLite); physical session rows may remain. MySQL/Windows untested. |
| IAM-AC06 | Platform-admin approval/rejection requires active scope, profile, reason; append-only audit; no implicit property access | `UserApprovalTest`, `UserAccessMigrationTest` | PASS (SQLite, 16 tests/60 assertions) |
| IAM-AC07 | Housekeeping membership sees only assigned active-property board/status and may change permitted state only | `PropertyRoleAuthorizationTest`, `HousekeepingTaskTest` | PASS for bounded SQLite flows; SOP approval and Windows/browser acceptance remain external. |
| IAM-AC08 | Platform-admin assignment/role-change/revoke requires reason and immutable audit; changes invalidate prior sessions logically | `UserMembershipManagementTest`, `PropertyMembershipEventsMigrationTest` | PASS for SQLite membership suite (10 tests/78 assertions); server session rows remain; MySQL concurrency/trigger and Windows unverified. |
