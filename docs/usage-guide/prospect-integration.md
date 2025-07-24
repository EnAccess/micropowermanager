---
order: 23
---

# Prospect Integration Setup Guide

This guide provides step-by-step instructions for setting up and integrating Prospect with your MicroPowerManager project for data analytics and monitoring.

## Overview

Prospect is a data analytics platform that allows you to import, analyze, and visualize data from your energy systems. This integration enables you to push installation data, customer information, and other relevant metrics to Prospect for comprehensive reporting and analysis.

## Prerequisites

- Access to Prospect demo platform
- Valid login credentials for Prospect
- API client (Postman, cURL, or similar)
- Installation data to import

## Getting Started

### Step 1: Access Prospect Platform

1. Navigate to the Prospect demo platform: `https://demo.prospect.energy/`
2. Login using your provided email and password credentials

![Prospect Login Page](images/prospect-login.png)

### Step 2: Navigate to Data Import

1. After successful login, you'll land on the main dashboard
2. Navigate to the **Data** section in the main menu
3. Select **Import** from the dropdown options

![Prospect Dashboard](images/prospect-dashboard.png)

## Project Setup

### Step 3: Create a New Project

1. You'll see a page listing all existing projects with an option to create a new one
2. Click the **"NEW PROJECT"** button to start creating a project

![Projects List Page](images/projects-list.png)

3. Provide the following information:
   - **Project Name**: Enter a descriptive name for your project
   - **Description**: Add relevant details about the project scope

![New Project Creation](images/new-project-form.png)

4. Click **Create** to proceed

### Step 4: Configure Data Source

After project creation, you'll be redirected to the project page where you can configure your data sources.

1. Click on the **"Create Datasource"** link to begin configuration

![Project Page](images/project-page.png)

## Data Source Configuration

### Step 5: Select API Push Data Source

1. Choose **API Push** from the available data source options
2. You'll find this under the **"Other"** category in the datasources list
3. Provide a descriptive name like `Test API Push Import`

![Data Source Selection](images/datasource-selection.png)

4. Click **Next** to continue

### Step 6: Choose Connection Type

Select the appropriate connection type based on the data you want to sync:

- **Installations** - For device installation data
- **Agents** - For agent/merchant information  
- **Customers** - For customer profiles

For this guide, we'll select **Installations**.

![Connection Type Selection](images/connection-type.png)

### Step 7: API Configuration Details

After selecting your connection type, you'll see the API configuration page with:

- **API Endpoint**: The URL where you'll POST your data
- **Documentation Link**: Access to full API documentation
- **Authorization Details**: Bearer token for authentication

![API Configuration](images/api-configuration.png)

**Key Information Displayed:**
- POST URL: `https://demo.prospect.energy/api/in/installations`
- Authorization Header: `Bearer 99b90db993b83c303e4f7511977a8d46`
- API Documentation: `https://demo.prospect.energy/api-docs/index.html`

### Step 8: Activate Data Source

1. Click **Next** to proceed to activation
2. Check the activation checkbox to enable the data source
3. Review the data source configuration:
   - **State**: Active/Inactive status
   - **Data Category**: installations
   - **Organization**: Your organization details
   - **Secret**: API authentication token

![Data Source Activation](images/datasource-activation.png)

## Testing the Integration

### Step 9: Prepare API Request

Use an API client like Postman or cURL to test the data import:

**Request Configuration:**
- **Method**: POST
- **URL**: `https://demo.prospect.energy/api/v1/in/installations`
- **Headers**: 
  - `Authorization: Bearer 99b90db993b83c303e4f7511977a8d46`
  - `Content-Type: application/json`

### Step 10: Sample Data Payload

Use the following JSON structure for your installation data:

```json
{
  "data": [
    {
      "customer_external_id": "SMU 12 Chinsanka",
      "seller_agent_external_id": "SMU 12 Chinsanka",
      "installer_agent_external_id": "SMU 12 Chinsanka",
      "product_common_id": "Verasol",
      "device_external_id": "1",
      "parent_external_id": "1",
      "account_external_id": "1",
      "battery_capacity_wh": 500,
      "usage_category": "household",
      "usage_sub_category": "farmer",
      "device_category": "solar_home_system",
      "ac_input_source": "generator, grid, wind turbine etc..",
      "dc_input_source": "solar",
      "firmware_version": "1.2-rc3",
      "manufacturer": "HOP",
      "model": "DTZ1737",
      "primary_use": "cooking",
      "rated_power_w": 30,
      "pv_power_w": 50,
      "serial_number": "A1233754345JL",
      "site_name": "Hospital Name, Grid Name, etc",
      "payment_plan_amount_financed_principal": 1500,
      "payment_plan_amount_financed_interest": 1500,
      "payment_plan_amount_financed_total": 1500,
      "payment_plan_amount_down_payment": 1500,
      "payment_plan_cash_price": 20000,
      "payment_plan_currency": "ZMW",
      "payment_plan_installment_amount": 25000,
      "payment_plan_number_of_installments": 365,
      "payment_plan_installment_period_days": 180,
      "payment_plan_days_financed": 3650,
      "payment_plan_days_down_payment": 30,
      "payment_plan_category": "paygo",
      "purchase_date": "2022-01-01",
      "installation_date": "2022-01-01",
      "repossession_date": "2022-01-01",
      "paid_off_date": "2022-01-01",
      "repossession_category": "swap",
      "write_off_date": "2022-01-01",
      "write_off_reason": "Return",
      "is_test": true,
      "latitude": 37.775,
      "longitude": -122.419,
      "country": "UG",
      "location_area_1": "Northern",
      "location_area_2": "Agago",
      "location_area_3": "Arum",
      "location_area_4": "Alela",
      "location_area_5": "Bila"
    }
  ]
}
```

### Step 11: Execute API Test

1. Send the POST request with your configured data
2. Verify the response indicates successful data import
3. Check the Prospect platform to confirm data appears correctly

![API Testing in Postman](images/postman-test.png)

## Data Field Descriptions

### Customer & Agent Information
- `customer_external_id`: Unique identifier for the customer
- `seller_agent_external_id`: ID of the selling agent
- `installer_agent_external_id`: ID of the installation agent

### Device Specifications
- `device_external_id`: Unique device identifier
- `product_common_id`: Product model/type identifier
- `battery_capacity_wh`: Battery capacity in watt-hours
- `rated_power_w`: Device power rating in watts
- `pv_power_w`: Solar panel power capacity
- `serial_number`: Device serial number
- `manufacturer`: Device manufacturer name
- `model`: Device model number
- `firmware_version`: Current firmware version

### Usage Information
- `usage_category`: Primary usage type (e.g., household, commercial)
- `usage_sub_category`: Specific use case (e.g., farmer, clinic)
- `device_category`: Device type (e.g., solar_home_system)
- `primary_use`: Main application (e.g., cooking, lighting)

### Payment Plan Details
- `payment_plan_cash_price`: Full cash price
- `payment_plan_amount_financed_principal`: Principal amount financed
- `payment_plan_amount_financed_interest`: Interest amount
- `payment_plan_amount_down_payment`: Down payment amount
- `payment_plan_currency`: Currency code (e.g., ZMW, USD)
- `payment_plan_installment_amount`: Individual installment amount
- `payment_plan_number_of_installments`: Total number of payments
- `payment_plan_category`: Payment type (e.g., paygo, loan)

### Important Dates
- `purchase_date`: Date of purchase
- `installation_date`: Date of installation
- `paid_off_date`: Date fully paid (if applicable)
- `repossession_date`: Date of repossession (if applicable)
- `write_off_date`: Date of write-off (if applicable)

### Location Data
- `latitude`: GPS latitude coordinate
- `longitude`: GPS longitude coordinate  
- `country`: Country code
- `location_area_1` through `location_area_5`: Hierarchical location data

## Project Management

### Viewing Projects and Data Sources

1. Return to the projects list to see all configured projects
2. Click on any project to view its associated data sources
3. Monitor data source status and manage configurations as needed

![Project Management](images/project-management.png)

## Troubleshooting

### Common Issues

1. **Authentication Errors**
   - Verify the Bearer token is correctly formatted
   - Ensure the token hasn't expired
   - Check that the Authorization header is properly set

2. **Data Format Issues**
   - Validate JSON syntax
   - Ensure required fields are included
   - Check data types match expected formats

3. **Connection Problems**
   - Verify API endpoint URL is correct
   - Check network connectivity
   - Confirm data source is activated

### API Documentation

For complete API reference and additional endpoints, visit:
`https://demo.prospect.energy/api-docs/index.html`

## Next Steps

1. **S3 Bucket Integration**: Consider migrating to S3 bucket data sources for larger datasets
2. **Automated Data Sync**: Implement scheduled data synchronization
3. **Data Validation**: Set up data quality checks and validation rules
4. **Analytics Setup**: Configure dashboards and reports for imported data

## Support

For technical support or questions regarding Prospect integration:
- Review the API documentation
- Check the Prospect platform help resources
- Contact your system administrator for access-related issues

---

This integration enables comprehensive tracking and analysis of your energy system installations, providing valuable insights for business operations and customer management.