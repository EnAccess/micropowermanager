---
order: 4
---

# Environment Variables

> [!INFO]
> The MicroPowerManager follows [The Twelve-Factor App](https://12factor.net/config) principle.
> MicroPowerManager's configuration is separated from the code base and can be done via **environment variables**.

In this document we describe the most relevant environment variables and highlight the ones which are **required** to be populated for MicroPowerManager to function properly.

## Frontend

### Backend connection

| Environment Variable | Default      | Description                                                                                                                                                                                                                                                                                              |
| -------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `MPM_ENV`            | **Required** | Environment identifier for the MicroPowerManager frontend. Can be `development`, `demo` or `production`. Recommended to set to `production` in production environments. Note: This is different from the builtin [`NODE_ENV`](https://cli.vuejs.org/guide/mode-and-env.html#modes) environment variable. |
| `MPM_BACKEND_URL`    | **Required** | The URL of the MicroPowerManager backend. For example `http://localhost:8000` (for non-local) or `https://demo-backend.micropowermanager.io` (for production).                                                                                                                                           |

## Backend

Because MicroPowerManager is based on [Laravel](https://laravel.com/) and Laravel Plugins a lot of behaviour can be configured using environment variables.

For more details see the corresponding plugin's documentation.

### Laravel

| Environment Variable | Default                 | Description                                                                                                                                                                                          |
| -------------------- | ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `APP_NAME`           | `MicroPowerManager`     | Display Name of the Application                                                                                                                                                                      |
| `APP_ENV`            | `development`           | Environment identifier for the MicroPowerManager backend. Can be `development`, `demo` or `production`. Recommended to set to `production` in production environments.                               |
| `APP_DEBUG`          | `True`                  | Whether or not to run MicroPowerManager in debug mode. Recommended to set to `false` in production environments.                                                                                     |
| `APP_KEY`            | **Required**            | Used by the Illuminate encrypter service to encrypt database entries. In production environments make sure this is a random, 32 character string, otherwise these encrypted strings will not be safe |
| `APP_URL`            | `http://localhost:8000` | Set this to root of MicroPowerManager in deployed environments (`production` or `demo`).                                                                                                             |

### MicroPowerManager

These environment variables control how the MicroPowerManager behaves as an application.

| Environment Variable | Default | Description |
|---|---|---|
| `MPM_FRONTEND_URL` | **Required** | The URL where MicroPowerManager frontend is located, this is **required** for email password reset and other related functionality that requires Knowledge of the frontend. |
| `MPM_LOAD_DEMO_DATA` | `false` | Whether or not the demo data should be loaded when the MicroPowerManager starts for the first time. Recommended for local development and demo environments. Optional for production environments. |
| `MPM_FORCE_OPTIMIZE` | `false` | Force Laravel optimization (`php artisan optimize`) on container startup even when not in production mode. Set to `true` to enable. Optimization runs automatically when `APP_ENV=production`. |

### JSON Web Token Authentication (jwt-auth)

For more details see [`jwt-auth` documentation](https://jwt-auth.readthedocs.io/en/stable/configuration/).

| Environment Variable | Default      | Description                                                       |
| -------------------- | ------------ | ----------------------------------------------------------------- |
| `JWT_SECRET`         | **Required** | `jwt-auht` secret, ideally created with `php artisan jwt:secret`. |

### Database connection

| Environment Variable | Default               | Description                                                                                                                                              |
| -------------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `DB_CONNECTION`      | `micro_power_manager` | Name of the laravel default connection. Should almost never be changed.                                                                                  |
| `DB_HOST`            | **Required**          | Network host name the database is accessible from. For example `db` (for local) or `long-url.my-cloud-provider.com/region/db` (for dedicated databases). |
| `DB_PORT`            | `3306`                | Database port.                                                                                                                                           |
| `DB_DATABASE`        | `micro_power_manager` | Database name.                                                                                                                                           |
| `DB_USERNAME`        | **Required**          | Database username. For example `root`.                                                                                                                   |
| `DB_PASSWORD`        | **Required**          | Database password. For example `password123!`                                                                                                            |

### Redis connection

We recommend running MicroPowerManager with [Redis](https://redis.io/).

| Environment Variable | Default                         | Description                                                                                                                                                          |
| -------------------- | ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `REDIS_HOST`         | **Required** (If Redis is used) | Network host name the Redis cluster is accessible from. For example `redis` (for local) or `long-url.my-cloud-provider.com/region/db` (for dedicated Redis cluster). |
| `REDIS_PASSWORD`     | **Required** (If Redis is used) | Password for Redis.                                                                                                                                                  |
| `REDIS_PORT`         | 6379                            |                                                                                                                                                                      |

### Caching

We recommend running MicroPowerManager with [Redis](https://redis.io/) for caching.

| Environment Variable | Default | Description                    |
| -------------------- | ------- | ------------------------------ |
| `CACHE_DRIVER`       | `file`  | Recommended to set to `redis`. |

### Session management

| Environment Variable | Default | Description |
| -------------------- | ------- | ----------- |
| `SESSION_DRIVER`     | `file`  |             |
| `SESSION_LIFETIME`   | 120     |             |

### Broadcasting

We recommend running MicroPowerManager with [Pusher Channels](https://pusher.com/docs/channels/) for broadcasting.

| Environment Variable | Default                          | Description                           |
| -------------------- | -------------------------------- | ------------------------------------- |
| `BROADCAST_DRIVER`   | `null`                           | Recommended to set to `pusher`.       |
| `PUSHER_APP_ID`      | **Required** (If Pusher is used) | Pusher App id.                        |
| `PUSHER_APP_KEY`     | **Required** (If Pusher is used) | Pusher App key.                       |
| `PUSHER_APP_SECRET`  | **Required** (If Pusher is used) | Pusher App secret.                    |
| `PUSHER_APP_CLUSTER` | **Required** (If Pusher is used) | Pusher App cluster. For example `eu`. |

### Basic setup

#### Logging

Set of environment variables that can be used to configure logging and logging providers in MicroPowerManager.

| Environment Variable | Default     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| -------------------- | ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `LOG_CHANNEL`        | `mpm_stack` | The default value `mpm_stack` configures a split logging where error logs go to `STDERR` and application info and debug logs to `STDOUT`. Additional log channels to external logging solutions are automatically enabled, if corresponding environment variables are configured, see below. For more information about available log channels, see [`logging.php`](https://github.com/EnAccess/micropowermanager/blob/main/src/backend/config/logging.php). |
| `LOG_LEVEL`          | `debug`     | General log level of the application. Note, that external logging systems may define their log level seperately. For example, it might be desired to only send critical errors to Slack, even if MicroPowerManager runs with an elevanted log level of `info`. Recommended to set this at least `info` or hire in normal operations of a production environment.                                                                                             |

Slack

| Environment Variable    | Default                                 | Description                                                                                                                                                                              |
| ----------------------- | --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LOG_SLACK_LEVEL`       | `critical`                              | The log level sent to Slack. URL                                                                                                                                                         |
| `LOG_SLACK_WEBHOOK_URL` | **Required** (When using Slack Logging) | Slack Webhook URL for logging. This requires a [Slack incoming webhook](https://api.slack.com/messaging/webhooks). If `LOG_SLACK_WEBHOOK_URL` is provided Slack logging will be enabled. |
| `LOG_SLACK_USERNAME`    | `Laravel Log`                           | Slack Webhook Username                                                                                                                                                                   |
| `LOG_SLACK_EMOJI`       | `:boom:`                                | Slack Webhook Emoji                                                                                                                                                                      |

#### Email

Configure the following environment variable to enable MicroPowerManager to send email via SMTP.
These configure instance level email sent to tenants, for example signup confirmation, password reset, etc...

| Environment Variable | Default             | Description                                                   |
| -------------------- | ------------------- | ------------------------------------------------------------- |
| `MAIL_FROM_ADDRESS`  | `hello@example.com` | Global "from" address for all emails sent by the application. |
| `MAIL_FROM_NAME`     | `Example`           | Global "from" name for all emails sent by the application.    |

Using SMTP Email Service

To Configure email with SMTP follow the official [guidge](https://laravel.com/docs/12.x/mail#driver-prerequisites).

#### Laravel Horizon and Horizon Dashboard

MicroPowerManager internally uses [Laravel Horizon](https://laravel.com/docs/12.x/horizon) to manage it's queue workers.

By default Horizon Dashboard will not be accessible in non-development environments.
Configure the below environment variables to enable HTTP Basic Auth.
Only if both `HORIZON_BASIC_AUTH_USERNAME` and `HORIZON_BASIC_AUTH_PASSWORD` are set, HTTP Basic Auth is enabled.

| Environment Variable          | Default | Description                                      |
| ----------------------------- | ------- | ------------------------------------------------ |
| `HORIZON_BASIC_AUTH_USERNAME` | `null`  | HTTP Basic Auth Username for Horizon Dashboard.  |
| `HORIZON_BASIC_AUTH_PASSWORD` | `null`  | HTTP Basic Auth Username for Horizon Dashboard.. |

Configure Horizon notifications

| Environment Variable        | Default | Description                                                                                                                                                                                                                                                                            |
| --------------------------- | ------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `HORIZON_SLACK_WEBHOOK_URL` | `null`  | Slack Webhook URL for Horizon notifications. This requires a [Slack incoming webhook](https://api.slack.com/messaging/webhooks). If `HORIZON_SLACK_WEBHOOK_URL` is provided Horizon Slack notifications will be enabled. **Note:** Can be the same webhook as `LOG_SLACK_WEBHOOK_URL`. |

### MPM Plugins

Certain MicroPowerManager plugins require configuration before they can be used.
Find below a reference of configurations which are required if the corresponding plugin is used.

#### SunKing

For detailed information see [SunKing Developer Documentation](https://sunking.com/)

| Environment Variable       | Default                                                                              | Description                                                                                                   |
| -------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| `SUNKING_AUTH_DEFAULT_URL` | `https://auth.central.glpapps.com/auth/realms/glp-dev/protocol/openid-connect/token` | Default authorisation URL used when tenants activate the SunKing plugin on the instance of MicroPowerManager. |
| `SUNKING_API_DEFAULT_URL`  | `https://dev.assetcontrol.central.glpapps.com/v2`                                    | Default API URL used when tenants activate the SunKing plugin on the instance of MicroPowerManager.           |

#### WaveMoney

For detailed information see [WaveMoney Developer Documentation](https://partners.wavemoney.com.mm/documentation)

| Environment Variable | Default                            | Description                                                                 |
| -------------------- | ---------------------------------- | --------------------------------------------------------------------------- |
| `WAVEMONEY_API_URL`  | **Required** (when plugin is used) | WaveMoney API URL. For example `https://preprodpayments.wavemoney.io:8107`. |
