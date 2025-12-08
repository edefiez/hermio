# Form Contracts: Account Management / Subscription Model

**Feature**: 003-account-subscription  
**Date**: 2025-12-08  
**Type**: Symfony Forms

## Overview

This document defines the form contracts for the account management and subscription system. Forms are built using Symfony Form component and rendered via Twig templates.

## Forms

### 1. Plan Change Form (Admin)

**Form Type**: `PlanChangeFormType`  
**Entity**: `Account`  
**Purpose**: Allow administrators to change a user's subscription plan

**Fields**:

| Field Name | Type | Required | Validation | Default Value |
|------------|------|----------|------------|---------------|
| `planType` | ChoiceType | Yes | Choice constraint (free/pro/enterprise) | Current plan type |
| `confirmDowngrade` | CheckboxType | No | None | false |

**Form Configuration**:
```php
$builder
    ->add('planType', ChoiceType::class, [
        'choices' => [
            'Free' => PlanType::FREE->value,
            'Pro' => PlanType::PRO->value,
            'Enterprise' => PlanType::ENTERPRISE->value,
        ],
        'label' => 'account.plan_type',
        'required' => true,
    ])
    ->add('confirmDowngrade', CheckboxType::class, [
        'label' => 'account.confirm_downgrade',
        'required' => false,
        'mapped' => false, // Not mapped to entity
    ]);
```

**Validation Rules**:
- `planType`: Must be one of: 'free', 'pro', 'enterprise'
- `confirmDowngrade`: Required if downgrading and user has more content than new plan allows

**Custom Validation**:
- If downgrading (new plan < current plan) and user's card count > new plan's quota limit:
  - `confirmDowngrade` must be checked
  - Otherwise, form validation fails with error message

**Error Messages** (translation keys):
- `planType`: `account.plan_type.invalid`
- `confirmDowngrade`: `account.downgrade.confirmation_required`

**Template**: `admin/account/_plan_change_form.html.twig`

**Usage**:
```php
$form = $this->createForm(PlanChangeFormType::class, $account);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $planType = PlanType::from($form->get('planType')->getData());
    $confirmDowngrade = $form->get('confirmDowngrade')->getData();
    
    $accountService->changePlan($account, $planType, $confirmDowngrade);
    // Redirect with success message
}
```

---

## Form Rendering

### Twig Form Theme

Forms use Symfony's default form theme with Bootstrap styling (if Bootstrap is used) or custom CSS.

**Form Rendering Pattern**:
```twig
{{ form_start(form, {'attr': {'class': 'plan-change-form'}}) }}
    {{ form_row(form.planType) }}
    
    {% if showDowngradeWarning %}
        <div class="alert alert-warning">
            {{ 'account.downgrade.warning'|trans({
                'current': currentPlan,
                'new': newPlan,
                'count': cardCount,
                'limit': newLimit
            }) }}
        </div>
        {{ form_row(form.confirmDowngrade) }}
    {% endif %}
    
    <button type="submit" class="btn btn-primary">
        {{ 'account.save'|trans }}
    </button>
{{ form_end(form) }}
```

### Form Styling

- Forms follow application's design system
- Error messages displayed below fields
- Required fields marked with asterisk (*)
- Submit buttons use primary action styling
- Forms are responsive (mobile-friendly)

---

## Form Validation Flow

### Plan Change Form Validation

1. **Client-Side Validation** (HTML5):
   - `planType` field is required
   - Browser validates choice selection

2. **Server-Side Validation** (Symfony):
   - Form data bound to Account entity
   - Symfony Validator checks constraints
   - Custom validation for downgrade confirmation

3. **Business Logic Validation** (Service Layer):
   - `AccountService::canChangePlan()` checks if change is allowed
   - Quota validation if downgrading
   - Admin authorization check

4. **Error Handling**:
   - Validation errors displayed in form
   - Flash messages for success/error
   - Redirect on success, stay on page on error

---

## Translation Keys

All form labels, placeholders, and error messages use translation keys:

**Form Labels**:
- `account.plan_type`: "Subscription Plan"
- `account.confirm_downgrade`: "I confirm this downgrade"

**Error Messages**:
- `account.plan_type.invalid`: "Please select a valid plan type"
- `account.downgrade.confirmation_required`: "You must confirm this downgrade as it will exceed the new plan's quota limit"
- `account.downgrade.warning`: "Warning: This user has {count} cards, but the {new} plan only allows {limit} cards. Please confirm to proceed."

**Success Messages**:
- `account.plan.changed`: "Plan successfully changed to {planType}"

---

## Form Security

### CSRF Protection

All forms include CSRF token:
```php
$form = $this->createForm(PlanChangeFormType::class, $account);
// CSRF token automatically included
```

### Authorization Checks

- Form submission validated in controller
- `#[IsGranted('ROLE_ADMIN')]` on admin form controllers
- User can only submit forms for their own account (except admins)

### Input Sanitization

- All form data validated and sanitized by Symfony
- Plan type values validated against enum
- No raw SQL or direct database access from forms

---

## Notes

- Forms follow Symfony best practices
- All user-facing text is internationalized
- Forms are accessible (ARIA labels, proper HTML structure)
- Error messages are user-friendly and actionable
- Form validation happens at multiple layers for security

