---
order: 1
---

# Setup a development environment

In a local development environment

- the backend is served under <http://localhost:8000>
- the frontend is served under <http://localhost:8001>

## Pre-requisites

- A docker container runtime with [Docker Compose](https://docs.docker.com/compose/) (for example [Docker Desktop](https://www.docker.com/products/docker-desktop))
- Working installation of [git](https://git-scm.com/)
- A Text editor (While you can use any text editor you're comfortable with, we recommend [VS Code](https://code.visualstudio.com/) for this project)

This guide requires a Unix-like environment. Supported platforms include:

1. macOS: Any recent version of macOS (13 Ventura or later)
2. various Linux distributions (Ubuntu, Fedora, Debian)
3. Windows with [Windows Subsystem for Linux 2 (WSL2)](https://learn.microsoft.com/en-us/windows/wsl/install)

For Windows users:

- Please ensure you have WSL2 installed and properly configured before proceeding.
- Please ensure you are running Docker Desktop in WSL2 mode.
- When opening the repository in VS Code ensure to open in WSL2 mode (for example by navigating to the source code in WSL2 terminal and running `code .`)

For all users:

This document assumes that you have a basic understanding of how to use the command line.
You should be familiar with common Unix commands such as cd, ls, mkdir, and how to edit text files using a text editor like [VS Code](https://code.visualstudio.com/).
Before proceeding, ensure you can open a terminal (Terminal app on macOS, terminal emulator on Linux, or WSL2 terminal on Windows) and run basic commands.

## Installation

1. `git`-clone the repository
2. Build and run the docker containers with `docker compose up`

## Migration on First run

When you run the application for the first time, you need to run the following command to initialize the databases and load Demo data:

```bash
docker exec -it backend-dev bash
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

## Generating transaction data

To generate dummy transaction data you can run

```sh
php artisan dummy:create-data 250
php artisan dummy:create-data --type=ticket 25
```

This commands will create 250 transactions and 25 tickets within the past 30 days respectively.
It can be run multiple times to generate more data as required.

## Reseting the Demo data

If you wish reset the Demo data setup to the default setup run:

```sh
docker exec -it backend-dev bash
php artisan migrate-tenant:drop-demo-company
php artisan migrate:fresh --seed
```

This can be helpful if

- Your sample setup got very messy and you wish to have a clean data basis for your work
- New Demo data seeders got added in the upstream code base and you wish to run them
