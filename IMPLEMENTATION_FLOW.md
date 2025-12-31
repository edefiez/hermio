# Implementation Flow Diagram

## User Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    User visits /cards page                      │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│         CardController::index() - Initial Load                  │
│  - Fetches first 10 cards                                       │
│  - Calculates total count                                       │
│  - Renders index.html.twig                                      │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              Page Rendered with Components:                     │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Header: Quota Display + Create Button                   │  │
│  └───────────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Search Bar: [Search Input] | Total: X cards            │  │
│  └───────────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Card Grid: [Card 1] [Card 2]                            │  │
│  │             [Card 3] [Card 4]  ...                       │  │
│  │             [Card 9] [Card 10]                           │  │
│  └───────────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Scroll Trigger (invisible)                              │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

## Search Flow

```
┌─────────────────────────────────────────────────────────────────┐
│         User types in search box: "john"                        │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│           JavaScript: Debounce 300ms                            │
│  - Wait for user to stop typing                                 │
│  - Prevent excessive API calls                                  │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│         loadCards(replace=true)                                 │
│  - Clear existing cards                                         │
│  - Reset offset to 0                                            │
│  - Show loading spinner                                         │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│     AJAX GET /cards/api/search?q=john&offset=0                  │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│            CardController::apiSearch()                          │
│  - CardService::searchAccessibleCardsForUser()                  │
│    └─> CardRepository::searchByUser()                           │
│        └─> SQL: WHERE ... AND (                                 │
│              LOWER(JSON_UNQUOTE(JSON_EXTRACT(..., '$.name')))   │
│              LIKE '%john%' OR ...                               │
│            )                                                     │
│  - Render _card_list.html.twig                                  │
│  - Include metadata (hasMore, totalCards)                       │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│        JavaScript receives HTML response                        │
│  - Parse HTML to DOM                                            │
│  - Extract metadata                                             │
│  - Append cards to container                                    │
│  - Update total count display                                   │
│  - Hide loading spinner                                         │
└─────────────────────────────────────────────────────────────────┘
```

## Infinite Scroll Flow

```
┌─────────────────────────────────────────────────────────────────┐
│      User scrolls down to near bottom of page                   │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│      Intersection Observer detects scroll trigger               │
│  - rootMargin: 100px (triggers 100px before visible)            │
│  - Checks: hasMore && !isLoading                                │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│         loadCards(replace=false)                                │
│  - Keep existing cards                                          │
│  - Use current offset (10, 20, 30...)                           │
│  - Show loading spinner                                         │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│     AJAX GET /cards/api/search?q=john&offset=10                 │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│            CardController::apiSearch()                          │
│  - Fetch cards 11-20                                            │
│  - Calculate hasMore flag                                       │
│  - Render _card_list.html.twig                                  │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│        JavaScript receives HTML response                        │
│  - Parse HTML to DOM                                            │
│  - Append NEW cards to existing cards                           │
│  - Update offset (10 -> 20)                                     │
│  - Update hasMore flag                                          │
│  - Hide loading spinner                                         │
│  - Continue observing scroll trigger                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│         User can scroll again to load more                      │
│  - Process repeats until hasMore = false                        │
└─────────────────────────────────────────────────────────────────┘
```

## Permission Flow (Enterprise Team Members)

```
┌─────────────────────────────────────────────────────────────────┐
│              searchAccessibleCardsForUser()                     │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                ┌─────────────┴─────────────┐
                │                           │
                ▼                           ▼
┌───────────────────────────┐   ┌───────────────────────────┐
│   No Account or           │   │   Enterprise Account      │
│   Non-Enterprise          │   │                           │
└───────────┬───────────────┘   └───────────┬───────────────┘
            │                               │
            ▼                               ▼
┌───────────────────────────┐   ┌───────────────────────────┐
│  Return owned cards only  │   │  Check team membership    │
└───────────────────────────┘   └───────────┬───────────────┘
                                            │
                          ┌─────────────────┴─────────────────┐
                          │                                   │
                          ▼                                   ▼
              ┌───────────────────────┐         ┌───────────────────────┐
              │  ADMIN Team Member    │         │  MEMBER Team Member   │
              └───────────┬───────────┘         └───────────┬───────────┘
                          │                                 │
                          ▼                                 ▼
              ┌───────────────────────┐         ┌───────────────────────┐
              │  Return ALL account   │         │  Return ASSIGNED      │
              │  owner's cards        │         │  cards only           │
              └───────────────────────┘         └───────────────────────┘
```

## Database Query for Search

```sql
-- Example SQL generated by CardRepository::searchByUser()
SELECT c.*
FROM cards c
WHERE c.user_id = :user
  AND c.status = 'active'
  AND (
    LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.content, '$.name'))) LIKE '%john%'
    OR LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.content, '$.email'))) LIKE '%john%'
    OR LOWER(JSON_UNQUOTE(JSON_EXTRACT(c.content, '$.company'))) LIKE '%john%'
    OR LOWER(c.slug) LIKE '%john%'
  )
ORDER BY c.created_at DESC
LIMIT 10 OFFSET 0;
```

## File Structure

```
app/
├── src/
│   ├── Controller/
│   │   └── CardController.php
│   │       ├── index() - Initial page load
│   │       └── apiSearch() - AJAX endpoint
│   ├── Service/
│   │   └── CardService.php
│   │       ├── searchAccessibleCardsForUser()
│   │       └── countAccessibleCardsForUser()
│   └── Repository/
│       └── CardRepository.php
│           ├── searchByUser()
│           └── countByUser()
└── templates/
    └── card/
        ├── index.html.twig - Main template with JS
        └── _card_list.html.twig - Partial for AJAX responses
```

## Key Features & Benefits

✅ **Performance**
- Loads only 10 cards at a time
- Reduces initial page load time
- Efficient database queries with proper indexing

✅ **User Experience**
- Real-time search results
- Smooth infinite scroll
- No page refreshes
- Loading indicators for feedback

✅ **Security**
- Parameterized SQL queries
- CSRF token protection
- Team permission enforcement
- XSS prevention via Twig escaping

✅ **Maintainability**
- Separation of concerns
- Reusable templates
- Well-documented code
- Clean architecture

✅ **Responsive Design**
- Mobile-friendly (col-12)
- Tablet optimized (col-md-6)
- Desktop layout (col-md-6)
