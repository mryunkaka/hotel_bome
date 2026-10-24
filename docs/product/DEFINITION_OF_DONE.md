# Project Completion Criteria

The project is complete only when all required release scope is approved and every mandatory item below has evidence linked from the release acceptance record.

## Product

- Approved PRD/scope, property topology, roles/SOP, tax/accounting, privacy, provider and device decisions are recorded.
- Each release feature has spec, plan, tasks, acceptance, dependencies and owner for residual support operations.
- Required pages and tables cover empty/loading/error states, filters/pagination, validation, authorization, complete lifecycle and audit behavior.

## Data and security

- Tenant isolation passes direct URL/action/selector/export/job/API tests.
- Posted financial, closing, invoice snapshot and access events cannot be silently changed/deleted; correction flows are tested.
- CSRF/session, XSS, mass assignment, secret/logging, upload/export, rate-limit, local-network and replay tests pass where applicable.
- Dependencies and proprietary package handling are documented; no secret, customer data or Flux archive is tracked.

## Quality and release

- Required automated suites, static checks, E2E, offline, regression and recovery tests pass on supported environment.
- Clean offline Windows install and reboot, complete front desk close, encrypted backup, second-machine restore and supported upgrade pass.
- Hardware/provider claims have real test evidence; otherwise feature is explicitly out-of-scope or BLOCKED.
- Operator docs cover setup, daily operations, backup, restore, upgrade, offline behavior and incident recovery.
- Final Codex audit records PASS/FAIL/BLOCKED per criterion, branch/commit, commands, actual results and known limits.
