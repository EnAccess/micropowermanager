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

| Plugin                        | Category           | Current Status                                                                                         | Integration Document URL                                                                                                |
| ----------------------------- | ------------------ | ------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| **Stron Meter**               | Meter              | Ready                                                                                                  | They do not provide API docs online                                                                                     |
| **Gomelong Meter**            | Meter              | Ready                                                                                                  | They do not provide API docs online                                                                                     |
| **Microstar Meter**           | Meter              | Ready (Companies have to make an agreement with MicroStarElectric to get their .p12 certificate)       | They do not provide API docs online                                                                                     |
| **Steama Meter**              | Meter              | Ready (periodic sync of sites, customers, meters, agents, and transactions)                            | [API Docs](https://api.steama.co/docs/) · [Plugin guide](./steama-meter-integration.md)                                 |
| **Angaza Solar Home System**  | Solar Home System  | Ready                                                                                                  | [API Docs](https://developers.angaza.com/docs/dev-portal-nexus/77a9ea5040a3b-retrieve-a-unit-s-payg-credit-information) |
| **SPARK Solar Home System**   | Solar Home System  | Ready                                                                                                  | [Plugin guide](./spark-shs-integration.md)                                                                              |
| **DalyBms (e-bike)**          | Battery Management | Ready (Battery Management System API, works only with e-bikes with Daly BMS installed)                 | [Website](https://www.dalybms.com/bms-electric-bike/)                                                                   |
| **Safaricom M-PESA (Daraja)** | Payment provider   | Ready (STK Push via operator-facing Initiate Payment page; sandbox + production)                       | [Developer Portal](https://developer.safaricom.co.ke/) · [Plugin guide](./safaricom-mobile-money-integration.md)        |
| **Vodacom MZ / M-Pesa**       | Payment provider   | Ready (production use requires Vodacom to validate test-environment transactions)                      | [Plugin guide](./vodacom-mz.md)                                                                                         |
| **Flutterwave Payment**       | Payment provider   | Ready (hosted checkout via public payment URL; sandbox + production)                                   | [Plugin guide](./flutterwave-integration.md)                                                                            |
| **Paystack Payment**          | Payment provider   | Ready (hosted checkout via public payment URL; test + production)                                      | [Plugin guide](./paystack-integration.md)                                                                               |
| **PesaPal Payment**           | Payment provider   | Ready (hosted checkout via public payment URL; sandbox + production)                                   | [Plugin guide](./pesapal-integration.md)                                                                                |
| **Wavecom Payment**           | Payment provider   | (Manual) Ready (MicroPowerManager users must export transaction data, paste into template, and upload) | N/A                                                                                                                     |
| **AfricasTalking**            | SMS gateway        | Ready                                                                                                  | [Plugin guide](./africastalking.md)                                                                                     |
| **TextBee**                   | SMS gateway        | Ready (uses an Android device you own as the SMS gateway)                                              | [Plugin guide](./textbee.md)                                                                                            |
| **Odyssey Energy**            | Data export        | Ready (Odyssey pulls transaction and customer data from MicroPowerManager)                             | [Plugin guide](./odyssey-integration.md)                                                                                |
| **ECREEE e-Tender**           | Data export        | Ready (e-Tender retrieves transaction and operational data for milestone verification)                 | [Plugin guide](./ecreee-e-tender-integration.md)                                                                        |

## Needs Refactoring / Development

| Plugin                 | Category         | Current Status       | Integration Document URL                                    |
| ---------------------- | ---------------- | -------------------- | ----------------------------------------------------------- |
| **Spark Meter**        | Meter            | Requires refactoring | [API Docs](https://api.sparkmeter.io/#intro)                |
| **Calin Meter**        | Meter            | Requires refactoring | They do not provide API docs online                         |
| **Calin Smart Meter**  | Meter            | Requires refactoring | They do not provide API docs online                         |
| **Chint Meter**        | Meter            | Requires refactoring | They do not provide API docs online                         |
| **Swifta Payment**     | Payment provider | Requires refactoring | [Website](https://swifta.com/)                              |
| **Mesomb Payment**     | Payment provider | Requires refactoring | [Login](https://business.mesomb.com/auth/login)             |
| **Wave Money Payment** | Payment provider | Requires refactoring | [API Docs](https://partners.wavemoney.com.mm/documentation) |

## Broken / Deprecated

| Plugin              | Category  | Current Status                                      | Integration Document URL                                                                     |
| ------------------- | --------- | --------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| **Kelin Meter**     | Meter     | Broken (They do not provide API anymore)            | N/A                                                                                          |
| **Viber Messaging** | Messaging | No longer usable due to new strict chatbot criteria | [Help Article](https://help.viber.com/hc/en-us/articles/15247629658525-Bot-commercial-model) |
