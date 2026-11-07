# WhatsApp Business Gateway

This guide walks you through connecting your own WhatsApp Business account to MicroPowerManager (MPM). Once set up, MPM's existing notifications, such as payment confirmations with tokens and appliance payment reminders, are delivered over WhatsApp. Customers can also message your WhatsApp number to check their balance or start and stop notifications.

The plugin uses Meta's WhatsApp Cloud API directly. You do not need a third-party messaging provider, only the WhatsApp Business credentials you already have.

## How it works

1. A customer pays, or an appliance payment becomes due.
2. MPM builds the message and asks the active SMS gateway to send it.
3. With WhatsApp selected, the plugin sends it as an approved WhatsApp template message.
4. If the customer has not opted in, or WhatsApp fails, MPM falls back to your SMS gateway (if you have one enabled).
5. Every attempt is recorded in the plugin's **Message log**, and delivery receipts update it from `sent` to `delivered` and `read`.

## Prerequisites

- Access to the MPM admin panel
- A Meta (Facebook) account with access to [Meta for Developers](https://developers.facebook.com/)
- A WhatsApp number. For testing, Meta provides a free test number that can message up to five verified recipients
- **An SMS gateway plugin (Africa's Talking or TextBee) if you want a fallback.** Without one, messages to customers who have not opted in, and messages that WhatsApp cannot deliver, fail visibly and are retried by MPM

## Step 1: Create a Meta app

1. Go to [Meta for Developers](https://developers.facebook.com/) and choose **My Apps** → **Create App**
2. Choose the use case for connecting with customers through WhatsApp
3. Attach or create a Business portfolio when asked
4. Open **WhatsApp** → **API Setup**. Note these values:
   - **Phone number ID**
   - **WhatsApp Business Account ID** (shown as _Messaging account ID_ in newer versions of the dashboard)
5. In **Settings** → **Basic**, copy the **App secret**. You need it later to verify incoming webhooks

When using the test number, add the phones that should receive messages under **To** → **Manage phone number list** and verify each one with the code WhatsApp sends.

## Step 2: Create a long-lived access token

The temporary token on the API Setup page expires after about 24 hours. Use a system user instead:

1. Go to [Meta Business Settings](https://business.facebook.com/) → **Users** → **System users** → **Add**
2. Choose the **Admin** role
3. Click **Add assets**, then give the system user full control of your app and your WhatsApp account
4. Click **Generate token**, select your app, and tick `whatsapp_business_messaging` and `whatsapp_business_management`. If offered, set the expiry to **Never**
5. Copy the token and store it safely. Meta only shows it once

::: warning
Some tokens expire after about 60 days. Note the expiry date and generate a new token before it passes, then paste it into the plugin settings.
:::

## Step 3: Create message templates

WhatsApp only allows free-form messages within 24 hours after a customer writes to you. Messages that MPM starts, such as receipts and reminders, must use templates approved by Meta.

In **WhatsApp Manager** → **Message templates** → **Create template**, create two templates with category **Utility**:

**`mpm_payment_notice`**

```text
Payment notification from your energy provider: {{1}} Thank you for choosing us.
```

**`mpm_payment_reminder`**

```text
Payment reminder from your energy provider: {{1}} Please keep your account up to date.
```

- `{{1}}` is the message MPM generates, for example the customer's name, the token and the energy or time it unlocks
- A template body cannot start or end with a variable, so keep the fixed text around them
- Approval can take a few minutes to a day. Until a template is approved, sending it fails with error `132001`
- The language you choose must match **Template language** in the plugin settings (`en` for English, `en_US` for English (US))

You can use different template names. Enter them in the plugin settings in Step 5.

## Step 4: Enable the WhatsApp plugin in MPM

1. Log into your MPM admin panel
2. Navigate to the **Plugins** section
3. Find the **Whatsapp** plugin and click **Enable**
4. Confirm the activation

## Step 5: Configure the credentials

1. Open **Whatsapp** → **Overview** → **Settings**
2. Enter:
   - **Phone Number ID** from Step 1
   - **Access Token** from Step 2
   - **Meta App Secret** from Step 1
   - **Template language** and the two **template names** from Step 3
   - **Fall back to SMS** (recommended when an SMS plugin is enabled)
3. Click **Save**, then **Test connection**. You should see the name and number of your WhatsApp account
4. Use **Send test message** with a verified recipient number to confirm delivery. While your own templates are still in review, tick the _hello_world_ option to send Meta's pre-approved template

Secrets are stored encrypted and are never shown again after saving. Leave a secret field blank to keep the saved value.

## Step 6: Select WhatsApp as your SMS gateway

1. Navigate to **Settings** → **Configuration** → **Main Settings**
2. In the **SMS Gateway** dropdown, select **WhatsApp**
3. Click **Save**

## Step 7: Connect the webhook (incoming messages and delivery receipts)

The webhook lets customers message your number and keeps the Message log up to date with delivery receipts. Sending notifications works without it.

1. In the plugin **Settings** tab, copy the **Callback URL** and the **Verify token**. The URL has this form:

```text
   https://your-mpm-domain.com/api/whatsapp/webhook/<company-id>
```

2. In Meta, open **WhatsApp** → **Configuration** → **Webhook** → **Edit**. Paste the callback URL and verify token, then click **Verify and save**
3. Under **Webhook fields**, subscribe to **messages**
4. Make sure your WhatsApp Business Account is subscribed to your app. If real messages do not arrive, run this once (replace the placeholders):

```bash
   curl -X POST "https://graph.facebook.com/v23.0/<WABA_ID>/subscribed_apps" \
     -H "Authorization: Bearer <ACCESS_TOKEN>"
```

Your MPM instance must be reachable from the internet over HTTPS. For local development, use a tunnel such as ngrok and use its address in the callback URL.

Every webhook request is checked against the **Meta App Secret**, and requests with an invalid signature are rejected. Repeated deliveries of the same message are ignored.

## Step 8: Opt-in and opt-out

WhatsApp requires customers to agree to receive messages. Only customers who opted in receive WhatsApp notifications. Everyone else receives the fallback SMS.

- **By message:** a customer sends any message to your number and receives a menu:
  - `1` shows their balance
  - `2` starts WhatsApp notifications (opt-in)
  - `3` or `STOP` stops them (opt-out)
- **By staff:** in **Whatsapp** → **Opt-ins**, record a customer's phone number after they have agreed to be contacted on WhatsApp. You can also revoke consent there

Phone numbers are matched in international format. Make sure the customer's primary phone number in MPM is correct.

## Step 9: Test the integration

1. Record your own phone number under **Opt-ins**, and set it as the primary phone of a test customer
2. Make sure **Main Settings** → **SMS Gateway** is **WhatsApp**
3. Register a payment for that customer. The confirmation message arrives on WhatsApp
4. Open **Whatsapp** → **Message log** and click **Refresh**. The message shows as `sent`, then `delivered` and `read` once the webhook is connected

## Message log statuses

| Status         | Meaning                                                    |
| -------------- | ---------------------------------------------------------- |
| `sent`         | Accepted by WhatsApp                                       |
| `delivered`    | Delivered to the customer's phone                          |
| `read`         | Opened by the customer                                     |
| `fallback_sms` | WhatsApp could not be used, so the message was sent as SMS |
| `failed`       | Not delivered. The error column shows the reason           |

## Troubleshooting

**Common issues:**

- **Authentication error (190)**
  - The access token is wrong or expired. Generate a new system user token and save it
- **Recipient not allowed (131030)**
  - With the test number, the recipient must be added and verified under **Manage phone number list**
- **Template does not exist (132001)**
  - Check that the template name and language match WhatsApp Manager exactly, and that the template is approved
- **Parameter mismatch (132000)**
  - Each template must have exactly one variable, `{{1}}`
- **Message accepted but nothing arrives**
  - The request was accepted by WhatsApp but delivery failed later. Connect the webhook (Step 7), and the Message log shows Meta's reason
  - Messages that WhatsApp classifies as marketing are not delivered to customers who have not recently contacted you. Use the Utility category for receipts and reminders
- **"No active SMS provider" in the logs**
  - A customer who has not opted in has no fallback because no SMS plugin is enabled. Enable Africa's Talking or TextBee, or record the customer's opt-in
- **Customers' messages get no reply**
  - Check that the webhook is verified, **messages** is subscribed, the App Secret is saved, and your WhatsApp Business Account is subscribed to your app

**Verification steps:**

1. Check the MPM logs for `WhatsApp` entries
2. Check the plugin's **Message log** for the status and error of the message
3. Confirm **Whatsapp** is selected in **Settings** → **Configuration** → **Main Settings**
4. Confirm the customer's phone number is in international format and opted in

## Production considerations

When moving from the test number to a production number:

1. Add and verify your own business phone number in Meta, and complete business verification if Meta requires it
2. Update the Phone Number ID and access token in the plugin settings
3. Submit your templates for the production account and wait for approval
4. Set the token's expiry date as a reminder, and rotate it before it expires
5. Keep an SMS gateway plugin enabled as a fallback
6. Review Meta's conversation pricing for your country
7. Make sure your MPM instance has a stable HTTPS address for the webhook

## Why choose WhatsApp?

WhatsApp is ideal if your customers:

- Already use WhatsApp every day and read it more reliably than SMS
- Need delivery and read receipts
- Benefit from asking for their balance on their own

For SMS-only delivery, consider [AfricasTalking SMS Gateway](./africastalking.md) or [TextBee SMS Gateway](./textbee.md).
