# OSEA Contributors Guide

Welcome to the Open Source and Energy Access (OSEA) contributors' guide.
We appreciate your consideration of contributing to this repository and celebrate all the contributors who add value to the community.
This document guides you through smooth paths to a meaningful contribution to the project. Following the guide implies respecting the maintainers and developers who manage and develop the projects.
In return, they will reciprocate by addressing your issue, assessing and helping you finalise your pull requests until they are merged. OSEA prioritizes and values its commitment to creating a safe and inclusive community where everyone can contribute and thrive.
It is therefore essential to ensure that everyone adheres to our [Code of Conduct](./CODE_OF_CONDUCT.md).

## Getting Started

Review the existing issues and the discussion thread to ensure your issue has not been previously addressed.
We have provided resources to reduce time wastage and increase productivity.
Most documentation is currently written in Markdown, making it easy to add, modify, and delete content as necessary.

## Issues

Issues are created [here](https://github.com/EnAccess/micropowermanager/issues/new/choose)

### How to Contribute to Issues

For any issue, there are fundamentally three ways an individual can contribute:

- **By opening the issue for discussion:** If you believe that you have found a new bug in this project, you should report it by creating a new issue in the [issue tracker](https://github.com/EnAccess/micropowermanager/issues).
- **By helping to triage the issue:** You can do this either by providing assistive details (a reproducible test case that demonstrates a bug) or by providing suggestions to address the issue.
- **By helping to resolve the issue:** This can be done by demonstrating that the issue is not a bug or is fixed, but more often, by opening a pull request that changes the source in a concrete and reviewable manner.

## Submitting a Bug Report

To submit a bug report:

- When opening a new issue in the [issue tracker](https://github.com/EnAccess/micropowermanager/issues/new/choose), users will be presented with a template that should be filled in.
- If you believe that you have found a bug in this project, please fill out the template to the best of your ability.
- The two most important pieces of information needed to evaluate the report are a description of the bug and a simple test case to recreate it. It is easier to fix a bug if it can be reproduced.
- See [How to create a Minimal, Complete, and Verifiable example](https://stackoverflow.com/help/mcve).

## Triaging a Bug Report

It's common for open issues to involve discussion. Some contributors may have differing opinions, including whether the behavior is a bug or a feature.
This discussion is part of the process and should be kept focused, helpful, and professional.

Terse responses that provide neither additional context nor supporting detail are not helpful or professional.
To many, such responses are annoying and unfriendly.

Contributors are encouraged to collaborate on solving issues and help one another make progress.
If you encounter an issue that you feel is invalid or that contains incorrect information, explain why you feel that way with additional supporting context, and be willing to be convinced that you may be wrong.
By doing so, we can often reach the correct outcome faster.

## Resolving a Bug Report

Most issues are resolved by opening a pull request.
The process for opening and reviewing a pull request is similar to that of opening and triaging issues, but carries with it a necessary review and approval workflow that ensures that the proposed changes meet the minimal quality and functional guidelines of this project.

## Languages

We accept issues in **any** language. When an issue is posted in a language besides English, it is acceptable and encouraged to post an English-translated copy as a reply.
Anyone may post the translated reply.
In most cases, a quick pass through translation software is sufficient.
Having the original text as well as the translation can help mitigate translation errors.

Responses to posted issues may or may not be in the original language.

## Setting up your Environment

⚠️ **Heads up!** If you'd like to work on issues, please ensure you fork the repository, create a new branch, and work on this branch to avoid pushing your changes directly to the master/main branch.

Follow the steps outlined in the repo's ReadMe file for local development.

Create a new branch by typing this command:

```bash
git switch -c <branch-name>
```

Change the branch name to whatever you want. For example:

```bash
git switch -c update-fixture
```

## Pull Requests

Pull Requests are the way concrete changes are made to the code, documentation, dependencies, and tools contained in this project's repository.

Prior to creating a Pull Request, it is recommended to familiarize yourself with how a project can be developed and tested locally.

This is usually documented in either a `DEVELOPMENT.md` file or the `docs` folder of the project.

### Creating a Pull Request

To create a pull request, please ensure that an existing issue exists and that you have been assigned to it, unless your change is minor, such as fixing a typo.
This allows the maintainer to provide guidance and prioritize tasks; otherwise, you may risk spending time on something that doesn't get accepted for various reasons.

We acknowledge everyone's contributions and strive for transparent communication.
We highly recommend clear communication before undertaking any work.
Upon issue assignment, two options are available to you:

1. Use the GitHub interface to fork the repo, make an edit right here in the GitHub client, and then submit a pull request (no code or terminals or IDE required).
   [This guide](https://guides.github.com/activities/hello-world/) shows just how to do that for a small personal repo. You would simply replace creating a new repository by navigating to this one and forking it instead.
   This is a great idea if you simply plan to add to or edit one of the Markdown files we use for documentation in this project.

2. Fork the repo, create a local copy of that fork, and work on your changes that way.
   This is also the only option if the project expands to include an associated application.
   For that, [we recommend this guide](https://www.dataschool.io/how-to-contribute-on-github).

## Conventional Commits

When creating a Pull Request, it is best practice to use **Conventional Commits** to describe the purpose of your changes:

- `chore:` Miscellaneous commits, e.g., modifying a file.
- `feat:` Commits that add or remove a feature.
- `docs:` Commits that affect documentation only.
- `fix:` Commits that fix a bug of a preceding feature commit.
- `refactor:` Commits that rewrite/restructure your code.
- `revert:` Creates a new commit that undoes the changes.

## Waiting for Review

Waiting plays a pivotal role in your contributor journey.
Please be patient if the maintainers do not review your pull request immediately after submission.
It might take time for one to be in the right condition to review all submitted pull requests.
We would rather not rush a response after someone has taken the time and effort to submit it.
If it has been over one week and you haven't received any acknowledgement, you can post a comment on your PR as a reminder.

The purpose of reviews is to create the best experience we can for our contributors.
Please be aware of the following:

1. Reviews are always respectful, acknowledging that everyone did the best possible job with the knowledge they had at the time.
2. Reviews discuss content, not the person who created it.
3. Reviews are constructive and start a conversation around feedback. Embrace the feedback!

If the pull request looks good, a maintainer will typically provide feedback and merge the request immediately.
Otherwise, they will let you know what questions they have or what needs to change before your work can be accepted.
Once it is, you'll see your changes on the master branch, and your open-source contribution will be complete!

### Style Guides

Coding style is enforced through automated checks on Pull Requests.
[See the corresponding GitHub Actions](.github/workflows/) for information about which standards are adhered to in different parts of the project's codebase.

### Further Information

Join the [**Open Source in Energy Access (OSEA)**](https://discord.osea-community.org/) community Discord server to connect with like-minded people.
