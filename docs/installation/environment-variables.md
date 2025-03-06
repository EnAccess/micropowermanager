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

| Environment Variable | Default | Description                                                                                                                                                                                        |
| -------------------- | ------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `MPM_LOAD_DEMO_DATA` | `false` | Whether or not the demo data should be loaded when the MicroPowerManager starts for the first time. Recommended for local development and demo environments. Optional for production environments. |

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

### Queue

| Environment Variable | Default            | Description                         |
| -------------------- | ------------------ | ----------------------------------- |
| `QUEUE_DRIVER`       | `sync`             | Recommended to set to `database`.   |
| `QUEUE_PAYMENT`      | `payment`          | Name of the payment queue.          |
| `QUEUE_ENERGY`       | `energy_payment`   | Name of the energy payment queue.   |
| `QUEUE_TOKEN`        | `token`            | Name of the token queue.            |
| `QUEUE_SMS`          | `sms`              | Name of the SMS queue.              |
| `QUEUE_HISTORY`      | `history`          | Name of the History queue.          |
| `QUEUE_REPORT`       | `report_generator` | Name of the Report Generator queue. |
| `QUEUE_MISC`         | `misc`             | Name of the miscellaneous queue.    |

### Basic setup

#### Logging

Set of environment variables that can be used to configure logging and logging providers in MicroPowerManager.

| Environment Variable    | Default                                 | Description                                                                                                                                                                 |
| ----------------------- | --------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LOG_CHANNEL`           | `stack`                                 | Log channel. For more information about available log channels, see [`logging.php`](https://github.com/EnAccess/micropowermanager/blob/main/src/backend/config/logging.php) |
| `LOG_LEVEL`             | `debug`                                 | Level of the logs.                                                                                                                                                          |
| `LOG_SLACK_WEBHOOK_URL` | **Required** (When using Slack Logging) | Slack Webhook URL. This require a [Slack incoming webhook](https://api.slack.com/messaging/webhooks). If `LOG_SLACK_WEBHOOK_URL` is provided Slack logging will be enabled. |
| `LOG_SLACK_USERNAME`    | `Laravel Log`                           | Slack Webhook URL                                                                                                                                                           |
| `LOG_SLACK_EMOJI`       | `:boom:`                                | Slack Webhook URL                                                                                                                                                           |

#### Email

Configure the following environment variable to enable MicroPowerManager to send email via SMTP.
These configure instance level email sent to tenants, for example signup confirmation, password reset, etc...

| Environment Variable        | Default                                        | Description                                                                                                                                     |
| --------------------------- | ---------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `MAIL_SMTP_HOST`            | `smtp.mailgun.org`                             | Mail server hostname. For example `smtp.mailserver.com`.                                                                                        |
| `MAIL_SMTP_PORT`            | `587`                                          | Mail server port.                                                                                                                               |
| `MAIL_SMTP_ENCRYPTION`      | `tls`                                          | Mail encryption.                                                                                                                                |
| `MAIL_SMTP_AUTH`            | `false`                                        | Whether to use SMTP Auth.                                                                                                                       |
| `MAIL_USERNAME`             | **Required** (when `MAIL_SMTP_AUTH` is `true`) | The username used in SMTP Auth.                                                                                                                 |
| `MAIL_PASSWORD`             | **Required** (when `MAIL_SMTP_AUTH` is `true`) | The password used in SMTP Auth.                                                                                                                 |
| `MAIL_SMTP_DEFAULT_SENDER`  | **Required**                                   | The email used in `from` and `replyTo` fields of sent email. Note: Depending on the mailserver this might be different from SMTP Auth username. |
| `MAIL_SMTP_DEFAULT_MESSAGE` | `Please do not reply to this email`            | Default message body of emails.                                                                                                                 |
| `MAIL_SMTP_DEBUG_LEVEL`     | `0`                                            | Debug level used in [PHPMailer](https://github.com/PHPMailer/PHPMailer/). `0` No output, `4` Noisy, low-level data output, rarely needed.       |

### MPM Plugins

Certain MicroPowerManager plugins require configuration before they can be used.
Find below a reference of configurations which are required if the corresponding plugin is used.

#### Airtel

For detailed information see [Airtel Developer Documentation](https://developers.airtel.africa/developer).

| Environment Variable | Default                            | Description                                 |
| -------------------- | ---------------------------------- | ------------------------------------------- |
| `AIRTEL_REQUEST_URL` | **Required** (when plugin is used) | The Airtel service URL.                     |
| `AIRTEL_USERNAME`    | **Required** (when plugin is used) | The Airtel username.                        |
| `AIRTEL_PASSWORD`    | **Required** (when plugin is used) | The Airtel password.                        |
| `AIRTEL_IPS`         | `[]`                               | List of IP whitelisted for Airtel services. |

#### Calin STS Meters

For detailed information see [Calin Meter Developer Documentation](https://www.szcalinmeter.com/).

| Environment Variable | Default                            | Description                                              |
| -------------------- | ---------------------------------- | -------------------------------------------------------- |
| `CALIN_CLIENT_URL`   | **Required** (when plugin is used) | Calin Meter client URL used for generating STS tokens.   |
| `CALIN_USER_ID`      | **Required** (when plugin is used) | Calin Meter API username used for generating STS tokens. |
| `CALIN_KEY`          | **Required** (when plugin is used) | Calin Meter API key used for generating STS tokens.      |

If you meters are used which can send their consumption data to Calin's
API the following environment variables need to be set.

| Environment Variable | Default                            | Description                                                |
| -------------------- | ---------------------------------- | ---------------------------------------------------------- |
| `METER_DATA_URL`     | **Required** (when plugin is used) | Calin Meter API URL used for consumption data upload.      |
| `METER_DATA_KEY`     | **Required** (when plugin is used) | Calin Meter API key used for consumption data upload.      |
| `METER_DATA_USER`    | **Required** (when plugin is used) | Calin Meter API username used for consumption data upload. |

#### Calin Smart Meters

For detailed information see [Calin Smart Meter Developer Documentation](https://www.szcalinmeter.com/).

| Environment Variable           | Default                            | Description                                                               |
| ------------------------------ | ---------------------------------- | ------------------------------------------------------------------------- |
| `CALIN_SMART_COMPANY_NAME`     | **Required** (when plugin is used) | Calin Smart Meter company name used for communication with Calin API.     |
| `CALIN_SMART_PURCHASE_API_URL` | **Required** (when plugin is used) | Calin Smart Meter Purchase API URL used for communication with Calin API. |
| `CALIN_SMART_CLEAR_API_URL`    | **Required** (when plugin is used) | Calin Smart Meter Clear API URL used for communication with Calin API.    |
| `CALIN_SMART_USER_NAME`        | **Required** (when plugin is used) | Calin Smart Meter username used for communication with Calin API.         |
| `CALIN_SMART_PASSWORD`         | **Required** (when plugin is used) | Calin Smart Meter password used for communication with Calin API.         |
| `CALIN_SMART_PASSWORD_VENT`    | **Required** (when plugin is used) | Calin Smart Meter password vent used for communication with Calin API.    |

#### Vodacom

Vodacom integration requires a VPN tunnel with Vodacom infrastructure.
For detailed information see [Vodacom Developer Documentation](https://business.m-pesa.com/vodacom-tanzania/business-onboarding-tanzania/)

| Environment Variable            | Default                            | Description                 |
| ------------------------------- | ---------------------------------- | --------------------------- |
| `VODACOM_SPID`                  | **Required** (when plugin is used) | Vodacom SPID.               |
| `VODACOM_SPPASSWORD`            | **Required** (when plugin is used) | Vodacom SPPASSWORD.         |
| `VODACOM_IPS`                   | **Required** (when plugin is used) | Vodacom IPs.                |
| `VODACOM_REQUEST_URL`           | **Required** (when plugin is used) | Vodacom Request API URL.    |
| `VODACOM_BROKER_CRT`            | **Required** (when plugin is used) | Path to broker `.crt` file. |
| `VODACOM_SLL_KEY`               | **Required** (when plugin is used) | Path to `.key` file.        |
| `VODACOM_CERTIFICATE_AUTHORITY` | **Required** (when plugin is used) | Path to `.cer` file.        |
| `VODACOM_SSL_CERT`              | **Required** (when plugin is used) | Path to `.pem` file.        |

#### SunKing

For detailed information see [SunKing Developer Documentation](https://sunking.com/)

| Environment Variable | Default                            | Description                                                                      |
| -------------------- | ---------------------------------- | -------------------------------------------------------------------------------- |
| `SUNKING_API_URL`    | **Required** (when plugin is used) | SunKing API URL. For example `https://dev.assetcontrol.central.glp apps.com/v2`. |

#### WaveMoney

For detailed information see [WaveMoney Developer Documentation](https://partners.wavemoney.com.mm/documentation)

| Environment Variable | Default                            | Description                                                                 |
| -------------------- | ---------------------------------- | --------------------------------------------------------------------------- |
| `WAVEMONEY_API_URL`  | **Required** (when plugin is used) | WaveMoney API URL. For example `https://preprodpayments.wavemoney.io:8107`. |

#### BingMaps

> [!WARNING]
> BingMaps is deprecated in favour of OpenStreetMap.
> It's not recommended for production use.

| Environment Variable | Default                              | Description                                                                                                               |
| -------------------- | ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------- |
| `BINGMAP_API_URL`    | **Required** (when BingMaps is used) | BingMaps API URL (including the API key). For example `https://dev.virtualearth.net/REST/v1/Imagery/Metadata/Aerial?key=` |
