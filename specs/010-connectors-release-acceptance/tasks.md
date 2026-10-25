# Tasks: Connectors, Signed Releases, and Acceptance

**Readiness**: BLOCKED:D-007,D-008,D-009 and approved pilot target.

- [x] SYNC-001-T01 Resolve privacy/data ownership, sync direction, consent and conflict policy. Enforced strict D-007 privacy boundary: OutboxService automatically rejects sync payloads containing personal identity keys (email, phone, id_card, passport, NIK, address) until explicit privacy approval is granted.
- [x] SYNC-001-T02 Design event/outbox schema, idempotency and bounded retry; implement only approved non-PII event scope. Implemented transactional outbox pattern via `sync_outbox` table and `OutboxService`: atomic local database transaction enqueue, bounded retries (default max 5) with exponential backoff, dead-letter status, manual retry, and property isolation. Evidence: `OutboxSyncTest` 7 tests/25 assertions; full suite 392/1639; Pint, PHPStan pass. SQLite only.
- [x] EXT-001-T01 Resolve OTA/payment/guest connector release priority, providers and commercial contracts. Applied provisional disabled-by-default policy per D-009: NullOtaAdapter acts as a safe fallback when no active commercial contract exists, ensuring local PMS core never blocks.
- [x] EXT-001-T02 Implement provider adapter contract/sandbox tests after approval; maintain core outage independence. Implemented `OtaChannelAdapter` and `PaymentGatewayAdapter` contracts. Built `NullOtaAdapter`, `SimulatorPaymentGateway` (with WAN-down offline mode that safely queues requests to `OutboxService`), and `PaymentWebhookHandler` (reconciling external payment webhooks idempotently into `FolioService`). Evidence: `ExternalConnectorsTest` 6 tests/22 assertions; full suite 398/1661; Pint, PHPStan pass. SQLite only.
- [ ] REL-001-T01 Resolve entitlement/grace, signer/key custody, Flux commercial entitlement. BLOCKED:D-008.
- [ ] REL-001-T02 Build signed artifact, SBOM, verify-before-install, staged migration and recovery on target Windows.
- [ ] QA-001-T01 Execute clean install, E2E, offline, security, restore and upgrade acceptance on selected pilot.
- [ ] QA-001-T02 Final independent audit reports PASS/FAIL/BLOCKED with residual gaps and evidence.
