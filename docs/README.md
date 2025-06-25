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
docker exec -it backend-dev bash
php artisan erd:generate micro_power_manager --excludes=plugins --file=central_database.sql
php artisan erd:generate tenant --path=/database/migrations/tenant --excludes=companies,company_databases,company_jobs,database_proxies --file=tenant_database.sql
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

Run (from the host)

```sh
cp -r src/backend/storage/framework/cache/scribe/ docs/.public/openapi/
```

## Further Read

- [Markdown Features](https://vitepress.dev/guide/markdown)
- [Markdown Front Matter](https://vitepress.dev/guide/frontmatter)
