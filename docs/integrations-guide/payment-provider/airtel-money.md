---
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Customer-initiated Airtel Money payments in Tanzania and Uganda
---

<p align="center">
  <a href="https://www.airtel.africa/">
    <img
      src="https://upload.wikimedia.org/wikipedia/commons/1/18/Airtel_logo.svg"
      alt="Airtel"
      width="160"
    >
  </a>
</p>

# Airtel Money

This guide shows how to accept [Airtel Money](https://www.airtel.africa/) payments in MicroPowerManager (MPM).

With Airtel Money enabled, your customers pay for electricity from their phone.
The customer pays to your merchant number and enters the meter's serial number as the reference.
MPM then generates the token and sends it to the customer by SMS.

The plugin is used with Airtel Money in [Tanzania](https://www.airtel.co.tz/) and [Uganda](https://www.airtel.co.ug/).

## Overview

### Pre-requisites

1. Access to the MPM admin panel
2. An Airtel Money merchant number.
   Ask your Airtel Money account manager for a merchant (biller) integration, in which Airtel asks your system to confirm each payment.
3. A secret agreed with Airtel.
   Airtel uses it to sign its requests, so MPM knows they really come from Airtel.
4. MPM is reachable from the internet over HTTPS.
   No VPN is needed.

### Integration

1. Enable the `Airtel Money` plugin in MPM
2. Enter the secret and your country calling code on the overview page
3. Give Airtel the three addresses shown on the overview page
4. Test with a small payment

> [!INFO]
> Payments are always started by the customer.
> MPM cannot request a payment from a customer through this plugin.

## Detailed Setup

### Step 1: Enable the Airtel Money Plugin in MPM

1. Navigate to **Settings** → **Configuration** → **Plugins**
2. Find the **Airtel Money** plugin in the list
3. Toggle the switch to enable the plugin

### Step 2: Enter the Secret and the Country Calling Code

1. Click on **Airtel Money** in the sidebar, then on **Overview**
2. Enter:
   - **Secret**: the secret you agreed with Airtel
   - **Country calling code**: `+255` for Tanzania, `+256` for Uganda
3. Click **Save**

> [!WARNING]
> Keep the secret confidential.
> Share it only with Airtel.
> MPM stores it encrypted.

### Step 3: Give Airtel the Three Addresses

When Airtel sets up your merchant number, it asks for the addresses (URLs) of your system.

1. On the overview page, open **Addresses to give to Airtel**
2. Copy the **Validation**, **Process** and **Enquiry** addresses
3. Send them to Airtel

The addresses already contain your own domain and company ID.
They look like this:

```text
https://your-mpm-domain/api/airtel-payment-provider/callback/{company-id}/validation
https://your-mpm-domain/api/airtel-payment-provider/callback/{company-id}/process
https://your-mpm-domain/api/airtel-payment-provider/callback/{company-id}/enquiry
```

> [!INFO]
> Airtel has to use the addresses exactly as shown.
> The company ID in the address is how MPM knows which company a payment belongs to.

### Step 4: Test with a Small Payment

1. Pay a small amount to your merchant number with Airtel Money and enter a meter's serial number as the reference
2. The payment appears under **Transactions** with the provider **Airtel Money**
3. The customer receives the token by SMS, if an SMS gateway is configured

## How a Payment Works

Airtel asks MPM about every payment in three steps:

| Step       | When                                  | What MPM does                                                                   |
| ---------- | ------------------------------------- | ------------------------------------------------------------------------------- |
| Validation | Before Airtel collects the money      | Checks the meter, its owner, its tariff and the minimum purchase amount.        |
| Process    | After Airtel collected the money      | Starts processing the payment: the token is generated and sent to the customer. |
| Enquiry    | When Airtel wants to know the outcome | Answers whether the payment succeeded, failed or is still being processed.      |

If the validation fails, Airtel does not collect the money and the customer sees the reason.

## Good to Know

- The payer's phone number is stored as the sender of the transaction.
  The token SMS is sent to that number.
- A customer needs a primary phone number in MPM.
  Payments for meters whose owner has none are rejected at validation.
- The country calling code is added when Airtel sends the payer's phone number without it.
- Airtel repeats a request when it does not get an answer in time.
  A repeated process request does not generate a second token.
- If the token cannot be generated, the transaction is marked as failed and Airtel learns this from its next enquiry.
  Refunds are handled by Airtel according to your agreement.

## Troubleshooting

| Problem                                          | What to do                                                                                 |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| Airtel gets `Invalid Token` for every request    | Check that the secret in MPM is the one Airtel uses.                                       |
| Airtel gets `Signature verification failed`      | The secrets differ, or Airtel signs with another algorithm than HS512.                     |
| `Meter not found with serial number you entered` | The customer entered a reference that is not a registered meter serial number.             |
| `Transaction amount is not enough`               | The amount is below the minimum purchase amount of the meter's tariff.                     |
| Payment arrived but the customer got no SMS      | Check that an SMS gateway is selected in Main Settings, and the transaction's detail page. |

## Technical Reference

This section is for the technical team that connects Airtel to MPM.

::: details Requests and answers

Airtel sends XML with `POST` and an `Authorization: Bearer <token>` header.
MPM always answers with HTTP 200 and an XML document; the outcome is in `STATUS`.

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
The process answer repeats the transaction ID in `TXNID`, the enquiry answer adds MPM's own ID in `REF`.

:::

::: details Status codes

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

:::

::: details Token

The token is a JWT signed with HS512 and the secret from step 2.
It must contain an `exp` claim that lies in the future and a `payload.txnId` claim equal to the transaction ID of the request (`REFERENCE1` or `TXNID`).
MPM rejects requests without a valid token.

:::
