# Facebook Post Integration Setup Guide

This guide will help you set up Facebook API integration to post fuel prices to your Facebook page.

## Prerequisites

- A Facebook account
- Admin access to the Facebook page you want to post to
- Access to Facebook Developers portal

## Step 1: Create a Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Click on "My Apps" in the top right corner
3. Click "Create App"
4. Choose "Business" as the app type
5. Fill in the required information:
   - **App Name**: "Fuel Price Poster" (or your preferred name)
   - **App Contact Email**: Your email address
6. Click "Create App"

## Step 2: Add Required Permissions

1. In your app dashboard, go to "App Settings" > "Basic"
2. Note down your **App ID** and **App Secret** (you'll need these later)
3. Go to "Add Products" in the left sidebar
4. Find "Facebook Login" and click "Set Up"
5. Choose "Web" as the platform
6. Enter your site URL (e.g., `http://localhost:3000` for development)

## Step 3: Get a Page Access Token

### Option A: Using Graph API Explorer (Recommended for Development)

1. Go to [Graph API Explorer](https://developers.facebook.com/tools/explorer/)
2. In the top right, select your app from the dropdown
3. Click "Generate Access Token"
4. When prompted, make sure to select these permissions:
   - `pages_show_list`
   - `pages_read_engagement`
   - `pages_manage_posts`
5. Authenticate and allow the permissions
6. Copy the generated access token

### Option B: Using OAuth Flow (For Production)

For production, you'll want to generate a long-lived Page Access Token:

1. Get a User Access Token with the required permissions (see Option A)
2. Exchange it for a long-lived token using this API call:
   ```
   https://graph.facebook.com/v18.0/oauth/access_token?
     grant_type=fb_exchange_token&
     client_id={app-id}&
     client_secret={app-secret}&
     fb_exchange_token={short-lived-token}
   ```
3. Use the long-lived user token to get a Page Access Token:
   ```
   https://graph.facebook.com/v18.0/me/accounts?access_token={long-lived-user-token}
   ```
4. Find your page in the response and copy its `access_token` - this is your Page Access Token

## Step 4: Get Your Facebook Page ID

1. Go to your Facebook page
2. Click "About" in the left sidebar
3. Scroll down to find "Page ID" or "Facebook Page ID"
4. Copy the numeric ID

**Alternative method:**
1. Visit: `https://graph.facebook.com/v18.0/me/accounts?access_token={YOUR_ACCESS_TOKEN}`
2. Find your page in the JSON response and copy the `id` field

## Step 5: Configure Your Application

1. Open your `.env` file in the project root
2. Add the following lines with your actual values:

```bash
# Facebook API Configuration
FACEBOOK_PAGE_ACCESS_TOKEN=your_page_access_token_here
FACEBOOK_PAGE_ID=your_page_id_here
```

**Example:**
```bash
FACEBOOK_PAGE_ACCESS_TOKEN=EAABsbCS1iHgBO7ZC8gVmN8qYCZBZBw4MqeJ3...
FACEBOOK_PAGE_ID=123456789012345
```

## Step 6: Test the Integration

1. Clear the Laravel cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Log in to your application and navigate to:
   - **Entry** → **Post**

3. Click the "Test Connection" button (if available) or try posting a test price

4. Fill in the fuel prices and click "Post"

5. Check your Facebook page to verify the post was created

## Step 7: Make the Page Access Token Long-Lived (Production)

Page Access Tokens from the Graph API Explorer expire after 1 hour. For production:

1. Exchange your short-lived user token for a long-lived user token (60 days)
2. Use that to get a Page Access Token (which never expires as long as the app is active)

### Script to Exchange Token:

```bash
curl -i -X GET "https://graph.facebook.com/v18.0/oauth/access_token?grant_type=fb_exchange_token&client_id=YOUR_APP_ID&client_secret=YOUR_APP_SECRET&fb_exchange_token=SHORT_LIVED_TOKEN"
```

Then use the returned token to get the Page Access Token:

```bash
curl -i -X GET "https://graph.facebook.com/v18.0/me/accounts?access_token=LONG_LIVED_USER_TOKEN"
```

## Troubleshooting

### Error: "Facebook credentials not configured"
- Make sure you've added `FACEBOOK_PAGE_ACCESS_TOKEN` and `FACEBOOK_PAGE_ID` to your `.env` file
- Run `php artisan config:clear` after updating the `.env` file

### Error: "Invalid OAuth access token"
- Your access token may have expired
- Generate a new long-lived token following Step 3
- Make sure the token has the required permissions

### Error: "Permissions error"
- Make sure your token has these permissions:
  - `pages_manage_posts`
  - `pages_read_engagement`
- Regenerate the token with the correct permissions

### Error: "Page not found"
- Verify your `FACEBOOK_PAGE_ID` is correct
- Make sure you're using the Page ID, not the username

### Posts not appearing
- Check if your page has any posting restrictions
- Verify the app is not in Development Mode (or add test users)
- Check the app's rate limits

## Security Best Practices

1. **Never commit your `.env` file** - It contains sensitive credentials
2. **Use environment variables** for all API keys and tokens
3. **Rotate your tokens periodically** - Generate new access tokens every few months
4. **Limit permissions** - Only request the permissions you need
5. **Monitor API usage** - Check Facebook's API usage dashboard regularly

## API Rate Limits

Facebook has rate limits for API calls:
- **Page-level limits**: 200 calls per hour per user
- **App-level limits**: Varies based on app usage

If you hit rate limits, the API will return an error. Implement proper error handling and retry logic.

## Additional Resources

- [Facebook Graph API Documentation](https://developers.facebook.com/docs/graph-api)
- [Page Access Tokens](https://developers.facebook.com/docs/pages/access-tokens)
- [Facebook API Error Codes](https://developers.facebook.com/docs/graph-api/using-graph-api/error-handling)
- [Publishing Posts](https://developers.facebook.com/docs/pages/publishing)

## Support

If you encounter issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check the browser console for frontend errors
3. Use Facebook's [Graph API Explorer](https://developers.facebook.com/tools/explorer/) to test your token and permissions
4. Verify your app is not in "Development Mode" if posting to a live page

## Testing the Implementation

After setup, your application should be able to:
- ✅ Post fuel prices to your Facebook page
- ✅ Format the post with proper price information
- ✅ Include hashtags for better reach
- ✅ Handle errors gracefully
- ✅ Show success/error messages to users

The post will appear on your Facebook page in this format:

```
⛽ Fuel Prices Update - December 12, 2025

💵 Regular (87): $3.49
💵 Midgrade (91): $3.79
💵 Premium (94): $4.09
💵 Diesel: $3.99

#FuelPrices #GasStation #FuelUpdate
```
