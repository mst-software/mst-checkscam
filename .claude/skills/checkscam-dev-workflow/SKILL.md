---
name: checkscam-dev-workflow
description: Orchestrates adding or changing features in the MST CheckScam Laravel app: create a branch, laravel-builder implements with tests, qa-reviewer checks tests, style and the browser, fix findings, then commit. Use for any request to build, change, fix or review app code or UI, including follow-ups such as "redo", "update", "fix this part" or "add a feature". Do not use for read-only questions.
---

# CheckScam development workflow

**Mode:** sequential subagents (builder, then QA); no workflow script needed.

## Step 0: Detect context

- New request: start at step 1.
- Follow-up on work already on the task branch: skip step 1; give `laravel-builder` only the part to change.
- Only a re-check: go straight to step 3.

## Step 1: Prepare

Follow `git-workflow`: create `<prefix>/<short-description>` from an up-to-date `main`.

## Step 2: Build

Call `laravel-builder` (`Agent` with `subagent_type: "laravel-builder"`) with the goal, affected areas, acceptance criteria and user constraints. It returns changed files and test results.

## Step 3: Verify

Call `qa-reviewer` with the changed files and what to check. It runs tests and Pint, reviews the diff against `mst-laravel-conventions`, and checks the UI in a browser when pages changed.

## Step 4: Fix loop

Pass only "blocking" / "should fix" findings to `laravel-builder`, then re-run `qa-reviewer`. Max 2 rounds; then report remaining issues to the user.

## Step 5: Commit

Small commits per `git-workflow` and `writing-commit-messages`. Open a PR (target `main`) only when the user asks or the task is complete; state what was and was not verified.

## Error handling

- An agent fails: retry once with a clearer prompt; then tell the user what is missing.
- App or browser cannot start for QA: say so, and report which checks (tests, Pint, static review) did run.
