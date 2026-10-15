# WhatsApp Business API Plugin for MicroPowerManager

This plugin integrates Meta's WhatsApp Business Cloud API with MicroPowerManager (MPM), enabling off-grid energy utilities to deliver transactional notifications, receive customer support inquiries, and fall back to cellular SMS when delivery fails.

## Key Components

- **`WhatsappGateway.php`**: Adapts WhatsApp messaging to MPM's SMS gateway interface (`sendSms(...)`). Formats energy token deliveries and appliance rate reminders into Meta Graph API templates.
- **`Http/Controllers/WhatsappWebhookController.php`**: Handles Meta webhook challenge verification, delivery status tracking (`delivered`, `failed`), SMS fallback on delivery failure, and inbound customer messaging into the ticket system.
- **`Http/Controllers/WhatsappCredentialController.php`**: Manages WABA ID, Phone Number ID, Access Token, Webhook Secret, and Template names.
- **`Http/Controllers/WhatsappOptInController.php`**: Enforces Meta compliance by tracking and querying customer messaging consent.
- **`Services/WhatsappCustomerSyncService.php`**: Detects Meta system change-of-number events and parses customer inbound commands (`UPDATE NUMBER ...`, `BADILI NAMBA ...`) to update customer records automatically.
- **`Services/WhatsappSelfService.php`**: Handles automated customer self-service menus and button actions:
  - Meaningless or unspecified customer text triggers the interactive menu page containing buttons:
    1. **Get latest token**: Fetches and formats customer's most recent energy token and units.
    2. **Did not receive token**: Checks recent transactions, re-delivers existing tokens, or opens an escalated support ticket in MPM.
    3. **Status for low balance**: Checks meter consumption and remaining credit, alerting customer if balance is low.
    4. **Meter info**: Displays registered meter serial number, meter type, and tariff.

## User & Administrator Documentation

Full setup, configuration, and integration instructions are available in the official documentation:
[docs/integrations-guide/whatsapp-business-api.md](../../../../../docs/integrations-guide/whatsapp-business-api.md).
