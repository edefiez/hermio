# Contracts: Account Management / Subscription Model

**Feature**: 003-account-subscription  
**Date**: 2025-12-08

## Overview

This directory contains contracts defining the interfaces and interactions for the account management and subscription system. Since this is a Twig-based web application (not a REST API), contracts define routes, forms, and service interfaces rather than API endpoints.

## Contract Documents

### [routes.md](./routes.md)

Defines all HTTP routes for the account management system:
- User-facing routes (My Plan page, Account management)
- Administrative routes (Account listing, plan changes)
- Route parameters, authorization requirements, and response formats

### [forms.md](./forms.md)

Defines Symfony Form contracts:
- Plan change form for administrators
- Form validation rules and error messages
- Form rendering and styling guidelines

## Contract Types

### Routes

Routes follow Symfony conventions:
- User routes: `/account/*`
- Admin routes: `/admin/accounts/*`
- All routes return HTML (Twig-rendered)
- Authorization enforced via Symfony Security

### Forms

Forms use Symfony Form component:
- Built with FormType classes
- Validated via Symfony Validator
- Rendered via Twig templates
- CSRF protection enabled

### Services

Service contracts are defined in code (not documented here):
- `AccountService`: Plan management operations
- `QuotaService`: Quota validation and enforcement
- Service interfaces follow Symfony dependency injection patterns

## Usage

These contracts serve as:
1. **Implementation Reference**: Developers use these to implement controllers, forms, and routes
2. **Testing Guide**: Test cases verify contracts are met
3. **Documentation**: Stakeholders understand system interfaces

## Notes

- Contracts are technology-agnostic where possible
- Implementation details (Symfony-specific) are documented for developer reference
- All contracts support internationalization (EN/FR)
- Security considerations are documented for each contract

