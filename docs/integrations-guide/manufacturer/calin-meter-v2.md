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
   If your system asks for a company name at login, enter it under **Advanced options**.
4. Click **Save**.
   MPM logs in to Calin first and only saves credentials Calin accepts.

## Usage

Register meters under the **Calin Meter API V2** manufacturer.
For bulk registration, use `Calin Meter V2` as the manufacturer name.

When a customer pays, MPM requests a token for the kWh computed from the meter's tariff.
The token records the kWh Calin reports it issued, which can differ slightly because Calin rounds on its side.

From a meter's **Manufacturer Control** card you can also:

- issue an ad-hoc top-up token,
- issue a clear credit token,
- verify that the meter is still registered on Calin.

MPM logs in again by itself once Calin's access token expires.
