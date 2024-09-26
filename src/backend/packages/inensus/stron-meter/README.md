# StronMeter Package

Stron API integration for Micropowermanager

This plugin integrates **Stron meters** to Micropowermanager. It uses the api login credentials for authentication.

## Sms Sending

This plugin use Micropowermanager's Sms-Gateway application for notifying customers.

## Installation

Install the package via `composer require inensus/stron-meter`

After the package is downloaded run `php artisan stron-meter:install` command.
This command will also publish its own vue files into the main project.
That means you need to run either `npm run production` manually or `docker-compose up node` if you're using the dockerized version.
