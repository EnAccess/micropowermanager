# Operator Dashboard (standalone Vue 2 SPA)

Read the [root `AGENTS.md`](../../AGENTS.md) first for repo-wide rules.
This app is the platform host's cross-tenant analytics view; it is deliberately separate from [`src/frontend`](../frontend/), which is the per-tenant admin panel.

## Conventions

Everything in [`src/frontend/AGENTS.md`](../frontend/AGENTS.md) about lint rules applies here too — mandatory local import extensions, alphabetised imports, and `<style lang="scss" scoped>` on every component.
On top of that:

- **No Vue Material.** The design drops the gradient/`md-card` idiom, and this app needs only cards, buttons, a table, chips and two bars. They live in [`src/shared/`](src/shared/). Do not add the dependency back to save writing a component.
- **`src/assets/sass/tokens.scss` is prepended to every SCSS block** by `vue.config.js`. Since `@use`/`@forward` must come first in a Sass file, component `<style>` blocks must never declare their own — add what they need to `tokens.scss` instead. The file must not be named `_tokens.scss`: the webpack `@/` alias does not resolve to a Sass partial.
- **Auth is HTTP Basic held in `sessionStorage`.** The backend deliberately answers 401 without `WWW-Authenticate`, because that header makes browsers open their own credential dialog on an XHR and bypass [`src/views/SignIn.vue`](src/views/SignIn.vue).
- **The refresh poll lives in the Vuex action**, not a component timer, so navigating mid-rebuild cannot orphan it.
- **The shell keeps `window` as the scroll container.** Adding `overflow: auto` to `.shell__main` silently breaks the router's scroll-to-top.
- **Health thresholds and status colours are data.** Thresholds live in [`src/design/health.js`](src/design/health.js) and return a semantic key; the colours come from the `$ops-status` map in `tokens.scss`. Chart colours are the one accepted SCSS↔JS duplication ([`src/design/palette.js`](src/design/palette.js)).
- **Money is per-tenant only.** Tenants bill in different currencies, so no view may sum or convert them. Platform-wide usage is expressed in transaction counts.

## Common Patterns

- **Stack.** Vue 2.7 + Vuex + Vue Router (hash mode) + vue-i18n, charts via vue-echarts/ECharts 5.
- **Data flow.** Repository → Service (+ `OperatorDashboardMapper` for snake_case → camelCase) → Vuex module → component, mirroring `src/frontend`. The envelope is always `response.data.data`.
- **Backend.** `GET /api/operator/dashboard`, `GET .../tenants/{id}`, `POST .../refresh`. The payload is a nightly-rebuilt cache; freshness is `generated_at` plus `stale`.

## Quality Checks

```bash
npm run lint
npx prettier --write .
npm run build
```
