# Hello Deer!

A scalable login/logout application built with Laravel (backend) and React + Redux + TypeScript (frontend).

## Project Structure

```
├── app/                    # Laravel backend
│   ├── Http/Controllers/   # API controllers
│   ├── Models/            # Eloquent models
│   └── ...
├── front-end/             # React frontend
│   ├── src/
│   │   ├── components/    # React components
│   │   ├── store/         # Redux store, slices, sagas
│   │   ├── services/      # API services
│   │   └── ...
│   └── ...
└── README.md
```

## Features

- **Backend (Laravel)**
  - RESTful API with Laravel Sanctum authentication
  - User management with secure password hashing
  - CORS configuration for frontend communication
  - SQLite database for development

- **Frontend (React + TypeScript)**
  - Modern React with TypeScript
  - Redux Toolkit for state management
  - Redux Saga for async operations
  - Tailwind CSS for styling
  - React Router for navigation
  - Axios for API communication

## Prerequisites

- PHP 8.1+
- Composer
- Node.js 16+
- npm or yarn

## Setup Instructions

### Backend Setup

1. **Install PHP dependencies:**
   ```bash
   composer install
   ```

2. **Environment configuration:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=UserSeeder
   ```

4. **Start the Laravel development server:**
   ```bash
   php artisan serve
   ```

### Frontend Setup

1. **Navigate to frontend directory:**
   ```bash
   cd front-end
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Start the development server:**
   ```bash
   npm start
   ```

## API Endpoints

- `POST /api/login` - User login
- `POST /api/logout` - User logout (requires authentication)
- `GET /api/user/profile` - Get user profile (requires authentication)

## Test Credentials

- **Email:** test@example.com
- **Password:** password

## Development

### Backend Development

The Laravel backend is configured to run on `http://127.0.0.1:8000/`. You can start it with:

```bash
php artisan serve
```

### Frontend Development

The React frontend runs on `http://localhost:3100` and communicates with the Laravel API.

## Scalability Features

This application is designed to be scalable with:

- **Modular Architecture:** Separate backend and frontend
- **State Management:** Redux with Redux Toolkit and Sagas
- **Type Safety:** TypeScript throughout the frontend
- **API Design:** RESTful API with proper authentication
- **Database:** Easy to switch between SQLite (dev) and MySQL/PostgreSQL (production)
- **Component Structure:** Reusable React components
- **Routing:** Client-side routing with React Router

## Production Deployment

### Backend
- Use a production web server (Apache/Nginx)
- Set up proper SSL certificates
- Configure environment variables
- Use a production database (MySQL/PostgreSQL)
- Set up proper caching (Redis)

### Frontend
- Build the application: `npm run build`
- Serve static files from a web server
- Configure environment variables for API endpoints

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
