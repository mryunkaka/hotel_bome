# Feature Specification: External Connectors, Signed Releases, and Final Acceptance

**Feature IDs**: SYNC-001, EXT-001, REL-001, QA-001 | **Status**: Planning baseline

## Ringkasan

Tambahkan connector/sync berprivasi, licensing/signed updates dan final acceptance pilot setelah local-first core stabil dan scope disahkan.

## User Scenarios & Testing

### User Story 1 — Outage-safe connector and evidence-based release (P1)

Sebagai admin hotel, saya ingin connector gagal secara eksplisit dan release baru diuji sampai bukti cukup.

Acceptance scenarios:
1. Given WAN down, when local reservation/folio/check-in runs, then core flow stays available and connector item is queued.
2. Given provider timeout/duplicate webhook, when it retries, then state is pending/failed or idempotently reconciled, never false success.
3. Given guest PII policy not approved, when sync is attempted, then PII is excluded and transmission blocked.
4. Given signed artifact is altered/unsupported, when update checks it, then it refuses before extraction/migration.
5. Given acceptance evidence incomplete, when release is audited, then status is BLOCKED/FAIL, not PASS.

## Functional Requirements

- FR-001: Optional connectors MUST not block local PMS core and MUST show queued/failed/unknown/reconciled states.
- FR-002: Sync MUST use approved data scope, stable event ID, idempotency, bounded retry and conflict rules.
- FR-003: No guest PII leaves property before owner privacy/consent approval.
- FR-004: Release MUST verify signature, digest, schema range and backup/preflight before installation.
- FR-005: Final PASS MUST require evidence for each mandatory acceptance on supported target hardware/software.

## Success Criteria

- SC-001: WAN-down core flows pass independently of provider availability.
- SC-002: Duplicate event/retry causes no duplicate booking, charge or payment.
- SC-003: Tampered update rejected without changing active data.
- SC-004: All release criteria have command/environment/exit/evidence and no mock-only hardware PASS.

## Dependencies and readiness

Depends on local core and OPS-002. **BLOCKED** on D-007 privacy/sync; D-008 licensing/signing policy; D-009 provider/feature priority and commercial contract; supported target Windows pilot.
