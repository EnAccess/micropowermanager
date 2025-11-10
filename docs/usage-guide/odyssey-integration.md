---
order: 24
---

# Odyssey Energy Integration Setup Guide

This guide provides easy-to-follow steps to set up an integration with the Odyssey energy platform for data analytics and reporting.

## Overview

Odyssey is a data analytics platform that pulls payment transaction data from your MicroPowerManager instance. This integration allows Odyssey to access your payment history, customer information, and transaction details for comprehensive analysis and reporting.

## Prerequisites

- Access to your Odyssey platform account
- MicroPowerManager instance with transaction data
- Admin access to MicroPowerManager to enable the Odyssey plugin

## Step 1: Access Odyssey Platform

1. Navigate to your Odyssey platform login page
2. Enter your credentials to access the dashboard

![Odyssey Login Page](images/odyssey-login-page.png)

## Step 2: Enable Odyssey Plugin in MicroPowerManager

Before Odyssey can access your data, you need to enable the Odyssey plugin:

1. Log in to your MicroPowerManager instance
2. Navigate to **Settings** → **Configuration** → **Plugins**
3. Locate the **Odyssey Data Export** plugin
4. Toggle the switch to enable the plugin

![Odyssey Plugin Overview](images/odyssey-plugin-overview-page.png)

## Step 3: Generate API Key for MPM

To allow Odyssey to securely access your MicroPowerManager data:

1. In MicroPowerManager, navigate to **Settings** → **API Keys**
2. Click **Generate New API Key**
3. Copy the generated API key (you'll need this for the Odyssey configuration)
4. Keep this key secure - it provides access to your transaction data

## Step 4: Configure Data Integration in Odyssey

1. In your Odyssey dashboard, navigate to the **Data Integration** section
2. Select **Add New Data Source**
3. Choose **Odyssey** as the data provider
4. Enter the following configuration details:
   - **API Key**: Paste the API key you generated in Step 3
   - **Base URL**: Enter your MicroPowerManager instance URL (e.g., `https://api.cloud.micropowermanager.io/api/odyssey/payments`)
   - **Site ID** (optional): If you want to filter data by specific mini-grid, enter the mini-grid name

5. Click **Save** to activate the integration

![Odyssey API Integration](images/odyssey-api-integration.png)

## Step 5: Verify Data Integration

Once configured, Odyssey will automatically pull payment data from your MicroPowerManager instance:

1. Navigate to the **Analytics** section in Odyssey
2. Check that transaction data is appearing in your dashboard

![Odyssey Data Integration Analytics](images/odyssey-data-integration-analytics-page.png)

## How It Works

The Odyssey integration works by:

1. Odyssey periodically queries the MicroPowerManager API endpoint at `/api/odyssey/payments`
2. The API returns payment transaction data including:
   - Customer information (ID, name, phone)
   - Transaction details (amount, timestamp, type)
   - Meter/device information (serial number, meter ID)
   - Location data (latitude, longitude)
   - Agent information (if applicable)
3. Odyssey processes and displays this data in analytics dashboards

**Note**: The API limits queries to 24-hour date ranges to ensure optimal performance.

## Troubleshooting

### Common Issues

1. **No Data Appearing in Odyssey**
   - Verify the Odyssey plugin is enabled in MicroPowerManager
   - Check that the API key is valid and correctly entered in Odyssey
   - Ensure your MicroPowerManager instance is accessible from Odyssey's servers

2. **Authentication Errors**
   - Regenerate the API key in MicroPowerManager
   - Update the API key in Odyssey's configuration

3. **Missing Transaction Data**
   - Confirm there are payment transactions in your MicroPowerManager database
   - Check the date range being queried by Odyssey

## Support

For technical support:

- Contact the Odyssey support team for platform-related issues
- Check MicroPowerManager logs for API errors

---

This integration enables real-time analytics and reporting of your payment transactions, providing valuable insights for business operations and financial management.
