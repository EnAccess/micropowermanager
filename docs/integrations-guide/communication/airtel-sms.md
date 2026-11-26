---
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Airtel bulk SMS over SMPP, sending only
---

# Airtel SMS Gateway

Airtel offers bulk SMS to business customers over SMPP, a protocol for talking directly to the operator's SMS server (SMSC).
With this plugin MicroPowerManager sends its SMS, for example token and payment confirmations, through such an Airtel account.

The plugin only sends SMS.
Incoming SMS and delivery reports are not supported.

## Prerequisites

- Access to the MPM admin panel
- An Airtel bulk SMS account with SMPP access.
  Airtel gives you a host, a port, a system ID and a password.
- A sender name registered with Airtel, at most 11 characters
- The server that runs MicroPowerManager can open a TCP connection to the host and port Airtel gave you.
  This is an outgoing connection; check your firewall, and ask Airtel whether they need to allow your server's IP address.

## Step 1: Enable the Airtel SMS plugin in MPM

1. Log into your MPM admin panel
2. Navigate to **Settings** → **Configuration** → **Plugins**
3. Find the **AirtelSmsGateway** plugin in the list
4. Toggle the switch to enable the plugin

## Step 2: Enter the connection details

1. Click on **Airtel SMS Gateway** in the sidebar, then on **Overview**
2. Enter the details Airtel gave you:
   - **Host** and **Port**: the address of Airtel's SMS server
   - **System ID** and **Password**: the login of your account
   - **Sender name**: the name your messages are sent from
3. Click **Save**

The password is stored encrypted.

## Step 3: Select Airtel SMS as your SMS gateway

1. Navigate to **Settings** → **Configuration** → **Main Settings**
2. Select **Airtel SMS Gateway (SMPP)** in the **SMS Gateway** dropdown field
3. Click **Save**

## Step 4: Test SMS functionality

1. Navigate to **Customers** and select a customer
2. On the customer detail page, send a test message with **Send SMS**
3. The message should arrive from your sender name

## Good to know

- For every SMS the plugin connects to Airtel, logs in, hands over the message and disconnects.
- Phone numbers are sent in international format without the plus sign, for example `255712345678`.
- Messages are sent in the standard SMS character set (GSM 03.38).
  Characters outside it, such as emoji or non-Latin scripts, are replaced with a question mark.
- A message longer than 160 characters is sent in several parts that the phone joins again.
  Airtel charges each part as one SMS.
- An SMS counts as sent when Airtel's server accepts it.
  Whether it reached the phone is not reported back.

## Troubleshooting

A failed SMS shows the reason in the SMS list.

| Message                                                   | What to do                                                                                         |
| --------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `Airtel SMS credentials are not configured.`              | Fill in all fields on the overview page (step 2).                                                  |
| `Could not connect to the SMSC at …`                      | Check host and port, your firewall, and whether Airtel allows your server's IP address.            |
| `The SMSC rejected the request: Invalid password …`       | Check the password.                                                                                |
| `The SMSC rejected the request: Invalid system ID …`      | Check the system ID.                                                                               |
| `The SMSC rejected the request: Bind failed …`            | The login was refused. Check system ID and password, and ask Airtel whether the account is active. |
| `The SMSC rejected the request: Invalid source address …` | The sender name is not the one registered for your account.                                        |
| `The SMSC rejected the request: Throttling error …`       | Too many messages in a short time. The message can be sent again later.                            |
| `The SMSC did not answer in time.`                        | Airtel's server was reachable but slow or busy. The message can be sent again later.               |
| `No active SMS provider is configured. …`                 | Select Airtel SMS as the SMS gateway in Main Settings (step 3).                                    |
