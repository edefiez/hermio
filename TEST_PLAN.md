# Test Plan - Profile Management Features

## Manual Testing Guide

This document provides step-by-step instructions for manually testing the new profile management features.

## Prerequisites
- Application is running (via `make up` or `docker compose up`)
- At least one user account exists
- You are logged into the application

## Test Cases

### 1. Change Password - Happy Path

**Steps:**
1. Navigate to `/profile`
2. Click the "Change Password" button
3. Enter your current password in the "Current Password" field
4. Enter a new valid password (e.g., "NewPass123!")
5. Confirm the new password in "Confirm New Password" field
6. Click "Change Password"

**Expected Result:**
- ✓ Redirected to `/profile`
- ✓ Success message: "Your password has been changed successfully."
- ✓ Can log out and log back in with the new password

---

### 2. Change Password - Wrong Current Password

**Steps:**
1. Navigate to `/profile/change-password`
2. Enter an incorrect password in "Current Password"
3. Enter a valid new password
4. Click "Change Password"

**Expected Result:**
- ✓ Stay on change password page
- ✓ Error message: "Current password is incorrect."

---

### 3. Change Password - Weak New Password

**Steps:**
1. Navigate to `/profile/change-password`
2. Enter correct current password
3. Enter a weak password (e.g., "weak") in new password fields

**Expected Result:**
- ✓ Validation error appears
- ✓ Error message indicates password requirements not met

---

### 4. Change Password - Password Mismatch

**Steps:**
1. Navigate to `/profile/change-password`
2. Enter correct current password
3. Enter "NewPass123!" in "New Password"
4. Enter "DifferentPass123!" in "Confirm New Password"

**Expected Result:**
- ✓ Validation error appears
- ✓ Error message: "The password fields must match."

---

### 5. Update Email - Happy Path

**Steps:**
1. Navigate to `/profile`
2. Click the "Update Email" button
3. Enter a new valid email address (e.g., "newemail@example.com")
4. Enter your current password
5. Click "Update Email"

**Expected Result:**
- ✓ Redirected to `/profile`
- ✓ Success message: "Your email has been updated successfully. Please verify your new email address."
- ✓ Email is updated on profile page
- ✓ Email verification status shows "Not Verified"

---

### 6. Update Email - Wrong Current Password

**Steps:**
1. Navigate to `/profile/update-email`
2. Enter a valid new email
3. Enter an incorrect password

**Expected Result:**
- ✓ Stay on update email page
- ✓ Error message: "Current password is incorrect."

---

### 7. Update Email - Duplicate Email

**Steps:**
1. Create or identify another user account with email "existing@example.com"
2. Navigate to `/profile/update-email`
3. Try to change your email to "existing@example.com"
4. Enter correct password

**Expected Result:**
- ✓ Stay on update email page
- ✓ Error message: "This email address is already in use."

---

### 8. Update Email - Invalid Email Format

**Steps:**
1. Navigate to `/profile/update-email`
2. Enter an invalid email (e.g., "notanemail")
3. Enter correct password

**Expected Result:**
- ✓ Validation error appears
- ✓ Error message about invalid email format

---

### 9. Navigation Tests

**Test A: From Profile to Change Password and Back**
1. Navigate to `/profile`
2. Click "Change Password"
3. Click "Back to Profile" link

**Expected Result:**
- ✓ Successfully navigate to change password page
- ✓ Successfully return to profile page

**Test B: From Profile to Update Email and Back**
1. Navigate to `/profile`
2. Click "Update Email"
3. Click "Back to Profile" link

**Expected Result:**
- ✓ Successfully navigate to update email page
- ✓ Successfully return to profile page

---

### 10. Authentication Tests

**Test: Access Without Authentication**
1. Log out of the application
2. Try to access `/profile/change-password` directly
3. Try to access `/profile/update-email` directly

**Expected Result:**
- ✓ Redirected to login page
- ✓ Cannot access pages without authentication

---

## Password Requirements

Valid passwords must meet these criteria:
- Minimum 8 characters
- At least one uppercase letter (A-Z)
- At least one lowercase letter (a-z)
- At least one digit (0-9)
- At least one special character (@$!%*?&)

**Valid Examples:**
- "Password123!"
- "Secure@Pass1"
- "MyEmail2024$"

**Invalid Examples:**
- "password" (no uppercase, no number, no special char)
- "PASSWORD123" (no lowercase, no special char)
- "Pass1!" (too short, less than 8 characters)
- "Password123" (no special character)

---

## Testing Checklist

Before marking this feature as complete, ensure:

- [ ] All happy path tests pass
- [ ] All error cases are handled correctly
- [ ] Validation messages are clear and helpful
- [ ] Navigation works correctly (buttons and links)
- [ ] Forms have proper styling consistent with the rest of the application
- [ ] Success and error flash messages appear and disappear correctly
- [ ] Password change actually updates the password (can log in with new password)
- [ ] Email change actually updates the email and resets verification status
- [ ] No console errors in browser
- [ ] No PHP errors in logs
- [ ] Authentication requirements are enforced

---

## Automated Testing (Future)

Once PHPUnit is set up, create tests for:
- `ProfileController::changePassword()`
- `ProfileController::updateEmail()`
- `ChangePasswordFormType` validation
- `UpdateEmailFormType` validation
- Integration tests for full user flows
