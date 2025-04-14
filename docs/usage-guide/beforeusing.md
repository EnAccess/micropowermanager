---
order: 2
---

# Before using MPManager

The key component(s) of the system is a mixture of device/customer.
That means both melts into each other a bit.
The only way to register a customer and a new device, is to register them both at the same time.
For that reason from now on, every registered person will be mentioned as a **customer**.

> [!INFO]
> MicroPowerManager users cannot create other device types.
> Each new device (whether a mini-grid pre-paid meter, SHS or e-bike) must be assigned under
>
> 1. a pre-defined cluster,
> 2. a mini-grid project
> 3. village (i.e. even if a company is only managing SHSs or e-bikes (and no mini-grid meters/customers), a "mini-grid project" must still be defined in MicroPowerManager under which the SHSs/e-bikes are registered.
>
> This is so because originally MPM software was developed exclusively for mini-grid operations.
> Further development work could now entail changing the name of the layer "mini-grid project" to "device catchment area".

## Register a customer & device

**Registering new mini-grid customers (electricity meter devices):** To register new mini-grid customers, the Customer Registration App has to be used.
After introducing the customer’s basic details (name, phone number, etc.), the app allows you to select the village where the customer lives, the meter manufacturer and the energy tariff that should be assigned to the meter.

> [!NOTE]
> While new customers can be created via the website interface and existing meter devices can be re-assigned to customers, new meter devices can only be registered via the mentioned Customer Registration App.

**Registering SHSs and e-bike devices:** Registration of these devices can be done both via the Customer Registration App as well as the website interface.

If user wants to use the app, there are two relevant inputs (on top of the usual customer data): 1) a dropdown menu listing all manufacturers integrated with MicroPowerManager (including both SHS and electricity meter manufacturers) and 2) an input field to insert the device serial number (kindly note that the app requests for "meter serial number" but in fact also refers to "SHS serial number" or "e-bike serial number", as applicable).
Future development work would entail adapting this field name to a more generic "device serial number".

If user wants to register a new SHS or e-bike device via the website interface, applicable menu section should be clicked (left side bar, click on "Solar Home System" or "e-bike").
Then click on the ":heavy_plus_sign:" button at the top right corner of the applicable screen and introduce requested data (device serial number, manufacturer, appliance (device) type.) Kindly check the "Solar Home Systems" and "E-bike" sections of the documentation to see a screenshot of the applicable menus.

![MPM Architecture](images/mpm-architecture.png)

## Tariffs

It is basically the energy price per kWh with an optional access rate/subscription fee.
The operator is free to define the period of that fee (for example: Every 7 days).
Such a subscription fee may also not be introduced at all.
This feature only applies for mini-grid meter device type, NOT for SHS and e-bikes.
For SHS and e-bikes, the payment scheme is set up at the time of registering the device on MicroPowerManager.

To define payment schemes for SHS and e-bikes go to "Appliance" menu (see [Appliance](appliances)).

## Payment Channels

The list of payment channels through which MicroPowerManager can receive mobile money is outlined in [Getting Started with MPM](../get-started).

## Payments

Each incoming payment has to contain the device serial number.
That is the unique number that is used to identify the other devices where the money could be spend.
After payment has been received, the system automatically checks no outstanding debt from the customer remains to be paid, before generating the applicable token.

![Payment Flow Detailed](images/payment-flow-detailed.png)

MicroPowerManager is designed in a way that the transferred money by the customer will first be used to clear outstanding debt by that customer (from an appliance loan and tariff access (if applicable)) before being converted into a token.
Therefore, only the remaining money after debt clearing will be converted into a token for the device number for which the transaction has been done.

> [!NOTE]
> a device number is required even if the customer intends not to generate a token for any of the devices (electricity meter, SHS or e-bike) but simply repay an appliance.
> In which case the customer should transfer the money amount matching the outstanding appliance debt (to avoid MicroPowerManager generating a token for that device with the surplus amount).

Please note:

> [!WARNING]
> If the entered device serial number is not valid, the system refuses the payment automatically.
