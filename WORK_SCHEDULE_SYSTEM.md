# Work Schedule System Documentation

## Overview
A comprehensive work schedule management system that allows creating flexible weekly schedules for employees with varying daily hours. The system supports both backend (Laravel) and frontend (React/TypeScript) functionality.

## Features

### ✅ **Flexible Daily Hours**
- Each day can have different start and end times
- Support for 6-hour days, 9-hour days, or any custom duration
- Days can be marked as "off" by leaving times empty
- Automatic calculation of total hours per day and week

### ✅ **Weekly Schedule Management**
- Create schedules for specific weeks (Monday to Sunday)
- Support for current week and future weeks
- Prevent overlapping schedules for the same employee
- Status management (Active, Draft, Inactive)

### ✅ **Employee Integration**
- Only active employees can have schedules created
- Employee relationship tracking
- Position and department information display

### ✅ **User Permissions**
- Role-based access control (Admin, Editor, Viewer)
- Create, Read, Update, Delete permissions
- Permission checks on both backend and frontend

### ✅ **Timezone Support**
- Alberta timezone (`America/Edmonton`) integration
- Proper date and time handling using `TimezoneUtil`

## Database Schema

### `work_schedules` Table
```sql
- id (Primary Key)
- employee_id (Foreign Key to employees)
- week_start_date (Date - Monday of the week)
- week_end_date (Date - Sunday of the week)
- title (String, nullable)
- notes (Text, nullable)
- status (Enum: active, inactive, draft)
- user_id (Foreign Key to users - who created it)
- created_at, updated_at, deleted_at (Timestamps)
```

### `work_schedule_days` Table
```sql
- id (Primary Key)
- work_schedule_id (Foreign Key to work_schedules)
- day_of_week (Enum: monday, tuesday, wednesday, thursday, friday, saturday, sunday)
- date (Date)
- start_time (Time, nullable)
- end_time (Time, nullable)
- hours_worked (Decimal - calculated)
- is_working_day (Boolean)
- notes (Text, nullable)
- created_at, updated_at (Timestamps)
```

## API Endpoints

### Core CRUD Operations
- `GET /api/work-schedules` - List all schedules
- `POST /api/work-schedules` - Create new schedule
- `GET /api/work-schedules/{id}` - Get specific schedule
- `PUT /api/work-schedules/{id}` - Update schedule
- `DELETE /api/work-schedules/{id}` - Delete schedule

### Additional Endpoints
- `GET /api/work-schedules/current-week` - Get current week schedules
- `GET /api/work-schedules/stats` - Get schedule statistics
- `GET /api/work-schedules/employees-without-current-week` - Get employees without current week schedules
- `GET /api/work-schedules/week-options` - Get available week options
- `GET /api/employees/{id}/work-schedules` - Get schedules for specific employee

## Frontend Components

### Pages
1. **WorkSchedulesPage** (`/work-schedules`)
   - List all work schedules
   - Search and filter functionality
   - Summary statistics
   - Create, Edit, Delete actions

2. **WorkScheduleCreatePage** (`/work-schedules/create`)
   - Form to create new schedules
   - Employee selection
   - Week selection
   - Daily schedule input with flexible hours
   - Real-time calculation of total hours

### Features
- **Responsive Design**: Works on desktop and mobile
- **Real-time Validation**: Form validation and error handling
- **Permission-based UI**: Buttons and actions based on user permissions
- **Search & Filter**: Filter by employee, status, date range
- **Summary Statistics**: Total schedules, active schedules, coverage percentage

## Business Logic

### Schedule Creation Rules
1. Only active employees can have schedules
2. No overlapping schedules for the same employee in the same week
3. Start time must be before end time
4. Hours are automatically calculated from start/end times
5. Days with no times are marked as "off"

### Data Validation
- Employee must exist and be active
- Week start date must be a valid date
- Schedule days must include all 7 days of the week
- Time format validation (HH:MM)
- Permission checks for all operations

### Calculations
- **Total Hours**: Sum of all working day hours
- **Working Days**: Count of days with start/end times
- **Average Hours/Day**: Total hours ÷ Working days
- **Week Range**: Monday to Sunday display format

## Sample Data

The system includes sample schedules with different patterns:
1. **Regular 8-hour shifts** (Monday-Friday, 9 AM - 5 PM)
2. **Flexible hours** (6-9 hour days, varied start times)
3. **Part-time schedules** (some days off, varied hours)
4. **Weekend workers** (Saturday-Sunday only)

## Usage Examples

### Creating a Schedule
1. Navigate to `/work-schedules`
2. Click "Create Schedule"
3. Select employee and week
4. Set flexible hours for each day
5. Add optional title and notes
6. Submit to create the schedule

### Managing Schedules
- View all schedules with search/filter
- Edit existing schedules
- Delete schedules (with confirmation)
- View schedule details and statistics

## Technical Implementation

### Backend (Laravel)
- **Models**: `WorkSchedule`, `WorkScheduleDay`
- **Controller**: `WorkScheduleController` with full CRUD
- **Migration**: Database schema creation
- **Seeder**: Sample data generation
- **Validation**: Comprehensive input validation
- **Permissions**: Role-based access control

### Frontend (React/TypeScript)
- **Components**: Reusable UI components
- **Pages**: Main application pages
- **API Integration**: Axios-based API calls
- **State Management**: React hooks and Redux
- **TypeScript**: Type-safe development
- **Tailwind CSS**: Modern styling

## Security Features
- Authentication required for all endpoints
- Permission-based access control
- Input validation and sanitization
- SQL injection prevention
- XSS protection

## Performance Considerations
- Database indexing on frequently queried fields
- Eager loading of relationships
- Pagination support for large datasets
- Efficient queries with proper joins

## Future Enhancements
- Bulk schedule creation
- Schedule templates
- Recurring schedules
- Schedule conflicts detection
- Integration with time tracking
- Mobile app support
- Email notifications
- Calendar integration

## Testing
The system includes:
- Sample data for testing
- API endpoint testing
- Frontend component testing
- Permission testing
- Validation testing

## Deployment
- Laravel backend with MySQL database
- React frontend with TypeScript
- Proper environment configuration
- Database migration and seeding
- File permissions and security setup
