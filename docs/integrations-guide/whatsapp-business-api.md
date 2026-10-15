---
order: 26
---

# WhatsApp Business API

> [!TIP]
> **Complete Illustrated PDF Manual Available**: A publication-grade 13-page configuration and operational guide with screenshots and customer workflows is available for offline distribution:
> [Download MicroPowerManager_WhatsApp_Plugin_Guide.pdf](MicroPowerManager_WhatsApp_Plugin_Guide.pdf)

The WhatsApp Business API plugin enables MicroPowerManager (MPM) to deliver transactional notifications — payment confirmations, energy token deliveries, and overdue appliance rate reminders — directly over the Meta WhatsApp Business Cloud API.

Across the regions MPM serves (sub-Saharan Africa, South and Southeast Asia), WhatsApp is the primary daily communication channel. By leveraging WhatsApp first, utilities achieve significantly lower messaging unit costs, higher deliverability, and rich delivery receipts (`sent`, `delivered`, `read`), with automated fallback to traditional SMS whenever a customer is offline or undeliverable.

---

## Key Features

- **Transactional Notification Delivery**: Automatically dispatches energy tokens, payment receipts, and financing reminders via pre-approved Meta message templates.
- **Failed Transaction Alerts**: Automatically notifies customers via WhatsApp when energy purchases or appliance payments fail (e.g. invalid meter, provider timeout), guiding them to resolve the issue with automatic SMS fallback.
- **Two-Tier SMS Fallback**: If a WhatsApp message fails or the recipient is offline/undeliverable, the notification is automatically re-routed through your configured SMS gateway (e.g. TextBee, Africa's Talking).
- **Two-Way Customer Support & Ticketing**: Inbound messages from customers automatically route into MPM's ticket management system and conversation history.
- **Manual Multi-Customer Opt-In & Registration Consent**: Direct opt-in toggle during customer onboarding, plus a multi-select table with checkboxes, search, filters, and batch opt-in/opt-out to manage compliance without blanket automation.
- **Self-Healing Phone Number Sync**: Detects Meta number change events (`new_wa_id`) and inbound customer update commands (e.g. `UPDATE NUMBER +255...`, `BADILI NAMBA +255...`), keeping customer records in sync.

---

## Prerequisites

1. **Access to MPM Admin Panel** with `Owner` or `Admin` privileges.
2. **Meta Developer Account**: Registered at [developers.facebook.com](https://developers.facebook.com/).
3. **WhatsApp Business Account (WABA)** with a registered phone number.
4. **Meta System User Access Token** with `whatsapp_business_messaging` and `whatsapp_business_management` permissions.
5. **Meta WhatsApp Cloud Phone Number ID** and **WABA ID**.
6. **Pre-approved WhatsApp Message Templates** in Meta Business Manager.

---

## Step 1: Create Meta Cloud API App & Credentials

1. Go to the [Meta for Developers Portal](https://developers.facebook.com/) and create an app with the **Business** type.
2. Add the **WhatsApp** product to your app.
3. Under **WhatsApp** → **API Setup**, retrieve your:
   - **Phone Number ID**: The unique ID for your sender phone number.
   - **WhatsApp Business Account ID (WABA ID)**.
   - **Permanent Access Token**: Generated via a System User in [Meta Business Manager](https://business.facebook.com/) with permissions for WhatsApp messaging.

---

## Step 2: Configure WhatsApp Templates in Meta

In the Meta Business Manager, create and submit message templates under **WhatsApp Manager** → **Account tools** → **Message templates**:

### 1. Transaction Confirmation / Token Delivery Template
- **Category**: `UTILITY`
- **Language**: `en` (or local language)
- **Sample Body**:
  ```text
  Dear customer, your payment of {{1}} has been received. Your electricity token is {{2}}. Thank you for using our service!
  ```
  *(Parameters: `{{1}}` = amount with currency, `{{2}}` = meter token)*

### 2. Appliance Rate / Payment Reminder Template
- **Category**: `UTILITY`
- **Language**: `en` (or local language)
- **Sample Body**:
  ```text
  Dear customer, your appliance installment of {{1}} is due on {{2}}. Please make your payment on time to avoid interruption.
  ```
  *(Parameters: `{{1}}` = remaining amount with currency, `{{2}}` = due date)*

---

## Step 3: Enable the Plugin in MPM

1. Log into your MPM administration panel.
2. Navigate to **Settings** → **Plugins**.
3. Locate **WhatsApp Business API** in the available plugins list.
4. Toggle the switch to **Enable** the plugin.

---

## Step 4: Configure Credentials & Templates in MPM

1. In the MPM sidebar, click on **WhatsApp Business API** → **Overview**.
2. Fill in your credentials:
   - **Phone Number ID**: Your Meta WhatsApp Phone Number ID.
   - **WhatsApp Business Account ID**: Your WABA ID.
   - **Access Token**: Your permanent System User Token.
   - **Webhook Secret / Verify Token**: A secure random string used to verify incoming webhooks.
   - **Transaction Confirmation Template**: The template name configured in Meta (e.g. `transaction_confirmation`).
   - **Reminder Template**: The reminder template name configured in Meta (e.g. `appliance_rate_reminder`).
3. Click **Save** to store and validate the credentials.

---

## Step 5: Configure Meta Webhooks

To receive delivery receipts (`delivered`, `read`) and inbound messages:

1. In the Meta Developer Portal, go to **WhatsApp** → **Configuration** (or **Step 2. Production setup** → **Configure Webhooks**).
2. Click **Edit** next to **Webhook**.
3. Set the **Callback URL** using this structure:
   ```text
   https://<your-mpm-domain>/api/whatsapp-business-api/callback/<company-id>/webhook
   ```

   ### Understanding the Callback URL Components:
   | Component | Description | Local Development (ngrok) | Production |
   | :--- | :--- | :--- | :--- |
   | **Protocol** | Must be `https://` (Meta rejects HTTP) | `https://` | `https://` |
   | **Host / Domain** | Public domain of your server | `xxxx.ngrok-free.app` *(from `ngrok http 8000`)* | `mpm.yourdomain.com` |
   | **Plugin API Route** | Fixed MicroPowerManager route | `/api/whatsapp-business-api/` | `/api/whatsapp-business-api/` |
   | **Callback Endpoint** | Company identifier + endpoint | `callback/1/webhook` | `callback/1/webhook` |

   > [!TIP]
   > - **What is `<company-id>`?**: It is the numeric ID of your organization/mini-grid in MicroPowerManager. In standard single-tenant setups, this is `1`.
   > - **Local Development Example (with ngrok)**:  
   >   If your ngrok terminal shows `Forwarding: https://1a2b-3c4d.ngrok-free.app -> http://localhost:8000`, your Callback URL is:  
   >   `https://1a2b-3c4d.ngrok-free.app/api/whatsapp-business-api/callback/1/webhook`
   > - **Production Example (with live SSL domain)**:  
   >   If your MPM server runs at `https://energy.example.com`, your Callback URL is:  
   >   `https://energy.example.com/api/whatsapp-business-api/callback/1/webhook`

4. Set the **Verify Token** to the exact **Webhook Secret** you entered in the MPM Overview tab (Step 4) (e.g. `mpm_webhook_token_2026`).

> [!CAUTION]
> **Crucial Meta Setting — Client Certificate Toggle**:
> In the Meta Webhook configuration modal, verify that the toggle switch **"Attach a client certificate to Webhook requests"** is **TURNED OFF (Grey)**.
> If this switch is accidentally left ON (Blue), Meta attempts Mutual TLS (mTLS) client certificate negotiation. Because standard web servers and ngrok tunnels do not terminate mTLS client certificates, Meta's webhook proxy (`WBxP`) will fail with error:
> `The callback URL or verify token couldn't be validated. (#N/A:WBxP-1483198068-4275553709)`.

5. Click **Verify and Save**.
   > [!NOTE]
   > **Sub-Millisecond Fast-Path Verification**:  
   > MPM features an optimized challenge responder in `public/index.php` that echoes Meta's `hub.challenge` in under 50ms, comfortably satisfying Meta's strict 5-second challenge verification timeout window.

6. Under **Webhook fields**, click **Manage** (or expand the fields table) and click **Subscribe** to:
   - **`messages`**: Delivers incoming customer messages, bot interactions, and message delivery receipts (`delivered`, `read`).
   - **`message_template_status_update`**: Notifies MPM when message templates are approved or rejected by Meta.
7. Click **Done** / **Save**.

---

## Customer Opt-Ins and Consent Management

Meta strictly requires explicit user opt-in before businesses send outbound notifications. MicroPowerManager provides granular, manual opt-in controls to ensure telecommunications compliance without blanket automation.

### 1. Direct Opt-In During Customer Registration
When field agents or operators register a new customer in MPM (**Clients** → **Add Customer**):
- An explicit **WhatsApp Opt-In** checkbox is available on the registration form:
  *`Opt-in to WhatsApp messaging (Send electricity tokens and alerts via WhatsApp)`*
- Enabling this checkbox immediately records verified consent in the database upon customer creation, allowing instant token delivery over WhatsApp from their very first energy purchase.

### 2. Manual Multi-Customer Selection & Batch Management
In **WhatsApp Business API** → **Customer Opt-ins**, operators have full manual control over customer consent:
- **No Blanket Automatic Opt-In**: Bulk operations are intentional and manual. You select specific customers rather than blindly altering all records across the company.
- **Clean Interactive Checkbox Selection**: Every row in the customer list contains a checkbox. You can manually check off individual customers or use the table header checkbox to select all visible records on the current page.
- **Batch Actions Bar**:
  When 1 or more customers are selected, a green action banner displays:
  - **`Opt-in Selected (X)`**: Grants WhatsApp messaging consent for only the selected customers.
  - **`Opt-out Selected (X)`**: Revokes WhatsApp messaging consent for only the selected customers (falling back to SMS).
  - **`Deselect All`**: Resets the current selection.
- **Confirmation Safety Dialog**: Shows a summary modal with the exact count of affected customers before executing the update.

### 3. Real-Time Search, Filters, and Unified Inline Status Toggles
- **Real-Time Search**: Search customers by name, surname, or phone number with instant debounced filtering.
- **Status Filter**: Filter the customer directory by status (*All Customers*, *Opted In Only*, *Opted Out Only*, *Pending / Not Set*).
- **Clean Unified Status Switch**: Instead of a cluttered badge column, each customer row features an integrated toggle switch clearly indicating their live consent state (`Opted In` or `Opted Out`). Clicking the switch immediately persists the update in the database without any modal.
- **Live Statistics Overview**: The overview cards (*Total Customers*, *Opted In*, *Opted Out*, *Pending*) automatically re-calculate and display updated counts in real time.

> [!NOTE]
> If a customer has not opted in, MPM blocks outbound WhatsApp delivery to that number and immediately routes the notification through the standard SMS gateway fallback.

---

## Failed Transaction Notifications

When an electricity token purchase or appliance payment fails in MicroPowerManager (e.g. meter communication error, invalid serial number, provider timeout, or insufficient balance):

1. **Automatic Event Capture**: MPM dispatches a `TransactionFailedEvent`, captured by the `TransactionFailedWhatsappListener`.
2. **Opt-In Verification**: If the customer has opted in to WhatsApp messaging, MPM prepares a dedicated failure notification.
3. **Actionable Customer Guidance**: The customer receives an immediate WhatsApp alert containing:
   - The transaction amount and date.
   - The reason for failure (e.g. *Invalid Meter Number* or *Gateway Timeout*).
   - Clear instructions on how to retry or contact utility support.
4. **SMS Gateway Fallback**: If the customer has not opted in or their phone is offline, the alert is automatically re-routed through the configured cellular SMS gateway.

---

## How Fallback to SMS Works

```
                        [Send Notification]
                                 │
                     Is WhatsApp Primary Gateway?
                                 │
                   ┌─────────────┴─────────────┐
                  Yes                          No
                   │                           │
         Is Customer Opted In?            [Send via SMS]
                   │
           ┌───────┴───────┐
          Yes              No ──────┐
           │                        │
  [Send WhatsApp]                   │
           │                        │
    Delivery Status?                │
     ┌─────┴─────┐                  │
Delivered      Failed ──────────────┼───────► [Automatic Fallback]
                                    │         Send via SMS Gateway
                                    │         (TextBee, Africa's Talking)
```

1. **Pre-dispatch Fallback**: If WhatsApp credentials are not configured or the customer has not opted in, the system dispatches via the fallback SMS gateway immediately.
2. **Delivery Failure Fallback**: If Meta accepts the message but subsequent webhooks report `failed` (e.g. recipient phone offline or not registered on WhatsApp), the plugin captures the webhook and dispatches the original message via the secondary SMS provider.

---

## Automated Self-Service & Inbound Interactive Menus

When a customer messages the utility over WhatsApp with meaningless or unspecified words (such as `"hi"`, `"hello"`, `"help"`, `"good morning"`, `"menu"`), the plugin automatically delivers an interactive template message containing a menu page with quick-action buttons:

```
┌──────────────────────────────────────────────┐
│ ⚡ Self-Service Menu                         │
│                                              │
│ Hello! Please select an option from the      │
│ menu below to manage your energy service:    │
│                                              │
│ ┌──────────────────────────────────────────┐ │
│ │            [Select Option]               │ │
│ └──────────────────────────────────────────┘ │
│                                              │
│ MicroPowerManager                            │
└──────────────────────────────────────────────┘
```

When tapped, WhatsApp opens a page with 4 self-service buttons:
1. **Get latest token** (`to get latest token`): Looks up the customer's account and returns their most recent energy token code, units (`kWh`), meter serial number, and timestamp.
2. **Did not receive token** (`did not recieve token`): Searches recent payments. If a token was generated, it immediately re-sends the token. If the payment is pending or token generation failed, it automatically opens an escalated Support Ticket in MPM and gives the customer the ticket ID.
3. **Status for low balance** (`status for low balance`): Checks the customer's meter consumption record (`credit_on_meter`). If credit is $\le$ 5.0 kWh, it issues an urgent low-balance warning; otherwise, it confirms balance is sufficient and power is active.
4. **Meter info** (`meter info`): Returns complete meter information including serial number, tariff type, connection phase, and active status.

Customers can also trigger any of these 4 actions directly by typing the natural phrase (e.g., *"did not recieve token"*, *"status for low balance"*, or *"meter info"*) or entering numbers `1`, `2`, `3`, or `4`. Every outbound bot reply is also recorded in MPM's SMS conversations table (`direction = 1`, `gateway_id = 33`), ensuring support agents have full visibility in the customer conversation thread.

---

## Operator Operations Guide

### 1. Unified Conversation History (`/sms`)
- Navigate to **Messaging / SMS** in the left navigation sidebar.
- Every WhatsApp message exchanged with customers is seamlessly visible in the conversation stream:
  - **WhatsApp messages** display the official WhatsApp outline logo next to the message time and recipient.
  - **Cellular SMS messages** display the standard `<md-icon>sms</md-icon>` icon.
  - Incoming customer inquiries appear in soft red/pink bubbles (`#ffebee`).
  - Outgoing notifications and bot replies appear in light blue bubbles (`#e2f3fd`).
- Operators can filter conversations by phone number or customer name to trace all transactional tokens, low-balance warnings, bot menu interactions, and manual agent replies.

### 2. Replying to Customers from the Dashboard
- Support agents can click on any customer conversation and enter a message in the compose box.
- When sent, MPM uses Meta Cloud API's free-form messaging interface within the open 24-hour customer service window.
- If the 24-hour window has expired or the customer's phone is offline, the message is automatically delivered via the fallback SMS gateway.

### 3. Managing Bot-Escalated Missing Token Tickets
- When a customer clicks **Did not receive token** and no recent completed token is found, the plugin automatically creates a high-priority ticket in **Tickets** → **Open Tickets**.
- The ticket title contains `Missing Token Report: <Customer Phone>`.
- The ticket body contains the customer's phone number and the last transaction ID/amount.
- Operators can investigate the payment provider gateway (e.g., Vodacom M-Pesa, Airtel Money, Wave), resolve the token issue, and close the ticket, notifying the customer directly in the thread.

---

## Customer Quick Reference Guide

Give your customers these guidelines so they can self-serve 24/7 without needing to call support:

| Action | How Customer Does It | What Customer Receives |
|---|---|---|
| **Open Menu** | Text anything (*"hi"*, *"hello"*, *"help"*, *"menu"*) | Interactive WhatsApp menu with **[Select Option]** button opening the 4 choices |
| **Get Latest Token** | Tap button or text *"latest token"*, *"to get latest token"*, or `1` | Token code (e.g., `4455-6677-8899`), units (`kWh`), meter serial, and date |
| **Missing Token** | Tap button or text *"did not recieve token"*, *"no token"*, or `2` | Instant re-send of completed token, or ticket confirmation `#123` with support alert |
| **Check Balance** | Tap button or text *"status for low balance"*, *"balance"*, or `3` | Current credit on meter (`kWh`) and low-balance recharge alert if $\le 5.0\text{ kWh}$ |
| **Meter Info** | Tap button or text *"meter info"*, *"my meter"*, or `4` | Meter serial number, tariff name, connection phase, and active status |
| **Update Phone** | Text `UPDATE NUMBER +255...` or `BADILI NAMBA +255...` | Automated confirmation and automatic synchronization of registered phone |

---

## Troubleshooting

| Issue | Cause | Solution |
|---|---|---|
| **`Callback URL or verify token couldn't be validated (#N/A:WBxP-...)`** | **"Attach a client certificate to Webhook requests"** is toggled ON (blue) in Meta Developer Portal. | **Turn the switch OFF (grey)**. Standard ngrok tunnels and web servers do not use mutual TLS client certificates; leaving it on causes Meta's webhook proxy to fail. |
| **`Webhook verification timeout (>5s)`** | ngrok is pointing to the frontend port (`8080`) instead of the backend port (`8000`). | Restart ngrok with `ngrok http 8000`. MPM features an ultra-fast challenge responder that responds in <50ms. |
| **`Customer has not opted in`** | The recipient's `opted_in` flag is false or unrecorded. | Check **Customer Opt-ins** in the sidebar and enable opt-in for the customer, or enable WhatsApp Opt-In during customer registration. |
| **`Template not found or disapproved`** | Template name or language code does not match Meta. | Confirm the template is approved in Meta Business Manager and names match exactly in MPM Overview. |
| **`Webhook verification failed (403)`** | Verify token mismatch. | Ensure the token in Meta webhook settings matches the Webhook Secret in MPM Overview (e.g. `mpm_webhook_token_2026`). |
| **`Transaction failed notification not delivered via WhatsApp`** | Customer is not opted in or phone number lacks international country code. | Verify customer is opted in under **Customer Opt-ins**. If not opted in, the notification automatically falls back to cellular SMS. |
| **`Token delivery delayed`** | Recipient has no internet connection. | The webhook will flag failure upon timeout, and the SMS fallback will deliver the token over GSM. |
