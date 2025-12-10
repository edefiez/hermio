# Feature Specification: Multi-user (Enterprise)

**Feature Branch**: `007-multi-user`  
**Created**: December 10, 2025  
**Status**: Draft  
**Input**: User description: "Feature 07 — Multi-user (Enterprise)"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add Team Members to Enterprise Account (Priority: P1)

An Enterprise account owner wants to invite team members to collaborate on their account. They navigate to a team management interface, enter an email address for a new team member, assign them a role (ADMIN or MEMBER), and send an invitation. The invited user receives an invitation email and can accept to join the team. Once accepted, the team member can access the Enterprise account's resources.

**Why this priority**: Adding team members is the foundation of multi-user functionality. Without this, Enterprise accounts cannot leverage collaborative features. This is the first step that enables all other team-based functionality.

**Independent Test**: Can be fully tested by logging in as an Enterprise account owner, navigating to team management, inviting a team member via email, and verifying that the invitation is sent and can be accepted. This delivers immediate value by enabling team collaboration.

**Acceptance Scenarios**:

1. **Given** I am logged in as an Enterprise account owner, **When** I navigate to the team management page, **Then** I see an interface to add team members with fields for email and role selection
2. **Given** I am an Enterprise account owner adding a team member, **When** I enter a valid email address and select a role (ADMIN or MEMBER), **Then** an invitation is sent to that email address
3. **Given** a user receives a team invitation email, **When** they click the invitation link, **Then** they are prompted to accept or decline the invitation
4. **Given** a user accepts a team invitation, **When** they complete the acceptance process, **Then** they become a team member with the assigned role and can access the Enterprise account
5. **Given** I am an Enterprise account owner, **When** I attempt to invite a user who is already a team member, **Then** I see a clear message indicating the user is already part of the team
6. **Given** I am a Free or Pro account owner, **When** I attempt to access team management features, **Then** I see a message indicating that team features are only available for Enterprise plans

---

### User Story 2 - Assign Cards to Team Members (Priority: P1)

An Enterprise account owner or team ADMIN wants to assign digital cards to specific team members so that team members can manage their assigned cards. They navigate to card management, select a card, and assign it to one or more team members. Assigned team members can then view, edit, and manage their assigned cards.

**Why this priority**: Card assignment is a core collaborative feature that enables team members to work on specific cards. This provides immediate value by allowing distributed card management within an Enterprise account.

**Independent Test**: Can be fully tested by logging in as an Enterprise account owner, selecting a card, assigning it to a team member, and verifying that the assigned team member can access and manage that card. This delivers value by enabling collaborative card management.

**Acceptance Scenarios**:

1. **Given** I am an Enterprise account owner or team ADMIN viewing a card, **When** I assign the card to a team member, **Then** that team member can view and edit the card
2. **Given** I am a team MEMBER, **When** I view my assigned cards, **Then** I see only the cards that have been assigned to me
3. **Given** I am a team MEMBER with assigned cards, **When** I edit an assigned card, **Then** my changes are saved and reflected on the public card page
4. **Given** I am an Enterprise account owner, **When** I assign a card to multiple team members, **Then** all assigned members can access and manage that card
5. **Given** I am a team MEMBER, **When** I attempt to access a card that has not been assigned to me, **Then** I see an access denied message
6. **Given** I am an Enterprise account owner or team ADMIN, **When** I view all cards in the account, **Then** I can see which cards are assigned to which team members

---

### User Story 3 - Manage Team Member Roles and Permissions (Priority: P2)

An Enterprise account owner wants to manage team member roles and permissions. They can view all team members, change their roles (ADMIN or MEMBER), and remove team members from the account. Team ADMINs have elevated permissions compared to MEMBERs, such as the ability to assign cards and manage other team members.

**Why this priority**: Role management enables proper access control and delegation of responsibilities within Enterprise accounts. Important for security and organizational structure but not critical for initial MVP since basic card assignment can work with a single role initially.

**Independent Test**: Can be fully tested by logging in as an Enterprise account owner, viewing team members, changing a member's role from MEMBER to ADMIN, and verifying that the member gains additional permissions. Then removing a team member and verifying they lose access. This delivers value by enabling proper team governance.

**Acceptance Scenarios**:

1. **Given** I am an Enterprise account owner viewing team members, **When** I change a team member's role from MEMBER to ADMIN, **Then** that member gains permissions to assign cards and manage other team members
2. **Given** I am an Enterprise account owner, **When** I remove a team member from the account, **Then** that member loses access to all account resources and assigned cards
3. **Given** I am a team ADMIN, **When** I view team management options, **Then** I can assign cards to team members and view team member list, but cannot change account owner or remove the account owner
4. **Given** I am a team MEMBER, **When** I attempt to access team management features, **Then** I see an access denied message indicating these features are only available to account owners and ADMINs
5. **Given** I am an Enterprise account owner, **When** I remove a team member who has assigned cards, **Then** those cards become unassigned and can be reassigned to other team members

---

### User Story 4 - View Team Activity and Card Assignments (Priority: P3)

Team members want to see which cards are assigned to which team members and view team activity. Enterprise account owners and ADMINs can see an overview of all team members, their roles, and card assignments. This helps with coordination and understanding team workload distribution.

**Why this priority**: Visibility into team structure and assignments improves coordination and helps Enterprise accounts manage their team effectively. Useful for larger teams but not critical for initial MVP since basic assignment functionality can work without detailed views.

**Independent Test**: Can be fully tested by logging in as an Enterprise account owner, navigating to team overview, and verifying that all team members, their roles, and card assignments are displayed clearly. This delivers value by providing team visibility.

**Acceptance Scenarios**:

1. **Given** I am an Enterprise account owner or team ADMIN, **When** I view the team overview page, **Then** I see a list of all team members with their roles and the number of cards assigned to each
2. **Given** I am viewing team member details, **When** I click on a team member, **Then** I see which specific cards are assigned to that member
3. **Given** I am a team MEMBER, **When** I view my dashboard, **Then** I see only my assigned cards and cannot see other team members' assignments
4. **Given** I am an Enterprise account owner, **When** I view the team overview, **Then** I can see when each team member was added and their last activity timestamp

---

### Edge Cases

- What happens when an Enterprise account owner downgrades to Pro or Free plan? All team members should lose access, and card assignments should be preserved but inaccessible until the account is upgraded back to Enterprise
- How does the system handle team member invitations that expire or are never accepted? Invitations should expire after a reasonable time period (e.g., 7 days), and expired invitations should be clearly marked
- What happens when a team member's user account is deleted? Their team membership should be removed, and cards assigned to them should become unassigned
- How does the system handle assigning cards when a team member is removed? Cards assigned to removed members should become unassigned and available for reassignment
- What happens if an Enterprise account owner attempts to remove themselves from the team? The system should prevent this action or require transferring ownership first
- How does the system handle duplicate invitations to the same email address? The system should prevent duplicate active invitations and show existing invitation status
- What happens when a user is invited to multiple Enterprise accounts? The user should be able to accept multiple invitations and switch between accounts
- How does the system handle role changes for team members who have active card assignments? Role changes should take effect immediately, affecting permissions but not removing existing card assignments

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow Enterprise account owners to invite team members by email address
- **FR-002**: System MUST support two team member roles: ADMIN (can assign cards and manage team members) and MEMBER (can only manage assigned cards)
- **FR-003**: System MUST send invitation emails to invited team members with acceptance links
- **FR-004**: System MUST allow invited users to accept or decline team invitations
- **FR-005**: System MUST allow Enterprise account owners and team ADMINs to assign cards to team members
- **FR-006**: System MUST restrict team MEMBERs to only view and edit cards assigned to them
- **FR-007**: System MUST allow Enterprise account owners to change team member roles (ADMIN ↔ MEMBER)
- **FR-008**: System MUST allow Enterprise account owners to remove team members from the account
- **FR-009**: System MUST prevent Free and Pro account owners from accessing team management features
- **FR-010**: System MUST preserve card assignments when team members are removed, making cards unassigned
- **FR-011**: System MUST allow Enterprise account owners and team ADMINs to view all team members and their roles
- **FR-012**: System MUST allow Enterprise account owners and team ADMINs to view which cards are assigned to which team members
- **FR-013**: System MUST prevent team MEMBERs from accessing cards that are not assigned to them
- **FR-014**: System MUST prevent duplicate active invitations to the same email address for the same account
- **FR-015**: System MUST allow users to be members of multiple Enterprise accounts simultaneously
- **FR-016**: System MUST handle team member access removal when an Enterprise account is downgraded to Pro or Free plan
- **FR-017**: System MUST track when team members were added and their last activity timestamp

### Key Entities *(include if feature involves data)*

- **TeamMember**: Represents a user's membership in an Enterprise account team. Contains relationship to Account, relationship to User, role (ADMIN or MEMBER), invitation status (pending, accepted, declined), invitation token, invitation expiration date, joined date, and last activity timestamp.

- **CardAssignment**: Represents the assignment of a Card to a TeamMember. Contains relationship to Card, relationship to TeamMember, assigned date, and assigned by (who made the assignment).

- **Account** (Modified): Extended to support team relationships. Contains relationship to team members (OneToMany to TeamMember), indicating which account owns the team.

- **Card** (Modified): Extended to support team assignments. Contains relationship to card assignments (OneToMany to CardAssignment), allowing cards to be assigned to multiple team members.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Enterprise account owners can add team members and send invitations within 2 minutes per member
- **SC-002**: Team members can accept invitations and gain account access within 5 minutes of receiving the invitation email
- **SC-003**: Enterprise account owners and ADMINs can assign cards to team members within 30 seconds per assignment
- **SC-004**: Team MEMBERs can successfully access and edit their assigned cards without errors
- **SC-005**: System prevents unauthorized access attempts (MEMBERs accessing unassigned cards) with 100% accuracy
- **SC-006**: Role changes take effect immediately (within 5 seconds) for team members
- **SC-007**: Team member removal completes within 10 seconds and immediately revokes access
- **SC-008**: Enterprise accounts can support up to 50 team members per account without performance degradation
- **SC-009**: Card assignment operations complete successfully 99% of the time
- **SC-010**: Team management interface loads and displays team member information within 2 seconds

## Assumptions

- Team invitations expire after 7 days if not accepted (industry standard)
- Team members can belong to multiple Enterprise accounts simultaneously
- Enterprise account owners cannot be removed from their own account
- Card assignments are preserved when team members are removed (cards become unassigned)
- Team features are only available for Enterprise plan accounts
- Team ADMINs have elevated permissions but cannot remove account owners or change account plan
- Free and Pro account owners see upgrade prompts when attempting to access team features
- Email invitations are sent using the existing email infrastructure
- Team member authentication uses the existing user authentication system

## Dependencies

- Feature 002 (User Account & Authentication): Required for user accounts and authentication
- Feature 003 (Account Subscription): Required for Enterprise plan identification and plan-based access control
- Feature 005 (Digital Card Management): Required for card assignment functionality
- Email system: Required for sending team invitation emails
- Database: Required for storing team members, assignments, and relationships
