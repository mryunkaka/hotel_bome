# Implementation Plan: Property Inventory and Guest Privacy

Feature IDs: PROP-001, GUEST-001 | Branch: feat/property-guest-foundation

Design property inventory first, then guest records; do not expose guest documents until D-007 policy is approved. For GUEST-001-T02, add explicit `receptionist`, `supervisor`, and `owner` membership roles; do not alias them to `hotel_admin` or `platform_admin`. Enforce property scope on every guest read/action. Receptionist edits and deactivation require reasons; deactivation is non-permanent. Supervisor can validate, audit receptionist activity, and report; owner can CRUD/approve/report across all properties in one installation as a business role distinct from `platform_admin`. Ordinary employee roles remain denied. Use synthetic data only, immutable audit, no guest export/sync/hard-delete. Verify PHPUnit against isolated MySQL as required for feature acceptance; SQLite is not target-DB acceptance.

Constitution gates: least privilege, PII minimization, local-first, history retention, tests with fake data only. Separate PROP-001 and GUEST-001 implementation commits if their readiness differs.
