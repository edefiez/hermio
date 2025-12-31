# QR Code Tracking System - Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER FLOW                                │
└─────────────────────────────────────────────────────────────────┘

1. Someone scans QR code on business card
          ↓
2. Visits /c/{slug}?k={access_key}
          ↓
3. PublicCardController::show()
          ↓
4. ScanTrackingService::trackScan() ← Records scan event
          ↓
5. Card is displayed to visitor


┌─────────────────────────────────────────────────────────────────┐
│                      ANALYTICS FLOW                              │
└─────────────────────────────────────────────────────────────────┘

1. Card owner logs into dashboard
          ↓
2. DashboardController::index()
          ↓
3. Check user's plan type
          ├─── Enterprise → Get analytics data
          ├─── Pro       → Show upgrade prompt
          └─── Free      → Normal dashboard
          ↓
4. ScanAnalyticsService::getAnalyticsForUser()
          ├─── Get user's cards
          ├─── Query CardScanRepository
          ├─── Calculate total scans
          ├─── Generate daily chart data
          └─── Get top cards ranking
          ↓
5. Render dashboard.html.twig
          ↓
6. Chart.js renders interactive graph


┌─────────────────────────────────────────────────────────────────┐
│                      DATABASE SCHEMA                             │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐         ┌─────────────────┐
│   users      │         │   cards         │
├──────────────┤         ├─────────────────┤
│ id (PK)      │────┐    │ id (PK)         │
│ email        │    │    │ user_id (FK)    │◄───┐
│ ...          │    │    │ slug            │    │
└──────────────┘    │    │ content         │    │
                    │    │ ...             │    │
                    │    └─────────────────┘    │
                    │                            │
                    │                            │
┌──────────────┐    │                            │
│  accounts    │    │                            │
├──────────────┤    │                            │
│ id (PK)      │    │                            │
│ user_id (FK) │◄───┘                            │
│ plan_type    │  (FREE, PRO, ENTERPRISE)        │
│ ...          │                                 │
└──────────────┘                                 │
                                                 │
                                                 │
                    ┌─────────────────┐          │
                    │  card_scans     │          │
                    ├─────────────────┤          │
                    │ id (PK)         │          │
                    │ card_id (FK)    │──────────┘
                    │ scanned_at      │
                    │ ip_address      │ (anonymized)
                    │ user_agent      │
                    │ country         │
                    └─────────────────┘
                    
                    Indexes:
                    - idx_card_scanned_at (card_id, scanned_at)
                    - idx_scanned_at (scanned_at)


┌─────────────────────────────────────────────────────────────────┐
│                    SERVICE ARCHITECTURE                          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────┐
│  PublicCardController   │
└───────────┬─────────────┘
            │
            ├─── Uses ───► ScanTrackingService
            │                     │
            │                     ├─── Creates ───► CardScan entity
            │                     │
            │                     └─── Persists via EntityManager
            │
            └─── Displays ───► Card view template


┌─────────────────────────┐
│   DashboardController   │
└───────────┬─────────────┘
            │
            ├─── Uses ───► ScanAnalyticsService
            │                     │
            │                     ├─── Uses ───► CardScanRepository
            │                     │                    │
            │                     │                    ├─── getTotalScansForCards()
            │                     │                    ├─── getScansPerDayForCards()
            │                     │                    └─── getTopCardsByScans()
            │                     │
            │                     └─── Uses ───► CardService
            │                                          │
            │                                          └─── getAccessibleCardsForUser()
            │
            └─── Renders ───► Dashboard template
                                    │
                                    └─── Uses ───► Chart.js (analytics.js)


┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND INTEGRATION                          │
└─────────────────────────────────────────────────────────────────┘

app.js
  ├─── Imports Bootstrap
  ├─── Imports Chart.js library
  └─── Imports analytics.js
           │
           └─── Initializes Chart on page load
                      │
                      ├─── Reads window.analyticsData
                      ├─── Formats dates and data
                      ├─── Creates line chart
                      └─── Configures tooltips, colors, etc.


┌─────────────────────────────────────────────────────────────────┐
│                     PLAN-BASED ACCESS                            │
└─────────────────────────────────────────────────────────────────┘

FREE Plan Users:
  ├─── Scans tracked: ✅ Yes
  ├─── Can view analytics: ❌ No
  └─── Dashboard shows: Normal metrics

PRO Plan Users:
  ├─── Scans tracked: ✅ Yes
  ├─── Can view analytics: ❌ No
  └─── Dashboard shows: Upgrade prompt card

ENTERPRISE Plan Users:
  ├─── Scans tracked: ✅ Yes
  ├─── Can view analytics: ✅ Yes
  └─── Dashboard shows: Full analytics
              │
              ├─── Summary metrics (Total, Cards, Avg)
              ├─── 30-day line chart
              └─── Top cards leaderboard


┌─────────────────────────────────────────────────────────────────┐
│                      DATA PRIVACY                                │
└─────────────────────────────────────────────────────────────────┘

IP Address Anonymization:
  ├─── IPv4: 192.168.1.100 → 192.168.1.0
  └─── IPv6: 2001:0db8:85a3:0000:0000:8a2e:0370:7334
              → 2001:0db8:85a3:0000::

User Agent:
  └─── Truncated to 255 characters max

No Personal Data:
  ├─── No user identification
  ├─── No email addresses
  └─── Only card reference


┌─────────────────────────────────────────────────────────────────┐
│                     TESTING STRATEGY                             │
└─────────────────────────────────────────────────────────────────┘

Unit Tests (13 tests):
  ├─── ScanTrackingServiceTest (6 tests)
  │         ├─── Track scan without request
  │         ├─── Track scan with request
  │         ├─── IPv4 anonymization
  │         ├─── IPv6 anonymization
  │         └─── User agent truncation
  │
  └─── ScanAnalyticsServiceTest (7 tests)
            ├─── Analytics with no cards
            ├─── Analytics with cards
            ├─── Analytics for specific card
            ├─── Fill missing days
            └─── Data aggregation

Manual Testing:
  ├─── Test scan tracking on card view
  ├─── Populate test data command
  ├─── Test Enterprise plan analytics view
  ├─── Test Pro plan upgrade prompt
  └─── Test Free plan normal dashboard


┌─────────────────────────────────────────────────────────────────┐
│                   PERFORMANCE OPTIMIZATIONS                      │
└─────────────────────────────────────────────────────────────────┘

Database:
  ├─── Composite index on (card_id, scanned_at)
  ├─── Index on scanned_at for date queries
  └─── Cascade delete for cleanup

Queries:
  ├─── Aggregated queries (COUNT, GROUP BY)
  ├─── Date range filtering
  └─── No N+1 queries

Error Handling:
  ├─── Try-catch around tracking
  └─── Never block card view on error

Batch Processing:
  └─── Test data command flushes every 100 inserts
```
