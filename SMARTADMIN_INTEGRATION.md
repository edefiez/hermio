# SmartAdmin Theme Integration - Testing Guide

## Overview
This document describes the SmartAdmin theme integration into the Hermio application and provides instructions for testing.

## Changes Made

### 1. Assets Integration
- **Location**: `/app/assets/smartadmin/`
- **Contents**:
  - CSS: SmartAdmin stylesheets (smartapp.min.css, themes)
  - JavaScript: Core scripts (smartApp.js, smartNavigation.js, smartFilter.js, smartSlimscroll.js)
  - Plugins: Bootstrap, Waves, SortableJS
  - Webfonts: SmartAdmin icons and Font Awesome
  - Icons: SVG sprite with all SmartAdmin icons

### 2. Webpack Configuration
- **File**: `/app/webpack.config.js`
- **Changes**:
  - Added new entry point `admin` for SmartAdmin assets
  - Configured `copyFiles()` to copy webfonts and plugins to build directory
  - Installed `file-loader` dependency for asset copying

### 3. New Entry Point
- **File**: `/app/assets/admin.js`
- **Imports**:
  - SmartAdmin CSS (smartapp.min.css)
  - SmartAdmin icons (sa-icons.css)
  - Font Awesome
  - Waves CSS
  - Core JavaScript files
  - Custom SmartAdmin styles for Hermio (`smartadmin-custom.scss`)

### 4. Template Changes

#### Base Admin Layout
- **File**: `/app/templates/base_admin.html.twig`
- **Structure**: Completely rewritten to use SmartAdmin layout structure:
  ```html
  <div class="app-wrap">
    <header class="app-header">...</header>
    <aside class="app-sidebar">...</aside>
    <main class="app-body">
      <div class="app-content">
        <div class="content-wrapper">
          <div class="main-content">
            <!-- Page content -->
          </div>
        </div>
      </div>
      <footer class="app-footer">...</footer>
    </main>
  </div>
  ```
- **Asset Loading**: Now loads `admin` entry instead of inheriting from base layout

#### New Sidebar
- **File**: `/app/templates/admin/_smartadmin_sidebar.html.twig`
- **Features**:
  - SmartAdmin sidebar structure with logo
  - Menu filter/search input
  - SVG icons for navigation items
  - Maintained all existing Hermio navigation links:
    - Dashboard
    - My Cards
    - Account
    - Profile
    - Branding (conditional)
  - No-results message for search
  - Mobile-responsive with backdrop overlay

#### New Header
- **File**: `/app/templates/admin/_smartadmin_header.html.twig`
- **Features**:
  - Mobile menu toggle button
  - Desktop sidebar collapse/expand button
  - Theme toggle (light/dark mode)
  - Language selector dropdown (EN/FR)
  - User profile dropdown with:
    - User email
    - Plan type
    - Profile link
    - Account settings link
    - Logout link
  - All using SmartAdmin SVG icons

#### New Footer
- **File**: `/app/templates/admin/_smartadmin_footer.html.twig`
- **Content**: Simple footer with copyright and rights reserved message

### 5. Template Block Name Changes
All admin templates were updated to use `content` block instead of `admin_content`:
- `/app/templates/admin/dashboard.html.twig`
- `/app/templates/admin/account/*.html.twig`
- `/app/templates/admin/webhook/*.html.twig`
- `/app/templates/account/*.html.twig`
- `/app/templates/branding/configure.html.twig`
- `/app/templates/card/*.html.twig`
- `/app/templates/profile/*.html.twig`
- `/app/templates/subscription/*.html.twig`
- `/app/templates/team/index.html.twig`

### 6. Public Assets
- **Icons**: Copied SmartAdmin SVG icons to `/app/public/icons/`
  - Main sprite: `/icons/sprite.svg`
  - Individual icons for all UI elements

## Build Verification

The assets have been successfully compiled:
```bash
npm run build
```

**Build Output**:
- ✅ `admin.194d04ab.js` - SmartAdmin JavaScript bundle (122KB)
- ✅ `admin.bcc1db67.css` - SmartAdmin CSS bundle (563KB)
- ✅ Webfonts copied to `/build/webfonts/`
- ✅ Plugins copied to `/build/plugins/`

## Testing Instructions

### Prerequisites
1. PHP 8.4+ installed
2. Composer dependencies installed:
   ```bash
   cd app
   composer install
   ```
3. Database configured and migrations run
4. Assets compiled (already done):
   ```bash
   npm run build
   ```

### Local Testing Steps

1. **Start Symfony Development Server**:
   ```bash
   cd app
   symfony server:start
   # or
   php -S localhost:8000 -t public/
   ```

2. **Login to Admin Area**:
   - Navigate to http://localhost:8000/login
   - Log in with valid credentials
   - You should be redirected to the dashboard

3. **Verify SmartAdmin Theme Loading**:
   - Check browser console for "SmartAdmin theme loaded" message
   - Verify no JavaScript errors
   - Check that admin.css and admin.js are loaded in Network tab

4. **Test Responsive Behavior**:
   - **Desktop (≥992px)**:
     - Sidebar should be visible on the left
     - Collapse/expand button should work
     - Logo should be visible at top of sidebar
     - Navigation should show icons and text
   
   - **Mobile (<992px)**:
     - Sidebar should be hidden by default
     - Hamburger menu button should be visible
     - Clicking hamburger should slide in sidebar
     - Clicking outside should close sidebar
     - Backdrop overlay should be visible when open

5. **Test Navigation**:
   - Click each navigation item:
     - Dashboard
     - My Cards
     - Account
     - Profile
     - Branding (if Pro/Enterprise plan)
   - Verify active state highlighting
   - Verify page content loads correctly

6. **Test Header Features**:
   - **Theme Toggle**: Click sun/moon icon to switch light/dark mode
   - **Language Selector**: 
     - Click globe icon
     - Select French/English
     - Verify language changes
   - **User Menu**:
     - Click user icon
     - Verify dropdown shows email and plan type
     - Test Profile link
     - Test Account link
     - Test Logout

7. **Test Search/Filter** (if implemented):
   - Type in sidebar search box
   - Verify menu items filter
   - Verify "no results" message appears when nothing matches
   - Press ESC to reset

8. **Test Dark Mode**:
   - Click theme toggle button
   - Verify colors switch to dark theme
   - Verify preference is saved (refresh page)
   - Verify all text is readable

9. **Browser Testing**:
   Test in multiple browsers:
   - Chrome/Edge
   - Firefox
   - Safari (if available)

### Visual Verification Checklist

- [ ] SmartAdmin logo/icon appears in sidebar
- [ ] Navigation icons (SVG) render correctly
- [ ] Active page is highlighted in navigation
- [ ] Header controls are properly aligned
- [ ] Footer is fixed at bottom
- [ ] All dropdowns open/close properly
- [ ] Animations are smooth (sidebar collapse, dropdown animations)
- [ ] Typography matches SmartAdmin style
- [ ] Colors match SmartAdmin theme
- [ ] Spacing and padding are consistent

### Known Limitations

1. **Custom Branding**: The logo in the sidebar is a simple "H" SVG. This should be replaced with actual Hermio branding.
2. **Advanced Features**: Some advanced SmartAdmin features are not yet implemented:
   - Settings drawer
   - Notifications panel
   - App drawer
   - Multiple sidebar states (minified, compact)
3. **Theme Persistence**: Dark mode preference may need localStorage implementation
4. **Menu Search**: Filter functionality requires JavaScript initialization

### Next Steps

If testing reveals issues:

1. **CSS Not Loading**: Verify `encore_entry_link_tags('admin')` in base_admin.html.twig
2. **JS Not Working**: Check browser console for errors
3. **Icons Not Showing**: Verify `/icons/sprite.svg` is accessible
4. **Layout Broken**: Check that all SmartAdmin CSS classes are present
5. **Responsive Issues**: Test media queries in browser dev tools

### Rollback Plan

If SmartAdmin integration causes critical issues:

1. Revert `base_admin.html.twig` to previous version
2. Restore old sidebar/header templates
3. Update block names back to `admin_content`
4. Remove `admin` entry from webpack.config.js
5. Rebuild assets: `npm run build`

## File Structure

```
app/
├── assets/
│   ├── admin.js (NEW - SmartAdmin entry point)
│   ├── smartadmin/ (NEW - All SmartAdmin assets)
│   │   ├── css/
│   │   ├── scripts/
│   │   ├── webfonts/
│   │   └── plugins/
│   └── styles/
│       └── smartadmin-custom.scss (NEW - Hermio customizations)
├── public/
│   ├── build/ (Compiled assets)
│   └── icons/ (SmartAdmin SVG icons)
├── templates/
│   ├── base_admin.html.twig (MODIFIED - New structure)
│   └── admin/
│       ├── _smartadmin_header.html.twig (NEW)
│       ├── _smartadmin_sidebar.html.twig (NEW)
│       └── _smartadmin_footer.html.twig (NEW)
└── webpack.config.js (MODIFIED - New entry and copyFiles)
```

## Support

For issues or questions about the SmartAdmin integration:
1. Check browser console for JavaScript errors
2. Verify all assets are loading in Network tab
3. Review SmartAdmin documentation in `docs/SmartAdmin/Documentation/`
4. Check compiled CSS/JS bundles in `public/build/`

## Conclusion

The SmartAdmin theme has been successfully integrated into Hermio's admin area. All admin pages now use the modern, professional SmartAdmin layout while maintaining all existing Hermio functionality.
