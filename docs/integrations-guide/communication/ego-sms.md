---
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Bulk SMS provider in Uganda, sending only
---

# EgoSMS Gateway

[EgoSMS](https://www.egosms.co/) is a bulk SMS provider in Uganda.
With this plugin MicroPowerManager sends its SMS, for example token and payment confirmations, through your EgoSMS account.

The plugin only sends SMS.
Incoming SMS and delivery reports are not supported.

## Prerequisites

- Access to the MPM admin panel
- An EgoSMS account with SMS credit
- A sender ID registered for your account, at most 11 characters.
  Ask EgoSMS support if you do not have one yet.

## Step 1: Enable the EgoSMS plugin in MPM

1. Log into your MPM admin panel
2. Navigate to **Settings** → **Configuration** → **Plugins**
3. Find the **EgoSmsGateway** plugin in the list
4. Toggle the switch to enable the plugin

## Step 2: Enter your EgoSMS credentials

1. Click on **EgoSMS Gateway** in the sidebar, then on **Overview**
2. Enter the following credentials:
   - **Username**: the username of your EgoSMS account
   - **Password**: the password of your EgoSMS account
   - **Sender ID**: the sender name registered with EgoSMS
3. Click **Save**

The password is stored encrypted.

## Step 3: Select EgoSMS as your SMS gateway

1. Navigate to **Settings** → **Configuration** → **Main Settings**
2. Select **EgoSMS Gateway** in the **SMS Gateway** dropdown field
3. Click **Save**

## Step 4: Test SMS functionality

1. Navigate to **Customers** and select a customer
2. On the customer detail page, send a test message with **Send SMS**
3. The message should arrive from your sender ID

## Good to know

- MicroPowerManager stores phone numbers in international format, for example `+256712345678`.
  EgoSMS expects the number without the plus sign, so the plugin removes it.
- Every SMS is one request to `https://www.egosms.co/api/v1/json/`.
  The server that runs MicroPowerManager must be able to reach that address.
- EgoSMS charges your account for every accepted SMS.
  Keep an eye on your SMS credit in the EgoSMS dashboard.

## Troubleshooting

A failed SMS shows the reason in the SMS list.

| Message                                   | What to do                                                                                                                                  |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `EgoSMS credentials are not configured.`  | Enter the username, password and sender ID (step 2).                                                                                        |
| `EgoSMS message sending failed: …`        | EgoSMS refused the SMS. The text after the colon is the reason EgoSMS gave, for example a wrong username or password, or not enough credit. |
| `Could not reach EgoSMS: …`               | The server could not connect to EgoSMS. Check its internet connection and try again.                                                        |
| `No active SMS provider is configured. …` | Select EgoSMS as the SMS gateway in Main Settings (step 3).                                                                                 |

## References

- [EgoSMS website](https://www.egosms.co/)
- [EgoSMS developer documentation](https://developers.pahappa.com/)
