<p align="center">
  <a href="https://github.com/EnAccess/micropowermanager">
    <img
      src="https://micropowermanager.io/mpmlogo_raw.png"
      alt="OpenSmartMeter"
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

---

# MicroPowerManager

MicroPowerManager (MPM) is a decentralized utility and customer management tool.
Manage customers, revenues and assets with this all-in one Open Source platform.

## Get Started

Explore MPM's capabilities by

- logging into the [Demo Version](https://demo.micropowermanager.io/#/login)
- setting up a local installation following this [guide](https://micropowermanager.io/development/development-environment.html)
- reading MicroPowerManager's documentation on [micropowermanager.io](https://micropowermanager.io/).

## Run locally with Docker Compose

Start Docker Desktop (or Docker Engine) and open a terminal in the repository root.
On Windows, use WSL/Bash for the setup commands below.
After setup, the Compose startup commands also work directly in PowerShell from the repository's Windows path.

### One-time setup for each contributor

Create `.env` only if you do not already have one:

```bash
cp .env.example .env
openssl rand -hex 32
```

Copy the generated value into the root `.env`:

```dotenv
OPENPAYGO_GENERATOR_API_KEY=paste-your-generated-value-here
```

This is your local generator service authentication key, not a device secret or a device credit token.
Each contributor can generate a different key for their own local installation.
Compose supplies the same value to the generator, backend, queue worker and scheduler.
For an existing installation, reuse its service key from `.env.override.micropowermanager-backend` instead of replacing it.
Keep the populated `.env` private; it is ignored by Git.
Commit `.env.example`, which contains no actual key.
If the key is missing, Compose reports a setup error before starting services.

### Start and stop the application

```bash
docker compose up
```

This starts all nine development services: backend, frontend, operator dashboard, scheduler, queue worker, OpenPAYGO generator, MySQL, testing MySQL and Redis.
No `--profile` or `--env-file` argument is needed.

To run in the background and keep your terminal available:

```bash
docker compose up -d
```

Background containers continue running after you close the terminal, while Docker and your computer remain running.
After restarting your computer, start Docker and run `docker compose up -d` again.

Check startup and follow frontend compilation:

```bash
docker compose ps
docker compose logs --tail=30 -f frontend-dev
```

Wait for `Compiled successfully`, then open [the frontend](http://localhost:8001/).
The frontend's `localhost:8080` log message refers to its container port; use port `8001` in your browser.
The backend is available on port `8000` and the operator dashboard on port `8002`.
Services with health checks may initially show `health: starting` while they initialize.
Pressing Ctrl+C while following logs only stops the log viewer.
Pressing Ctrl+C in the foreground `docker compose up` session stops the containers.

To stop background services while keeping their data:

```bash
docker compose stop
```

The default development installation seeds a demo company.
Log in with `demo_company_admin@example.com` and password `123123` when demo seeding is enabled.
See the [development environment guide](docs/development/development-environment.md) for prerequisites and configuration details.

## Generate your own OpenPAYGO device credit token

Three separate values are involved:

| Value                      | Source                                                     | Purpose                                      |
| -------------------------- | ---------------------------------------------------------- | -------------------------------------------- |
| Generator service key      | Generate locally during the `.env` setup above             | Authenticate MPM's requests to the generator |
| Device provisioning secret | Obtain from your device or its simulator provisioning data | Generate tokens valid for that device        |
| Device credit token        | MPM generates it after processing a payment                | Enter the token on that device's keypad      |

The service key must never be entered into the simulator keypad or the device registration secret field.
Do not use another device's provisioning secret or invent a replacement; MPM and the device must use matching provisioning values.

### Prepare the company

The company needs a manufacturer using `OpenPaygoApi`, a PayGO-enabled solar home system appliance, and a customer.
Create the customer under **Customers → +** before registering the device; the registration form selects an existing customer.

For a fresh seeded demo company, if an OpenPAYGO manufacturer is missing, open Tinker:

```bash
docker compose exec -u www-data backend-dev php /var/www/html/artisan tinker
```

Run these commands inside Tinker, then exit:

```php
app(App\Services\DatabaseProxyManagerService::class)->buildDatabaseConnectionDemoCompany();
app(App\Plugins\OpenPaygo\Services\ManufacturerService::class)->register('OpenPAYGO Simulator', 'shs');
exit
```

This setup targets only the seeded demo company.
For another company, its administrator must register the manufacturer in that company's tenant instead.
Manufacturer setup is performed once per company, not once per device.

### Register, pay and retrieve the token

1. Open the [OpenPAYGO hardware simulator](https://enaccess.github.io/OpenPAYGO-HW-sim/) and create or select your test device.
   Obtain that device's serial number, secret key, starting code and current token counter from its provisioning/settings.
   Keep those device values for the next step.
2. In MPM, open **Solar Home Systems → Register OpenPAYGO Device**.
   Select the manufacturer, customer and matching PayGO-enabled appliance.
   Enter the exact serial, 32-character hexadecimal device secret and starting code.
   Use initial counter `0` only for a fresh device that has never accepted tokens.
   The demonstrated browser simulator uses time divider `1`; verify the divider for other firmware.
   Use a new serial for a new device; already registered serials are rejected.
3. Open the selected customer and click **+** under **Sold Appliances**.
   Select the same appliance and registered serial to create its payment plan.
   For a simple test, choose **Energy as a Service**, with daily price `10` and minimum payment `10`.
4. Open the sold appliance's detail page and use its payment button to record `20` through a configured payment provider.
   This buys two days of service.
5. Open the solar home system's detail page and wait for transaction processing to complete.
   Copy the generated token from **In Return** in **Solar Home System Transaction**.
   If processing fails or remains unresolved, inspect the displayed status instead of making another payment to retry it.
6. Enter the copied token into the keypad of the same simulator device and press **OK**.
   Confirm it is accepted and adds two days.
   A second payment of `10` should produce a successive token for one day.
   Re-entering a consumed token should be rejected as a replay; a different device with a different secret should reject it too.

Device keys and counters are stored per device; the integration is not restricted to a particular simulator serial number.
Do not reset an existing counter to zero when generating another token.
For the detailed workflow and automated regression commands, see the [OpenPAYGO operator testing guide](docs/usage-guide/solar-home-systems.md#registering-and-testing-an-openpaygo-device).

## Support

- [OSEA Discord](https://discord.osea-community.org/) (`#micropowermanager` channel)
- [EnAccess](https://enaccess.org/)

## Tech stack

This project

- Is written in PHP 8.0
- It uses Laravel 11.0
- It uses Vue.js 2.6
- It uses Node 18.20.4
- It uses MySQL 8.4
