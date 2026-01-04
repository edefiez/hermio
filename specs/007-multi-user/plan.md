# Implementation Plan: Multi-user (Enterprise)

**Branch**: `007-multi-user` | **Date**: December 10, 2025 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/007-multi-user/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This feature implements multi-user team collaboration for Enterprise accounts, enabling account owners to invite team members, assign roles (ADMIN/MEMBER), and delegate card management. Team members can be invited via email, accept invitations, and collaborate on assigned cards. The system enforces role-based access control, allowing ADMINs to assign cards and manage team members, while MEMBERs can only manage their assigned cards. Card assignments are preserved when team members are removed, and the system handles plan downgrades gracefully by revoking team access while preserving data.

**Technical Approach**: 
- Create `TeamMember` entity with ManyToOne relationship to `Account` and `User`, role enum (ADMIN/MEMBER), invitation status, and token management
- Create `CardAssignment` entity with ManyToMany relationship between `Card` and `TeamMember`
- Implement `TeamInvitationService` for sending invitation emails and managing invitation tokens (similar to EmailVerificationService pattern)
- Build `TeamService` for team member management, role changes, and access control
- Create `TeamMemberVoter` for role-based authorization (ADMIN can assign cards, MEMBER can only access assigned cards)
- Extend `CardService` to check team member assignments before allowing card access
- Integrate with existing `AccountService` to handle plan downgrades and team access revocation
- Use Twig templates for team management interface and invitation acceptance pages

## Technical Context

**Language/Version**: PHP 8.4+  
**Framework**: Symfony 8.0  
**Primary Dependencies**: 
- Doctrine ORM 3.x (TeamMember entity, CardAssignment entity, relationships, migrations)
- Symfony Security Bundle (authentication, role-based access control via Voters)
- Symfony Form Bundle (team member invitation forms, role management forms)
- Symfony Validator (entity and form validation)
- Symfony Mailer (invitation email sending)
- Twig 3.x (templates for team management and invitation acceptance)
- Symfony Translation (i18n for team-related messages)

**Storage**: Doctrine ORM with PostgreSQL/MySQL  
**Testing**: PHPUnit 10+ with Symfony Test framework  
**Target Platform**: Linux/macOS development environment, Docker containers for production  
**Project Type**: Web application (Symfony backend + Twig frontend)  
**Performance Goals**: 
- Team member invitation sent within 2 minutes (SC-001)
- Invitation acceptance completed within 5 minutes (SC-002)
- Card assignment completed within 30 seconds (SC-003)
- Team management interface loads within 2 seconds (SC-010)
- Support up to 50 team members per account without degradation (SC-008)
- Role changes take effect within 5 seconds (SC-006)
- Team member removal completes within 10 seconds (SC-007)

**Constraints**: 
- MUST follow Symfony architecture: Controllers → Services → Repositories
- MUST use Doctrine ORM for all database operations
- MUST enforce Enterprise plan requirement at service layer
- MUST use Symfony Security Voters for role-based authorization
- MUST use Twig templates exclusively (no React/Vue/Svelte)
- MUST follow PSR-12 coding standards
- MUST use Symfony Security for authentication (ROLE_USER required)
- MUST support internationalization (EN/FR) for all user-facing messages
- MUST prevent duplicate active invitations to same email for same account
- MUST allow users to be members of multiple Enterprise accounts
- MUST handle plan downgrades gracefully (revoke access, preserve data)
- Invitation tokens MUST expire after 7 days
- MUST use existing email infrastructure (Symfony Mailer)

**Scale/Scope**: 
- Support for Enterprise plan accounts only (Free/Pro accounts see upgrade prompts)
- Up to 50 team members per Enterprise account (SC-008)
- Team members can belong to multiple Enterprise accounts simultaneously
- Card assignments support multiple team members per card
- Invitation system with 7-day expiration
- Role-based access control (ADMIN vs MEMBER permissions)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Architecture Gate ✅

- **Controller → Service → Repository**: Team management logic will be in `TeamService` and `TeamInvitationService`, database access via `TeamMemberRepository` and `CardAssignmentRepository`
- **No business logic in controllers**: Controllers will only handle HTTP requests/responses and delegate to services
- **Dependency injection**: All services will be injected via constructor
- **Voter pattern**: Authorization logic will be in `TeamMemberVoter` following Symfony Security best practices

### Frontend Gate ✅

- **Twig-only rendering**: All team management pages and invitation acceptance will use Twig templates
- **No React/Vue/Svelte**: Confirmed - using Twig exclusively
- **Webpack Encore**: Existing asset pipeline will be used for team management styling
- **No business logic in templates**: Templates will only display data passed from controllers

### ORM Gate ✅

- **Doctrine ORM**: TeamMember and CardAssignment entities will use Doctrine ORM with proper relationships
- **Migrations**: Schema changes will be managed via Doctrine migrations
- **Repository pattern**: Custom repositories for TeamMember and CardAssignment queries
- **Relationship integrity**: Foreign key constraints ensure data integrity

### Security Gate ✅

- **Symfony Security**: Team management routes require ROLE_USER authentication and Enterprise plan
- **Voter-based authorization**: TeamMemberVoter will enforce ADMIN/MEMBER role permissions
- **CSRF protection**: All form submissions protected with CSRF tokens
- **Invitation token security**: Tokens use secure random generation and expiration
- **Plan-based access**: Enterprise plan requirement enforced at service layer

### i18n Gate ✅

- **Symfony Translation**: All user-facing messages will use translation keys
- **EN/FR support**: Translation files will be created for team-related messages
- **Invitation emails**: Email templates support internationalization

### Coding Standards Gate ✅

- **PSR-12**: All code will follow PSR-12 coding standards
- **Strong typing**: All method signatures will use type hints
- **Descriptive names**: Services, entities, and methods will have clear, descriptive names
- **Symfony conventions**: Directory structure follows Symfony 8 conventions

## Project Structure

### Documentation (this feature)

```text
specs/007-multi-user/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
│   ├── routes.md        # API route definitions
│   └── forms.md         # Form specifications
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── src/
│   ├── Entity/
│   │   ├── TeamMember.php           # New: Team membership entity
│   │   └── CardAssignment.php       # New: Card-to-team-member assignment
│   ├── Repository/
│   │   ├── TeamMemberRepository.php # New: Team member queries
│   │   └── CardAssignmentRepository.php # New: Assignment queries
│   ├── Service/
│   │   ├── TeamService.php          # New: Team member management
│   │   ├── TeamInvitationService.php # New: Invitation handling
│   │   └── CardService.php          # Modified: Add assignment checks
│   ├── Security/
│   │   └── Voter/
│   │       └── TeamMemberVoter.php  # New: Role-based authorization
│   ├── Form/
│   │   ├── TeamInvitationFormType.php    # New: Invitation form
│   │   └── TeamMemberRoleFormType.php     # New: Role change form
│   ├── Controller/
│   │   ├── TeamController.php       # New: Team management routes
│   │   └── CardController.php       # Modified: Add assignment logic
│   └── Enum/
│       └── TeamRole.php             # New: ADMIN/MEMBER enum
├── templates/
│   ├── team/
│   │   ├── index.html.twig         # New: Team member list
│   │   ├── invite.html.twig        # New: Invitation form
│   │   └── accept_invitation.html.twig # New: Invitation acceptance
│   └── email/
│       └── team_invitation.html.twig # New: Invitation email template
├── translations/
│   ├── messages.en.yaml             # Modified: Add team translations
│   └── messages.fr.yaml             # Modified: Add team translations
└── migrations/
    └── Version[timestamp].php       # New: TeamMember and CardAssignment tables
```

**Structure Decision**: Single Symfony application following existing project structure. Team management features integrated into existing controllers and services where appropriate (CardController extended for assignments), with new dedicated controllers for team-specific operations (TeamController).

## Complexity Tracking

> **No violations identified - all gates pass**
