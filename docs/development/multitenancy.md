---
order: 4
---

# Databases and multitenancy

> [!INFO]
> MPM's multitenancy feature was sometimes called **sharding** (and the corresponding databases **shards**).
> We are moving away from this notion in favour of **multitenancy** and **tenants**.

## Sharding in Micropower Manager

Sharding is a technique employed to partition a large database into smaller, more agile, and easily manageable segments
known as data shards. In the context of Micropower Manager, sharding is integral to the implementation of Software as a
Service (SaaS) functionality.

### Shard Representation

Each shard in Micropower Manager represents an individual company leveraging the platform for their Customer
Relationship Management (CRM) needs.

### Central Database - "micro_power_manager"

To facilitate this, a central database named "micro_power_manager" is established. This central database houses
company-specific information and common data such as installable plugins.

### New Company Registration Process

When a new company registers an account, a dedicated database is dynamically created for that specific company. This new
database incorporates Micropower Manager's core migration files located at `Website/htdocs/mpmanager/database/migrations/micropowermanager`

### User Session Interaction

Upon successful registration, when a user associated with a particular company logs into Micropower Manager, the
database connection for their session is dynamically altered. This ensures that the user gains access to and interacts
with data exclusive to their company, providing a personalized and secure experience within the application.

## Sharding Specific Migration Commands

- **Creating Migration File:**
  When creating a migration file, you need to use the following command:

```bash
docker exec -it laravel-dev bash
cd mpmanager
php artisan migrator:create {migration-name}
```

This command creates a migration file in Micropower Manager's core migration files location: `Website/htdocs/mpmanager/database/migrations/micropowermanager`

After creating the migration file, you can shift it to other company databases using the following command:

```bash
docker exec -it laravel-dev bash
cd mpmanager
php artisan migrator:copy
```

This command syncs the migration files in the core migration folder for other company migrations.

To migrate the database, use the following command:

```bash
docker exec -it laravel-dev bash
cd mpmanager
php artisan migrator:migrate
```

Alternatively, you can migrate the database for a specific company using the following command:

```bash
docker exec -it laravel-dev bash
cd mpmanager
php shard:migrate {company_id} {--force}
```
