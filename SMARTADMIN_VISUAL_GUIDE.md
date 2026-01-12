# SmartAdmin Theme - Visual Description

## Overview
The Hermio admin interface now uses the SmartAdmin professional admin template theme, providing a modern, clean, and highly functional user experience.

## Layout Structure

### Desktop View (≥992px)

```
┌─────────────────────────────────────────────────────────────┐
│ ┌───────┐ ┌──────────────────────────────────────────────┐ │
│ │   H   │ │  [≡]  [Globe] [Theme] [User▾]                │ │ <- Header
│ └───────┘ └──────────────────────────────────────────────┘ │
│ ┌───────┐ ┌──────────────────────────────────────────────┐ │
│ │[🔍]   │ │                                              │ │
│ ├───────┤ │                                              │ │
│ │🏠 Dash│ │                                              │ │
│ │📇 Card│ │           Page Content Area                  │ │
│ │⚙ Acct │ │                                              │ │
│ │👤 Prof│ │                                              │ │
│ │🎨 Brnd│ │                                              │ │
│ │       │ │                                              │ │
│ │       │ │                                              │ │
│ │       │ │                                              │ │
│ │  [📶] │ └──────────────────────────────────────────────┘ │
│ └───────┘ ┌──────────────────────────────────────────────┐ │
│           │ Hermio © 2026. All rights reserved.          │ │ <- Footer
│           └──────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Mobile View (<992px)

```
┌───────────────────────────────┐
│ [☰] [Globe] [Theme] [User▾]  │ <- Header
├───────────────────────────────┤
│                               │
│                               │
│                               │
│       Page Content Area       │
│                               │
│                               │
│                               │
├───────────────────────────────┤
│ Hermio © 2026. Rights res...  │ <- Footer
└───────────────────────────────┘

Hamburger Menu (☰) opens sidebar overlay
```

## Components Detail

### 1. Header (app-header)

**Desktop Layout**:
```
┌─────────────────────────────────────────────────────────┐
│  [◀]  (collapse button)  │   [🌙/☀]  [🌐]  [👤]         │
│                          │   Theme   Lang   User         │
└─────────────────────────────────────────────────────────┘
```

**Features**:
- **Collapse Button** (≡): Toggles sidebar between full (250px) and minified (80px)
  - Animated SVG icon
  - Only visible on desktop
- **Theme Toggle** (🌙/☀): Switches between light and dark mode
  - Sun icon in light mode
  - Moon icon in dark mode
  - Smooth transition animation
- **Language Selector** (🌐): Dropdown for EN/FR
  - Globe icon
  - Shows current language
  - Dropdown with flags: 🇬🇧 English, 🇫🇷 Français
- **User Menu** (👤): Profile dropdown
  - User icon
  - Shows user email
  - Dropdown contains:
    - Email and plan type
    - Profile link
    - Account settings link
    - Divider
    - Logout (in red)

**Mobile Layout**:
```
┌─────────────────────────────────────┐
│  [☰]    [🌙/☀]  [🌐]  [👤]          │
│  Menu   Theme   Lang   User         │
└─────────────────────────────────────┘
```

### 2. Sidebar (app-sidebar)

**Full State (250px)**:
```
┌──────────────┐
│      H       │ <- Logo (clickable to dashboard)
├──────────────┤
│ [🔍______]   │ <- Search/filter input
├──────────────┤
│ 🏠 Dashboard │
│ 📇 My Cards  │
│ ⚙ Account    │
│ 👤 Profile   │
│ 🎨 Branding  │ (conditional on plan)
│              │
│              │
├──────────────┤
│     [📶]     │ <- Connection indicator
└──────────────┘
```

**Collapsed State (80px)**:
```
┌────┐
│ H  │
├────┤
│[🔍]│
├────┤
│ 🏠 │
│ 📇 │
│ ⚙  │
│ 👤 │
│ 🎨 │
│    │
│    │
├────┤
│[📶]│
└────┘
```

**Features**:
- **Logo**: Simple "H" icon (should be replaced with Hermio branding)
  - Clickable, links to dashboard
  - Centered in sidebar
- **Search Box**: Filters menu items in real-time
  - Placeholder: "Search menu..."
  - Shows "No results" message when empty
  - ESC key resets filter
- **Navigation Items**: All with icons
  - **Dashboard** (home icon): Main overview page
  - **My Cards** (credit-card icon): Card management
  - **Account** (settings icon): Account settings
  - **Profile** (user icon): User profile
  - **Branding** (droplet icon): Brand customization (Pro/Enterprise only)
- **Active State**: Current page highlighted with:
  - Different background color
  - Bolder text
  - Visual indicator
- **Hover Effect**: Smooth color transition on hover
- **Connection Indicator**: WiFi icon at bottom

### 3. Content Area (app-content)

**Structure**:
```
┌─────────────────────────────────────┐
│ Page Title (if defined)             │
│ Page Subtitle (if defined)          │
├─────────────────────────────────────┤
│                                     │
│ Flash Messages (alerts)             │
│                                     │
├─────────────────────────────────────┤
│                                     │
│                                     │
│     Main Page Content               │
│     (dashboard, forms, tables, etc) │
│                                     │
│                                     │
└─────────────────────────────────────┘
```

**Features**:
- **Fluid Width**: Adjusts based on sidebar state
  - Full sidebar: Content pushed right
  - Collapsed sidebar: More content width
  - Smooth transition animation
- **Flash Messages**: Bootstrap alerts with dismiss button
  - Success (green)
  - Error/Danger (red)
  - Warning (yellow)
  - Info (blue)
- **Responsive Padding**: Appropriate spacing on all screen sizes

### 4. Footer (app-footer)

```
┌──────────────────────────────────────────┐
│ Hermio © 2026. All rights reserved.     │
└──────────────────────────────────────────┘
```

**Features**:
- Fixed at bottom of content area
- Dynamic year (JavaScript)
- Responsive text (shortened on mobile)
- Subtle background color

## Color Scheme

### Light Mode
- **Background**: Clean white (#ffffff)
- **Sidebar**: Light gray background (#f8f9fa)
- **Header**: White with subtle shadow
- **Primary**: Brand blue (from SmartAdmin)
- **Text**: Dark gray (#212529)
- **Links**: Blue on hover
- **Active Nav**: Highlighted background (#e9ecef)

### Dark Mode
- **Background**: Dark gray (#1a1d21)
- **Sidebar**: Darker gray (#0d0f12)
- **Header**: Dark with shadow
- **Primary**: Lighter blue (adjusted for dark)
- **Text**: Light gray (#e4e6eb)
- **Links**: Light blue on hover
- **Active Nav**: Highlighted background (#2d3034)

## Typography

- **Headings**: 
  - Page titles: 1.75rem, bold
  - Section headers: 1rem, semi-bold
- **Body Text**: 1rem, regular
- **Navigation**: 0.875rem, medium
- **Footer**: 0.875rem, regular

## Icons

All icons are SVG from SmartAdmin's icon sprite:
- **Format**: SVG with `<use>` element
- **Path**: `/icons/sprite.svg#icon-name`
- **Size**: Configurable via CSS classes
- **Color**: Inherits from text color (currentColor)
- **Examples**:
  - home, credit-card, settings, user
  - globe, sun, moon, log-out
  - menu, chevron-left, chevron-right

## Animations & Transitions

1. **Sidebar Collapse**:
   - Duration: 300ms
   - Easing: ease-in-out
   - Animates: width, opacity (text)

2. **Theme Toggle**:
   - Duration: 200ms
   - Easing: ease
   - Animates: colors, backgrounds

3. **Dropdown Open**:
   - Duration: 150ms
   - Easing: ease-out
   - Animates: opacity, transform

4. **Navigation Hover**:
   - Duration: 200ms
   - Easing: ease
   - Animates: background-color

5. **Mobile Sidebar**:
   - Duration: 300ms
   - Easing: ease-in-out
   - Animates: transform (slide)
   - Includes backdrop fade

## Responsive Breakpoints

- **Desktop**: ≥992px (lg)
  - Sidebar always visible
  - Full header controls
  - Collapse functionality active

- **Tablet**: 768px - 991px (md)
  - Hamburger menu
  - Sidebar overlay
  - Reduced header controls

- **Mobile**: <768px (sm)
  - Hamburger menu
  - Sidebar overlay
  - Minimal header controls
  - Stacked content

## Interactive States

### Navigation Links
- **Default**: Regular style with icon
- **Hover**: Background color change, cursor pointer
- **Active**: Bold, highlighted background
- **Focus**: Outline for keyboard navigation

### Buttons
- **Default**: Primary button style
- **Hover**: Slightly darker shade
- **Active**: Pressed state (darker)
- **Disabled**: Grayed out, no pointer

### Dropdowns
- **Closed**: Button/icon visible
- **Open**: Menu slides down with shadow
- **Hover Item**: Background color change
- **Active Item**: Checkmark or indicator

## Accessibility Features

1. **ARIA Labels**: All interactive elements have descriptive labels
2. **Keyboard Navigation**: 
   - Tab through all interactive elements
   - Enter to activate
   - ESC to close modals/dropdowns
3. **Focus Indicators**: Visible outline on focus
4. **Screen Reader Support**: Semantic HTML and ARIA attributes
5. **Color Contrast**: WCAG AA compliant in both themes
6. **Skip Links**: "Skip to main content" link for screen readers

## Browser Compatibility

- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support (with -webkit prefixes)
- IE11: Not supported (uses modern CSS)

## Performance

- **CSS Bundle**: 563KB (minified)
- **JS Bundle**: 122KB (minified)
- **Icons**: SVG sprite (loaded once)
- **Fonts**: Web fonts (cached)
- **Load Time**: <2s on average connection

## Future Enhancements

Potential additions (not yet implemented):
1. Settings drawer (right side panel)
2. Notifications panel
3. App drawer with AI assistant
4. Multiple sidebar themes
5. Compact mode option
6. Custom color picker
7. Breadcrumb navigation
8. Advanced search
