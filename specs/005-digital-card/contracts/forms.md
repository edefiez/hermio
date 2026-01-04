# Form Contracts: Digital Card Management

**Feature**: 005-digital-card  
**Date**: December 8, 2025  
**Type**: Symfony Forms

## Overview

This document defines the form contracts for the digital card management system. Forms are built using Symfony Form component and rendered via Twig templates. The card form supports both creation and editing modes.

## Forms

### 1. Card Form (Create/Edit)

**Form Type**: `CardFormType`  
**Entity**: `Card`  
**Purpose**: Allow users to create and edit digital cards

**Fields**:

| Field Name | Type | Required | Validation | Default Value |
|------------|------|----------|------------|---------------|
| `name` | TextType | Yes | NotBlank, Length(max: 255) | Empty |
| `email` | EmailType | No | Email format | Empty |
| `phone` | TextType | No | Length(max: 50) | Empty |
| `company` | TextType | No | Length(max: 255) | Empty |
| `title` | TextType | No | Length(max: 255) | Empty |
| `bio` | TextareaType | No | Length(max: 1000) | Empty |
| `website` | UrlType | No | URL format | Empty |
| `linkedin` | UrlType | No | URL format | Empty |
| `twitter` | UrlType | No | URL format | Empty |

**Form Configuration**:
```php
$builder
    ->add('name', TextType::class, [
        'label' => 'card.name',
        'required' => true,
        'attr' => ['placeholder' => 'card.name.placeholder'],
    ])
    ->add('email', EmailType::class, [
        'label' => 'card.email',
        'required' => false,
        'attr' => ['placeholder' => 'card.email.placeholder'],
    ])
    ->add('phone', TextType::class, [
        'label' => 'card.phone',
        'required' => false,
        'attr' => ['placeholder' => 'card.phone.placeholder'],
    ])
    ->add('company', TextType::class, [
        'label' => 'card.company',
        'required' => false,
        'attr' => ['placeholder' => 'card.company.placeholder'],
    ])
    ->add('title', TextType::class, [
        'label' => 'card.title',
        'required' => false,
        'attr' => ['placeholder' => 'card.title.placeholder'],
    ])
    ->add('bio', TextareaType::class, [
        'label' => 'card.bio',
        'required' => false,
        'attr' => [
            'placeholder' => 'card.bio.placeholder',
            'rows' => 5,
        ],
    ])
    ->add('website', UrlType::class, [
        'label' => 'card.website',
        'required' => false,
        'attr' => ['placeholder' => 'card.website.placeholder'],
    ])
    ->add('linkedin', UrlType::class, [
        'label' => 'card.linkedin',
        'required' => false,
        'attr' => ['placeholder' => 'card.linkedin.placeholder'],
    ])
    ->add('twitter', UrlType::class, [
        'label' => 'card.twitter',
        'required' => false,
        'attr' => ['placeholder' => 'card.twitter.placeholder'],
    ]);
```

**Data Transformation**:
Form fields are mapped to Card entity's `content` JSON field:

```php
// Form data structure
[
    'name' => 'John Doe',
    'email' => 'john@example.com',
    // ...
]

// Transformed to Card entity content
$card->setContent([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '',
    'company' => '',
    'title' => '',
    'bio' => '',
    'website' => '',
    'social' => [
        'linkedin' => '',
        'twitter' => '',
    ],
]);
```

**Validation Rules**:
- `name`: Required, maximum 255 characters
- `email`: Optional, must be valid email format if provided
- `phone`: Optional, maximum 50 characters
- `company`: Optional, maximum 255 characters
- `title`: Optional, maximum 255 characters
- `bio`: Optional, maximum 1000 characters
- `website`: Optional, must be valid URL format if provided
- `linkedin`: Optional, must be valid URL format if provided
- `twitter`: Optional, must be valid URL format if provided

**Custom Validation**:
- At least one field must be filled (name is required, but other fields can be empty)
- URL fields must be valid URLs if provided (Symfony URL validator)
- Email must be valid format if provided (Symfony Email validator)

**Error Messages** (translation keys):
- `name`: `card.name.required`, `card.name.max_length`
- `email`: `card.email.invalid`
- `phone`: `card.phone.max_length`
- `company`: `card.company.max_length`
- `title`: `card.title.max_length`
- `bio`: `card.bio.max_length`
- `website`: `card.website.invalid`
- `linkedin`: `card.linkedin.invalid`
- `twitter`: `card.twitter.invalid`

**Template**: `card/_form.html.twig`

**Usage**:
```php
// Create mode
$card = new Card();
$form = $this->createForm(CardFormType::class, $card);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $cardService->createCard($card, $this->getUser());
    // Redirect with success message
}

// Edit mode
$card = $cardRepository->find($id);
$form = $this->createForm(CardFormType::class, $card);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $cardService->updateCard($card);
    // Redirect with success message
}
```

---

## Form Rendering

### Twig Form Theme

Forms use Symfony's default form theme with Bootstrap styling (if Bootstrap is used) or custom CSS.

**Form Rendering Pattern**:
```twig
{{ form_start(form, {'attr': {'class': 'card-form'}}) }}
    {{ form_row(form.name) }}
    {{ form_row(form.email) }}
    {{ form_row(form.phone) }}
    {{ form_row(form.company) }}
    {{ form_row(form.title) }}
    {{ form_row(form.bio) }}
    {{ form_row(form.website) }}
    
    <h3>{{ 'card.social.title'|trans }}</h3>
    {{ form_row(form.linkedin) }}
    {{ form_row(form.twitter) }}
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            {{ 'card.save'|trans }}
        </button>
        <a href="{{ path('app_card_index') }}" class="btn btn-secondary">
            {{ 'card.cancel'|trans }}
        </a>
    </div>
{{ form_end(form) }}
```

### Form Styling

- Forms follow application's design system
- Error messages displayed below fields
- Required fields marked with asterisk (*)
- Submit buttons use primary action styling
- Forms are responsive (mobile-friendly)
- Social media fields grouped in a section

---

## Form Validation Flow

### Card Form Validation

1. **Client-Side Validation** (HTML5):
   - `name` field is required
   - `email` field validates email format
   - `website`, `linkedin`, `twitter` fields validate URL format
   - Browser validates required fields and formats

2. **Server-Side Validation** (Symfony):
   - Form data bound to Card entity
   - Symfony Validator checks constraints
   - Custom validation for content structure

3. **Business Logic Validation** (Service Layer):
   - Quota validation before card creation
   - Slug generation and uniqueness check
   - User ownership validation for edits

4. **Error Handling**:
   - Validation errors displayed in form
   - Flash messages for success/error
   - Redirect on success, stay on page on error

---

## Translation Keys

All form labels, placeholders, and error messages use translation keys:

**Form Labels**:
- `card.name`: "Name"
- `card.email`: "Email"
- `card.phone`: "Phone"
- `card.company`: "Company"
- `card.title`: "Job Title"
- `card.bio`: "Bio"
- `card.website`: "Website"
- `card.linkedin`: "LinkedIn"
- `card.twitter`: "Twitter"

**Placeholders**:
- `card.name.placeholder`: "Your full name"
- `card.email.placeholder`: "your.email@example.com"
- `card.phone.placeholder`: "+1234567890"
- `card.company.placeholder`: "Company Name"
- `card.title.placeholder`: "Your job title"
- `card.bio.placeholder`: "Tell us about yourself..."
- `card.website.placeholder`: "https://yourwebsite.com"
- `card.linkedin.placeholder`: "https://linkedin.com/in/yourprofile"
- `card.twitter.placeholder`: "https://twitter.com/yourhandle"

**Error Messages**:
- `card.name.required`: "Name is required"
- `card.name.max_length`: "Name cannot exceed 255 characters"
- `card.email.invalid`: "Please enter a valid email address"
- `card.phone.max_length`: "Phone cannot exceed 50 characters"
- `card.company.max_length`: "Company name cannot exceed 255 characters"
- `card.title.max_length`: "Job title cannot exceed 255 characters"
- `card.bio.max_length`: "Bio cannot exceed 1000 characters"
- `card.website.invalid`: "Please enter a valid URL"
- `card.linkedin.invalid`: "Please enter a valid LinkedIn URL"
- `card.twitter.invalid`: "Please enter a valid Twitter URL"

**Success Messages**:
- `card.created`: "Card created successfully. Public URL: /c/{slug}"
- `card.updated`: "Card updated successfully"
- `card.deleted`: "Card deleted successfully"

---

## Form Security

### CSRF Protection

All forms include CSRF token:
```php
$form = $this->createForm(CardFormType::class, $card);
// CSRF token automatically included
```

### Authorization Checks

- Form submission validated in controller
- `#[IsGranted('ROLE_USER')]` on card management controllers
- User can only submit forms for their own cards (ownership validated in service)

### Input Sanitization

- All form data validated and sanitized by Symfony
- URL fields validated for proper format
- Email fields validated for proper format
- Text fields sanitized to prevent XSS (Twig auto-escaping)

---

## Notes

- Forms follow Symfony best practices
- All user-facing text is internationalized
- Forms are accessible (ARIA labels, proper HTML structure)
- Error messages are user-friendly and actionable
- Form validation happens at multiple layers for security
- Content is stored as JSON in Card entity (form handles transformation)

