# Feature Specification: Housekeeping Work Queue

**Feature ID**: HK-001 | **Status**: Provisional implementation baseline

## Intent

Give housekeeping staff a property-scoped queue of room-cleaning work, with assignment and auditable lifecycle. Existing room cleanliness events remain the source for room status. A work item never creates a reservation, changes occupancy, opens a room block, or marks a room available for sale.

## Provisional decisions

- A task refers to one active room in one property; cross-property room/user links are invalid.
- Task states: `open` → `in_progress` → `completed`; `open` or `in_progress` may be `cancelled` with a required reason. Completed/cancelled tasks are terminal; corrections create a new task.
- Assignment is optional; if present, assignee must have an active `housekeeping` membership in the same property. Creator and every transition actor are recorded.
- `completed` does not automatically set room cleanliness. An authorized housekeeping user separately records the existing room-status transition.
- Housekeeping may list and update tasks only for its assigned active property. Platform admins may manage queue for any active property. Other roles denied.
- No guest name, stay, folio, payment, or sensitive personal data appears in this feature.

These are reversible technical defaults under `docs/HERMES_AUTONOMOUS_BUILD.md`, not approved Hotel Bome SOP. Record in decision log and review before production acceptance.

## User scenarios

1. Housekeeping opens assigned-property queue and filters by task status.
2. Authorized staff creates a task for an active room, optionally assigns an eligible colleague, and enters a short instruction.
3. Assignee or authorized housekeeping member starts work and completes it; every state change is retained with actor, time, and optional reason.
4. Staff cancels an unfinished task with a reason. Terminal history remains visible and cannot be edited or deleted.
5. Direct URL, altered property/room/user IDs, inactive membership/property, or another property's task is denied without data mutation.

## Functional requirements

- FR-001: Persist work items with property, room, optional assignee, creator, status, instruction, and timestamps.
- FR-002: Enforce property and role authorization server-side on every list/read/create/update action.
- FR-003: Validate active room and same-property active housekeeping assignee within the transaction that creates/changes assignment.
- FR-004: Enforce the state machine; append immutable transition events with actor and reason.
- FR-005: No physical delete. Completed/cancelled tasks are read-only; cancellation requires nonblank reason up to 500 characters.
- FR-006: Paginate/filter queue; render responsive desktop/mobile layouts, empty state, validation errors, and escaped text.
- FR-007: Do not expose guest or financial data or infer room availability from task status.

## Acceptance

- CRUD lifecycle: list/create/show, assign/reassign, start, complete, cancel; invalid transitions and terminal edits rejected.
- Cross-property IDs and staff without active housekeeping membership cannot read or mutate task; no unauthorized event is written.
- Room status remains unchanged when work completes.
- Assignment and transition history survive reload and cannot be overwritten/deleted through app routes.
- SQLite feature tests, Pint, PHPStan, full composer test, and scratch Vite build pass. MySQL locking, browser/device/Windows and real SOP acceptance remain external.
