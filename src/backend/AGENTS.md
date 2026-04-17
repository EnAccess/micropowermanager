# Backend (Laravel 12 / PHP 8.2)

Read the [root `AGENTS.md`](../../AGENTS.md) first for repo-wide rules. This file covers backend-specific conventions, patterns, and commands.

## Conventions

- **Imports.** Always import full module names with `use`. Do not qualify a full path inline to avoid importing.
- **Request input.** Do not use `$request->input(...)`. Use the typed accessors — `$request->integer(...)`, `$request->string(...)`, `$request->boolean(...)` — so the type is explicit at the boundary.
- **Route syntax.** Use `[Controller::class, 'method']`, never string-based `'Controller@method'`.
- **Migrations must declare a connection.**
  Tenant migrations (in `database/migrations/tenant/`) use `Schema::connection('tenant')` for every operation (`create`, `table`, `hasTable`, `dropIfExists`).
  Central migrations use `Schema::connection('micro_power_manager')`.
  The migration runner does **not** set a default connection — omitting it runs against the wrong DB.
- **Tests.** Name test methods with a `test` prefix (e.g. `public function testItDoesX()`). Do **not** use `/** @test */` annotations.
- **Abstraction headers.** When introducing an interface or service that multiple plugins or flows will depend on, add a short `/** */` header stating **why** the abstraction exists — not what it does. Two or three sentences. No `@see` chains, no method-by-method description. Do not add such headers to DTOs, single-use helpers, or classes whose name already tells the story. Do not add docstrings or type annotations to code you didn't change.

## Common Patterns

- **Multi-tenancy.** Two MySQL connections: `micro_power_manager` (shared) and a per-tenant connection. `UserDefaultDatabaseConnectionMiddleware` switches the tenant connection per request.
- **Authentication.** JWT (tymon/jwt-auth) with dual guards: `User` (admin panel) and `Agent` (field agent app). Middleware: `JwtMiddleware`.
- **Plugin system.** Plugins live in `app/Plugins/` (payment providers, SMS gateways, smart meters, solar home systems). Managed through the `PluginGateway` pattern.
- **Event-driven transactions.** Transaction lifecycle uses `TransactionSuccessfulEvent` / `TransactionFailedEvent` with listeners that trigger payment processing, SMS notifications, and token generation. Key jobs: `TokenProcessor`, `EnergyTransactionProcessor`, `ApplianceTransactionProcessor`.
- **Service layer.** Business logic lives in `app/Services/`. Controllers delegate to services. Models in `app/Models/` map to database entities.
- **Queue processing.** Laravel Horizon on Redis. Workers run in a separate container.
- **Routes.** `routes/api.php` includes sub-route files from `routes/api_paths/`. Agent and customer registration apps have their own route groups.

## Tests

Run tests directly from `src/backend/` (not via Docker). Requires MySQL on port 53306 and Redis.

```bash
php artisan test                    # all tests
php artisan test --stop-on-failure  # CI default
php artisan test --filter=TestName  # single class or method
```

PHPUnit config: `phpunit.xml`. Suites: `Unit` (`tests/Unit/`), `Feature` (`tests/Feature/`).

Fresh test-DB migration:

```bash
php artisan --env=testing migrate:fresh
```

## Quality Checks

Run before considering work done — CI runs the same checks on PRs to `main`:

```bash
composer quality:check        # cs-fixer + larastan + rector
composer rector-fix           # Rector + cs-fixer auto-fix
```
