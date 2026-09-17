# Project Instructions for AI Agents

This file provides instructions and context for AI coding agents working on this project.

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

## Runtime Matrix

- CI support floor: PHP 8.4 and Node.js 24.
- Local development: PHP >= 8.4.1 and Node.js >= 22.18.
- Production image: PHP 8.5 and Node.js 26, with MariaDB 11.8.8 behind Caddy 2.11.4.
- The default local database is SQLite.
- The lockfiles (composer.lock and package-lock.json), .php-version, Docker files, Compose files, and GitHub workflows are the source of truth. Exact dependency patch versions may change through Dependabot.
- Key frontend packages are Vue 3.5.42, Vue Router 5.3.1, Vue I18n 11.4.10, Vite 8.3.0, and Tailwind 4.3.3.


## Build & Test

Use these commands for the normal local workflow:

```bash
composer run setup
php artisan app:install --no-demo
composer run dev
npm run build
vendor/bin/pint --dirty
php artisan test
```

Run app:install --demo only on a fresh local database when demo credentials are explicitly wanted. The regular installer requires an administrator password of at least 12 characters.

composer run dev already starts Vite. Use npm run dev separately only when the Laravel services are started by another process.

## Architecture Overview

- The public product is a Vue 3 SPA in resources/js/, with client routes in resources/js/routes.js, German/English i18n, public runtime feature flags, and pages for products, releases, timelines, search, security, comparison, and authentication/account flows.
- The Filament admin lives at /admin in app/Filament/; pages, resources, and widgets are Livewire-backed.
- The API is intentionally unversioned under /api. Public /api/public/* endpoints expose published data only. Authenticated endpoints use auth:sanctum, API throttling, granular token abilities, and EnforceApiTokenPermissions.
- MCP is exposed through /mcp/versiontracker with upload transfer endpoints under /mcp/uploads/{upload}; access is protected by Sanctum and the mcp ability. Server tools live in app/Mcp/Servers/VersionTrackerServer.php.
- bootstrap/app.php owns application bootstrap, custom middleware, routing, health checks, and trusted host/proxy configuration. Custom middleware belongs under app/Http/Middleware/ and is registered or aliased there.

## Conventions & Patterns

- Use Eloquent models, typed relationships, Form Requests, API Resources, policies/gates, and queued jobs according to the existing sibling implementations. Use DB::transaction and DB::table where transaction boundaries, system tables, or complex aggregates require them.
- Keep public content publication-aware: drafts, unapproved versions, and unpublished releases must not leak through public API or download routes. Version workflows include approval, readiness checks, publish/reject, and release metadata.
- Production deployments require an eligible published/approved version. Deployment logbook states and corrections are audited; do not bypass the service-layer authorization and state transitions.
- SBOM ingestion supports CycloneDX/SPDX and OSV vulnerability data, with optional EPSS and CISA KEV enrichment. GitHub release sync is disabled by default, queued, and idempotent.
- Roles are admin, editor, and viewer, with granular API token abilities. Tokens may be REST- or MCP-scoped, expire, and can be revoked; no endpoint or tool may bypass authorization.
- Preserve rate limits, trusted host/proxy handling, security headers, upload allowlists and size limits, and safe public download behavior.
- Read configuration through config() in application code. Keep trusted host/proxy env() reads confined to bootstrap configuration. Preserve the existing Vue/Filament boundary and do not introduce Livewire public pages without a deliberate architectural decision.

## Operations & CI

- On a fresh database, composer run setup followed by php artisan app:install --no-demo installs the application. app:install aborts when users already exist; --demo creates public demo credentials and requires an explicit local-only decision. Normal administrator passwords must be at least 12 characters.
- composer run dev starts the local server, queue workers for notifications,imports,default, log output, and Vite. The Docker worker currently consumes notifications; the scheduler runs schedule:work.
- Scheduled operations include app:lifecycle-alerts, app:sync-github-releases, hourly MCP upload pruning, and daily sanctum:prune-expired --hours=24. GitHub sync supports --software, --dry-run, and --force.
- CI classifies backend, frontend, infrastructure, dependency, source, and automation changes. README/docs/agent metadata changes are intentionally unclassified; component jobs skip while secret scanning still runs.
- Active workflows are Change classification, Continuous Integration, Release Validation, Release, and Security Audit; Dependabot manages Composer, npm, GitHub Actions, and Docker updates weekly with grouped minor/patch updates and major updates ignored.
- Required merge checks are CI gate and Security gate from the active Protect master repository ruleset; classic branch protection is not the source of truth. Tags matching v0.*.* must be based on master and run validation, publish GHCR images with provenance/SBOM, smoke-test and scan the immutable digest, create a checksummed release bundle, and generate release notes; the release workflow does not deploy the application.
