# 📦 Guide de Chargement des Fixtures

Ce guide explique comment charger les fixtures de test pour l'application Hermio.

## 🚀 Chargement rapide

```bash
# Dans le conteneur Docker
docker-compose exec app php bin/console doctrine:fixtures:load

# Ou depuis le répertoire du projet
cd /Users/edefiez/Projects/Hermio
docker-compose exec app php bin/console doctrine:fixtures:load
```

## ⚠️ Attention

**Cette commande va supprimer toutes les données existantes** et recréer la base de données avec les fixtures.

## 📊 Données créées

### Utilisateurs et Comptes
- **20 comptes FREE** : `free_user_1@example.com` à `free_user_20@example.com`
- **20 comptes PRO** : `pro_user_1@example.com` à `pro_user_20@example.com`
- **20 comptes ENTERPRISE** : `enterprise_user_1@example.com` à `enterprise_user_20@example.com`

**Mot de passe pour tous** : `password123`

### Cartes
- **Comptes FREE** : 0-1 carte par compte (80% des comptes ont une carte)
- **Comptes PRO** : 1-10 cartes par compte (selon quota)
- **Comptes ENTERPRISE** : 5-30 cartes par compte (illimité)

### Membres d'Équipe (Enterprise uniquement)
- **2-8 membres** par compte Enterprise
- **Statuts** : 70% accepted, 20% pending, 10% declined/expired
- **Rôles** : Premier membre = ADMIN, autres = MEMBER
- **30% des membres acceptés** sont liés à des utilisateurs existants

### Assignations de Cartes (Enterprise uniquement)
- **0-3 assignations** par carte
- Assignées uniquement aux membres avec statut "accepted"
- Assignées par le propriétaire du compte

## 🔄 Réinitialisation

Pour réinitialiser complètement la base de données :

```bash
# Supprimer et recréer la base de données
docker-compose exec app php bin/console doctrine:database:drop --force
docker-compose exec app php bin/console doctrine:database:create
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Charger les fixtures
docker-compose exec app php bin/console doctrine:fixtures:load
```

## 🧪 Utilisation pour les tests

Les fixtures sont idéales pour :
1. **Tests manuels** : Avoir des données réalistes pour tester l'application
2. **Développement** : Tester les fonctionnalités avec différents types de comptes
3. **Démonstrations** : Présenter l'application avec des données complètes

## 📝 Notes

- Les emails sont générés avec Faker (données françaises)
- Les dates de création sont aléatoires sur les 6 derniers mois
- Les slugs de cartes sont uniques avec un identifiant unique
- Les tokens d'invitation sont générés de manière sécurisée

## 🔍 Vérification

Après le chargement, vous pouvez vérifier les données :

```bash
# Compter les utilisateurs
docker-compose exec app php bin/console doctrine:query:sql "SELECT COUNT(*) FROM users"

# Compter les comptes par plan
docker-compose exec app php bin/console doctrine:query:sql "SELECT plan_type, COUNT(*) FROM accounts GROUP BY plan_type"

# Compter les cartes
docker-compose exec app php bin/console doctrine:query:sql "SELECT COUNT(*) FROM cards"

# Compter les membres d'équipe
docker-compose exec app php bin/console doctrine:query:sql "SELECT COUNT(*) FROM team_members"

# Compter les assignations
docker-compose exec app php bin/console doctrine:query:sql "SELECT COUNT(*) FROM card_assignments"
```

