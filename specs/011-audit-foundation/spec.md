# Feature Specification: Audit History Viewer (AUD-001 slice 1)

**Status**: Implemented and independently reviewed locally; SQLite verification passes. MySQL/Windows acceptance and hotel-specific retention policy remain open.

## Intent

Provide platform administrators one read-only view over existing immutable operational and IAM audit tables before financial modules are built. Reuse current event sources; do not create a duplicate write path or alter existing event semantics.

## Scope

Included sources: `user_access_events`, `property_membership_events`, `user_account_events`, `room_status_events`, and `room_block_cancellations`.

Included behavior: chronological event list, source/event type, timestamp, actor/subject/property labels when available, reason when recorded, date/property/source filters, bounded pagination, and strict platform-admin authorization. Missing actor/property links remain visible as unavailable labels where nullable.

Excluded: retention/deletion policy, export, audit mutations, new event producers, financial records, full role matrix, site SOP interpretation, MySQL/Windows acceptance.

## Temporary defaults (AUD-D1)

- Only active `platform_admin` can view this cross-property audit surface; every other account is denied. Do not infer rights from membership profile.
- Preserve source records indefinitely for now; no automated purge or UI deletion. This is a conservative technical default, not legal retention approval. Revisit with hotel/privacy owner before release.
- Display recorded event values only. Do not select password, remember token, two-factor/passkey secrets, guest data, or other columns not in these event tables.
- Default order is newest first. Date filters use the application timezone and inclusive calendar dates; maximum date span is 366 days. Page size fixed at 50.
- No export in this slice.

## User scenarios

1. As an active platform administrator, I can review existing operational and IAM events across properties in one paginated screen.
2. I can narrow entries by source, property and date range without changing source records.
3. As non-admin or unauthenticated user, I cannot view the page or its records.
4. Missing actor/property display does not hide or corrupt an event.

## Functional requirements

- FR-001: Route and query require authenticated active platform-admin.
- FR-002: Viewer reads only the five listed sources and returns common, minimized fields; original event IDs/source names remain distinguishable.
- FR-003: Results sort by event timestamp descending with deterministic source/id tie-breaks and paginate at 50 rows; navigation does not require exact total counts.
- FR-004: Validate filters server-side; date range requires both endpoints or neither; reject invalid/reversed dates, spans over 366 calendar dates, unknown sources and nonexistent properties.
- FR-005: Viewing must not write to audit tables or alter source events.
- FR-006: HTML output escapes untrusted names/reasons.

## Acceptance criteria

- AC-01: Guest and non-platform-admin direct requests denied; active platform-admin gets 200.
- AC-02: All five event sources render with source/event labels, recorded timestamps and only allowed columns.
- AC-03: Source/property/date filters and pagination operate correctly; invalid date ranges return validation errors.
- AC-04: XSS strings in names/reasons render escaped; secret-like user fields and environment values are absent.
- AC-05: Event counts and source rows remain unchanged after reads.
- AC-06: SQLite tests pass. MySQL query/trigger behavior and Windows UI acceptance remain unverified.

## Dependencies and risks

Depends on IAM-001 and PROP-001. Existing event schemas remain separate and immutable. No legal retention or privacy approval is implied by this implementation.