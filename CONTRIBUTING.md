# OSEA Contributors Guide

Welcome to the Open Source and Energy Access (OSEA) contributors' guide.
This guide covers how to make a meaningful contribution and what you can expect from maintainers in return.
OSEA prioritises and values its commitment to creating a safe and inclusive community where everyone can contribute and thrive.
It is therefore essential to ensure that everyone adheres to our [Code of Conduct](./CODE_OF_CONDUCT.md).

## Getting Started

Review the existing issues and the discussion thread to ensure your issue has not been previously addressed.
This document narrows down by providing resources to reduce time wastage and increase productivity.
Most documentation is currently written in Markdown, making it easy to add, modify, and delete content as necessary.

## Setting up your Environment

> ⚠️ **Heads up!** If you'd like to work on issues, please ensure you fork the repository, create a new branch, and work on this branch to avoid pushing your changes directly to the master/main branch.

Follow the steps outlined in the repo's ReadMe file for local development.

Create a new branch by typing this command:

```bash
git switch -c <branch-name>
```

Change the branch name to whatever you want. For example:

```bash
git switch -c update-fixture
```

## Creating a Pull Request

> ⚠️ Heads up! Please ensure that there is an existing issue first and that you've been assigned to it before opening a PR, unless your change is minor (e.g. fixing a typo).
> This allows the maintainer to provide guidance and prioritise tasks.
> We acknowledge everyone's contributions and strive for transparent communication.

### What makes a good PR?

Every good PR should contain one logical block of work. Unrelated changes should go into separate PRs.
The rule of thumb is: PR should be as small as possible, as large as necessary to form a meaningful, self-contained piece of work.
Smaller PRs are easier to review, faster to merge. If you find yourself wanting to fix a typo, refactor a helper, and add a feature on the same branch, split them; each change gets its own PR.

### Two ways to contribute

- **GitHub interface**- Fork the repo, edit directly in the browser, and submit a PR. This [guide](https://guides.github.com/activities/hello-world) shows just how to do that for a small personal repo.
- **Fork and Clone**- Fork, clone locally, and work in your IDE. Required for code changes and any project with an associated application. For that, we recommend this [guide](https://www.dataschool.io/how-to-contribute-on-github/).

### Commits and Pull Request Titles

We use GitHub Flow. Every PR is squash-merged into a single commit on main. This shapes how you should think about commits and PR titles.

- **Inside your PR**:commits do not need to follow any convention. Commit as often as you like, and use whatever messages help you track your own work. Frequent commits help reviewers follow your thinking.
- **PR title**: becomes the squashed commit message on main, so it must be clean and follow Conventional Commits.

## Conventional Commits

| Prefix    | When to Use                                                     |
| --------- | --------------------------------------------------------------- |
| chore:    | Miscellaneous commits, e.g., modifying .gitignore               |
| feat:     | Commits that add or remove a feature to the API or UI           |
| docs:     | Commits that affect documentation only                          |
| fix:      | Commits that fix a bug of a preceding feat commit               |
| refactor: | Rewrites/restructures code without changing API or UI behaviour |
| revert:   | Creates a new commit that undoes previous changes               |

## Waiting for Review

The waiting period plays a pivotal role in your contributor journey. We kindly ask that you be patient if maintainers do not review your pull request immediately, as it may take time to give every PR the attention it deserves.
If it has been over one week and you haven't received any acknowledgement, you can post a comment on your PR as a polite reminder.

## Using AI Tools as a Contributor

OSEA is a learning community. Contributing here is not just about shipping code or documentation; it is about building skills and understanding that serve the broader energy access ecosystem. This shapes how we think about AI tools.
AI coding assistants can genuinely help you learn and contribute more effectively. They can also produce plausible-looking work that neither you nor the maintainer fully understands, and reviewing that work takes real time. Our AI policy exists to protect both your learning and the maintainers' capacity.

> **Guiding principle**: _Al is your assistant, not your author. If you cannot explain it, do not submit_.

## What AI use is appropriate

Use AI tools to support your understanding, not to replace it. For Example:

- Using AI to explain unfamiliar syntax, functions, or patterns before you implement something yourself
- Asking AI to review code you have already written for edge cases or clarity
- Using AI to help you draft a test scaffold, which you then review, correct, and extend
- Asking AI to help you structure a bug report or feature proposal in your own words
- Using AI to improve the grammar or clarity of the documentation you have already written

Uses that cross the line:

- Asking AI to implement a feature and submitting the output with minor changes
- Using AI to generate documentation for code you do not understand
- Pasting a codebase into AI and asking "what should I change?" without attempting the problem yourself first
- Using AI to respond to review comments on your behalf

If you are unsure whether a specific use is appropriate, describe it in your PR, and the maintainer will let you know.

## Disclosure

Transparency about AI use is required whenever AI meaningfully shapes your contribution. For instance, when it drafted code, suggested architecture, rewrote documentation, or helped diagnose a complex issue. It is not required for single-line autocomplete, minor grammar fixes, or syntax lookups.

Add a note to your PR description:

> “_Cursor suggested the initial approach. I adapted it to fit our PAYGo data model, rewrote the core logic, and added all edge-case tests manually"_

Undisclosed AI use that becomes apparent during review is treated as a trust issue, not just a policy issue.

## Your responsibility as a contributor

You are fully responsible for everything you submit, regardless of how you wrote it.

- Be able to explain any block of code and how it fits the project. Maintainers may ask you to walk through your changes before a PR is merged.
- Review AI output critically: AI can produce plausible-looking but incorrect or insecure code.
- Never submit AI-assisted code without running the test suite locally.
- AI output often introduces inconsistent formatting, naming conventions, or unnecessary complexity. Match the style of the surrounding codebase.
- Own the edge cases: AI has no knowledge of low-connectivity environments, PAYGo loan structures, mini-grid constraints, or the specific conventions of this project.
- Verify generated tests: AI may assert incorrect values or miss domain-specific edge cases entirely. Every generated test needs your sign-off.
- Remove AI filler: phrases like "This function efficiently handles…" belong in marketing copy, not documentation. Be precise and direct.

## What contributors can expect from maintainers

Contribution is a two-way relationship. In return, you can expect the following from OSEA maintainers:

- If a week passes with no acknowledgement of your issue or PR, a polite reminder is welcome.
- Reviews focus on the work, not the contributor. Comments aim to teach, not to gatekeep.
- If a design decision in the codebase is not obvious, ask, and a maintainer will explain the reasoning behind it.
- If a PR is declined or significantly reshaped, you will receive a clear explanation of why.
- Contributions are publicly acknowledged, including documentation, bug reports, and thoughtful discussion, not just merged code.
- Maintainers are held to the same standards we hold contributors to.

If any of these expectations are not being met, you are welcome to raise them directly with the maintainer team or in our community channels.

## AI and the human review process

You are encouraged to use AI to review your own work before submitting as a quality step, for example, asking "What edge cases might this miss?" or "Is there a simpler way to write this?", but do not use it as a substitute for the human review process.
Maintainer reviews are about knowledge transfer, mentorship, and project coherence. They cannot be replicated by an AI tool that has no knowledge of OSEA's history, design decisions, or community values.
Issue discussions and PR conversations should remain human-to-human. Use AI to help you prepare your thoughts, but write in your own words and engage genuinely with the community.

## A note to new contributors

If you are new to Open Source, AI tools can feel like a shortcut past the uncomfortable parts of learning. We encourage you to sit with those uncomfortable parts. Reading unfamiliar code, debugging without a clear answer, and working through a reviewer's feedback are where real understanding develops.

Use AI to unblock yourself when you are genuinely stuck, not to avoid getting stuck in the first place. The stuck moments are where the learning happens.

If you are unsure where to start or how to approach a problem, ask in [Discord](https://community.oseas.org) or open a GitHub Discussion. We would rather help you find your footing than review a well-formatted PR that nobody fully understands.

## A note to reviewers

Maintainers are not expected to audit contributions for AI use. However, if a PR shows signs of AI generation, inconsistent style, logic that does not match the surrounding codebase, or documentation that does not reflect the contributor's apparent understanding, reviewers may ask the contributor to walk through their changes before merging. Pull requests that deviate significantly from our established stylistic conventions or impose an undue review burden on the maintainer team may be declined for those reasons.
This is not punitive. It is part of how we ensure that contributions represent genuine understanding, and that every contributor walks away having learned something.

## Style Guide & Further Information

Coding style is enforced through automated checks on Pull Requests. [See the corresponding GitHub Actions](https://github.com/EnAccess/micropowermanager/blob/main/.github/workflows) for information about which standards are adhered to in different parts of the project's codebase.

Join the [Open Source in Energy Access (OSEA) community Discord](https://community.oseas.org) server to connect with like-minded people.
