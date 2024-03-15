# SteamacoMeter Package

Steamaco API integration for Micropowermanager

This plugin integrates **Steamaco meters** to Micropowermanager. It uses the same credentials as **ui.steama.co** for authentication.
After it got authenticated, the plugin synchronizes Site, Customer, Meter and Agent records from **steama.co**.

When all records been updated, the plugin will start to synchronize the elder transactions( or the transaction that are not processed over Micropowermanager) automatically.

## Sms Sending

This plugin use Micropowermanager's Sms-Gateway application for notifying customers.
When their account balances reduce under their low balance warning limit and when customers make new payments, notifies through sms.

## Installation

Install the package via `composer require inensus/steama-meter`

After the package is downloaded run `php artisan steama-meter:install` command.
This command will also publish its own vue files into the main project.
That means you need to run either `npm run production` manually or `docker-compose up node` if you're using the dockerized version.
