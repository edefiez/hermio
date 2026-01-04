# Changelog - Profile Management Features

## 2026-01-04 - Profile and Password Management

### Added

#### Change Password Feature
- **Route**: `/profile/change-password` (named: `app_profile_change_password`)
- **Form**: `ChangePasswordFormType` - Handles password change with current password verification
- **Template**: `templates/profile/change_password.html.twig`
- **Validations**:
  - Current password verification
  - New password must be at least 8 characters
  - Must contain uppercase, lowercase, number, and special character
  - Password confirmation must match

#### Update Email Feature
- **Route**: `/profile/update-email` (named: `app_profile_update_email`)
- **Form**: `UpdateEmailFormType` - Handles email updates with password verification
- **Template**: `templates/profile/update_email.html.twig`
- **Validations**:
  - Current password verification
  - Valid email format
  - Email uniqueness check
  - Email verification status is reset after update

### Modified
- **ProfileController**: Added `changePassword()` and `updateEmail()` methods
- **templates/profile/index.html.twig**: Updated button links to use the new routes instead of `href="#"`

### Security Features
- Both features require user authentication (`IsGranted('ROLE_USER')`)
- Password verification required for both operations
- Protection against email address duplication
- Automatic email verification status reset when email is changed

### Usage

#### Change Password
1. Navigate to `/profile`
2. Click "Change Password" button
3. Enter current password and new password (twice)
4. Submit the form
5. On success, redirect to profile with success message

#### Update Email
1. Navigate to `/profile`
2. Click "Update Email" button
3. Enter new email address and current password
4. Submit the form
5. On success, redirect to profile with success message
6. Email verification status will be reset and user will need to verify the new email

### Implementation Notes
- Password hashing uses Symfony's `UserPasswordHasherInterface`
- Email uniqueness is checked against the `UserRepository`
- Flash messages provide user feedback for success and error cases
- Form validation follows the same patterns as the registration form
