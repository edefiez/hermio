# Form Contracts: Branding & Theme (Pro / Enterprise)

**Feature**: 006-branding-theme  
**Date**: December 10, 2025  
**Type**: Symfony Forms (Twig-based web application)

## Overview

This document defines the form contracts for the branding configuration system. Forms include branding configuration (colors, logo) and custom template configuration (Enterprise only). All forms use Symfony Form component with Twig rendering.

## Form Types

### 1. BrandingFormType

**Purpose**: Form for configuring brand colors and logo

**Class**: `App\Form\BrandingFormType`

**Fields**:

| Field Name | Type | Required | Validation | Description |
|------------|------|----------|------------|-------------|
| `primaryColor` | TextType | No | Regex: `/^#[0-9A-Fa-f]{6}$/` | Primary brand color (hex format) |
| `secondaryColor` | TextType | No | Regex: `/^#[0-9A-Fa-f]{6}$/` | Secondary brand color (hex format) |
| `logo` | FileType | No | File: PNG/JPG/JPEG/SVG, max 5MB | Logo file upload |
| `logoPosition` | ChoiceType | No | Choice: top-left, top-center, top-right, center, bottom-left, bottom-center, bottom-right | Logo display position |
| `logoSize` | ChoiceType | No | Choice: small, medium, large | Logo display size |

**Form Configuration**:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => AccountBranding::class,
        'csrf_protection' => true,
        'csrf_field_name' => '_token',
        'csrf_token_id' => 'branding',
    ]);
}
```

**Validation Rules**:
- `primaryColor`: Optional, must match hex format (#RRGGBB) if provided
- `secondaryColor`: Optional, must match hex format (#RRGGBB) if provided
- `logo`: Optional, must be valid image file (PNG/JPG/JPEG/SVG), max 5MB
- `logoPosition`: Optional, must be one of allowed position values
- `logoSize`: Optional, must be one of allowed size values

**Form Rendering** (Twig):
```twig
{{ form_start(form, {'attr': {'enctype': 'multipart/form-data'}}) }}
    {{ form_row(form.primaryColor) }}
    {{ form_row(form.secondaryColor) }}
    {{ form_row(form.logo) }}
    {{ form_row(form.logoPosition) }}
    {{ form_row(form.logoSize) }}
    {{ form_row(form._token) }}
    <button type="submit">{{ 'branding.save'|trans }}</button>
{{ form_end(form) }}
```

---

### 2. TemplateFormType

**Purpose**: Form for configuring custom template (Enterprise only)

**Class**: `App\Form\TemplateFormType`

**Fields**:

| Field Name | Type | Required | Validation | Description |
|------------|------|----------|------------|-------------|
| `customTemplate` | TextareaType | Yes | Twig syntax validation | Custom Twig template content |

**Form Configuration**:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => AccountBranding::class,
        'csrf_protection' => true,
        'csrf_field_name' => '_token',
        'csrf_token_id' => 'template',
    ]);
}
```

**Validation Rules**:
- `customTemplate`: Required, must be valid Twig syntax, must extend base template

**Form Rendering** (Twig):
```twig
{{ form_start(form) }}
    {{ form_row(form.customTemplate, {'attr': {'rows': 20, 'class': 'code-editor'}}) }}
    {{ form_row(form._token) }}
    <button type="submit">{{ 'branding.template.save'|trans }}</button>
{{ form_end(form) }}
```

---

## Field Specifications

### primaryColor Field

**Type**: `TextType`  
**Widget**: `<input type="text">` with color picker integration (optional)

**Attributes**:
- `placeholder`: "#FF5733"
- `pattern`: `^#[0-9A-Fa-f]{6}$`
- `maxlength`: 7

**Validation**:
- Format: Hex color code (#RRGGBB)
- Case-insensitive (accepts both uppercase and lowercase)
- Optional (can be empty)

**Example Values**:
- Valid: `#FF5733`, `#ff5733`, `#000000`, `#FFFFFF`
- Invalid: `FF5733` (missing #), `#FF5` (too short), `#GGGGGG` (invalid hex)

---

### secondaryColor Field

**Type**: `TextType`  
**Widget**: `<input type="text">` with color picker integration (optional)

**Attributes**:
- `placeholder`: "#6c757d"
- `pattern`: `^#[0-9A-Fa-f]{6}$`
- `maxlength`: 7

**Validation**:
- Format: Hex color code (#RRGGBB)
- Case-insensitive (accepts both uppercase and lowercase)
- Optional (can be empty)

**Example Values**:
- Valid: `#6c757d`, `#6C757D`, `#000000`, `#FFFFFF`
- Invalid: `6c757d` (missing #), `#6c7` (too short), `#HHHHHH` (invalid hex)

---

### logo Field

**Type**: `FileType`  
**Widget**: `<input type="file">`

**Attributes**:
- `accept`: `image/png,image/jpeg,image/jpg,image/svg+xml`
- `max`: 5242880 (5MB in bytes)

**Validation**:
- File type: PNG, JPG, JPEG, SVG only
- File size: Maximum 5MB
- Optional (can be empty if logo already exists)

**File Handling**:
- Uploaded file moved to `public/uploads/branding/logos/`
- Filename generated as random hex string + extension
- Old logo file deleted when new logo uploaded

**Example Files**:
- Valid: `logo.png`, `brand.jpg`, `company.svg`
- Invalid: `logo.gif` (unsupported format), `large-logo.png` (exceeds 5MB)

---

### logoPosition Field

**Type**: `ChoiceType`  
**Widget**: `<select>` dropdown

**Choices**:
```php
[
    'top-left' => 'branding.logo.position.top_left',
    'top-center' => 'branding.logo.position.top_center',
    'top-right' => 'branding.logo.position.top_right',
    'center' => 'branding.logo.position.center',
    'bottom-left' => 'branding.logo.position.bottom_left',
    'bottom-center' => 'branding.logo.position.bottom_center',
    'bottom-right' => 'branding.logo.position.bottom_right',
]
```

**Validation**:
- Must be one of the allowed position values
- Optional (defaults to 'top-left' if not provided)

**Default Value**: `top-left`

---

### logoSize Field

**Type**: `ChoiceType`  
**Widget**: `<select>` dropdown

**Choices**:
```php
[
    'small' => 'branding.logo.size.small',
    'medium' => 'branding.logo.size.medium',
    'large' => 'branding.logo.size.large',
]
```

**Validation**:
- Must be one of the allowed size values
- Optional (defaults to 'medium' if not provided)

**Default Value**: `medium`

---

### customTemplate Field

**Type**: `TextareaType`  
**Widget**: `<textarea>` (suggested: code editor with syntax highlighting)

**Attributes**:
- `rows`: 20
- `cols`: 80
- `class`: `code-editor` (for syntax highlighting)
- `placeholder`: `{% extends 'public/card.html.twig' %}\n\n{% block body %}\n    {# Your custom template content #}\n{% endblock %}`

**Validation**:
- Required (must not be empty)
- Must be valid Twig syntax
- Must extend base template (`public/card.html.twig`)
- Must not contain dangerous functions (exec, system, file operations)

**Example Template**:
```twig
{% extends 'public/card.html.twig' %}

{% block stylesheets %}
    {{ parent() }}
    <style>
        .custom-card {
            background: {{ branding.primaryColor|default('#007bff') }};
            color: {{ branding.secondaryColor|default('#ffffff') }};
        }
    </style>
{% endblock %}

{% block body %}
    <div class="custom-card">
        {# Custom card content #}
    </div>
{% endblock %}
```

---

## Form Validation

### Client-Side Validation

- HTML5 validation attributes on form fields
- Color format validation via `pattern` attribute
- File type validation via `accept` attribute
- File size validation via JavaScript (before upload)

### Server-Side Validation

- Symfony Validator constraints on form fields
- Entity-level validation (AccountBranding entity)
- Custom validation for Twig template syntax
- File upload validation (type, size, security)

### Validation Error Messages

**Translation Keys**:
- `branding.color.invalid_format`: "Color must be in hex format (#RRGGBB)"
- `branding.logo.invalid_type`: "Logo must be a PNG, JPG, JPEG, or SVG file"
- `branding.logo.too_large`: "Logo file size must not exceed 5MB"
- `branding.logo.position.invalid`: "Invalid logo position"
- `branding.logo.size.invalid`: "Invalid logo size"
- `branding.template.invalid_syntax`: "Invalid Twig template syntax"
- `branding.template.must_extend_base`: "Template must extend 'public/card.html.twig'"
- `branding.template.dangerous_function`: "Template contains dangerous functions"

---

## Form Rendering

### Branding Configuration Form

**Template**: `branding/configure.html.twig`

**Layout**:
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ 'branding.title'|trans }}{% endblock %}

{% block body %}
    <div class="branding-container">
        <h1>{{ 'branding.title'|trans }}</h1>
        
        {% if not canConfigureBranding %}
            <div class="alert alert-warning">
                {{ 'branding.access_denied'|trans }}
                <a href="{{ path('app_subscription_manage') }}">{{ 'branding.upgrade'|trans }}</a>
            </div>
        {% else %}
            {{ form_start(brandingForm, {'attr': {'enctype': 'multipart/form-data'}}) }}
                <div class="form-section">
                    <h2>{{ 'branding.colors.title'|trans }}</h2>
                    {{ form_row(brandingForm.primaryColor) }}
                    {{ form_row(brandingForm.secondaryColor) }}
                </div>
                
                <div class="form-section">
                    <h2>{{ 'branding.logo.title'|trans }}</h2>
                    {% if branding and branding.logoFilename %}
                        <img src="{{ asset('uploads/branding/logos/' ~ branding.logoFilename) }}" alt="Logo">
                        <a href="{{ path('app_branding_remove_logo') }}">{{ 'branding.logo.remove'|trans }}</a>
                    {% endif %}
                    {{ form_row(brandingForm.logo) }}
                    {{ form_row(brandingForm.logoPosition) }}
                    {{ form_row(brandingForm.logoSize) }}
                </div>
                
                {{ form_row(brandingForm._token) }}
                <button type="submit" class="btn btn-primary">{{ 'branding.save'|trans }}</button>
            {{ form_end(brandingForm) }}
            
            {% if canConfigureTemplate %}
                <div class="form-section">
                    <h2>{{ 'branding.template.title'|trans }}</h2>
                    {{ form_start(templateForm) }}
                        {{ form_row(templateForm.customTemplate) }}
                        {{ form_row(templateForm._token) }}
                        <button type="submit" class="btn btn-primary">{{ 'branding.template.save'|trans }}</button>
                    {{ form_end(templateForm) }}
                </div>
            {% endif %}
        {% endif %}
    </div>
{% endblock %}
```

---

## Form Data Flow

### Saving Branding Configuration

1. User submits `BrandingFormType` form
2. Form validated (colors, logo file, position, size)
3. Logo file uploaded and stored (if provided)
4. Old logo file deleted (if logo replaced)
5. `AccountBranding` entity created or updated
6. Branding applied to public card pages immediately

### Saving Custom Template

1. User submits `TemplateFormType` form (Enterprise only)
2. Template syntax validated
3. Template content saved to `AccountBranding.customTemplate`
4. Custom template applied to public card pages immediately

---

## Notes

- All forms use Symfony Form component with Twig rendering
- File uploads require `enctype="multipart/form-data"` on form
- CSRF protection enabled on all forms
- Form validation happens at both client and server side
- Logo uploads handled securely (filename sanitization, type validation)
- Template validation ensures security (no dangerous functions)

