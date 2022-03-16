Before using MPManager
----------------------

The key component(s) of the system is a mixture of meter/customer. That
means both melts into each other a bit. The only way to register a
customer or a meter is to register them both at the same time. For that
reason from now on, every registered person will be mentioned as a
**customer**.

Register a customer & meter
~~~~~~~~~~~~~~~~~~~~~~~~~~~

There is an additional `Android
Application <https://github.com/inensus/Customer-Meter-Registration>`__
that should be used to register a customer with a meter together. The
application allows you to select the village where the customer lives,
the meter manufacturer, and the energy tariff that should be assigned to
the meter.

Tariffs
~~~~~~~

Its basically the energy price per kWh with an optional access
rate/subscription fee. The operator is free to define the period of that
fee. Ex: Every 7 days.

Payment Channels
~~~~~~~~~~~~~~~~

or now, the system supports only incoming payments from Airtel Tanzania
and Vodacom Tanzania. Both providers are accepting Mobile Money and
notify the MPManager over a secure tunnel.

Payments
~~~~~~~~

Each incoming payment has to contain the meter number. That is the
unique number that is used to identify the other channels where the
money could spend. After payment is been received the system
automatically checks these further points; 1. Missing Asset Type Rates
2. Not paid Access Rates 3. Convert the money to energy and generate an
STS-Token for the calculated energy amount. At the end of the payment
process, the customer will be notified about each step.

At the end of the payment process the customer will be notified about
each step.

If the entered meter number is not valid the system refuses the payment
automatically.

Selling an Asset
~~~~~~~~~~~~~~~~

The system supports to sell assets to customers on a rate basis plan. A
water pump or a milling machine will be a good example of that.