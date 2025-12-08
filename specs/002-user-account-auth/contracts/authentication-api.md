# Authentication API Contracts

**Date**: December 8, 2025  
**Feature**: User Account & Authentication  
**API Style**: HTTP Forms + JSON responses (Symfony web application)

## Overview

These contracts define the HTTP endpoints, request/response formats, and validation rules for the authentication system. The API follows Symfony conventions with form-based requests for web UI and structured JSON responses.

## Contracts Summary

| Endpoint | Method | Purpose |
|----------|---------|---------|
| `/register` | GET/POST | User registration |
| `/login` | GET/POST | User authentication |
| `/logout` | POST | Session termination |
| `/verify-email/{token}` | GET | Email verification |
| `/reset-password` | GET/POST | Password reset request |
| `/reset-password/{token}` | GET/POST | Password reset form |
| `/profile` | GET/POST | Profile management |
| `/profile/change-password` | POST | Password change |

## Detailed Contracts

### 1. User Registration

#### Registration Form Display
```http
GET /register
Accept: text/html
```

**Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
<!-- Registration form with CSRF protection -->
</html>
```

#### Registration Submission
```http
POST /register
Content-Type: application/x-www-form-urlencoded
X-Requested-With: XMLHttpRequest (optional for AJAX)

registration_form[email]=user@example.com&
registration_form[plainPassword][first]=password123&
registration_form[plainPassword][second]=password123&
registration_form[agreeTerms]=1&
registration_form[_token]=csrf_token_value
```

**Success Response**:
```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "status": "success",
  "message": "Registration successful. Please check your email to verify your account.",
  "redirect": "/login"
}
```

**Validation Error Response**:
```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "status": "error",
  "message": "Please correct the errors below.",
  "errors": {
    "email": ["This email is already registered."],
    "plainPassword": ["Password must contain at least one uppercase letter."]
  }
}
```

### 2. User Login

#### Login Form Display
```http
GET /login
Accept: text/html
```

**Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
<!-- Login form with remember me option -->
</html>
```

#### Login Submission
```http
POST /login
Content-Type: application/x-www-form-urlencoded

_username=user@example.com&
_password=password123&
_remember_me=on&
_csrf_token=csrf_token_value
```

**Success Response**:
```http
HTTP/1.1 302 Found
Location: /dashboard
Set-Cookie: PHPSESSID=session_id; HttpOnly; Secure; SameSite=Strict
```

**Authentication Failure**:
```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "status": "error",
  "message": "Invalid credentials.",
  "remaining_attempts": 3
}
```

**Account Locked Response**:
```http
HTTP/1.1 423 Locked
Content-Type: application/json

{
  "status": "error",
  "message": "Account temporarily locked due to multiple failed login attempts. Try again in 15 minutes.",
  "lockout_expires_at": "2025-12-08T15:30:00Z"
}
```

### 3. Email Verification

#### Email Verification Link
```http
GET /verify-email/{token}
Accept: text/html
```

**Success Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
<!-- Success page with login link -->
</html>
```

**Invalid/Expired Token**:
```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "status": "error",
  "message": "Invalid or expired verification link.",
  "action": "request_new",
  "resend_url": "/resend-verification"
}
```

### 4. Password Reset

#### Reset Request Form
```http
GET /reset-password
Accept: text/html
```

#### Reset Request Submission
```http
POST /reset-password
Content-Type: application/x-www-form-urlencoded

reset_password_request_form[email]=user@example.com&
reset_password_request_form[_token]=csrf_token_value
```

**Success Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "status": "success",
  "message": "If an account with that email exists, you will receive a password reset link."
}
```

#### Reset Form with Token
```http
GET /reset-password/{token}
Accept: text/html
```

#### Password Reset Submission
```http
POST /reset-password/{token}
Content-Type: application/x-www-form-urlencoded

change_password_form[plainPassword][first]=newpassword123&
change_password_form[plainPassword][second]=newpassword123&
change_password_form[_token]=csrf_token_value
```

**Success Response**:
```http
HTTP/1.1 302 Found
Location: /login
Flash-Message: Password successfully changed. You can now log in.
```

### 5. User Logout

```http
POST /logout
Content-Type: application/x-www-form-urlencoded

_csrf_token=csrf_token_value
```

**Response**:
```http
HTTP/1.1 302 Found
Location: /
Clear-Cookie: PHPSESSID
```

### 6. Profile Management

#### Profile Display
```http
GET /profile
Accept: text/html
Authorization: Required (authenticated user)
```

**Response**:
```http
HTTP/1.1 200 OK
Content-Type: text/html

<!DOCTYPE html>
<html>
<!-- Profile form with current user data -->
</html>
```

#### Profile Update
```http
POST /profile
Content-Type: application/x-www-form-urlencoded
Authorization: Required

profile_form[email]=newemail@example.com&
profile_form[_token]=csrf_token_value
```

**Success Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "status": "success",
  "message": "Profile updated. Please verify your new email address.",
  "email_verification_required": true
}
```

### 7. Password Change

```http
POST /profile/change-password
Content-Type: application/x-www-form-urlencoded
Authorization: Required

change_password_form[currentPassword]=oldpassword&
change_password_form[plainPassword][first]=newpassword123&
change_password_form[plainPassword][second]=newpassword123&
change_password_form[_token]=csrf_token_value
```

**Success Response**:
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "status": "success",
  "message": "Password successfully changed."
}
```

**Current Password Error**:
```http
HTTP/1.1 400 Bad Request
Content-Type: application/json

{
  "status": "error",
  "message": "Current password is incorrect.",
  "errors": {
    "currentPassword": ["The current password is invalid."]
  }
}
```

## Form Field Specifications

### Registration Form Fields

```yaml
registration_form:
  email:
    type: email
    required: true
    constraints:
      - NotBlank
      - Email
      - Length: { max: 180 }
      - UniqueEntity: { fields: [email] }
  
  plainPassword:
    type: repeated
    first_options: { label: 'Password' }
    second_options: { label: 'Confirm Password' }
    invalid_message: 'The password fields must match.'
    constraints:
      - NotBlank
      - Length: { min: 8 }
      - PasswordStrength: { minScore: 3 }
  
  agreeTerms:
    type: checkbox
    required: true
    constraints:
      - IsTrue: { message: 'You must agree to the terms.' }
```

### Login Form Fields

```yaml
login_form:
  _username:
    type: email
    required: true
    label: 'Email'
  
  _password:
    type: password
    required: true
    label: 'Password'
  
  _remember_me:
    type: checkbox
    required: false
    label: 'Remember me'
```

## Security Headers

All authentication endpoints include security headers:

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```

## Rate Limiting

### Login Endpoints

- Maximum 5 failed attempts per IP per 15 minutes
- Account lockout after 5 failed attempts (15 minutes)
- Progressive delays: 1s, 2s, 4s, 8s, 16s

### Registration Endpoints

- Maximum 3 registrations per IP per hour
- Email verification: 1 request per 5 minutes per email

### Password Reset

- Maximum 3 reset requests per email per hour
- Maximum 10 reset requests per IP per hour

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Bad Request | Invalid form data or malformed request |
| 401 | Unauthorized | Invalid credentials |
| 403 | Forbidden | Access denied (unverified email, etc.) |
| 422 | Unprocessable Entity | Validation errors |
| 423 | Locked | Account temporarily locked |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error |

## CSRF Protection

All forms include CSRF tokens:
- Generated via Symfony's CSRF component
- Validated on form submission
- Tokens expire after 1 hour
- Different tokens for different forms

## Session Management

### Session Configuration

```yaml
framework:
  session:
    handler_id: ~
    cookie_secure: auto
    cookie_samesite: strict
    cookie_lifetime: 7200  # 2 hours
    gc_maxlifetime: 7200
```

### Authentication Events

The system fires events for monitoring:
- `security.authentication.success`
- `security.authentication.failure` 
- `user.registered`
- `user.email_verified`
- `user.password_reset`

## Testing Contracts

### Test Scenarios

Each endpoint should be tested with:
- Valid input (happy path)
- Invalid input (validation errors)
- Security edge cases (CSRF, rate limiting)
- Authentication state requirements
- Concurrent request handling