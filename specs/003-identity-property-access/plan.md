# Implementation Plan: Identity and Property Authorization

Feature ID: IAM-001 | Branch: feat/iam-001 | Spec: spec.md

## Technical Context

Laravel 13, Livewire 4, Flux Pro 2.20.1; existing Fortify auth/passkeys/two-factor remains foundation. Multi-property install and LAN confirmed. Additive migrations only; SQLite unit tests plus target MySQL/Windows checks when selected.

## Constitution Check

Least privilege, tenant isolation at every data path, audit sensitive actions, pending registration cannot authenticate, no production seed identity, no secrets in Git.

## Design phases

1. Define permission/role matrix, bootstrap trust boundary, session invalidation, and property membership model.
2. Define schema/FKs/unique constraints and test actor/property matrix.
3. Implement pending state, approval, assignment and policies without removing existing auth protections.
4. Test direct URL/Livewire/export/API/jobs, role changes, bootstrap replay, session revocation and regressions.

Readiness: design READY; implementation task becomes READY after exact role/approval policy and acceptance mapping are reviewed.
