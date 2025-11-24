# TextBee SMS Gateway Plugin

This plugin integrates [TextBee](https://textbee.dev/) SMS gateway functionality into MicroPowerManager, allowing you to send SMS messages through your own Android device.

## About

TextBee turns your Android phone into a powerful SMS gateway for your applications, providing a cost-effective alternative to traditional SMS providers.

## Installation

Install the plugin package:

```bash
php artisan textbee-sms-gateway:install
```

Then enable the plugin in the MPM admin panel under Plugins → TextbeeSmsGateway.

## Configuration

### API Credentials

Get your credentials from [TextBee](https://textbee.dev/):

1. Create a TextBee account
2. Install the TextBee Android app on your device
3. Generate an API key and note your Device ID

### Configure the Plugin

Set credentials via API:

```bash
PUT /api/textbee-sms-gateway/credential
```

**Request Body**:

```json
{
  "id": 1,
  "api_key": "your-textbee-api-key",
  "device_id": "your-textbee-device-id"
}
```

View current configuration:

```bash
GET /api/textbee-sms-gateway/credential
```

## Usage

Once configured and enabled, the plugin automatically handles SMS sending through your Android device. It integrates seamlessly with MPM's existing SMS functionality for customer notifications, transaction confirmations, and payment receipts.

## Resources

- **TextBee Website**: [https://textbee.dev/](https://textbee.dev/)
- **TextBee API Documentation**: [https://api.textbee.dev/](https://api.textbee.dev/)
- **MPM Documentation**: [Full documentation available in the main docs]

## License

MIT License - Same as MicroPowerManager
