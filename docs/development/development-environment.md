---
order: 1
---

# Setup a development environment

In a local development environment

- the backend is served under <http://localhost:8000>
- the frontend is served under <http://localhost:8001>

## Pre-requisites

- A docker container runtime (for example [Docker Desktop](https://www.docker.com/products/docker-desktop))
- [Docker Compose](https://docs.docker.com/compose/)

### Docker Desktop installation

**Windows and Mac:**

- [Docker Desktop](https://www.docker.com/products/docker-desktop).

**Linux:**

- Docker provides installation instructions for various Linux distributions. You can find them on
  the [Docker installation page for Linux.](https://docs.docker.com/engine/install/).

### Docker Compose installation

Docker Compose is often included with the Docker Desktop installations for Windows and Mac. For Linux, you might need to
install it separately.

**Windows and Mac:**

- Included with Docker Desktop.

**Linux:**

- Docker Compose on GitHub <https://github.com/docker/compose/releases>
- On the GitHub page, you can find instructions for downloading and installing Docker Compose on Linux. Be sure to check
  for the latest release.

## Installation

1. Clone the repository
2. Build the docker containers with `docker compose up`

## Migration on First run

When you run the application for the first time, you need to run the following command to initialize the databases and load Demo data:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan migrate --seed
```

This command will

- create the central database which is required for MPM to function.
- create the Demo company, it's database and run migrations
- populate the database entities with demo data

To access the local instance of MicroPowerManager open <http://localhost:8001/> in a web browser.

Log in to the application using the following credentials:

```sh
username: dummy_company_admin@example.com
password: 123123
```

The dummy protected page password of this company is 123123.

> [!NOTE]
> If you wish to run MicroPowerManage without the Demo data you can
> skip `--seed` and only run `php artisan migrate`.
>
> Then you can then explore a vanilla MicroPowerManager instance,
> where you can register new users and tenants.
>
> However, for a better development flow it is generally recommended to load demo data.

## Reseting the Demo data

If you wish reset the Demo data setup to the default setup run:

```sh
docker exec -it laravel bash
cd mpmanager
php artisan migrate-tenant:drop-demo-company
php artisan migrate:fresh --seed
```

This can be helpful if

- Your sample setup got very messy and you wish to have a clean data basis for your work
- New Demo data seeders got added in the upstream code base and you wish to run them
