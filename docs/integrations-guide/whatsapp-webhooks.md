# WhatsApp webhook foundation

Milestone 1 authenticates callbacks without persisting delivery events.
Each company owns its own Meta Developer App, WABA, phone number, verification token, app secret, and outbound access token.

## Deployment boundary

Callbacks are disabled by default.
`WHATSAPP_WEBHOOKS_ENABLED=true` enables callbacks only when `APP_ENV` is `local`, `development`, or `testing`.
Production and staging remain unavailable even if the flag is enabled.
Use only a dedicated test Meta app and test phone while this milestone is deployed.
Do not subscribe a production integration until durable processing is implemented.

The endpoints are `GET /api/whatsapp/webhook/{companyId}` and `POST /api/whatsapp/webhook/{companyId}`.
The company ID is a routing hint, not an authentication secret.
Existing credential and test-message endpoints retain their JWT path.

GET requires `hub.mode=subscribe`, `hub.verify_token`, and a nonempty `hub.challenge`.
The original decoded challenge is returned as plain text after timing-safe token verification.
The parser reads the wire query rather than PHP-normalized names and rejects duplicate, array-shaped, missing, or additional parameters.

POST requires `X-Hub-Signature-256: sha256=<64 hexadecimal digits>`.
HMAC-SHA256 is calculated over the unchanged raw body using that company's app secret.
Only authenticated JSON is parsed.
Every entry must match the configured WABA; message changes must also match the configured phone number.
Authenticated message events return `503` with `Retry-After: 60` because persistence is unavailable.
Authenticated unsupported change fields return `200` without writes in the isolated environment.
Malformed payloads return `400`; authentication and tenant configuration failures return generic `403` responses.
No outbound resend is triggered by any callback.

## Tenant isolation

The plugin resolver authenticates before returning a company ID to the global tenant middleware.
It finds an existing central company/database registration without cache-writing lookup helpers.
A connection factory creates an unregistered temporary connection using trusted server configuration and the registered database name.
The reader selects only enabled state, ownership identifiers, and the relevant encrypted secret.
It never invokes credential initialization and disconnects in `finally`.
The application's default and active tenant connections are unchanged during candidate lookup.
An integration is disabled when `whatsapp_credentials.is_enabled` is false; the current Company model has no independent suspension flag.

Request bodies are limited to 1 MiB and query strings to 8 KiB.
The resolver limits attempts to 60 per source IP per minute before database lookup.
Use a shared cache store across application instances and verify trusted-proxy configuration before production activation.
Enforce a matching body limit at the reverse proxy because PHP must receive the body before application validation.

## Credentials

Apply the additive tenant migration through the normal tenant migration process after verifying the target environment.
It adds nullable `meta_app_secret` to `whatsapp_credentials` and does not copy secrets to the central database.
Do not run migration commands against an unverified database.
Rollback removes the new column and its stored values; export no plaintext secrets for rollback.
Roll application code back before removing a column required by that code.

The access token authenticates outbound requests; the verification token authenticates GET setup; the app secret authenticates POST bodies.
WhatsApp uses Laravel `Crypt::encryptString` and `decryptString`, compatible with valid encrypted Task 2 values.
Encryption and decryption errors fail closed without plaintext fallback.
Legacy plaintext or corrupt fields must be replaced or explicitly cleared through the authenticated API.
The shared encryption trait is unchanged.
Placeholder creation remains intentional for normal credential initialization, never webhook authentication.

For each secret, omission preserves the stored value, nonempty input replaces it, and explicit JSON null clears it.
Empty strings, whitespace-only strings, and masked placeholders are rejected.
Secret updates accept JSON or URL-encoded bodies; multipart secret submissions are rejected because normalized empty fields cannot reliably express explicit null.
The frontend adapter must omit unchanged secrets rather than submit returned masks.
Frontend integration and an app-secret input are separate Task 1 work.
All three secrets are hidden from ordinary model array/JSON serialization.
Credential resources expose only masks and presence flags.
All replacement secrets are encrypted before writes; the database update is transactional.

To rotate credentials, update the tenant's settings through the authenticated API and coordinate the corresponding Meta configuration change.
Only the current token/app secret is accepted; there is no previous-secret overlap mechanism.
Clearing a verification token or app secret disables the corresponding callback authentication immediately.
Back up and protect Laravel's application encryption key separately from database backups.

## Logging and local callbacks

Telescope excludes all entries recorded during WhatsApp credential and webhook requests, including query and exception diagnostics.
Application code does not log callback bodies, signatures, or secrets.
Disable debug responses for any externally reachable environment and audit other installed debugging/request-recording tools.
Configure proxy/access logs to omit verification query strings and authentication headers.
Avoid logging request bodies, including credential updates and callback payloads.

`VUE_APP_MPM_BACKEND_URL_EXTERNAL` supplies the external URL in frontend configuration; it does not create a tunnel or configure Meta automatically.
Use an HTTPS tunnel to the backend and set the dedicated test app's callback URL explicitly.
Meta's current documentation was not accessible during development.
The archived Meta WhatsApp SDK documents challenge verification and app-secret signature checking at https://whatsapp.github.io/WhatsApp-Nodejs-SDK/api-reference/webhooks/start/.
Current event schemas, dashboard setup, subscription details, retry behavior, and provider limits require official documentation verification before production activation.
No numeric retry guarantee is assumed here.

## Tests and next milestone

WhatsApp tests use temporary separate SQLite tenant files plus an in-memory central registry.
They do not create, migrate, truncate, or drop a development MySQL database.
The standard test bootstrap still requires `mpm_testing` before tests can start.
The existing send-client tests use fake HTTP responses and prevent stray requests.
Run `php artisan test --filter=WhatsApp` in the supported testing environment.
Host tests require the dependencies and host PHP described in the backend agent instructions.
A disposable Docker runtime may be used only with explicit testing configuration, never inherited application-container database settings.
The implementation was checked with a separate `mysql_testing` service and explicit `DB_DATABASE=mpm_testing`, then database fixtures were bound to temporary SQLite files.
MySQL locking and production worker behavior are not proven by SQLite tests.

Milestone 2 must add durable delivery processing before production callbacks are enabled.
Coordinate external `message_id`, notification types, tenant context, and outbound persistence timing with Task 3.
Define duplicate handling, out-of-order transitions, failure precedence, atomic updates, unknown-message reconciliation, and bounded processing retries.
Retrying webhook processing must remain distinct from resending a message.
No history API, frontend changes, queue job changes, or delivery-status writes are included in Milestone 1.
