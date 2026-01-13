# Google Sheets Integration Setup

This guide will help you set up Google Sheets integration to automatically update fuel price data when fuel volumes are created or updated.

## Prerequisites

- A Google Cloud Console account
- A Google Sheets document where you want to store the data
- PHP Google API Client (already installed via Composer)

## Setup Steps

### 1. Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Sheets API:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Sheets API"
   - Click "Enable"

### 2. Create a Service Account

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "Service Account"
3. Fill in the service account details:
   - **Name**: `hello-deer-panel-sheets`
   - **Description**: `Service account for Hello Deer Panel Google Sheets integration`
4. Click "Create and Continue"
5. Skip the optional steps and click "Done"

### 3. Generate Service Account Key

1. Click on the newly created service account
2. Go to the "Keys" tab
3. Click "Add Key" > "Create New Key"
4. Choose "JSON" format
5. Download the JSON file
6. Rename it to `google-service-account.json`
7. Place it in `storage/app/google-service-account.json`

**Important**: Add `storage/app/google-service-account.json` to your `.gitignore` to keep credentials secure.

### 4. Prepare Your Google Sheet

1. Create a new Google Sheet or use an existing one
2. Set up your sheet structure (example):

```
   A          B          C          D          E
1  Date       Regular    Premium    Diesel     Last Updated
2  Current:   1.234      1.456      1.678      2026-01-12
```

3. Share the sheet with your service account:
   - Click "Share" button
   - Add the service account email (found in your JSON key file under `client_email`)
   - Give "Editor" permissions

### 5. Configure Environment Variables

Copy the Google Sheets configuration from `.env.example` to your `.env` file:

```bash
# Google Sheets API Configuration
GOOGLE_SHEETS_SPREADSHEET_ID=your_spreadsheet_id_here
GOOGLE_SHEETS_ENABLE_UPDATES=true
GOOGLE_SHEETS_LOG_UPDATES=true
GOOGLE_SHEETS_RETRY_ATTEMPTS=3
GOOGLE_SHEETS_RETRY_DELAY=5

# Google Sheets Cell Mappings (customize based on your sheet structure)
GOOGLE_SHEETS_REGULAR_PRICE_CELL=Sheet1!B2
GOOGLE_SHEETS_PREMIUM_PRICE_CELL=Sheet1!C2
GOOGLE_SHEETS_DIESEL_PRICE_CELL=Sheet1!D2
GOOGLE_SHEETS_LAST_UPDATED_CELL=Sheet1!E2
GOOGLE_SHEETS_HISTORY_RANGE=History!A:E
```

**To find your Spreadsheet ID:**
- Open your Google Sheet
- Look at the URL: `https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit`
- Copy the long string between `/d/` and `/edit`

### 6. Test the Integration

Run the test command to verify everything is working:

```bash
# Test connection only
php artisan google-sheets:test --connection

# Test updating values
php artisan google-sheets:test --update

# Run both tests
php artisan google-sheets:test
```

### 7. Configure Queue Processing

Since Google Sheets updates are processed in background jobs, make sure your queue worker is running:

```bash
# For development (runs in foreground)
php artisan queue:work

# For production (use supervisor or similar process manager)
php artisan queue:work --daemon
```

## How It Works

### Automatic Updates

The integration automatically triggers Google Sheets updates when:

1. **New Fuel Volume Created**: When a new fuel volume record is created with price data
2. **Fuel Volume Updated**: When price fields (`regular_price`, `premium_price`, `diesel_price`) are modified

### Update Process

1. **Model Observer**: `FuelVolumeObserver` detects changes to fuel volume records
2. **Background Job**: `UpdateGoogleSheetJob` is dispatched to handle the Google Sheets update
3. **Service**: `GoogleSheetsService` performs the actual API calls to update the sheet
4. **Retry Logic**: Failed updates are automatically retried up to 3 times

### Data Flow

```
Fuel Volume Create/Update
    ↓
FuelVolumeObserver (detects price changes)
    ↓
UpdateGoogleSheetJob (queued)
    ↓
GoogleSheetsService (updates specific cells)
    ↓
Google Sheets (updated with current prices)
```

## Customization

### Cell Mappings

You can customize which cells are updated by modifying the environment variables:

```bash
# Example: Update different cells
GOOGLE_SHEETS_REGULAR_PRICE_CELL=FuelData!A1
GOOGLE_SHEETS_PREMIUM_PRICE_CELL=FuelData!B1
GOOGLE_SHEETS_DIESEL_PRICE_CELL=FuelData!C1
GOOGLE_SHEETS_LAST_UPDATED_CELL=FuelData!D1
```

### Sheet Structure

The service supports various sheet structures. You can:
- Use different sheet names (e.g., `FuelPrices!A1` instead of `Sheet1!A1`)
- Update different cell ranges
- Add historical data tracking (configure `GOOGLE_SHEETS_HISTORY_RANGE`)

### Disable Updates

To temporarily disable Google Sheets updates:

```bash
GOOGLE_SHEETS_ENABLE_UPDATES=false
```

## Troubleshooting

### Common Issues

1. **"Insufficient Permission" Error**
   - Ensure the service account email has Editor access to your Google Sheet
   - Verify the spreadsheet ID is correct

2. **"File not found" Error**
   - Check that `storage/app/google-service-account.json` exists
   - Verify the JSON file is valid

3. **"Invalid grant" Error**
   - Regenerate the service account key
   - Ensure system time is synchronized

### Logging

All Google Sheets operations are logged. Check your Laravel logs for details:

```bash
tail -f storage/logs/laravel.log | grep "GoogleSheets"
```

### Manual Testing

You can manually trigger an update for testing:

```php
// In tinker or a test script
use App\Jobs\UpdateGoogleSheetJob;
use App\Models\FuelVolume;

$fuelVolume = FuelVolume::latest()->first();
UpdateGoogleSheetJob::dispatch($fuelVolume, 'manual_test');
```

## Security Considerations

1. **Service Account Key**: Keep the JSON key file secure and never commit it to version control
2. **Sheet Permissions**: Only grant necessary permissions to the service account
3. **Environment Variables**: Use environment variables for all configuration
4. **Queue Jobs**: Monitor job failures and implement proper error handling

## Monitoring

Monitor the integration by:

1. **Log Files**: Check Laravel logs for update success/failure
2. **Queue Status**: Monitor job queue for failed jobs
3. **Google Sheets**: Verify data is being updated correctly
4. **Test Command**: Run periodic tests with `php artisan google-sheets:test`