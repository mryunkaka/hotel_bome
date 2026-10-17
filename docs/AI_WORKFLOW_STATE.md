# Workflow state and Hermes handoff

Last updated: 2026-10-17

## Repository and branches

- Local project: `D:\Project\hotel_new`
- Remote: `https://github.com/mryunkaka/hotel_bome.git`
- Existing workflow branch remains `chore/spec-kit-workflow` at base `a2ce0c13bd002679a1b9f08bc0c9135d2e10a7db`; `main` is untouched.
- Planning artifacts are prepared on separate branch `docs/pms-project-plan`, based on the workflow branch. Record the final planning SHA after push.
- Hermes implementation must use a feature/implementation branch created from the pushed planning SHA; never work on main and never force-push.

## Owner decisions confirmed 2026-10-17

- One installation manages multiple hotels.
- Staff access the local server from multiple PCs over the hotel LAN; allowed subnet/firewall is site-configured and must be pilot-tested.
- Self-registration remains enabled, but new accounts remain pending and cannot log in until admin approval.
- First admin is created through a local one-time setup wizard; the wizard is locked permanently after successful creation.
- UI stack remains Laravel 13, Livewire 4, Tailwind CSS 4 and Flux Pro 2.20.1; do not use Filament.

## Planning package

- Product requirements, scope: `docs/product/PRD.md`
- Architecture/domain: `docs/product/ARCHITECTURE.md`, `docs/product/DOMAIN_MODEL.md`
- Roadmap and module/task IDs: `docs/product/ROADMAP.md`, `docs/product/FEATURE_REGISTER.md`, `docs/product/FEATURE_SPEC_MAP.md`
- Open business decisions: `docs/product/OPEN_DECISIONS.md`
- Master tests/deployment/DoD: `docs/product/TEST_PLAN.md`, `docs/product/DEPLOYMENT_PLAN.md`, `docs/product/DEFINITION_OF_DONE.md`
- Ten feature packages, each with spec/plan/tasks/acceptance: `specs/001-discovery-baseline/` through `specs/010-connectors-release-acceptance/`.
- No feature implementation has started in this planning package.

## Open gates

- D-004: tax, jurisdiction, currency, invoice numbering and rate rules (hotel owner/accountant).
- D-005: cancellation, no-show, deposit, room move/share, waitlist, overbooking, checkout exceptions (hotel SOP).
- D-006: lock/reader/card model, vendor SDK and redistribution rights; physical pilot device.
- D-007: guest PII fields, consent, retention, export and permitted central sync.
- D-008: licensing/offline grace/signing key custody/support; Flux commercial entitlement for customer distribution.
- D-009: which connector/accounting/POS/OTA/QR/guest features are release-critical and provider choices.
- D-010: approval thresholds, tender/cash variances, invoice correction, close/reopen and reporting SOP.
- Windows target version/runtime/service/signing resources are not yet chosen or verified.

## Active task for Hermes

Only `DISC-001` read-only baseline evidence is READY. See `specs/001-discovery-baseline/tasks.md` and `acceptance.md`.

Handoff instructions:

1. Use checkout `/workspace/hotel_bome_workflow`; explicitly `cd` there. Use `/opt/data/.local/bin/specify` if CLI is needed. Hermes managed Spec Kit integration remains prohibited because `multi_install_safe: false`; use installed global skills and shared Bash adapters only.
2. Confirm branch `docs/pms-project-plan`, planning SHA recorded below, and clean worktree before any check.
3. Run only `composer test` for the baseline; do not change app code, dependencies, workflows, `.specify/integration.json`, or other feature artifacts.
4. Update only `docs/product/BASELINE_EVIDENCE.md` with actual test command, environment, exit code, counts, sanitized result and any blocker. Never include secrets.
5. Review and commit only that file to the planning branch if the branch is explicitly supplied by handoff; do not touch main. Report exact SHA and stop because implementation tasks remain gated.

## User-owned local changes to preserve

The Windows checkout has pre-existing uncommitted changes: `composer.lock`, `packages/composer.json`, deletion of `packages/flux-pro-2.20.0.zip`, untracked `packages/flux-pro-2.20.1.zip`, and `tmp/pdfs/`. Never stage, read archive contents, alter, or push these files. They are not present in the planning commit. The Flux artifact/license distribution path must be resolved before a dependency task.

## Hermes environment

- Hermes Agent 0.21.6; Spec Kit CLI 1.2.1 and uv 0.13.1 under persistent `/opt/data`.
- Project checkout: host `/home/saidadam28/workspace/hotel_bome_workflow`, container `/workspace/hotel_bome_workflow`.
- Ten Spec Kit skills active under `/opt/data/skills/speckit-*`; global Hermes managed integration must not be installed in project metadata.
- SSH host fingerprint was verified from the VPS console in earlier session. Use configured SSH key auth; never echo credentials.

## Actual verification state

- Initial repository check: branch `chore/spec-kit-workflow`, HEAD `a2ce0c1`, synced with `origin/chore/spec-kit-workflow` at check time.
- `specify check` local: PASS (CLI available; Codex CLI detected).
- `git diff --check`: run before planning commit; record result after validation.
- Application tests, static analysis, MySQL, Windows installer, LAN scan, hardware, backup/restore, update and final audit: NOT RUN. Do not report PASS.
- Hermes readiness/read-only acceptance test succeeded in earlier workflow session; this does not verify the new planning branch or application tests.

## Completion and audit protocol

Hermes may continue each child task only when its feature gate is READY, run and record tests, and commit/push only the explicitly handed feature branch. It may work through independent ready slices without asking Codex to review each ordinary task. It must stop at unresolved business/security/licensing/device/OS decisions or unavailable access. When Hermes reports completion/blocker, Codex fetches the exact branch, audits diff and acceptance evidence independently, reports PASS/FAIL/BLOCKED, and returns concrete defects to Hermes for correction. No final audit has been performed.
