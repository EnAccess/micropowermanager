---
order: 1
---

# Get Started with MPM

> [!WARNING]
> The MicroPowerManager documentation is currently getting reworked.
> It might not be fully up-to-date.
> Use this rendered documentation with caution.

<p align="center">
  <a href="https://github.com/EnAccess/micropowermanager">
    <img
      src="/mpmlogo_stylized.svg"
      alt="MicroPowerManager"
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
    src="https://img.shields.io/github/actions/workflow/status/EnAccess/micropowermanager/check-generic.yaml"
  >
  <a href="https://github.com/EnAccess/micropowermanager/blob/main/LICENSE" target="_blank">
    <img
      alt="License"
      src="https://img.shields.io/github/license/EnAccess/micropowermanager"
    >
  </a>
</p>

## MicroPowerManager

MicroPowerManager (MPM) is an open source, free-of-charge customer relationship manager (CRM) software that enables companies in the rural electrification space to manage their portfolio of customers.

It is designed to be suitable to both Mini-Grid operators as well as Solar-Some-System (SHS) and e-bike distributors.
The software was originally developed by INENSUS GmbH and is now hosted and co-developed by EnAccess.
This User Manual is designed for persons with a basic understanding of what a CRM tool, Mini-Grid and SHS are.

The MicroPowerManager package includes:

1. The **website interface** (where company-level data in regards to gathered revenues and potentially technical operational data) can be accessed.
   Customer complaints and technical faults can also be managed in a centralized manner via this interface.
   "Bulk-registration" of an existing portfolio of customers (transferring customer data from legacy systems to MicroPowerManager software) can be offered by EnAccess as-a-service.

2. **MPM Field App**: a single cross-platform app (Android) for the team of agents on site.
   It lets agents register customers, sell appliances, and collect payments — online or offline — and manually generate STS tokens where customers cannot do so themselves with their own phones.
   See [The Field App](/usage-guide/android-apps) for details.

## Get Started

Explore MPM's capabilities by

- logging into the [Demo Version](https://demo.micropowermanager.io/#/login)
- setting up a local installation following this [guide](https://micropowermanager.io/development/development-environment.html)
- reading MicroPowerManager's documentation on [micropowermanager.io](https://micropowermanager.io/).

## Support

- [OSEA Discord](https://discord.osea-community.org/) (`#micropowermanager` channel)
- [EnAccess](https://enaccess.org/)

## Tech stack

This project

- Is written in PHP 8.5
- It uses Laravel 12.0
- It uses Vue.js 2.6
- It uses Node 18.20.4
- It uses MySQL 8.4
