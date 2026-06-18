---
order: 7
---

# Agents

Agents are company staff that are on site (or close to the site) and are able to support customers with tasks such as token generation (in exchange for cash payments), selling appliances, report customer issues to the company headquarters, etc.

Agents require the apps to do their work (see [Android apps](/usage-guide/android-apps)).

The user can register a new Agent on the MicroPowerManager account by going to the "Agents" menu and then pressing on ":heavy_plus_sign:".

The defined log in credentials by the user are then to be shared with the Agent, for them to be able to use the Agent account.
For more information on how to generate and manage tickets, see [Tickets](/usage-guide/tickets).

## Agent Commission Types

![Agent Commission Types](images/agent-commission-types.png)

Agents receive cash from customers on site.
An agent's **outstanding balance** is the money it owes the company (i.e. it is the money it has collected on behalf of the company and has to transfer accordingly).

The **risk balance** is the maximum amount of money that an agent can collect before it has to transfer the money to the company.
When that balance is reached, agents will not be able to collect money anymore (generate tokens) or sell appliances (his account on the app will not work.
So, when the agent sends the balance money to the company, then headquarters has to manually adjust the outstanding balance by that agent through MicroPowerManager desktop.
This is done by creating a **receipt** on the specific agent’s profile.
When that receipt is created, balance of agent is removed/decreased to 0.

Agents are paid on a commission-basis.
There are 2 commission types:

- **Energy commission:** share of the energy transaction that is kept by the agent.
- **Appliance commission:** share of the appliance value that is kept by the agent.

Both values are stored as a **fraction between 0 and 1**, not as a percentage number.
So `0.1` means 10%, `0.05` means 5%, and `0.5` means 50%.

::: warning Do not enter a whole number
Entering `10` to mean "10%" is interpreted as 1000%, and `50` becomes 5000%.
This massively overstates the commission the agent keeps and corrupts the suggested receipt amount (see [Agent Receipts](#agent-receipts) below).
Always enter the rate as a fraction, for example `0.1` for 10%.
:::

### Putting it together: a worked example

It helps to follow the money through a single day.

Suppose an agent is on a commission type with a **10% energy commission** and a **risk balance of 10,000**.

A customer pays the agent **3,000** in cash for electricity, and the agent generates the token on the spot.
The agent has now collected 3,000 on behalf of the company, so their outstanding balance rises by 3,000.
Out of that sale the agent keeps **300** as commission (10% of 3,000), so the amount they actually owe the company is **2,700**.

As the day goes on the agent keeps selling, and the outstanding balance keeps climbing.
Once it reaches the **risk balance**, the app stops the agent from generating tokens or selling appliances until they settle up.
This protects the company: the agent is holding its cash, and the risk balance caps how much it is willing to have out in the field at any one time.

The agent then travels to headquarters and hands over the cash they owe.
A staff member creates a **receipt** on the agent's profile for that amount.
The receipt clears the outstanding balance back to zero and releases the commission the agent earned, and the agent can start serving customers again.

## Assigning or changing the commission of an agent

1. Create a commission type under Agent --> Commission type (click on the ":heavy_plus_sign:" button at the top right corner).
2. You either create a new agent, or you go to the page of the specific agent for which you want to change the commission type.
3. If you create a new agent, you select the commission type you want form the drop box.
4. If you edit it from an existing agent, you go to the agent, press the "pencil" drawing  commission field, and select from the dropdown.

## Agent Transaction Entities

These records track money given to and returned by agents, keeping their balance up to date.

### Agent Charges

An **agent charge** represents money the company gives to the agent so that they can continue serving customers. Charges are created from the web panel (`Agents` → `Commission Types` → `+`). When a charge is saved, a matching balance entry is added so the credit appears in the agent’s ledger.

### Agent Receipts

An **agent receipt** records money the agent hands back to the company.
This is how you "collect" an agent's outstanding balance from the web.
You create receipts from the agent profile (`Agents` → `Receipt` → `+`).

Receipts do the opposite of charges: they settle the debt the agent owes the company.
When a receipt is saved, the system will:

- capture the latest snapshot in the agent’s balance history,
- compute how much was already owed, what was collected since the last visit, and any prior difference,
- and update the agent’s totals — what they owe the company, the commission they’ve earned, and their current balance.

#### The suggested amount is not the cash the agent collected

When you open the receipt form, the system pre-fills a **suggested amount**.
This is the agent's **outstanding balance** — the money owed to the company — **not** the total cash the agent collected from customers.

The two differ on purpose: the agent keeps their commission.

```text
suggested amount  =  cash collected  −  commission the agent keeps
```

The commission stays with the agent as their earnings, so you only receipt the company's share.
Create the receipt for **exactly the suggested amount**.

::: warning Do not add the commission back on top
If you receipt the full cash collected (commission included), the commission is counted twice — once in the amount you enter and again when the system credits the agent's earned commission.
This pushes the outstanding balance below zero and breaks reconciliation.
The form caps the amount at the suggested value and rejects anything higher for this reason.
:::

#### When the suggested amount looks wrong

If the suggested amount looks far too high (or even negative), the cause is almost always a **commission type that was set up with the wrong scale** — a whole number such as `10` or `50` instead of a fraction like `0.1` or `0.5` (see the warning under [Agent Commission Types](#agent-commission-types)).
An inflated commission rate distorts every figure derived from it, including the suggested receipt amount.

To recover:

1. Fix the commission type so the rate is a fraction between 0 and 1.
2. Create a receipt for the suggested amount to clear the agent's outstanding balance back to zero.
   This resets the balance and the risk balance.
3. From then on, new transactions apply the corrected commission, and the suggested amount will be accurate again.

#### Receipt breakdown

Each receipt automatically creates a detailed breakdown showing how the payment was calculated.
It explains what the agent already owed before this visit, how much cash they just handed in, how much additional sales activity happened since the last time they checked in, any older outstanding amounts from before that, and the remaining balance after applying the payment (which never goes below zero).

You can view these details in the receipts tab of an agent profile to understand how the receipt amount was calculated.

![Agent balance history](images/agent-balance-history.png)

The balance history panel summarizes the flow of charges, commissions, and receipts for the selected agent so you can reconcile the current balance at a glance.
