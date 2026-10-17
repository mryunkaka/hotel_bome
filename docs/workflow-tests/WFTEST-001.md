# WFTEST-001 — Hermes repository-context acceptance test

## Control values

- test_id: WFTEST-001
- expected_repository: mryunkaka/hotel_bome
- expected_branch: test/ai-workflow-e2e
- expected_base_commit: 54fec20f0b0624bb28ab1fb2b88971c851c3d120
- verification_nonce: verify_6894bd7987334295b1497fb80c645fd9
- required_output: docs/workflow-tests/results/WFTEST-001-result.md

## Executor task

Read this complete task file and the applicable repository instructions. Verify the repository root, remote, branch, current commit, base/history, working tree, Laravel/framework context, and instruction/context documents from the checked-out repository. Do not infer facts from the conversation. Cite the file paths or commands that support each reported fact.

In the output, report:

1. The test ID, repository, branch, current commit, and base commit.
2. The exact verification_nonce value printed above, copied from this file.
3. Which repository instruction files exist and apply at the working directory; distinguish absent root workflow files from scoped instructions.
4. Verified Laravel/framework facts with source paths.
5. Facts verified, facts not found, and claims requiring further verification as three separate lists.
6. The output path and the checks actually performed.

## Allowlist and acceptance criteria

You may create or modify exactly one repository file: docs/workflow-tests/results/WFTEST-001-result.md. Do not modify any other file. Do not change application code, dependencies, lockfiles, tests, migrations, routes, environment files, package contents, service configuration, or deployment configuration. Do not use secrets or credentials.

The output must be factual, concise, and supported by evidence. It must reproduce the nonce exactly, identify unknowns rather than guess, and state any checks that could not be performed. The task passes only if the output meets every numbered reporting requirement and only the allowlisted output file changes.

After verifying the output against these criteria and confirming the diff contains only that file, create a commit containing only that output file on this branch and push this branch to origin. Never commit or push to main. If authentication, branch state, conflicting instructions, or any safety condition blocks completion, do not change anything outside the allowlisted output and report the blocker.
