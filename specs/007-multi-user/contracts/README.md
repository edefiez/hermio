# Contracts: Multi-user (Enterprise)

**Feature**: 007-multi-user  
**Date**: December 10, 2025

## Overview

This directory contains API and form contracts for the multi-user team collaboration system. Contracts define the interfaces, routes, and form specifications that must be implemented.

## Files

- **routes.md**: Defines all HTTP routes for team management, invitations, and card assignments
- **forms.md**: Defines all Symfony form types and their validation rules

## Contract Types

### Routes

All routes follow Symfony conventions:
- Authentication required (ROLE_USER) for team management routes
- Enterprise plan requirement enforced at service layer
- Role-based access control via TeamMemberVoter
- CSRF protection on all POST routes

### Forms

All forms use Symfony Form component:
- Client-side and server-side validation
- CSRF protection enabled
- Twig rendering
- Internationalization support (EN/FR)

## Integration Points

- **Authentication**: Uses existing Symfony Security system
- **Email**: Uses existing Symfony Mailer infrastructure
- **Account**: Extends existing Account entity with team relationships
- **Card**: Extends existing Card entity with assignment relationships

