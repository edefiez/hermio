# API Contracts

**Feature**: 001-symfony-setup  
**Status**: N/A for infrastructure feature

## Overview

This infrastructure setup feature does not define any API endpoints or contracts. This directory exists to maintain consistency with the Speckit structure.

## Future Contract Documentation

When business features add API endpoints, contracts will be documented here using:

- **REST APIs**: OpenAPI 3.0 specification (YAML)
- **GraphQL APIs**: GraphQL schema definition language

### Example REST Contract Structure

Future features will create files like:

```
contracts/
├── openapi.yaml          # Full OpenAPI 3.0 spec
├── users.yaml            # User endpoints
└── products.yaml         # Product endpoints
```

### Example OpenAPI Contract

```yaml
openapi: 3.0.0
info:
  title: Hermio API
  version: 1.0.0

paths:
  /api/users:
    get:
      summary: List all users
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: array
                items:
                  $ref: '#/components/schemas/User'

components:
  schemas:
    User:
      type: object
      properties:
        id:
          type: integer
        email:
          type: string
          format: email
        roles:
          type: array
          items:
            type: string
```

## Contract-First Development

Future API features should follow this workflow:

1. **Define contract** in this directory (OpenAPI/GraphQL schema)
2. **Review contract** with stakeholders
3. **Generate code** from contract (if using code generators)
4. **Implement endpoints** following contract
5. **Validate implementation** against contract (API testing)

## Testing Contracts

Recommended tools for contract testing:

- **Postman/Newman**: API testing and automation
- **Symfony API Platform**: REST/GraphQL with auto-generated docs
- **Swagger UI**: Interactive API documentation
- **Pact**: Consumer-driven contract testing

---

**Note**: This infrastructure feature establishes the directory structure. Business features will populate it with actual API contracts.

