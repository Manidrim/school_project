# Admin Authentication Setup

This document describes the complete admin authentication system implemented for the blog project.

## System Overview

The authentication system includes:
- **Symfony API**: User entity, security configuration, login/logout endpoints
- **Next.js PWA**: Login page, authentication context, protected admin routes
- **Complete test coverage**: API and PWA tests

## API Components (Symfony)

### 1. User Entity
- Location: `api/src/Entity/User.php`
- Features: Email-based authentication, role management (ROLE_ADMIN)
- Password hashing with Symfony's built-in security

### 2. Security Configuration
- Location: `api/config/packages/security.yaml`
- Features: Form-based authentication, CSRF protection, role-based access control

### 3. Security Controller
- Location: `api/src/Controller/SecurityController.php`
- Routes:
  - `/login` - Login form and authentication
  - `/logout` - Logout functionality
  - `/admin` - Protected admin dashboard

### 4. User Repository
- Location: `api/src/Repository/UserRepository.php`
- Features: Password upgrade interface, email-based user lookup

### 5. Admin User Command
- Location: `api/src/Command/CreateAdminUserCommand.php`
- Usage: `php bin/console app:create-admin-user admin@example.com password123`

### 6. Database Migration
- Location: `api/migrations/Version20241224120000.php`
- Creates the users table with proper indexes

### 7. API Tests
- Location: `api/tests/Api/AuthenticationTest.php`
- Coverage: Login, logout, admin access, authentication protection

## PWA Components (Next.js)

### 1. Login Page
- Location: `pwa/pages/login.tsx`
- Features: Modern UI with Tailwind CSS, form validation, error handling

### 2. Authentication Context
- Location: `pwa/contexts/AuthContext.tsx`
- Features: State management, session handling, automatic authentication checks

### 3. Protected Admin Page
- Location: `pwa/pages/admin/index.tsx`
- Features: Authentication guard, logout functionality, integration with API Platform Admin

### 4. PWA Tests
- Location: `pwa/__tests__/`
- Files:
  - `login.test.tsx` - Login form testing
  - `auth-context.test.tsx` - Authentication context testing

## Setup Instructions

### 1. Database Setup
```bash
# Navigate to project root
cd school_project

# Start Docker containers
make start

# Run migrations (when containers are healthy)
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

# Create admin user
docker compose exec php bin/console app:create-admin-user admin@test.com admin123
```

### 2. PWA Dependencies
```bash
# Navigate to PWA directory
cd pwa

# Install dependencies
pnpm install
```

### 3. Running Tests

#### API Tests
```bash
# From project root
cd api
docker compose exec php bin/console doctrine:database:create --env=test
docker compose exec php bin/console doctrine:migrations:migrate --env=test --no-interaction
docker compose exec php vendor/bin/phpunit
```

#### PWA Tests
```bash
# From PWA directory
cd pwa
pnpm install
pnpm test
```

### 4. Development URLs
- **API**: http://localhost (Symfony application)
- **PWA**: http://localhost:3000 (Next.js application)
- **Login**: http://localhost:3000/login
- **Admin**: http://localhost:3000/admin

## Authentication Flow

1. **User visits `/login`** - Presents login form
2. **User submits credentials** - Form sends POST to Symfony `/login`
3. **Symfony validates** - Checks credentials against database
4. **Session established** - Symfony creates authenticated session
5. **PWA detects auth** - Context checks authentication status
6. **Admin access granted** - User can access protected routes
7. **Logout available** - User can logout from admin panel

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Password Hashing**: Secure password storage with auto algorithm
- **Role-Based Access**: ROLE_ADMIN required for admin access
- **Session Management**: Secure session handling
- **Input Validation**: Email and password validation
- **Error Handling**: Proper error messages and logging

## Testing Coverage

### API Tests
- ✅ Login page accessibility
- ✅ Valid login redirects to admin
- ✅ Invalid login shows errors
- ✅ Admin page requires authentication
- ✅ Authenticated user can access admin
- ✅ Logout redirects properly

### PWA Tests
- ✅ Login form rendering
- ✅ Form validation
- ✅ Successful authentication flow
- ✅ Error handling
- ✅ Loading states
- ✅ Authentication context management
- ✅ Logout functionality

## Default Admin Credentials
- **Email**: admin@test.com
- **Password**: admin123

⚠️ **Important**: Change these credentials in production!

## Troubleshooting

### Common Issues
1. **Docker containers unhealthy**: Check logs with `docker compose logs`
2. **Database connection**: Ensure database container is running
3. **CORS issues**: Check CORS configuration in `api/config/packages/nelmio_cors.yaml`
4. **Session issues**: Clear browser cookies and restart containers

### Development Tips
- Use `docker compose logs -f php` to watch API logs
- Use `pnpm dev` for PWA development with hot reload
- Test authentication with multiple browsers to verify session isolation 