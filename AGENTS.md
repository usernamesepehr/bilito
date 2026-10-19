<laravel-boost-guidelines>
=== .ai/skills rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

## Auth (AUTH-001)

- Bearer auth under `/api/v1/auth`: register, login, refresh, logout, me (see `routes/api.php`).
- Access tokens: Sanctum, 30 min (`config/sanctum.php`). Refresh tokens: 64-char plain, SHA-256 hashed in `refresh_tokens`, 30 days, rotated on `/auth/refresh`; reuse of a rotated token revokes the whole `chain_id`.
- Layering: Controller (no logic) → FormRequest (normalizes phone/national_id) → `AuthService`/`TokenService` → repositories (only Eloquent). DTOs in `app/DataTransferObjects`.
- Phone `09xxxxxxxxx` via `App\Support\PhoneNormalizer` (Persian/Arabic digits, `+98`/`0098` prefixes). National id checksum via `App\Support\NationalId`.
- Errors use `{error: {code, message, details}}`; codes: `INVALID_CREDENTIALS`, `UNAUTHENTICATED`, `REFRESH_TOKEN_INVALID`, `TOO_MANY_REQUESTS`, `VALIDATION_FAILED`, `FORBIDDEN` (see `bootstrap/app.php` renderer).
- Rate limiters (`AppServiceProvider`): login 5/min per phone+IP, register 5/min per IP, refresh 10/min per IP, api 60/min per user/IP.
- `admin` middleware checks `users.is_admin` (boolean), not token abilities; 403 `FORBIDDEN` for non-admins.
- Login does not reveal whether a phone exists (dummy hash check); never log passwords/tokens.
