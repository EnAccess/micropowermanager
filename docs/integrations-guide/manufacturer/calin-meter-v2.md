---
exclude_from_sidebar: true
mpm_plugin_status: ✅ Ready
mpm_plugin_doc: ✅ Ready
mpm_plugin_comment: Vends Calin meters through your own Calin API V2 (AMR) system
---

# Calin Meter API V2

The **CalinMeterV2** plugin vends kWh tokens for Calin meters on Calin's API V2, also called the AMR API.
Calin sets up an own API V2 system per customer, on its own host and port.
It is common for LoRaWAN and gateway-connected meters.

If you log in at `ami.calinhost.com` with a company name and vending password, use the **Calin Smart Meter** plugin instead.

## Prerequisites

- Access to the MPM admin panel.
- The address of your Calin API V2 system, including its port.
- A user ID and password from Calin, and a company name if your system asks for one at login.

## Configuration

1. Enable the plugin under **Settings** → **Configuration** → **Plugins**.
2. Open **Calin Meter API V2** → **Overview** in the sidebar.
3. Enter the API URL, user ID and password.
4. Choose how tokens are vended under **Vend by**, see [Vend by energy or by amount paid](#vend-by-energy-or-by-amount-paid) below.
5. Under **Advanced options**, enter what your system asks for:
   - **Company**: if your system asks for a company name at login.
   - **Authorization password**: if your system asks for a separate password when a token is generated.
     It is sent with every vend request.
6. Click **Save**.
   MPM logs in to Calin first and only saves credentials Calin accepts.

## Vend by energy or by amount paid

Calin's API V2 can be asked for a token in two ways.
Which one fits depends on where the tariff of your meters is kept.

### Energy (default)

MPM converts the payment to kWh with the meter's tariff in MPM and asks Calin for a token of that energy.
Use this when the tariff is managed in MPM.

### Amount paid

MPM sends the paid amount, and Calin converts it to kWh with the tariff stored on the Calin system.
Use this when your meters are set up on Calin with their own tariff, which is common for STS keypad meters.
Keep the tariff in MPM the same as on Calin, so that reports and minimum purchase amounts match.

In both modes the token records the kWh Calin reports it issued.
Calin rounds on its side: for example, 6,000 at a tariff of 1,830 per kWh is issued as 3.2 kWh, where a plain division gives 3.28.

## Usage

Register meters under the **Calin Meter API V2** manufacturer.
For bulk registration, use `Calin Meter V2` as the manufacturer name.

When a customer pays, MPM requests a token from Calin, by energy or by amount paid as configured.
The token records the kWh Calin reports it issued.

From a meter's **Manufacturer Control** card you can also:

- issue an ad-hoc top-up token,
- issue a clear credit token,
- verify that the meter is still registered on Calin.

MPM logs in again by itself once Calin's access token expires.
