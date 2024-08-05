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

When you run the application for the first time, you need to run the following command to initialize the databases:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan migrate --seed --seeder ShardingDatabaseSeeder
```

This command will create the central database which is required for MPM to function.

> [!NOTE]
> At this point you are ready to use the MPM locally.
> For example, you can register new users and tenants.
>
> However, for a better development flow you might want to load load dummy data.
> See next section.

## Loading the dummy data

If you want to load sample (dummy) data for testing run:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan dummy:create-company-with-dummy-data
php artisan migrate-tenant
```

This commands will create the first company database with dummy data from a snapshot.
It will then apply any migrations that have been added to the application after the snapshot has been taken.

You can use the following credentials to login to the application:

```sh
username: dummy@user.com
password: 123123
```

The dummy protected page password of this company is 123123.

## Explore the internals

See the follow section to learn more about the MPM code base is structured.
