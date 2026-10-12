# WhatsApp frontend

The WhatsApp frontend is a mock-only administration experience for configuring customer notifications.

The module lives under [`src/frontend/src/plugins/whatsapp`](../../src/frontend/src/plugins/whatsapp).

The overview, settings, template editor, preview, test-message form, and message history are available under `/whatsapp`.

`WhatsAppService` provides the future integration contract and currently stores cloned in-memory data for the duration of the browser session.

No API credentials are included in mock data.

No WhatsApp API request, webhook, queue, persistence, or real delivery is performed.
