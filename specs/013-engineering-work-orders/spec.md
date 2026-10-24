# Feature Specification: Engineering Work Orders

**Feature ID**: ENG-001 | **Status**: Provisional local slice

## Goal

Track property-scoped maintenance requests for rooms and hotel equipment without changing room availability, financial ledgers, or guest records.

## Provisional decisions (ENG-D1)

- Work orders are standalone requests with optional active room reference; no asset registry, preventive schedule, costing, purchase order, or out-of-order transition in this slice.
- Status: `open` → `in_progress` → `resolved`; `open`/`in_progress` may be `cancelled` with reason. Terminal work orders cannot be edited/deleted; corrections create a new order.
- Any active property member with `operations` role or platform admin may create, list, assign, transition. Assignee must be active and have `operations` role on same property. Read/update constrained to same active property.
- Every assignment/status change is immutable, actor/timestamp recorded. Description max 1000 chars; no guest, stay, payment, or contact data.
- Resolution is not proof of room readiness; cleanliness and room availability stay separate operations.

These reversible technical defaults are not hotel SOP approval. Property manager reviews before pilot.

## Asset inventory slice (ENG-D2 provisional)

- Engineering can register and update property equipment with unique property code, name, category, optional location and serial, operational status, and active flag.
- Assets are disabled, never hard-deleted. Every create/update/status/deactivation change records actor/time and before/after snapshot in immutable history.
- Asset status: `operational`, `under_repair`, `retired`; status never changes room availability or room cleanliness.
- Work order may link an asset belonging to same property; no purchase cost, depreciation, supplier or GL fields.
- These reversible defaults are not owner/property-manager asset SOP approval.

## Acceptance

- Asset list/create/edit/deactivate, code uniqueness, same-property scope, status lifecycle, immutable history, linked work orders, no hard delete.
- CRUD through open/list/detail-assignment/transitions, no hard delete.
- Cross-property task/room/assignee IDs denied with no writes; inactive memberships/properties denied.
- Invalid transitions and blank cancellation reason rejected; event history retained.
- Resolving work order does not write room status or availability.
- Pagination/status filtering, escaped text, accessible responsive UI.
- SQLite, Pint, PHPStan, full suite, scratch build pass; MySQL/browser/Windows/pilot acceptance remain external.
