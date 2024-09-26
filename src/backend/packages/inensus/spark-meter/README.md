# SparkMeter API integration

This plugin uses **KoiosAPI** for the main authentication.
After it got authenticated it uses the **ThunderCloud API** for basic CRUD operations.

You need to enter the **ThunderCloud Token** on the site levels.

Koios access gives us the ability to synchronize the elder transactions( or the transaction that are not processed over Micropowermanager).

## Sms Sending

This plugin use Micropowermanager's Sms-Gateway application for notifying customers.
When their account balances reduce under their low balance warning limit and when customers make new payments, notifies through sms.

## Installation

Install the package via `composer require inensus/spark-meter`

After the package is downloaded run `php artisan spark-meter:install` command.

This command will also publish its own
vue files into the main project.
That means you need to run either `npm run production` manually or `docker-compose up node` if you're using the dockerized version.
