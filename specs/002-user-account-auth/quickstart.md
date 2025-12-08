# Quick Start: User Account & Authentication

**Date**: December 8, 2025  
**Feature**: User Account & Authentication  
**Framework**: Symfony 8 + Doctrine + Twig + Webpack Encore

## Overview

This guide provides step-by-step instructions for setting up and developing the user authentication system in the Hermio project. Follow these instructions to get the authentication feature running locally.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ and npm/yarn
- MySQL/PostgreSQL database
- Git

## 1. Initial Setup

### Clone and Setup Project

```bash
# Clone the repository (if not already done)
git clone <repository-url> hermio
cd hermio

# Checkout the authentication feature branch
git checkout 002-user-account-auth

# Install PHP dependencies
cd app
composer install

# Install JavaScript dependencies
npm install
```

### Environment Configuration

```bash
# Copy environment file
cp .env .env.local

# Configure your database in .env.local
DATABASE_URL="mysql://username:password@127.0.0.1:3306/hermio_db"

# Configure mailer (for email verification)
MAILER_DSN=smtp://localhost:1025
```

## 2. Database Setup

### Create Database and Run Migrations

```bash
# Create the database
php bin/console doctrine:database:create

# Run existing migrations
php bin/console doctrine:migrations:migrate

# Generate new migration for authentication tables
php bin/console make:migration

# Run the new migration
php bin/console doctrine:migrations:migrate
```

### Verify Database Schema

The following tables should be created:
- `users` - Main user accounts
- `email_verification_tokens` - Email verification tokens
- `password_reset_tokens` - Password reset tokens
- `user_sessions` - Active user sessions (optional)
- `authentication_logs` - Security audit logs

## 3. Generate Authentication Components

### Create Entities

```bash
# Generate User entity
php bin/console make:entity User

# Generate token entities
php bin/console make:entity EmailVerificationToken
php bin/console make:entity PasswordResetToken
php bin/console make:entity AuthenticationLog
```

### Create Repositories

```bash
# Repositories are auto-generated with entities
# Customize with additional query methods as needed
```

### Create Form Types

```bash
# Generate registration form
php bin/console make:form RegistrationFormType

# Generate other forms
php bin/console make:form LoginFormType
php bin/console make:form PasswordResetRequestFormType
php bin/console make:form ChangePasswordFormType
php bin/console make:form UserProfileFormType
```

### Create Controllers

```bash
# Generate security controller
php bin/console make:controller SecurityController

# Generate registration controller
php bin/console make:controller RegistrationController

# Generate profile controller  
php bin/console make:controller ProfileController

# Generate password reset controller
php bin/console make:controller ResetPasswordController
```

### Create Services

```bash
# Create service classes manually in src/Service/
# - UserRegistrationService.php
# - EmailVerificationService.php
# - PasswordResetService.php
# - AuthenticationLogService.php
```

## 4. Security Configuration

### Configure Security Bundle

Edit `config/packages/security.yaml`:

```yaml
security:
    password_hashers:
        App\Entity\User:
            algorithm: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            lazy: true
            provider: app_user_provider
            form_login:
                login_path: app_login
                check_path: app_login
                enable_csrf: true
            logout:
                path: app_logout
                target: app_homepage
            remember_me:
                secret: '%kernel.secret%'
                lifetime: 604800 # 1 week

    access_control:
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/reset-password, roles: PUBLIC_ACCESS }
        - { path: ^/verify-email, roles: PUBLIC_ACCESS }
        - { path: ^/profile, roles: ROLE_USER }
```

### Configure Routing

Edit `config/routes.yaml` or create `config/routes/security.yaml`:

```yaml
# Authentication routes
app_register:
    path: /register
    controller: App\Controller\RegistrationController::register

app_login:
    path: /login
    controller: App\Controller\SecurityController::login

app_logout:
    path: /logout
    methods: POST

app_verify_email:
    path: /verify-email/{token}
    controller: App\Controller\RegistrationController::verifyEmail

app_forgot_password:
    path: /reset-password
    controller: App\Controller\ResetPasswordController::request

app_reset_password:
    path: /reset-password/{token}
    controller: App\Controller\ResetPasswordController::reset

app_profile:
    path: /profile
    controller: App\Controller\ProfileController::index
```

## 5. Frontend Setup

### Build Assets

```bash
# Build assets for development
npm run dev

# Or watch for changes during development
npm run watch

# Build for production
npm run build
```

### Create Templates

Create template files in `templates/`:
- `security/login.html.twig`
- `security/register.html.twig`
- `security/reset_password/request.html.twig`
- `security/reset_password/reset.html.twig`
- `profile/index.html.twig`
- `registration/confirmation_email.html.twig`

### Add Stimulus Controllers

Create JavaScript controllers in `assets/controllers/`:
- `registration_controller.js` - Form enhancements
- `login_controller.js` - Login form features
- `password_controller.js` - Password visibility toggle

## 6. Email Configuration

### Setup Mailer

Configure email settings in `.env.local`:

```bash
# For development (use MailHog or similar)
MAILER_DSN=smtp://localhost:1025

# For production
MAILER_DSN=smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=your-email&password=your-password
```

### Create Email Templates

Create email templates in `templates/emails/`:
- `registration_confirmation.html.twig`
- `password_reset.html.twig`

## 7. Development Workflow

### Start Development Servers

```bash
# Terminal 1: Start Symfony server
symfony server:start

# Terminal 2: Watch assets
npm run watch

# Terminal 3: Start mail catcher (optional)
mailhog
```

### Access the Application

- Main application: http://127.0.0.1:8000
- Registration: http://127.0.0.1:8000/register
- Login: http://127.0.0.1:8000/login
- Mail interface: http://127.0.0.1:8025 (if using MailHog)

## 8. Testing Setup

### Create Test Database

```bash
# Create test database
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:migrations:migrate
```

### Run Tests

```bash
# Run all tests
php bin/phpunit

# Run authentication-specific tests
php bin/phpunit tests/Controller/SecurityControllerTest.php
php bin/phpunit tests/Service/UserRegistrationServiceTest.php
```

### Create Test Fixtures

```bash
# Install fixtures bundle (if not already installed)
composer require --dev doctrine/doctrine-fixtures-bundle

# Create fixtures
php bin/console make:fixtures UserFixtures
```

## 9. Key Implementation Files

### Essential Files to Implement

1. **Entities** (`src/Entity/`):
   - `User.php` - Main user entity
   - `EmailVerificationToken.php`
   - `PasswordResetToken.php`
   - `AuthenticationLog.php`

2. **Controllers** (`src/Controller/`):
   - `SecurityController.php` - Login/logout
   - `RegistrationController.php` - User registration
   - `ResetPasswordController.php` - Password reset
   - `ProfileController.php` - Profile management

3. **Services** (`src/Service/`):
   - `UserRegistrationService.php`
   - `EmailVerificationService.php`
   - `PasswordResetService.php`
   - `AuthenticationLogService.php`

4. **Forms** (`src/Form/`):
   - `RegistrationFormType.php`
   - `LoginFormType.php`
   - `PasswordResetRequestFormType.php`
   - `ChangePasswordFormType.php`

5. **Templates** (`templates/`):
   - Security templates for forms
   - Email templates for notifications
   - Profile management templates

## 10. Debugging and Troubleshooting

### Common Issues

**Database Connection Issues**:
```bash
# Check database configuration
php bin/console debug:config doctrine

# Test database connection
php bin/console doctrine:query:sql "SELECT 1"
```

**Email Sending Issues**:
```bash
# Test email configuration
php bin/console debug:config framework mailer

# Send test email
php bin/console mailer:test user@example.com
```

**Asset Building Issues**:
```bash
# Clear cache and rebuild
rm -rf var/cache/*
npm run dev

# Check Webpack configuration
npx encore dev --json
```

### Debug Tools

Enable debug mode in `.env.local`:
```bash
APP_ENV=dev
APP_DEBUG=1
```

Use Symfony profiler:
- Access profiler at `/_profiler`
- Check security panel for authentication details
- Review doctrine panel for database queries

### Logging

Check logs for authentication events:
```bash
# View recent logs
tail -f var/log/dev.log

# Filter for authentication logs
grep "authentication" var/log/dev.log
```

## 11. Production Deployment

### Pre-deployment Checklist

1. **Environment Configuration**:
   ```bash
   APP_ENV=prod
   APP_DEBUG=0
   DATABASE_URL=production_database_url
   MAILER_DSN=production_mailer_config
   ```

2. **Security Configuration**:
   - Enable HTTPS
   - Configure proper session settings
   - Set secure cookie flags
   - Configure CORS headers

3. **Database Migration**:
   ```bash
   php bin/console doctrine:migrations:migrate --env=prod
   ```

4. **Asset Building**:
   ```bash
   npm run build
   php bin/console cache:clear --env=prod
   ```

### Performance Optimization

- Enable OPcache in production
- Configure database connection pooling
- Set up Redis for session storage (optional)
- Enable HTTP/2 and compression
- Configure CDN for static assets

## 12. Next Steps

After completing the authentication system:

1. **Add Admin Interface**:
   - User management panel
   - Authentication logs viewer
   - System configuration

2. **Enhanced Security**:
   - Two-factor authentication
   - Account lockout policies
   - Advanced password policies

3. **User Experience**:
   - Social login integration
   - Progressive web app features
   - Mobile-responsive design

4. **Monitoring**:
   - Authentication metrics
   - Security alerts
   - Performance monitoring

## Support and Resources

### Documentation
- [Symfony Security Documentation](https://symfony.com/doc/current/security.html)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
- [Twig Documentation](https://twig.symfony.com/doc/)

### Development Tools
- Symfony CLI: https://symfony.com/download
- MailHog (mail testing): https://github.com/mailhog/MailHog
- Symfony Profiler: Built into debug mode

This completes the quick start guide for implementing user authentication in the Hermio project. Follow these steps systematically to build a robust, secure authentication system.