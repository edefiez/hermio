# Form Contracts: Multi-user (Enterprise)

**Feature**: 007-multi-user  
**Date**: December 10, 2025  
**Type**: Symfony Forms (Twig-based web application)

## Overview

This document defines the form contracts for the multi-user team collaboration system. Forms include team member invitations, role management, and card assignments. All forms use Symfony Form component with Twig rendering.

## Form Types

### 1. TeamInvitationFormType

**Purpose**: Form for inviting team members to Enterprise account

**Class**: `App\Form\TeamInvitationFormType`

**Fields**:

| Field Name | Type | Required | Validation | Description |
|------------|------|----------|------------|-------------|
| `email` | EmailType | Yes | Email format, max 180 chars | Email address of team member to invite |
| `role` | ChoiceType | Yes | Choice: admin, member | Team member role (ADMIN or MEMBER) |

**Form Configuration**:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'csrf_protection' => true,
        'csrf_field_name' => '_token',
        'csrf_token_id' => 'team_invite',
    ]);
}
```

**Validation Rules**:
- `email`: Required, valid email format, max 180 characters, must not be duplicate invitation for same account
- `role`: Required, must be TeamRole enum value (ADMIN or MEMBER)

**Form Rendering** (Twig):
```twig
{{ form_start(form, {'action': path('app_team_invite'), 'method': 'POST'}) }}
    {{ form_row(form.email) }}
    {{ form_row(form.role) }}
    {{ form_row(form._token) }}
    <button type="submit">{{ 'team.invite.submit'|trans }}</button>
{{ form_end(form) }}
```

---

### 2. TeamMemberRoleFormType

**Purpose**: Form for changing team member role

**Class**: `App\Form\TeamMemberRoleFormType`

**Fields**:

| Field Name | Type | Required | Validation | Description |
|------------|------|----------|------------|-------------|
| `role` | ChoiceType | Yes | Choice: admin, member | New team member role |

**Form Configuration**:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'csrf_protection' => true,
        'csrf_field_name' => '_token',
        'csrf_token_id' => 'team_role',
    ]);
}
```

**Validation Rules**:
- `role`: Required, must be TeamRole enum value (ADMIN or MEMBER)

**Form Rendering** (Twig):
```twig
{{ form_start(form, {'action': path('app_team_change_role', {'id': teamMember.id}), 'method': 'POST'}) }}
    {{ form_row(form.role) }}
    {{ form_row(form._token) }}
    <button type="submit">{{ 'team.role.change.submit'|trans }}</button>
{{ form_end(form) }}
```

---

### 3. CardAssignmentFormType

**Purpose**: Form for assigning cards to team members

**Class**: `App\Form\CardAssignmentFormType`

**Fields**:

| Field Name | Type | Required | Validation | Description |
|------------|------|----------|------------|-------------|
| `teamMembers` | EntityType | Yes | Multiple selection, TeamMember entities | Team members to assign card to |

**Form Configuration**:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'class' => TeamMember::class,
        'multiple' => true,
        'expanded' => false,
        'choice_label' => 'email',
        'query_builder' => function (TeamMemberRepository $er) use ($account) {
            return $er->createQueryBuilder('tm')
                ->where('tm.account = :account')
                ->andWhere('tm.invitationStatus = :status')
                ->setParameter('account', $account)
                ->setParameter('status', 'accepted');
        },
        'csrf_protection' => true,
        'csrf_field_name' => '_token',
        'csrf_token_id' => 'card_assign',
    ]);
}
```

**Validation Rules**:
- `teamMembers`: Required, must be array of valid TeamMember IDs, all team members must belong to same account as card

**Form Rendering** (Twig):
```twig
{{ form_start(form, {'action': path('app_card_assign', {'id': card.id}), 'method': 'POST'}) }}
    {{ form_row(form.teamMembers) }}
    {{ form_row(form._token) }}
    <button type="submit">{{ 'card.assign.submit'|trans }}</button>
{{ form_end(form) }}
```

---

## Field Specifications

### email Field (TeamInvitationFormType)

**Type**: `EmailType`  
**Widget**: `<input type="email">`

**Attributes**:
- `placeholder`: "team.member.email@example.com"
- `maxlength`: 180
- `required`: true

**Validation**:
- Format: Valid email address
- Length: Maximum 180 characters
- Required: Yes
- Business Rule: Must not be duplicate invitation for same account

**Example Values**:
- Valid: `john@example.com`, `team.member@company.com`
- Invalid: `invalid-email`, `user@`, `@domain.com`

---

### role Field (TeamInvitationFormType, TeamMemberRoleFormType)

**Type**: `ChoiceType`  
**Widget**: `<select>` dropdown

**Choices**:
```php
[
    TeamRole::ADMIN->value => 'team.role.admin',
    TeamRole::MEMBER->value => 'team.role.member',
]
```

**Validation**:
- Must be TeamRole enum value (admin or member)
- Required: Yes

**Default Value**: `member` (for invitations)

**Display Labels**:
- `admin`: "Administrator" (can assign cards, manage team members)
- `member`: "Member" (can only access assigned cards)

---

### teamMembers Field (CardAssignmentFormType)

**Type**: `EntityType`  
**Widget**: `<select multiple>` dropdown (or checkboxes if expanded)

**Entity Options**:
- `class`: `TeamMember::class`
- `multiple`: true
- `expanded`: false (dropdown) or true (checkboxes)
- `choice_label`: `email` (display team member email)

**Query Builder**:
- Filters to team members of same account as card
- Only includes accepted team members (`invitationStatus = 'accepted'`)
- Excludes account owner (owner has implicit access)

**Validation**:
- Required: Yes (must select at least one team member)
- All selected team members must belong to same account as card
- All selected team members must have `invitationStatus = 'accepted'`

**Example Selection**:
- Valid: `[1, 2, 3]` (array of team member IDs)
- Invalid: `[]` (empty array), `[999]` (non-existent team member)

---

## Form Validation

### Client-Side Validation

- HTML5 validation attributes on form fields
- Email format validation via `type="email"` attribute
- Required field validation via `required` attribute
- Multiple selection validation via JavaScript (at least one selection)

### Server-Side Validation

- Symfony Validator constraints on form fields
- Entity-level validation (TeamMember entity)
- Custom validation for duplicate invitations
- Custom validation for team member account matching

### Validation Error Messages

**Translation Keys**:
- `team.invite.email.required`: "Email address is required"
- `team.invite.email.invalid`: "Invalid email address"
- `team.invite.email.duplicate`: "This user has already been invited to this team"
- `team.invite.role.required`: "Role is required"
- `team.invite.role.invalid`: "Invalid role"
- `card.assign.team_members.required`: "At least one team member must be selected"
- `card.assign.team_members.invalid`: "Invalid team member selection"

---

## Form Rendering

### Team Management Page

**Template**: `team/index.html.twig`

**Layout**:
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ 'team.title'|trans }}{% endblock %}

{% block body %}
    <div class="team-container">
        <h1>{{ 'team.title'|trans }}</h1>
        
        {% if not isEnterprise %}
            <div class="alert alert-warning">
                {{ 'team.access_denied'|trans }}
                <a href="{{ path('app_subscription_manage') }}">{{ 'team.upgrade'|trans }}</a>
            </div>
        {% else %}
            {% if canManageTeam %}
                <div class="invitation-section">
                    <h2>{{ 'team.invite.title'|trans }}</h2>
                    {{ form_start(invitationForm) }}
                        {{ form_row(invitationForm.email) }}
                        {{ form_row(invitationForm.role) }}
                        {{ form_row(invitationForm._token) }}
                        <button type="submit" class="btn btn-primary">{{ 'team.invite.submit'|trans }}</button>
                    {{ form_end(invitationForm) }}
                </div>
            {% endif %}
            
            <div class="team-members-section">
                <h2>{{ 'team.members.title'|trans }}</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ 'team.members.email'|trans }}</th>
                            <th>{{ 'team.members.role'|trans }}</th>
                            <th>{{ 'team.members.status'|trans }}</th>
                            <th>{{ 'team.members.joined'|trans }}</th>
                            {% if canManageTeam %}
                                <th>{{ 'team.members.actions'|trans }}</th>
                            {% endif %}
                        </tr>
                    </thead>
                    <tbody>
                        {% for member in teamMembers %}
                            <tr>
                                <td>{{ member.email }}</td>
                                <td>{{ member.role.displayName|trans }}</td>
                                <td>{{ member.invitationStatus|trans }}</td>
                                <td>{{ member.joinedAt|date('Y-m-d H:i')|default('N/A') }}</td>
                                {% if canManageTeam %}
                                    <td>
                                        {% if member.invitationStatus == 'accepted' %}
                                            {{ form_start(roleForm, {'action': path('app_team_change_role', {'id': member.id})}) }}
                                                {{ form_row(roleForm.role) }}
                                                {{ form_row(roleForm._token) }}
                                                <button type="submit">{{ 'team.role.change'|trans }}</button>
                                            {{ form_end(roleForm) }}
                                            <a href="{{ path('app_team_remove', {'id': member.id}) }}" 
                                               onclick="return confirm('{{ 'team.remove.confirm'|trans }}')">
                                                {{ 'team.remove'|trans }}
                                            </a>
                                        {% endif %}
                                    </td>
                                {% endif %}
                            </tr>
                        {% endfor %}
                    </tbody>
                </table>
            </div>
        {% endif %}
    </div>
{% endblock %}
```

---

### Invitation Acceptance Page

**Template**: `team/accept_invitation.html.twig`

**Layout**:
```twig
{% extends 'base.html.twig' %}

{% block title %}{{ 'team.invitation.title'|trans }}{% endblock %}

{% block body %}
    <div class="invitation-container">
        {% if isExpired %}
            <div class="alert alert-danger">
                {{ 'team.invitation.expired'|trans }}
            </div>
        {% elseif teamMember.invitationStatus != 'pending' %}
            <div class="alert alert-warning">
                {{ 'team.invitation.already_processed'|trans }}
            </div>
        {% else %}
            <h1>{{ 'team.invitation.title'|trans }}</h1>
            <p>{{ 'team.invitation.message'|trans({'%account%': account.user.email}) }}</p>
            <p>{{ 'team.invitation.role'|trans({'%role%': teamMember.role.displayName|trans}) }}</p>
            
            {% if not isLoggedIn %}
                <div class="alert alert-info">
                    {{ 'team.invitation.login_required'|trans }}
                    <a href="{{ path('app_login') }}">{{ 'team.invitation.login'|trans }}</a>
                </div>
            {% elseif userEmail != teamMember.email %}
                <div class="alert alert-warning">
                    {{ 'team.invitation.email_mismatch'|trans({'%expected%': teamMember.email, '%actual%': userEmail}) }}
                </div>
            {% else %}
                <form method="post" action="{{ path('app_team_accept', {'token': teamMember.invitationToken}) }}">
                    <input type="hidden" name="_token" value="{{ csrf_token('team_accept') }}">
                    <button type="submit" name="action" value="accept" class="btn btn-primary">
                        {{ 'team.invitation.accept'|trans }}
                    </button>
                    <button type="submit" name="action" value="decline" class="btn btn-secondary">
                        {{ 'team.invitation.decline'|trans }}
                    </button>
                </form>
            {% endif %}
        {% endif %}
    </div>
{% endblock %}
```

---

### Card Assignment Form (in Card Edit Page)

**Template**: `card/edit.html.twig` (modified)

**Additional Section**:
```twig
{% if isEnterprise and canAssignCards %}
    <div class="card-assignment-section">
        <h3>{{ 'card.assignments.title'|trans }}</h3>
        <p>{{ 'card.assignments.description'|trans }}</p>
        
        {% if card.assignments|length > 0 %}
            <div class="current-assignments">
                <h4>{{ 'card.assignments.current'|trans }}</h4>
                <ul>
                    {% for assignment in card.assignments %}
                        <li>
                            {{ assignment.teamMember.email }} ({{ assignment.teamMember.role.displayName|trans }})
                            {% if canAssignCards %}
                                <a href="{{ path('app_card_unassign', {'id': card.id, 'teamMemberId': assignment.teamMember.id}) }}"
                                   onclick="return confirm('{{ 'card.unassign.confirm'|trans }}')">
                                    {{ 'card.unassign'|trans }}
                                </a>
                            {% endif %}
                        </li>
                    {% endfor %}
                </ul>
            </div>
        {% endif %}
        
        {{ form_start(assignmentForm) }}
            {{ form_row(assignmentForm.teamMembers) }}
            {{ form_row(assignmentForm._token) }}
            <button type="submit" class="btn btn-primary">{{ 'card.assign.submit'|trans }}</button>
        {{ form_end(assignmentForm) }}
    </div>
{% endif %}
```

---

## Form Data Flow

### Inviting Team Member

1. User submits `TeamInvitationFormType` form
2. Form validated (email format, role, duplicate check)
3. `TeamMember` entity created with status 'pending'
4. Invitation token generated and stored
5. Expiration date set (7 days from now)
6. Invitation email sent via `TeamInvitationService`
7. Redirect to team management page with success message

### Accepting Invitation

1. User clicks invitation link (GET `/team/accept/{token}`)
2. Token validated (exists, not expired, status is 'pending')
3. If user logged in, email matched, show acceptance form
4. User submits acceptance (POST `/team/accept/{token}`)
5. `TeamMember` status updated to 'accepted'
6. User linked to `TeamMember.user`
7. `joinedAt` timestamp set
8. Invitation token cleared
9. Redirect to team page or dashboard

### Assigning Card

1. User submits `CardAssignmentFormType` form
2. Form validated (team members selected, belong to same account)
3. `CardAssignment` entities created for each selected team member
4. `assignedAt` timestamp set
5. `assignedBy` set to current user
6. Redirect to card management page with success message

---

## Notes

- All forms use Symfony Form component with Twig rendering
- CSRF protection enabled on all forms
- Form validation happens at both client and server side
- Team member selection filtered to accepted members only
- Account owner excluded from team member selection (has implicit access)
- Duplicate invitations prevented at form validation level
- Email matching enforced for invitation acceptance
- All forms follow Symfony conventions and return appropriate HTTP status codes

