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

| Environment Variable      | Default                                              | Description                                                                                       |
| ------------------------- | ---------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `VUE_APP_MPM_BACKEND_URL` | `http://localhost:8000` **Required** (for non-local) | The URL of the MicroPowerManager backend. For example `https://demo-backend.micropowermanager.io` |

## Backend

Because MicroPowerManager is based on [Laravel](https://laravel.com/) and Laravel Plugins a lot of behaviour can be configured using environment variables.

For more details see the corresponding plugin's documentation.

### Laravel

| Environment Variable | Default                 | Description                                                                                                                                                                                          |
| -------------------- | ----------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `APP_NAME`           | `MicroPowerManager`     | Display Name of the Application                                                                                                                                                                      |
| `APP_ENV`            | `development`           | Environment identifier for the MicroPowerManager. Can be `local`, `development`, `demo` or `production`. Recommended to set to `production` in production environments.                              |
| `APP_DEBUG`          | `True`                  | Whether or not to run MicroPowerManager in debug mode. Recommended to set to `false` in production environments.                                                                                     |
| `APP_KEY`            | **Required**            | Used by the Illuminate encrypter service to encrypt database entries. In production environments make sure this is a random, 32 character string, otherwise these encrypted strings will not be safe |
| `APP_URL`            | `http://localhost:8000` | Set this to root of MicroPowerManager in deployed environmens (`production` or `demo`).                                                                                                              |

### Database connection

| Environment Variable | Default               | Description                                                                                                                                                        |
| -------------------- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `DB_CONNECTION`      | `micro_power_manager` | Name of the laravel default connection. Should almost never be changed.                                                                                            |
| `DB_HOST`            | **Required**          | Network name the database is accessible from. For example `db` (for name network) or `https://long-url.my-cloud-provider.com/region/db` (for dedicated databases). |
| `DB_PORT`            | `3306`                | Database port.                                                                                                                                                     |
| `DB_DATABASE`        | `micro_power_manager` | Database name.                                                                                                                                                     |
| `DB_USERNAME`        | **Required**          | Database username. For example `root`.                                                                                                                             |
| `DB_PASSWORD`        | **Required**          | Database password. For example `password123!`                                                                                                                      |

### Caching

We recommend running MicroPowerManager with [Redis](https://redis.io/) for caching.

| Environment Variable | Default                         | Description                        |
| -------------------- | ------------------------------- | ---------------------------------- |
| `CACHE_DRIVER`       | `file`                          | Recommended to set to `redis`.     |
| `REDIS_HOST`         | **Required** (If Redis is used) | Network address of the Redis host. |
| `REDIS_PASSWORD`     | **Required** (If Redis is used) | Password for Redis.                |
| `REDIS_PORT`         | 6379                            |                                    |

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

| Environment Variable | Default            | Description                                                                                          |
| -------------------- | ------------------ | ---------------------------------------------------------------------------------------------------- |
| `QUEUE_DRIVER`       | `sync`             | Recommended to set to `database`.                                                                    |
| `QUEUE_CONNECTION`   | `null`             | It's not clear what this environment variables does. Keeping it for historical reasons.              |
| `QUEUES`             | **Required**       | Set it to a string conncatenating all queues. For example `payment, energy_payment, token, sms, ...` |
| `QUEUE_PAYMENT`      | `payment`          | Name of the payment queue.                                                                           |
| `QUEUE_ENERGY`       | `energy_payment`   | Name of the energy payment queue.                                                                    |
| `QUEUE_TOKEN`        | `token`            | Name of the token queue.                                                                             |
| `QUEUE_SMS`          | `sms`              | Name of the SMS queue.                                                                               |
| `QUEUE_HISTORY`      | `history`          | Name of the History queue.                                                                           |
| `QUEUE_REPORT`       | `report_generator` | Name of the Report Generator queue.                                                                  |
| `QUEUE_MISC`         | `misc`             | Name of the miscellaneous queue.                                                                     |

### Basic setup

#### JWT

For more details see [`jwt-auth` documentation](https://jwt-auth.readthedocs.io/en/stable/configuration/).

| Environment Variable | Default      | Description                                                       |
| -------------------- | ------------ | ----------------------------------------------------------------- |
| `JWT_SECRET`         | **Required** | `jwt-auht` secret, ideally created with `php artisan jwt:secret`. |

#### Logging

Set of environment variables that can be used to configure logging and logging providers in MicroPowerManager.

| Environment Variable    | Default                          | Description                                                                                                                                                                 |
| ----------------------- | -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LOG_CHANNEL`           | `stack`                          | Log channel. For more information about available log channels, see [`logging.php`](https://github.com/EnAccess/micropowermanager/blob/main/src/backend/config/logging.php) |
| `LOGGLY_TOKEN`          | **Required** (When using Loggly) | Access token for [Loggly](https://www.loggly.com/).                                                                                                                         |
| `LOG_LEVEL`             | `debug`                          | Level of the logs send to [Loggly](https://www.loggly.com/).                                                                                                                |
| `LOG_SLACK_WEBHOOK_URL` | **Required** (When using Slack)  | Slack Webhook URL                                                                                                                                                           |

#### Email

Configure the following environment variable to enable MicroPowerManager to send email (signup confirmation, password reset, etc...)

| Environment Variable | Default                         | Description                                              |
| -------------------- | ------------------------------- | -------------------------------------------------------- |
| `MAIL_HOST`          | `smtp.mailgun.org`              | Mail server hostname. For example `smtp.mailserver.com`. |
| `MAIL_PORT`          | `587`                           | Mail server port.                                        |
| `MAIL_ENCRYPTION`    | `tls`                           | Mail encryption.                                         |
| `MAIL_USERNAME`      | **Required** (when using email) | Mail server username.                                    |
| `MAIL_PASSWORD`      | **Required** (when using email) | Mail server password.                                    |

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
