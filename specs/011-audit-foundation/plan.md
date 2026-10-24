# Implementation Plan: Audit History Viewer

Feature ID: AUD-001 slice 1 | Branch: `work/hermes-full-build`

## Technical context

Use Laravel 13 query builder over existing immutable event tables. Avoid a new central table, event writer abstraction or dependency. Build one normalized query per source with a source discriminator and nullable actor/property/name/reason fields. Apply validated source/property/date predicates to source queries before combining them with `UNION ALL`; this preserves source indexes and avoids filtering union aliases. Apply stable outer ordering and fixed-size pagination. All source tables stay read-only.

## Data and access

- Sources: `user_access_events`, `property_membership_events`, `user_account_events`, `room_status_events`, `room_block_cancellations`.
- Query only event columns and actor/property display names required by the view. Room events and cancellations join `rooms`/`properties` for property and room labels. Keep nullable actor context for backfilled events.
- Route middleware: `auth`, `active`, `platform-admin`. Reuse current sidebar conventions.
- Fixed page size 50; filter bounds and timezone follow `spec.md` temporary defaults.

## Phases

1. Add feature tests for permission, all source records, filter validation/pagination, escaping, and read-only behavior.
2. Implement controller/query/view and admin navigation.
3. Run feature tests, full `composer test`, Composer validation, Vite scratch build, and diff check.

## Risks / limits

`UNION ALL` and bindings must behave under SQLite and the eventual MySQL target; only SQLite is locally available. Simple pagination avoids an exact-count query, but newest-first ordering can still inspect/sort a large history; existing sources do not consistently index `created_at`. Measure representative hotel history before proposing an additive index or materialized read model. Event sources retain their own semantics and deletion rules. No retention/legal approval, export, complete hotel role matrix, or Windows acceptance is delivered.
