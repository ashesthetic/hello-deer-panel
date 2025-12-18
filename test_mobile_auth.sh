#!/bin/bash

# Mobile Google Drive Authentication Test Script

echo "🔍 Testing Mobile Google Drive Authentication Improvements"
echo "=========================================================="

# Test 1: Check if the backend endpoints are accessible
echo "📡 Testing backend endpoints..."

echo "1. Testing auth-url endpoint..."
AUTH_URL_RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/auth_url_test.json \
  -H "Authorization: Bearer test-token" \
  -H "Accept: application/json" \
  http://localhost:8000/api/google/auth-url)

if [ "$AUTH_URL_RESPONSE" = "200" ]; then
    echo "✅ Auth URL endpoint working"
else
    echo "❌ Auth URL endpoint failed (HTTP $AUTH_URL_RESPONSE)"
fi

echo "2. Testing auth-status endpoint..."
AUTH_STATUS_RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/auth_status_test.json \
  -H "Authorization: Bearer test-token" \
  -H "Accept: application/json" \
  http://localhost:8000/api/google/auth-status)

if [ "$AUTH_STATUS_RESPONSE" = "200" ]; then
    echo "✅ Auth status endpoint working"
else
    echo "❌ Auth status endpoint failed (HTTP $AUTH_STATUS_RESPONSE)"
fi

echo "3. Testing new test-connection endpoint..."
TEST_CONN_RESPONSE=$(curl -s -w "%{http_code}" -o /tmp/test_conn.json \
  -H "Authorization: Bearer test-token" \
  -H "Accept: application/json" \
  http://localhost:8000/api/google/test-connection)

if [ "$TEST_CONN_RESPONSE" = "200" ]; then
    echo "✅ Test connection endpoint working"
else
    echo "❌ Test connection endpoint failed (HTTP $TEST_CONN_RESPONSE)"
fi

# Test 2: Check frontend build
echo ""
echo "🏗️  Testing frontend build..."

if [ -f "/Users/ash/Documents/Locals/hello-deer-panel/app/public/front-end/build/index.html" ]; then
    echo "✅ Frontend build exists"
    
    # Check if GoogleDriveAuth component was built
    if grep -q "GoogleDriveAuth" /Users/ash/Documents/Locals/hello-deer-panel/app/public/front-end/build/static/js/main.*.js 2>/dev/null; then
        echo "✅ GoogleDriveAuth component included in build"
    else
        echo "⚠️  Could not verify GoogleDriveAuth component in build"
    fi
else
    echo "❌ Frontend build not found"
fi

# Test 3: Check mobile-specific features in source
echo ""
echo "📱 Testing mobile-specific features..."

COMPONENT_FILE="/Users/ash/Documents/Locals/hello-deer-panel/app/public/front-end/src/components/GoogleDriveAuth.tsx"

if grep -q "isMobileDevice" "$COMPONENT_FILE"; then
    echo "✅ Mobile device detection implemented"
else
    echo "❌ Mobile device detection not found"
fi

if grep -q "showMobileFallback" "$COMPONENT_FILE"; then
    echo "✅ Mobile fallback mechanism implemented"
else
    echo "❌ Mobile fallback mechanism not found"
fi

if grep -q "connectionStatus" "$COMPONENT_FILE"; then
    echo "✅ Connection status monitoring implemented"
else
    echo "❌ Connection status monitoring not found"
fi

# Test 4: Check backend improvements
echo ""
echo "🔧 Testing backend improvements..."

SERVICE_FILE="/Users/ash/Documents/Locals/hello-deer-panel/app/public/app/Services/GoogleDriveService.php"

if grep -q "clearStoredTokens" "$SERVICE_FILE"; then
    echo "✅ Enhanced token cleanup implemented"
else
    echo "❌ Enhanced token cleanup not found"
fi

if grep -q "Log::debug.*Google Drive" "$SERVICE_FILE"; then
    echo "✅ Enhanced logging implemented"
else
    echo "❌ Enhanced logging not found"
fi

CONTROLLER_FILE="/Users/ash/Documents/Locals/hello-deer-panel/app/public/app/Http/Controllers/GoogleAuthController.php"

if grep -q "testConnection" "$CONTROLLER_FILE"; then
    echo "✅ Connection testing endpoint implemented"
else
    echo "❌ Connection testing endpoint not found"
fi

# Test 5: Check error handling improvements
echo ""
echo "🚨 Testing error handling improvements..."

VENDOR_CONTROLLER="/Users/ash/Documents/Locals/hello-deer-panel/app/public/app/Http/Controllers/Api/VendorInvoiceController.php"

if grep -q "reconnect_required" "$VENDOR_CONTROLLER"; then
    echo "✅ Enhanced error messages implemented"
else
    echo "❌ Enhanced error messages not found"
fi

if grep -q "reconnect_suggested" "$VENDOR_CONTROLLER"; then
    echo "✅ Reconnection suggestions implemented"
else
    echo "❌ Reconnection suggestions not found"
fi

echo ""
echo "📋 Summary:"
echo "=========="
echo "✅ = Feature implemented and working"
echo "⚠️  = Feature may be present but needs verification"
echo "❌ = Feature missing or not working"
echo ""
echo "🎯 Next steps for mobile testing:"
echo "1. Start the Laravel server: php artisan serve"
echo "2. Test on actual mobile devices"
echo "3. Check browser console for errors"
echo "4. Verify popup behavior on different mobile browsers"
echo "5. Test the fallback authentication flow"
echo ""
echo "📱 Mobile browsers to test:"
echo "- iOS Safari"
echo "- Chrome Mobile (iOS/Android)"
echo "- Samsung Internet"
echo "- Firefox Mobile"

# Cleanup temp files
rm -f /tmp/auth_url_test.json /tmp/auth_status_test.json /tmp/test_conn.json
