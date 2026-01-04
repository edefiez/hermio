# 🧪 Guide des Tests - Hermio

Ce document décrit la structure et l'exécution des tests pour l'application Hermio.

## 📁 Structure des Tests

Les tests sont organisés en trois catégories :

### Tests Unitaires (`tests/Unit/`)
Tests isolés qui vérifient le comportement des classes individuelles sans dépendances externes.

**Services testés** :
- ✅ `QuotaService` - Gestion des quotas par plan (FREE/PRO/ENTERPRISE)
- ✅ `TeamService` - Gestion des équipes et permissions
- ✅ `TeamInvitationService` - Création et acceptation d'invitations
- ✅ `CardService` - Création, mise à jour et suppression de cartes
- ✅ `AccountService` - Gestion des comptes et changements de plan
- ✅ `InvitationRateLimiter` - Limitation du taux d'invitations

**Enums testés** :
- ✅ `PlanType` - Quotas et limites par plan
- ✅ `TeamRole` - Permissions ADMIN vs MEMBER

**Total** : 50 tests unitaires

### Tests Fonctionnels (`tests/Functional/`)
Tests d'intégration qui vérifient le comportement des contrôleurs avec la base de données.

**Contrôleurs testés** :
- ⚠️ `CardController` - Routes de gestion des cartes (en cours de correction)
- ⚠️ `TeamController` - Routes de gestion d'équipe (en cours de correction)

**Note** : Les tests fonctionnels nécessitent une configuration supplémentaire de la base de données de test.

### Tests d'Intégration (`tests/Integration/`)
Tests qui vérifient l'interaction entre plusieurs services avec la base de données réelle.

**Services testés** :
- ⚠️ `TeamInvitationIntegrationTest` - Workflow complet d'invitation
- ⚠️ `QuotaServiceIntegrationTest` - Validation des quotas avec DB réelle

**Note** : Les tests d'intégration nécessitent une base de données de test configurée.

## 🚀 Exécution des Tests

### Tous les tests unitaires
```bash
docker-compose exec app php bin/phpunit tests/Unit/
```

### Tests unitaires avec détails
```bash
docker-compose exec app php bin/phpunit tests/Unit/ --testdox
```

### Un fichier de test spécifique
```bash
docker-compose exec app php bin/phpunit tests/Unit/Service/QuotaServiceTest.php
```

### Un test spécifique
```bash
docker-compose exec app php bin/phpunit tests/Unit/Service/QuotaServiceTest.php --filter testCanCreateContentWithFreePlanAndNoCards
```

### Tous les tests
```bash
docker-compose exec app php bin/phpunit tests/
```

### Avec couverture de code (nécessite Xdebug)
```bash
docker-compose exec app php bin/phpunit --coverage-html var/coverage
```

## ✅ Tests Actuellement Fonctionnels

### Tests Unitaires (50 tests)

#### QuotaService (9 tests)
- ✅ Vérification des quotas FREE (1 carte max)
- ✅ Vérification des quotas PRO (10 cartes max)
- ✅ Vérification des quotas ENTERPRISE (illimité)
- ✅ Validation des exceptions de quota dépassé
- ✅ Comptage de l'utilisation actuelle

#### TeamService (8 tests)
- ✅ Vérification des permissions de gestion d'équipe
- ✅ Changement de rôles (propriétaire uniquement)
- ✅ Suppression de membres (propriétaire uniquement)
- ✅ Révocation d'accès équipe lors du downgrade

#### TeamInvitationService (7 tests)
- ✅ Création d'invitations (Enterprise uniquement)
- ✅ Détection des invitations en double
- ✅ Génération de tokens sécurisés
- ✅ Validation des tokens expirés
- ✅ Validation des emails correspondants

#### CardService (6 tests)
- ✅ Création de cartes avec validation de quota
- ✅ Génération de slugs uniques
- ✅ Vérification d'accès aux cartes
- ✅ Mise à jour et suppression de cartes

#### AccountService (3 tests)
- ✅ Création de comptes par défaut (FREE)
- ✅ Changement de plan
- ✅ Révocation d'accès équipe lors du downgrade Enterprise

#### InvitationRateLimiter (3 tests)
- ✅ Limitation horaire (10 invitations/heure)
- ✅ Limitation quotidienne (50 invitations/jour)
- ✅ Validation des limites

#### PlanType (7 tests)
- ✅ Quotas par plan
- ✅ Vérification des plans illimités
- ✅ Noms d'affichage

#### TeamRole (7 tests)
- ✅ Permissions ADMIN vs MEMBER
- ✅ Capacités d'assignation de cartes
- ✅ Capacités de gestion de membres

## 🔧 Configuration Requise

### Base de données de test
Les tests fonctionnels et d'intégration nécessitent une base de données de test configurée dans `.env.test` :

```env
DATABASE_URL="postgresql://user:password@db:5432/hermio_test?serverVersion=16&charset=utf8"
```

### Services Symfony
Les services doivent être publics dans l'environnement de test ou accessibles via le conteneur.

## 📊 Statistiques

**Tests unitaires** : 50 tests, tous fonctionnels ✅  
**Tests fonctionnels** : 9 tests, en cours de correction ⚠️  
**Tests d'intégration** : 5 tests, en cours de correction ⚠️

**Total** : 64 tests créés

## 🐛 Problèmes Connus

1. **Tests fonctionnels** : Nécessitent une configuration supplémentaire pour accéder aux services Symfony
2. **Tests d'intégration** : Nécessitent une base de données de test configurée et accessible
3. **MailerInterface** : Service non public dans l'environnement de test

## 📝 Ajout de Nouveaux Tests

### Structure d'un test unitaire
```php
<?php

namespace App\Tests\Unit\Service;

use App\Service\MyService;
use PHPUnit\Framework\TestCase;

class MyServiceTest extends TestCase
{
    private MyService $service;

    protected function setUp(): void
    {
        // Initialisation des mocks et du service
    }

    public function testMyFeature(): void
    {
        // Arrange
        // Act
        // Assert
    }
}
```

### Structure d'un test fonctionnel
```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testMyRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/my-route');
        
        $this->assertResponseIsSuccessful();
    }
}
```

## 🎯 Prochaines Étapes

1. ✅ Corriger les tests fonctionnels pour utiliser correctement le conteneur Symfony
2. ✅ Configurer la base de données de test pour les tests d'intégration
3. ✅ Ajouter des tests pour les contrôleurs restants
4. ✅ Ajouter des tests pour les Voters de sécurité
5. ✅ Ajouter des tests pour les Event Subscribers

