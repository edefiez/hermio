# Feature 005: Digital Card Management

## Overview

This feature enables users to create, manage, and share digital cards through unique URLs and QR codes. Cards are publicly accessible and subject to quota limits based on subscription plans.

## Quick Links

- [Specification](spec.md) - Complete feature specification
- [Quality Checklist](checklists/requirements.md) - Validation checklist
- [Contracts](contracts/) - API and interface contracts (to be created during planning)

## Key Features

1. **Card Creation**: Users can create digital cards with unique, URL-safe slugs
2. **Public Access**: Cards are accessible at `/c/<slug>` without authentication
3. **QR Code Generation**: Generate QR codes linking to card public URLs
4. **Card Management**: View, edit, and delete own cards
5. **Quota Enforcement**: Limits based on subscription plan (Free: 1, Pro: 10, Enterprise: unlimited)

## User Stories

- **P1**: Create Digital Card
- **P1**: View Public Card Page
- **P2**: Generate QR Code for Card
- **P2**: Manage Own Cards

## Status

- ✅ Specification complete
- ⏳ Planning phase (next step: `/speckit.plan`)

