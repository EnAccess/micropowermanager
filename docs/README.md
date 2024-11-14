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
php artisan erd:generate micro_power_manager --excludes=plugins
php artisan export
```

Run (from the host)

```sh
cp src/backend/dist/laravel-erd/index.html docs/.public/schemas/schema_central_database.html
```

Go to [laravel-erd](http://localhost:8000/laravel-erd) and export PNG using "right click -> export" and save it as

```sh
docs/.public/schemas/schema_central_database.png
```

## Further Read

- [Markdown Features](https://vitepress.dev/guide/markdown)
- [Markdown Front Matter](https://vitepress.dev/guide/frontmatter)
