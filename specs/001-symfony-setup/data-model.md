# Data Model: Initial Project Infrastructure Setup

**Feature**: 001-symfony-setup  
**Date**: 2025-12-07  
**Phase**: 1 - Design

## Overview

This infrastructure feature does **not introduce domain entities**. It establishes the Doctrine ORM foundation and directory structure for future business features to define their entities.

## Entity Structure

### No Domain Entities

This feature is infrastructure-only. No business entities are created or modeled.

### What IS Configured

1. **Doctrine ORM Integration**:
   - Entity manager configured
   - Repository pattern enabled
   - Migrations system ready
   - PSR-4 autoloading for `App\Entity` namespace

2. **Database Connection**:
   - Connection parameters in `.env`
   - Support for PostgreSQL (primary) and MySQL (alternative)
   - Connection pooling configured
   - Charset and collation set

3. **Directory Structure**:
   ```
   src/Entity/          # Empty, ready for domain entities
   src/Repository/      # Empty, ready for custom repositories
   migrations/          # Empty, ready for schema migrations
   ```

## Schema

### Current State

**No tables exist.** The database schema is empty after infrastructure setup.

### Future Entity Guidelines

Future features will define entities following Doctrine ORM best practices with PHP 8 attributes.

## Relationships

### No Relationships

Since no entities exist, no relationships are defined.

### Future Relationship Guidelines

When entities are added in future features, follow these Doctrine best practices:

- Use explicit cascade operations
- Default to lazy loading
- Use eager loading sparingly with JOIN fetch
- Document complex relationships

## Validation Rules

### No Validation Yet

Since no entities exist, no validation constraints are defined.

### Future Validation Guidelines

When entities are added, use Symfony Validator constraints.

## Migrations Strategy

### Initial State

```bash
# No migrations exist yet
php bin/console doctrine:migrations:list
# Output: No migrations to execute
```

### Future Migration Workflow

1. Generate migration after entity changes
2. Review generated SQL
3. Execute migration
4. Never edit executed migrations

### Migration Best Practices

- Test both up() and down() methods
- Add data migrations separately from schema migrations
- Use transactions for data integrity
- Document complex migrations with comments

## Database Configuration

### Connection Parameters

Located in `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci

    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App
```

### Environment Variables

```env
# .env
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

# .env.local (developer-specific)
DATABASE_URL="postgresql://hermio:secret@127.0.0.1:5432/hermio_dev?serverVersion=15&charset=utf8"
```

## Testing Data

### Fixtures

No fixtures exist yet. Future features can create fixtures using DoctrineFixturesBundle.

## Performance Considerations

### Query Optimization

Future entity development should follow best practices to avoid N+1 queries and optimize database access.

### Database Indexes

Future entities should add indexes for frequently queried columns.

## Summary

This infrastructure feature establishes the **foundation** for data modeling:

✅ **Configured**:
- Doctrine ORM integration
- Entity directories
- Migration system
- Database connection
- Repository pattern

❌ **NOT Created**:
- Domain entities
- Database tables
- Migrations
- Fixtures
- Validation rules

**Next Steps**: Future business features will define entities in `src/Entity/` following Symfony and Doctrine best practices.

---

**Note**: This data-model.md file serves as a template and guideline document for future entity development rather than documenting actual entities (since none exist in this infrastructure feature).

