---
order: 2
---

# Before using MPManager

The key component(s) of the system is a mixture of electricity meter/customer. That means both melts into each other a bit. The only way to register a customer or a meter is to register them both at the same time. For that reason from now on, every registered person will be mentioned as a **customer**.

## Register a customer & meter

There is an additional [Android Application] "Customer Registration App" (https://github.com/inensus/Customer-Meter-Registration>) that should be used to register a customer with a meter together. The application allows you to select the village where the customer lives, the meter manufacturer (if applicable), and the energy tariff that should be assigned to the meter.  can we clarify the process for  a SHS customer? Is the meter field also available or not anymore?

## Tariffs

Its basically the energy price per kWh with an optional access rate/subscription fee. The operator is free to define the period of that fee. Ex: Every 7 days. Such a subscription fee may also not be introduced at all.
	How is this for SHSs payments? I understand this section not to be applicable for SHS (since they are registered via appliances?)


## Payment Channels

For now, the system supports only incoming payments from Airtel Tanzania and Vodacom Tanzania. Both providers are accepting Mobile Money and notify the MPManager over a secure tunnel. Integration with Vodacom Mozambique is on-going.

## Payments

Each incoming payment has to contain the meter number/customer ID. That is the unique number that is used to identify the other channels where the money could spend. After payment has been received, the system automatically checks no outstanding debt from the customer remains to be paid, before generating the applicable token. Concretely, MPM performs following steps: 
1. Check for Missing Asset Type Rates 
2. Check for Not paid Access Rates 
3. Proceed to convert the money to energy and generate an STS-Token for the calculated energy amount. 
How do these steps differ for SHS?

At the end of the payment process, the customer will be notified about each step.
Note: If the entered meter number is not valid the system refuses the payment automatically.

## Selling an Asset

The system supports to sell assets to customers on a rate basis plan. This includes solar home systems as well as, for instance, a water pump or a milling machine.
