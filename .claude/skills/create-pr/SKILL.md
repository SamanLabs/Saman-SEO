---
name: create-pr
description: Generate a pull request title and description from the current branch's commits. Produces a concise summary, optional feature highlights, and collapsible technical details.
---

# Create PR Description

Generate a pull request title and description that's scannable, informative, and has just enough personality to feel human.

## Instructions

### 1. Gather context (do ALL of these)

Run these commands to build a complete picture before writing anything:

```bash
# Commit overview
git log main..HEAD --oneline --stat

# Full diff stat for file-level scope
git diff main..HEAD --stat

# Actual code changes — read the diff, don't just skim filenames
git diff main..HEAD
```

If the full diff is too large, diff individual areas (backend routes, frontend, storage, etc.) in batches. You must understand **what the code actually does**, not just which files were touched.

### 2. Write the PR file

Write the file to `.pr/YYYY-MM-DD.md` (using today's date). Create the `.pr/` directory if it doesn't exist. If a file for today's date already exists, append a counter: `YYYY-MM-DD-2.md`, `YYYY-MM-DD-3.md`, etc.

The structure depends on whether the PR introduces user-facing features or is purely internal (refactors, bug fixes, infra).

#### When the PR has user-facing features:

~~~markdown
# <Title>

<4-6 sentence summary>

### Highlights

- Highlight 1
- Highlight 2
- ...

<details>
<summary>Technical changes</summary>

- Detail 1
- Detail 2
- ...

</details>
~~~

#### When the PR is purely internal (no user-facing features):

~~~markdown
# <Title>

<4-6 sentence summary>

<details>
<summary>Technical changes</summary>

- Detail 1
- Detail 2
- ...

</details>
~~~

Omit the Highlights section entirely for internal-only PRs — don't force it.

### Style Rules

#### Title
- **Follow the repo's conventional-commit style:** `type(scope): summary` — e.g. `feat(agent): ...`, `fix(deps): ...`, `ci: ...`, `refactor(api): ...`, `test: ...`. Match the types and scopes already in `git log`; don't invent a new convention.
- Pick the `type` that covers the **dominant** change across the whole PR, not just one commit. Mixed PRs lean on the most user-significant change (a feature wins over the chores that came with it).
- Lowercase summary, imperative mood, no trailing period. Keep it under ~70 chars.
- The CI version-bump keys off commit/title prefixes, so the `type` is functional, not cosmetic — `feat`/`fix` carry release weight; `ci`/`docs`/`test`/`chore` do not.
- The `# <Title>` line at the top of the generated file **is** the PR title — when the PR is eventually opened, that same string is the `gh pr create --title` value. Don't write a second, different headline.

#### Summary
- **4-6 sentences.** This is the part people actually read — give it room to breathe.
- **Open with a touch of personality.** One line that makes the reader smile — a wry observation, a lighthearted remark, a playful metaphor. Not forced, just human. Examples of the energy (don't copy these literally, invent your own each time):
  - "This one's mostly about cleaning house."
  - "Turns out the type checker was right to complain."
  - A playful metaphor about what the code was doing wrong
  - A wry observation about the state of things before this PR
- **Match the tone to the change.** The voice should fit what the PR actually is — a bug fix can read dry and a little relieved ("This should've been caught months ago."), a new feature can read genuinely excited, a refactor can read like satisfying cleanup, a security fix should stay sober and matter-of-fact. Don't paste the same energy onto every PR; that's just a different kind of static.
- **Then explain what was going on and what this PR does about it.** Set the scene — what was broken, missing, or annoying? What's the approach? Name the main change areas (new feature, refactor target, bug fixed) but describe them in context, not as a dry list. The reader should walk away understanding the *story* of this PR, not just a changelog.
- **Include the "why" and the reasoning.** If there was a design choice, a trade-off, or a particular reason you went one way instead of another, mention it briefly. "We went with X instead of Y because Z" is the kind of thing that saves people from asking in review.
- **Do not repeat what Highlights or Technical changes already cover** verbatim, but it's fine to reference the same areas — the summary gives narrative context, the sections below give specifics.

#### Highlights (only when applicable)
- One bullet per user-facing feature, behavior change, or notable improvement.
- Write from the user's perspective — what they'll notice, not internal implementation.
- Plain language, no code references. "Schedules now respect your configured timezone" not "`SchedulerService` gains a `timezone` attribute".
- 3-7 bullets is the sweet spot. If you can only think of 1-2, fold them into the summary and skip this section.

#### Technical changes (inside the accordion)
- One bullet per discrete change. Be specific — name files, classes, functions, patterns.
- Format: `backtick code references` for identifiers, plain text for descriptions.
- Every meaningful change in the diff must have a bullet. If a change touches security (CORS, auth, SQL injection), error handling, accessibility, or concurrency, it gets its own bullet — do not bury these.
- Bullets should describe the mechanism, not just the intent. "Race condition in `get_or_create_chat` fixed by moving creation inside the lookup session" is good. "Fix database issues" is not.
- Group related changes together (all typing fixes, all security hardening, all API changes, etc.)

#### Contributors
- If the PR includes commits from multiple authors (not just the repo owner), add a **Contributors** section after the summary and before Highlights.
- Use `git log main..HEAD --format='%aN <%aE>' | sort -u` to find unique commit authors.
- Exclude bot accounts (e.g., `github-actions[bot]`).
- Format: `@username` if their GitHub handle is available (check the ARGUMENTS or commit metadata), otherwise use their name. Add a brief note about what they contributed if it's clear from the commits.
- Keep it short — one line per contributor, no need for a full changelog.

#### General
- **No test plan section.** Do not include "Test plan" or "Testing".
- **No mention of tests.** Do not reference test files, test results, or testing.
- **No emoji.**
- **No "Generated by" footer.**

### 3. Stop after writing the file

This skill's job ends when the `.pr/` file is written. **Do not** run `gh pr create`, `git push`, or any other remote-affecting command to actually open the PR — that's a separate, explicit step the user requests on its own. Print the path of the file you wrote and a one-line note that it's ready to review/copy.
