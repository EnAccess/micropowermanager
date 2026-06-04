---
order: 28
---

# Steama Meter Integration

This guide explains how to connect a Steamaco account to MicroPowerManager so that its sites, customers, meters, agents, and transactions are synchronized into your project.

## Overview

The Steama Meter plugin periodically pulls data from the Steamaco API and mirrors it into MicroPowerManager:

- **Sites** → registered as MiniGrids (see [Cluster assignment](#cluster-assignment) below)
- **Customers** → people with their primary address
- **Meters** → meters with their connection type, tariff, and geo location
- **Agents** → agents linked to their site's mini-grid
- **Transactions** → recorded as energy payments and customer is credited by SteamaCo

After credentials are saved, the syncs run automatically in the background via the `steama-meter:dataSync` scheduled command. Sites, Customers, Meters, and Agents sync once per day; Transactions sync every few minutes.

## Prerequisites

- Valid Steamaco API username and password.
- At least one **Cluster** registered in MicroPowerManager (Locations → Add Cluster). The plugin cannot create mini-grids without a cluster to attach them to.

## Getting started

1. Enable the **SteamaCo Meter** plugin for your company.
2. Open **SteamaCo Meter → Overview** and enter your Steamaco username and password, then **Save**. A green "Authorized" indicator confirms the credentials are valid.
3. Once authorized, use the **Get Updates From Steama.co** button on the Sites, Customers, Meters, and Agents pages to pull data on demand, or simply wait for the scheduled background sync.

## Cluster assignment

> [!WARNING]
> Every Steama **site** is registered as a **MiniGrid under the most recently created Cluster**.
> The plugin does not let you choose which cluster a site belongs to — it always attaches new
> mini-grids to whichever cluster was created last.

Because of this, before syncing sites you should make sure the cluster you want them grouped under is the **most recently created** one. If you maintain several clusters and the latest one is not the intended target, create (or re-create) the intended cluster last, then run the sync.

This is a known limitation; a future release may let you pick the target cluster explicitly.

## Sync scheduling notes

- A resource sync (Sites/Customers/Meters/Agents) only advances its schedule **after it writes to the database successfully**. A failed run stays due and is retried on the next scheduled tick rather than waiting a full day.
- A given sync action never runs concurrently for the same company — overlapping runs are dropped, so a slow sync cannot pile up.
