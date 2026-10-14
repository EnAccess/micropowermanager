# OpenPAYGO token generator

This component generates OpenPAYGO tokens using the official JavaScript encoder.
It exposes an authenticated internal HTTP service, a JavaScript function, and a JSON command-line interface.
MPM includes a PHP manufacturer adapter, tenant-scoped device configuration, issuance reservations, and recovery.
The adapter supports appliance unlock, reset, and time-credit issuance for devices with a configured time divider.
The complete payment-to-physical-device workflow has not been verified.

## Run the HTTP service with Docker

Run from the repository root.
Building downloads the Node image and installs the locked dependencies inside the image.
The targeted startup command starts only the generator, without running MPM migrations or seeding.

First complete the one-time root `.env` setup in the [development environment guide](../../docs/development/development-environment.md#installation).
Plain `docker compose up` starts all development services, including this generator.
To build and start only the generator:

```bash
docker compose up --build --no-deps -d openpaygo-generator
docker compose ps openpaygo-generator
docker compose logs --tail=20 openpaygo-generator
```

The service runs as a nonroot user on the Compose network with a read-only filesystem.
No port is published to the host.
Its internal base URL is `http://openpaygo-generator:3000`, reachable from MPM's backend and queue worker containers.
It starts with normal development Compose startup without a profile.
A missing or weak service key stops the process before it starts listening.

`OPENPAYGO_GENERATOR_API_KEY` is the shared password between the backend and this service.
It is different from each device's `secretKeyHex`.
Use the same service key in both callers and the generator.
It must contain 32-256 letters, digits, hyphens, or underscores.
Keep it out of Git, screenshots, logs, and browser code.

The root `.env` supplies the service key persistently across terminal sessions.
Development Compose passes the same key to the generator, backend, queue worker and scheduler, overriding any older key in `.env.override.micropowermanager-backend`.
The generator URL is set to `http://openpaygo-generator:3000` for those backend services.
If the key changes, recreate the affected containers with `docker compose up -d`; `docker compose restart` does not reload environment values.

To stop only this service:

```bash
docker compose stop openpaygo-generator
```

## HTTP contract

### Generate a token

Send `POST /generate` with these headers:

```text
Authorization: Bearer <shared service key>
Content-Type: application/json
```

Example body using public reference-test configuration:

```json
{
  "secretKeyHex": "bc41ec9530f6dac86b1a29ab82edc5fb",
  "startingCode": 516959010,
  "counter": 1,
  "tokenType": "ADD_TIME",
  "value": 1,
  "restrictedDigitSet": false
}
```

A successful response has HTTP status 200:

```json
{"token":"588224011","nextCounter":2}
```

Responses are marked `Cache-Control: no-store`.
The endpoint does not store keys, counters, devices, or transactions.
It does not log request bodies or authentication headers.
It does not confirm that a device accepted the returned token.

| Field | Meaning |
| --- | --- |
| `secretKeyHex` | Device key: exactly 32 hexadecimal characters |
| `startingCode` | Device's configured starting code, integer 0-999999999 |
| `counter` | Previous issued counter, integer 0-100000 |
| `tokenType` | `ADD_TIME` (default), `SET_TIME`, or `DISABLE_PAYG` |
| `value` | Raw integer credit units, 0-995; omitted for `DISABLE_PAYG` |
| `restrictedDigitSet` | Boolean, default false; true uses digits 1-4 |

For ADD_TIME, a device time divider of `d` means each integer value unit grants `1 / d` day.
The backend therefore converts MPM's whole credit days to the integer generator value with `creditDays * d`; this conversion is exact and does not round.
OpenPAYGO defines valid dividers as integers 1-255.
MPM requires an explicit per-device divider and does not assume the protocol's optional default of 1, so existing configurations without one remain blocked until provisioned.
Provision it explicitly with the fifth `timeDivider` argument to `OpenPaygoDeviceConfigurationService::saveForDevice()` after the device setup sheet or manufacturer specification confirms the value.
The generator value must be between 1 and 995 for paid credit; MPM rejects zero or out-of-range credit rather than clamping it.
Payment-to-time conversion and MPM payment rounding belong to the backend.
Unknown fields are rejected.
Extended tokens and counter-synchronization commands are not supported in this slice.

Preserve `token` as a string, including leading zeroes.
Store the exact returned `nextCounter`; do not increment it independently.
Depending on token type and counter parity, the encoder advances by one or two.
Different devices have separate counters.

### Errors and health

Errors use `{"error":"message"}` and do not echo device secrets.
Only uncompressed JSON request bodies up to 4096 bytes are accepted.

| HTTP status | Meaning | Caller action |
| --- | --- | --- |
| 400 | Malformed JSON | Correct serialization |
| 401 | Missing or invalid service authentication | Check shared service key |
| 404 | Unknown route | Check configured URL |
| 405 | Unsupported method | Use POST for generation |
| 413 | Body exceeds 4096 bytes | Send only the documented fields |
| 415 | Unsupported content type or encoding | Send uncompressed application/json |
| 422 | Invalid token configuration or credit | Correct input; show meaningful feedback |
| 500 | Unexpected generation failure | Mark processing failure; investigate or retry the same input |

`GET /health` returns `{"status":"ok"}` without authentication for Docker healthchecks.
A healthy listener does not prove that provisioning data is correct or that a simulator accepts a particular token.
HTTP request uploads time out after 10 seconds; headers time out after 5 seconds.

Generation rebuilds the hash chain synchronously from the beginning.
The 100000 input-counter ceiling limits workload, not the protocol itself.
The returned counter can exceed this ceiling by up to two; a later generation rejects that counter.
The integration must surface exhaustion before accepting further payments.
HTTP timeouts do not interrupt an active encoder calculation.
Production throughput and high-counter concurrency have not been characterized.

## Tests and local verification

The service image uses Node 22.
The OpenPAYGO dependency is pinned to published version 0.0.6.
With Node 22 or later installed locally:

```bash
cd src/openpaygo-token-generator
npm ci --ignore-scripts
npm test
```

Installation downloads packages and writes `node_modules`.
It does not access MPM's database.

After building the service image, run its tests without starting MPM:

```bash
docker run --rm --network none --read-only --entrypoint npm \
  micropowermanager-openpaygo-generator:local test
```

Tests compare generated tokens and counters against published reference examples.
They also verify successive issuance, validation, the CLI, authentication, JSON handling, size limits, HTTP errors, and secret-free unexpected-error responses.
They do not prove physical-device compatibility.
The published 0.0.6 decoder is not used as a verification oracle because its counter-return and history-handling code has defects.
Current upstream source differs from this release.

To verify the running endpoint from inside its container using public test data:

```bash
docker compose exec -T openpaygo-generator node <<'JS'
const assert = require("node:assert/strict")

async function main() {
  const response = await fetch("http://127.0.0.1:3000/generate", {
    method: "POST",
    headers: {
      Authorization: "Bearer " + process.env.OPENPAYGO_GENERATOR_API_KEY,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      secretKeyHex: "bc41ec9530f6dac86b1a29ab82edc5fb",
      startingCode: 516959010,
      counter: 1,
      tokenType: "ADD_TIME",
      value: 1,
    }),
  })
  assert.equal(response.status, 200)
  const result = await response.json()
  assert.deepEqual(result, { token: "588224011", nextCounter: 2 })
  console.log(result)
}

main().catch(() => {
  console.error("HTTP verification failed.")
  process.exitCode = 1
})
JS
```

Verification on 2026-10-07: all 70 tests passed in the Node 22 service image with external networking disabled.
The development Compose configuration was validated.
Laravel's HTTP client in the existing backend and queue worker containers returned the expected public reference token and rejected unauthenticated requests.
Those connection checks did not boot MPM or access its database.
The temporary generator container was removed after verification.
The PHP manufacturer plugin exists, but these checks did not verify the full payment-to-simulator workflow.

The CLI remains available through `node cli.js`.
It reads one JSON object from standard input and returns one JSON result on standard output.
Errors return JSON on standard error with exit status 1.
Do not put real device keys directly in shell commands or source files.

## PHP backend integration status

The backend and queue worker read these client settings:

```text
OPENPAYGO_GENERATOR_URL=http://openpaygo-generator:3000
OPENPAYGO_GENERATOR_API_KEY=<same shared key used by the generator>
```

Backend and queue worker must receive the same settings.
Recreating existing MPM containers to change their environment can run migrations and demo seeding through their entrypoints.
Coordinate that step separately; starting only the generator does not perform those operations.

Laravel defines these values in `src/backend/config/services.php` under `services.openpaygo_generator`:

```php
'openpaygo_generator' => [
    'url' => env('OPENPAYGO_GENERATOR_URL', 'http://openpaygo-generator:3000'),
    'api_key' => env('OPENPAYGO_GENERATOR_API_KEY'),
    'connect_timeout' => env('OPENPAYGO_GENERATOR_CONNECT_TIMEOUT', 2),
    'timeout' => env('OPENPAYGO_GENERATOR_TIMEOUT', 8),
],
```

`OpenPaygoGeneratorClient` posts validated generator input to `/generate`, validates the response, and redacts transport failures.
Do not log outgoing request bodies or authorization headers.

The PHP configuration service stores device secrets encrypted in the tenant database.
Reservations pin the operation and counter before generation.
The generator response is first persisted as a recoverable result; a follow-on tenant-database transaction writes the token, exact next counter, and completed reservation together.
An uncertain HTTP outcome is not automatically retried unless replay safety has been verified for the installed encoder.
Recovery persists a stored generated result or marks a stale in-flight request uncertain.
The PHP service accepts and persists the documented returned-counter range through 100002, then blocks a subsequent request before generation when the stored counter exceeds the encoder's 100000 input limit.
Issuance validates the persisted transaction's device serial and rejects payment-provider transactions that are not marked successful.

The adapter maps paid time credit to `ADD_TIME` using the configured device divider, appliance unlock to `DISABLE_PAYG`, and reset to `SET_TIME` with value zero.
The backend reuses `TransactionDataContainer::creditDays()` and its installment/down-payment rules.
Meter-energy credit remains unsupported because the OpenPAYGO token contract only defines time-based activation values.
The backend exposes a configuration service, but an administrator-facing configuration UI/API and physical-device acceptance have not been verified.

## Payment retries, overlapping payments, and recovery

Apply tenant migrations before running the updated workers.
For the local demo company, run from the repository root in WSL:

```bash
docker compose exec -u www-data backend-dev php /var/www/html/artisan migrate-tenant --company-id=1
docker compose restart queue-worker-dev scheduler-dev frontend-dev
```

Wait for worker startup checks and frontend compilation before testing in MPM.
The scheduler runs `openpaygo:recover-issuances` every minute.
Waiting payments keep their original credit days, but receive a counter and provisioning snapshot only when the device becomes available.
Retries of the same OpenPAYGO appliance transaction reuse the financial payment entry and token.
Recovery emits the normal payment-completion event when a stored token is completed, without emitting it again for a completed retry.

The SHS transaction table displays the token, credited days, and issuance status.
`pending` means waiting or processing; `completed` means a token has been saved.
`blocked` includes a configuration or stored-result recovery reason.
`uncertain` means a generator request may have completed but its result was not safely recorded.
An uncertain request retains the device's active slot and is never automatically regenerated.
Completed issuance does not prove that a device accepted its token.

Run the backend tests on the WSL host, using the dedicated test database:

```bash
docker compose up -d mysql_testing redis
cd src/backend
composer install
APP_ENV=testing APP_CONFIG_CACHE=/tmp/mpm-openpaygo-test-config.php \
  DB_HOST=127.0.0.1 DB_PORT=53306 DB_DATABASE=mpm_testing \
  php artisan test app/Plugins/OpenPaygo/Tests/Unit --stop-on-failure
```

The test bootstrap rejects other database targets.
These tests recreate the dedicated test database and tenant databases on the testing server; they must not target the development MySQL service.
They cover repeated issuance, whole-payment retries, overlapping requests, FIFO counters, blocked configuration recovery, uncertain outcomes, and reconciliation.

To demonstrate overlapping payments, open the same sold appliance in two browser tabs and submit two separate minimum payments.
Each payment should eventually display its own token and the correct days; any waiting payment should complete after the earlier issuance and a recovery pass.
Enter the tokens in issuance order in the simulator.
To replay a completed backend payment without creating a new payment, use its transaction ID in Tinker:

```php
app(App\Services\DatabaseProxyManagerService::class)->buildDatabaseConnectionDemoCompany();
$transaction = App\Models\Transaction\Transaction::findOrFail(REPLACE_WITH_TRANSACTION_ID);
$before = [$transaction->paymentHistories()->count(), $transaction->token()->firstOrFail()->token];
$configuration = App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration::where('device_id', $transaction->token->device_id)->firstOrFail();
$counter = $configuration->next_counter;
(new App\Jobs\ApplianceTransactionProcessor(1, $transaction->id))->executeJob();
dump(['payment_rows_unchanged' => $before[0] === $transaction->paymentHistories()->count(), 'token_unchanged' => $before[1] === $transaction->token()->firstOrFail()->token, 'counter_unchanged' => $counter === $configuration->fresh()->next_counter]);
```

All three checks should remain true after the queued token job finishes.
Use a disposable test device for configuration-failure demonstrations.
Temporarily remove its time divider, make one payment, and confirm MPM shows blocked status without another token or counter advancement.
Restore its original divider and run safe recovery:

```bash
docker compose exec -u www-data backend-dev php /var/www/html/artisan openpaygo:recover-issuances --company-id=1
```

The recorded payment should complete once, without another financial payment.
If a payment plan had invalid credit calculation, fix the plan and retry the same appliance transaction explicitly; recovery never invents missing credit days.

For an uncertain result, obtain the verified token and exact returned counter for the reservation's original snapshot from an authoritative generator result.
Do not guess a token or use the simulator's current counter as the returned backend counter.
The reconciliation command accepts operator-supplied results; it checks their format and reservation/counter consistency, not cryptographic device acceptance.
Resolve the uncertain reservation explicitly, then allow waiting payments to drain:

```bash
docker compose exec -u www-data backend-dev php /var/www/html/artisan openpaygo:recover-issuances \
  --company-id=1 --reservation-id=REPLACE_WITH_RESERVATION_ID \
  --token=REPLACE_WITH_VERIFIED_TOKEN --next-counter=REPLACE_WITH_RETURNED_COUNTER
```

Reconciliation rejects completed reservations and an edited device counter.
For a lost generator response with no authoritative result, investigate before reconciliation; keep the payment visible and blocked rather than resetting counters or issuing new credit.

## Proposed team agreements

Review these decisions together before Person 2 connects payments.
These are proposals, not maintainer-approved requirements.

| Decision | Proposed agreement |
| --- | --- |
| Credit conversion | OpenPAYGO docs define each integer unit as 1 / divider day; configure an explicit divider per device |
| Counter ownership | Backend owns one counter per device across every operation and saves the exact returned counter |
| Payment retries | One payment gets one stored token; block automatic retries after uncertain outcomes until replay safety is verified |
| Concurrent payments | Serialize issuance for each device and persist token, payment association, and counter atomically |
| Secrets | Keep device keys and the shared service key in backend-only configuration or protected storage |
| Failures | Keep payment processing state visible and avoid claiming credit was delivered when generation fails |
| First demo | Register one device, process two payments, verify both tokens, and show that replaying the first token adds no credit |

Person 2 should first build a PHP client test against the documented public reference request.
Then test invalid authentication and generation failure before connecting the client to payment processing.
Person 4 should record simulator starting state, each issued counter, credit changes, and replay rejection for the final demonstration.

## Sources and contribution review

- [Official JavaScript library](https://github.com/EnAccess/OpenPAYGO-js)
- [OpenPAYGO token customization and time-divider specification](https://enaccess.github.io/OpenPAYGO-docs/docs/openpaygo-token/customization-of-openpaygo-token/)
- [Published reference examples](https://unpkg.com/openpaygo@0.0.6/test/sample_tokens.json)
- [MPM plugin guide](../../docs/development/plugins.md)
- [Hackathon brief](https://github.com/EnAccess/oseas26-mpm-openpaygo-plugin)

This component was drafted with AI assistance.
Review it, understand its behavior, and follow MPM's contribution and disclosure policy before submission.
