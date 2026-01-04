# Solution Architecture - Profile Management Features

## Overview Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         User Interface Layer                        │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────────┐       ┌──────────────────┐                   │
│  │  Profile Page    │       │  Navigation Bar  │                   │
│  │  /profile        │       │  (base.html.twig)│                   │
│  │                  │       └──────────────────┘                   │
│  │  [Change Pass]   │                                               │
│  │  [Update Email]  │                                               │
│  └────────┬─────────┘                                               │
│           │                                                          │
│           ├──────────────┬───────────────────────┐                 │
│           │              │                       │                  │
│           v              v                       v                  │
│  ┌────────────────┐  ┌──────────────┐  ┌───────────────────┐     │
│  │ change_        │  │ update_      │  │  index.html.twig  │     │
│  │ password.twig  │  │ email.twig   │  │  (modified)       │     │
│  └────────────────┘  └──────────────┘  └───────────────────┘     │
│                                                                      │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                         Controller Layer                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  ProfileController                                             │ │
│  │  @IsGranted('ROLE_USER')                                      │ │
│  │                                                                 │ │
│  │  • index()                  → Display profile                  │ │
│  │  • changePassword()         → Handle password change           │ │
│  │  • updateEmail()            → Handle email update              │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                                                                       │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                           Form Layer                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌────────────────────────┐         ┌──────────────────────────┐   │
│  │ ChangePasswordFormType │         │ UpdateEmailFormType      │   │
│  │                        │         │                          │   │
│  │ • currentPassword      │         │ • newEmail               │   │
│  │ • newPassword          │         │ • currentPassword        │   │
│  │   - min 8 chars        │         │   (verification)         │   │
│  │   - uppercase          │         │                          │   │
│  │   - lowercase          │         │ Validates:               │   │
│  │   - number             │         │ • Email format           │   │
│  │   - special char       │         │ • Password correct       │   │
│  └────────────────────────┘         │ • Email unique           │   │
│                                      └──────────────────────────┘   │
│                                                                       │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                         Service/Data Layer                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────────────┐  ┌────────────────────┐                  │
│  │ UserPasswordHasher   │  │ EntityManager      │                  │
│  │ Interface            │  │ (Doctrine ORM)     │                  │
│  │                      │  │                    │                  │
│  │ • isPasswordValid()  │  │ • flush()          │                  │
│  │ • hashPassword()     │  │ (save changes)     │                  │
│  └──────────────────────┘  └────────────────────┘                  │
│                                                                       │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │  UserRepository                                                 │ │
│  │  • findOneBy(['email' => $email])  → Check email uniqueness    │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                                                       │
└──────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                         Database Layer                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │  users table                                                    │ │
│  │  ┌──────┬─────────┬──────────┬────────────────────┬─────────┐ │ │
│  │  │ id   │ email   │ password │ isEmailVerified    │ ...     │ │ │
│  │  ├──────┼─────────┼──────────┼────────────────────┼─────────┤ │ │
│  │  │ 1    │ user@.. │ $hash... │ true/false         │ ...     │ │ │
│  │  └──────┴─────────┴──────────┴────────────────────┴─────────┘ │ │
│  └────────────────────────────────────────────────────────────────┘ │
│                                                                       │
└──────────────────────────────────────────────────────────────────┘
```

## Request Flow

### Change Password Flow

```
User → Profile Page → Click "Change Password"
                             ↓
                    GET /profile/change-password
                             ↓
                    Display Form (change_password.twig)
                             ↓
User fills form → POST /profile/change-password
                             ↓
                    ChangePasswordFormType validates
                             ↓
                    ProfileController::changePassword()
                             ↓
        ┌───────────────────┴───────────────────┐
        ↓                                       ↓
Current password valid?                 New password valid?
        │                                       │
        ├─ NO → Error message                  ├─ NO → Validation error
        │                                       │
        └─ YES ─────────────────────────────────┘
                             ↓
                    Hash new password
                             ↓
                    Update user password
                             ↓
                    Save to database
                             ↓
                    Redirect to /profile
                             ↓
                    Show success message
```

### Update Email Flow

```
User → Profile Page → Click "Update Email"
                             ↓
                    GET /profile/update-email
                             ↓
                    Display Form (update_email.twig)
                             ↓
User fills form → POST /profile/update-email
                             ↓
                    UpdateEmailFormType validates
                             ↓
                    ProfileController::updateEmail()
                             ↓
        ┌───────────────────┴───────────────────────────┐
        ↓                   ↓                           ↓
Current password    Email format       Email already
     valid?            valid?           in use?
        │                   │                           │
        ├─ NO               ├─ NO                       ├─ YES
        │   → Error         │   → Validation error     │   → Error
        │                   │                           │
        └─ YES ─────────────┴─ YES ─────────────────────┘
                             ↓
                    Update user email
                             ↓
                    Set isEmailVerified = false
                             ↓
                    Save to database
                             ↓
                    Redirect to /profile
                             ↓
                    Show success message
```

## Security Flow

```
┌─────────────────────────────────────────────────┐
│         Every Request to Profile Routes         │
└─────────────────┬───────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────┐
│  Check Authentication (#[IsGranted('ROLE_USER')])│
└─────────────────┬───────────────────────────────┘
                  │
          ┌───────┴────────┐
          ↓                ↓
       NOT AUTH         AUTHENTICATED
          │                │
          ↓                ↓
   Redirect to      Allow Access
    Login Page            │
                          ↓
              ┌───────────┴────────────┐
              ↓                        ↓
      Form Submission         Password Operations
              │                        │
              ↓                        ↓
      CSRF Token Check      Verify Current Password
              │                        │
              ↓                        ↓
      Validate Input        Hash New Password
              │                        │
              ↓                        ↓
      Process Request       Update Database
```

## File Structure

```
hermio/
├── app/
│   ├── src/
│   │   ├── Controller/
│   │   │   └── ProfileController.php ........... (Modified) +80 lines
│   │   └── Form/
│   │       ├── ChangePasswordFormType.php ...... (Created)  ~70 lines
│   │       ├── UpdateEmailFormType.php ......... (Created)  ~45 lines
│   │       └── RegistrationFormType.php ........ (Modified) ~1 line
│   └── templates/
│       └── profile/
│           ├── index.html.twig ................. (Modified) ~2 lines
│           ├── change_password.html.twig ....... (Created)  ~35 lines
│           └── update_email.html.twig .......... (Created)  ~35 lines
├── CHANGELOG_PROFILE.md ......................... (Created)  ~60 lines
├── PROFILE_FEATURES.md .......................... (Created)  ~150 lines
├── TEST_PLAN.md ................................. (Created)  ~180 lines
├── SUMMARY.md ................................... (Created)  ~180 lines
└── ARCHITECTURE.md .............................. (Created)  This file
```

## Routes Added

```
Route Name                     | Path                        | Methods
-------------------------------|-----------------------------|---------
app_profile_change_password    | /profile/change-password    | GET|POST
app_profile_update_email       | /profile/update-email       | GET|POST
```

## Key Design Decisions

1. **Minimal Changes**: Only added what was necessary to fix the broken links
2. **Consistency**: Followed existing code patterns and styling
3. **Security First**: All operations require authentication and password verification
4. **User Experience**: Clear error messages and success feedback
5. **Validation**: Strong client and server-side validation
6. **Documentation**: Comprehensive docs for future maintenance

## Technology Stack

- **Framework**: Symfony 8.0
- **Forms**: Symfony Form Component
- **Security**: Symfony Security Bundle
- **Database**: Doctrine ORM
- **Templates**: Twig
- **Validation**: Symfony Validator

## Success Metrics

✅ Broken links fixed
✅ Security requirements met
✅ Code quality maintained
✅ Documentation complete
✅ No new dependencies
✅ No database changes
✅ Minimal code changes
✅ Follows existing patterns
