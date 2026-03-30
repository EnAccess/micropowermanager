---
order: 1
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

| Plugin                        | Current Status                                                                                         | Integration Document URL                                                                                                |
| ----------------------------- | ------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| **Stron Meter**               | Ready                                                                                                  | They do not provide API docs online                                                                                     |
| **Gomelong Meter**            | Ready                                                                                                  | They do not provide API docs online                                                                                     |
| **SunKing Solar Home System** |                                                                                                        | They do not provide API docs online                                                                                     |
| **Calin Meter**               | Ready                                                                                                  | They do not provide API docs online                                                                                     |
| **Calin Smart Meter**         | Ready                                                                                                  | They do not provide API docs online                                                                                     |
| **Microstar Meter**           | Ready (Companies have to make an agreement with MicroStarElectric to get their .p12 certificate)       | They do not provide API docs online                                                                                     |
| **DalyBms (e-bike)**          | Ready (Battery Management System API, works only with e-bikes with Daly BMS installed)                 | [Website](https://www.dalybms.com/bms-electric-bike/)                                                                   |
| **Wavecom Payment**           | (Manual) Ready (MicroPowerManager users must export transaction data, paste into template, and upload) | N/A                                                                                                                     |
| **Angaza Solar Home System**  | (`unlockDevice` not implemented, see [here](https://github.com/EnAccess/micropowermanager/issues/570)) | [API Docs](https://developers.angaza.com/docs/dev-portal-nexus/77a9ea5040a3b-retrieve-a-unit-s-payg-credit-information) |

## Needs Refactoring / Development

| Plugin                 | Current Status       | Integration Document URL                                    |
| ---------------------- | -------------------- | ----------------------------------------------------------- |
| **Spark Meter**        | Requires refactoring | [API Docs](https://api.sparkmeter.io/#intro)                |
| **Steama Meter**       | Requires refactoring | [API Docs](https://api.steama.co/docs/)                     |
| **Swifta Payment**     | Requires refactoring | [Website](https://swifta.com/)                              |
| **Mesomb Payment**     | Requires refactoring | [Login](https://business.mesomb.com/auth/login)             |
| **Wave Money Payment** | Requires refactoring | [API Docs](https://partners.wavemoney.com.mm/documentation) |

## Broken / Deprecated

| Plugin              | Current Status                                      | Integration Document URL                                                                     |
| ------------------- | --------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| **Kelin Meter**     | Broken (They do not provide API anymore)            | N/A                                                                                          |
| **Viber Messaging** | No longer usable due to new strict chatbot criteria | [Help Article](https://help.viber.com/hc/en-us/articles/15247629658525-Bot-commercial-model) |
