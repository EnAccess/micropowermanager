---
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Customer-initiated Airtel Money payments in Tanzania and Uganda
---

# Airtel Money

With this plugin your customers pay for electricity with Airtel Money.
The customer pays to your merchant number and enters the meter's serial number as the reference.
Airtel then calls MicroPowerManager, which checks the payment, generates the token and sends it to the customer by SMS.

The plugin is used in Tanzania and Uganda.
Payments are always started by the customer.
MicroPowerManager cannot request a payment from a customer through this plugin.

## How it works

Airtel calls MicroPowerManager in three steps:

| Step       | When                                  | What MicroPowerManager does                                                     |
| ---------- | ------------------------------------- | ------------------------------------------------------------------------------- |
| Validation | Before Airtel collects the money      | Checks the meter, its owner, its tariff and the minimum purchase amount.        |
| Process    | After Airtel collected the money      | Starts processing the payment: the token is generated and sent to the customer. |
| Enquiry    | When Airtel wants to know the outcome | Answers whether the payment succeeded, failed or is still being processed.      |

If the validation fails, Airtel does not collect the money and the customer sees the reason.

Airtel signs every call with a token that is created with a secret only you and Airtel know.
MicroPowerManager rejects calls without a valid token.

## Prerequisites

- Access to the MPM admin panel
- A merchant agreement with Airtel Money in which Airtel calls your system to validate and process each payment.
  Airtel sets this up for you; ask your Airtel account manager.
- A secret agreed with Airtel for signing the calls (JWT with the HS512 algorithm).
- MicroPowerManager is reachable from the internet over HTTPS.
  No VPN is needed.

## Step 1: Enable the plugin

1. Navigate to **Settings** → **Configuration** → **Plugins**
2. Find the **AirtelPaymentProvider** plugin in the list
3. Toggle the switch to enable the plugin

## Step 2: Enter the secret and the country calling code

1. Click on **Airtel Money** in the sidebar, then on **Overview**
2. Enter:
   - **Secret**: the secret you agreed with Airtel
   - **Country calling code**: `+255` for Tanzania, `+256` for Uganda.
     It is added when Airtel sends the payer's phone number without it.
3. Click **Save**

The secret is stored encrypted.

## Step 3: Give Airtel the three addresses

When Airtel sets up your merchant number, it asks for the addresses (URLs) of your system.
Airtel has to call MicroPowerManager at exactly the three addresses below, one for each step.
Other address formats are not accepted.

```text
https://your-mpm-domain/api/airtel-payment-provider/callback/{company-id}/validation
https://your-mpm-domain/api/airtel-payment-provider/callback/{company-id}/process
https://your-mpm-domain/api/airtel-payment-provider/callback/{company-id}/enquiry
```

The overview page shows them with your own domain and company ID filled in, ready to copy.
Send them to Airtel together with the secret from step 2.

> [!INFO]
> The company ID in the address is how MicroPowerManager knows which company a payment belongs to.
> Nothing in Airtel's request or token identifies the company, so the addresses must be used exactly as shown.

## Step 4: Test with a small payment

1. Pay a small amount to your merchant number with Airtel Money and enter a meter's serial number as the reference
2. The payment appears under **Transactions** with the provider **Airtel Money**
3. The customer receives the token by SMS, if an SMS gateway is configured

## Good to know

- The payer's phone number is stored as the sender of the transaction.
  The token SMS is sent to that number.
- A customer needs a primary phone number in MicroPowerManager.
  Payments for meters whose owner has none are rejected at validation.
- Airtel repeats a call when it does not get an answer in time.
  A repeated process call does not generate a second token.
- If the token cannot be generated, the transaction is marked as failed and Airtel learns this from its next enquiry.
  Refunds are handled by Airtel according to your agreement.

## Technical reference

Airtel sends XML with `POST` and an `Authorization: Bearer <token>` header.
MicroPowerManager always answers with HTTP 200 and an XML document; the outcome is in `STATUS`.

Validation request:

```xml
<COMMAND>
  <TYPE>C2B</TYPE>
  <CUSTOMERMSISDN>712345678</CUSTOMERMSISDN>
  <MERCHANTMSISDN>689000111</MERCHANTMSISDN>
  <AMOUNT>6000</AMOUNT>
  <REFERENCE>47000056460</REFERENCE>
  <REFERENCE1>MP261126.1030.A12345</REFERENCE1>
</COMMAND>
```

`REFERENCE` is the meter's serial number and `REFERENCE1` is Airtel's transaction ID.

Answer:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<COMMAND><STATUS>200</STATUS><MESSAGE>Success</MESSAGE></COMMAND>
```

The process request carries `REFERENCE1` and `REFERENCE2`, the enquiry request carries `TXNID`.
The process answer repeats the transaction ID in `TXNID`, the enquiry answer adds MicroPowerManager's own ID in `REF`.

| Step       | `STATUS` | `MESSAGE`                                                                        |
| ---------- | -------- | -------------------------------------------------------------------------------- |
| any        | 401      | The token is missing, wrong or expired                                           |
| any        | 400      | The token was issued for another transaction, or the request is not valid XML    |
| Validation | 200      | `Success`                                                                        |
| Validation | 400      | The reason the payment cannot be accepted                                        |
| Process    | 200      | `Success`                                                                        |
| Process    | 400      | `Transaction not found`                                                          |
| Enquiry    | 200      | `Success`                                                                        |
| Enquiry    | 400      | `Pending`                                                                        |
| Enquiry    | 404      | `Failed`, `Not Found` (validated but never processed) or `Transaction not found` |

The token is a JWT signed with HS512.
It must contain an `exp` claim that lies in the future and a `payload.txnId` claim equal to the transaction ID of the request (`REFERENCE1` or `TXNID`).

## Troubleshooting

| Problem                                          | What to do                                                                                 |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| Airtel gets `Invalid Token` for every call       | Check that the secret in MicroPowerManager is the one Airtel uses.                         |
| Airtel gets `Signature verification failed`      | The secrets differ, or Airtel signs with another algorithm than HS512.                     |
| `Meter not found with serial number you entered` | The customer entered a reference that is not a registered meter serial number.             |
| `Transaction amount is not enough`               | The amount is below the minimum purchase amount of the meter's tariff.                     |
| Payment arrived but the customer got no SMS      | Check that an SMS gateway is selected in Main Settings, and the transaction's detail page. |
