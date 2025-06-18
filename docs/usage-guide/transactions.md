---
order: 11
---

# Transactions

The "Transactions" menu includes a list of all transactions (regardless of purpose/device/appliance).

The page contains two main sections:

1. The comparison section; gives a quick overview of the situation.
   That section contains: Total incoming transactions, Confirmed Transactions, Cancelled Transactions, and the Revenue
   The part which makes that information interesting is the availability of comparison.
   The manager/admin can compare the day with; yesterday, same day last week, or the current week with last week or the current month with last month.

2. A basic list with incoming transactions.
   The list has an advanced filtering option instead of a basic search as in other pages.

By clicking on a Transaction, the `Transaction detail` page will load.
The detail page contains the `Mobile Provider-specific data`,`Basic Data`, `Sent Sms`, and `Transaction Processing`.

**Mobile Money Provider-Specific Information:** The name of the provider and the transaction details
This information is required by the mobile money provider in case of an issue.

**Transaction Processing:** A list and pie chart that shows how the incoming money from that customer has been used by MicroPowerManager system.
For example: a payment of UGX 20,000 is split in UGX 3,000 for mini-grid electricity, UGX 2,000 for mini-grid electricity Access Rate (if applicable), and UGX 15,000 for the repayment/instalment of a milling machine (appliance).

## Transaction Types

MicroPowerManager supports two main types of transactions, each serving different purposes and operating through different channels:

### Agent Transactions

Agent transactions are payments processed through authorized agents who facilitate customer payments using mobile devices. These transactions:

- Are initiated by **agents** using mobile devices on behalf of customers
- Process energy purchases for customers who cannot directly access mobile money services
- Generate commission revenue for agents based on configured commission rates
- Include Firebase notifications to keep agents informed of transaction status
- Automatically update agent balance history and commission tracking
- Are associated with physical devices through device serial numbers

### Cash Transactions

Cash transactions are manual payments processed directly by system users/administrators. These transactions:

- Are initiated by **system users** (administrators/staff) rather than agents
- Handle deferred payments for situations where immediate mobile money processing isn't available
- Do not involve commission calculations or agent-specific processing
- Are typically used for offline payments, emergency top-ups, or administrative adjustments
- Provide a simpler workflow without the complexity of agent commission and notification systems

**Key Difference:** Agent transactions represent a distributed sales model where authorized agents facilitate customer payments and earn commissions, while cash transactions represent direct administrative processing of payments by system operators.
