# Tasks: Discovery and Baseline Evidence

**Feature ID**: DISC-001
**Readiness**: READY — read-only baseline check on the existing VPS checkout.

- [ ] **DISC-001-T01** Verify VPS checkout branch, base SHA, status and remote sync; stop if dirty or divergent. No file changes.
- [ ] **DISC-001-T02** Run `composer test` using the current project dependencies and record exact exit status and sanitized summary.
- [ ] **DISC-001-T03** Write only `docs/product/BASELINE_EVIDENCE.md` with actual results and blockers.
- [ ] **DISC-001-T04** Review diff and commit only that evidence file on `docs/pms-project-plan`; never touch main or owner-local files.

## Stop conditions

If branch, base, clean worktree, dependency access, or remote sync differs from handoff, stop before write. If tests fail, preserve output, do not modify code, and report blocker. Do not push until the parent confirms the plan branch is present and the task branch is correct.
