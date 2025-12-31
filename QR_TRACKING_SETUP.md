# QR Code Tracking System - Setup & Testing Guide

This document provides instructions for setting up and testing the QR Code tracking system.

## Overview

The QR Code tracking system tracks every scan of a card's QR code and provides analytics:
- **All plans**: Scans are tracked automatically when cards are viewed
- **Enterprise plan**: Full analytics dashboard with graphs and statistics
- **Pro plan**: Upgrade prompt to access analytics
- **Free plan**: Normal dashboard without analytics

## Setup Instructions

### 1. Install Dependencies

```bash
# Install npm dependencies (includes Chart.js)
cd app
npm install

# Install PHP dependencies (if needed)
composer install
```

### 2. Run Database Migrations

```bash
# Run migrations to create card_scans table
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### 3. Build Frontend Assets

```bash
# Build assets with webpack
cd app
npm run build

# Or for development with watch mode
npm run watch
```

## Testing Instructions

### Unit Tests

Run the unit tests for the new services:

```bash
# Run all unit tests
docker compose exec app php bin/phpunit tests/Unit/

# Run specific test files
docker compose exec app php bin/phpunit tests/Unit/Service/ScanAnalyticsServiceTest.php
docker compose exec app php bin/phpunit tests/Unit/Service/ScanTrackingServiceTest.php
```

### Manual Testing

#### 1. Test Scan Tracking

1. Create a test card (or use an existing one)
2. Visit the public card URL: `http://localhost/c/{slug}?k={access_key}`
3. Refresh the page multiple times to generate scans
4. Check the database to verify scans are being tracked:

```bash
docker compose exec app php bin/console dbal:run-sql "SELECT * FROM card_scans ORDER BY scanned_at DESC LIMIT 10;"
```

#### 2. Test Enterprise Plan Analytics

1. Set a user's account to Enterprise plan:

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE accounts SET plan_type = 'enterprise' WHERE user_id = 1;"
```

2. Login as that user and visit the dashboard: `http://localhost/dashboard`
3. Verify you see:
   - Total Scans metric
   - Tracked Cards metric
   - Average Scans/Day metric
   - Line chart showing scans per day
   - Table showing top scanned cards

#### 3. Test Pro Plan Upgrade Prompt

1. Set a user's account to Pro plan:

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE accounts SET plan_type = 'pro' WHERE user_id = 2;"
```

2. Login as that user and visit the dashboard: `http://localhost/dashboard`
3. Verify you see:
   - An upgrade prompt card with warning styling
   - "Unlock Advanced Analytics" message
   - "Upgrade to Enterprise" button linking to account plans

#### 4. Test Free Plan (No Analytics)

1. Set a user's account to Free plan (or create a new user):

```bash
docker compose exec app php bin/console dbal:run-sql "UPDATE accounts SET plan_type = 'free' WHERE user_id = 3;"
```

2. Login as that user and visit the dashboard: `http://localhost/dashboard`
3. Verify you see:
   - Normal dashboard without analytics section
   - No upgrade prompt
   - Standard dashboard metrics (cards, usage, etc.)

## Features Implemented

### Backend

1. **CardScan Entity** (`src/Entity/CardScan.php`)
   - Tracks each scan with timestamp
   - Stores anonymized IP address (privacy-friendly)
   - Stores user agent for analytics
   - Indexed for performance

2. **ScanTrackingService** (`src/Service/ScanTrackingService.php`)
   - Tracks scans automatically
   - Anonymizes IP addresses (IPv4 and IPv6)
   - Truncates user agent to 255 characters
   - Handles errors gracefully

3. **ScanAnalyticsService** (`src/Service/ScanAnalyticsService.php`)
   - Calculates total scans per user's cards
   - Generates daily scan data for charts
   - Identifies top performing cards
   - Fills missing days with zero values

4. **CardScanRepository** (`src/Repository/CardScanRepository.php`)
   - Efficient queries for analytics
   - Date range filtering
   - Aggregation by day
   - Top cards ranking

5. **Updated Controllers**
   - `PublicCardController`: Tracks scans on card view
   - `DashboardController`: Provides analytics data based on plan

### Frontend

1. **Dashboard Template** (`templates/admin/dashboard.html.twig`)
   - Analytics section for Enterprise users
   - Upgrade prompt for Pro users
   - Chart.js integration for visualizations
   - Responsive card-based layout

2. **JavaScript** (`assets/analytics.js`)
   - Renders line chart for daily scans
   - Configures Chart.js with proper styling
   - Handles date formatting
   - Responsive chart configuration

3. **Translations**
   - French translations in `translations/messages.fr.yaml`
   - English translations in `translations/messages.en.yaml`
   - All analytics UI text is translatable

### Database

1. **Migration** (`migrations/Version20251231133000.php`)
   - Creates `card_scans` table
   - Adds indexes for performance
   - Foreign key to cards table with cascade delete

## Privacy Considerations

The system implements privacy-friendly tracking:

- **IP Anonymization**: Only the first 3 octets (IPv4) or 4 groups (IPv6) are stored
- **No Personal Data**: No user accounts or emails are stored in scan records
- **Optional Metadata**: IP and user agent storage is optional
- **GDPR Compliant**: Anonymized data reduces privacy concerns

## Performance Considerations

- Indexes on `(card_id, scanned_at)` for efficient queries
- Scan tracking uses try-catch to avoid blocking card views
- Analytics queries use aggregation for efficiency
- Limited to 30 days of data by default to keep charts readable

## Future Enhancements

Potential improvements for future iterations:

1. **Geographic Analytics**: Use IP to determine country/region
2. **Device Analytics**: Parse user agent to determine device types
3. **Real-time Dashboard**: WebSocket updates for live scan tracking
4. **Export Functionality**: CSV/PDF export of analytics data
5. **Custom Date Ranges**: Allow users to select custom date ranges
6. **Card-specific Analytics**: Detailed view for individual cards
7. **Referrer Tracking**: Track where scans are coming from
8. **A/B Testing**: Compare different card designs

## Troubleshooting

### Chart Not Displaying

1. Check browser console for JavaScript errors
2. Verify Chart.js is loaded: `npm list chart.js`
3. Rebuild assets: `npm run build`
4. Clear Symfony cache: `php bin/console cache:clear`

### No Scans Being Tracked

1. Check PublicCardController is being used for card views
2. Verify migration was run: `php bin/console doctrine:migrations:status`
3. Check for errors in logs: `tail -f var/log/dev.log`
4. Test with a simple SQL query to see if scans are being inserted

### Analytics Not Showing

1. Verify user has Enterprise plan: Check `accounts` table
2. Check that user has cards: `SELECT * FROM cards WHERE user_id = ?`
3. Verify scans exist: `SELECT COUNT(*) FROM card_scans cs JOIN cards c ON cs.card_id = c.id WHERE c.user_id = ?`
4. Check controller is passing analytics data to template

## Support

For issues or questions about this implementation, please refer to:
- The test files for examples of usage
- The inline code documentation
- The Symfony documentation for general framework questions
