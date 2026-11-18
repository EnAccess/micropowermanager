# MicroPowerManager Documention

This MicroPowerManager Documention website is built using [VitePress](https://vitepress.dev/), a Vite & Vue Powered Static Site Generator.

## Pre-requisites

- [NodeJS](https://nodejs.org/en)

## Installation

```sh
cd docs/
npm install
```

## Local Development

```sh
npm run docs:dev
```

Then open [docs](http://localhost:5173/) in a local web browser.
This command starts a local development server and opens up a browser window.
Most changes are reflected live without having to restart the server.

## Plugin frontmatter

Every page in a category folder of `integrations-guide/` describes one plugin.
We use a small Vue component to auto-generate the plugin overview table in `integrations-guide/overview.md`.
To achieve this all plugin pages need to populate the following frontmatter fields.

| Field                | Description                                      | Values                                                                       |
| -------------------- | ------------------------------------------------ | ---------------------------------------------------------------------------- |
| `mpm_plugin_status`  | State of the plugin implementation               | `✅ Ready`, `⚠️ Requires refactoring`, `❌ Broken`, `🌑 No longer supported` |
| `mpm_plugin_doc`     | State of the plugin documentation                | `✅ Ready`, `❌ Missing`                                                     |
| `mpm_plugin_comment` | Optional free-text note shown next to the plugin | Any text                                                                     |

## Modify drawings

To edit the **drawings** please install

- [Excalidraw VS Code Extension](https://marketplace.visualstudio.com/items?itemName=pomdtr.excalidraw-editor)

Then, open files with the `.excalidraw.svg` or `.excalidraw.png` in VS Code.

## Deployment

Deployment is done using Github Actions and does not involve manual steps.
Check out `.github/workflows` folder of the root repository.

## Generate Database ERD

Currently, ERD generation is a (semi-)manual process.

Assuming you have a local development setup running, first prepare you environment by running

```sh
docker exec -it mysql bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS erd;"
```

Then

```sh
docker exec -it -u www-data backend-dev bash
php artisan erd:generate micro_power_manager --excludes=plugins --file=central_database.sql
php artisan erd:generate tenant --path=/database/migrations/tenant --excludes=companies,company_databases,database_proxies --file=tenant_database.sql
php artisan export
```

Run (from the host)

```sh
cp -r src/backend/dist/laravel-erd/ docs/.public/schemas/
```

Go to each

- [laravel-erd](http://localhost:8000/laravel-erd/central_database)
- [laravel-erd](http://localhost:8000/laravel-erd/tenant_database)

and export PNG by either

- using "right click -> export"
- or your operating system's screenshot tool

and save these as

```sh
docs/development/images/schema_central_database.png
docs/development/images/schema_central_database.png
```

## Generate OpenAPI docs

OpenAPI docs are automatically generated when the docs are deployed via [Github Actions](https://github.com/EnAccess/micropowermanager/blob/main/.github/workflows/deploy-gh-pages.yaml)

Assumping you have a [local development setup](https://micropowermanager.io/development/development-environment.html#access-docker-mysql-database-locally) running, then a rendered version of the OpenAPI docs are available at

- [OpenAPI](http://localhost:8000/docs/api/)

> [!INFO]
> Rendered OpenAPI docs are only available on a development environment.
> Production containers do not expose OpenAPI docs.

## Further Read

- [Markdown Features](https://vitepress.dev/guide/markdown)
- [Markdown Front Matter](https://vitepress.dev/guide/frontmatter)
