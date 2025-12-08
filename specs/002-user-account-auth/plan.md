# Implementation Plan: User Account & Authentication

**Branch**: `002-user-account-auth` | **Date**: December 8, 2025 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/002-user-account-auth/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Implementation of comprehensive user account and authentication system for Hermio, including user registration, email verification, login/logout, password reset, session management, and profile management. Built on Symfony 8 architecture with Doctrine ORM, Twig templating, and Webpack Encore for assets.

## Technical Context

**Language/Version**: PHP 8.2+ with Symfony 8.0  
**Primary Dependencies**: Symfony Security Bundle, Doctrine ORM, Symfony Mailer, Twig, Webpack Encore  
**Storage**: Database via Doctrine ORM (existing setup)  
**Testing**: PHPUnit with Symfony Test Framework  
**Target Platform**: Web application (Linux/macOS server)
**Project Type**: Web application - follows Symfony MVC architecture  
**Performance Goals**: Handle 100 concurrent sessions, <200ms authentication response time  
**Constraints**: HTTPS required, email delivery dependency, 2-hour session timeout  
**Scale/Scope**: Multi-user system with secure authentication, email verification, and session management

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Initial Check (Pre-Phase 0)
✅ **Clean Symfony Architecture**: Will follow Controller → Service → Repository pattern  
✅ **Twig-Driven Frontend**: Using Twig templates, no React/Vue components  
✅ **Doctrine ORM**: Entities for User, tokens, sessions; migrations for schema  
✅ **Security & Authentication**: Using Symfony Security component with PasswordHasher  
✅ **Asset Pipeline**: Webpack Encore for assets, no changes to build system  
✅ **Internationalization**: All UI text will use Symfony Translation  
✅ **Coding Standards**: PSR-12, Symfony directory structure, strong typing  
✅ **Feature Workflow**: Following speckit pattern with proper documentation

### Post-Design Check (Phase 1 Complete)
✅ **Controllers remain thin**: All business logic moved to Service layer classes  
✅ **Service layer properly defined**: UserRegistrationService, EmailVerificationService, etc.  
✅ **Repository pattern**: Custom repositories for complex queries, proper data access  
✅ **Entities in correct location**: src/Entity/ with proper Doctrine annotations  
✅ **Form types structured**: src/Form/ with proper validation constraints  
✅ **Twig templates only**: No frontend frameworks, proper translation usage  
✅ **Doctrine migrations**: Schema changes via migration files  
✅ **Symfony Security**: Native authentication, PasswordHasher, proper user providers  
✅ **Webpack Encore unchanged**: Using existing build system, Stimulus controllers only  
✅ **Event subscribers**: AuthenticationSubscriber for cross-cutting concerns  
✅ **No static services**: All services use dependency injection  

**Final Status**: ✅ PASS - All constitution requirements satisfied in final design

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/src/
├── Entity/
│   ├── User.php                    # Main user account entity
│   ├── EmailVerificationToken.php  # Email verification tokens
│   ├── PasswordResetToken.php     # Password reset tokens
│   └── AuthenticationLog.php      # Security audit logs
├── Repository/
│   ├── UserRepository.php         # User queries and data access
│   ├── EmailVerificationTokenRepository.php
│   ├── PasswordResetTokenRepository.php
│   └── AuthenticationLogRepository.php
├── Controller/
│   ├── SecurityController.php     # Login, logout, registration
│   ├── RegistrationController.php # Registration flow
│   ├── ResetPasswordController.php # Password reset
│   └── ProfileController.php      # Profile management
├── Service/
│   ├── UserRegistrationService.php # Registration business logic
│   ├── EmailVerificationService.php # Email verification
│   ├── PasswordResetService.php   # Password reset logic
│   ├── AuthenticationLogService.php # Security logging
│   └── SessionService.php         # Session management
├── Form/
│   ├── RegistrationFormType.php   # Registration form
│   ├── LoginFormType.php          # Login form
│   ├── ResetPasswordFormType.php  # Password reset forms
│   └── ChangePasswordFormType.php # Password change form
├── Security/
│   ├── LoginFormAuthenticator.php # Custom authenticator
│   └── UserChecker.php           # User verification checks
└── EventSubscriber/
    └── AuthenticationSubscriber.php # Auth event handling

app/templates/
├── security/
│   ├── login.html.twig
│   ├── register.html.twig
│   └── reset_password/
├── registration/
│   └── confirmation_email.html.twig
└── profile/
    └── edit.html.twig

app/assets/
├── controllers/
│   ├── registration_controller.js  # Registration form handling
│   ├── login_controller.js         # Login form enhancement
│   └── password_controller.js      # Password visibility toggle
└── styles/
    └── auth.scss                   # Authentication-specific styles

app/migrations/
└── VersionXXXX_CreateAuthTables.php # Database schema

app/tests/
├── Controller/
├── Service/
├── Entity/
└── Integration/
    └── AuthenticationFlowTest.php
```

**Structure Decision**: Symfony 8 web application following MVC architecture with clear separation of concerns: Entities for data model, Repositories for data access, Services for business logic, Controllers for HTTP handling, and Twig templates for presentation.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |
