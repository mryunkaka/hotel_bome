# Engineering Work Orders Implementation Plan

## Goal

Deliver a local-first, property-scoped engineering request queue with immutable history; no asset costing, preventive schedules, financial postings, or room-availability changes.

## Design

Additive `work_orders` and `work_order_events` tables; composite property/room/task foreign keys; append-only event triggers. Use Laravel DB transactions, `lockForUpdate`, existing `property-role:operations` middleware, active-property checks, and Blade forms/pagination. No new dependency.

## Tasks

1. Schema, constraints, indexes, immutability and SQLite rollback tests.
2. Transactional CRUD/assignment/status service and tests for role/property boundaries, lifecycle, cancellation reason, XSS, no room-status side effects.
3. Property-role protected routes and responsive queue/list/detail controls.
4. Run focused/full test suite, Pint, PHPStan, scratch build and diff check; update register/state with evidence.

## Constraints

- Do not edit/delete posted history or use hard-delete.
- Validate same-property active room/assignee on each write.
- Resolve work order does not mark room clean/available.
- Provisional ENG-D1 requires property-manager review before pilot.
- MySQL, browser, Windows/LAN and pilot acceptance stay external.
