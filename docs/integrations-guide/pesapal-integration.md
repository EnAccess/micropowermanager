---
order: 28
---

<p align="center">
  <a href="https://www.pesapal.com/">
    <img
      src="https://developer.pesapal.com/templates/pesapaldev/assets/images/logo.png?v=1779358466"
      alt="PesaPal"
      width="160"
    >
  </a>
</p>

# Pesapal Payment Provider

This guide provides step-by-step instructions for integrating [PesaPal](https://www.pesapal.com/) with your MicroPowerManager (MPM) project to accept online payments from customers for meter tokens and solar home system (SHS) services.

With PesaPal enabled, MPM generates a public payment URL that you can share with customers.
Customers visit the link, select their device, enter an amount, and pay via PesaPal — the transaction is automatically recorded in MPM.

## Overview

### Pre-requisites

1. Access to the MPM admin panel
2. A [PesaPal merchant account](https://www.pesapal.com/dashboard/account/register) (a sandbox account is sufficient for initial setup)
3. Your PesaPal **Consumer Key** and **Consumer Secret** from your PesaPal merchant dashboard

### Integration

1. Enable the `Pesapal Payment Provider` plugin in MPM
2. Enter your API keys, currency, and merchant details on the overview page
3. MPM auto-registers the IPN callback with PesaPal — no dashboard step required
4. Share the generated payment URL with your customers

> [!INFO]
> You can start with PesaPal **Test** credentials (sandbox host `cybqa.pesapal.com`) to verify the integration before switching to **Live** keys for production (`pay.pesapal.com`).

## Detailed Setup

### Step 1: Create a PesaPal Account

1. Visit [PesaPal Registration](https://www.pesapal.com/dashboard/account/register)
2. Fill in your business details and verify your email
3. Complete the onboarding steps in the PesaPal dashboard

### Step 2: Get Your Consumer Key & Secret

1. Log into your PesaPal merchant dashboard
2. Navigate to **Account** → **API Keys**
3. Copy your **Consumer Key** and **Consumer Secret**
   - Use the **sandbox** key pair while setting up; switch to **live** keys when ready for production

> [!WARNING]
> Keep your Consumer Secret confidential. Never share it publicly or commit it to version control.

### Step 3: Enable the Pesapal Plugin in MPM

1. Log into your MPM admin panel
2. Navigate to the **Plugin** page
3. Find **Pesapal Payment Provider** in the available plugins list
4. Click **Enable** to activate the plugin
5. The setup wizard will appear — you can configure credentials now or skip and do it later from the overview page

### Step 4: Configure Credentials

1. Navigate to **Pesapal** → **Overview** in the MPM sidebar
2. Fill in the credential form:
   - **Consumer Key** — your PesaPal consumer key from Step 2
   - **Consumer Secret** — your PesaPal consumer secret from Step 2
   - **Merchant Name** — your business or mini-grid name
   - **Merchant Email** — the email associated with your PesaPal account
   - **Currency** — `KES`, `UGX`, `TZS`, or `USD`.
     This is sent to PesaPal with every payment, so it must match what your PesaPal account is configured to accept.
   - **Environment** — select `Test` for the sandbox or `Live` for production
   - **Callback URL** — read-only and auto-generated; MPM derives it from the public payment URL, so you don't need to fill or copy anything by hand
3. Click **Save**
4. The Configuration status box at the top should turn green and show "Configured".
   Behind the scenes, MPM exchanges your keys for a bearer token and then calls `RegisterIPN` so PesaPal knows where to send asynchronous payment notifications.
   The returned **IPN ID** appears in the form as a read-only field.

> [!INFO]
> If the IPN ID stays empty after saving, MPM will display the PesaPal error inline.
> The most common cause is that your MPM instance is not publicly reachable from PesaPal's servers — the `RegisterIPN` call will fail if PesaPal cannot reach your callback URL.

### Step 5: Share the Payment URL with Customers

1. In the MPM Pesapal overview page, find the **Public Payment Link** card and copy the **Permanent Payment URL**
2. Share this URL with your customers through:
   - SMS messages
   - Printed QR codes at your office
   - WhatsApp or other messaging apps
   - Your website or customer portal

When customers visit the payment URL, they will:

1. Select their device type (Meter or Solar Home System)
2. Enter their device serial number (validated against your MPM records)
3. Enter the payment amount in the configured currency
4. Complete payment through PesaPal's secure checkout
5. See a confirmation page with their transaction status

### Step 6: Test a Payment

Before going live, verify the integration works end-to-end:

1. Ensure your environment is set to **Test** in MPM credentials
2. On the Pesapal overview page, click **Open test payment page** to open the public payment form
3. Select a device type and enter a valid serial number from your MPM system
4. Enter a test amount and submit
5. Complete the payment using [PesaPal sandbox card details](https://developer.pesapal.com/api3-demo-keys.source.html)
6. After payment, you should be redirected to the result page showing the transaction status
7. Back in MPM, navigate to **Pesapal** → **Transactions** to verify the transaction was recorded

## How It Works

PesaPal's API 3.0 is slightly different from one-shot webhook providers.
Each transaction goes through three calls:

1. **Submit Order** — MPM calls `SubmitOrderRequest` with the amount, currency, and IPN ID.
   PesaPal returns a `redirect_url` and `order_tracking_id`.
2. **Customer Pays** — the customer's browser is redirected to PesaPal's hosted checkout.
3. **IPN + Status Query** — PesaPal calls MPM's IPN endpoint to notify that the order changed.
   MPM **does not trust** the IPN payload; it immediately calls `GetTransactionStatus` to fetch the authoritative status and updates the transaction accordingly.

Bearer tokens from `RequestToken` are cached for ~4 minutes (PesaPal expires them after 5 minutes) so high-volume operators don't hit the auth endpoint on every payment.

## Monitoring Transactions

The Pesapal overview page in MPM shows:

- **Total Transactions** — all payment attempts
- **Successful Payments** — completed and verified transactions
- **Pending Payments** — transactions awaiting verification
- **Configuration** — current credential + IPN status

For detailed transaction history, navigate to **Pesapal** → **Transactions** to view, filter, and inspect individual payment records.

## Troubleshooting

- **Payment form shows "Invalid device serial number":**
  - Verify the serial number exists in your MPM system under the correct device type (Meter or SHS)
  - Check that the device is registered and active

- **Saving credentials returns "PesaPal IPN registration failed":**
  - Ensure your MPM instance is publicly accessible — PesaPal must be able to reach the IPN URL from the public internet
  - Re-check the consumer key/secret pair and the selected environment

- **Customer not redirected back to MPM after payment:**
  - The `callback_url` is set on every order, so this should "just work"; if it doesn't, confirm that the callback URL configured on the credential is publicly reachable

- **Transaction not appearing in MPM:**
  - Check that the IPN ID is set on the credential (Overview page)
  - Verify the environment setting matches the keys you're using
  - Check MPM logs for IPN processing errors (`docker compose logs -f backend-dev`)

- **Authentication errors:**
  - Double-check that you copied the full keys without extra spaces
  - Sandbox keys only work with the sandbox host and vice versa

## Production Considerations

When moving from test to production:

1. Switch your keys to **Live** in both MPM and the PesaPal dashboard
2. Change the environment setting to **Live** in MPM credentials — this re-registers the IPN against `pay.pesapal.com` on save
3. Test a real payment with a small amount to confirm everything works
4. Monitor the first few transactions in both MPM and PesaPal dashboards
5. Ensure your MPM instance uses HTTPS — PesaPal requires secure connections for live payments

## References

- [PesaPal Developer Portal](https://developer.pesapal.com)
- [PesaPal API 3.0 Reference](https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/api-reference)
- [PesaPal Sandbox Demo Cards](https://developer.pesapal.com/api3-demo-keys.txt)
