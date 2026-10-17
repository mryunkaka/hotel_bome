# Implementation Plan: Accounting and Doorlock/RFID

Feature IDs: ACC-001, HW-001 | Branch: feat/accounting-hardware

Keep accounting and hardware as separate child implementations, security review and commits. Accounting requires approved chart/tax/period rules and balanced journal model. Hardware requires a vendor adapter contract, authenticated local agent, certificate/secret rotation, replay defense, idempotency, allow-list, strict binding and actual vendor SDK license review. Test fail-closed behavior, LAN isolation, offline decisions and audit. Never ship vendor SDK until redistribution terms are confirmed.
