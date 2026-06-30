---
order: 30
---

<p align="center">
  <a href="https://developer.safaricom.co.ke/">
    <img
      src="https://upload.wikimedia.org/wikipedia/commons/1/15/M-PESA_LOGO-01.svg"
      alt="M-PESA"
      width="160"
    >
  </a>
</p>

# Safaricom M-PESA (Daraja) Payment Provider

This guide walks you through integrating Safaricom's [Daraja API](https://developer.safaricom.co.ke/) with your MicroPowerManager (MPM) project to accept M-PESA payments from customers in Kenya for meter tokens and solar home system (SHS) services.

Unlike hosted-checkout providers (Paystack, PesaPal), M-PESA uses **STK Push** (M-Pesa Express). The operator initiates a payment from inside MPM; the customer's phone receives an M-PESA prompt; they enter their M-PESA PIN to confirm. There is no redirect — the page polls Safaricom until the transaction resolves.

> [!INFO]
> The **operator** initiates STK Push from the MPM sidebar (**Safaricom M-PESA → Initiate Payment**). There is no customer-facing public payment URL for M-PESA.

## Overview

### Pre-requisites

1. Access to the MPM admin panel.
2. A [Safaricom Developer account](https://developer.safaricom.co.ke/) (free).
3. A Daraja **app** with the **M-Pesa Express** product enabled, and the **Consumer Key** and **Consumer Secret** that app issues. M-Pesa Express is Safaricom's official name for the STK Push API
4. For **production only**: your own paybill or till shortcode and the matching production passkey (issued by Safaricom).

### Integration at a glance

1. Enable the `Safaricom KE` plugin in MPM.
2. Save your Daraja **Consumer Key** and **Consumer Secret** on the Credentials page.
3. Test against Daraja's sandbox — shortcode/passkey can be left blank and will fall back to Safaricom's published sandbox defaults.
4. Initiate a test STK Push from **Safaricom M-PESA → Initiate Payment**.
5. When ready for production, switch the **Environment** field to `Production` and add your real shortcode and passkey.

> [!INFO]
> You can run end-to-end test transactions in sandbox using **only** your Consumer Key and Consumer Secret. The plugin auto-fills Daraja's published sandbox `BusinessShortCode` (`174379`) and LNM passkey when those fields are left blank.

## Detailed Setup

### Step 1: Create a Safaricom Developer Account

1. Visit the [Safaricom Developer Portal](https://developer.safaricom.co.ke/).
2. Sign up and verify your email.
3. Log in.

### Step 2: Create a Daraja App

1. From the developer portal dashboard, create a new app.
2. When asked which products to subscribe the app to, select **M-Pesa Express**
3. Save the app.
4. Copy your **Consumer Key** and **Consumer Secret** from the app's detail page.

> [!WARNING]
> Keep the Consumer Secret confidential. Never share it publicly or commit it to version control. MPM encrypts it at rest via Laravel's `Crypt` facade, but the value flows in plain text over your dashboard session — only operators with admin access should ever see it.

### Step 3: Enable the Plugin in MPM

1. Log into your MPM admin panel.
2. Navigate to the **Plugin** page.
3. Find **Safaricom KE** in the available plugins list.
4. Click **Enable** to activate the plugin.

### Step 4: Configure Credentials

1. Navigate to **Safaricom M-PESA → Credentials** in the MPM sidebar.
2. Fill in the credential form:
   - **Consumer Key** — from your Daraja app (Step 2).
   - **Consumer Secret** — from your Daraja app (Step 2).
   - **Passkey** — leave **blank** for sandbox; required for production.
   - **Shortcode** — leave **blank** for sandbox; required for production.
   - **Environment** — `Sandbox` while testing, `Production` when going live.
   - **Result URL / Validation URL / Confirmation URL / Timeout URL** — optional. When left blank, MPM auto-derives the STK Push result URL from your `APP_URL` and uses it as `CallBackURL` on every STK Push request. Either way the URL must be a public HTTPS endpoint — Daraja rejects `localhost`/HTTP callbacks (see Step 5).
3. Click **Save**.
4. The Configuration status box on the Overview page should turn green and show **Configured (Sandbox)**.

> [!INFO]
> In sandbox the plugin pairs your Consumer Key/Secret with Daraja's public sandbox shortcode `174379` and the published LNM passkey `bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919`. This combination is the only one Daraja sandbox accepts. Custom shortcodes will fail with `Merchant does not exist`.

### Step 5: Expose Your Backend to Daraja

> [!WARNING]
> This step is **required**, including for local development. Without a public HTTPS `APP_URL`, no STK Push can be initiated at all.

Daraja validates the `CallBackURL` when it *accepts* an STK Push, and it rejects `localhost` and any non-HTTPS URL up front with `400.002.02 — Bad Request - Invalid CallBackURL`. MPM derives the callback from your `APP_URL`, so a default local setup (`APP_URL=http://localhost`) fails before the push is ever sent.

Expose your backend over HTTPS and point `APP_URL` at it before testing. The general MPM setup — Cloudflare Tunnel or ngrok plus the `APP_URL` change — is documented in the [Development Environment guide](/development/development-environment#api-gateway-with-ngrok).


### Step 6: Test an STK Push

1. Navigate to **Safaricom M-PESA → Initiate Payment**.
2. Pick a **Device type** (Meter or Solar Home System).
3. Enter a **Device Serial** that is registered on this tenant. The form validates against your MPM records on blur — a green ✓ means it found a customer; a red ✗ means the serial isn't registered.
4. Enter the **Customer Phone** — any of these formats work; MPM normalises to `2547XXXXXXXX` server-side:
   - `0712345678`
   - `712345678`
   - `+254712345678`
   - `254712345678`
5. Enter the **Amount** in KES (whole numbers only — Daraja rejects decimals; the plugin rounds for you).
6. Optionally enter a short **Description**.
7. Click **Send STK Push**.

The page transitions to **Waiting**: a spinner, the masked phone number, and a countdown until the next poll. The customer should now receive an M-PESA prompt on their phone and enter their M-PESA PIN.

When the transaction resolves, the page shows a **Result** view with a Daraja-code-mapped message (Payment Successful, Insufficient Funds, Cancelled by Customer, etc.) and — on success — the M-PESA receipt number.

> [!INFO]
> Sandbox test phone numbers and PINs are published on Safaricom's developer portal — log in to your dashboard's **Test Credentials** section to find the one matching the M-Pesa Express product.

## Monitoring Transactions

The Safaricom M-PESA Overview page in MPM shows:

- **Total Transactions** — all STK Push attempts initiated through this plugin.
- **Successful Payments** — confirmed by Daraja with `ResultCode 0` (and an M-PESA receipt number).
- **Pending Payments** — STK Push initiated but not yet resolved.
- **Configuration** — current credential status. Shows **Configured (Sandbox)** if the sandbox defaults are in play, or **Configured** for production.

For detailed transaction history including raw Daraja `CheckoutRequestID` / `MerchantRequestID` / M-PESA receipt, navigate to **Safaricom M-PESA → Transactions** and click the row's action icon.

## Production Considerations

When moving from sandbox to production:

1. In your Daraja app, switch to **Live** mode and request **Go-Live** approval from Safaricom (this requires a registered paybill or till).
2. Get a fresh **Consumer Key** and **Consumer Secret** issued for the live app — sandbox credentials do not work in production.
3. From the M-PESA portal (not Daraja), copy your paybill/till **Shortcode** and your production **Passkey**. The passkey is issued separately by Safaricom and is unique to your shortcode.
4. In MPM, open **Safaricom M-PESA → Credentials** and:
   - Set **Environment** to `Production`.
   - Paste the production **Consumer Key**, **Consumer Secret**, **Passkey**, and **Shortcode**.
   - Save.
5. Make sure `APP_URL` points at your production HTTPS domain so Daraja can post callbacks to `/api/safaricom/webhook/stk-push-result/{companyId}`.
6. Initiate a small STK Push (e.g. KES 1) against a known internal phone number to confirm the live path before exposing to customers.

> [!WARNING]
> Daraja's M-Pesa Express is configured to a **shortcode + passkey pair** — they are issued together. Using a sandbox passkey with a production shortcode (or vice versa) produces an opaque `Merchant does not exist` error.

## Troubleshooting

- **"Daraja: Merchant does not exist":**
  - Your shortcode isn't registered with the consumer key/secret being used. In sandbox, clear the Shortcode and Passkey fields to fall back to defaults. In production, double-check shortcode and passkey both come from the same Daraja "Go-Live" approval bundle.

- **"Daraja rejected the consumer key/secret":**
  - Your Consumer Key/Secret aren't valid for this environment. Sandbox and production each issue their own pair — make sure you copied them from the right Daraja app and that **Environment** in MPM matches.

- **STK Push sent but no prompt received on the phone:**
  - Confirm the phone number is in the correct format. The plugin accepts `0712…`, `712…`, `+254…`, and `254…` but rejects anything else.
  - The phone must be M-PESA-registered and online (a SIM with no service won't receive the push).
  - Check the **Transactions** page for the row — if `CheckoutRequestID` is populated, Daraja accepted the push; the issue is on the carrier side.

- **Page stays on "Waiting" until timeout, then shows "Stopped Waiting":**
  - The customer didn't enter their PIN within the polling window (~60s).
  - Daraja's async callback may still arrive afterwards — refresh the **Transactions** page in a minute to see if the transaction has flipped to **Success** or **Failed**.
  - For local development, no callback will arrive unless you've exposed `APP_URL` via a tunnel (Step 5 above).

- **"Wrong credentials" or `2001`:**
  - The customer entered the wrong M-PESA PIN. They can retry — initiate the STK Push again.

- **`1032 — Cancelled by Customer`:**
  - The customer dismissed the prompt. The transaction is recorded as **Abandoned** (distinct from **Failed**) so you can tell apart "didn't pay" from "tried to pay and it failed".

- **Custom callback URLs (`Result URL` etc.) not being respected:**
  - These fields exist for advanced setups (e.g. you front Daraja with your own proxy). Most operators should leave them blank — MPM derives a working `CallBackURL` from `APP_URL` automatically.

## References

- [Safaricom Developer Portal (Daraja)](https://developer.safaricom.co.ke/)
- [M-Pesa Express (STK Push) — community guide](https://dev.to/msnmongare/m-pesa-express-stk-push-api-guide-40a2)
- [Implementing M-pesa STK Push + Query — community guide](https://dev.to/anne46/implementing-m-pesa-stk-push-and-query-in-ruby-on-rails-328d)
- [django-daraja library docs](https://django-daraja.readthedocs.io/en/latest/pages/apis/stk_push.html) — useful for cross-referencing endpoint shapes
- [MicroPowerManager repository](https://github.com/EnAccess/micropowermanager)
- [Open issues](https://github.com/EnAccess/micropowermanager/issues) for reporting bugs or requesting features
