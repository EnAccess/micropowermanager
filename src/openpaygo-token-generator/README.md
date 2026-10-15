# OpenPAYGO plugin for MicroPowerManager

This document explains what we built, how we approached it, and how anyone can start the application, run the tests and repeat the demonstration.
It was written by the four-person team that built the plugin for the OSEA hackathon (issue #1713).

Component details live next to the code:

- [Token generator README](../../src/openpaygo-token-generator/README.md): the HTTP contract of the token service.
- [Plugin README](../../src/backend/app/Plugins/OpenPaygo/README.md): backend configuration, issuance, recovery.

## 1. What this is

MicroPowerManager (MPM) manages customers, devices and payments for energy companies.
OpenPAYGO is an open standard for pay-as-you-go devices: a device only runs while it has credit, and credit is added by entering a numeric token.
Each token is computed from the device's secret key, a starting code and a counter, so a token works once and only on the device it was made for.

This plugin lets MPM issue OpenPAYGO tokens.
The flow is: register a device in MPM, record a payment, MPM generates a token, and the operator gives that token to the customer to enter on the device.

## 2. How the work was split

We split the project into four parts, one per person:

- **Person 1, token generation:** a small Node service that wraps the official OpenPAYGO JavaScript encoder (`src/openpaygo-token-generator`).
- **Person 2, backend:** the plugin in `app/Plugins/OpenPaygo`: device configuration, counters, issuance, payment retries, recovery and migrations.
- **Person 3, operator interface:** registering a device, assigning it to a customer, and showing the token and its status.
- **Person 4, testing, integration and documentation:** test vectors, the test-database safeguard, plugin tests, this document and the end-to-end demonstration.

Everything is delivered in one pull request.

## 3. How we approached it

1. **Read the brief and learn MPM.** None of us knew PHP or MPM, so we first learned how MPM is built (Laravel backend, Vue frontend, plugins) and what OpenPAYGO requires.
2. **Agree on the shared decisions before coding.**

   - **Token engine:** reuse the official JavaScript encoder through a small internal HTTP service instead of writing our own cryptography.
   - **Credit conversion:** one integer value unit is `1 / divider` day, with an explicit divider per device.
   - **Counter ownership:** the backend owns one counter per device and stores the exact counter the generator returns.
   - **Payment retries:** one payment gets one stored token, and an uncertain result is never regenerated automatically.
   - **Concurrent payments:** issuance for one device is serialized, and the token, payment link and counter are saved together.
   - **Secrets:** device keys and the shared service key stay in backend-only configuration, encrypted at rest.

3. **Build each part against a contract.** The generator has a documented HTTP contract, so the backend could be written and tested without the UI, and the UI without the simulator.
4. **Test from the first day.** Person 4 built test vectors and the test setup while the others were still building, and merged the parts as they became ready.
5. **Test the real application end to end** with the running app and the simulator.

## 4. How we used AI

We used AI assistance to help us understand the project, to explain the unfamiliar parts.

## 5. Start the application

Run every command in the Ubuntu (WSL2) terminal, not in Git Bash or PowerShell.

### 5.1 One-time setup

1. Install Docker Desktop, turn on **Use the WSL 2 based engine**, and enable WSL integration for Ubuntu under **Settings → Resources → WSL Integration**.
2. In Ubuntu, check that Docker works:

   ```bash
   docker --version
   docker compose version
   ```

3. Clone the repository inside the Ubuntu filesystem (not under `/mnt/c`):

   ```bash
   mkdir -p ~/src && cd ~/src
   git clone https://github.com/EnAccess/micropowermanager.git
   cd micropowermanager
   ```

   If you work from a fork, clone your fork and check out the pull request branch.
4. From the repository root, create the root `.env` and a local service key:

   ```bash
   cp .env.example .env
   openssl rand -hex 32
   ```

   Copy the generated value into `.env`:

   ```text
   OPENPAYGO_GENERATOR_API_KEY=your-generated-value
   ```

   The key is the password between MPM's backend and the token generator.
   It must be 32 to 256 letters, digits, hyphens or underscores.
   Each contributor can use their own local key.
   Keep `.env` private: never commit it, and keep the key out of screenshots and logs.
   If you already have a working `.env`, keep it.
5. For anything else in the one-time setup, see the [development environment guide](../development/development-environment.md#installation).

### 5.2 Start

From the repository root:

```bash
docker compose up
```

The first run takes a while because Docker builds the containers.
Leave the terminal open, and wait for the frontend to print "Compiled successfully".
This one command starts everything, including the token generator.

Then open:

- MPM web app: <http://localhost:8001/>
- Backend: <http://localhost:8000/>
- Token generator: internal only, at `http://openpaygo-generator:3000`, with no host port.

Demo login (local demo data only):

```text
Username: demo_company_admin@example.com
Password: 123123
```

To run in the background, use `docker compose up -d`.
Then use `docker compose logs -f` for logs, `docker compose ps` for status, and `docker compose down` to stop.
Avoid `docker compose down --volumes`, which deletes the development database.

### 5.3 Apply the plugin's database migrations

The plugin adds tenant tables.
Run this once after pulling, and wait for the workers and frontend to restart:

```bash
docker compose exec -u www-data backend-dev php /var/www/html/artisan migrate-tenant --company-id=1
docker compose restart queue-worker-dev scheduler-dev frontend-dev
```

### 5.3a Register the OpenPAYGO manufacturer (first time only)

The OpenPAYGO manufacturer is registered once per installation with an administrator command, not from a screen.
Find the plugin's command name with:

```bash
docker compose exec -u www-data backend-dev php /var/www/html/artisan list | grep -i openpaygo
```

### 5.4 Start only the token generator

This does not run MPM migrations or seeding:

```bash
docker compose up --build --no-deps -d openpaygo-generator
docker compose ps openpaygo-generator
docker compose logs --tail=20 openpaygo-generator
docker compose stop openpaygo-generator
```

If you change the service key, recreate the containers with `docker compose up -d`.
`docker compose restart` does not reload environment values.

### 5.5 Reset the demo data

```bash
docker exec -it -u www-data backend-dev bash
php artisan migrate-tenant:drop-demo-company
php artisan migrate:fresh --seed
```

### 5.6 Get a credit token for a device

With MPM running at <http://localhost:8001/>:

1. Create a customer in MPM.
2. Register a simulator device using that device's serial, secret, starting code, counter and time divider.
3. Create the device's appliance sale under the selected customer.
4. Record a payment.
5. Open the device's transactions and copy the token from **In Return**.
6. Enter the token on the same simulator device's keypad.

Three different secrets are involved, and they are not interchangeable:

- The service key in `.env` authenticates MPM's backend to the token generator.
- The device's secret key generates valid tokens for that one device.
- The payment token is the number you type on the simulator keypad.

## 6. Run the tests

Do these in order.
Each step should pass before the next.

### 6.1 Prerequisites on the Ubuntu host

- Node 22 or later.
- PHP 8.5 with the MySQL driver and common extensions:

  ```bash
  sudo apt update
  sudo apt install -y php8.5-mysql php8.5-mbstring php8.5-xml php8.5-zip php8.5-curl
  ```

- Composer.

### 6.2 Test vectors

The vector generator regenerates reference tokens from the OpenPAYGO JavaScript library and compares them with the committed `vectors.json`.
`--check` only reads; it never writes.

```bash
NODE_PATH="$HOME/opg-vectors/node_modules" \
  node src/backend/tests/Fixtures/openpaygo/vectors.js --check
```

Expected: `Verified 896 vectors, 7 error cases, and 128 skipped library cases.`
The fixture proves that results are reproducible against the library.
It does not prove independent correctness; the simulator is the real validator.
The 128 skipped cases are extended tokens that crash openpaygo-js 0.0.6.

To install the library for this script once:

```bash
mkdir -p ~/opg-vectors && cd ~/opg-vectors
npm install openpaygo@0.0.6
```

### 6.3 Token generator tests

```bash
cd src/openpaygo-token-generator
npm ci --ignore-scripts
npm test
```

Expected: all 70 tests pass.

### 6.4 Backend plugin tests

These tests recreate databases, so the test bootstrap only allows the dedicated `mpm_testing` database (`127.0.0.1:53306` or `mysql_testing:3306`).
They cannot touch the development database.

```bash
docker compose up -d mysql_testing redis
cd src/backend
composer install
APP_ENV=testing APP_CONFIG_CACHE=/tmp/mpm-openpaygo-test-config.php \
  DB_HOST=127.0.0.1 DB_PORT=53306 DB_DATABASE=mpm_testing \
  php artisan test app/Plugins/OpenPaygo/Tests/Unit --stop-on-failure
```

The tests cover repeated issuance, payment retries, overlapping requests, first-in-first-out counters, independent counters per device, blocked configuration recovery, uncertain outcomes and reconciliation.

### 6.5 Style and static analysis (the same checks as CI)

```bash
cd src/backend
composer php-cs-fixer-check
composer larastan
composer rector-check
```

### 6.6 Frontend tests

```bash
cd src/frontend
npm ci
npm test
```

## 7. Manual end-to-end demonstration

We tested the real, running application with the OpenPAYGO simulator.
To repeat it, start MPM (section 5), register a device, and follow this table.
Record the simulator's starting state before the first payment, and the counter after each payment.

1. **Register a device.** Register it in MPM with the simulator device's serial, secret, starting code, counter and time divider, and assign a customer. Expected: the device is saved.
2. **First payment.** Record a payment. Expected: a token, the credited days and status `completed` appear.
3. **Activate.** Enter the token in the simulator. Expected: the device activates and its credit increases.
4. **Second payment.** Record another payment. Expected: a new token with the next counter, accepted by the simulator.
5. **Multiple devices.** Register a second device and pay for both. Expected: separate counters and tokens per device.
6. **Replay.** Enter an already used token again. Expected: the simulator rejects it and credit does not change.
7. **Wrong device.** Enter device A's token on device B. Expected: device B rejects it.
8. **Duplicate payment.** Replay a completed transaction (see "Replay a completed payment" in the plugin README). Expected: no new payment row, the same token and the same counter.
9. **Overlapping payments.** Open one appliance in two tabs and submit two payments. Expected: each gets its own token in order. Enter them in that order in the simulator.
10. **Generation failure.** Stop the generator, make a payment, restart the generator and wait one recovery pass. Expected: the payment stays visible as pending or blocked, then completes once with no double credit.

Token statuses in MPM:

- `pending`: waiting or processing.
- `completed`: the token is saved.
- `blocked`: a configuration problem.
- `uncertain`: a generator request may have completed but was not safely recorded.

Evidence for each scenario (status, counter before and after, simulator response, screenshot without any secret key) is attached to the pull request.

## 8. Known limitations

What the plugin covers: any manufacturer through MPM's native manufacturer plugin, with payment-to-token issuance, saved counters and safe retries.
We do not claim support for every OpenPAYGO device.
These are the limits of what we built:

- **Registration is manual.** Operators copy the simulator values into the form. There is no JSON import and no QR scanning.
- **Device types.** The registration screen creates solar home system (SHS) devices, and the backend issues tokens for SHS and e-bike devices.
- **Serial numbers** are limited to 8 to 15 characters.
- **Restricted keypads.** The backend always sets `restrictedDigitSet` to `false`, although OpenPAYGO supports keypads that only use the digits 1 to 4.
- **Extended tokens and counter-synchronization commands** are not supported.
- **Skipped test cases.** 128 extended-token vectors are skipped because openpaygo-js 0.0.6 crashes on them. This is tracked as a follow-up.
- **No decoder check.** openpaygo-js 0.0.6's decoder is unreliable, so we do not use it as a check.
- **Operator screens.** There is no screen to edit a device's provisioning data or to recover a blocked or uncertain payment. Recovery uses commands.
- **Concurrency.** The tests simulate overlapping requests. A test with truly simultaneous workers has not been done.
- **Performance.** The generator rebuilds the hash chain on every request. Throughput at very high counters has not been measured.
- **Static analysis.** The full-repository static analysis run is still to be confirmed in CI.
- **Delivery to the device is manual.** The operator types the token on the keypad. A completed issuance means a token was saved, not that a device accepted it.

## 9. Troubleshooting

- **`docker: command not found` in Ubuntu:** start Docker Desktop and enable WSL integration for Ubuntu.
- **`wsl -d Ubuntu` fails:** in PowerShell run `wsl --shutdown`, then start Ubuntu again.
- **The page does not load:** check `docker compose ps` and that `docker compose up` is still running.
- **Port 8000 or 8001 is in use:** stop the other program and restart.
- **PHPUnit says `could not find driver`:** run `sudo apt install php8.5-mysql` in Ubuntu.
- **Git shows permission denied after a Docker run:** run `sudo chown -R $USER:$USER .`
- **Git shows changes you did not make:** you are probably in Git Bash. Use the Ubuntu terminal only.
- **Sass deprecation warnings:** they are warnings, not failures. Wait for the build to finish.
- **The generator rejects requests with 401:** the service key differs between `.env` and the containers. Recreate them with `docker compose up -d`.