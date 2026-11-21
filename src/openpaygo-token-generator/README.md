# OpenPAYGO token generator

This component generates OpenPAYGO tokens using the official JavaScript encoder.
It exposes an authenticated internal HTTP service, a JavaScript function, and a JSON command-line interface.
It is not yet an installed MPM manufacturer plugin.
The PHP adapter, registration screens, payment handling, and database persistence belong to the integration work.

## Run the HTTP service with Docker

Run from the repository root.
Building downloads the Node image and installs the locked dependencies inside the image.
The targeted startup command starts only the generator, without running MPM migrations or seeding.

Create a shared service key in your current WSL shell:

```bash
export OPENPAYGO_GENERATOR_API_KEY="$(python3 -c 'import secrets; print(secrets.token_hex(32))')"
docker compose --profile openpaygo up --build --no-deps -d openpaygo-generator
docker compose ps openpaygo-generator
docker compose logs --tail=20 openpaygo-generator
```

The service runs as a nonroot user on the Compose network with a read-only filesystem.
No port is published to the host.
Its internal base URL is `http://openpaygo-generator:3000`, reachable from MPM's backend and queue worker containers.
It is optional under the `openpaygo` profile, so normal MPM startup does not require it.
A missing or weak service key stops the process before it starts listening.

`OPENPAYGO_GENERATOR_API_KEY` is the shared password between the backend and this service.
It is different from each device's `secretKeyHex`.
Use the same service key in both callers and the generator.
It must contain 32-256 letters, digits, hyphens, or underscores.
Keep it out of Git, screenshots, logs, and browser code.

The shell variable lasts only for the current terminal.
Choose a persistent local secret configuration with your backend teammate before recreating containers.
Use the ignored `.env.override.micropowermanager-backend` file for the backend and queue worker's client settings.
Compose interpolation of the generator key needs the shell environment or an explicitly selected local Compose env file.
Do not assume a service's `env_file` automatically supplies Compose interpolation variables.

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

Payment-to-time conversion and rounding belong to the backend.
The caller must account for the device's configured time divider.
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
The PHP manufacturer plugin and full payment-to-simulator workflow have not been implemented or verified by these checks.

The CLI remains available through `node cli.js`.
It reads one JSON object from standard input and returns one JSON result on standard output.
Errors return JSON on standard error with exit status 1.
Do not put real device keys directly in shell commands or source files.

## PHP backend handoff

Agree on these client settings with Person 2:

```text
OPENPAYGO_GENERATOR_URL=http://openpaygo-generator:3000
OPENPAYGO_GENERATOR_API_KEY=<same shared key used by the generator>
```

Backend and queue worker must receive the same settings.
Recreating existing MPM containers to change their environment can run migrations and demo seeding through their entrypoints.
Coordinate that step separately; starting only the generator does not perform those operations.

Person 2 adds the proposed entry to Laravel's `config/services.php`:

```php
'openpaygo' => [
    'url' => env('OPENPAYGO_GENERATOR_URL', 'http://openpaygo-generator:3000'),
    'api_key' => env('OPENPAYGO_GENERATOR_API_KEY'),
],
```

An illustrative Laravel call, using a validated `$tokenRequest` array:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.openpaygo.api_key'))
    ->connectTimeout(3)
    ->timeout(15)
    ->post(
        rtrim(config('services.openpaygo.url'), '/').'/generate',
        $tokenRequest,
    );

$response->throw();
$tokenResult = $response->json();
```

This example has not been added to MPM's PHP code.
Person 2 must validate the response shape and map failures into MPM's domain exceptions and user-visible processing state.
Do not log outgoing request bodies or authorization headers.
Agree on timeouts for the permitted counter range.

Person 2 owns:

- Manufacturer-plugin installation, registration, and API binding.
- Tenant-scoped device configuration and protected secret storage.
- Payment-to-credit conversion and token metadata.
- Coordination of counters across charge, set-time, and unlock operations.
- Atomic persistence of the token and returned counter for a transaction.
- Duplicate callbacks, repeated queue jobs, and retries.
- Tests of the PHP client and payment workflow.

Generation is stateless and deterministic.
A network retry with identical input returns identical output.
Retrying with a newly advanced counter would issue a different token and can grant duplicate credit.
Reserve or lock device state and coordinate transaction idempotency before calling; persist the resulting token and next counter atomically.
Do not assume the HTTP service prevents concurrent payments from using the same counter.

Person 3 owns device registration, customer assignment, and displaying stored tokens and processing errors.
Person 4 owns the reproducible MPM-to-simulator demonstration.
A contributor reports manual simulator success for the generator; record the exact devices, operations, counter transitions, and replay checks before submission.
Production Compose and Kubernetes deployment wiring are outside this development service package.

## Proposed team agreements

Review these decisions together before Person 2 connects payments.
These are proposals, not maintainer-approved requirements.

| Decision | Proposed agreement |
| --- | --- |
| Credit conversion | Backend owns price, time units, and rounding; confirm the simulator's units and device time divider |
| Counter ownership | Backend owns one counter per device across every operation and saves the exact returned counter |
| Payment retries | One payment gets one stored token; retry identical input after uncertain network failures |
| Concurrent payments | Serialize issuance for each device and persist token, payment association, and counter atomically |
| Secrets | Keep device keys and the shared service key in backend-only configuration or protected storage |
| Failures | Keep payment processing state visible and avoid claiming credit was delivered when generation fails |
| First demo | Register one device, process two payments, verify both tokens, and show that replaying the first token adds no credit |

Person 2 should first build a PHP client test against the documented public reference request.
Then test invalid authentication and generation failure before connecting the client to payment processing.
Person 4 should record simulator starting state, each issued counter, credit changes, and replay rejection for the final demonstration.

## Sources and contribution review

- [Official JavaScript library](https://github.com/EnAccess/OpenPAYGO-js)
- [Published reference examples](https://unpkg.com/openpaygo@0.0.6/test/sample_tokens.json)
- [MPM plugin guide](../../docs/development/plugins.md)
- [Hackathon brief](https://github.com/EnAccess/oseas26-mpm-openpaygo-plugin)

This component was drafted with AI assistance.
Review it, understand its behavior, and follow MPM's contribution and disclosure policy before submission.
