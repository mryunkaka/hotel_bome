# Feature Specification: Offline Runtime and First Run

**Feature ID**: OPS-001
**Created**: 2026-10-17
**Status**: Draft — blocked on target Windows/runtime validation

## Ringkasan

Install and initialize the local hotel server offline, create the first administrator through a one-time local wizard, and expose safe service health to authorized operators.

## User Scenarios & Testing

### User Story 1 — Install and create the first administrator (P1)

As the hotel owner, I want a signed offline installer and one-time local bootstrap so a new hotel can start without developer tools or WAN.

**Independent Test**: Install on a clean, supported Windows pilot machine with WAN disconnected; create first admin; reboot; verify local login and bootstrap lock.

**Acceptance Scenarios**:

1. Given a supported clean machine with no WAN, when the verified installer runs, then services and local assets install without developer tooling.
2. Given no admin exists, when the local bootstrap wizard completes, then an admin is created and the bootstrap path locks permanently.
3. Given the app is restarted, when an authorized operator checks health, then app/DB/storage/schema status appears without secrets.
4. Given access from a non-allowed network, when app/database/diagnostic ports are probed, then the firewall/bind policy denies access.

### Edge cases

- Tampered package, insufficient disk, interrupted install, existing partial install, DB startup failure, WAN/DNS loss, and bootstrap replay.

## Functional Requirements

- **FR-001**: Installer MUST verify signature, checksum and supported OS before writing application data.
- **FR-002**: Core app assets MUST be served locally without CDN/font/network dependency.
- **FR-003**: First-admin wizard MUST be usable only when no admin exists and lock after success.
- **FR-004**: Health output MUST omit passwords, tokens and sensitive configuration values.
- **FR-005**: Database MUST bind to loopback; web access MUST be limited to approved local/LAN interfaces.
- **FR-006**: Installation and service startup MUST be repeatable after reboot and provide actionable failure status.

## Key Entities

Installation, service, first-admin bootstrap state, health result, release package.

## Success Criteria

- **SC-001**: Clean installation and local login succeed with WAN disconnected.
- **SC-002**: Bootstrap cannot create a second initial admin after completion.
- **SC-003**: Reboot restores required services and health checks pass.
- **SC-004**: Unauthorized LAN/WAN client cannot access DB or diagnostic endpoint.

## Dependencies and readiness

Depends on DISC-001. **BLOCKED**: owner must select supported Windows version(s), installer/runtime/service, signing-key custody and provide a pilot target machine. FrankenPHP/WinSW remain candidates only.
