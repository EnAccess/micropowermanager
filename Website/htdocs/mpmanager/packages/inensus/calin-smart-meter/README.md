# CalinSmartMeter Package

Calin Smart API integration for Micropowermanager

This plugin integrates Calin meters to Micropowermanager. It uses company_name, user_name, password and password_vend for creating tokens for energy.

## Sms Sending

This plugin use Micropowermanager's  Sms-Gateway application for notifying customers.

## Installation

Install the package via `composer require inensus/calin-smart-meter`

After the package is downloaded run `php artisan calin-smart-meter:install` command.
This command will also publish its own vue files into the main project.
That means you need to run either `npm run production` manually or `docker-compose up node` if you're using the dockerized version.
