---
order: 3
---

# Configuration for Production

> [!INFO]
> This page provides information how to **configure** an installation for MicroPowerManager.

In this section we focus on the most common instance-level settings, which are required to run MicroPowerManager in a common set up.

An installation of MicroPowerManager can be customised using environment variables.
We will mention the ones relevant to the corresponding integrations below.
The full list of all environment variables can be found [here](environment-variables.md).

## Prerequisite

We assume you know how you set environment variables.
How this will be achieved depends on the deployment scenario.

## Email

For tenant and user management, which is a core feature of MicroPowerManager it is required to have access to a mail server to send Welcome emails and required communications.

It is recommended to use a third party mail service which provides a mail server.

For example Mailgun, Google Workspace, etc..

Set the following environment variables.

- `MAIL_SMTP_HOST`
- `MAIL_SMTP_AUTH`
- `MAIL_SMTP_USERNAME`
- `MAIL_SMTP_PASSWORD`
- `MAIL_SMTP_DEFAULT_SENDER`

Optional, to debug Email you can also set

- `MAIL_SMTP_DEBUG_LEVEL`

## Agent Apps

Placeholder, do this, do that
