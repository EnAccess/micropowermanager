---
order: 2
---

# Before using MPManager

The key component(s) of the system is a mixture of device/customer. That means both melts into each other a bit. The only way to register a customer and a new device, is to register them both at the same time. For that reason from now on, every registered person will be mentioned as a **customer**.

## Register a customer & device

**Registering new mini-grid customers (electricity meter devices):** To register new mini-grid customers, the Customer Registration App has to be used. After introducing the customer’s basic details (name, phone number, etc.), the app allows you to select the village where the customer lives, the meter manufacturer and the energy tariff that should be assigned to the meter. **Note that while new customers can be created via the website interface and existing meter devices can be re-assigned to customers, new meter devices can only be registered via the mentioned Customer Registration App.**

**Registering SHSs and e-bike devices:** Registration of these devices can be done both via the Customer Registration App as well as the website interface. If user wants to use the app, there are two relevant inputs (on top of the usual customer data): 1) a dropdown menu listing all manufacturers integrated with MPM (including both SHS and electricity meter manufacturers) and 2) an input field to insert the device serial number (kindly note that the app requests for “meter serial number” but in fact also refers to “SHS serial number” or “e-bike serial number”, as applicable). Future development work would entail adapting this field name to a more generic “device serial number”.

![MPM Architecture](images/mpm-architecture.png)

## Tariffs

It is basically the energy price per kWh with an optional access rate/subscription fee. The operator is free to define the period of that fee. Ex: Every 7 days. Such a subscription fee may also not be introduced at all. This feature only applies for mini-grid meter device type, NOT for SHS and e-bikes. For SHS and e-bikes, the payment scheme is set up at the time of registering the device on MPM.

To define payment schemes for SHS and e-bikes go to “Appliance” menu (see “Appliance” section below).

## Payment Channels

The list of payment channels through which MPM can receive mobile money is outlined on the section “Getting Started with MPM above”.

## Payments

Each incoming payment has to contain the device serial number. That is the unique number that is used to identify the other devices where the money could be spend. After payment has been received, the system automatically checks no outstanding debt from the customer remains to be paid, before generating the applicable token.

![Payment Flow Detailed](images/payment-flow-detailed.png)

MPM is designed in a way that the transferred money by the customer will first be used to clear outstanding debt by that customer (from an appliance loan and tariff access (if applicable)) before being converted into a token. Therefore, only the remaining money after debt clearing will be converted into a token for the device number for which the transaction has been done.

Note: a device number is required even if the customer intends not to generate a token for any of the devices (electricity meter, SHS or e-bike) but simply repay an appliance. In which case the customer should transfer the money amount matching the outstanding appliance debt (to avoid MPM generating a token for that device with the surplus amount).

Note: If the entered device serial number is not valid, the system refuses the payment automatically.

## Selling an Appliance

The system supports to sell SHS and e-bike devices as well as appliances to customers on a rate basis plan. User can create whatever appliance it uses to sell (water pump, electric pressure cooker, mill, TV, etc.). The steps to sell a SHS or e-bike to a customer as well as registering a new electricity device are explained on the subsection “Register a customer & device” above.

In this subsection, the procedure to sell/assign a new appliance (non-device) to a customer is outlined:

1. If the customer is not registered, first use MPM website interface to register a new customer (see “Customers” section below”). If the customer already exists, skip this point.

2. Go to the “Appliance” menu of the website interface, click on “+” and define a new appliance (name and appliance cost/price).

3. Go to the “Customer” menu, find the customer to which the created appliance is to be sold, go to “Sold Appliances”, click “+” and assign the mentioned customer the recently created appliance. MPM asks the user to select one of the 2 re-payment scheme options:

   a) **Installation count based**: user defines the down payment, the number of instalments under which the total appliance cost is to be financed, and the rate type (monthly or weekly). MPM gives as output the instalment amount the customer has to pay.

   b) **Instalment cost based**: unlike the case above, in this case the user defines the instalment amount (as well as total appliance cost and payment rates (weekly or monthly). MPM then calculates the number of instalments under which the total appliance cost is to be paid.

   Note: Future development work would include to update the Agent App so that not only appliances but also SHS and e-bike devices can be registered (right now Agent App does not enable to add a device with a serial number).

![New Appliance](images/new-appliance.png)
![Customer Sold Appliance](images/customer-sold-appliance.png)
![Appliance Payment Scheme](images/appliance-payment-scheme.png)
