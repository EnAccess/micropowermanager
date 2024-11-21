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

## Deployment

Deployment is done using Github Actions and does not involve manual steps.
Check out `.github/workflows` folder of the root repository.

## Generate Database ERD

Currently, ERD generation is a (semi-)manual process.

Assumping you have a local development setup running, run

```sh
docker exec -it backend-dev bash
php artisan erd:generate micro_power_manager --excludes=plugins --file=central_database.sql
php artisan erd:generate shard --path=/database/migrations/micropowermanager --excludes=companies,company_databases,company_jobs,database_proxies --file=tenant_database.sql
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

Currently, OpenAPI docs generation is a (semi-)manual process.

Assumping you have a local development setup running, run

```sh
docker exec -it backend-dev bash
php artisan scribe:generate
```

The output will be generated at [OpenAPI docs](http://localhost:8000/docs/).

## Further Read

- [Markdown Features](https://vitepress.dev/guide/markdown)
- [Markdown Front Matter](https://vitepress.dev/guide/frontmatter)
