# Hermio – Speckit Constitution (Symfony 8 + Twig + Webpack Encore)

This constitution defines the architectural principles, development workflow, and governance rules for all AI-assisted code generation in the Hermio Symfony 8 project.

It ensures consistency, maintainability, and compliance with Symfony best practices.

---

# 1. Core Principles

## I. Clean Symfony Architecture (Mandatory)
All generated backend code MUST follow official Symfony 7/8 guidelines:

- Controllers must remain thin.
- Business logic goes into Services under `src/Service/`.
- Database access is done via Doctrine Repositories under `src/Repository/`.
- Entities must be located in `src/Entity/`.
- FormTypes under `src/Form/`.
- Validation through Symfony Validator.
- Event Subscribers in `src/EventSubscriber/`.
- Use dependency injection everywhere.
- No static services or global state.
- No business logic inside controllers.

**Controllers → Services → Repositories**  
is the mandatory architecture flow.

---

## II. Twig-Driven Frontend

Rendering layer MUST use:

- Twig templates (`templates/`)
- Twig components (`templates/components/`)
- Twig layouts, fragments, partials

Rules:
- Strictly no React/Vue/Svelte unless explicitly required.
- Twig templates must contain no business logic.
- Translations must use the Symfony Translator:
  ```
  {{ 'message.key'|trans }}
  ```

---

## III. Doctrine ORM as Single Source of Truth

- Entities define the domain model.
- Repositories manage all persistence operations.
- Migrations must reflect schema modifications.
- AI must avoid raw SQL unless justified by performance.

Doctrine conventions MUST be followed for:
- relations
- cascade behavior
- lifecycle callbacks
- naming consistency

---

## IV. Security & Authentication Rules

Security MUST use Symfony components:

- `security.yaml` configuration
- PasswordHasher for all user credentials
- Custom Authenticator (LoginFormAuthenticator or JSON Login API)
- Role hierarchy:  
  - `ROLE_USER`  
  - `ROLE_ADMIN`  
  - `ROLE_SUPER_ADMIN` (Hermio root control)

Authorization MUST use:

```
#[IsGranted('ROLE_ADMIN')]
```

or Voters.

No sessions or tokens must be implemented outside Symfony Security unless explicitly requested.

---

# 2. Asset Pipeline Governance (Webpack Encore)

Hermio uses **Webpack Encore** as the exclusive asset compilation tool.

## I. Directory Structure (REQUIRED)

```
assets/
  js/
    app.js
    pages/
    controllers/      # Stimulus (Symfony UX)
  styles/
    app.scss
    components/
  images/
public/build/
webpack.config.js
```

AI must NEVER introduce:
- Vite
- Parcel
- Laravel Mix
- Custom Webpack config

Encore is the only allowed build system.

---

## II. Entrypoint Rules

Global entrypoints:

```
assets/js/app.js
assets/styles/app.scss
```

Page-specific bundles must be under:

```
assets/js/pages/
```

Twig templates must load entrypoints using:

```
{{ encore_entry_link_tags('app') }}
{{ encore_entry_script_tags('app') }}
```

No hardcoded `<script>` tags unless required by 3rd-party libs.

---

## III. Stimulus (Symfony UX)

Stimulus controllers MUST be stored under:

```
assets/controllers/
```

Example controller:

```js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log("Hello from Stimulus");
    }
}
```

AI must not introduce frontend frameworks unless explicitly required.

---

## IV. Allowed Modifications in webpack.config.js

AI MAY:

- Add new entrypoints
- Enable Sass loader
- Enable PostCSS
- Enable Stimulus bridge
- Configure image/font loaders

AI MUST NOT:

- Rewrite entire config
- Replace Encore
- Remove default loaders
- Break compatibility with existing build steps

---

## V. Build Commands

AI must use:

```
npm run dev
npm run watch
npm run build
```

or

```
yarn encore dev
yarn encore production
```

`public/build/` must be listed in `.gitignore`.

---

# 3. Internationalization (i18n)

Hermio backend and frontend MUST use Symfony Translation:

```
translations/messages.en.yaml
translations/messages.fr.yaml
```

Rules:

- All UI text must be translatable.
- Validation messages must use translation keys.
- Backend exceptions/messages must use translator service.
- Locale detection based on request or user settings.

No inline strings in controllers or services unless wrapped in translator.

---

# 4. Coding Standards & Conventions

The AI MUST follow:

- PSR-12 coding style
- Symfony directory structure
- Strong typing everywhere
- Descriptive method names
- Proper namespaces

Required project layout:

```
src/
  Controller/
  Service/
  Entity/
  Repository/
  Security/
  EventSubscriber/
  Form/
templates/
assets/
config/
migrations/
tests/
```

---

# 5. Feature Workflow (Speckit)

Each feature MUST follow:

```
.specify/features/<id>-<slug>/
  spec.md
  plan.md
  data-model.md
  tasks.md
  research.md
  contracts/
```

AI must:

- Read the constitution
- Read the feature folder
- Generate code respecting Symfony conventions
- Avoid overwriting existing custom logic
- Add new code modularly

---

# 6. Code Review Requirements

Every generated or modified file MUST be checked for:

- No dead code
- No commented-out logic
- Controllers remain thin
- Business logic placed in services
- Doctrine queries optimized (no N+1)
- Translations for all visible text
- Proper security annotations

A PR must be rejected if it violates Symfony or Encore conventions.

---

# 7. Governance

- This constitution overrides all AI-generated suggestions.
- Any proposed modification must be documented in:
  ```
  CHANGELOG-CONSTITUTION.md
  ```
- Amendments require justification and compatibility validation.

---

**Version**: 1.0.0  
**Ratified**: 2025-01-01  
**Last Amended**: 2025-01-01
