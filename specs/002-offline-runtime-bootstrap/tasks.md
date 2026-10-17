# Tasks: Offline Runtime and First Run

**Feature ID**: OPS-001
**Depends on**: DISC-001
**Readiness**: BLOCKED — supported Windows/runtime/signing decisions and pilot machine required.

- [ ] **OPS-001-T01** Resolve supported OS, local runtime/service wrapper, signing-key custody and pilot machine. BLOCKED until owner provides choices/resources.
- [ ] **OPS-001-T02** Prototype offline bundle on disposable target machine; inventory extension/service/asset compatibility. Depends on T01.
- [ ] **OPS-001-T03** Implement one-time bootstrap state and secure health status. Depends on T01-T02.
- [ ] **OPS-001-T04** Configure least-privilege service, loopback DB and approved LAN firewall. Depends on T01-T02.
- [ ] **OPS-001-T05** Test install/reboot/WAN-down/bootstrap replay/network denial/tampered package. Depends on T03-T04.
- [ ] **OPS-001-T06** Record verified operator installation and recovery runbook. Depends on T05.

Do not implement or claim target Windows compatibility until T01 is resolved.
