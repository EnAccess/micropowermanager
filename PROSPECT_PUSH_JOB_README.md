# Prospect Push Job Conversion

## Overview
The `prospect:push` command has been converted from a synchronous CLI command to a Laravel Job that can be queued and scheduled.

## Changes Made

### 1. Created ProspectPushJob
- **File**: `src/backend/app/Jobs/ProspectPushJob.php`
- **Extends**: `AbstractJob` for tenant abstraction
- **Queue**: Uses `redis` connection and `prospect_push` queue
- **Features**: 
  - Handles CSV file loading
  - Processes data with test/dry-run options
  - Sends data to Prospect API
  - Comprehensive logging
  - Tenant-aware database operations

### 2. Updated ProspectPush Command
- **File**: `src/backend/app/Console/Commands/ProspectPush.php`
- **Changes**: 
  - Removed synchronous processing logic
  - Now dispatches `ProspectPushJob` instead
  - Maintains all existing CLI options
  - Provides immediate feedback about job dispatch

### 3. Added Scheduling
- **File**: `src/backend/bootstrap/app.php`
- **Schedule**: Runs every minute for testing purposes
- **Command**: `prospect:push`

## Benefits

### Queue Advantages
- **Timeout Handling**: Jobs can run longer without CLI timeout issues
- **Logging**: Built-in job tracking and logging
- **Retry Logic**: Failed jobs can be retried automatically
- **Monitoring**: Queue monitoring and failed job tracking
- **Scalability**: Can process multiple jobs in parallel

### Tenant Abstraction
- **AbstractJob**: Inherits tenant-aware database operations
- **Company Isolation**: Each job runs in the correct tenant context
- **Job Tracking**: Company-specific job status tracking

## Usage

### Manual Execution
```bash
# Run the command (dispatches job)
php artisan prospect:push

# With options
php artisan prospect:push --file=/path/to/file.csv --test --dry-run
```

### Scheduled Execution
The job runs automatically every minute (as configured in `app.php`)

### Queue Management
```bash
# Check queue status
php artisan queue:work --queue=prospect_push

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Configuration

### Environment Variables
- `PROSPECT_API_TOKEN`: API authentication token
- `PROSPECT_API_URL`: Prospect API endpoint
- `QUEUE_DRIVER`: Set to `redis` for production (default: `sync`)

### Queue Configuration
- **Connection**: Redis
- **Queue Name**: `prospect_push`
- **Retry After**: 90 seconds (default)
- **Timeout**: 30 seconds (HTTP request timeout)

## Testing

### Local Testing
1. Ensure Redis is running
2. Set `QUEUE_DRIVER=redis` in `.env`
3. Run `php artisan queue:work --queue=prospect_push`
4. Execute `php artisan prospect:push`

### Production Deployment
1. Configure Redis connection
2. Set up queue workers
3. Monitor job execution via logs
4. Use queue monitoring tools

## Migration Notes

### From Old Command
- **Before**: Command processed data synchronously
- **After**: Command dispatches job, job processes data asynchronously
- **Logging**: Now uses Laravel's job logging system
- **Error Handling**: Improved with job failure tracking

### Backward Compatibility
- All CLI options remain the same
- Command signature unchanged
- Output format similar (job dispatch confirmation)
- Existing scripts continue to work

## Future Enhancements

### Potential Improvements
- **Batch Processing**: Process multiple files in sequence
- **Progress Tracking**: Real-time progress updates
- **Web Interface**: Job monitoring dashboard
- **Alerting**: Notifications for failed jobs
- **Metrics**: Performance and success rate tracking
