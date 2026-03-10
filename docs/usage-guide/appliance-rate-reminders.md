---
order: 13
---

# Appliance Rate Reminders

MicroPowerManager automatically monitors appliance rates and notifies both customers and staff when payments are upcoming or overdue. This runs as a scheduled background task (`appliance-rate:check`).

## How It Works

The system checks appliance rates on a recurring schedule and performs two actions for each matched rate:

1. **Sends an SMS reminder** to the customer.
2. **Creates a ticket** assigned to the tenant admin so the team can follow up.

## Upcoming Rates

When an appliance rate's due date falls within the configured reminder window, the system:

- Sends an SMS reminder to the customer (SMS type: `APPLIANCE_RATE`).
- Creates a ticket under the **"Customer Follow Up"** category with a description like:
  _"Dear Customer 1, should pay TSZ 50 until 2026-04-01"_.

## Overdue Rates

When an appliance rate's due date has passed and the customer has not yet paid, the system:

- Sends an overdue SMS to the customer (SMS type: `OVER_DUE_APPLIANCE_RATE`).
- Creates a ticket under the **"Payments Issue"** category with a description like:
  _"Dear Customer 1, didn't pay TSZ 50 on 2026-03-15"_.
- Marks the rate as reminded so the customer is not notified again for the same overdue rate.

## Configuration

Reminder timing is configured through **SMS Appliance Remind Rates**, which control:

- **Remind rate**: how many days before the due date an upcoming reminder is sent.
- **Overdue remind rate**: how many days after the due date an overdue reminder is sent.

These can be managed via the appliance SMS reminder rate settings (see [SMS](sms) and [Appliances](appliances)).

> [!NOTE]
> SMS reminders are only sent when the SMS reminder configuration is enabled. If disabled, no SMS notifications will be sent, but tickets will still be created for staff follow-up.
