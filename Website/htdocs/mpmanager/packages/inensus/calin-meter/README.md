# CalinMeter Package

Calin API integration for Micropowermanager

This plugin integrates **Calin meters** to Micropowermanager. It uses `user_id` & `api_key` for creating tokens for energy.

## Sms Sending

This plugin use Micropowermanager's Sms-Gateway application for notifying customers.

## Installation

Install the package via `composer require inensus/calin-meter`

After the package is downloaded run `php artisan calin-meter:install` command.
This command will also publish its own vue files into the main project.
That means you need to run either `npm run production` manually or `docker-compose up node` if you're using the dockerized version.
