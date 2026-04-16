# Rules for AI Agents

## Code style guidelines

### software engineering practise

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
  The code base has grown organically and accumlated a lot of tech debt.
  You don't need permission to restructure code in a meaningful way.
  If completing a task cleanly requires reshaping what's already there, that's part of the task.

- Complexity budget should always be net zero or negative.
  After your change, the codebase should have the same or fewer total abstractions, files, and code paths than before.

- Follow the Scout Rule in software engineering.
  "Always leave the campground cleaner than you found it".
  Make small, incremental improvements to the codebase every time you touch it.

### generic code style

Apply the following to all code

- Do not write organizational comments that summarize the code. Comments should only be written in order to explain "why" the code is written in some way in the case there is a reason that is tricky / non-obvious.
- Use full words for variable names (no abbreviations like "q" for "queue").
- Explicit is Better Than Implicit.
- Encourage a low level of code nesting. Return early, use appropriate data structures instead.

### Backend guidelines

Apply the following to backend related code

- When implementing async operations that may fail, ensure errors propagate to the UI layer so users get meaningful feedback.

### Frontend guidelines

Apply the following to backend related code

### Language specific requirements

Apply the following to all code of the corresponding language

#### Markdown

- One sentence per line.
  After `.` should be a new line.

#### CSS

- Avoid `!important`

#### PHP

- Discourage `$request->input(...)` prefer to use the explicit `$request->integer(...)`

## Known tech debt

- Vue2 is long deprecated

## Rules Hygiene

These `.rules` files are read by every agent session. Keep them high-signal.

### After any agentic session

If you discover a non-obvious pattern that would help future sessions, include a **"Suggested .rules additions"** heading in your PR description with the proposed text.
Do **not** edit `.rules` inline during normal feature/fix work.

### High bar for new rules

Editing or clarifying existing rules is always welcome.
New rules must meet **all three** criteria:

1. **Non-obvious** — someone familiar with the codebase would still get it wrong without the rule.
2. **Repeatedly encountered** — it came up more than once (multiple hits in one session counts).
3. **Specific enough to act on** — a concrete instruction, not a vague principle.

### What NOT to put in `.rules`

Avoid architectural descriptions and explanation of code.
These go stale fast and the agent can gather them by reading the code.
Rules should be **traps to avoid**, not **maps to follow**.

### No drive-by additions

Rules emerge from validated patterns, not one-off observations.
The workflow is:

1. Agent or human reviewer notes a pattern during a session.
2. Team validates the pattern in code review.
3. A dedicated commit adds the rule with context on _why_ it exists.
