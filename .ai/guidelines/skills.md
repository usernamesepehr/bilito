# Skill Usage (MANDATORY)

Skills are not optional. Before starting any task, check the list of available skills and activate every skill whose domain matches the task. Do this before writing code, asking questions, or exploring files. If there is even a small chance a skill applies, load it. Never skip a matching skill because the task seems simple.

At the start of each task, state in one line which skills you are loading and why.

## Always (every task)

- `using-superpowers`: load first in every session; it defines how to find and use the other skills.
- `verification-before-completion`: before saying work is done, fixed, or passing, run the verification commands and confirm the output. No success claims without evidence.

## By phase

### Starting a feature or change
- `brainstorming`: before any new feature, behavior change, or ambiguous request. Explore intent and requirements before implementing.
- `writing-plans`: when the task has multiple steps or touches multiple files, write the plan before touching code.
- `executing-plans`: when a written plan exists, follow it step by step with review checkpoints.
- `using-git-worktrees`: when the work should be isolated from the current workspace.

### Designing and writing code
- `clean-ddd-hexagonal`: for any module, bounded context, entity, value object, repository, use case, or layering decision.
- `laravel-best-practices`: for any PHP/Laravel code you write or modify.
- `tailwindcss-development`: for any Tailwind or styling change in Blade/CSS/JS views.
- `test-driven-development`: when implementing a feature or bugfix, write the failing test first.
- `testing-best-practices`: whenever writing, changing, or reviewing tests.

### Debugging
- `systematic-debugging`: for any bug, test failure, or unexpected behavior, before proposing fixes. Find the root cause first.

### Parallel work
- `dispatching-parallel-agents`: when there are 2+ independent tasks with no shared state.
- `subagent-driven-development`: when executing a plan made of independent tasks in this session.

### Review and finishing
- `requesting-code-review`: after completing a feature or major change, before merging.
- `receiving-code-review`: when handling review feedback; verify each point technically before applying it.
- `finishing-a-development-branch`: when implementation is complete and tests pass, to decide how to integrate (merge, PR, cleanup).

### Deployment
- `deploying-to-cloud`: for Laravel Cloud deployment, environments, resources, CLI, or troubleshooting.

### Skill maintenance
- `writing-skills`: only when creating or editing skills.
- `diagnosing-superpowers`: only when a skill failed to trigger or behaved unexpectedly.

## Combined workflows

- New feature: `brainstorming` → `writing-plans` → `clean-ddd-hexagonal` + `laravel-best-practices` → `test-driven-development` + `testing-best-practices` → `verification-before-completion` → `requesting-code-review` → `finishing-a-development-branch`.
- Bug fix: `systematic-debugging` → `test-driven-development` → `verification-before-completion`.
- UI change: `tailwindcss-development` + `laravel-best-practices` → `verification-before-completion`.

## Rules

- Never rely on memory of a skill; load it fresh each task.
- If two skills conflict, follow the more specific one for the domain, and mention the conflict.
- If a skill listed above is unavailable, say so and continue with the closest alternative.