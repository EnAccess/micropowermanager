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

Set the following environment variables to configure the Email provider

- `MAIL_SMTP_HOST`
- `MAIL_SMTP_DEFAULT_SENDER`

If your Email provider requires authentication, also populate:

- `MAIL_SMTP_AUTH`
- `MAIL_SMTP_USERNAME`
- `MAIL_SMTP_PASSWORD`

Alternatively, when using an Email provider with IP whitelisting:

- Make sure cluster egress is using a static IP.
  For example of GKE, see [`egress-nat-policy.yaml`](https://github.com/EnAccess/micropowermanager/blob/main/k8s/base/gcp_gke/kustomization.yaml).
- Whitelist the NAT Gateway's static IP in the Email provider.

Optionally, to debug Email you can set:

- `MAIL_SMTP_DEBUG_LEVEL`

### Testing Email integration

A quick and dirty way to test sending of email, is to open a Laravel Tinker shell

```sh
php artisan tinker
```

Then

```php
$mailHelper = app(App\Helpers\MailHelper::class);
$mailHelper->sendPlain('test@example.org', '[TEST] Welcome to MicroPowerManager', 'lorem ipsum');
$mailHelper->sendViaTemplate('test@example.org', '[TEST] Welcome to MicroPowerManager', 'templates.mail.register_welcome', ['userName' => 'Lorem', 'companyName' => 'Ipsum']);
```

## Logging

> [!INFO]
> This section is optional, but recommended.

By default we are running MicroPowerManager using `debug` logging level.
In normal operation it is recommended to set at least `info` using

- `LOG_LEVEL`

When debugging errors or problems it can be helpful to temporarily revert `LOG_LEVEL` to `debug`.

Set up a logging channel which allows you to monitor critical errors of the application in realtime.

Currently, we support Slack logging using [incoming webhooks](https://api.slack.com/messaging/webhooks).
Set the following environment variables

- `LOG_SLACK_WEBHOOK_URL`

By default, we are logging `CRITICAL` errors to Slack.

### Testing logging setup

To test logging setup run the Artisan logging test command

```sh
php artisan log:test
```

## Agent Apps

Placeholder, do this, do that
