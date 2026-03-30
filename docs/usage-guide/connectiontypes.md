---
order: 22
---

It is common in the rural electrification sector to have different tariff levels for different types of customers (especially in the case of mini-grid projects).
MicroPowerManager enables users to define **different customer types and groups** and to assign different tariff levels to each.

# Connection Types

**Connection Types** refer to the broad categorization of each electricity connection by the primary function the electricity supply serves.

Common examples include:

- `Household` – private residential use
- `Institutional` – public or community-serving facilities (e.g. schools, clinics)
- `Commercial` – small-scale businesses and service providers
- `Productive Use / Industrial` – larger-scale or power-intensive enterprises
- `Not Specified` – for cases where classification is unknown or not relevant

## Connection Subtypes

**Connection Subtypes** provide a more specific classification _within_ each Connection Type and are the level **used to assign different tariff levels**.

Examples include:

| Connection Type             | Example Subtypes                                                                    |
| --------------------------- | ----------------------------------------------------------------------------------- |
| Institutional               | Primary school, Health clinic, Community center, Water pumping station              |
| Commercial                  | Retail shop, Bar/restaurant, Guesthouse, Workshop, Tailoring business, Market stall |
| Productive Use / Industrial | Ice-making plant, Welding workshop, Brick making operation, Irrigation system       |
| Household                   | _(No subtypes defined by default)_                                                  |

MPM users can define various connection sub types and assign a different tariff level to each (see [Tariff](beforeusing.md#tariffs) section).

![Connection Types](images/connection-types.png)

# Connection Groups

Connection groups are an additional layer of flexibility given to MPM users in order to further categorize each connection.

A Connection Group can be used to represent a logical grouping of connections that share something in common.
Unlike connection types, **no different tariff levels** are assigned to connection groups.
Their main purpose is to simplify management, monitoring, and reporting.

Examples of possible connection groups:

- _Pilot Mini-grid A (Lakeview)_
- _Institutional Electrification Program 2025_
- _Commercial Productive Use Initiative_
- _Solar Village Cluster North_
- _Private Concession Area 3_

Connection Groups help organize data in [Reports](reports.md) and can be linked to [Targets](targets.md).
They make it easy to view totals or statistics by project, area, or other groupings.

![Connection Groups](images/connection-groups.png)

# Summary

| Concept                | Purpose                             | Affects Tariff? | Example                                  |
| ---------------------- | ----------------------------------- | --------------- | ---------------------------------------- |
| **Connection Type**    | Broad functional category           | ❌ No           | Commercial, Institutional                |
| **Connection Subtype** | Specific use within a type          | ✅ Yes          | Retail shop, Clinic                      |
| **Connection Group**   | Logical grouping across connections | ❌ No           | Pilot Mini-grid A, Solar Village Cluster |
