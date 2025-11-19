---
order: 14
---

# SMS/Messages

SMS is the key communication infrastructure.
It is used during `Transactions` and `Maintenance` requests.
Headquarter staff can therefore send SMS to customers, agents and service providers via the website interface.
Furthermore, it can also be used by the company to reach out to its entire (or part of its) customer base, in instances such as wanting to inform on a specific event (such a planned/unplanned electricity cut in case of mini-grid operation, or a marketing campaign).
That is the reason why `SMS` is listed in the sidebar as an extra service.

The manager/admin can send SMS's to the customers of a specific Mini-Grid, to a specific customer group/type or single customers.

## SMS Gateway Options

MicroPowerManager supports multiple SMS gateway options:

1. **AfricasTalking** - Traditional SMS provider with wide coverage across Africa
2. **TextBee SMS Gateway** - Cost-effective solution using your own Android device
3. **Android Gateway** - ⚠️ **DEPRECATED** - Legacy fallback option (use TextBee instead)

> **⚠️ Important Note:** The built-in Android Gateway is deprecated and will be removed in a future release. We strongly recommend using **TextBee SMS Gateway** as a modern, more reliable alternative for Android-based SMS delivery. TextBee offers better reliability, monitoring, and support compared to the legacy Android Gateway.

## Setup SMS Delivery using TextBee SMS Gateway (Cost-Effective)

TextBee allows you to use your own Android device as an SMS gateway, providing up to 98% cost savings compared to traditional SMS providers. This is ideal for organizations looking to minimize SMS costs while maintaining reliable delivery.

### TextBee Prerequisites

- Access to MPM admin panel
- An Android device (Android 7.0+)
- TextBee account (free plan available)
- Internet connection for the Android device

### Step 1: Create TextBee Account

1. Visit [TextBee.dev](https://textbee.dev/)
2. Click **Sign Up** and create your account
3. Verify your email address
4. Complete the account setup

### Step 2: Install TextBee Android App

1. Download the TextBee app from the [TextBee website](https://textbee.dev/)
2. Install the app on your Android device
3. Open the app and log in with your TextBee credentials
4. Grant necessary permissions (SMS, notifications, etc.)
5. Keep the app running in the background

### Step 3: Generate API Credentials

1. Log into the TextBee dashboard at [textbee.dev](https://textbee.dev/)
2. Navigate to **API Settings**
3. Click **Generate API Key**
4. Copy the API key - you'll need this for MPM configuration
5. Note your **Device ID** from the dashboard or app

### Step 4: Enable TextBee Plugin in MPM

1. Log into your MPM admin panel
2. Navigate to **Settings** → **Plugins**
3. Find the **TextBee SMS Gateway** plugin in the list
4. Toggle the switch to **Enable** the plugin
5. Confirm the activation

### Step 5: Configure TextBee Credentials

1. After enabling the plugin, click on **TextBee SMS Gateway** in the sidebar
2. Click on **Overview** to access the configuration page
3. Enter the following credentials:
   - **API Key**: Paste the API key from Step 3
   - **Device ID**: Enter your device ID from Step 3
4. Click **Save** to store the configuration
5. The plugin will validate your credentials automatically

### Step 6: Test SMS Functionality

1. Navigate to **Customers** in your MPM dashboard
2. Select a customer to view their details
3. Ensure the customer has the Primary toggle enabled
4. On the customer detail page, locate the **Send SMS** option
5. Compose a test message
6. Send the SMS to verify the integration is working
7. The message should be sent through your Android device

### TextBee Pricing

- **Free Plan**: Up to 300 messages/month (50 per day, 1 device)
- **Pro Plan**: Up to 5,000 messages/month ($69.99/year, currently 30% off)
- **Custom Plans**: Available for enterprise needs

Visit [TextBee Pricing](https://textbee.dev/) for more details.

### TextBee Troubleshooting

**Common Issues:**

- **SMS not sending**:
  - Verify the TextBee app is running on your Android device
  - Check that your device has cellular connectivity
  - Ensure API credentials are correct in MPM
- **"No active SMS provider" error**:
  - Verify the plugin is enabled in MPM Settings → Plugins
  - Check that credentials are properly configured
- **Authentication errors**:
  - Double-check API key and Device ID in plugin settings
  - Try regenerating the API key in TextBee dashboard

**Verification Steps:**

1. Check the TextBee app on your Android device for message status
2. Review MPM logs for any error messages
3. Verify your TextBee account hasn't exceeded message limits
4. Test with different phone numbers to ensure compatibility

### TextBee Production Considerations

For production deployment:

1. Use a dedicated Android device for SMS gateway
2. Ensure the device stays powered and connected to internet
3. Monitor the TextBee app to ensure it stays running
4. Set up automatic app restart on device boot
5. Consider the Pro plan if sending more than 300 messages/month
6. Keep backup device ready in case primary fails

---

## Setup SMS Delivery using AfricasTalking (Recommended for High Volume)

This guide will walk you through the complete setup process for integrating
AfricasTalking SMS service with MicroPowerManager (MPM) to enable SMS
notifications and communications.

### AfricasTalking Prerequisites

- Access to MPM admin panel
- AfricasTalking account (free sandbox account is sufficient for testing)
- Basic understanding of API integrations

### Step 1: Create AfricasTalking Account

1. Visit [AfricasTalking](https://account.africastalking.com/auth/register)
2. Fill in your details and verify your email address
3. Complete the account setup process

![Register Africanstalking account](images/africanstalking-account-register.png)

### Step 2: Generate API Key for Plugin

1. Log into your AfricasTalking dashboard
2. Navigate to **Settings** → **API Key**
3. Click **Request**
4. Copy the generated API key - you'll need this for MPM configuration

![Request API Key](images/africanstalking-api-key.png)

### Step 3: Set Up SMS Short Code

1. In your AfricasTalking dashboard, go to **SMS** → **Short Codes**
2. Request a short code for your application
3. Note down the assigned short code number
4. Configure the short code settings as needed for your use case

![Short code from Africanstalking](images/africanstalking-sms-shortcode.png)

### Step 4: Enable AfricasTalking Plugin in MPM

1. Log into your MPM admin panel
2. Navigate to **Plugins** section
3. Find the **AfricasTalking** plugin in the available plugins list
4. Click **Enable** to activate the plugin
5. Confirm the activation

![Plugin settings page](images/africanstalking-enable-plugin.png)

### Step 5: Configure AfricasTalking Credentials

1. After enabling the plugin, go to **Plugins** → **AfricasTalking** → **Settings**
2. Enter the following credentials:
   - **API Key**: Paste the API key generated in Step 2
   - **Short Code**: Enter the short code obtained in Step 3
   - **Username**: Your AfricasTalking username
3. Save the configuration
4. Test the connection to ensure credentials are valid

![Plugin Overview page](images/africanstalking-cred-overview-page.png)

### Step 6: Register SMS Delivery Callback URL

1. In your AfricasTalking dashboard, go to **SMS** → **Callback URLs**
2. Set up the delivery callback URL to point to your MPM instance: Copy the url
   from the plugin overview page

   ```bash
   https://your-mpm-domain.com/api/africas-talking/callback/1/delivery-reports
   ```

3. Enable delivery reports to track SMS delivery status

![Register callback URL for sms delivery](images/africanstalking-sms-delivery-callback.png)

### Step 7: Test SMS Functionality

1. Navigate to **Customers** in your MPM dashboard
2. Select a customer to view their details
3. Ensure the customer Primary toggle is on i.e customer is a primary customer.
4. On the customer detail page, locate the **Send SMS** option
5. Compose a test message
6. Send the SMS to verify the integration is working
7. Check the delivery status in both MPM and AfricasTalking dashboard

![Customer detail page](images/customer-add-sms-history.png)

### AfricasTalking Troubleshooting

**Common Issues:**

- **SMS not sending**: Verify API credentials and check if the plugin is
  properly enabled
- **Delivery reports not updating**: Ensure callback URL is
  correctly configured and accessible
- **Authentication errors**: Double-check API key and username in plugin settings

**Verification Steps:**

1. Check MPM logs for any error messages
2. Verify SMS delivery in AfricasTalking dashboard
3. Test with different phone numbers to ensure compatibility

### AfricasTalking Production Considerations

When moving from sandbox to production:

1. Update API key to production environment in AfricasTalking
2. Update credentials in MPM plugin settings
3. Test thoroughly with real phone numbers
4. Monitor SMS delivery rates and costs
5. Set up proper error handling and logging

---

## SMS Gateway Priority

When multiple SMS gateways are enabled, MPM uses the following priority order:

1. **AfricasTalking** - Checked first if enabled
2. **TextBee SMS Gateway** - Checked second if enabled
3. **Viber Messaging** - Used if recipient has Viber and plugin is enabled
4. **Android Gateway** - ⚠️ **DEPRECATED** - Legacy fallback (not recommended)

You can have multiple gateways enabled simultaneously. MPM will automatically use the first available active gateway based on the priority order above.

> **⚠️ Migration Notice:** If you are currently using the Android Gateway, please migrate to TextBee SMS Gateway. TextBee provides the same Android-based SMS functionality with improved reliability, better monitoring, and active support. The migration process is simple and can be completed in under 10 minutes.

## Choosing the Right SMS Gateway

### Use AfricasTalking if

- You need high-volume SMS delivery (1000+ messages/month)
- You require guaranteed delivery rates
- You need delivery reports and analytics
- You have budget for per-message costs

### Use TextBee SMS Gateway if

- You want to minimize SMS costs (up to 98% savings)
- You have a reliable Android device available
- Your message volume is moderate (under 5000/month)
- You want full control over your SMS infrastructure
