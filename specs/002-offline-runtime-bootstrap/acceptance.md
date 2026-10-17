# Acceptance Matrix — OPS-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| OPS-001-AC01 | Clean offline install and local login | Supported Windows pilot | BLOCKED: target OS/runtime |
| OPS-001-AC02 | First-admin wizard is one-use | Automated + direct-request replay | NOT RUN |
| OPS-001-AC03 | Service survives reboot and health is secret-free | Pilot reboot and output review | NOT RUN |
| OPS-001-AC04 | DB/diagnostics protected from unauthorized network | Firewall/bind scan from allowed and denied segments | BLOCKED: LAN pilot |
| OPS-001-AC05 | Tampered package rejected before install | Signature/checksum negative test | BLOCKED: signer/package |
