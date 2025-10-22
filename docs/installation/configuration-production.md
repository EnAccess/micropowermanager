---
order: 3
---

# Configuration for Production

> [!INFO]
> This page provides information how to **configure** an installation for MicroPowerManager.

In this section we focus on the most common instance-level settings, which are required to run MicroPowerManager in a common set up.

An installation of MicroPowerManager can be customised using environment variables.
We will mention the ones relevant to the corresponding integrations below.
The full list of all environment variables can be found [here](environment-variables.md).

## Prerequisite

We assume you know how you set environment variables.
How this will be achieved depends on the deployment scenario.

## Email

For tenant and user management, which is a core feature of MicroPowerManager it is required to have access to a mail server to send Welcome emails and required communications.

It is recommended to use a third party mail service which provides a mail server.

For example Mailgun, Google Workspace, etc..

Set the following environment variables to configure the Email provider

- `MAIL_SMTP_HOST`
- `MAIL_SMTP_DEFAULT_SENDER`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

If your Email provider requires authentication, also populate:

- `MAIL_SMTP_AUTH`
- `MAIL_SMTP_USERNAME`
- `MAIL_SMTP_PASSWORD`

Alternatively, when using an Email provider with IP whitelisting:

- Make sure cluster egress is using a static IP.
  For example of GKE, see [`egress-nat-policy.yaml`](https://github.com/EnAccess/micropowermanager/blob/main/k8s/base/gcp_gke/kustomization.yaml).
- Whitelist the NAT Gateway's static IP in the Email provider.

### Testing Email integration

A quick and dirty way to test sending of email, is to open a Laravel Tinker shell

```sh
php artisan tinker
```

Then

```php
$mailHelper = app(App\Helpers\MailHelper::class);
$mailHelper->sendPlain('test@example.org', '[TEST] Welcome to MicroPowerManager', 'lorem ipsum');
$mailHelper->sendViaTemplate('test@example.org', '[TEST] Welcome to MicroPowerManager', 'templates.mail.register_welcome', ['userName' => 'Lorem', 'companyName' => 'Ipsum']);
```

## Logging

> [!INFO]
> This section is optional, but recommended.

By default we are running MicroPowerManager using `debug` logging level.
In normal operation it is recommended to set at least `info` using

- `LOG_LEVEL`

When debugging errors or problems it can be helpful to temporarily revert `LOG_LEVEL` to `debug`.

Set up a logging channel which allows you to monitor critical errors of the application in realtime.

Currently, we support Slack logging using [incoming webhooks](https://api.slack.com/messaging/webhooks).
Set the following environment variables

- `LOG_SLACK_WEBHOOK_URL`

By default, we are logging `CRITICAL` errors to Slack.

### Testing logging setup

To test logging setup run the Artisan logging test command

```sh
php artisan log:test
```

## Configuring Trusted Proxies

MicroPowerManager uses Laravel's Trusted Proxy feature to correctly handle requests coming through load balancers or reverse proxies (such as those used in Kubernetes or cloud environments). You must configure the list of trusted proxies to ensure correct detection of client IP addresses and secure handling of headers.

### Why configure trusted proxies?

- If not set, Laravel may not correctly identify the real client IP, which can affect logging, security, and application logic.
- Paginated response links do not include https routes.
- In cloud environments (GCP, AWS), the load balancer IP ranges should be trusted.

### How to configure

1. **Set the `TRUSTEDPROXY_PROXIES` environment variable** in your backend ConfigMap. For example:

   ```yaml
   # In your ConfigMap (e.g. k8s/base/gcp_gke/configmaps.yaml)
   TRUSTEDPROXY_PROXIES: 35.191.0.0/16,130.211.0.0/22  # GCP load balancer IP ranges
   # For AWS, use the appropriate AWS ELB IP ranges or '*', if you understand the risks
   TRUSTEDPROXY_PROXIES: '*'  # Trust all proxies (not recommended for production)
   ```

2. **The application will automatically use this value** via the `src/backend/config/trustedproxy.php` config file.

3. **Recommended values:**

   - **GCP:** `35.191.0.0/16,130.211.0.0/22`
   - **AWS:** Use the documented AWS ELB IP ranges or `*` if you are behind a private network
   - **Development:** `127.0.0.1` or your proxy IP

4. **Reload your deployment** after changing the ConfigMap to apply the new settings.

> [!NOTE]
> Setting `TRUSTEDPROXY_PROXIES` to `*` trusts all proxies. Only use this in secure, private environments.

## File Storage

> [!INFO]
> This section is optional, but recommended for production environments.

MicroPowerManager supports multiple storage backends for file storage. By default, files are stored locally, but for production environments, it's recommended to use cloud storage for better scalability, reliability, and backup capabilities.

### Storage Overview

MicroPowerManager stores various types of files including:

- **Reports and Exports**: CSV and Excel files generated for data exports
- **PDF Documents**: Generated reports and invoices
- **Certificates**: SSL certificates for device integrations (e.g., MicroStar meters)
- **Geographic Data**: Cluster location and mapping data
- **Prospect Data**: Customer prospect files and extracts
- **Ticket Reports**: Outsourced ticket reports

### Storage Configuration

Set the following environment variable to configure the default storage disk:

- `FILESYSTEM_DISK` - The default storage disk to use (`local`, `s3`, or `gcs`)

### Amazon S3 Storage

To use Amazon S3 for file storage, configure the following environment variables:

#### Required S3 Configuration

- `AWS_ACCESS_KEY_ID` - Your AWS access key ID
- `AWS_SECRET_ACCESS_KEY` - Your AWS secret access key
- `AWS_DEFAULT_REGION` - The AWS region where your S3 bucket is located (e.g., `us-east-1`, `eu-west-1`)
- `AWS_BUCKET` - The name of your S3 bucket

#### Optional S3 Configuration

- `AWS_USE_PATH_STYLE_ENDPOINT` - Set to `true` if using S3-compatible services that require path-style URLs (default: `false`)

#### Example S3 Configuration

```bash
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE
AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=micropowermanager-files
```

#### S3 Bucket Setup

1. Create an S3 bucket in your preferred AWS region
2. Configure appropriate bucket policies for your use case
3. Ensure the AWS credentials have the following permissions:
   - `s3:GetObject`
   - `s3:PutObject`
   - `s3:DeleteObject`
   - `s3:ListBucket`

### Google Cloud Storage

To use Google Cloud Storage for file storage, configure the following environment variables:

#### Required GCS Configuration

- `GOOGLE_CLOUD_PROJECT_ID` - Your Google Cloud project ID
- `GOOGLE_CLOUD_STORAGE_BUCKET` - The name of your GCS bucket

#### Authentication Options

You can authenticate using either a service account key file or individual credential components:

**Option 1: Service Account Key File**

- `GOOGLE_CLOUD_KEY_FILE` - Path to your service account JSON key file

**Option 2: Individual Credential Components**

- `GOOGLE_CLOUD_ACCOUNT_TYPE` - Service account type (usually `service_account`)
- `GOOGLE_CLOUD_PRIVATE_KEY_ID` - Private key ID from service account
- `GOOGLE_CLOUD_PRIVATE_KEY` - Private key from service account
- `GOOGLE_CLOUD_CLIENT_EMAIL` - Client email from service account
- `GOOGLE_CLOUD_CLIENT_ID` - Client ID from service account
- `GOOGLE_CLOUD_AUTH_URI` - Auth URI (usually `https://accounts.google.com/o/oauth2/auth`)
- `GOOGLE_CLOUD_TOKEN_URI` - Token URI (usually `https://oauth2.googleapis.com/token`)
- `GOOGLE_CLOUD_AUTH_PROVIDER_CERT_URL` - Auth provider cert URL
- `GOOGLE_CLOUD_CLIENT_CERT_URL` - Client cert URL

#### Optional GCS Configuration

- `GOOGLE_CLOUD_STORAGE_PATH_PREFIX` - Optional path prefix for all stored files
- `GOOGLE_CLOUD_STORAGE_API_URI` - Custom storage API URI (for custom endpoints)
- `GOOGLE_CLOUD_STORAGE_API_ENDPOINT` - Custom API endpoint

#### Example GCS Configuration

```bash
FILESYSTEM_DISK=gcs
GOOGLE_CLOUD_PROJECT_ID=my-project-id
GOOGLE_CLOUD_STORAGE_BUCKET=micropowermanager-files
GOOGLE_CLOUD_KEY_FILE=/path/to/service-account-key.json
```

#### GCS Bucket Setup

1. Create a GCS bucket in your Google Cloud project
2. Create a service account with appropriate permissions
3. Download the service account key file
4. Ensure the service account has the following roles:
   - `Storage Object Admin` (for full read/write access)
   - Or custom role with `storage.objects.create`,
     `storage.objects.delete`, `storage.objects.get`,
     `storage.objects.list` permissions

### Testing Storage Configuration

To test your storage configuration, you can use Laravel Tinker:

```sh
php artisan tinker
```

Then test file operations:

```php
// Test file storage
use Illuminate\Support\Facades\Storage;


// Store a test file
$testContent = 'This is a test file for storage configuration';
$testPath = 'test/storage-test.txt';
$result = Storage::put($testPath, $testContent);

if ($result) {
    echo "File stored successfully\n";

    // Test file retrieval
    $retrievedContent = Storage::get($testPath);
    if ($retrievedContent === $testContent) {
        echo "File retrieved successfully\n";
    }

    // Test file existence
    if (Storage::exists($testPath)) {
        echo "File exists\n";
    }

    // Test file URL generation
    $url = Storage::url($testPath);
    echo "File URL: " . $url . "\n";

    // Clean up test file
    Storage::delete($testPath);
    echo "Test file cleaned up\n";
} else {
    echo "Failed to store file\n";
}
```

### Storage Recommendations

- **Development**: Use local storage for simplicity
- **Production**: Use cloud storage (S3 or GCS) for better reliability and scalability
- **Backup**: Ensure your cloud storage has appropriate backup and versioning policies
- **Security**: Use IAM roles and policies to restrict access to only necessary operations
- **Monitoring**: Set up monitoring and alerting for storage usage and costs

## Agent Apps

Placeholder, do this, do that
