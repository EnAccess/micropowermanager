<p align="center">
  <a href="https://github.com/EnAccess/micropowermanager-cloud">
    <img
      src="https://micropowermanager.com/assets/images/Website_Illustrations_Logo.png"
      alt="OpenSmartMeter"
      width="320"
    >
  </a>
</p>
<p align="center">
    <em>Decentralized utility management made simple. Manage customers, revenues and assets with this all-in one open source platform.</em>
</p>
<p align="center">
  <img
    alt="Project Status"
    src="https://img.shields.io/badge/Project%20Status-stable-green"
  >
  <img
    alt="GitHub Workflow Status"
    src="https://img.shields.io/github/actions/workflow/status/EnAccess/micropowermanager-cloud/check.yaml"
  >
  <a href="https://github.com/EnAccess/micropowermanager-cloud/blob/main/LICENSE" target="_blank">
    <img
      alt="License"
      src="https://img.shields.io/github/license/EnAccess/micropowermanager-cloud"
    >
  </a>
</p>

---

# MicroPowerManager

---

## This project

- Is written in PHP 8.0
- It uses Laravel 9.0
- It uses Vue.js 2.6
- It uses Node 16.10
- It uses MariaDB 10.3 which is compatible with MySQL 5.7

## Setup

---

### Docker installation

1. Windows & Mac

- [Docker Desktop](https://www.docker.com/products/docker-desktop).

2. Linux

- Docker provides installation instructions for various Linux distributions. You can find them on
  the [Docker installation page for Linux.](https://docs.docker.com/engine/install/).

### Docker Compose

Docker Compose is often included with the Docker Desktop installations for Windows and Mac. For Linux, you might need to
install it separately.

1. Windows and Mac:

- Included with Docker Desktop.

2. Linux:

- Docker Compose on GitHub https://github.com/docker/compose/releases
- On the GitHub page, you can find instructions for downloading and installing Docker Compose on Linux. Be sure to check
  for the latest release.

### Repository setup

1. Clone or download the repository
2. Build the docker containers with `docker-compose up`

## Development

---

The development environment is served under http://mpmanager.local To reach the site over the given url; enter the
following lines to your hosts file.

#### For Linux/Mac Users

```
/etc/hosts
127.0.0.1       mpmanager.local
127.0.0.1       db.mpmanager.local
```

#### For Windows Users

```
c:\windows\system32\drivers\etc\hosts
127.0.0.1       mpmanager.local
127.0.0.1       db.mpmanager.local
```

## Frontend

The frontend is served under http://mpmanager.local. You can find frontend files under `Website/ui`.
The frontend is built with Vue.js. After first run with `docker-compose up` dependencies will be installed
automatically.
If you want to install dependencies manually, you can run `npm install` under `Website/ui` folder.

#### Folder Structure

When adding new files to the project, please adhere to the following folder structure:

- **Creating New Modules:**
  Modules are the components used in pages. For example, the Client module holds components related to clients. Every
  component associated with clients should be placed under the Client module.

```
Website/ui
├── src
│   ├── modules
│   │   ├── newModule
```

- **Creating New Pages:**
  Pages are the components used in routes. We follow the nuxt.js folder structure for pages. The `index.vue` file under
  a page folder represents the listing page of the page, while the `_id.vue` file represents the detail page. Since we
  are not using nuxt.js, routes need to be defined manually. You can find the routes in
  the `Website/ui/src/ExportedRoutes.js` file.

```
Website/ui
├── src
│   ├── pages
│   │   ├── newPage
|   |   |   ├── index.vue
|   |   |   ├── _id.vue
```

#### Plugins

Plugins are additional components developed as separate packages to enhance our product. This separation helps keep the
main codebase clean. Each plugin should reside in its own folder under the `Website/ui/src/plugins` directory.
Additionally, each plugin should have its own backend code, which will be explained in the backend section.

```
Website/ui
├── src
│   ├── plugins
│   │   ├── newPlugin
```

In the backend section, you'll find instructions on how to create a plugin.

## Backend

The backend is built with Laravel. The backend is served under http://api.mpmanager.local/api. You can find backend
files under `Website/htdocs/mpmanager`. After the first run with `docker-compose up`, dependencies will be installed
automatically. If you prefer to install dependencies manually or need to add additional packages, follow these steps:

1. Enter the Docker container named "laravel" by navigating to the "mpmanager" directory:

   ```bash
   docker exec -it laravel bash
   cd mpmanager
     ```
2. Run the following command to install dependencies, replacing {package-name} with the actual name of the package:

   ```bash
    ./composer.phar install {package-name}
   ```

These steps ensure that you can manage dependencies either automatically during the initial docker-compose up or
manually when needed.
Make sure to replace `{package-name}` with the actual name of the package you want to install.

We followed the laravel folder structure for the backend. If you want to learn more about the folder structure, you can
check the [Laravel documentation](https://laravel.com/docs/9.x/structure).

### Sharding in Micropower Manager

Sharding is a technique employed to partition a large database into smaller, more agile, and easily manageable segments
known as data shards. In the context of Micropower Manager, sharding is integral to the implementation of Software as a
Service (SaaS) functionality.

#### Shard Representation

Each shard in Micropower Manager represents an individual company leveraging the platform for their Customer
Relationship Management (CRM) needs.

#### Central Database - "micro_power_manager"

To facilitate this, a central database named "micro_power_manager" is established. This central database houses
company-specific information and common data such as installable plugins.

#### New Company Registration Process

When a new company registers an account, a dedicated database is dynamically created for that specific company. This new
database incorporates Micropower Manager's core migration files located at `Website/htdocs/mpmanager/database/migrations/micropowermanager`

#### User Session Interaction

Upon successful registration, when a user associated with a particular company logs into Micropower Manager, the
database connection for their session is dynamically altered. This ensures that the user gains access to and interacts
with data exclusive to their company, providing a personalized and secure experience within the application.

### Migration on First run

When you run the application for the first time, you need to run the following command to migrate the database:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan sharding:initialize
php artisan dummy:create-company-with-dummy-data
```

These commands will create the central database and the first company database. The first company database will have
dummy data.
You can use the following credentials to login to the application:

```
username: dummy@user.com
password: 123123
```

The dummy protected page password of this company is 123123.

### Sharding Specific Migration Commands

- **Creating Migration File:**
  When creating a migration file, you need to use the following command:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan migrator:create {migration-name}
```
This command creates a migration file in Micropower Manager's core migration files location: `Website/htdocs/mpmanager/database/migrations/micropowermanager

After creating the migration file, you can shift it to other company databases using the following command:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan migrator:copy
```
This command syncs the migration files in the core migration folder for other company migrations.

To migrate the database, use the following command:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan migrator:migrate
```

Alternatively, you can migrate the database for a specific company using the following command:

```bash
docker exec -it laravel bash
cd mpmanager
php shard:migrate {company_id} {--force}
```

#### Plugins
We have a custom plugin creator command that generates a template. Use the following command to create a new plugin:

```bash
docker exec -it laravel bash
cd mpmanager
php artisan micropowermanager:new-package {package-name}
``` 

This command creates a plugin template in the Website/htdocs/mpmanager/packages/inensus folder. Upon creation, you can proceed with plugin development. You can check other plugins for reference.
Additionally, this command will create UI folders for the newly created plugin. Move the created UI folder to the Website/ui/src/plugins folder.


### phpMyAdmin

To project also includes phpMyAdmin which enables quick database operations without installing third-party software or writing any single line into the terminal.

The default credentials for the database are;
    
```
username: root
password: wF9zLp2qRxaS2e
```