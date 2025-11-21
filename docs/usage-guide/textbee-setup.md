---
order: 25
---

# TextBee SMS Gateway Setup

TextBee allows you to use your own Android device as an SMS gateway, providing up to 98% cost savings compared to traditional SMS providers. This is ideal for organizations looking to minimize SMS costs while maintaining reliable delivery.

## Prerequisites

- Access to MPM admin panel
- An Android device (Android 7.0+)
- TextBee account (free plan available)
- Internet connection for the Android device

## Step 1: Create TextBee Account

1. Visit [TextBee.dev](https://textbee.dev/)
2. Click **Sign Up** and create your account
3. Verify your email address
4. Complete the account setup

## Step 2: Install TextBee Android App

1. Download the TextBee app from the [TextBee website](https://textbee.dev/)
2. Install the app on your Android device
3. Open the app and log in with your TextBee credentials
4. Grant necessary permissions (SMS, notifications, etc.)
5. Keep the app running in the background

## Step 3: Generate API Credentials

1. Log into the TextBee dashboard at [textbee.dev](https://textbee.dev/)
2. Navigate to **API Settings**
3. Click **Generate API Key**
4. Copy the API key - you'll need this for MPM configuration
5. Note your **Device ID** from the dashboard or app

## Step 4: Enable TextBee Plugin in MPM

1. Log into your MPM admin panel
2. Navigate to **Settings** → **Plugins**
3. Find the **TextBee SMS Gateway** plugin in the list
4. Toggle the switch to **Enable** the plugin
5. Confirm the activation

## Step 5: Configure TextBee Credentials

1. After enabling the plugin, click on **TextBee SMS Gateway** in the sidebar
2. Click on **Overview** to access the configuration page
3. Enter the following credentials:
   - **API Key**: Paste the API key from Step 3
   - **Device ID**: Enter your device ID from Step 3
4. Click **Save** to store the configuration
5. The plugin will validate your credentials automatically

## Step 6: Select TextBee as Your SMS Gateway

1. Navigate to **Settings** → **Configuration** → **Main Settings**
2. Scroll to the **SMS Gateway** dropdown field
3. Select **TextBee SMS Gateway** from the list
4. Click **Save** to apply your selection

## Step 7: Test SMS Functionality

1. Navigate to **Customers** in your MPM dashboard
2. Select a customer to view their details
3. Ensure the customer has the Primary toggle enabled
4. On the customer detail page, locate the **Send SMS** option
5. Compose a test message
6. Send the SMS to verify the integration is working
7. The message should be sent through your Android device

## Pricing

- **Free Plan**: Up to 300 messages/month (50 per day, 1 device)
- **Pro Plan**: Up to 5,000 messages/month ($69.99/year, currently 30% off)
- **Custom Plans**: Available for enterprise needs

Visit [TextBee Pricing](https://textbee.dev/) for more details.

## Troubleshooting

**Common Issues:**

- **SMS not sending**:
  - Verify the TextBee app is running on your Android device
  - Check that your device has cellular connectivity
  - Ensure API credentials are correct in MPM
  - Verify TextBee is selected in Main Settings
- **"No active SMS provider" error**:
  - Verify the plugin is enabled in MPM Settings → Plugins
  - Check that credentials are properly configured
  - Ensure TextBee is selected as the SMS gateway in Main Settings
- **Authentication errors**:
  - Double-check API key and Device ID in plugin settings
  - Try regenerating the API key in TextBee dashboard

**Verification Steps:**

1. Check the TextBee app on your Android device for message status
2. Review MPM logs for any error messages
3. Verify your TextBee account hasn't exceeded message limits
4. Test with different phone numbers to ensure compatibility
5. Confirm TextBee is selected in Settings → Main Settings

## Production Considerations

For production deployment:

1. Use a dedicated Android device for SMS gateway
2. Ensure the device stays powered and connected to internet
3. Monitor the TextBee app to ensure it stays running
4. Set up automatic app restart on device boot
5. Consider the Pro plan if sending more than 300 messages/month
6. Keep backup device ready in case primary fails
7. Regularly check message logs in both TextBee dashboard and MPM

## Why Choose TextBee?

TextBee is ideal if you:

- Want to minimize SMS costs (up to 98% savings)
- Have a reliable Android device available
- Your message volume is moderate (under 5000/month)
- Want full control over your SMS infrastructure

For higher volumes or mission-critical messaging, consider [AfricasTalking](/docs/usage-guide/africastalking-setup.md).
