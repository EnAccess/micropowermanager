# WhatsApp Transactional Notifications

Branch: `whatsapp/transactional-notifications`

Base: `whatsapp-notifications` (b5e165dc), which contains the WhatsApp plugin scaffold and the Task 1 frontend views.

## Scope

Task 3 connects the WhatsApp plugin to MPM's existing payment, low-balance, and token-delivery flows so WhatsApp becomes a real notification channel.
The plugin must not duplicate MPM business logic and must never generate a second token.

The base branch does not contain the Task 2 template/message-history layer, so this branch adds the minimum foundation Task 3 needs (templates, message lifecycle fields, message-history and settings endpoints).

## Architecture

Notifications are event driven: MPM event → listener → `WhatsAppNotificationService` → queued `SendWhatsAppMessageJob` → `WhatsAppApiClient`.

- `TransactionSuccessfulEvent` (existing) → `SendWhatsAppTransactionListener` → payment receipt + token delivery.
- `ApplianceRateReminderEvent` (new, dispatched by the existing `ApplianceRateChecker`) → `SendWhatsAppLowBalanceListener` → low-balance warning.
- Sending is queued on the existing Horizon/Redis architecture so payment processing is never blocked by the WhatsApp API.

## Data

New tenant tables/columns:

- `whatsapp_templates` (name, type, language, body, status).
- `whatsapp_messages`: `person_id`, `transaction_id`, `template_id`, `recipient_name`, `reference`.
- `whatsapp_credentials`: `notification_settings` JSON (payment receipts / low-balance warnings / token deliveries).

Message statuses follow the required lifecycle: `queued`, `sending`, `sent`, `delivered`, `read`, `failed`.
Every attempt produces a message row.

## Services

- `WhatsAppNotificationService`: the entry point. `sendPaymentReceipt()`, `sendLowBalanceWarning()`, `sendTokenDelivery()`, plus `sendTestMessage()`.
- `WhatsAppMessageService`: persistence and lifecycle (`queue`, `deliver`, `recordFailure`, duplicate check).
- `WhatsAppTemplateVariableResolver`: allow-listed placeholder substitution.
- `WhatsAppApiClient`: pure HTTP (returns the provider message id, no persistence).
- `WhatsAppCredentialService`: credentials plus connection status.

## Duplicate protection

`WhatsAppMessageService::hasActiveMessage()` blocks a second message of the same type and source reference while the first is queued, sending, sent, delivered, or read.
`WhatsAppMessageService::deliver()` only sends records still in `queued`, so a retried job cannot resend an already-attempted message.

## API

- `GET/PUT /api/whatsapp/credential`
- `GET /api/whatsapp/status`
- `GET /api/whatsapp/templates`, `POST /api/whatsapp/templates`, `PUT /api/whatsapp/templates/{template}`
- `GET /api/whatsapp/messages`
- `POST /api/whatsapp/test-message`

## Frontend

The Task 1 mock `WhatsAppService` is replaced with a repository-backed service that talks to the endpoints above.
The overview, settings, templates, messages, and failed-message views read real data; status badges keep the existing labels.

## Tests

Backend: variable resolver, API client, notification service scenarios (payment, low balance, token, disabled, missing phone, duplicates), delivery job success/failure/idempotency, and event wiring (including that token delivery does not create a token).
Frontend: service mapping test against a mocked repository.

## Out of scope

Task 4 webhook handling for `delivered`/`read` callbacks; the statuses are persisted and rendered but inbound webhook updates are deferred.
