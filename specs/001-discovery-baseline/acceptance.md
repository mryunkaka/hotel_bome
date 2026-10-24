# Acceptance Matrix — DISC-001

| ID | Criterion | Evidence | Status |
|---|---|---|---|
| DISC-001-AC01 | VPS branch/base/status match handoff and worktree is clean | Git status and SHA output | NOT RUN |
| DISC-001-AC02 | Composite baseline check has recorded exact command and exit | Sanitized command output | NOT RUN |
| DISC-001-AC03 | Only baseline evidence file is changed/committed | Git diff and commit file list | NOT RUN |
| DISC-001-AC04 | Any failure is reported as blocker, with no app changes | Git diff + test result | NOT RUN |

No PASS until evidence is returned by Hermes and independently checked.
