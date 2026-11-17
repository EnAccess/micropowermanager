---
order: 30
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Syncs sites, customers, tariffs and transactions via Koios and ThunderCloud
---

<p align="center">
  <a href="https://www.sparkmeter.io/">
    <img
      src="../images/sparkmeter-logo.png"
      alt="SparkMeter"
      width="267"
    >
  </a>
</p>

# Spark Meter Integration

This guide explains how to connect a [SparkMeter](https://www.sparkmeter.io/) account to MicroPowerManager so that its sites, customers, meters, tariffs, and transactions are synchronized into your project.

## Overview

SparkMeter exposes two APIs, and the plugin uses both:

- **Koios** is the SparkMeter cloud.
  It authenticates your organization and lists your sites.
- **ThunderCloud** runs per site.
  It holds the site's customers, meters, tariffs, and transactions, and it credits meters.

The plugin mirrors the following data into MicroPowerManager:

- **Sites** → registered as MPM Sites, each with a `<site name> Village` village (see [Cluster assignment](#cluster-assignment) below)
- **Meter Models** and **Tariffs** → used to register the site's meters
- **Sales Accounts** → the site's SparkMeter sales accounts
- **Customers** → people with their address and their meter
- **Transactions** → recorded as MicroPowerManager transactions and payments

## Prerequisites

- A SparkMeter **API key** and **API secret** for Koios.
  These are provided by SparkMeter.
- For every site, a **ThunderCloud API token**.
  It is found on a ThunderCloud user with the `api` role, under **API Credentials**.
- At least one **Cluster** registered in MicroPowerManager (Locations → Add Cluster).
  The plugin cannot create Sites without a cluster to attach them to.

## Getting started

1. Enable the **Spark Meter** plugin for your company.
2. Open **Spark Meter → Overview**, enter the **API Key** and **API Secret**, then save.
   The status card shows whether the credentials were authorized.
3. Open **Spark Meter → Sites** and sync the sites from Koios.
4. Edit each site and enter its **ThunderCloud API Token**.
   The **ThunderCloud URL** is filled in from Koios and normally does not need to change.
5. Save the site.
   MicroPowerManager checks the connection, and the site is marked **Is Authenticated** when ThunderCloud accepts the token.

The first time a site connects successfully, the plugin starts the initial sync in this order: Sites → Meter Models → Tariffs → Sales Accounts → Customers → Transactions.
The order matters, because customers can only be synced once their tariff has been synced, and transactions need their customers.

> [!INFO]
> The **SparkMeter API URL** under **Advanced Options** defaults to `https://www.sparkmeter.cloud/api/v0`.
> Only change it if SparkMeter gave you a different URL.

## Cluster assignment

> [!WARNING]
> Every SparkMeter **site** is registered as a **Site under the most recently created Cluster**.
> The plugin does not let you choose which cluster a site belongs to.

Before syncing sites, make sure the cluster you want them grouped under is the **most recently created** one.
If you maintain several clusters and the latest one is not the intended target, create the intended cluster last, then run the sync.

## Selling energy

SparkMeter meters are connected to the internet, so there is no token to type into the meter.
When a payment for a SparkMeter meter succeeds in MicroPowerManager, the plugin credits the meter directly through ThunderCloud.
The transaction then shows the credit status reported by SparkMeter instead of a token.

Unlocking or clearing a meter from MicroPowerManager is not supported.

## Synchronization

After the initial sync, the data stays up to date through background commands:

- `spark-meter:dataSync` syncs sites, meter models, tariffs, sales accounts, customers, and transactions according to their schedule.
- `spark-meter:transactionStatusCheck` runs every five minutes and updates the status of pending transactions.
- `spark-meter:smsNotifier` sends the SMS notifications described below.

Each entity syncs once a day by default.
You can change the interval and the maximum number of attempts per entity under **Spark Meter → Settings → Synchronization Settings**.
The **Sites**, **Meter Models**, **Customers**, **Tariffs**, **Sales Accounts**, and **Transactions** pages also let you sync on demand, and they show an alert with a retry option when a sync fails.

A site is shown as **Is Online** when its ThunderCloud reported a sync within the last 15 minutes.

## SMS notifications

Under **Spark Meter → Settings → SMS Settings** you can configure:

- **Notification Settings**: SMS for successful transactions and low balance warnings, and how old an event may be before its SMS is skipped.
- **Customer Balance Feedback Settings** and **Meter Reset Feedback Settings**: the keywords customers can send by SMS to request their balance or a meter reset.

SMS are sent through the communication plugin configured for your company.

## References

- [SparkMeter](https://www.sparkmeter.io/)
- [SparkMeter API Docs](https://api.sparkmeter.io/#intro)
