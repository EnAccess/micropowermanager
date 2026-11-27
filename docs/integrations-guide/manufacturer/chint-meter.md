---
order: 31
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Vends STS tokens for keypad meters through the Chint prepaid web service
---

# Chint Meter

This guide explains how to vend tokens for [Chint](https://www.chintglobal.com/) STS keypad meters from MicroPowerManager.

## Overview

The **ChintMeter** plugin asks the Chint prepaid platform for a recharge token whenever a customer pays for a Chint meter.
MicroPowerManager stores the token and sends it to the customer by SMS.

The plugin only vends credit tokens.
Chint's web service offers no remote reading or relay switching, so those functions are not available.

### Pre-requisites

1. An account on the Chint prepaid platform with access to its web service (`PrepaidWebService.asmx`).
   Chint support provides the service address, a user name and a password.
2. Every meter is registered as a customer on the Chint platform.
   The plugin identifies a meter by its **Customer ID** on the Chint platform, which has to be the meter's serial number in MicroPowerManager.
3. The server that runs MicroPowerManager can reach the service address.
   Ask Chint whether they need to allow your server's IP address.

### Integration

1. Enable the `ChintMeter` plugin under **Settings** → **Configuration** → **Plugins**.
   This adds the manufacturer **Chint Meters**.
2. Open **Chint Meter** → **Overview** and fill in the form:

   | Field                       | Value                                                                                 |
   | --------------------------- | ------------------------------------------------------------------------------------- |
   | API URL                     | The full address of the web service, ending in `PrepaidWebService.asmx`.              |
   | User Name                   | The user name from Chint.                                                             |
   | User Password               | The password from Chint.                                                              |
   | Payment converted to kWh by | Who turns the paid amount into energy, see [Tariff handling](#tariff-handling) below. |

3. Register your meters with the manufacturer **Chint Meters**.
   Use the Customer ID from the Chint platform as the serial number, and assign the tariff the customer pays.

Payments for these meters now produce Chint tokens.

## Tariff handling

Chint's web service takes a money amount and converts it to energy with the tariff stored on the Chint platform.
The **Payment converted to kWh by** setting decides how MicroPowerManager deals with that.

### Chint platform (default)

MicroPowerManager sends the paid amount, and Chint applies its tariff.
Before each purchase the plugin compares Chint's tariff price with the total price of the meter's tariff in MicroPowerManager.
If they differ, the purchase is stopped and the transaction fails, so that the customer is never charged at a price other than the one shown in MicroPowerManager.

Use this when you keep the same tariff on both systems.

### MicroPowerManager

MicroPowerManager calculates the energy from its own tariff, rounded to 0.1 kWh.
It then sends Chint the amount that buys exactly this energy at Chint's tariff price.

Use this when the tariff on the Chint platform cannot be kept in line with the one in MicroPowerManager, for example because your account is not allowed to change it.

Example: the tariff in MicroPowerManager is 1,304.8 per kWh and the tariff on the Chint platform is 1,000 per kWh.
A customer pays 10,000.
MicroPowerManager calculates 10,000 / 1,304.8 = 7.7 kWh and sends Chint an amount of 7,700.
Chint issues a token for 7.7 kWh.

> [!WARNING]
> In this mode the amounts in Chint's own sales reports are not the amounts your customers paid.
> Use MicroPowerManager for revenue figures.

In both modes MicroPowerManager records the energy that Chint reports for the token.

> [!INFO]
> Chint can also be set up to deduct fees or taxes from each purchase.
> The energy of the token is then lower than the amount divided by the tariff price, in both modes.
> MicroPowerManager does not add such fees to the amount it sends.

## Good to know

- A purchase is two calls to Chint: a validation that returns the customer's tariff price, then the transaction that returns the token.
- Each call waits at most 30 seconds for Chint.
  If Chint does not answer or rejects the purchase, the transaction is marked as failed.
- On installations running with `APP_ENV` set to `demo` or `development` the plugin does not call Chint and returns a fixed debug token.
- Chint's server certificate is not verified, because Chint serves the web service from a bare IP address.

## Troubleshooting

A failed purchase shows the reason in the transaction's details.

| Message                                                              | What to do                                                                                                           |
| -------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| `Chint credentials are not configured.`                              | Enter the user name and password on the overview page.                                                               |
| `Could not reach Chint at …`                                         | Check the API URL, and ask Chint whether your server's IP address is allowed.                                        |
| `Chint returned an unexpected response (HTTP …). Check the API URL.` | The address answers, but not as the prepaid web service; correct the API URL.                                        |
| `Chint's tariff price (…) does not match the price of tariff …`      | Align the tariff on the Chint platform with the one in MicroPowerManager, or switch **Payment converted to kWh by**. |
| `Chint rejected the request: … (code N)`                             | Chint refused the request; see the reason codes below.                                                               |

Reason codes returned by Chint:

| Code | Reason                                      |
| ---- | ------------------------------------------- |
| 0    | Other reason                                |
| 1    | No permission to access the service         |
| 2    | Customer id does not exist                  |
| 3    | Customer status anomaly                     |
| 4    | Customer meter anomaly                      |
| 5    | SGC is disabled                             |
| 6    | Tariff anomaly                              |
| 7    | Tax anomaly                                 |
| 8    | Customer meter key anomaly                  |
| 9    | Customer has been locked                    |
| 10   | Out of limit times power purchase one day   |
| 11   | The prepaid amount exceeds maximum limit    |
| 12   | The prepaid amount lower than minimum limit |
| 13   | TID already exists                          |
| 14   | TID does not exist                          |
| 15   | TID already cancels                         |
| 16   | Not previous TID                            |
| 17   | Station credit is not enough                |
| 18   | Function transfer parameters error          |

Code 1 usually means a wrong user name or password, or a server clock that is off: the password and request signatures are derived from the current UTC date and time.

## References

Chint does not publish the API documentation online.
Ask Chint support for the document "Vending System WEB Services API Description".
The plugin uses its `Validation` and `Transaction` functions.
