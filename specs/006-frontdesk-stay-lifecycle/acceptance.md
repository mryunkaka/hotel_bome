# Acceptance Matrix — FO-001

| ID | Criterion | Verification | Status |
|---|---|---|---|
| FO-AC01 | Valid guest/reservation/ready-room check-in succeeds | Integration E2E | NOT RUN |
| FO-AC02 | Invalid room, property or role denied | IDOR/policy tests | NOT RUN |
| FO-AC03 | Room move/share preserves linked folio/history | Integration test | BLOCKED:D-005 |
| FO-AC04 | Checkout/outstanding exception follows approved rule | Role/state tests | BLOCKED:D-005 |
| FO-AC05 | Flow remains available with WAN disconnected | Target LAN/WAN pilot | NOT RUN |
