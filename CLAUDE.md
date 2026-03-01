# MongoDumper

A PHP class for dumping MongoDB databases to disk. It wraps `mongodump`, zips the output, and cleans up the uncompressed dump folder.

## Codebase

- `mongodumper.php` — Single class `MongoDumper`. Entry point is `run($database, $debug = false)`.
- `readme.md` — Usage examples.

## Conventions

- PHP 5+ compatible (avoid PHP 8-only syntax unless upgrading the whole file)
- Shell commands executed via `shell_exec()`; avoid adding new native PHP extensions
- Keep the class API stable — `new MongoDumper($backupFolder)` and `run($db, $debug)`

---

## MXWL Agent Workflow

You are running headlessly as part of an automated mxwl agent pipeline. You will not receive any interactive input. Complete your work autonomously and write results before exiting.

### Context available via environment variables

| Variable | Description |
|---|---|
| `MXWL_WORK_ITEM_TITLE` | Title of the work item you are processing |
| `MXWL_WORK_ITEM_NUMBER` | Item number (e.g. `3`) |
| `MXWL_PROJECT_KEY` | Project key (e.g. `AIPL`) |
| `MXWL_RUN_ID` | Unique ID for this agent run |
| `MXWL_MXWL_DIR` | Absolute path to the `.mxwl/` directory |
| `MXWL_PRIMARY_REPO` | Absolute path to the cloned repository root |

Read env vars with `process.env.MXWL_WORK_ITEM_TITLE` (Node) or `$MXWL_WORK_ITEM_TITLE` (bash) or `getenv('MXWL_WORK_ITEM_TITLE')` (PHP). Because opencode runs in the repo root, you can also just `cat` files relative to `.`.

### Artifact handoff between agents

Previous agent output lives in `.mxwl/work-item-artifacts/`:

| File | Written by | Read by |
|---|---|---|
| `pm-spec.md` | Product Manager | Development Architect, Developer |
| `tech-spec.md` | Development Architect | Developer, Code Reviewer |
| `review-notes.md` | Code Reviewer | Developer (on revision), QA |
| `qa-report.md` | QA Engineer | Product Reviewer |

Always read the artifacts that exist before starting your own work. Do not assume a file is present — check first and adapt if it's missing.

### CRITICAL: Writing results before you finish

**You MUST write `.mxwl/agent-results.json` (or `$MXWL_MXWL_DIR/agent-results.json`) before your session ends.** The pipeline halts if this file is missing or unparseable.

```json
{
  "$schema": "./agent-results.schema.json",
  "nextAgentName": "Developer",
  "newReviewLoopContent": "## Summary\n\nBrief markdown shown to the next reviewer.",
  "newTests": [],
  "updatedTestResults": {},
  "summary": "1–3 sentence description of what you accomplished this run."
}
```

**Field guide:**

- `nextAgentName` — Exact name of the next agent (must match a name in the project's agent config). Set to `""` to mark this run complete with no handoff.
- `newReviewLoopContent` — Markdown surfaced to reviewers/humans (PR description, change notes, etc.).
- `newTests` — Any new test cases you created: `{ id, name, status, durationMs?, error? }`.
- `updatedTestResults` — Map of `testId → TestCase` for tests you ran.
- `summary` — Short plain-text summary (1–3 sentences). Stored in the run history.

### Agent pipeline order (Basic template)

1. **Task Validator** → validates the task is clear and actionable
2. **Product Manager** → writes PM spec to `.mxwl/work-item-artifacts/pm-spec.md`
3. **Development Architect** → writes tech spec to `.mxwl/work-item-artifacts/tech-spec.md`
4. **Developer** → implements code changes; commits on a feature branch
5. **Code Reviewer** → reviews the diff; writes notes to `.mxwl/work-item-artifacts/review-notes.md`
6. **QA Engineer** → tests the implementation; writes report to `.mxwl/work-item-artifacts/qa-report.md`
