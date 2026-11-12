---
order: 0
---

# Overview

MicroPowerManager is configured and extended through plugins.
Plugins extend the core functionality by integrating external services and APIs.
This makes it possible to connect MicroPowerManager to third-party systems without modifying the core application.

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
