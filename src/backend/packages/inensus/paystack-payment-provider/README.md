# Paystack Payment Provider - Backend Package

Backend package for Paystack payment provider integration in MicroPowerManager.

## Overview

This package provides the backend functionality for integrating Paystack payment gateway with MicroPowerManager. It includes:

- API integration with Paystack
- Transaction management
- Webhook processing
- Credential management
- Database models and migrations

## Features

- **Secure Payment Processing** with Paystack integration
- **Webhook Support** with HMAC signature verification
- **Multi-currency Support** (NGN, GHS, KES, ZAR)
- **Complete Transaction Lifecycle** management
- **Credential Management** with secure storage
- **API Endpoints** for all operations

## Requirements

- PHP 8.1+
- Laravel 10+
- MicroPowerManager 2.0+
- Paystack account with API keys

## Installation

### 1. Install the Package

```bash
composer require inensus/paystack-payment-provider
```

### 2. Run the Installation Command

```bash
php artisan paystack-payment-provider:install
```

This command will:

- Create the necessary database tables
- Publish configuration files
- Set up initial credentials
- Generate routes and menu items

### 3. Configure Environment Variables

Add the following to your `.env` file:

```env
PAYSTACK_API_BASE_URL=https://api.paystack.co
PAYSTACK_API_TIMEOUT=30
PAYSTACK_VERIFY_WEBHOOK_SIGNATURE=true
```

## Usage

### Backend Integration

#### Initialize a Payment

```php
use Inensus\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;

$apiService = app(PaystackApiService::class);
$transaction = PaystackTransaction::create([
    'amount' => 5000, // Amount in kobo (50 NGN)
    'currency' => 'NGN',
    'customer_id' => 123,
    'meter_serial' => 'METER001',
]);

$result = $apiService->initializeTransaction($transaction);
```

#### Process Webhooks

```php
use Inensus\PaystackPaymentProvider\Services\PaystackWebhookService;

$webhookService = app(PaystackWebhookService::class);

// Verify webhook signature
if ($webhookService->verifyWebhook($request)) {
    $webhookService->processWebhook($request);
}
```

## API Endpoints

### Credential Management

- `GET /api/paystack/credential` - Get current credentials
- `PUT /api/paystack/credential` - Update credentials

### Transaction Management

- `POST /api/paystack/transaction/initialize` - Initialize payment
- `GET /api/paystack/transaction/verify/{reference}` - Verify transaction
- `GET /api/paystack/transactions` - List all transactions
- `GET /api/paystack/transactions/{id}` - Get specific transaction
- `PUT /api/paystack/transactions/{id}` - Update transaction
- `DELETE /api/paystack/transactions/{id}` - Delete transaction

### Webhooks

- `POST /api/paystack/webhook` - Paystack webhook endpoint

## Frontend Integration

The frontend components are now located in a separate plugin directory:
`src/frontend/src/plugins/paystack-payment-provider/`

## Configuration

### Webhook Setup

1. Configure your webhook URL in Paystack dashboard: `https://yourdomain.com/api/paystack/webhook`
2. Ensure your webhook secret is configured in the plugin settings
3. The plugin will automatically verify webhook signatures and process payments

### Currency Configuration

The plugin supports multiple currencies. Configure supported currencies in `config/paystack-payment-provider.php`:

```php
'currency' => [
    'default' => 'NGN',
    'supported' => ['NGN', 'GHS', 'KES', 'ZAR'],
],
```

## Development

### Testing

```bash
php artisan test --filter=PaystackPaymentProvider
```

## Security

- All API keys are encrypted in the database
- Webhook signatures are verified using HMAC SHA512
- HTTPS is required for production environments
- Input validation and sanitization implemented

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:

- Create an issue on GitHub
- Contact the development team
- Check the documentation
