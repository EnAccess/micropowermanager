# Frontend (Vue 2 SPA)

Read the [root `AGENTS.md`](../../AGENTS.md) first for repo-wide rules. This file covers frontend-specific conventions, patterns, and commands.

Vue 2 is long deprecated — see the root _Known tech debt_ section. Avoid doubling down on Vue-2-only patterns when an incremental path to Vue 3 exists.

## Conventions

- **Local import extensions are mandatory.** `import Foo from "@/services/FooService.js"` — not `"@/services/FooService"`. Package imports are exempt. Enforced by `import/extensions: "ignorePackages"`.
- **Vue `<style>` blocks.** Must use `<style lang="scss" scoped>`. Both `lang="scss"` (`vue/block-lang`) and `scoped` (`vue/enforce-style-attribute`) are required.
- **Import order.** Imports must be alphabetically sorted with consistent spacing (`import/order`).
- **CSS.** Avoid `!important`.
- **i18n locales.** Files in `src/assets/locales/` must be deep-sorted JSON with the same keys as `en.json` (the source of truth). CI enforces this via `npm run lang:check`. To check/fix locally: `npm run i18n:check-json`, `npm run i18n:fix-json`. `npm run i18n:translate-json` fills keys missing from a locale via the Google Cloud Translation API (`GOOGLE_TRANSLATE_API_KEY` env var required) — local use only, review its output before committing.

## Common Patterns

- **Stack.** Vue 2 + Vuex + Vue Router + Vue Material.
- **Organization.** Feature code lives under `src/modules/`. Cross-cutting concerns split by shape: `plugins/`, `repositories/` (API clients), `services/`.
- **Maps.** Leaflet.

## Tests

Frontend tests live under `src/__tests__/`.

## Quality Checks

Run both before considering UI/frontend work done:

```bash
npm run lint
npx prettier@3.6.2 --write .
```
