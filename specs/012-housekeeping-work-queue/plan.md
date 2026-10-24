# Housekeeping Work Queue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an auditable, property-scoped room-cleaning task queue that never changes room availability or cleanliness implicitly.

**Architecture:** Add append-only housekeeping task records and transition records with composite property/room constraints. Use existing Laravel controller, middleware, database query-builder, and Blade patterns; keep housekeeping access under assigned-property middleware and admin queue access under platform-admin.

**Tech Stack:** Laravel 13, PHP 8.4+, Blade, SQLite feature tests; no new dependency.

**Spec:** `spec.md`

## Global Constraints

- Never hard-delete operational history.
- Deny by default and enforce property scope server-side.
- No guest/financial PII in queue.
- Do not alter room status, occupancy, blocks, or reservation availability implicitly.
- Preserve all pre-existing worktree changes and Flux archive.

## Review Focus

- Cross-property room/user/task IDs: reject without writes.
- Membership/property revoked or inactive after page load: next mutation denied.
- Terminal/invalid state transitions: reject and retain history.
- Validation/XSS/long input: reject or escape; no untrusted HTML.
- Concurrent state changes: serialize/revalidate; SQLite evidence does not prove MySQL locking.

---

### Task 1: Persist queue and immutable history

**Files:** Create additive migration under `database/migrations/`; add migration rollback coverage in a new focused feature test.

- [ ] Add housekeeping tasks table and append-only events with property, room, optional assignee, creator, status, instruction, actor, reason, timestamp. Add composite foreign keys for room/property and indexes for property/status/date/assignee. Restrict deletion; immutable update/delete triggers match room operations pattern.
- [ ] Test schema constraints, migration rollback, immutability on SQLite.
- [ ] Run focused migration tests.

### Task 2: Domain operations and authorization

**Files:** Create `app/Services/Properties/HousekeepingTasks.php`; create a focused feature test; modify `routes/web.php` only for task routes.

- [ ] Implement create/list/show/assign/start/complete/cancel with DB transactions, active same-property room and assignee validation, legal transitions, and event append.
- [ ] Use existing `platform-admin` and `property-role:housekeeping` gates; bind all reads/mutations to route property and task ID.
- [ ] Test allowed transitions, denied role/property/user/task IDs, invalid state, terminal state, audit and no room-status side effects.
- [ ] Run focused tests and Pint.

### Task 3: UI, filters, and integration evidence

**Files:** Create controller and `resources/views/properties/housekeeping/tasks.blade.php`; add route/controller assertions in the feature test.

- [ ] Add paginated status filter, create/assignment form, task cards/table, validation and empty state; escape all task text.
- [ ] Verify responsive markers, actions, access denials and persisted state.
- [ ] Run focused tests, full `composer test --no-interaction`, PHPStan, `git diff --check`, and Vite+ scratch build.
- [ ] Update task checklist, feature register, decision log, and workflow state only with observed evidence.

## External acceptance

MySQL row-lock/concurrency, owner/property-manager SOP review, Windows/LAN/browser/device acceptance remain external. Do not call these locally verified.
