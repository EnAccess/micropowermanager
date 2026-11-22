---
order: 2
---

# Commission and balance

## Balance and risk balance

Agents receive cash from customers on site.
MicroPowerManager tracks two totals for every agent, both visible on the agent's profile card:

- **Balance**: the amount of company money the agent currently holds.
  Every energy or appliance sale **adds** to the balance, because the agent has collected the company's cash.
  Settling that money with the company (a receipt) **subtracts** the amount collected from the balance.
  This balance is what the agent owes the company at any moment.
- **Commission**: the commission the agent has earned but not yet been credited.
  It accumulates with every sale and is kept strictly separate from the balance until a receipt is created.

A payment the customer makes from their own phone through a payment provider (for example mobile money) goes straight to the company.
The agent never holds that money, so it does not add to their balance.

The **risk balance** is the ceiling the balance may not rise above — the maximum amount of company money an agent is allowed to hold before they must transfer it back.
When a cash sale would push the balance above the risk balance, the app refuses it with the message "Risk balance exceeded", and the agent can no longer collect cash for tokens or sell appliances.
So, when the agent hands the collected money to the company, headquarters records this through the MicroPowerManager website.
This is done by creating a **receipt** on the specific agent's profile.
When that receipt is created, the amount collected comes off the balance.
If the agent settles everything they hold, their earned commission is credited to them as an explicit **Payout** entry on the commission history, and the cash they actually hand over is the amount collected minus that commission.

> [!NOTE]
> Prior to version 0.1.34, an agent holding company cash was displayed as a negative balance.
> It is now shown as a positive "money held" figure of the same amount — only the sign and its meaning changed.

## Commission types

Agents are paid on a commission-basis.
There are 2 commission types:

- **Energy commission:** share of the energy transaction that is kept by the agent.
- **Appliance commission:** share of the appliance value that is kept by the agent.

Both values are stored as a **fraction between 0 and 1**, not as a percentage number.
So `0.1` means 10%, `0.05` means 5%, and `0.5` means 50%.

> [!WARNING]
> Do not enter a whole number.
> Entering `10` to mean "10%" is interpreted as 1000%, and `50` becomes 5000%.
> This massively overstates the commission the agent keeps and corrupts the suggested receipt amount (see [Agent Receipts](/usage-guide/agents/charges-and-receipts#agent-receipts)).
> Always enter the rate as a fraction, for example `0.1` for 10%.

::: details Worked example: following the money through one day

Suppose an agent is on a commission type with a **10% energy commission** and a **risk balance of 10,000**.

A customer pays the agent **3,000** in cash for electricity, and the agent generates the token on the spot.
The agent has now collected 3,000 on behalf of the company, so their **Balance** rises to +3,000.
Out of that sale the agent earns **300** as commission (10% of 3,000), which appears in their **Commission** total and in the commission history as an **Earned** entry.
The commission does not reduce the balance yet — the full 3,000 of collected cash stays on the balance until the agent settles up.

As the day goes on the agent keeps selling, and the balance keeps climbing.
Once a sale would push it above the **risk balance**, the app stops the agent from generating tokens or selling appliances until they settle up.
This protects the company: the agent is holding its cash, and the risk balance caps how much it is willing to have out in the field at any one time.

The agent then travels to headquarters to settle up.
A staff member creates a **receipt** on the agent's profile for the **amount collected**, which defaults to the full 3,000 the agent is holding.
The receipt shows the 300 commission credited to the agent and an **Amount Received** of 2,700 — the cash the staff member counts, because the agent keeps their commission out of the money they collected.
The 3,000 comes off the balance and the 300 leaves the commission total as a **Payout** entry, so the agent settles at a balance of zero and can start serving customers again.

The three figures on the receipt always reconcile: amount collected minus commission credited equals the cash received.
If the agent settles only part of what they hold — say 1,000 of the 3,000 — no commission is credited at that visit, the Amount Received is the full 1,000, and their 300 stays pending until they settle the balance completely.

:::
