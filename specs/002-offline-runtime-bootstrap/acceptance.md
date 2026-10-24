# Acceptance Matrix — OPS-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| OPS-001-AC01 | Clean offline install and local login | Supported Windows pilot | BLOCKED: Windows release/runtime/service wrapper and pilot machine not selected |
| OPS-001-AC02 | First-admin wizard is one-use and inaccessible over LAN | Automated loopback/LAN/header spoof/replay tests | PASS (local SQLite, 6 `FirstAdminBootstrapTest` cases: peer guard, forwarded header rejection, validation, one-use/replay, login) |
| OPS-001-AC03 | Service survives reboot and health is secret-free | `HealthControllerTest` plus pilot reboot/output review | PARTIAL PASS: SQLite endpoint tests 4 cases/17 assertions incl DB-up/schema-missing generic 503; reboot/service manager awaits Windows pilot |
| OPS-001-AC04 | DB/diagnostics protected from unauthorized network | Firewall/bind scan from allowed and denied segments | BLOCKED: LAN pilot |
| OPS-001-AC05 | Tampered package rejected before install | Signature/checksum negative test | BLOCKED: signer/package |
