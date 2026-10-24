# Deployment & Operations Plan

This plan states acceptance requirements, not a claim that a production installer/runtime exists. Target OS family is Windows only (owner confirmed); supported Windows versions/editions, architecture, runtime and service wrapper remain open.

## Target shape

- Windows server located at hotel; staff use local browser on server or approved hotel LAN (D-002 pending).
- Bundle PHP/runtime, static assets and all app dependencies offline; hotel staff should not install developer tools.
- App service and local MySQL use least-privilege identities; MySQL binds loopback, firewall permits only the approved web path.
- Use local fonts/scripts/assets; no CDN, external analytics or WAN dependency in login/front desk/reporting.
- FrankenPHP + WinSW is only a candidate until tested against target Windows, PHP extensions, printer/PDF and service recovery.
- Runtime preflight must reconcile `composer.json` PHP `^8.4.1` (lockfile minimum) with config/package APIs; `pdo_sqlite` is needed for default SQLite, and `pdo_mysql` for MySQL. No Windows extension bundle is selected.
- Web routing must be verified for selected Windows server: current `public/.htaccess` covers Apache rewrite; no tracked IIS `web.config` is present. Do not assume IIS URL Rewrite configuration exists.
- Email is optional to core operation; if deployment selects `MAIL_MAILER=sendmail`, configure a Windows-compatible executable or select SMTP. Current default sendmail path is Unix-specific.
- Current test/build evidence comes from Linux with PHP 8.4.26 and SQLite, not a Windows host or Windows filesystem/service manager.

## Installation and first run

1. Verify signed package, checksum, supported OS, disk, permissions and prerequisites before touching data.
2. Install runtime/service and database as separate managed components; generate unique secrets locally.
3. Create first admin via the approved bootstrap policy; ensure bootstrap is disabled after completion.
4. Set property, timezone, currency, LAN binding and initial room/rate settings only after owner-confirmed decisions.
5. Health check confirms app/DB/storage/schema without exposing credentials; operator gets clear remediation steps.
6. Install with WAN unplugged, reboot, and perform a local login and safe test workflow.

### Account and property access (provisional technical procedure)

- Create the initial account only through the local, one-time first-admin setup. Never promote the test seeder or share a generic staff account.
- Keep `platform_admin` for installation-wide technical support and account administration; do not use it as a routine front-desk account.
- Before approving a staff account, verify the person and active property, assign only the documented provisional role, provide a nonblank reason, and confirm the immutable audit entry appears.
- Review property and role before granting access. Current application routes grant operational access only to explicitly supported `housekeeping` and `operations` memberships; other role memberships do not imply permissions.
- On role change or departure, change/revoke membership or suspend the account with reason. Confirm the user is denied on the next protected request. Revoke changes rotate remember-me tokens; existing server-side session records may remain until normal expiry.
- Never approve self, bypass pending status, or assume this provisional procedure is hotel-approved SOP. Owner/property-manager review and Windows/browser pilot remain required.

## Backup and restore

- Encrypted backup covers DB, required attachments/config/license recovery metadata and exact app/schema version.
- Never include Git credentials/master secrets. Store recovery key out-of-band according to owner procedure.
- Run integrity checks and restore to separate machine/database; verify counts, balances, snapshots and ability to sign in.
- Show last successful backup, age, target capacity and actionable failure; alert before storage exhaustion.

## Update and rollback

- Build reproducibly from locked source; record SBOM, checksum, signer and supported schema range.
- Verify signature before unpacking; preflight disk/version/service state; take and verify backup.
- Use staged release directory, additive restart-aware migration, health check, then switch active release.
- If code health fails, restore previous code and report DB recovery requirement. Never assume DDL rollback is atomic.
- Provide signed offline USB update path with the same verification and evidence.

## Operations and network

- Core use is tested with WAN and DNS unavailable. Optional connector queues bounded work and reports queued/failed/reconcile state.
- If LAN is approved, bind only to approved interface/subnet; MySQL/diagnostic endpoints remain private; verify guest/WAN cannot access.
- Logs omit credentials, payment secrets and unnecessary PII; define retention, support bundle redaction and low-disk handling.

## Release gates

Install/reboot, offline login/write, E2E front desk, backup+second-machine restore, upgrade from each supported schema, failed-signature refusal, health diagnostics, and network exposure tests all require recorded evidence. No deployment is claimed until run on approved Windows target hardware.
