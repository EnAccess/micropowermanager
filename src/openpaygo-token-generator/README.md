# OpenPAYGO token generator

This is Person 1's token-generation component for the MPM OpenPAYGO challenge.
It wraps the official `openpaygo` encoder rather than implementing cryptography.
It is a server-side module with a JSON command-line entry point, not an installed MPM plugin or HTTP service.

## Setup and tests

Requires Node 20 or later and npm.
The dependency is pinned to the published OpenPAYGO version 0.0.6.

**Installation downloads packages and writes `node_modules`; it does not access MPM's database.**

```bash
cd src/openpaygo-token-generator
npm ci --ignore-scripts
npm test
```

If Node is unavailable in WSL, use the existing MPM frontend development image after dependencies are installed.
Run this from the token-generator directory:

```bash
docker run --rm --network none --entrypoint npm \
  --mount type=bind,source="$PWD",target=/work,readonly \
  --workdir /work micropowermanager-frontend-dev:latest test
```

This starts an isolated test container with no network and a read-only source mount.
It does not start MPM services or connect to the database.

Tests compare supported tokens and returned counters against the reference vectors shipped in the pinned package.
They also check input validation, successive issuance, deterministic retries, and the JSON command-line interface.
They do not prove simulator acceptance or device-side replay rejection.
The published version 0.0.6 decoder is not used as a verification oracle because its counter-return and history-handling code has defects.
Current upstream source differs from this published release.

## Input and output

Supply one JSON object on standard input and close the stream.
Read one JSON result from standard output on success.
Errors return JSON on standard error and exit status 1.
Standard input keeps device keys out of command-line arguments.
Do not paste real keys into shell commands, screenshots, source control, or browser code.

| Field | Meaning |
| --- | --- |
| `secretKeyHex` | Device key: exactly 32 hexadecimal characters |
| `startingCode` | Device's starting code, integer 0-999999999 |
| `counter` | Previous issued counter, integer 0-100000 |
| `tokenType` | `ADD_TIME` (default), `SET_TIME`, or `DISABLE_PAYG` |
| `value` | Raw integer credit units, 0-995; omitted for `DISABLE_PAYG` |
| `restrictedDigitSet` | Boolean, default false; true uses digits 1-4 |

The value is already expressed in device units.
Payment-to-time conversion and rounding belong to the MPM backend.
Device time-divider configuration must be accounted for by the caller.
Extended tokens and counter-synchronization commands are outside this first slice.
Unknown fields are rejected to catch mismatched integration contracts.

The input-counter ceiling is an operational guard because the encoder rebuilds its hash chain on every request.
It is not a protocol maximum; higher-counter devices require a reviewed performance strategy.
The returned counter can exceed the input ceiling by up to two; a later generation will reject that counter.
The integration must surface this exhaustion before accepting further payments for that device.

This example uses public upstream test data, not a production key:

```bash
node cli.js <<'JSON'
{
  "secretKeyHex": "bc41ec9530f6dac86b1a29ab82edc5fb",
  "startingCode": 516959010,
  "counter": 1,
  "tokenType": "ADD_TIME",
  "value": 1
}
JSON
```

Expected output:

```json
{"token":"588224011","nextCounter":2}
```

`nextCounter` comes directly from the encoder.
Depending on token type and counter parity, it advances by one or two.
Preserve tokens as strings, including leading zeroes.

## Backend handoff

The module exports `generateToken(input)` for a future JavaScript service.
The CLI is usable for local verification; PHP cannot invoke it until Node and dependencies are deliberately packaged for the backend and queue worker.
The current PHP Docker images do not include Node.
Transport and deployment remain an integration decision for the team.

Person 2 owns tenant-scoped configuration storage, secret protection, payment-to-credit conversion, counter locking, and token persistence.
Generation does not update a database or confirm that a device accepted a token.
Repeating the same input produces the same output.
Retries must reuse the transaction's stored result; blindly using a newer counter would issue additional credit.
Persist the token and next counter atomically for the same transaction, with coordination between all device-control operations.

Person 3 owns registration and displaying the stored token.
Person 4 should verify first and subsequent tokens, multiple devices, wrong-device rejection, and replay rejection in the simulator.
The simulator provisioning schema and compatibility have not yet been verified.

## Sources and contribution review

- [Official JavaScript library](https://github.com/EnAccess/OpenPAYGO-js)
- [Published reference vectors](https://unpkg.com/openpaygo@0.0.6/test/sample_tokens.json)
- [MPM plugin guide](../../docs/development/plugins.md)
- [Hackathon brief](https://github.com/EnAccess/oseas26-mpm-openpaygo-plugin)

This component was drafted with AI assistance.
Review it, understand its behavior, and follow MPM's contribution and disclosure policy before submission.
