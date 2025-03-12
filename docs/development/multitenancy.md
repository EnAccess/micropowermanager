---
order: 4
---

# Databases and multitenancy

> [!INFO]
> MPM's multitenancy feature was sometimes called **sharding** (and the corresponding databases **shards**).
> We are moving away from this notion in favour of **multitenancy** and **tenants**.

## Multitenancy in Micropower Manager

Multitenancy in Micropower Manager is implemented using a database-per-tenant approach, where each tenant has a dedicated database. This ensures data isolation, enhances security, and allows for more flexible scaling. By storing each tenant's data in a separate database, Micropower Manager enables efficient management of Software as a Service (SaaS) functionality while maintaining performance and customization per tenant.

### Tenant Representation

Each tenant in Micropower Manager represents an individual company leveraging the platform for their Customer
Relationship Management (CRM) needs.

### Central Database - "micro_power_manager"

To facilitate this, a central database named "micro_power_manager" is established. This central database houses
company-specific information and common data such as installable plugins.

### New Company Registration Process

When a new company registers an account, a dedicated database is dynamically created for that specific company. This new
database incorporates Micropower Manager's core migration files located at `src/backend/database/migrations/tenant`

### User Session Interaction

Upon successful registration, when a user associated with a particular company logs into Micropower Manager, the
database connection for their session is dynamically altered. This ensures that the user gains access to and interacts
with data exclusive to their company, providing a personalized and secure experience within the application.

## Tenant Specific Migration Commands

- **Creating Migration File (core):**
  When creating a migration file, you need to use the following command:

```bash
docker exec -it backend-dev bash
php artisan make:migration {migration-name}
```

This command creates a migration file in Micropower Manager's core migration files location: `src/backend/database/migrations/tenant`

- **Creating Migration File (tenant):**
  To create a migration file for tenant database(s) use the following command:

```bash
docker exec -it backend-dev bash
php artisan make:migration-tenant {migration-name}
```

To migrate the database, use the following command:

```bash
docker exec -it backend-dev bash
php artisan migrate
```

Alternatively, you can migrate the database for a specific company using the following command:

```bash
docker exec -it backend-dev bash
php migrate-tenant {company_id} {--force}
```
