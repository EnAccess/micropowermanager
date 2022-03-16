## System Requirements

PHP ^8.0

Node ^v14.3

## Installation
1. Clone or download the repository
2. Build the docker containers with `docker-compose up`


## Installing Dependencies 
All  dependencies will be automatically installed on the installation step. However, if you need additional dependencies, install them in the `laravel` container.
To Install additional php dependencies enter the Docker-Container named `laravel`  navigate to `mpmanager`  & run `php ../composer.phar install XXX`

## Migrate the database 
  - Run `docker exec -it laravel /bin/bash` to jump into the laravel container
  - navigate to `mpmanager` directory with `cd mpmanager`
  - Run `php artisan migrate --seed` to initialize the Database. The `--seed`  option will create the default user to login.
  - The default user to login is `admin@admin.com` and `basic-password`.


## phpMyAdmin 
To project also includes phpMyAdmin which enables quick database operations without installing  third-party software or writing any single line into the terminal.

The default credentials for the database are; 
```
username : laravel
password: laravel
```
**Please don't forget to change these before you publish your project**

## Building the Frontend
The project will automatically build the frontend in the **production** mode. If you want to  build the project in **development** mode, change `NMP_MODE` variable in the `.env` file.

## Essential Configurations
There are bound services like the Payment Services (Vodacom Tanzania and Airtel Tanzania), Ticketing Service(Trello API), Critical Logging notification(Slack Channel), WebSocket(Pusher), etc. if you plan to get your payments through these services you need to change/edit following files/configurations

### Mobile Payment Configurations - Vodacom
1. `ips` array element in `services.php`. The file is located under `app/config/`. The element `ips` holds a list of authorized IP-addresses that are allowed to send transaction data.
2. Following changes should be done in the `.env` file
```bash
VODACOM_SPID=YOUR-SPID
VODACOM_SPPASSWORD="YOUR-PASSWORD"
VODACOM_REQUEST_URL="END-POINT WHERE YOU CONFIRM THE TRANSACTION"
VODACOM_BROKER_CRT="LOCATION-OF-.CRT-FILE"
VODACOM_SLL_KEY="LOCATION-OF-.KEY-FILE"
VODACOM_CERTIFICATE_AUTHORITY="LOCATION-OF-.CER-FILE"
VODACOM_SSL_CERT="LOCATION-OF-.PEM-FILE"
```

### Mobile Payment Configurations - Airtel
When we set up the second payment provider in our live system, we were not that experienced by setting up **VPN Tunnels** that's why we go with the idea 'one tunnel per host`. Thatswhy the airtel payment integration is on a separate project for now. We're planning to migrate it into this project soon. 

 --> **The project link comes as soon as we uploaded the project to GitHub** <-- 

Change the `api_user`, `api_password`, and `ips` in `services.php`
```php
  'airtel' => [
        'request_url' => env('AIRTEL_REQUEST_URL'),
        'api_user' => 'YOUR-USER',
        'api_password' => 'YOUR-PASSWORD',
        'ips' => [
            'ALLOWED_IPS TO SEND YOU TRANSACTION DATA'
        ],
    ]
```
The following change should be done in the `.env` file
```bash
AIRTEL_REQUEST_URL="AIRTEL SERVICE URL"
```

### STS Meter Configuration
Currently, the system supports only CALIN-STS meters. To be able to communicate with Calin and generate STS-Tokens, the following changes should be done;
1. Your key and the endpoint where you create those tokens. 
``` bash
CALIN_KEY="CALIN-KEY"
CALIN_CLIENT_URL="CALIN-CLIENT-URL"
```
2. If you have meters which can send their consumption data to CALIN's server please fill the below-listed variables too 
```bash
METER_DATA_URL="REMOTE-METER-READING-URL"
METER_DATA_KEY="METER-READING-KEY"
METER_DATA_USER="METER-READING-USER"
```
### Pusher(Web Socket)
Pusher is used to notify your admins when a new ticket is been created.
```
PUSHER_APP_ID="PUSHER-APP-ID"
PUSHER_APP_KEY="PUSHER-KEY"
PUSHER_APP_SECRET="PUSHER-APP-SECRET"
PUSHER_APP_CLUSTER="YOUR-CLUSTER ex. eu"
```

### Slack
Slack is the current critical logging service that alerts the admins when something went wrong. Like a transaction is been canceled.

```bash
LOG_SLACK_WEBHOOK_URL="SLACK-WEBHOOK-URL"
```


## Installing  Customer Registration App (Android)
Please read the project documentation to get an idea of why we're using a separate app to register customers via an Android-App.
Follow the link to get to the Customer Register App Project

## Setup Sms Communication
There are currently two supported SMS-Gateways.
1. Bongo Live Tanzania
2. Inhouse SMS-Gateway Application


### Configuration for BongoLive
**Important Note: The Bongo API integration on our system is not been maintained since early-2019.**

Firstly, you have to uncomment these lines in `app/Providers/AppServiceProvider.php`. Because the default SMSProvider is the 2nd option above.

```php
 //$this->app->singleton('SmsProvider', function ($app) {
        //   return new \App\Sms\Bongo();
        //});
```
After that, change the following configuration
```bash
 'bongo' => [
            'url' => 'http://www.bongolive.co.tz/api/sendSMS.php',
            'sender' => 'SENDER_NUMBER',
            'username' => 'USER NAME',
            'password' => 'PASSWORD',
            'key' =>'KEY',
        ],
```

### Configuration for SMS-Gateway Application
**Advice: Please read the SMS-Gateway documentation before you continue.**


To lower the costs of the system we are using the following application to send and receive SMSes over that application.
To be able to use the application you need to assign following configuration values in `services.php`

You are not forced to use our inhouse solution for SMS communication. You can change the SmsProvider easily in `app/Providers/AppServiceProvider.php`

```php
 $this->app->singleton('SmsProvider', static function ($app) {
            return new AndroidGateway();
        });
```


```bash
'sms' => [

        'android' => [
            'url' => 'https://fcm.googleapis.com/fcm/send',
            'token' => 'FIREBASE_TOKEN',
            'key' => 'PHONE_KEY',
        ],
        'callback' => 'https://mpmanager.local/api/sms/%s/confirm',
    ],
```
**Dont forget to change the `callback` variable to a globaly reachable domain**

### Change Predefined SMS Text
To change the predefined SMS texts, please edit `app/Sms/SmsTypes.php`

## Weather Data
The system shows the weather data on the Mini-Grid level. To be able to read out the data from  `Open Weather Map` service you have to register yourself there and get an **API-KEY**
Change the following value in `services.php`
```bash
'weather' => [
        'owm_app_id' => 'api_key',
    ]
```

## Email
To be able to send E-Mails please edit following configuration variables
```bash
return [
    'host' => '', //your host to send through
    'smtp_auth' => true, // enable SMTP authentication
    'username' => '',// SMTP username
    'password' => '', //SMTP username
    'smtp_secure' => PHPMailer::ENCRYPTION_STARTTLS,// default is tls
    'port' => '',
    'default_sender' => '',
    'default_message' => 'Please do not reply to this email', // adds a small footer text to your email
];
```

# Deploy for Production

The production mode will automatically install **Let's Encrypt SSL certificates**. Therefore you need firstly register a domain. 


When you have your domain, the first thing to do is editing `app.conf` and `db.conf`(if you planning to use phpMyAdmin as well) files under `NginxProxy/conf.p`.

Afer that, paste `chmod +x ./install-production.sh` to make the file executable and run it via `./install-production.sh`. This will guide you through the installation and finally, it will start the services.


# Development
The development environment is served under **http://mpmanager.local**
To reach the site over the given url;
enter the following lines to your hosts file.
#### For Linux/Mac Users
```
/etc/hosts
127.0.0.1       mpmanager.local
127.0.0.1       db.mpmanager.local
``` 
#### For Windows
```
c:\windows\system32\drivers\etc\hosts
127.0.0.1       mpmanager.local
127.0.0.1       db.mpmanager.local
```



## Generate API Documentation
To generate the API documentation, jump in the `laravel` container and type `php artisan apidoc:generate` in the **mpmanager** directory. That will create a new **docs** folder under **public** folder.
The API documentation should be available under `http://mpmanager.local/docs/`. The whole API documentation will be migrated to third-party tools like Postman or Swagger.
