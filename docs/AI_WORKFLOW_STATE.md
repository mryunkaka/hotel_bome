# Workflow state and Hermes handoff

## Active handoff override — 2026-10-18

- Owner requested autonomous planning/research/implementation/testing and periodic pushes; see `docs/HERMES_AUTONOMOUS_BUILD.md` for the complete execution mandate. This supersedes the baseline-only handoff below on the integration branch.
- Integration branch: `work/hermes-full-build`, starting at `ca295269766b482df89b78358a3c154a3f159c3d` plus the autonomous mandate commit. Use the actual current remote tip and record every verified checkpoint; do not require HEAD to stay at the starting SHA.
- Checkout for current unattended VPS run: `/home/saidadam28/workspace/hotel_bome_workflow`. Preserve pre-existing baseline evidence, Flux Pro archive and `tmp/`.
- Latest verified slice: branch `work/hermes-full-build`, HEAD `220e61d266758da313c043953f6cd1bad31c6f24` matches `origin/work/hermes-full-build` (verified with `git ls-remote`); PHP 8.4.26; runtime paths owned by `saidadam28`. Preserved baseline evidence, Flux archive and `tmp/`.
- Current run: `composer test` exit 0; Pint PASS, PHPStan 0 errors, PHPUnit 36/36 tests and 90 assertions PASS. Composer emits PHP 8.4 deprecation notices from system Composer dependencies. `bootstrap/cache`, `storage/framework`, `storage/logs` ownership/modes checked; all owned by `saidadam28`, writable mode 775. No `.env` contents read.
- Read OPS-001 and IAM-001 active artifacts. No implementation task presently marked READY: OPS-001 T01 needs Windows/runtime/signing/pilot decisions; IAM-001 T01 needs role/approval transitions, and T02-T06 depend on it. Remaining feature queues are explicitly gated by D-004..D-010 or per-feature SOP/pilot. Do not create code slice until readiness artifacts identify one.
- Existing IAM T03 login fix remains verified; full suite still green. Next action: owner/requirements planning must make a bounded task READY (e.g. role matrix / bootstrap policy); continue with independent READY work after task artifacts are updated.
- IAM pending login regression addressed: Fortify authentication rejects non-active accounts; registration now uses `App\\Http\\Controllers\\Auth\\PendingRegisteredUserController` so pending registrations are stored without establishing a session. `routes/web.php` shadows Fortify POST endpoint; no vendor edits retained.
- `php artisan test --filter=RegistrationTest`: 4 tests/12 assertions PASS. Full `composer test`: Pint PASS, PHPStan 0 errors, PHPUnit 36/36 tests, 90 assertions PASS; Composer emits PHP 8.4 deprecation notices from system Composer libraries. `git diff --check` PASS before state update.
- IAM remains incomplete: admin approval/bootstrap UI, membership/tenant enforcement, and audit tasks not implemented. Next: inspect readiness and continue independent foundation slices. Windows/LAN/hardware/signing acceptance stays BLOCKED_EXTERNAL.
- Worktree also contains user-owned `docs/product/BASELINE_EVIDENCE.md` and `packages/flux-pro-2.20.1.zip`; never stage either. No commit/push recorded in this checkpoint.

## Repository and branches

- Local project: `D:\Project\hotel_new`
- Remote: `https://github.com/mryunkaka/hotel_bome.git`
- Existing workflow branch remains `chore/spec-kit-workflow` at base `a2ce0c13bd002679a1b9f08bc0c9135d2e10a7db`; `main` is untouched.
- Planning artifacts are pushed on `docs/pms-project-plan`; package commit: `69a7faac831a8014a792c695c326a82b66110369`.
- The active read-only task branch is `feat/disc-001-baseline`, based on the latest pushed tip of `docs/pms-project-plan`; the exact base SHA is recorded in the dispatch message.
- Future implementation must use separate feature/implementation branches from the latest approved planning SHA; never work on main and never force-push.

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
2. Confirm branch `feat/disc-001-baseline`, base SHA recorded in the task handoff, and clean worktree before any check.
3. Run only `composer test` for the baseline; do not change app code, dependencies, workflows, `.specify/integration.json`, or other feature artifacts.
4. Update only `docs/product/BASELINE_EVIDENCE.md` with actual test command, environment, exit code, counts, sanitized result and any blocker. Never include secrets.
5. Review and commit only that file on `feat/disc-001-baseline`; push only that task branch. Report exact SHA and stop because implementation tasks remain gated.

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
