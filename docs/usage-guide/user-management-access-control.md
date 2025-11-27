---
order: 4
---

# User Management and Access Control

This guide explains how user accounts and access control work in **MicroPowerManager**.

## User Management

A **user account** is required for every individual who needs access to the company's (tenant's) MicroPowerManager system.

This includes:

- Project developer staff who use the **web interface**.
- Field employees working with the **Customer Registration Android app**.

Each user account is **personal** and provides access to:

- The MicroPowerManager **web interface**.
- The **Customer Registration Android app**.

Additionally, user accounts can be assigned to a cluster if the user’s responsibilities are linked to a specific operational cluster.

## Access Control and administration

The MicroPowerManager web interface provides access to certain sensitive areas that are reserved for **administrative tasks**.

These include:

- Managing company-wide settings.
- Configuring tariffs and targets.
- Adding new locations (villages, mini-grids, clusters).

The [Android Apps](/usage-guide/android-apps), by design, **do not** include administrative capabilities.
This ensures:

- Enhanced security for sensitive operations.
- A focused experience for field staff working on customer registration and data collection.

> [!INFO]
> All administrative actions must be performed through the web platform.
> Field employees using the mobile apps do **not** have access to these protected areas.

### Role-Based Permissions (RBAC)

MicroPowerManager uses **Role-Based Access Control (RBAC)** to decide who can perform administrative tasks. Every authenticated request carries a JWT token populated with the user’s **roles** and the derived **permissions**. Both the API and the web UI validate those permissions before rendering sensitive features.

Built-in roles:

- **Owner** – Full control, including managing admins and system settings.
- **Admin** – Manage all operational data and configuration, but not owner accounts.
- **Financial Manager** – Manage customers, finances, transactions, and reports.
- **User** – Operational access to customers, assets, and tickets only.

Key permission areas:

- `users`, `roles` – User and role administration.
- `settings`, `settings.api-keys` – Company configuration, SMS gateways, API keys.
- `plugins` – Enable/disable and configure plugins.
- `assets`, `customers`, `payments`, `transactions`, `reports`, `exports`, `tickets` – Operational domains.

> [!INFO]
> Pages that were previously guarded by the Protected Pages Password now rely on these permissions.
