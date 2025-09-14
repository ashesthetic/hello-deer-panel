# Google Drive OAuth2 Setup Guide

## Overview

The system now uses Google Drive OAuth2 client ID flow instead of service account credentials. This provides better security and user experience as users authenticate with their own Google accounts.

## Setup Steps

### 1. Create Google Cloud Project OAuth2 Credentials

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project (or create a new one)
3. Navigate to "APIs & Services" > "Credentials"
4. Click "Create Credentials" > "OAuth 2.0 Client IDs"
5. Choose "Web application" as the application type
6. Configure the redirect URIs:
   - Add your application's callback URL: `http://your-domain.com/api/google/callback`
   - For local development: `http://localhost:8000/api/google/callback`

### 2. Enable Required APIs

Ensure the following APIs are enabled in your Google Cloud project:
- Google Drive API
- Google Sheets API (if needed)

### 3. Configure Environment Variables

Add the following to your `.env` file:

```bash
# Google Drive OAuth2 Configuration
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/api/google/callback
GOOGLE_DRIVE_FOLDER_ID=your_folder_id_here
```

### 4. Get the Google Drive Folder ID

1. Create or navigate to the folder in Google Drive where you want to store files
2. Copy the folder ID from the URL (the string after `/folders/`)
3. Add this ID to the `GOOGLE_DRIVE_FOLDER_ID` environment variable

## How It Works

### Authentication Flow

1. User clicks "Connect" in the Google Drive authentication component
2. A popup window opens with Google's OAuth2 authorization page
3. User grants permission to access their Google Drive
4. Google redirects to the callback URL with an authorization code
5. The system exchanges the code for access and refresh tokens
6. Tokens are stored in session and cache for future use

### File Operations

- **Upload**: Files are uploaded to the specified Google Drive folder
- **Access**: Files are made publicly readable with a view link
- **Download**: Files can be downloaded through the application
- **Delete**: Files are removed from Google Drive when invoices are deleted

### Token Management

- Access tokens are automatically refreshed when they expire
- Tokens are stored both in session (for web requests) and cache (for API requests)
- Users can revoke access through the authentication component

## Testing

Use the following command to test your OAuth2 setup:

```bash
php artisan google-drive:test-oauth
```

This will:
- Check if Google Drive is authenticated
- Provide the authorization URL for manual testing
- Validate your environment configuration

## Security Considerations

### Production Setup

1. Use HTTPS for all redirect URIs in production
2. Ensure proper SSL certificate configuration
3. Set `APP_ENV=production` in your environment
4. Use secure session configuration

### Privacy

- Users authenticate with their own Google accounts
- Each user's authentication is separate
- Files are uploaded to a shared folder but with proper permissions
- Access tokens are not shared between users

## Troubleshooting

### Common Issues

1. **"missing the required client identifier"**
   - Check that `GOOGLE_CLIENT_ID` is set in your `.env` file
   - Ensure you've created OAuth2 credentials (not service account)

2. **"redirect_uri_mismatch"**
   - Verify the redirect URI in Google Cloud Console matches your `GOOGLE_REDIRECT_URI`
   - Ensure the protocol (http/https) matches

3. **"insufficient permissions"**
   - Check that Google Drive API is enabled
   - Ensure the folder ID is correct and accessible

4. **Authentication popup blocked**
   - Allow popups for your domain in browser settings
   - Try the manual authentication URL provided by the test command

### Frontend Integration

The `GoogleDriveAuth` component handles:
- Checking authentication status
- Opening OAuth2 popup windows
- Providing visual feedback
- Handling authentication errors

It's automatically included in the vendor invoice add/edit pages.

## Benefits of OAuth2 Flow

1. **Better Security**: No service account keys to manage
2. **User Control**: Users can revoke access at any time
3. **Audit Trail**: Actions are associated with individual Google accounts
4. **Easier Setup**: No need to share service account keys with folder
5. **Better UX**: Clear authentication status and controls
