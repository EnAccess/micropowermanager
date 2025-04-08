## MeSomb Payment Provider

### Payment provider package for getting payments to MicroPowerManager

This plugin created for getting MeSomb payments into MicroPowerManager.

## Installation

Install the package via `composer require inensus/mesomb-payment-provider`

After the package is downloaded run `php artisan mesomb-payment-provider:install` command.

## Note

To get payments with use this package from MeSomb, you have to log in `https://mesomb.hachther.com/`
and follow these steps bellow;

### step 1

Go to the list of your services (in "My Applications") and click on the name or the "View" button of your service

### step 2

On the detail page, click on the cog icon then on "Webhook"

### step 3

Fill the webhook link field is as `http://yourdomain.com/api/mesomb`.
