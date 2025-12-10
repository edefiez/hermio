# Contracts: Digital Card Management

**Feature**: 005-digital-card  
**Date**: December 8, 2025

## Overview

This directory contains API and interface contracts for the digital card management feature. Contracts define the structure and behavior of routes, forms, and services.

## Contracts

### Routes

- **[routes.md](./routes.md)** - Symfony route definitions for card management and public card viewing
  - Public routes: `/c/{slug}` for public card access
  - User-facing routes: `/cards/*` for card management
  - Route parameters, authorization, and response formats

### Forms

- **[forms.md](./forms.md)** - Symfony form definitions for card creation and editing
  - CardFormType structure and fields
  - Validation rules and error messages
  - Form rendering patterns

## Usage

These contracts serve as the specification for implementing controllers, forms, and services. They define:

- Route patterns and requirements
- Request/response formats
- Authorization requirements
- Form field structures
- Validation rules
- Error handling

## Implementation Notes

- All routes return HTML (Twig-rendered) - this is not a REST API
- Public routes are accessible without authentication
- Card management routes require ROLE_USER authentication
- Forms use Symfony Form component with Twig rendering
- All user-facing text is internationalized (EN/FR)

