# 🔧 Configuration des Tests - Hermio

Ce document décrit la configuration de la base de données de test et des services pour les tests fonctionnels et d'intégration.

## ✅ Configuration Complétée

### 1. Fichier `.env.test`
Créé avec les variables d'environnement nécessaires pour l'environnement de test :
- `APP_ENV=test`
- `DATABASE_URL` pour la base de données de test
- `MAILER_DSN=null://null` (transport nul pour les tests)

### 2. Configuration des Services (`config/services.yaml`)
Ajout de la section `when@test` pour :
- Rendre publics les services nécessaires aux tests
- Configurer les services Stripe avec des valeurs de test (dummy keys)

### 3. Base de Données de Test
- Base de données créée : `hermio_test`
- Migrations appliquées automatiquement

## 🚀 Commandes Utiles

### Créer la base de données de test
```bash
docker-compose exec app php bin/console doctrine:database:create --env=test --if-not-exists
```

### Appliquer les migrations sur la base de test
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

### Réinitialiser la base de données de test
```bash
docker-compose exec app php bin/console doctrine:database:drop --env=test --force
docker-compose exec app php bin/console doctrine:database:create --env=test
docker-compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

### Vider le cache de test
```bash
docker-compose exec app php bin/console cache:clear --env=test
```

## 📝 Notes Importantes

1. **Isolation des Tests** : Chaque test devrait nettoyer ses propres données pour éviter les conflits
2. **Transactions** : Les tests fonctionnels utilisent des transactions qui sont rollback automatiquement
3. **Services Publics** : Les services nécessaires aux tests sont rendus publics uniquement en environnement de test
4. **Stripe** : Les services Stripe utilisent des clés de test (dummy) pour éviter les appels API réels

## ⚠️ Problèmes Connus et Solutions

### Erreur : "Cannot autowire service StripeWebhookController"
**Solution** : La configuration dans `when@test` doit être placée après la configuration générale dans `services.yaml`

### Erreur : "Database does not exist"
**Solution** : Créer la base de données avec `doctrine:database:create --env=test`

### Erreur : "Service not public"
**Solution** : Ajouter le service dans la section `when@test` avec `public: true`

