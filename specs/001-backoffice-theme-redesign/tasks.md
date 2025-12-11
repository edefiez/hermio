# Tasks: Back-Office Theme Redesign

**Input**: Design documents from `/specs/001-backoffice-theme-redesign/`  
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/design-tokens.scss ✅, quickstart.md ✅

**Feature Branch**: `001-backoffice-theme-redesign`  
**Organization**: Tasks are grouped by user story to enable independent implementation and testing

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1-US5)
- All file paths are absolute from `/home/runner/work/hermio/hermio`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and asset pipeline configuration

- [ ] T001 Verify Bootstrap 5.3.8 and FontAwesome 7.1.0 are installed in app/package.json
- [ ] T002 Create directory structure for new SCSS files in app/assets/styles/
- [ ] T003 Create directory structure for new components in app/templates/components/
- [ ] T004 Create directory for auth illustration in app/assets/images/

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core design system that MUST be complete before ANY user story implementation

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T005 Create design tokens file app/assets/styles/_design-tokens.scss from contracts/design-tokens.scss
- [ ] T006 Update app/assets/styles/bootstrap-custom.scss to import _design-tokens.scss before Bootstrap
- [ ] T007 Create auth entry point app/assets/auth.js for authentication pages
- [ ] T008 Update app/webpack.config.js to add 'auth' entry point with Encore.addEntry()
- [ ] T009 Create app/assets/styles/auth.scss for authentication page styles
- [ ] T010 Build assets with npm run dev to verify Webpack configuration works

**Checkpoint**: Foundation ready - design tokens available, webpack configured, user story implementation can begin

---

## Phase 3: User Story 1 - Modern Login Experience (Priority: P1) 🎯 MVP

**Goal**: Transform the login page with a modern two-column layout (form + illustration) that is responsive and accessible

**Independent Test**: Navigate to /login, verify two-column layout on desktop (≥768px), stacked layout on mobile (<768px), form submission works, and keyboard navigation functions properly

### Implementation for User Story 1

- [ ] T011 [P] [US1] Create business card illustration SVG in app/assets/images/auth-illustration.svg with QR code for https://hermio.cards
- [ ] T012 [P] [US1] Create auth layout component SCSS in app/assets/styles/components/_auth-layout.scss
- [ ] T013 [P] [US1] Create illustration component SCSS in app/assets/styles/components/_illustration.scss
- [ ] T014 [US1] Import component SCSS files into app/assets/styles/auth.scss
- [ ] T015 [US1] Create auth base template in app/templates/auth/_base.html.twig with two-column structure
- [ ] T016 [US1] Update login template app/templates/security/login.html.twig to extend auth/_base.html.twig
- [ ] T017 [US1] Add login form to left column with Bootstrap form components in app/templates/security/login.html.twig
- [ ] T018 [US1] Add illustration to right column in app/templates/auth/_base.html.twig
- [ ] T019 [US1] Add responsive breakpoints for mobile stacking in app/assets/styles/components/_auth-layout.scss
- [ ] T020 [US1] Implement focus indicators for form fields in app/assets/styles/components/_auth-layout.scss
- [ ] T021 [US1] Add ARIA landmarks (role="main", role="complementary") to app/templates/auth/_base.html.twig
- [ ] T022 [US1] Add translation keys for login page to app/translations/messages.en.yaml (auth.login.*)
- [ ] T023 [US1] Add translation keys for login page to app/translations/messages.fr.yaml (auth.login.*)
- [ ] T024 [US1] Build assets with npm run dev and test login page at /login on desktop and mobile viewports

**Checkpoint**: Login page fully functional with modern design, responsive layout, and accessibility features

---

## Phase 4: User Story 2 - Streamlined Registration Flow (Priority: P2)

**Goal**: Update registration page to match login page design with consistent two-column layout

**Independent Test**: Navigate to /register, verify layout matches login page, form validation works, and responsive design functions on mobile and desktop

### Implementation for User Story 2

- [ ] T025 [US2] Update registration template app/templates/security/register.html.twig to extend auth/_base.html.twig
- [ ] T026 [US2] Add registration form to left column with Bootstrap form components in app/templates/security/register.html.twig
- [ ] T027 [US2] Add form validation error styling with Bootstrap feedback classes in app/templates/security/register.html.twig
- [ ] T028 [US2] Add ARIA attributes for error messages (aria-describedby) in app/templates/security/register.html.twig
- [ ] T029 [US2] Add translation keys for registration page to app/translations/messages.en.yaml (auth.register.*)
- [ ] T030 [US2] Add translation keys for registration page to app/translations/messages.fr.yaml (auth.register.*)
- [ ] T031 [US2] Test registration page at /register on desktop and mobile viewports

**Checkpoint**: Registration page matches login design, fully functional with consistent UX

---

## Phase 5: User Story 3 - Modern Dashboard Layout (Priority: P3)

**Goal**: Redesign dashboard pages with card-based layout, remove sidebar navigation, implement responsive grid

**Independent Test**: Log in and navigate to /account, verify card-based layout displays user data, no sidebar navigation appears, and cards stack vertically on mobile

### Implementation for User Story 3

- [ ] T032 [P] [US3] Create dashboard styles file app/assets/styles/dashboard.scss
- [ ] T033 [P] [US3] Create dashboard card component SCSS in app/assets/styles/components/_dashboard-card.scss
- [ ] T034 [US3] Import dashboard.scss and component files into app/assets/app.js entry point
- [ ] T035 [US3] Create reusable dashboard card component in app/templates/components/_dashboard_card.html.twig
- [ ] T036 [US3] Create reusable page header component in app/templates/components/_page_header.html.twig
- [ ] T037 [US3] Update base template app/templates/base.html.twig to remove sidebar navigation for dashboard pages
- [ ] T038 [US3] Update account dashboard template app/templates/account/index.html.twig with card-based layout
- [ ] T039 [US3] Add Bootstrap grid (row g-4, col-12 col-md-6 col-xl-4) to app/templates/account/index.html.twig
- [ ] T040 [US3] Use _dashboard_card.html.twig component for account overview in app/templates/account/index.html.twig
- [ ] T041 [US3] Use _page_header.html.twig component for page title in app/templates/account/index.html.twig
- [ ] T042 [US3] Update plan page template app/templates/account/my_plan.html.twig with card-based layout
- [ ] T043 [US3] Use _dashboard_card.html.twig component for plan information in app/templates/account/my_plan.html.twig
- [ ] T044 [US3] Update profile page template app/templates/profile/index.html.twig with card-based layout
- [ ] T045 [US3] Use _dashboard_card.html.twig component for profile information in app/templates/profile/index.html.twig
- [ ] T046 [US3] Add translation keys for dashboard components to app/translations/messages.en.yaml (dashboard.*)
- [ ] T047 [US3] Add translation keys for dashboard components to app/translations/messages.fr.yaml (dashboard.*)
- [ ] T048 [US3] Build assets with npm run dev and test all dashboard pages (/account, /account/my-plan, /profile)

**Checkpoint**: Dashboard pages fully redesigned with card layout, no sidebar, responsive grid working

---

## Phase 6: User Story 4 - Unified Visual Design System (Priority: P4)

**Goal**: Ensure consistent visual styling across all pages through design token usage and component standardization

**Independent Test**: Review all SCSS files to confirm design tokens are used instead of hardcoded values, navigate through all pages to verify visual consistency

### Implementation for User Story 4

- [ ] T049 [P] [US4] Audit app/assets/styles/auth.scss for hardcoded colors and replace with design tokens
- [ ] T050 [P] [US4] Audit app/assets/styles/dashboard.scss for hardcoded colors and replace with design tokens
- [ ] T051 [P] [US4] Audit app/assets/styles/components/_auth-layout.scss for hardcoded values and replace with tokens
- [ ] T052 [P] [US4] Audit app/assets/styles/components/_dashboard-card.scss for hardcoded values and replace with tokens
- [ ] T053 [US4] Verify all spacing uses $hermio-space-* variables across all SCSS files
- [ ] T054 [US4] Verify all typography uses $hermio-text-* and $hermio-font-* variables across all SCSS files
- [ ] T055 [US4] Verify all border-radius uses $hermio-radius-* variables across all SCSS files
- [ ] T056 [US4] Verify all shadows use $hermio-shadow-* variables across all SCSS files
- [ ] T057 [US4] Add component state variants (hover, focus, active) using design tokens in all component SCSS files
- [ ] T058 [US4] Test visual consistency by navigating login → register → dashboard → profile pages
- [ ] T059 [US4] Build production assets with npm run build and verify CSS bundle size increase is <50KB gzipped

**Checkpoint**: All pages use design tokens consistently, visual design is unified across the back-office

---

## Phase 7: User Story 5 - Accessible and Inclusive Interface (Priority: P5)

**Goal**: Ensure all pages meet WCAG 2.1 Level AA accessibility standards with proper ARIA, keyboard navigation, and contrast ratios

**Independent Test**: Use keyboard-only navigation to access all features, run axe DevTools accessibility audit, verify with screen reader (NVDA/VoiceOver)

### Accessibility Implementation for User Story 5

- [ ] T060 [P] [US5] Add visible focus indicators to all form inputs in app/assets/styles/components/_auth-layout.scss
- [ ] T061 [P] [US5] Add visible focus indicators to all interactive elements in app/assets/styles/components/_dashboard-card.scss
- [ ] T062 [US5] Verify all form labels are properly associated with inputs in app/templates/security/login.html.twig
- [ ] T063 [US5] Verify all form labels are properly associated with inputs in app/templates/security/register.html.twig
- [ ] T064 [US5] Add aria-describedby to form validation errors in app/templates/security/login.html.twig
- [ ] T065 [US5] Add aria-describedby to form validation errors in app/templates/security/register.html.twig
- [ ] T066 [US5] Verify ARIA landmarks in app/templates/auth/_base.html.twig (main, complementary)
- [ ] T067 [US5] Add ARIA landmarks to dashboard templates (main, navigation) in app/templates/base.html.twig
- [ ] T068 [US5] Test keyboard navigation on login page (Tab order, Enter to submit, Esc to close modals)
- [ ] T069 [US5] Test keyboard navigation on registration page (Tab order, Enter to submit)
- [ ] T070 [US5] Test keyboard navigation on dashboard pages (Tab order for cards, links, buttons)
- [ ] T071 [US5] Verify color contrast ratios meet 4.5:1 for normal text using Chrome DevTools
- [ ] T072 [US5] Verify color contrast ratios meet 3:1 for large text using Chrome DevTools
- [ ] T073 [US5] Add skip navigation link to app/templates/base.html.twig for keyboard users
- [ ] T074 [US5] Test with screen reader (NVDA on Windows or VoiceOver on Mac) on all pages
- [ ] T075 [US5] Run axe DevTools accessibility audit on /login and fix any critical issues
- [ ] T076 [US5] Run axe DevTools accessibility audit on /register and fix any critical issues
- [ ] T077 [US5] Run axe DevTools accessibility audit on /account and fix any critical issues
- [ ] T078 [US5] Test at 200% browser zoom and verify layout remains functional
- [ ] T079 [US5] Verify touch targets are ≥44px on mobile viewports for all buttons and links

**Checkpoint**: All pages meet WCAG 2.1 Level AA standards, fully keyboard accessible, screen reader compatible

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Final refinements, testing, validation, and documentation

- [ ] T080 [P] Test login page in Chrome, Firefox, Safari, and Edge browsers
- [ ] T081 [P] Test registration page in Chrome, Firefox, Safari, and Edge browsers
- [ ] T082 [P] Test dashboard pages in Chrome, Firefox, Safari, and Edge browsers
- [ ] T083 Test responsive design at 320px, 576px, 768px, 992px, 1200px, 1400px, and 2560px viewports
- [ ] T084 Test login page with JavaScript disabled (progressive enhancement)
- [ ] T085 Test registration page with JavaScript disabled
- [ ] T086 Verify CSRF tokens are properly integrated in all forms
- [ ] T087 Test French translations by switching locale to FR and verifying all text displays correctly
- [ ] T088 Test English translations by switching locale to EN and verifying all text displays correctly
- [ ] T089 Verify illustration SVG loads correctly and QR code is scannable to https://hermio.cards
- [ ] T090 Test empty states on dashboard when user has no cards or minimal data
- [ ] T091 Test form validation with missing required fields on login page
- [ ] T092 Test form validation with invalid email format on registration page
- [ ] T093 Test extremely long text in French translations to ensure layout doesn't break
- [ ] T094 Measure page load time for /login and verify <2 seconds on standard broadband
- [ ] T095 Measure page load time for /account and verify <2 seconds on standard broadband
- [ ] T096 Run Lighthouse performance audit on login page (target score >90)
- [ ] T097 Run Lighthouse performance audit on dashboard page (target score >90)
- [ ] T098 Verify public front-site styling is not affected by back-office changes
- [ ] T099 Document component usage in app/templates/components/README.md (how to use _dashboard_card.html.twig and _page_header.html.twig)
- [ ] T100 Build production assets with npm run build and commit to repository

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational phase completion
- **User Story 2 (Phase 4)**: Depends on Foundational phase completion (can run parallel with US1)
- **User Story 3 (Phase 5)**: Depends on Foundational phase completion (can run parallel with US1, US2)
- **User Story 4 (Phase 6)**: Depends on US1, US2, US3 completion (needs existing styles to audit)
- **User Story 5 (Phase 7)**: Depends on US1, US2, US3 completion (needs pages built to test accessibility)
- **Polish (Phase 8)**: Depends on all user stories being complete

### User Story Dependencies

```
Setup (Phase 1)
    ↓
Foundational (Phase 2) ← BLOCKS everything below
    ↓
    ├── User Story 1 (P1) - Login Page ← MVP starts here
    ├── User Story 2 (P2) - Registration Page (can run parallel with US1)
    └── User Story 3 (P3) - Dashboard Layout (can run parallel with US1, US2)
         ↓
         ├── User Story 4 (P4) - Design System Consistency
         └── User Story 5 (P5) - Accessibility (can run parallel with US4)
              ↓
         Polish & Testing (Final Phase)
```

### Within Each User Story

- **US1 (Login)**: Illustration and SCSS components can be created in parallel, then templates use them
- **US2 (Registration)**: Reuses auth base template from US1, only needs new form template
- **US3 (Dashboard)**: Multiple dashboard pages can be updated in parallel after components are created
- **US4 (Design System)**: All SCSS audits can run in parallel
- **US5 (Accessibility)**: Most accessibility tasks can run in parallel across different pages

### Parallel Opportunities

**Within Foundational Phase (after T001-T004):**
```bash
# Can run in parallel:
T005: Create _design-tokens.scss
T007: Create auth.js entry point
T009: Create auth.scss

# Sequential after above:
T006: Update bootstrap-custom.scss (needs T005)
T008: Update webpack.config.js (needs T007)
T010: Build assets (needs all above)
```

**Within User Story 1 (after T010):**
```bash
# Can run in parallel:
T011: Create auth-illustration.svg
T012: Create _auth-layout.scss
T013: Create _illustration.scss

# Then:
T014: Import components into auth.scss (needs T012, T013)
T015: Create auth/_base.html.twig
# ... rest sequential
```

**Within User Story 3 (after T031):**
```bash
# Can run in parallel:
T032: Create dashboard.scss
T033: Create _dashboard-card.scss
T035: Create _dashboard_card.html.twig component
T036: Create _page_header.html.twig component

# Then update all dashboard pages in parallel:
T038-T041: Update account/index.html.twig
T042-T043: Update account/my_plan.html.twig
T044-T045: Update profile/index.html.twig
```

**Within User Story 4:**
```bash
# All audits can run in parallel:
T049-T052: Audit all SCSS files simultaneously
```

**Within User Story 5:**
```bash
# Many accessibility tasks can run in parallel:
T060-T061: Add focus indicators (different files)
T062-T065: Verify form labels (different templates)
T075-T077: Run accessibility audits (different pages)
```

**Within Polish Phase:**
```bash
# Browser testing can run in parallel:
T080-T082: Test different pages in different browsers simultaneously
```

---

## Implementation Strategy

### MVP First (User Story 1 Only - Login Page)

**Goal**: Get the most critical page (login) redesigned and working first

1. ✅ Complete Phase 1: Setup (T001-T004)
2. ✅ Complete Phase 2: Foundational (T005-T010) ← CRITICAL - blocks all stories
3. ✅ Complete Phase 3: User Story 1 - Login (T011-T024)
4. **STOP and VALIDATE**: 
   - Test login page at multiple viewports (320px to 2560px)
   - Test keyboard navigation
   - Test with actual login credentials
   - Verify responsive layout works
5. **Deploy/Demo MVP**: Login page is production-ready

**Benefits**:
- Users see immediate improvement to most-used page
- Tests Webpack configuration and design system
- Establishes patterns for remaining pages
- Can gather feedback before doing more work

### Incremental Delivery (Recommended)

**Sprint 1 - Foundation + MVP**:
1. Complete Setup + Foundational (T001-T010) → Design system ready
2. Complete User Story 1 (T011-T024) → Login page redesigned
3. **DEPLOY**: Users can log in with new modern interface

**Sprint 2 - Authentication Complete**:
1. Complete User Story 2 (T025-T031) → Registration page redesigned
2. **DEPLOY**: Full authentication flow modernized

**Sprint 3 - Dashboard Redesign**:
1. Complete User Story 3 (T032-T048) → Dashboard pages redesigned
2. **DEPLOY**: Complete back-office experience modernized

**Sprint 4 - Quality & Polish**:
1. Complete User Story 4 (T049-T059) → Visual consistency verified
2. Complete User Story 5 (T060-T079) → Accessibility compliance achieved
3. Complete Polish Phase (T080-T100) → Production-ready
4. **DEPLOY**: Fully polished, accessible, tested back-office theme

### Parallel Team Strategy

**With 2 developers after Foundational phase completes:**

- **Developer A**: User Story 1 (Login) + User Story 3 (Dashboard)
- **Developer B**: User Story 2 (Registration) + User Story 4 (Design System)
- **Both**: User Story 5 (Accessibility) - split pages
- **Both**: Polish Phase (Testing) - split browsers/viewports

**With 3 developers after Foundational phase completes:**

- **Developer A**: User Story 1 (Login) + User Story 5 accessibility for auth pages
- **Developer B**: User Story 2 (Registration) + User Story 4 (Design System)
- **Developer C**: User Story 3 (Dashboard) + User Story 5 accessibility for dashboard pages
- **All**: Polish Phase (Testing) - parallel browser testing

---

## Validation Checklist

After completing all tasks, verify:

### Functionality
- [ ] Can log in successfully at /login
- [ ] Can register new account at /register
- [ ] Dashboard displays user data at /account
- [ ] All dashboard pages render correctly (/account/my-plan, /profile)
- [ ] CSRF tokens work in all forms
- [ ] Form validation errors display properly

### Responsive Design
- [ ] All pages work at 320px viewport (mobile)
- [ ] All pages work at 768px viewport (tablet)
- [ ] All pages work at 1200px viewport (desktop)
- [ ] All pages work at 2560px+ viewport (wide screen)
- [ ] No horizontal scrolling at any viewport
- [ ] Layout stacks properly on mobile (<768px)
- [ ] Two-column layout shows on desktop (≥768px)

### Accessibility
- [ ] All pages navigable with keyboard only
- [ ] All interactive elements have visible focus indicators
- [ ] All form inputs have proper labels
- [ ] Color contrast meets WCAG 2.1 AA (4.5:1 normal, 3:1 large)
- [ ] Screen reader can navigate all pages
- [ ] axe DevTools shows no critical violations
- [ ] Pages work at 200% zoom

### Design System
- [ ] All SCSS uses design tokens (no hardcoded colors/spacing)
- [ ] Visual consistency across all pages
- [ ] Bootstrap components styled correctly
- [ ] Card components reusable and consistent
- [ ] Shadows, radius, transitions consistent

### Translations
- [ ] All text in English displays correctly (locale: en)
- [ ] All text in French displays correctly (locale: fr)
- [ ] No missing translation keys
- [ ] Long French text doesn't break layout

### Performance
- [ ] Login page loads in <2 seconds
- [ ] Dashboard page loads in <2 seconds
- [ ] CSS bundle increase <50KB gzipped
- [ ] Lighthouse performance score >90

### Browser Compatibility
- [ ] Chrome: All pages work correctly
- [ ] Firefox: All pages work correctly
- [ ] Safari: All pages work correctly
- [ ] Edge: All pages work correctly

### Security
- [ ] CSRF tokens integrated in all forms
- [ ] No security regressions in authentication flow
- [ ] Public front-site styling not affected

---

## Notes

- **[P] tasks**: Different files, can run in parallel
- **[Story] labels**: Map tasks to user stories for traceability (US1-US5)
- **File paths**: All paths are absolute from repository root
- **Tests**: No test tasks included (not requested in specification)
- **Commits**: Commit after each completed user story phase
- **Validation**: Stop at each checkpoint to validate story independently
- **MVP scope**: User Story 1 (Login page) is the minimum viable product
- **Design tokens**: Must be used consistently - no hardcoded values
- **Accessibility**: Build in from the start, not retrofitted
- **Responsive**: Mobile-first approach (design for 320px up)

---

## Quick Reference

### Key Files Modified

**SCSS/Assets** (app/assets/):
- styles/_design-tokens.scss ← NEW
- styles/bootstrap-custom.scss ← MODIFIED
- styles/auth.scss ← NEW
- styles/dashboard.scss ← NEW
- styles/components/_auth-layout.scss ← NEW
- styles/components/_dashboard-card.scss ← NEW
- styles/components/_illustration.scss ← NEW
- auth.js ← NEW
- images/auth-illustration.svg ← NEW

**Templates** (app/templates/):
- auth/_base.html.twig ← NEW
- security/login.html.twig ← MODIFIED
- security/register.html.twig ← MODIFIED
- account/index.html.twig ← MODIFIED
- account/my_plan.html.twig ← MODIFIED
- profile/index.html.twig ← MODIFIED
- base.html.twig ← MODIFIED (remove sidebar)
- components/_dashboard_card.html.twig ← NEW
- components/_page_header.html.twig ← NEW

**Translations** (app/translations/):
- messages.en.yaml ← MODIFIED (add auth.*, dashboard.*)
- messages.fr.yaml ← MODIFIED (add auth.*, dashboard.*)

**Config**:
- webpack.config.js ← MODIFIED (add 'auth' entry)

### Design Token Categories

- **Colors**: $hermio-primary, $hermio-secondary, $hermio-success, $hermio-danger, $hermio-gray-*
- **Spacing**: $hermio-space-xs through $hermio-space-3xl
- **Typography**: $hermio-text-xs through $hermio-text-5xl, $hermio-font-*
- **Radius**: $hermio-radius-sm through $hermio-radius-full
- **Shadows**: $hermio-shadow-sm through $hermio-shadow-xl
- **Transitions**: $hermio-transition-fast, $hermio-transition-base, $hermio-transition-slow

### User Story Summary

1. **US1 (P1)**: Modern Login Experience - Two-column layout with illustration
2. **US2 (P2)**: Streamlined Registration - Consistent with login design
3. **US3 (P3)**: Modern Dashboard Layout - Card-based, no sidebar
4. **US4 (P4)**: Unified Visual Design System - Consistent tokens usage
5. **US5 (P5)**: Accessible Interface - WCAG 2.1 AA compliance

**Total Tasks**: 100 tasks across 8 phases
**Parallelizable**: ~35 tasks marked [P] can run in parallel within their phase
**MVP**: 24 tasks (Setup + Foundational + US1) for minimum viable product

---

**Ready to implement! Start with Phase 1 (Setup) and proceed through phases sequentially, but feel free to parallelize tasks within each phase where marked [P].**
