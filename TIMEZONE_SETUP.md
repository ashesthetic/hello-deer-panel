# Timezone Configuration for Alberta, Canada

This document outlines the timezone configuration changes made to ensure all date/time calculations use Alberta, Canada timezone (America/Edmonton) instead of UTC.

## Overview

The project has been updated to centralize timezone handling for Alberta, Canada. All date and time operations now consistently use the `America/Edmonton` timezone to ensure accurate local time calculations.

## Changes Made

### 1. Laravel Backend Changes

#### Environment Configuration
- Added `APP_TIMEZONE=America/Edmonton` to `.env.example`
- Updated `config/app.php` to use environment variable: `'timezone' => env('APP_TIMEZONE', 'America/Edmonton')`

#### Centralized Timezone Utility
Created `app/Utils/TimezoneUtil.php` with the following methods:
- `getTimezone()` - Get application timezone
- `now()` - Create Carbon instance in application timezone
- `parse()` - Parse date string in application timezone
- `createFromFormat()` - Create Carbon from format in application timezone
- `formatNow()` - Format current time for display
- `formatDate()` - Format date for display
- `today()` - Get today's date in Y-m-d format
- `daysBeforeToday()` - Get date N days before today
- `isToday()`, `isPast()`, `isFuture()` - Date comparison methods

#### Updated Controllers
- **EmployeeController.php**: Replaced hardcoded timezone settings with `TimezoneUtil`
- **WorkHourController.php**: Updated Carbon parsing to use `TimezoneUtil`
- **DailyFuelController.php**: Updated date() calls to use `TimezoneUtil`
- **DailySaleController.php**: Updated date() calls to use `TimezoneUtil`
- **ImportDailySalesFromCsv.php**: Updated date parsing to use `TimezoneUtil`

### 2. React Frontend Changes

#### Enhanced Date Utilities
Updated `front-end/src/utils/dateUtils.ts` with:
- Added `ALBERTA_TIMEZONE` constant
- All functions now use Alberta timezone consistently
- Added new utility functions:
  - `formatTimeForDisplay()` - Format time for display
  - `formatDateDetailed()` - Format date with full details
  - `formatDateShort()` - Format date in short form
  - `formatDateTimeForDisplay()` - Format datetime for display

#### Updated Components
- **WorkHoursViewPage.tsx**: Replaced inline date formatting with centralized utilities

## Usage Examples

### Laravel Backend
```php
use App\Utils\TimezoneUtil;

// Get current time in Alberta timezone
$now = TimezoneUtil::now();

// Parse a date string in Alberta timezone
$date = TimezoneUtil::parse('2024-01-15');

// Format current time for display
$formatted = TimezoneUtil::formatNow(); // "Jan 15, 2024 2:30 PM"

// Get today's date
$today = TimezoneUtil::today(); // "2024-01-15"
```

### React Frontend
```typescript
import { 
  formatDateDetailed, 
  formatTimeForDisplay, 
  formatDateTimeForDisplay 
} from '../utils/dateUtils';

// Format date for display
const formattedDate = formatDateDetailed('2024-01-15'); // "Monday, January 15, 2024"

// Format time for display
const formattedTime = formatTimeForDisplay('14:30'); // "2:30 PM"

// Format datetime for display
const formattedDateTime = formatDateTimeForDisplay('2024-01-15T14:30:00Z'); // "Jan 15, 2024 2:30 PM"
```

## Benefits

1. **Consistency**: All date/time operations use the same timezone
2. **Accuracy**: Local time calculations are accurate for Alberta
3. **Maintainability**: Centralized timezone handling makes updates easier
4. **User Experience**: Users see times in their local timezone
5. **Compliance**: Proper handling of daylight saving time changes

## Environment Setup

To set up the timezone configuration:

1. Add to your `.env` file:
   ```
   APP_TIMEZONE=America/Edmonton
   ```

2. For the React frontend, the timezone is hardcoded to Alberta in the utilities, but you can modify `ALBERTA_TIMEZONE` constant in `dateUtils.ts` if needed.

## Testing

To verify timezone configuration:

1. **Laravel**: Check that `config('app.timezone')` returns `'America/Edmonton'`
2. **React**: Verify that date displays show Alberta time (considering daylight saving time)
3. **API**: Ensure all date responses are in Alberta timezone

## Notes

- The timezone is set to `America/Edmonton` which automatically handles daylight saving time
- All database timestamps are stored in UTC but displayed in Alberta timezone
- The React frontend uses the browser's `toLocaleString` API with explicit timezone specification
- Carbon instances in Laravel are automatically converted to the application timezone 