# Quickstart Guide: Hermio Symfony 8 Development

**Feature**: 001-symfony-setup  
**Target Audience**: New developers joining the Hermio project  
**Estimated Setup Time**: 15-20 minutes

## Prerequisites

Before starting, ensure you have:

- **PHP 8.2+** installed
- **Composer 2.x** installed globally
- **Node.js 18+** and npm
- **Docker Desktop** (optional but recommended)
- **Symfony CLI** (optional)
- **Git** configured with your credentials

### Verify Prerequisites

```bash
php --version        # Should show 8.2.x or higher
composer --version   # Should show 2.x
node --version       # Should show 18.x or higher
npm --version        # Should show 9.x or higher
docker --version     # Should show recent version
```

## Quick Setup (5 minutes)

### 1. Clone and Setup

```bash
# Clone the repository
git clone <repository-url> hermio
cd hermio/app

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env .env.local
```

### 2. Configure Database

Edit `.env.local`:

```env
# PostgreSQL (recommended)
DATABASE_URL="postgresql://hermio:secret@127.0.0.1:5432/hermio?serverVersion=15&charset=utf8"

# Or MySQL
# DATABASE_URL="mysql://hermio:secret@127.0.0.1:3306/hermio?serverVersion=8.0"
```

### 3. Start Development Environment

**Option A: Docker (Recommended)**

```bash
# From project root
cd /Users/edefiez/Projects/Hermio
docker-compose up -d

# Database is now running
# Access app at: http://localhost:8000
```

**Option B: Local Services**

```bash
# Start your local PostgreSQL/MySQL
# Then start Symfony server
cd app
symfony server:start -d

# Or use PHP built-in server
php -S localhost:8000 -t public/
```

### 4. Compile Assets

```bash
cd app

# Development mode (unminified, with source maps)
npm run dev

# Or watch mode (auto-recompile on changes)
npm run watch
```

### 5. Verify Installation

```bash
cd app

# Check Symfony info
php bin/console about

# Run tests
php bin/phpunit

# Access the application
# Open: http://localhost:8000
```

**Expected output**: You should see the Symfony welcome page with debug toolbar.

## Project Structure Overview

```
hermio/
├── app/                        # Symfony application
│   ├── assets/                 # Frontend source files
│   │   ├── app.js              # Main JS entrypoint
│   │   ├── styles/app.css      # Main stylesheet
│   │   └── controllers/        # Stimulus controllers
│   ├── config/                 # Configuration
│   ├── src/                    # PHP application code
│   │   ├── Controller/         # HTTP controllers (thin!)
│   │   ├── Entity/             # Doctrine entities
│   │   ├── Repository/         # Database repositories
│   │   └── Service/            # Business logic
│   ├── templates/              # Twig templates
│   ├── public/                 # Web root
│   │   ├── index.php           # Front controller
│   │   └── build/              # Compiled assets (gitignored)
│   └── tests/                  # PHPUnit tests
├── specs/                      # Feature specifications
└── docker-compose.yml          # Docker services
```

## Development Workflow

### Daily Development

```bash
# 1. Start services (if using Docker)
docker-compose up -d

# 2. Start asset watch mode
cd app
npm run watch

# 3. Start Symfony server (if not using Docker)
symfony server:start

# 4. Code away!
# - Edit PHP in src/
# - Edit Twig in templates/
# - Edit JS/CSS in assets/
# - Assets recompile automatically
```

### Creating a New Feature

```bash
# 1. Create a controller
php bin/console make:controller MyFeatureController

# 2. Create a service
php bin/console make:service MyFeatureService

# 3. Create an entity (if needed)
php bin/console make:entity MyEntity

# 4. Create a migration
php bin/console make:migration

# 5. Apply migration
php bin/console doctrine:migrations:migrate

# 6. Create a test
php bin/console make:test MyFeatureTest
```

### Running Tests

```bash
cd app

# Run all tests
php bin/phpunit

# Run specific test
php bin/phpunit tests/Unit/MyTest.php

# Run with coverage
XDEBUG_MODE=coverage php bin/phpunit --coverage-html var/coverage
```

### Code Quality

```bash
cd app

# Check code style (PSR-12)
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style
vendor/bin/php-cs-fixer fix

# Run static analysis
vendor/bin/phpstan analyse src tests

# Clear cache
php bin/console cache:clear
```

## Common Tasks

### Add a New Page

1. **Create Controller**:

```php
// src/Controller/MyPageController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyPageController extends AbstractController
{
    #[Route('/my-page', name: 'app_my_page')]
    public function index(): Response
    {
        return $this->render('my_page/index.html.twig', [
            'title' => 'My Page',
        ]);
    }
}
```

2. **Create Template**:

```twig
{# templates/my_page/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ title }}{% endblock %}

{% block body %}
    <h1>{{ 'my_page.welcome'|trans }}</h1>
{% endblock %}
```

3. **Add Translation**:

```yaml
# translations/messages.en.yaml
my_page:
  welcome: "Welcome to My Page"

# translations/messages.fr.yaml
my_page:
  welcome: "Bienvenue sur Ma Page"
```

4. **Access**: http://localhost:8000/my-page

### Add Stimulus Controller

1. **Create Controller**:

```javascript
// assets/controllers/dropdown_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu'];
    
    toggle() {
        this.menuTarget.classList.toggle('hidden');
    }
}
```

2. **Use in Template**:

```twig
<div data-controller="dropdown">
    <button data-action="click->dropdown#toggle">Toggle</button>
    <div data-dropdown-target="menu" class="hidden">
        Menu content
    </div>
</div>
```

## Troubleshooting

### Issue: Assets not compiling

**Solution**:
```bash
# Clear cache
rm -rf public/build/
npm run dev

# Check webpack.config.js exists
ls -la webpack.config.js
```

### Issue: Database connection failed

**Solution**:
```bash
# Check DATABASE_URL in .env.local
# Verify database is running
docker-compose ps

# Create database if needed
php bin/console doctrine:database:create
```

### Issue: Symfony cache issues

**Solution**:
```bash
# Clear all caches
php bin/console cache:clear
php bin/console cache:warmup

# Remove cache directory
rm -rf var/cache/*
```

## Architecture Principles

### Controllers -> Services -> Repositories

✅ **DO**:
```php
class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $productService
    ) {}
    
    #[Route('/products/{id}')]
    public function show(int $id): Response
    {
        $product = $this->productService->getProduct($id);
        return $this->render('product/show.html.twig', [
            'product' => $product
        ]);
    }
}
```

❌ **DON'T**:
```php
// Business logic in controller - WRONG!
class ProductController extends AbstractController
{
    #[Route('/products/{id}')]
    public function show(int $id, ProductRepository $repo): Response
    {
        $product = $repo->find($id);
        $product->setViews($product->getViews() + 1);
        // ... more logic here
    }
}
```

## Next Steps

Once setup is complete:

1. **Explore the codebase**: Review `src/Controller/`, `templates/`, `assets/`
2. **Read the constitution**: `.specify/memory/constitution.md`
3. **Review architecture docs**: `specs/001-symfony-setup/`
4. **Build your first feature**: Follow the Speckit workflow
5. **Join the team**: Ask questions, pair program, contribute!

## Resources

- **Symfony Documentation**: https://symfony.com/doc/current/index.html
- **Doctrine Documentation**: https://www.doctrine-project.org/
- **Twig Documentation**: https://twig.symfony.com/doc/
- **Webpack Encore**: https://symfony.com/doc/current/frontend.html
- **Stimulus Handbook**: https://stimulus.hotwired.dev/
- **Project Constitution**: `.specify/memory/constitution.md`

---

**Need Help?** Check the troubleshooting section above or reach out to the team!

