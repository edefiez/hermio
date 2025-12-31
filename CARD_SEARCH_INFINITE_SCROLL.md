# Card Search and Infinite Scroll Implementation

## Overview

This implementation adds search functionality and infinite scroll to the card list page (`/cards`) without pagination. The solution uses standard JavaScript with AJAX calls instead of Symfony UX Live Component due to platform compatibility constraints.

## Features Implemented

### 1. Card Search
- **Search Input**: Added at the top of the card list page
- **Search Fields**: Searches across card name, email, company, and slug
- **Debounce**: 300ms delay to avoid excessive API calls while typing
- **Case-Insensitive**: All searches are case-insensitive
- **Real-time**: Results update as you type

### 2. Infinite Scroll
- **Auto-Load**: Automatically loads more cards when scrolling near the bottom
- **Batch Size**: Loads 10 cards at a time
- **Loading Indicator**: Shows a spinner while fetching new cards
- **Smart Detection**: Uses Intersection Observer API for efficient scroll detection
- **No Pagination UI**: Seamless experience without page numbers or "Load More" buttons

### 3. Card Counter
- **Total Display**: Shows total number of cards matching the current search
- **Dynamic Update**: Updates when search query changes

## Technical Implementation

### Backend Changes

#### 1. CardRepository (`app/src/Repository/CardRepository.php`)

Added two new methods:

```php
public function searchByUser(User $user, ?string $query, int $limit = 10, int $offset = 0): array
```
- Searches cards for a specific user with pagination
- Uses MariaDB JSON functions to search within the JSON content field
- Supports search in: name, email, company fields, and slug

```php
public function countByUser(User $user, ?string $query = null): int
```
- Counts total cards matching the search criteria
- Used to determine if more cards are available for infinite scroll

**Database Compatibility**: 
- Uses `JSON_UNQUOTE(JSON_EXTRACT(...))` for proper string handling in MariaDB
- Compatible with MariaDB 11.4+ (as used in the project)

#### 2. CardService (`app/src/Service/CardService.php`)

Added methods to handle search with team member access control:

```php
public function searchAccessibleCardsForUser(User $user, ?string $query, int $limit = 10, int $offset = 0): array
```
- Respects Enterprise plan permissions
- ADMIN team members can search all account cards
- MEMBER team members can only search their assigned cards
- Non-Enterprise users search only their own cards

```php
public function countAccessibleCardsForUser(User $user, ?string $query = null): int
```
- Counts cards with same permission logic

#### 3. CardController (`app/src/Controller/CardController.php`)

**Modified `index()` method**:
- Now loads initial 10 cards instead of all cards
- Passes `totalCards` count to template
- More efficient for accounts with many cards

**New `apiSearch()` method**:
- Route: `/cards/api/search`
- Method: GET
- Parameters:
  - `q`: Search query (optional)
  - `offset`: Pagination offset (default: 0)
- Returns: HTML fragment with card items and metadata
- Used by JavaScript for AJAX requests

### Frontend Changes

#### 1. Template Structure

**Main Template** (`app/templates/card/index.html.twig`):
- Added search input field
- Added card counter display
- Added loading indicator
- Added scroll trigger div
- Included inline JavaScript for search and infinite scroll

**Partial Template** (`app/templates/card/_card_list.html.twig`):
- Extracted card list items to separate template
- Reusable for both initial load and AJAX responses
- Maintains all existing functionality (edit, delete, QR code, assignments)

#### 2. JavaScript Implementation

**Key Features**:
- **Debounced Search**: 300ms timeout prevents excessive API calls
- **Intersection Observer**: Efficient scroll detection (100px before reaching trigger)
- **State Management**: Tracks current query, offset, loading state, and hasMore flag
- **Error Handling**: Catches and logs API errors
- **DOM Manipulation**: Safely parses HTML and appends cards

**Functions**:
- `loadCards(replace)`: Fetches cards from API
  - `replace=true`: Clears container and resets (used for new search)
  - `replace=false`: Appends to existing cards (used for infinite scroll)

## How to Test

### Prerequisites
```bash
# Start the application
make up

# Ensure database is migrated
make migrate

# Compile assets if needed
make npm-build
```

### Manual Testing Steps

1. **Access the Card List**
   - Navigate to `/cards` (requires login)
   - Should see initial 10 cards (if you have that many)

2. **Test Search Functionality**
   - Type in the search box
   - Results should update after 300ms
   - Try searching for:
     - Card names (e.g., "John")
     - Email addresses (e.g., "example.com")
     - Company names
     - Partial matches (e.g., "joh" should match "John")
   - Verify the card count updates

3. **Test Infinite Scroll**
   - Create more than 10 cards (or use fixtures)
   - Scroll down to the bottom
   - New cards should automatically load
   - Loading spinner should appear briefly
   - Continue scrolling to load more batches

4. **Test Edge Cases**
   - Search with no results: Should show "No cards found" message
   - Search then clear: Should reload all cards
   - Account with < 10 cards: Infinite scroll should not trigger
   - Empty account: Should show "No cards" message with create button

5. **Test Existing Functionality**
   - Edit button should still work
   - QR Code button should still work
   - Delete button should still work (with confirmation)
   - Team assignments should still display (Enterprise only)
   - Quota display should still show correctly

### Browser Console Testing

Open browser console and test JavaScript:
```javascript
// Check if search input exists
document.getElementById('card-search')

// Check if scroll trigger exists
document.getElementById('scroll-trigger')

// Monitor API calls
// (Network tab should show /cards/api/search requests)
```

## Performance Considerations

1. **Initial Load**: Only fetches 10 cards instead of all cards
2. **Lazy Loading**: Cards are loaded on-demand as user scrolls
3. **Debounced Search**: Reduces API calls during typing
4. **Optimized Queries**: Uses single query to fetch card assignments
5. **Indexed Fields**: Slug field is indexed; JSON searches are functional

## Security Considerations

1. **Authentication**: All endpoints require `ROLE_USER`
2. **Authorization**: CardService enforces team member access rules
3. **CSRF Protection**: Delete forms include CSRF tokens
4. **Input Sanitization**: Search query is parameterized in SQL
5. **XSS Protection**: Twig auto-escapes output by default

## Browser Compatibility

- **Intersection Observer API**: Supported in all modern browsers
  - Chrome 51+
  - Firefox 55+
  - Safari 12.1+
  - Edge 15+
- **Fetch API**: Supported in all modern browsers
- **DOMParser**: Supported in all modern browsers

For older browsers, consider adding polyfills.

## Future Enhancements

Possible improvements for future iterations:

1. **Sort Options**: Allow sorting by name, date created, etc.
2. **Filter Options**: Filter by assignment status, team member, etc.
3. **Search History**: Remember recent searches
4. **Keyboard Navigation**: Arrow keys to navigate results
5. **Advanced Search**: Multiple criteria, date ranges, etc.
6. **Export Results**: Export search results to CSV
7. **Bulk Operations**: Select and perform actions on multiple cards
8. **Real-time Updates**: WebSocket for live updates when cards change

## Troubleshooting

### Search Not Working
- Check browser console for JavaScript errors
- Verify `/cards/api/search` endpoint is accessible
- Check database JSON field structure matches expected format

### Infinite Scroll Not Triggering
- Verify scroll trigger div exists in DOM
- Check if `hasMore` flag is properly set
- Ensure more than 10 cards exist in the account

### Cards Not Loading
- Check network tab for failed requests
- Verify user has proper permissions
- Check PHP logs for server-side errors

## Files Changed

1. `app/src/Repository/CardRepository.php` - Added search methods
2. `app/src/Service/CardService.php` - Added search with permissions
3. `app/src/Controller/CardController.php` - Added API endpoint
4. `app/templates/card/index.html.twig` - Updated with search and infinite scroll
5. `app/templates/card/_card_list.html.twig` - New partial template

## References

- [Intersection Observer API](https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API)
- [Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [MariaDB JSON Functions](https://mariadb.com/kb/en/json-functions/)
- [Symfony UX Documentation](https://symfony.com/bundles/ux-live-component/current/index.html) (for reference, not used)
