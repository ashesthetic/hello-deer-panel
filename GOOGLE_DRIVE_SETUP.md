# Google Drive Integration Setup

This document provides instructions for setting up Google Drive integration for vendor invoice file uploads.

## Prerequisites

1. Google Cloud Console project with Drive API enabled
2. Service account credentials
3. Google Drive folder for storing invoice files

## Setup Steps

### 1. Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google Drive API:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Drive API"
   - Click "Enable"

### 2. Create Service Account

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "Service Account"
3. Fill in the service account details
4. Create a key for the service account:
   - Go to the service account
   - Click "Keys" tab
   - Click "Add Key" > "Create new key"
   - Choose "JSON" format
   - Download the JSON file

### 3. Google Drive Folder Setup

**Important**: Service accounts cannot upload to regular Google Drive folders. You need to use a **Shared Drive** (Google Workspace) or **share a regular folder** with the service account.

#### Option A: Using a Shared Drive (Recommended for Google Workspace)
1. Create a Shared Drive in Google Drive
2. Get the Shared Drive ID from the URL
3. Add the service account email as a member with "Content Manager" permissions

#### Option B: Using a Regular Folder (Personal Google Account)
1. Create a folder in your personal Google Drive
2. Share the folder with the service account email (found in the JSON credentials file)
3. Give "Editor" permissions to the service account
4. Get the folder ID from the URL (the long string after `/folders/`)

**Note**: The folder must be explicitly shared with the service account email address for uploads to work.

### 4. Laravel Configuration

1. Place the downloaded JSON credentials file in `storage/app/google-credentials.json`
2. Update your `.env` file with the **full absolute path** to the JSON file:
   ```
   GOOGLE_DRIVE_CREDENTIALS_PATH="/full/path/to/your/project/storage/app/google-credentials.json"
   GOOGLE_DRIVE_FOLDER_ID=your_folder_id_here
   ```
   
   **Important**: Make sure the path points to the actual JSON file, not just the directory.

### 5. Testing

After setup, test the integration:
1. Go to the vendor invoices page
2. Create or edit a vendor invoice
3. Upload a file
4. Verify the file appears in your Google Drive folder
5. Test viewing and downloading the file

## Features

- **Automatic Upload**: Files are automatically uploaded to Google Drive when vendor invoices are created or updated
- **Secure Storage**: Files are stored in a designated Google Drive folder with proper permissions
- **View in Drive**: Users can view files directly in Google Drive
- **Download**: Users can download files directly from the application
- **Cleanup**: Old files are automatically deleted when invoices are updated or deleted

## File Management

- Files are named with a timestamp prefix to avoid conflicts
- Original file names are preserved in the database
- Files are made publicly readable for easy access
- Local file storage is disabled in favor of Google Drive

## Troubleshooting

### Common Issues

1. **"Failed to upload file to Google Drive"**
   - Check if the service account has proper permissions
   - Verify the folder ID is correct
   - Ensure the credentials file path is correct

2. **"File not found" when downloading**
   - Check if the file still exists in Google Drive
   - Verify the file ID is correct in the database

3. **Permission denied errors**
   - Ensure the service account has Editor access to the folder
   - Check if the Google Drive API is enabled

4. **SSL Certificate errors (cURL error 77)**
   - This is common in local development environments
   - The service automatically disables SSL verification for local development
   - In production, proper SSL certificates are used
   - If you need to configure SSL manually, set the environment to 'production'

5. **"Service Accounts do not have storage quota" (Error 403)**
   - This means the folder is not properly shared with the service account
   - **Solution**: Make sure you've shared the folder with the service account email
   - The service account email is found in your credentials JSON file (usually ends with .iam.gserviceaccount.com)
   - Grant "Editor" or "Content Manager" permissions to this email address

### Logs

Check Laravel logs for detailed error messages:
```bash
tail -f storage/logs/laravel.log
```

## Migration from Local Storage

Existing files stored locally will continue to work. The system will:
1. Check for Google Drive files first
2. Fall back to local files if no Google Drive file is found
3. New uploads will always go to Google Drive

To migrate existing files to Google Drive, you would need to create a custom migration script.
