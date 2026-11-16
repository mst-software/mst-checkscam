---
name: git-workflow
description: How to create branches, commit and open PRs in the MST CheckScam repo. Use for EVERY task that changes files (feature, fix, refactor, docs, chore) before the first edit and at every commit.
---

# Git workflow

Full team convention: `docs/convention-git.md`. Commit message format: use the `writing-commit-messages` skill (commitlint + Husky enforce it).

## 1. One task = one branch

```
git checkout develop && git pull origin develop
git checkout -b <prefix>/<short-kebab-description>
```

- Branch from `develop`, never from `main`. Never commit on `develop` or `main`.
- Prefixes: `feature/`, `bugfix/`, `hotfix/` (from `main`), `refactor/`, `docs/`, `test/`, `style/`, `ui/`, `chore/`.
- Lowercase, hyphenated, describe the component: `bugfix/footer-login-link`, not `fix-bug`. No personal names. One task per branch.

## 2. Commit as small as possible

- One logical change per commit (migration, model, one controller action, one fix).
- Stage explicitly (`git add <files>`); check `git status`, `git diff --staged`, `git log --oneline -10`.
- Renames/moves in their own commit.
- Run `vendor/bin/pint --dirty` and the relevant tests before committing code.
- Message: `type(scope): subject`, one line, lowercase (see `writing-commit-messages`).

## 3. Pull requests

- One PR per branch, opened when the task is done; target `develop`. Leader reviews before merge.
- Push with `git push -u origin HEAD`; no force-push on shared branches.
- Body: short bullets (what, why, how verified, what was not verified). For UI changes, check desktop and mobile width in a browser first.
- Do not merge unless the user says so. Never merge locally into `develop` or `main`; use `gh pr create` / `gh pr merge`.
- Delete the branch after merge.

## 4. Never commit

`.env*`, `vendor/`, `node_modules/`, `public/build`, `public/uploads`, `storage` contents, `.playwright-cli/` output, DB dumps, debug screenshots, personal paths, IPs or keys.

## Checklist before every commit

1. On a task branch.
2. Only this change's files are staged.
3. Message passes commitlint.
4. Pint clean, relevant tests pass.
5. No secrets or personal data in the diff.
