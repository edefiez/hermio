# Research: Modern Admin Template for Authenticated Users

**Feature**: 009-admin-template  
**Date**: 2025-12-11  
**Purpose**: Research Bootstrap patterns and best practices for implementing a modern admin layout with sidebar navigation

## Research Questions

### 1. Bootstrap Sidebar Patterns

**Question**: What are the best Bootstrap 5 patterns for creating responsive sidebars with collapse/expand functionality?

**Research Findings**:
- Bootstrap 5 does not include a built-in sidebar component, but provides utilities and components that can be combined
- Common pattern: Use `offcanvas` component for mobile sidebar (slide-in from left)
- Desktop sidebar: Use flexbox utilities with fixed positioning or grid layout
- Collapse functionality: Use Bootstrap's collapse component or custom JavaScript with CSS transitions
- Best practice: Use `position-fixed` for sidebar, `margin-left` or `padding-left` for main content on desktop
- Responsive breakpoints: Use `d-none d-lg-block` to hide/show sidebar at different viewport sizes

**Decision**: 
- Use Bootstrap `offcanvas` component for mobile sidebar (slide-in overlay)
- Use custom flexbox layout with `position-fixed` sidebar for desktop
- Implement collapse/expand with Bootstrap collapse utilities and custom JavaScript

**Rationale**: 
- Bootstrap offcanvas provides accessible, mobile-friendly sidebar out of the box
- Flexbox layout is flexible and works well with Bootstrap's grid system
- Custom JavaScript needed for state persistence and smooth transitions

**Alternatives Considered**:
- Full custom sidebar implementation: More control but more code to maintain
- Third-party Bootstrap sidebar libraries: Adds dependency, may not match design system
- CSS-only solution: Limited interactivity, harder to maintain state

---

### 2. Admin Layout Best Practices

**Question**: What are industry best practices for admin dashboard layouts with sidebar navigation?

**Research Findings**:
- Standard pattern: Left sidebar (200-250px width), top header (60-80px height), main content area
- Sidebar should be fixed/sticky to remain visible during scroll
- Active navigation item should be clearly highlighted
- Icons + labels for navigation items improve usability
- Collapsible sidebar should show icons-only when collapsed (60-80px width)
- Header should show page title and user context
- Main content area should have proper padding and max-width for readability

**Decision**:
- Sidebar width: 250px expanded, 80px collapsed on desktop
- Header height: 70px
- Main content: Full width with container padding
- Active item: Use Bootstrap `active` class with background color highlight
- Icons: Font Awesome icons for all navigation items

**Rationale**:
- Standard dimensions provide good balance between navigation visibility and content space
- Bootstrap classes ensure consistency with design system
- Font Awesome already integrated in project

**Alternatives Considered**:
- Narrower sidebar (180px): Less space for labels, harder to read
- Wider sidebar (300px): Takes too much space from content on smaller screens
- No icons: Less visual hierarchy, harder to scan

---

### 3. Mobile Sidebar Patterns

**Question**: What are best practices for mobile-first sidebar patterns with hamburger menu?

**Research Findings**:
- Mobile sidebar should be hidden by default (overlay pattern)
- Hamburger menu button in header toggles sidebar
- Sidebar should slide in from left with smooth animation (300-400ms)
- Clicking outside sidebar or selecting item should close sidebar
- Backdrop/overlay behind sidebar improves focus and prevents accidental clicks
- Sidebar should be full-height and overlay main content
- Close button (X) in sidebar header improves accessibility

**Decision**:
- Use Bootstrap `offcanvas` component with `offcanvas-start` (slide from left)
- Hamburger button in header triggers offcanvas
- Auto-close on navigation item click
- Backdrop enabled for focus management
- Animation duration: 350ms (Bootstrap default)

**Rationale**:
- Bootstrap offcanvas provides accessible, tested mobile sidebar pattern
- Consistent with Bootstrap design language
- Built-in accessibility features (ARIA, focus management)
- Minimal custom code required

**Alternatives Considered**:
- Custom slide-in animation: More control but more code, potential accessibility issues
- Bottom sheet pattern: Less common for admin layouts, harder to navigate
- Tab-based navigation: Doesn't scale well with many items

---

### 4. Accessibility Patterns

**Question**: What ARIA patterns and keyboard navigation support are needed for sidebar navigation?

**Research Findings**:
- Sidebar should use `<nav>` element with `aria-label="Main navigation"`
- Navigation items should use `<a>` elements (not buttons) for proper keyboard navigation
- Active item should use `aria-current="page"` attribute
- Keyboard navigation: Tab to navigate items, Enter/Space to activate
- Skip link to main content improves keyboard navigation
- Focus management: When sidebar opens, focus should move to first item or close button
- Screen reader announcements: Use `aria-live` regions for dynamic content

**Decision**:
- Use semantic HTML: `<nav>`, `<ul>`, `<li>`, `<a>` structure
- Add `aria-label="Main navigation"` to sidebar nav
- Add `aria-current="page"` to active navigation item
- Implement keyboard navigation support in JavaScript
- Add skip link to main content area
- Manage focus when sidebar opens/closes

**Rationale**:
- Semantic HTML provides baseline accessibility
- ARIA attributes enhance screen reader experience
- Keyboard navigation is essential for accessibility compliance
- Skip links are WCAG 2.1 Level AA requirement

**Alternatives Considered**:
- Minimal ARIA: Insufficient for screen reader users
- Full ARIA implementation: Over-engineering for this use case
- Third-party accessibility library: Adds dependency, may conflict with Bootstrap

---

### 5. State Persistence Patterns

**Question**: How should sidebar collapse/expand state be persisted across page navigations?

**Research Findings**:
- localStorage is standard for persisting UI preferences
- Key naming: Use descriptive key like `admin-sidebar-collapsed`
- State should be boolean (true = collapsed, false = expanded)
- Read state on page load, apply CSS class accordingly
- Fallback: Default to expanded state if localStorage unavailable
- Consider user preference vs. session preference (localStorage vs. sessionStorage)
- State should be per-user if multi-user support needed (not applicable here)

**Decision**:
- Use localStorage with key `hermio-admin-sidebar-collapsed`
- Store boolean value (true/false)
- Read on page load, apply `sidebar-collapsed` class to body or sidebar
- Default to expanded (false) if no stored preference
- Update state when user clicks collapse/expand button

**Rationale**:
- localStorage persists across sessions, providing better UX
- Simple boolean state is easy to manage
- Default to expanded ensures sidebar is visible on first visit
- No server-side state needed for this UI preference

**Alternatives Considered**:
- sessionStorage: State lost on browser close, less convenient
- Cookie-based: More complex, requires server-side handling
- No persistence: Poor UX, users must collapse on every page

---

## Consolidated Decisions

1. **Sidebar Implementation**: Bootstrap offcanvas for mobile, custom flexbox layout for desktop
2. **Layout Dimensions**: 250px sidebar (expanded), 80px (collapsed), 70px header height
3. **Mobile Pattern**: Offcanvas slide-in from left with backdrop
4. **Accessibility**: Semantic HTML, ARIA labels, keyboard navigation, skip links
5. **State Persistence**: localStorage for sidebar collapse state

## Implementation Notes

- All patterns use Bootstrap 5 components and utilities exclusively
- No third-party dependencies required beyond existing Bootstrap and Font Awesome
- JavaScript will be minimal, focused on state management and event handling
- SCSS will extend existing design tokens and Bootstrap variables
- Templates will use Twig blocks and includes for component reusability

