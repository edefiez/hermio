# Feature 008: Secure Card Access

## Overview

This feature enhances the security of public card URLs by adding a non-guessable access key component. Currently, cards are accessible via `/c/<slug>` where the slug can be guessed. After implementation, cards will require a secure key: `/c/<slug>?k=<secure_key>`.

## Quick Start

See [tasks.md](./tasks.md) for the complete task breakdown.

## Summary

- **Total Tasks**: 70
- **MVP Tasks**: ~45 (Phases 1-7)
- **Parallelizable Tasks**: 32 tasks marked with [P]
- **User Stories**: 10 (US1-US10)

## Task Breakdown by User Story

| User Story | Priority | Tasks | Description |
|------------|----------|-------|-------------|
| US1 | P1 (MVP) | 6 | Auto-generate keys for new cards |
| US2 | P1 (MVP) | 7 | Validate keys on public access |
| US3 | P1 (MVP) | 5 | Display secure URLs in UI |
| US4 | P2 (MVP) | 7 | Manual key regeneration |
| US5 | P2 (MVP) | 7 | CLI commands for key management |
| US6 | P2 | 4 | Service layer validation |
| US7 | P3 | 5 | Backward compatibility & migration |
| US8 | P3 | 4 | Security enhancements (rate limiting, logging) |
| US9 | P3 | 4 | HMAC-derived keys alternative |
| US10 | P3 | 5 | Expiring signed URLs |

**Non-story tasks**: 16 (Setup, Foundational, Polish phases)

## MVP Scope (Recommended)

For fastest delivery, implement:
1. **Phase 1**: Setup (T001-T002) - SecureKeyGenerator service
2. **Phase 2**: Foundational (T003-T007) - Database schema
3. **Phase 3**: User Story 1 (T008-T013) - Auto-generate keys
4. **Phase 4**: User Story 2 (T014-T020) - Validate keys
5. **Phase 5**: User Story 3 (T021-T025) - Display secure URLs
6. **Phase 6**: User Story 4 (T026-T032) - Key regeneration
7. **Phase 7**: User Story 5 (T033-T039) - CLI migration tools

**Total MVP**: ~45 tasks

## Deferred Features (Optional)

These can be implemented later or omitted:
- **Phase 8**: US6 (Service validation refactor)
- **Phase 9**: US7 (Backward compatibility with warnings)
- **Phase 10**: US8 (Rate limiting & logging)
- **Phase 11**: US9 (HMAC alternative)
- **Phase 12**: US10 (Expiring signed URLs)

## Implementation Order

```
1. Setup (Phase 1) → 
2. Foundational (Phase 2) → BLOCKS ALL USER STORIES
3. User Stories 1-3 (P1 - MVP Core) → Parallel possible
4. User Stories 4-5 (P2 - Management) → Parallel possible
5. User Stories 6-10 (P3 - Enhancements) → Optional
6. Polish (Phase 13) → Documentation & translations
```

## Parallel Opportunities

Tasks marked with `[P]` can be executed in parallel:
- 32 parallelizable tasks across all phases
- Tests can run parallel with implementation
- Different user stories (after Phase 2) can be worked on by different developers
- Documentation tasks can run anytime

## Key Files Modified

### Core Implementation
- `/home/runner/work/hermio/hermio/app/src/Entity/Card.php` - Add publicAccessKey field
- `/home/runner/work/hermio/hermio/app/src/Service/CardService.php` - Key generation & validation
- `/home/runner/work/hermio/hermio/app/src/Controller/PublicCardController.php` - Key validation
- `/home/runner/work/hermio/hermio/app/templates/card/edit.html.twig` - Display secure URL

### New Files
- `/home/runner/work/hermio/hermio/app/src/Service/SecureKeyGenerator.php` - Key generation service
- `/home/runner/work/hermio/hermio/app/src/Command/RegenerateCardKeyCommand.php` - CLI key regeneration
- `/home/runner/work/hermio/hermio/app/src/Command/MigrateCardKeysCommand.php` - CLI migration
- `/home/runner/work/hermio/hermio/app/templates/error/403_invalid_key.html.twig` - Error page

## Testing Strategy

- **Unit Tests**: SecureKeyGenerator, Card entity, CardService
- **Functional Tests**: Public access validation, key regeneration, UI display
- **Integration Tests**: End-to-end card creation and access flows
- **Manual Tests**: QR code scanning, browser compatibility

## Security Considerations

1. Use `random_bytes()` for cryptographically secure key generation
2. Constant-time comparison to prevent timing attacks
3. 48-character keys (288 bits entropy)
4. Optional rate limiting to prevent brute force
5. Access logging for security monitoring
6. HTTPS required in production

## Migration Path

1. Deploy database changes (nullable column)
2. Deploy key generation for new cards
3. Run migration command for existing cards
4. Notify users about URL changes
5. Monitor access logs
6. Optional: Phase out backward compatibility after grace period

## Success Criteria

✅ New cards automatically receive secure access keys  
✅ Public URLs require valid key to display card (403 on invalid/missing key)  
✅ Card owners can view and copy secure URLs with keys  
✅ Card owners can regenerate keys when compromised  
✅ CLI tools available for bulk operations and migration  
✅ All existing cards can be migrated to use keys  
✅ QR codes include secure keys in URLs  

## Documentation

- [tasks.md](./tasks.md) - Complete task breakdown (this file)
- MIGRATION.md - Migration guide for users (to be created in Phase 13)
- SECURITY.md - Security best practices (to be created in Phase 13)

## Questions?

For implementation questions, refer to:
- Existing Card entity: `/home/runner/work/hermio/hermio/app/src/Entity/Card.php`
- Existing CardService: `/home/runner/work/hermio/hermio/app/src/Service/CardService.php`
- Feature 005 spec: `/home/runner/work/hermio/hermio/specs/005-digital-card/`
