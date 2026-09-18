<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.25
- filament/filament (FILAMENT) - v5.8.2
- laravel/framework (LARAVEL) - v13.32.0
- laravel/prompts (PROMPTS) - v0.3.24
- laravel/sanctum (SANCTUM) - v4.3.3
- livewire/livewire (LIVEWIRE) - v4.4.5
- laravel/mcp (MCP) - v1.0.0
- laravel/pint (PINT) - v1.32.1
- laravel/sail (SAIL) - v1.67.0
- phpunit/phpunit (PHPUNIT) - v13.3.4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use an available Laravel Boost command-discovery tool when you need to verify Artisan parameters. If that tool is not exposed, use `php artisan list` or `php artisan <command> --help` locally.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- Use Laravel Boost's `database-query` tool for read-only database inspection and its browser/runtime diagnostics for application errors.
- Use an exposed Tinker tool for direct Eloquent/PHP debugging when available; otherwise prefer focused tests or a narrowly scoped Artisan command. Do not add ad-hoc debug scripts.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Follow the existing enum convention: case names are generally uppercase (for example, `DRAFT`, `ADMIN`, and `IN_PROGRESS`) and backed values use lower snake case where applicable. Preserve existing case names because they are part of the application's API and persistence contract.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). Use an available Laravel Boost command-discovery tool when possible; otherwise use `php artisan list` or `php artisan <command> --help` locally.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to Artisan commands used by automation and provide all required options. Commands intended for an administrator, such as a fresh `app:install`, may remain interactive when run deliberately in a terminal.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Prefer Eloquent models and relationships for ordinary domain reads and writes. Use `DB::transaction`, `DB::table`, and carefully scoped raw expressions when they are appropriate for transactions, system tables/migrations, or complex aggregates.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Check the available options with a Laravel Boost command-discovery tool or locally with `php artisan make:model --help`.

### APIs & Eloquent Resources
- Use Eloquent API Resources and Form Requests for API boundaries. The current API intentionally uses unversioned `/api` routes; do not introduce `/v1` without an explicit API versioning decision.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables through configuration and `config()` in application services and business logic. Direct `env()` reads are allowed only where Laravel bootstrapping requires them, such as trusted host/proxy middleware configuration in `bootstrap/app.php`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v13 rules ===

## Laravel 13

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 13 Structure
- The streamlined Laravel 13 structure has no default application HTTP Kernel. Custom middleware may still live in `app/Http/Middleware/` and must be registered or aliased in `bootstrap/app.php`.
- `bootstrap/app.php` registers middleware, exceptions, routing, health checks, and other application bootstrap behavior.
- `bootstrap/providers.php` contains application specific service providers.
- **No `app/Console/Kernel.php`** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel supports limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`. Use this where it matches the query's intent and verify the behavior against the installed framework version when changing related loading logic.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` Artisan command when adding a class-based Livewire component. Check whether the feature belongs in the public Vue SPA or the Filament admin area first.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v4 rules ===

## Livewire 4

### Component And API Conventions
- Verify Livewire code against the v4 documentation before making assumptions based on older v2/v3 behavior.
- Use the `App\Livewire` namespace for new class-based Livewire components unless the existing codebase is intentionally using one of Livewire 4's newer component formats. Filament pages, resources, and widgets under `app/Filament/` are already Livewire-backed; the public application UI remains Vue 3 under `resources/js/`.
- Use `$this->dispatch()` for server-side event dispatching.
- When using `wire:model` modifiers, remember Livewire 4 changed some client-side sync timing semantics; prefer explicit modifiers such as `wire:model.live` when immediate synchronization is required.

### Newer Livewire 4 Capabilities
- Livewire 4 supports additional component formats, async actions, islands, and newer directives such as `wire:intersect`, `wire:ref`, and `wire:sort`.
- Use these capabilities only when they fit the existing codebase and verify exact syntax with the `search-docs` tool.

### Alpine And JavaScript
- Alpine is included with Livewire; do not manually add a separate Alpine bundle.
- Livewire 4 deprecates parts of the older JavaScript hook API in favor of interceptors. If you need custom client-side hooks or request interception, verify the current v4 pattern in the docs before implementing it.

### Infrastructure Notes
- Livewire 4 assets and endpoints now use a hash-based `/livewire-{hash}/...` prefix. If you touch middleware, proxies, or route customizations around Livewire endpoints, preserve that hashed path format.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== github-actions/core rules ===

## GitHub Actions CI

- Continuous Integration runs on pull requests, pushes to `master`, and manual `workflow_dispatch` runs. Change classification selects the relevant checks: backend changes run the frontend build plus PHP/Pint and MariaDB tests; frontend changes run the frontend build; infrastructure changes run Compose, the installer, container checks, and backup/restore checks; workflow changes run the full CI matrix.
- Changes limited to README/docs/agent metadata are currently unclassified, so component jobs are skipped and the gate accepts skipped jobs; Security Audit still runs its secret scan.
- Security Audit runs on pull requests, pushes to `master`, and weekly on Monday. Secret scanning runs on every trigger; dependency audits run for dependency/automation changes or weekly; SAST runs for source changes on `master` or weekly. The required merge checks are `CI gate` and `Security gate`, supplied by the active `Protect master` repository ruleset.
- The CI, Security Audit, and reusable Release Validation workflows are validation-only. The tag-triggered Release workflow publishes exact, minor, and `latest` GHCR images with provenance and an SBOM, validates the immutable digest through the complete Compose stack, builds/checksums both Docker deployment and Dockerless native bundles with release metadata, and creates GitHub release notes; it does not deploy the application.
- GitHub workflows use PHP 8.4 and Node.js 24; local frontend checks require PHP >= 8.4.1 and Node.js >= 22.18. Production images use PHP 8.5 and Node.js 26.
- Tags matching `v0.*.*` trigger release validation and publication; release tags must be based on `master`.
- Before handing off PHP, frontend, workflow, or infrastructure changes, run the applicable local equivalent of the CI checks. CI runs full Pint and fails if it produces a diff.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit <name>` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
</laravel-boost-guidelines>

<!-- BEGIN BEADS INTEGRATION v:1 profile:minimal hash:6cd5cc61 -->
## Beads Issue Tracker

This project uses **bd (beads)** for issue tracking. Run `bd prime` to see full workflow context and commands.

### Quick Reference

```bash
bd ready              # Find available work
bd show <id>          # View issue details
bd update <id> --claim  # Claim work
bd close <id>         # Complete work
```

### Rules

- Use `bd` for ALL task tracking — do NOT use TodoWrite, TaskCreate, or markdown TODO lists
- Run `bd prime` for detailed command reference and session close protocol
- Use `bd remember` for persistent knowledge — do NOT use MEMORY.md files

**Architecture in one line:** issues live in a local Dolt DB; sync uses `refs/dolt/data` on your git remote; `.beads/issues.jsonl` is a passive export. See https://github.com/gastownhall/beads/blob/main/docs/SYNC_CONCEPTS.md for details and anti-patterns.

## Agent Context Profiles

The managed Beads block is task-tracking guidance, not permission to override repository, user, or orchestrator instructions.

- **Conservative (default)**: Use `bd` for task tracking. Do not run git commits, git pushes, or Dolt remote sync unless explicitly asked. At handoff, report changed files, validation, and suggested next commands.
- **Minimal**: Keep tool instruction files as pointers to `bd prime`; use the same conservative git policy unless active instructions say otherwise.
- **Team-maintainer**: Only when the repository explicitly opts in, agents may close beads, run quality gates, commit, and push as part of session close. A current "do not commit" or "do not push" instruction still wins.

## Session Completion

This protocol applies when ending a Beads implementation workflow. It is subordinate to explicit user, repository, and orchestrator instructions.

1. **File issues for remaining work** - Create beads for anything that needs follow-up
2. **Run quality gates** (if code changed) - Tests, linters, builds
3. **Update issue status** - Close finished work, update in-progress items
4. **Handle git/sync by active profile**:
   ```bash
   # Conservative/minimal/default: report status and proposed commands; wait for approval.
   git status

   # Team-maintainer opt-in only, unless current instructions forbid it:
   git pull --rebase
   git push
   git status
   ```
5. **Hand off** - Summarize changes, validation, issue status, and any blocked sync/commit/push step

**Critical rules:**
- Explicit user or orchestrator instructions override this Beads block.
- Do not commit or push without clear authority from the active profile or the current user request.
- If a required sync or push is blocked, stop and report the exact command and error.
<!-- END BEADS INTEGRATION -->

## Project Context

### Source of truth and runtime

- Treat composer.lock, package-lock.json, .php-version, Dockerfile, compose*.yml, docker-vhost.conf, Caddyfile, environment examples, and .github/ as authoritative. Exact dependency patch versions can change through Dependabot; do not update these instructions from local runtime data alone.
- Compatibility floors are PHP >= 8.4.1 and Node.js >= 22.18. CI runs PHP 8.4 and Node.js 24. The production image runs PHP 8.5 and Node.js 26, with MariaDB 11.8.8 behind Caddy 2.11.4. The default local database is SQLite.
- Key frontend packages are Vue 3.5.42, Vue Router 5.3.1, Vue I18n 11.4.10, Vite 8.3.0, and Tailwind 4.3.3.

### Architecture and boundaries

- The public product is a Vue 3 SPA in resources/js/, with client routes in resources/js/routes.js, German/English i18n, runtime feature flags loaded from /api/public/runtime, and pages for products, releases, timelines, search, security, comparison, and authentication/account flows.
- The Filament admin lives at /admin in app/Filament/; pages, resources, and widgets are Livewire-backed. Keep the public Vue boundary separate from admin Livewire components.
- The API is intentionally unversioned under /api. Public /api/public/* endpoints expose published data only. Authenticated routes use auth:sanctum, API throttling, granular token abilities, and EnforceApiTokenPermissions. Use Form Requests and API Resources at API boundaries.
- MCP is exposed through /mcp/versiontracker with upload transfer endpoints under /mcp/uploads/{upload}; access is protected by Sanctum and the mcp ability. Server tools live in app/Mcp/Servers/VersionTrackerServer.php.
- bootstrap/app.php owns application bootstrap, custom middleware, routing, health checks, and trusted host/proxy configuration. Custom middleware belongs under app/Http/Middleware/ and is registered or aliased there.

### Domain and security invariants

- Only published content is public. Versions move through draft, approval, readiness, publish, and reject flows; public release data and downloads must not expose drafts or unapproved records.
- Production deployment requires an eligible published/approved version. Deployment logbook states are Planned, Approved, In Progress, Succeeded, Failed, Canceled, and Rolled Back; corrections and audit history must remain traceable.
- SBOM ingestion supports CycloneDX and SPDX, with OSV vulnerability data and optional EPSS/CISA KEV enrichment. GitHub release synchronization is disabled by default, queued, and idempotent.
- Roles are admin, editor, and viewer, with granular API token abilities. Tokens may be REST- or MCP-scoped, expire, and can be revoked; no endpoint or tool may bypass authorization.
- Preserve rate limits, trusted host/proxy handling, security headers, upload allowlists and size limits, and safe public download behavior.

### Commands and operations

- On a fresh database, composer run setup followed by php artisan app:install --no-demo installs the application. app:install aborts when users already exist; --demo creates public demo credentials and requires an explicit local-only decision. Normal administrator passwords must be at least 12 characters.
- composer run dev starts the local server, database-backed workers for notifications, imports, and default queues, log output, and Vite. The Docker worker currently consumes notifications; the scheduler service runs schedule:work.
- Scheduled operations include app:lifecycle-alerts, app:sync-github-releases, hourly MCP upload pruning, and daily sanctum:prune-expired --hours=24. GitHub synchronization supports --software, --dry-run, and --force.

### GitHub and release operations

- Active workflows are Change classification, Continuous Integration, Release Validation, Release, and Security Audit; Dependabot also manages Composer, npm, GitHub Actions, and Docker updates weekly on Monday with grouped minor/patch updates and major updates ignored.
- The classifier covers backend, frontend, infrastructure, dependency, source, and automation changes, including `.env.example`, `.php-version`, `docker-vhost.conf`, `artisan`, `.github/dependabot.yml`, and `.github/scripts/**`. README/docs/agent metadata remain intentionally unclassified, so component jobs skip while secret scanning still runs.
- The active Protect master repository ruleset requires pull requests, resolved review threads, and the CI gate plus Security gate status checks. Classic branch protection is not the source of truth.
- Tags matching v0.*.* must be based on master and trigger validation, native and Docker archive builds with checksums/metadata, GHCR publication with provenance and SBOM, immutable-digest full-stack smoke tests/scanning, and generated release notes. The release workflow does not deploy the application.
