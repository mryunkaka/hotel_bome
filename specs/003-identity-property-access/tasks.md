# Tasks: Identity and Property Authorization

**Feature ID**: IAM-001 | **Depends on**: OPS-001 | **Status**: DESIGN READY; implementation awaits role/approval SOP.

- [ ] IAM-001-T01 Define role/action/property matrix and user approval/rejection transitions. READY for discovery; owner/SOP input required.
- [ ] IAM-001-T02 Specify user status, membership, permission and audit schema, migration and session revoke behavior. Depends on T01.
- [ ] IAM-001-T03 Implement pending registration and admin approval UI/actions; ensure pending users cannot authenticate. BLOCKED until T01-T02.
- [ ] IAM-001-T04 Implement multi-property authorization for policies, queries, selectors, exports, jobs and API. Depends on T02.
- [ ] IAM-001-T05 Test bootstrap replay, pending login, role/membership change, cross-property IDOR and audit. Depends on T03-T04.
- [ ] IAM-001-T06 Record role-based acceptance evidence and operator setup docs. Depends on T05.

Never use the Test User seeder as production admin. Do not loosen pending state or tenant controls to make a test pass.
