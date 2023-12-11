# Micro Power Manager

## Terms

- **Mini-Grid:**  It is an off-grid electricity distribution network that includes small/medium-scale electricity generation.

- **Cluster:** The area that covers the grouped Mini-Grids.

- **Customer:**  It defines individual clients and businesses registered in MPManager.

- **Meter:** A pre-paid energy meter that works with an STS-Token.

- **Transaction:** Incoming payments from customers.

- **Ticket:** It is the place where customer problems, complaints, and demands are collected.

- **Tarriff:** The combination of energy(kWh) price and the fixed price(access rate).

- **Target:** It is a goal for a Mini-Grid/ Cluster that the company wants to reach at a given time.

- **Asset Type:** An object, that could be sold to locals to help/improve their productivity or live quality.

- **Maintenance:** This is the place where external contractors are assigned to predefined jobs like "connect a new house-hold to the grid", "replacing not working meters, etc.".

## Before using MPManager

The key component(s) of the system is a mixture of meter/customer. That means both melts into each other a bit. The only way to register a customer or a meter is to register them both at the same time. For that reason from now on, every registered person will be mentioned as a **customer**.

### Register a customer & meter

There is an additional [Android Application](https://github.com/inensus/Customer-Meter-Registration)  that should be used to register a customer with a meter together. The application allows you to select the village where the customer lives, the meter manufacturer, and the energy tariff that should be assigned to the meter.

### Tariffs

Its basically the energy price per kWh with an optional access rate/subscription fee. The operator is free to define the period of that fee. Ex: Every 7 days.

### Payment Channels

or now, the system supports only incoming payments from Airtel Tanzania and Vodacom Tanzania. Both providers are accepting Mobile Money and notify the MPManager over a secure tunnel.

### Payments

Each incoming payment has to contain the meter number. That is the unique number that is used to identify the other channels where the money could spend. After payment is been received the system automatically checks these further points;

 1. Missing Asset Type Rates
 2. Not paid Access Rates
 3. Convert the money to energy and generate an STS-Token for the calculated energy amount. At the end of the payment process, the customer will be notified about each step.

 At the end of the payment process the customer will be notified about each step.

If the entered meter number is not valid the system refuses the payment automatically.

### Selling an Asset

The system supports to sell assets to customers on a rate basis plan. A water pump or a milling machine will be a good example of that.

## Targets

A target can be assigned to a whole cluster or for a single Mini-Grid. The important thing by assigning a target is;

If the target is assigned to a cluster, the manager will see only that target on the cluster dashboard. However, if a single Mini-Grid or all Mini-Grids in a cluster has a defined target. The manager will see the calculated sum of these targets.

Example: Cluster 1 has following Mini-Grids; MG-1 MG-2 and MG-3

|#| MG-1 | MG-2| MG-3|
|---|---|---|---|
|New Connections | 100 | 200 | 500 |

The result of the cluster overview page would be 800 for expected new connections.

## MPManager

**Note: The pages that contain only a `List of X` and a `Add new X` button without any other key feature are not explained below.**

### Clusters Dashboard

It is basically the whole overview of the business. It shows some quick information like  total revenue, registered meters and the number of registered clusters.

There are also more sections;

1. **Financial overview:** That breaks the total revenue to Mini-Grid level. The manager can see/analyse each Mini-Grid with that.
2. **Cluster Map:**  The maps shows graphically where the clusters are located. The clusters on the map are clickable and forwards the manager to the next level dashboard **Cluster Dashboard**

To register a new cluster find the red plus button on the right bottom corner of the screen.

## Cluster Dashboard

The cluster dashboard is built similar to the clusters dashboard. There are small informative boxes at the top of the screen.
The financial overview and the map are based on Mini-Grids instead of clusters.

There are two new sections available;

1. Revenue Analysis: That is the place where all targets of MiniGrids are shown together.
2. Revenue Trends: That is a chart that breaks down the revenue to customer groups.

## Mini-Grid Dashboard

Mini-grids are the building blocks of clusters. This dashboard provides information about the selected Mini-Grid, such as **revenue per customer**, **targeted revenue per customer type**, **energy sold**, **actual payment** ,and **the daily weather forecast** is the area where it can observe and analyze the income distribution of the selected Mini-Grid.

Also on this page, the status of **tickets** created by customers in the related Mini-Grid can be observed.

## Customers

MPManager customers are listed under `Customers` in the sidebar. This table contains the customers' name, phone number, city, and meter number. The search function also includes all those fields.

By clicking on a customer, will load the details of that specific customer.

  This `customer details` page shows all information about that customer.

  Such as

- Basic Details: Name, Surname, Birth Date, etc.
- Payment flow: Is a chart that shows how often the customer makes payments
- Addresses: A list of the addresses that belong to the customer.
- Sold Assets: The assets, that bought by the customer.
- A detailed list of the payments.
- Payment types: Shows how the sent money is neem spent (Energy, Access Rate payment, etc.).
- List of tickets that belong to the customer.
- A list of the meters which belong to the customer and a map where the meters are visually displayed.

Some of the elments are editable (ex:name,surname) or addable (ex:ticket, address).

## Meters

The `Meters` link on the sidebar loads a list that contains all registered meters with some additional details such as its serial number, assigned tariff, etc.. The search area on the page searches in `serial_number` and `tariff name`.

By clicking on a meter in the list, a new `meter detail` page will be loaded. This page, contains `basic information`, `meter details`, and `meter transactions`. If the meter can send its usage data it also shows it in an additional `meter reading` section. The `Basic Information` section contains the total revenue that the meter made, the owner, when the last payment occurs, and the registration date. `Meter Details` are meter specified details such as the manufacturer name, the serial number, assigned tariff, and its connection type. `Meter transactions` is a basic list that contains all transactions that hit the meter.

## Targets (Sidebar)

 By clicking on `Targets` in the sidebar will load a page with already set targets. The list shows only the key fields of each target. To see the details of a target, click on the `Expand` button.

To add a **new Target** just click on the `New Target` on the right top side. After clicking on that button, a new page will be loaded. Firstly the manager/admin should assign a Cluster or a Mini-Grid.
Then, the date until that target is valid should be selected.

When these two steps are done; Its time to define our target with some fields like `New connections`, `Revenue per Month`,... None of these fields are marked as required. That means the manager/admin is free to enter or not to enter a value for each goal.

## Transactions

The page contains two main sections.

1. The comparison section; gives a quick overview of the situation. That section contains; Total incoming transactions, Confirmed Transactions, Cancelled Transactions, and the Revenue. The part which makes that information a bit interesting is the availability of comparison. The manager/admin can compare the day with; yesterday, same day last week, or the current week with last week or the current month with last month.

2. A basic list with incoming transactions. The list has an advanced filtering option instead of a basic search as in other pages.

By clicking on a Transaction, the `Transaction detail` page will load. The detail page contains the `Mobile Provider-specific data`, `Basic Data`, `Sent Sms`, and `Transaction Processing`.

**Mobile Money Provider-specific data:** The name of the provider and the transaction details. This information is required by the mobile money provider in case of an issue.

**Transaction Processing:** A detailed list that shows how the incoming money is been used by the system. Ex: 100$ for Energy, 20$ for Access Rate, and 400$ for Milling Machine Rate Payment.

## Tickets

MPManager is using [Trello](https://trello.com) as a ticketing platform. All tickets are basically Trello cards. The database is only holding references to the tickets. The ticketing system aims to resolve requests and complaints from customers as quickly as possible. It is important to assign a ticket to the correct category to maintain tickets easily. Therefore, there are some ticket categories. To add/ list category please click on `Categories` that is listed under `Tickets` in the sidebar.

### Adding User to Ticketing System

As already mentioned, the ticketing system is using Trello. To be able to assign tickets internally, all the staff has to be registered on [Trello](https://trello.com). The user name is been used to associate the Trello user to MPManager users.

To add a user click on `Users` in the list below `Tickets`. It will ask you the `Ticketing System Tag`. That is the name that begins with an **@** in the Trello user profile.

## Maintenance

In some cases, it is wiser to use external resources to solve small problems. Maintenance is exactly for that there. The maintenance users are some experts who are not working for the company but works per contract.

There is a form to create a **New Maintenance Request**. That page asks the manager/admin about the job todo, the deadline for the task, the person who is responsible to do that, and the price for the task. The created task will be sent out to the external person via SMS. The created maintenance job/task is also saved as a ticket. The gain by saving that task as a ticket is, in case of a problem the person who is assigned to that job can reply to the initial SMS. The incoming SMS will automatically add to the ticket as a response.

## Sms

Sms is the key communication infrastructure. It is used by `Transactions` and `Maintenance`. But what if the company wants to send some inform their customers about something like an unplanned electricity cut. That is the reason why `Sms`is listed in the sidebar as an extra service.

The manager/admin can send SMS's to a specific Mini-Grid, to a specific customer group/type or single customers.

## Reports

MPManager has a reports page where managers can download reports. This page contains weekly, monthly, and payment requests.
