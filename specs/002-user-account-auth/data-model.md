# Data Model: User Account & Authentication

**Date**: December 8, 2025  
**Feature**: User Account & Authentication  
**Database**: Doctrine ORM with Symfony 8

## Entity Overview

The authentication system requires five core entities to manage user accounts, security tokens, sessions, and audit logging. All entities follow Symfony/Doctrine best practices with proper relationships and validation.

## Entities

### 1. User Entity

**Purpose**: Represents a registered user account in the system

```php
namespace App\Entity;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="users")
 * @UniqueEntity(fields={"email"}, message="An account with this email already exists")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Properties
    private int $id;                    // Primary key, auto-increment
    private string $email;              // Unique email address (max 180 chars)
    private string $password;           // Hashed password
    private bool $isEmailVerified;      // Email verification status
    private array $roles;               // User roles (ROLE_USER default)
    private DateTime $createdAt;        // Account creation timestamp
    private ?DateTime $lastLoginAt;     // Last successful login
    private string $status;             // active, suspended, pending
    
    // Relationships
    private Collection $emailVerificationTokens;  // One-to-many
    private Collection $passwordResetTokens;      // One-to-many
    private Collection $authenticationLogs;       // One-to-many
}
```

**Validation Rules**:
- Email: Valid email format, unique across system, max 180 characters
- Password: Minimum 8 characters, mixed case, numbers, special characters
- Status: Enum values (active, suspended, pending)
- Roles: Array of valid Symfony roles

**State Transitions**:
- pending → active (email verified)
- active ↔ suspended (admin action)
- Account deletion sets status to "deleted" (soft delete)

### 2. EmailVerificationToken Entity

**Purpose**: Manages email verification tokens for new registrations and email changes

```php
namespace App\Entity;

/**
 * @ORM\Entity(repositoryClass=EmailVerificationTokenRepository::class)
 * @ORM\Table(name="email_verification_tokens")
 */
class EmailVerificationToken
{
    // Properties
    private int $id;                    // Primary key
    private string $token;              // Unique verification token (64 chars)
    private DateTime $createdAt;        // Token creation time
    private DateTime $expiresAt;        // Expiration time (24 hours)
    private bool $isUsed;              // Single-use flag
    private ?DateTime $usedAt;         // When token was consumed
    private string $email;             // Email being verified
    
    // Relationships
    private User $user;                // Many-to-one to User
}
```

**Validation Rules**:
- Token: Unique, 64-character hex string
- ExpiresAt: Must be 24 hours from creation
- Email: Valid email format
- IsUsed: Defaults to false

**Business Rules**:
- Tokens expire after 24 hours
- Single-use only (isUsed flag)
- New token invalidates previous unused tokens for same email

### 3. PasswordResetToken Entity

**Purpose**: Manages secure password reset tokens

```php
namespace App\Entity;

/**
 * @ORM\Entity(repositoryClass=PasswordResetTokenRepository::class)
 * @ORM\Table(name="password_reset_tokens")
 */
class PasswordResetToken
{
    // Properties
    private int $id;                    // Primary key
    private string $token;              // Unique reset token (64 chars)
    private DateTime $createdAt;        // Token creation time
    private DateTime $expiresAt;        // Expiration time (24 hours)
    private bool $isUsed;              // Single-use flag
    private ?DateTime $usedAt;         // When token was consumed
    
    // Relationships
    private User $user;                // Many-to-one to User
}
```

**Validation Rules**:
- Token: Unique, 64-character hex string
- ExpiresAt: Must be 24 hours from creation
- IsUsed: Defaults to false

**Business Rules**:
- Tokens expire after 24 hours
- Single-use only (isUsed flag)
- Multiple active tokens allowed (user can request multiple resets)
- Used tokens remain for audit trail

### 4. UserSession Entity

**Purpose**: Tracks active authenticated sessions (optional - can use Symfony's default session handling)

```php
namespace App\Entity;

/**
 * @ORM\Entity(repositoryClass=UserSessionRepository::class)
 * @ORM\Table(name="user_sessions")
 */
class UserSession
{
    // Properties
    private string $sessionId;          // Primary key (session identifier)
    private DateTime $createdAt;        // Session start time
    private DateTime $lastActivityAt;   // Last activity timestamp
    private DateTime $expiresAt;        // Session expiration (2 hours idle)
    private string $ipAddress;          // Client IP address
    private string $userAgent;          // Client user agent
    private bool $isActive;            // Session status
    
    // Relationships
    private User $user;                // Many-to-one to User
}
```

**Validation Rules**:
- SessionId: Unique string identifier
- IP Address: Valid IP format
- UserAgent: Max 500 characters

**Business Rules**:
- Sessions expire after 2 hours of inactivity
- Multiple concurrent sessions allowed
- Cleanup expired sessions via scheduled task

### 5. AuthenticationLog Entity

**Purpose**: Audit trail for authentication events and security monitoring

```php
namespace App\Entity;

/**
 * @ORM\Entity(repositoryClass=AuthenticationLogRepository::class)
 * @ORM\Table(name="authentication_logs")
 */
class AuthenticationLog
{
    // Properties
    private int $id;                    // Primary key
    private string $eventType;          // Event type enum
    private DateTime $timestamp;        // Event timestamp
    private string $ipAddress;          // Client IP address
    private string $userAgent;          // Client user agent
    private ?string $details;          // Additional event details (JSON)
    private bool $successful;          // Success/failure flag
    
    // Relationships
    private ?User $user;               // Many-to-one to User (nullable)
}
```

**Event Types** (enum values):
- login_success
- login_failure
- logout
- registration
- email_verified
- password_reset_requested
- password_reset_completed
- password_changed
- account_locked

**Validation Rules**:
- EventType: Must be valid enum value
- IP Address: Valid IP format
- UserAgent: Max 500 characters
- Details: Valid JSON if present

## Entity Relationships

### Relationship Map

```
User (1) ←→ (∞) EmailVerificationToken
User (1) ←→ (∞) PasswordResetToken
User (1) ←→ (∞) UserSession
User (1) ←→ (∞) AuthenticationLog
```

### Foreign Key Constraints

- All token entities have required foreign keys to User
- AuthenticationLog.user_id is nullable (for events without user context)
- Cascade options: ON DELETE SET NULL for logs, CASCADE for tokens/sessions

## Database Indexes

### Primary Indexes

- Users: `email` (unique), `status`, `created_at`
- Tokens: `token` (unique), `expires_at`, `user_id`
- Sessions: `user_id`, `expires_at`, `last_activity_at`
- Logs: `user_id`, `timestamp`, `event_type`

### Composite Indexes

- `(user_id, is_used)` on token tables for active token lookup
- `(event_type, timestamp)` on logs for security reporting
- `(user_id, timestamp DESC)` on logs for user activity history

## Data Validation

### Entity-Level Validation

Each entity includes Symfony validation constraints:

```php
use Symfony\Component\Validator\Constraints as Assert;

class User 
{
    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private string $email;
    
    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', 
                   message: 'Password must contain uppercase, lowercase, number and special character')]
    private string $plainPassword;  // Temporary field for validation
}
```

### Database-Level Constraints

- NOT NULL constraints on required fields
- UNIQUE constraints on email and token values
- CHECK constraints for enum values (status, event_type)
- Foreign key constraints with appropriate cascade options

## Migration Strategy

### Initial Migration

Create all tables with proper indexes and constraints:

```sql
-- Users table with authentication fields
-- Token tables with expiration and usage tracking  
-- Session table for multi-device support
-- Log table for security audit trail
```

### Seed Data

- Default admin user (optional)
- Role hierarchy setup
- Initial system configuration

## Data Retention

### Cleanup Policies

- **Expired Tokens**: Delete after 7 days post-expiration
- **Old Sessions**: Delete after 30 days of inactivity  
- **Authentication Logs**: Retain for 90 days (configurable)
- **User Accounts**: Soft delete with 30-day recovery window

### Scheduled Tasks

Implement Symfony Console commands for:
- Daily cleanup of expired tokens
- Weekly session cleanup
- Monthly log archival
- User account purge (if soft-deleted > 30 days)

## Performance Considerations

### Query Optimization

- Use Repository pattern for complex queries
- Implement query caching for frequently accessed data
- Optimize token lookup queries with proper indexing
- Use pagination for admin user lists and log views

### Scaling Considerations  

- Consider read replicas for authentication logs
- Implement caching for user role/permission checks
- Use database connection pooling for high concurrency
- Consider partitioning logs by date for large datasets