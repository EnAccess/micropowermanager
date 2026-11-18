---
order: 0
---

# Overview

Plugins are what bring MicroPowerManager to life.
The Open Source core manages customers, devices and payments.
Plugins connect that core to the external services and APIs a utility actually runs on.
This makes it possible to integrate third-party systems without modifying the core application.

Anyone can build a plugin and contribute it to the Open Source project.
See the [Plugin Development Guide](/development/plugins) to get started.

Typical examples include:

- Manufacturer integrations (for token generation or remote management)
- Transaction providers (e.g. mobile money) or Payment aggregators
- Data import and export services
- External communication interfaces

Most plugins require an initial setup.
This usually includes:

- Providing credentials for the external service
- Configuring synchronization intervals
- Defining connection parameters

After the initial configuration, plugins generally run without requiring changes in day-to-day operations.

> [!INFO]
> The words **plugin** and **integration** are mostly used interchangeably.
> The _integration_ of an external party or service with MicroPowerManager is
> implemented through development of a _plugin_.

## Plugin overview

The following plugins are available, grouped by category.
Each plugin links to its guide.
A plugin whose documentation is marked as missing only has a placeholder page.

<PluginOverview />
