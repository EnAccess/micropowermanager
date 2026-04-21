# Rules for AI Agents

## Project Overview

MicroPowerManager (MPM) is a decentralized utility and customer management platform for mini-grids and renewable energy distribution.
It manages customers, revenues, devices (smart meters, solar home systems), and payments.

## Repository Structure

Monorepo with Docker-based services:

- `src/backend/` — Laravel 12 (PHP 8.2) REST API. See [`src/backend/AGENTS.md`](src/backend/AGENTS.md).
- `src/frontend/` — Vue.js 2 SPA (Vue CLI, Node 18). See [`src/frontend/AGENTS.md`](src/frontend/AGENTS.md).
- `docker/` — Dockerfiles for all services.
- `dev/` — Development environment config files.

When working inside `src/backend/` or `src/frontend/`, read the nested `AGENTS.md` first — it contains the conventions, patterns, and commands specific to that subtree.

## Development Environment

Start all services with Docker Compose from the repo root:

```bash
docker compose up
```

| Service          | Port  |
| ---------------- | ----- |
| backend          | 8000  |
| frontend         | 8001  |
| mysql            | 3306  |
| mysql_testing    | 53306 |
| redis, scheduler, queue-worker | — |

Backend source is mounted at `/var/www/html` inside the container.
Exec into the backend container with:

```bash
docker compose exec backend-dev bash
```

## Working Agreement

- Always use Plan Mode first, never go straight to implementation.
  Write the plan before touching any code.
  If something goes wrong mid-task, stop and re-plan.
  Never push through.
- Offload complex work to sub-agents, keep the main context clean.
- Never mark a task as complete without verifying it works.
  Run tests, check logs.
  Ask yourself: would a staff engineer approve this?
- For non-trivial changes, pause and ask if there is a more elegant solution.
  If a fix feels hacky, rebuild it properly.

## Code style guidelines

### Software engineering practise

Apply the following guidelines to all code:

- Prioritize code correctness and clarity.
  Speed and efficiency are secondary priorities unless otherwise specified.

- Prefer modification over addition.
  Implementing functionality in existing files unless it is a new logical component.
  Do not add a new parallel implementation instead generalize, extend, or refactor existing code.
  Avoid creative additions unless explicitly requested.

- Merge, don't layer.
  If you find duplicated or near-duplicated logic, consolidate it.
  Extract shared behavior into a common function, base class, contract or utility.

- Delete fearlessly.
  If your change makes existing code obsolete, remove it.
  Dead code should always be deleted.

- Refactor as part of the work, not as a separate step.
  The code base has grown organically and accumulated a lot of tech debt.
  You don't need permission to restructure code in a meaningful way.
  If completing a task cleanly requires reshaping what's already there, that's part of the task.

- Complexity budget should always be net zero or negative.
  After your change, the codebase should have the same or fewer total abstractions, files, and code paths than before.

- Follow the Scout Rule in software engineering.
  "Always leave the campground cleaner than you found it".
  Make small, incremental improvements to the codebase every time you touch it.

### Generic code style

Apply the following to all code:

- Do not write organizational comments that summarize the code.
  Comments should only be written in order to explain "why" the code is written in some way in the case there is a reason that is tricky / non-obvious.
- Use full words for variable names (no abbreviations like "q" for "queue").
- Explicit is better than implicit.
- Encourage a low level of code nesting.
  Return early, use appropriate data structures instead.

### Backend guidelines

Apply the following to backend related code:

- When implementing async operations that may fail, ensure errors propagate to the UI layer so users get meaningful feedback.

### Language specific requirements

#### Markdown

- One sentence per line.
  After `.` should be a new line.

## Known tech debt

- Vue 2 is long deprecated.

## Rules Hygiene

These agent files come in two flavors — keep them separate:

- **Rules sections** (this file's *Working Agreement*, *Code style guidelines*, and per-subtree *Conventions*) are **traps to avoid** — non-obvious, repeatedly encountered, specific-enough-to-act-on.
- **Awareness sections** (*Project Overview*, *Repository Structure*, *Common Patterns*) are **maps to follow** — concise orientation for a fresh session.

Rules go stale slowly; maps go stale fast. Keep maps short and link to the code; resist the urge to describe what the reader can learn by opening the file.

### After any agentic session

If you discover a non-obvious pattern that would help future sessions, include a **"Suggested AGENTS.md additions"** heading in your PR description with the proposed text.
Do **not** edit `AGENTS.md` inline during normal feature/fix work.

### High bar for new rules

Editing or clarifying existing rules is always welcome.
New rules must meet **all three** criteria:

1. **Non-obvious** — someone familiar with the codebase would still get it wrong without the rule.
2. **Repeatedly encountered** — it came up more than once (multiple hits in one session counts).
3. **Specific enough to act on** — a concrete instruction, not a vague principle.

### No drive-by additions

Rules emerge from validated patterns, not one-off observations.
The workflow is:

1. Agent or human reviewer notes a pattern during a session.
2. Team validates the pattern in code review.
3. A dedicated commit adds the rule with context on *why* it exists.
