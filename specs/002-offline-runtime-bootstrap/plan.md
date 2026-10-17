# Implementation Plan: Offline Runtime and First Run

**Feature ID**: OPS-001
**Branch**: feat/ops-001-offline-runtime-bootstrap
**Spec**: spec.md

## Summary

Package Laravel for local Windows operation, bootstrap first admin once, and limit app/database reachability to approved local hotel clients.

## Technical Context

PHP constraint ^8.3; Laravel ^13.17; Livewire ^4.1; Flux Pro 2.20.1 owner choice; local assets; local DB. Target OS/runtime and tested service wrapper are unresolved. Multiple LAN workstations are owner-confirmed; subnet policy is site-configured.

## Constitution Check

Local-first, no exposed diagnostics/secrets, least privilege, signed recoverable updates, no production destructive migration.

## Phases

1. Resolve supported Windows/runtime/service/signing/pilot decisions.
2. Prototype package and service on a disposable clean Windows machine.
3. Implement bootstrap lock and safe health endpoint.
4. Run offline install, reboot, LAN isolation, tamper and recovery acceptance.

## Risk

No production installer or Windows compatibility is verified. Do not claim deployment success from Linux/VPS tests.
