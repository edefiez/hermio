# Research: Account Management / Subscription Model

**Feature**: 003-account-subscription  
**Date**: 2025-12-08  
**Phase**: 0 - Research & Technology Selection

## Overview

This research document consolidates technology decisions for implementing the subscription-based account management system. Since this feature builds on the existing Symfony 8 infrastructure and follows constitutional requirements, most technology choices are pre-determined by the project's architecture standards.

## Technology Decisions

### Decision 1: Account Entity Design Pattern

**Decision**: Create separate `Account` entity with OneToOne relationship to `User` entity

**Rationale**:
- Separation of concerns: User entity handles authentication/authorization, Account entity handles subscription/plan management
- Follows single responsibility principle
- Allows for future expansion (billing, payment history, etc.) without bloating User entity
- Easier to query and manage subscription-related data independently
- Supports audit trail for plan changes (timestamps, modified by admin)

**Alternatives Considered**:
- Embedding plan fields directly in User entity: Rejected because it violates separation of concerns and makes User entity responsible for too many concerns
- Using a subscription service without entity: Rejected because we need persistence and relationships for quota tracking

**Constitutional Reference**: Follows Doctrine ORM best practices (Constitution Section III)

---

### Decision 2: Plan Type Representation

**Decision**: Use PHP enum (BackedEnum) for plan types (Free, Pro, Enterprise)

**Rationale**:
- Type-safe representation of plan types
- Prevents invalid plan type values
- Easy to extend with methods for quota limits
- Native PHP 8.1+ feature (project uses PHP 8.4+)
- Better IDE support and static analysis
- Can include quota limit logic directly in enum

**Alternatives Considered**:
- String constants: Rejected because less type-safe and harder to maintain
- Database enum type: Rejected because less flexible and harder to query in PHP
- Separate Plan entity: Rejected because over-engineered for fixed three-tier system

**Implementation Pattern**:
```php
enum PlanType: string
{
    case FREE = 'free';
    case PRO = 'pro';
    case ENTERPRISE = 'enterprise';
    
    public function getQuotaLimit(): ?int
    {
        return match($this) {
            self::FREE => 1,
            self::PRO => 10,
            self::ENTERPRISE => null, // unlimited
        };
    }
}
```

---

### Decision 3: Quota Enforcement Strategy

**Decision**: Service-layer validation before content creation with clear error messages

**Rationale**:
- Quota checks must happen before any content creation operation
- Service layer ensures consistent enforcement across all entry points
- Clear error messages improve user experience (FR-005, SC-002)
- Prevents partial creation scenarios
- Can be reused by multiple controllers/services

**Alternatives Considered**:
- Database-level constraints: Rejected because quota is calculated dynamically (count of existing cards), not a simple column constraint
- Event listener approach: Considered but rejected because validation should happen synchronously before creation, not asynchronously
- Middleware/request interceptor: Rejected because quota logic is business logic, not infrastructure concern

**Implementation Pattern**:
- `QuotaService::canCreateContent(User $user, int $quantity = 1): bool`
- `QuotaService::validateQuota(User $user, int $quantity = 1): void` (throws QuotaExceededException)
- Exception includes user-friendly message with upgrade suggestions

**Constitutional Reference**: Business logic in services (Constitution Section I)

---

### Decision 4: Default Plan Assignment

**Decision**: Automatically assign Free plan to all new users during registration

**Rationale**:
- Matches assumption in spec: "All users are automatically assigned a Free plan when they first register"
- Simplifies user registration flow
- Ensures every user has an account record
- Can be overridden by administrators if needed

**Implementation**: Modify `UserRegistrationService` to create Account entity with Free plan after user creation

**Alternatives Considered**:
- Requiring explicit plan selection during registration: Rejected because spec assumes automatic Free assignment
- Null account until first upgrade: Rejected because complicates quota checks and requires null handling

---

### Decision 5: Plan Change Handling

**Decision**: Immediate effect with real-time quota update, no session invalidation required

**Rationale**:
- Matches success criterion SC-004: "Plan changes take effect immediately (within 1 second) without requiring users to log out"
- Quota limits are checked on-demand, not cached in session
- User experience: no disruption to active sessions
- Simpler implementation: no cache invalidation needed

**Alternatives Considered**:
- Requiring logout/login: Rejected because violates SC-004 and creates poor UX
- Caching quota in session: Rejected because requires complex cache invalidation and doesn't match real-time requirement

**Implementation**: Quota checks always query current Account entity, ensuring latest plan is used

---

### Decision 6: Admin Interface Authorization

**Decision**: Use Symfony Security `#[IsGranted('ROLE_ADMIN')]` attribute on admin controllers

**Rationale**:
- Matches constitutional requirement (Constitution Section IV)
- Type-safe and declarative
- Integrated with Symfony Security system
- Clear separation between user-facing and admin controllers

**Alternatives Considered**:
- Custom voter: Rejected because ROLE_ADMIN check is simple and doesn't need custom logic
- Manual role checking in controller: Rejected because less secure and violates Symfony best practices

**Constitutional Reference**: Explicitly mandated by Constitution Section IV

---

### Decision 7: Quota Usage Calculation

**Decision**: Calculate quota usage by counting related Card entities (when Card entity exists)

**Rationale**:
- Quota is based on actual content created, not a stored counter
- More accurate: reflects actual usage
- Handles edge cases (deleted cards, etc.)
- Can be optimized with database count queries

**Implementation Note**: 
- For MVP, assume Card entity will exist (referenced in spec assumptions)
- Use `COUNT()` query for performance
- Cache count if performance becomes issue (future optimization)

**Alternatives Considered**:
- Stored counter field: Rejected because requires maintaining consistency and adds complexity
- Event-based counter: Considered but rejected for MVP - can be added later if needed

---

### Decision 8: Downgrade Protection

**Decision**: Prevent plan downgrades if user has more content than new plan allows, with admin override option

**Rationale**:
- Protects user data integrity
- Matches edge case requirement: "System should prevent plan downgrades if the user has more items than the new plan allows"
- Admin override allows support scenarios where downgrade is intentional

**Implementation**:
- `AccountService::canDowngrade(User $user, PlanType $newPlan): bool`
- Admin interface shows warning if downgrade would exceed quota
- Admin can confirm override if intentional

**Alternatives Considered**:
- Automatic content deletion: Rejected because destructive and violates data integrity
- Allow downgrade with warning only: Rejected because violates spec requirement to prevent invalid states

---

## Integration Points

### With Existing User Entity

- Add `OneToOne` relationship from User to Account
- Modify `UserRegistrationService` to create Account on registration
- No changes to User authentication/authorization logic

### With Future Card Entity

- QuotaService will query Card repository to count user's cards
- Card creation controllers will call QuotaService before creating cards
- Quota validation is decoupled - works even if Card entity doesn't exist yet (returns 0 usage)

### With Symfony Security

- Use existing ROLE_ADMIN role for admin authorization
- No changes to security configuration needed
- Admin controllers use `#[IsGranted('ROLE_ADMIN')]` attribute

## Unresolved Dependencies

- **Card Entity**: Quota usage calculation assumes Card entity exists. If Card entity doesn't exist yet, quota usage will return 0 until Card entity is implemented. This is acceptable for MVP.

## Best Practices Applied

1. **Doctrine Relationships**: Using proper OneToOne bidirectional relationship with cascade options
2. **Service Layer**: All business logic in services, not controllers
3. **Type Safety**: Using PHP enums for plan types
4. **Error Handling**: Custom exceptions for quota violations with user-friendly messages
5. **Internationalization**: All user-facing messages use translation keys
6. **Validation**: Symfony Validator constraints on Account entity
7. **Migrations**: Schema changes managed via Doctrine migrations

## References

- Symfony 8 Documentation: Entity Relationships
- Doctrine ORM 3.x: OneToOne Relationships
- PHP 8.4: Enumerations (BackedEnum)
- Symfony Security: Authorization with Attributes
- Project Constitution: Sections I, II, III, IV

