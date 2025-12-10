# Tasks: Multi-user (Enterprise)

**Input**: Design documents from `/specs/007-multi-user/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3, US4)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `app/src/`, `app/templates/`, `app/migrations/`
- Paths shown below follow Symfony 8 structure

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [x] T001 [P] Add team translation keys to `app/translations/messages.en.yaml` for team management interface, invitations, roles, and card assignments
- [x] T002 [P] Add team translation keys to `app/translations/messages.fr.yaml` for team management interface, invitations, roles, and card assignments

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T003 [US1] [US2] [US3] [US4] Create TeamRole enum in `app/src/Enum/TeamRole.php` with ADMIN and MEMBER cases, displayName method, and permission methods (canAssignCards, canManageMembers, canViewAllCards)
- [x] T004 [US1] [US2] [US3] [US4] Create TeamMember entity in `app/src/Entity/TeamMember.php` with ManyToOne relationship to Account, ManyToOne relationship to User (nullable), role field (TeamRole enum), invitation fields (status, token, expiresAt), timestamps (joinedAt, lastActivityAt, createdAt), and OneToMany relationship to CardAssignment
- [x] T005 [US1] [US2] [US3] [US4] Create CardAssignment entity in `app/src/Entity/CardAssignment.php` with ManyToOne relationship to Card, ManyToOne relationship to TeamMember, ManyToOne relationship to User (assignedBy), assignedAt timestamp, and unique constraint on (card_id, team_member_id)
- [x] T006 [US1] [US2] [US3] [US4] Update Account entity in `app/src/Entity/Account.php` to add OneToMany relationship to TeamMember (mappedBy: 'account', cascade: ['remove'])
- [x] T007 [US1] [US2] [US3] [US4] Update Card entity in `app/src/Entity/Card.php` to add OneToMany relationship to CardAssignment (mappedBy: 'card', cascade: ['remove'])
- [x] T008 [US1] [US2] [US3] [US4] Create TeamMemberRepository in `app/src/Repository/TeamMemberRepository.php` with findByAccount, findByAccountAndUser, findByToken, and findExpiredInvitations methods
- [x] T009 [US1] [US2] [US3] [US4] Create CardAssignmentRepository in `app/src/Repository/CardAssignmentRepository.php` with findByCard, findByTeamMember, and isAssignedTo methods
- [x] T010 [US1] [US2] [US3] [US4] Create Doctrine migration for team_members and card_assignments tables with all required columns, indexes, foreign key constraints, and unique constraints
- [x] T011 [US1] [US2] [US3] [US4] Run migration to create team_members and card_assignments tables in database

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Add Team Members (Priority: P1) 🎯 MVP

**Goal**: Enterprise account owners can invite team members via email, send invitation emails, and team members can accept invitations to join the team

**Independent Test**: Log in as Enterprise account owner, navigate to team management, invite a team member via email, verify invitation email is sent, click invitation link, accept invitation, and verify team member can access Enterprise account

### Implementation for User Story 1

- [x] T012 [US1] Create TeamInvitationService in `app/src/Service/TeamInvitationService.php` with createInvitation method (validates Enterprise plan, checks duplicates, generates token, sets expiration), sendInvitationEmail method (uses Symfony Mailer with Twig template), and acceptInvitation method (validates token, checks expiration, links user, updates status)
- [x] T013 [US1] Create TeamService in `app/src/Service/TeamService.php` with canManageTeam method (checks if user is account owner or ADMIN), getTeamMembers method (returns team members for account), and basic team management methods
- [x] T014 [US1] Create TeamInvitationFormType in `app/src/Form/TeamInvitationFormType.php` with email field (EmailType) and role field (ChoiceType with ADMIN/MEMBER choices)
- [x] T015 [US1] Create TeamController in `app/src/Controller/TeamController.php` with index method (GET /team) that displays team member list, checks Enterprise plan, and shows invitation form for owners/ADMINs
- [x] T016 [US1] Add invite method to TeamController in `app/src/Controller/TeamController.php` for POST /team/invite route that handles invitation form submission, creates invitation, sends email, and redirects with success message
- [x] T017 [US1] Add acceptInvitation method to TeamController in `app/src/Controller/TeamController.php` for GET /team/accept/{token} route that displays invitation acceptance page
- [x] T018 [US1] Add processAcceptance method to TeamController in `app/src/Controller/TeamController.php` for POST /team/accept/{token} route that processes invitation acceptance or decline
- [x] T019 [US1] Create team management template in `app/templates/team/index.html.twig` with team member list, invitation form (for owners/ADMINs), and plan-based access messages
- [x] T020 [US1] Create invitation acceptance template in `app/templates/team/accept_invitation.html.twig` with invitation details, acceptance/decline buttons, and email mismatch handling
- [x] T021 [US1] Create invitation email template in `app/templates/email/team_invitation.html.twig` with invitation details, acceptance link, and expiration date

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently - Enterprise owners can invite team members and invitations can be accepted

---

## Phase 4: User Story 2 - Assign Cards to Team Members (Priority: P1) 🎯 MVP

**Goal**: Enterprise account owners and team ADMINs can assign cards to team members, and MEMBERs can only access their assigned cards

**Independent Test**: Log in as Enterprise account owner, assign a card to a team member, log in as that team member (MEMBER role), verify they can access and edit the assigned card, attempt to access unassigned card and verify access denied

### Implementation for User Story 2

- [x] T022 [US2] Create TeamMemberVoter in `app/src/Security/Voter/TeamMemberVoter.php` with supports method (checks TEAM_ASSIGN_CARD, TEAM_MANAGE_MEMBERS, TEAM_VIEW_ALL attributes), voteOnAttribute method (checks account ownership, team membership, role permissions), and integrates with Symfony Security
- [x] T023 [US2] Create CardAssignmentFormType in `app/src/Form/CardAssignmentFormType.php` with teamMembers field (EntityType, multiple selection, filtered to accepted team members of same account)
- [x] T024 [US2] Update CardService in `app/src/Service/CardService.php` to add canAccessCard method (checks card ownership, team membership, role, and assignments), getAssignedCardsForUser method (returns cards assigned to team member), and assignment-related methods
- [x] T025 [US2] Add assign method to CardController in `app/src/Controller/CardController.php` for POST /cards/{id}/assign route that handles card assignment form submission, creates CardAssignment records, and redirects with success message
- [x] T026 [US2] Add unassign method to CardController in `app/src/Controller/CardController.php` for POST /cards/{id}/unassign/{teamMemberId} route that removes card assignment
- [x] T027 [US2] Update CardController in `app/src/Controller/CardController.php` index method to filter cards by assignment for MEMBERs (show only assigned cards) and show assignments for owners/ADMINs
- [x] T028 [US2] Update CardController in `app/src/Controller/CardController.php` edit method to check team member access using CardService.canAccessCard before allowing edit
- [x] T029 [US2] Update card list template in `app/templates/card/index.html.twig` to show card assignments for Enterprise accounts and filter cards for MEMBERs
- [x] T030 [US2] Update card edit template in `app/templates/card/edit.html.twig` to show card assignment section (for owners/ADMINs) with current assignments and assignment form

**Checkpoint**: At this point, User Story 2 should be fully functional and testable independently - Cards can be assigned to team members and MEMBERs can only access assigned cards

---

## Phase 5: User Story 3 - Manage Team Member Roles and Permissions (Priority: P2)

**Goal**: Enterprise account owners can change team member roles (ADMIN ↔ MEMBER) and remove team members, with proper permission enforcement

**Independent Test**: Log in as Enterprise account owner, change a team member's role from MEMBER to ADMIN, verify ADMIN gains permissions, remove a team member, verify they lose access, attempt to change role as ADMIN and verify access denied

### Implementation for User Story 3

- [x] T031 [US3] Create TeamMemberRoleFormType in `app/src/Form/TeamMemberRoleFormType.php` with role field (ChoiceType with ADMIN/MEMBER choices)
- [x] T032 [US3] Update TeamService in `app/src/Service/TeamService.php` to add changeRole method (validates account owner, prevents changing owner role, updates role), removeTeamMember method (validates account owner, prevents removing owner, deletes team member), and revokeTeamAccess method (for plan downgrades)
- [x] T033 [US3] Add changeRole method to TeamController in `app/src/Controller/TeamController.php` for POST /team/{id}/role route that handles role change form submission
- [x] T034 [US3] Add remove method to TeamController in `app/src/Controller/TeamController.php` for POST /team/{id}/remove route that handles team member removal with confirmation
- [x] T035 [US3] Update team management template in `app/templates/team/index.html.twig` to add role change form and remove button for each team member (owners only)
- [x] T036 [US3] Update TeamMemberVoter in `app/src/Security/Voter/TeamMemberVoter.php` to enforce role-based permissions (ADMIN can assign cards and view all, MEMBER can only access assigned cards)

**Checkpoint**: At this point, User Story 3 should be fully functional and testable independently - Team member roles can be changed and members can be removed

---

## Phase 6: User Story 4 - View Team Activity and Card Assignments (Priority: P3)

**Goal**: Enterprise account owners and ADMINs can view team overview with member roles and card assignments, MEMBERs see only their assigned cards

**Independent Test**: Log in as Enterprise account owner, view team overview page, verify all team members with roles and card counts are displayed, log in as MEMBER, verify only assigned cards are visible

### Implementation for User Story 4

- [x] T037 [US4] Update TeamService in `app/src/Service/TeamService.php` to add getTeamOverview method (returns team members with card assignment counts) and getTeamMemberDetails method (returns team member with assigned cards)
- [x] T038 [US4] Update TeamController in `app/src/Controller/TeamController.php` index method to include team overview data (card assignment counts per member) for owners/ADMINs
- [x] T039 [US4] Update team management template in `app/templates/team/index.html.twig` to display card assignment counts per team member and last activity timestamps
- [x] T040 [US4] Update CardService in `app/src/Service/CardService.php` to add getCardAssignments method (returns assignments for a card) and update card queries to include assignment information
- [x] T041 [US4] Update card list template in `app/templates/card/index.html.twig` to show which cards are assigned to which team members (for owners/ADMINs)

**Checkpoint**: At this point, User Story 4 should be fully functional and testable independently - Team overview shows member roles and card assignments

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [x] T042 [P] Update AccountService in `app/src/Service/AccountService.php` changePlan method to call TeamService.revokeTeamAccess when downgrading from Enterprise plan
- [x] T043 [P] Add invitation expiration cleanup to TeamInvitationService in `app/src/Service/TeamInvitationService.php` with method to mark expired invitations (can be called by scheduled task)
- [x] T044 [P] Add error handling and validation error messages display in team management templates
- [x] T045 [P] Add flash message display for success/error feedback in team management templates
- [x] T046 [P] Add security hardening for invitation tokens (rate limiting, token rotation on resend) - Implemented with InvitationRateLimiter service (10/hour, 50/day limits) and resendInvitation method with token rotation
- [x] T047 [P] Add performance optimization for team queries (ensure no N+1 queries, use QueryBuilder joins)
- [x] T048 [P] Update translations with all team-related messages (error messages, success messages, labels, help text)
- [ ] T049 [P] Add integration tests for team invitation flow (invitation creation, email sending, acceptance)
- [ ] T050 [P] Add integration tests for card assignment flow (assignment creation, access control, unassignment)
- [ ] T051 [P] Add integration tests for role management (role changes, permission enforcement, member removal)
- [x] T052 [P] Add validation for duplicate invitations at form level (prevent duplicate active invitations) - Already implemented in TeamInvitationService.createInvitation
- [x] T053 [P] Add validation for email matching on invitation acceptance (ensure user email matches invitation email) - Already implemented in TeamInvitationService.acceptInvitation
- [x] T054 [P] Add handling for user account deletion (ensure TeamMember.user is set to NULL, preserve team membership record) - Already configured via onDelete: 'SET NULL' in TeamMember entity
- [x] T055 [P] Add handling for card deletion (ensure CardAssignments are deleted via CASCADE) - Already configured via onDelete: 'CASCADE' in CardAssignment entity
- [x] T056 [P] Add handling for team member removal with assigned cards (preserve assignments, make cards unassigned) - Already configured via onDelete: 'CASCADE' in CardAssignment entity
- [x] T057 [P] Add lastActivityAt timestamp updates when team members access cards (update on card view/edit)
- [x] T058 [P] Add validation to prevent account owner from removing themselves from team - Already implemented in TeamService.removeTeamMember
- [x] T059 [P] Add validation to prevent account owner role changes - Already implemented in TeamService.changeRole
- [x] T060 [P] Add support for multiple Enterprise account membership (user can be member of multiple accounts) - Data model already supports it, added TeamMemberRepository methods (findEnterpriseAccountsForUser, findAllMembershipsForUser) for multi-account queries
- [ ] T061 [P] Add account context switching UI (optional enhancement - can be deferred)
- [x] T062 [P] Add scheduled task for marking expired invitations (optional enhancement - can use Symfony Scheduler or cron) - Created CleanupExpiredInvitationsCommand (app:team:cleanup-expired-invitations) that can be scheduled via cron or Symfony Scheduler

---

## Testing Checklist

### User Story 1 Testing
- [ ] Team member invitation sent successfully
- [ ] Invitation email received with valid link
- [ ] Invitation acceptance works for logged-in users
- [ ] Invitation expiration enforced (7 days)
- [ ] Duplicate invitations prevented
- [ ] Plan-based access control works (Free/Pro users see upgrade prompts)

### User Story 2 Testing
- [ ] Card assignment works correctly
- [ ] MEMBERs can only access assigned cards
- [ ] ADMINs can access all cards in account
- [ ] Card unassignment works correctly
- [ ] Card assignments preserved when team members removed
- [ ] Account owner has full access to all cards

### User Story 3 Testing
- [ ] Role changes work correctly (ADMIN ↔ MEMBER)
- [ ] Team member removal works correctly
- [ ] Permission enforcement works (ADMIN can assign, MEMBER cannot)
- [ ] Account owner cannot be removed
- [ ] Account owner cannot change own role
- [ ] Only account owner can change roles and remove members

### User Story 4 Testing
- [ ] Team overview shows all members with roles
- [ ] Card assignment counts displayed correctly
- [ ] Last activity timestamps displayed correctly
- [ ] MEMBERs see only assigned cards
- [ ] ADMINs see all cards in account

### Cross-Cutting Testing
- [ ] Plan downgrade revokes team access
- [ ] Plan downgrade preserves team data
- [ ] Multiple Enterprise account membership works
- [ ] User account deletion handles team memberships correctly
- [ ] Card deletion handles assignments correctly
- [ ] No N+1 queries in team-related operations
- [ ] Performance meets success criteria (SC-001 to SC-010)

---

## Notes

- All tasks follow Symfony architecture patterns (Controllers → Services → Repositories)
- Authorization enforced via TeamMemberVoter
- Plan-based access enforced at service layer
- Invitation tokens expire after 7 days
- Card assignments preserved when team members removed
- Account downgrade preserves data but revokes access
- All user-facing messages use translation keys (EN/FR)
- CSRF protection on all POST routes
- Form validation at both client and server side

