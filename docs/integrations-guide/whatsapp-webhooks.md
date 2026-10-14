# WhatsApp webhook foundation

Milestone 2 authenticates callbacks, durably stages delivery events, and synchronizes tenant-local message statuses.
Each company owns its own Meta Developer App, WABA, phone number, verification token, app secret, and outbound access token.

## Deployment boundary

Callbacks are disabled by default.
`WHATSAPP_WEBHOOKS_ENABLED=true` enables callbacks only when `APP_ENV` is `local`, `development`, or `testing`.
Production and staging remain unavailable even if the flag is enabled, including the reconciliation command.
Use only a dedicated test Meta app and test phone while this milestone is deployed.
Do not subscribe a production integration until the remaining production activation checks are reviewed.

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
Authenticated delivery statuses return `200` only after the complete validated batch is committed to the tenant inbox.
Matching records are updated synchronously; unknown external IDs remain durably pending.
An inbox or processing database error returns a generic `503` with `Retry-After: 60`.
Unhandled inbound messages and standalone message errors still return `503`; any delivery statuses accompanying them are durably staged first.
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
The credential migration adds nullable `meta_app_secret` to `whatsapp_credentials` and does not copy secrets to the central database.
The delivery migration adds nullable tracking columns to `whatsapp_messages` and a tenant-local `whatsapp_delivery_events` inbox.
Existing outbound message fields, external-ID indexing, and notification-type strings are unchanged.
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

## Delivery state machine

Correlation uses only the exact, case-sensitive external `message_id` inside the authenticated tenant.
Recipient numbers and local timestamps never select messages.
More than one matching message in a tenant is treated as an ambiguous contract violation, retained as an exhausted event for investigation.
The migration does not impose a new message-ID unique constraint on Task 3's writes.

| Current state | Incoming event | Result |
| --- | --- | --- |
| queued / sending | sent / failed | Apply provider evidence |
| sent | failed | Apply only if provider time is newer or equal |
| failed | sent | Recover only if provider time is strictly newer |
| queued / sending / sent / failed | delivered / read | Accept confirmed delivery/read evidence, regardless of arrival order |
| delivered | sent / failed | Preserve delivered |
| delivered | read | Advance to read |
| read | sent / delivered / failed | Preserve read |

Same-state events retain the earliest actual provider timestamp for that status.
The latest provider timestamp governing the effective state is retained separately.
Equal-time sent/failed conflicts resolve to failed; equal-time failure diagnostics use a deterministic lexical code tie-break.
A delayed failure cannot erase confirmed delivery or reading.
Read is terminal, but delayed sent/delivered events can backfill their missing timestamps.
No delivered timestamp is invented from a read event.

`sent_at` remains the existing local outbound-send time.
`provider_sent_at`, `delivered_at`, `read_at`, and `failed_at` record UTC provider times.
`provider_status_at` records the provider time governing the current state, while `delivery_status_updated_at` records local UTC processing time.
Provider epochs must fit the existing MySQL TIMESTAMP columns (positive Unix seconds through 2147483647).
The inbox preserves receipt and processing times separately.

For failures, the primary numeric provider error code is retained with a fixed sanitized reason.
Arbitrary provider titles, descriptions, payloads, and headers are never persisted by the delivery processor.
Successful recovery clears the message's current failure fields; the historical failed inbox event remains available with its provider timestamp and notification type.
An unknown message's notification type remains null until correlation succeeds.
Unsupported future provider statuses are retained with state `unsupported`, without changing a message.

## Durability, locking, and reconciliation

The tenant inbox has a unique SHA-256 event key derived from external ID, status, normalized provider time, and primary error code.
Duplicate receipt upserts do not reset processing state, timestamps, or retry budgets.
The complete batch is staged in a transaction before application begins, so a processing crash leaves durable pending work.
Each event then locks its inbox row and the correlated message row within one transaction.
The message update and inbox completion commit atomically, with up to five database deadlock retries.
The inbox uses InnoDB; verify that the existing message table also uses InnoDB before activation.
Laravel's locking behavior is documented at https://laravel.com/docs/12.x/queries#pessimistic-locking.

The plugin registers `whatsapp:reconcile-delivery` with Laravel's scheduler every minute, guarded by the same environment and feature restrictions.
`php artisan whatsapp:reconcile-delivery --company=123` restricts a manual run to an existing company.
The command uses isolated connections per company and never inherits the active tenant, even in long-lived processes.
There are no new Redis queue jobs or outbound retry jobs.
The durable SQL inbox is the source of pending work, avoiding a database/queue publication gap.
The existing scheduler service must be running for reconciliation to progress.

A sweep processes at most 100 due events per company.
Missing correlations and processing errors allow eight attempts total, using delays of 60, 120, 300, 900, 3600, 10800, and 21600 seconds.
Events also expire from automatic reconciliation after 24 hours from first receipt.
Duplicate callbacks never extend this window or restart exhausted attempts.
Exhausted records remain stored and produce a sanitized tenant-scoped warning.
The command reports pending, exhausted, and unsupported totals plus a processing-error flag.
It exits unsuccessfully on processing errors, exhausted records, or unsupported statuses; an unknown ID within its retry window is normally pending.
Review the retained event and Task 3 persistence before arranging an explicit replay; automatic replay of exhausted events is not implemented.
Do not resend the customer message merely because delivery reconciliation failed.
Monitor inbox growth, exhausted counts, unsupported statuses, scheduler health, and sustained database errors.
No automatic destructive retention or cleanup is implemented.

## Message history API

`GET /api/whatsapp/messages` returns read-only history for the JWT-authenticated company.
Send `Authorization: Bearer <user JWT>` and `Accept: application/json` through the existing frontend Axios client.
The global MPM middleware selects the tenant from the verified token; `auth:api` also requires an existing user, and the history request verifies that the user's company matches the resolved company.
There is no company ID in the endpoint or accepted query parameters.
No WhatsApp credentials or enabled outbound integration are required to review historical messages.
Production callback restrictions do not disable this authenticated read-only endpoint.

| Parameter | Default | Validation and meaning |
| --- | --- | --- |
| `page` | 1 | Integer from 1 through 10000 |
| `per_page` | 25 | Integer from 1 through 100 |
| `status` | none | One of queued, sending, sent, delivered, read, failed |
| `notification_type` | none | Exact stored type, maximum 255 characters |
| `date_from` | none | `YYYY-MM-DD`, inclusive UTC creation date |
| `date_to` | none | `YYYY-MM-DD`, inclusive UTC creation date, not before date_from |

Filters combine with AND.
Empty or malformed supplied filters and unknown parameters return `422`.
Tenant identifiers, connection names, database names, and arbitrary sort expressions cannot override tenant selection.
History is ordered by `created_at DESC, id DESC`; delivery-status updates do not change that order.
Null creation dates sort last, with IDs breaking ties.
Pages beyond the last page return an empty data array with pagination metadata.
New outbound records can shift offset-based page boundaries, so the UI should replace the current page on refresh rather than append it.
The resource uses Laravel's normal `data`, `links`, and `meta` pagination envelope and only validated filters enter pagination links.
Responses use `Cache-Control: private, no-store`.

The following is a sanitized response excerpt; Laravel also supplies its usual pagination links and metadata.

```json
{
  "data": [
    {
      "id": 42,
      "message_id": "wamid.example",
      "recipient_phone": "250788123456",
      "notification_type": "test",
      "status": "failed",
      "created_at": "2026-10-14T12:00:00.000000Z",
      "sent_at": "2026-10-14T12:00:01.000000Z",
      "provider_sent_at": null,
      "delivered_at": null,
      "read_at": null,
      "failed_at": "2026-10-14T12:00:02.000000Z",
      "provider_error_code": "131026",
      "error_message": "Message delivery failed."
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 25,
    "total": 1
  }
}
```

Timestamps are UTC ISO 8601 strings, or null when unavailable.
Sent time remains the local outbound-send timestamp; `provider_sent_at` preserves separate provider evidence.
For a current failed status, the API supplies a fixed safe failure reason and a numeric provider error code when available.
For other statuses, failure reason and error code are null.
Raw stored error descriptions are neither selected nor returned because legacy outbound failures may contain arbitrary upstream text.
Message bodies, inbox payloads, credentials, customer records, connection settings, and invented references are excluded.
Retrieval runs no initialization or write operation and loads no per-message relationships.

## Proposed Task 1 history integration contract

Task 1 files are unchanged pending explicit owner/user approval.
The local frontend snapshot is clean and still uses intentional in-memory fixtures; unpublished teammate changes cannot be determined from local Git status.
The existing `WhatsAppService.getMessages()` array contract must remain available to Overview.
Add a paginated service method without changing the settings, templates, or mock test-send contracts:

```js
getMessagePage({ page = 1, perPage = 25, status, notificationType, dateFrom, dateTo } = {})
// Promise<{ messages, pagination: { currentPage, perPage, total, lastPage } }>

getMessages(options)
// Promise<Array>: (await this.getMessagePage(options)).messages
```

The API adapter must use `@/repositories/Client/AxiosClient.js`, which already supplies the backend URL, bearer token, custom headers, and JWT refresh.
Call `Client.get("/api/whatsapp/messages", { params })`, then unwrap `response.data.data` and `response.data.meta`.
Map options to `page`, `per_page`, lowercase `status`, `notification_type`, `date_from`, and `date_to`; omit unset filters entirely.
Use API history by default for the configured production singleton and require an explicit mock mode, such as `new WhatsAppService({ historyMode: "mock" })`, for tests and demos.
Keep existing fixtures available in mock mode and update mock tests to select it explicitly.
Never fall back silently to fixtures when an API request fails.

| Backend | Existing UI row |
| --- | --- |
| `id` | `id`, also the stable row identity |
| `created_at` | `date`, formatted for display; null remains unavailable |
| `recipient_phone` | `phone` |
| `notification_type` | `type`, preserving the stored value until Task 3 agrees labels |
| `status` | `status`, mapped to Queued, Sending, Sent, Delivered, Read, Failed |
| `message_id` | `externalId` |
| no source | `customer: null`, `reference: null` |
| `error_message` | `errorMessage` |
| `provider_error_code` | `errorCode` |
| `sent_at`, `delivered_at`, `read_at`, `failed_at` | Optional sentAt, deliveredAt, readAt, failedAt values |

Bind the Messages table to the returned row array, track page metadata, and move status filtering to API requests.
Reset to page 1 on filter changes and offer explicit refresh plus previous/next page controls.
Replace page rows on each successful fetch; do not append refreshed rows.
Use a request generation counter or cancellation so an older response cannot overwrite a newer filter/page result.
Render distinct loading, empty, and error states, with retry behavior and neutral placeholders for missing customer/reference values.
Render the safe failure reason/code only for failed rows, using normal escaped Vue text rather than HTML injection.
Overview can continue calling `getMessages()` and slicing four records, but its mock statistics must not be presented as verified delivery totals.
Retain explicit mock labels for the existing test-send UI; its simulated rows must not enter API history.
No live test send is part of this read-only integration.
The StatusBadge component already supports all six status labels and requires no rewrite.
Frontend integration tests should cover mapping, HTTP errors, empty data, paging, stale-response handling, replacement refresh, safe failure display, and explicit mock compatibility after approval.

## Tests and integration dependencies

Most WhatsApp tests use temporary separate SQLite tenant files plus an in-memory central registry.
They do not create, migrate, truncate, or drop a development MySQL database.
The standard test bootstrap still requires `mpm_testing` before tests can start.
The existing send-client tests use fake HTTP responses and prevent stray requests.
Run `php artisan test --filter=WhatsApp` in the supported testing environment.
Host tests require the dependencies and host PHP described in the backend agent instructions.
A disposable Docker runtime may be used only with explicit testing configuration, never inherited application-container database settings.

Set `WHATSAPP_MYSQL_TESTS=1` to enable MySQL concurrency tests.
Those tests require the exact `mysql_testing` host and `mpm_testing` bootstrap database and verify live database identity before provisioning.
They create fresh `mpm_whatsapp_m2_<random>` scratch databases, print their names, and retain them without DROP, TRUNCATE, or development-data operations.
Independent PHP workers contend on a real locked InnoDB message row to test concurrent delivery/read events and duplicate receipt handling.
Additional MySQL tests verify case-sensitive correlation, duplicate-ID ambiguity, and durable staging after an injected SQL failure.
The SQLite tests do not establish MySQL locking guarantees.

Task 3 must persist the Meta response ID unchanged in `message_id`, preserve tenant context, and supply its existing notification-type string.
Correlation tolerates a delayed outbound commit within the bounded reconciliation window.
Two outbound records with the same external ID in one tenant require coordination rather than arbitrary matching.
The authenticated history API is implemented; Task 1 still needs approval for its frontend adapter and error presentation using the contract above.
Production activation also requires current Meta schema verification, operational scheduler/monitoring checks, InnoDB verification, credential and proxy-log checks, and review of replay/retention operations.
No production callback activation, frontend changes, transactional notification jobs, or outbound resends are included in the current changes.
