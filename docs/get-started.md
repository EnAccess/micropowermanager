---
order: 1
---

# Get Started with MPM

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

MicroPowerManager (MPM) is an open source platform for managing a utility business in the rural electrification space.
It handles the full operation: customers, revenues, payments, and devices such as smart meters and solar home systems.

MPM is built for mini-grid operators and for solar home system (SHS) and e-bike distributors alike.
This manual assumes a basic familiarity with CRM tools, mini-grids, and SHS.

MPM has two parts:

1. The **web interface** is the central dashboard for the business.
   Use it to oversee customers, track revenues and payments, monitor devices, and handle customer complaints and technical faults in one place.

2. The **MPM Field App** is a mobile app (Android and iOS) for agents working on site.
   It lets them register customers, sell appliances, and collect payments (online or offline) and generate STS tokens by hand when customers cannot do so from their own phones.
   See [The MPM Field App](/usage-guide/field-app) for details.

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
