# 🧪 Roadmap de Tests - Hermio

**Date de création** : 2025-12-10  
**Version** : 1.0  
**Objectif** : Valider toutes les fonctionnalités de l'application Hermio étape par étape

---

## 📋 Table des matières

1. [Tests d'Infrastructure](#1-tests-dinfrastructure)
2. [Tests d'Authentification et Comptes Utilisateurs](#2-tests-dauthentification-et-comptes-utilisateurs)
3. [Tests de Gestion des Cartes](#3-tests-de-gestion-des-cartes)
4. [Tests de Plans et Abonnements](#4-tests-de-plans-et-abonnements)
5. [Tests Multi-User (Enterprise)](#5-tests-multi-user-enterprise)
6. [Tests de Branding](#6-tests-de-branding)
7. [Tests de Paiement Stripe](#7-tests-de-paiement-stripe)
8. [Tests de Sécurité](#8-tests-de-sécurité)
9. [Tests de Performance](#9-tests-de-performance)
10. [Tests d'Intégration](#10-tests-dintégration)

---

## 1. Tests d'Infrastructure

### 1.1 Configuration de base
- [ ] **T001** : Vérifier que la base de données est accessible et fonctionnelle
- [ ] **T002** : Vérifier que toutes les migrations sont appliquées
- [ ] **T003** : Vérifier que le cache Symfony fonctionne correctement
- [ ] **T004** : Vérifier que les services Docker sont démarrés (app, db, redis, nginx)
- [ ] **T005** : Vérifier que les logs sont accessibles et fonctionnels
- [ ] **T006** : Vérifier que les traductions (EN/FR) sont chargées correctement

### 1.2 Fixtures et Données de test
- [ ] **T007** : Charger les fixtures avec `php bin/console doctrine:fixtures:load`
- [ ] **T008** : Vérifier que 20 comptes FREE sont créés
- [ ] **T009** : Vérifier que 20 comptes PRO sont créés
- [ ] **T010** : Vérifier que 20 comptes ENTERPRISE sont créés
- [ ] **T011** : Vérifier que les cartes sont créées selon les quotas (FREE: max 1, PRO: max 10, ENTERPRISE: illimité)
- [ ] **T012** : Vérifier que les membres d'équipe Enterprise sont créés
- [ ] **T013** : Vérifier que les assignations de cartes sont créées pour Enterprise

---

## 2. Tests d'Authentification et Comptes Utilisateurs

### 2.1 Inscription
- [ ] **T014** : Accéder à la page d'inscription (`/register`)
- [ ] **T015** : Remplir le formulaire d'inscription avec des données valides
- [ ] **T016** : Vérifier que l'inscription crée un compte FREE par défaut
- [ ] **T017** : Vérifier que l'email de vérification est envoyé
- [ ] **T018** : Vérifier qu'un utilisateur non vérifié ne peut pas se connecter
- [ ] **T019** : Cliquer sur le lien de vérification dans l'email
- [ ] **T020** : Vérifier que le compte est activé après vérification
- [ ] **T021** : Tester l'inscription avec un email déjà existant (doit échouer)
- [ ] **T022** : Tester l'inscription avec un mot de passe trop faible (doit échouer)

### 2.2 Connexion
- [ ] **T023** : Accéder à la page de connexion (`/login`)
- [ ] **T024** : Se connecter avec un compte valide et vérifié
- [ ] **T025** : Vérifier la redirection vers la page d'accueil après connexion
- [ ] **T026** : Tester la connexion avec un email invalide (doit échouer)
- [ ] **T027** : Tester la connexion avec un mot de passe incorrect (doit échouer)
- [ ] **T028** : Tester la connexion avec un compte non vérifié (doit échouer)
- [ ] **T029** : Vérifier que "Se souvenir de moi" fonctionne (cookie remember_me)

### 2.3 Déconnexion
- [ ] **T030** : Se déconnecter depuis la page d'accueil
- [ ] **T031** : Vérifier que la session est détruite
- [ ] **T032** : Vérifier la redirection vers la page d'accueil après déconnexion

### 2.4 Réinitialisation de mot de passe
- [ ] **T033** : Accéder à la page de réinitialisation (`/reset-password`)
- [ ] **T034** : Demander une réinitialisation avec un email valide
- [ ] **T035** : Vérifier que l'email de réinitialisation est envoyé
- [ ] **T036** : Cliquer sur le lien dans l'email
- [ ] **T037** : Réinitialiser le mot de passe avec succès
- [ ] **T038** : Se connecter avec le nouveau mot de passe
- [ ] **T039** : Tester avec un token expiré (doit échouer)
- [ ] **T040** : Tester avec un token déjà utilisé (doit échouer)

### 2.5 Profil utilisateur
- [ ] **T041** : Accéder à la page de profil (`/profile`)
- [ ] **T042** : Modifier l'email du profil
- [ ] **T043** : Modifier le mot de passe depuis le profil
- [ ] **T044** : Vérifier que les modifications sont sauvegardées

---

## 3. Tests de Gestion des Cartes

### 3.1 Liste des cartes
- [ ] **T045** : Accéder à la page de liste des cartes (`/cards`)
- [ ] **T046** : Vérifier que les cartes du compte sont affichées
- [ ] **T047** : Vérifier l'affichage du quota (limite et utilisation)
- [ ] **T048** : Vérifier que le bouton "Créer une carte" est visible si quota disponible
- [ ] **T049** : Vérifier que le bouton "Créer une carte" est désactivé si quota atteint

### 3.2 Création de carte
- [ ] **T050** : Accéder à la page de création (`/cards/create`)
- [ ] **T051** : Créer une carte avec toutes les informations (nom, email, téléphone, entreprise, titre, bio, site web, LinkedIn, Twitter)
- [ ] **T052** : Vérifier que la carte est créée avec un slug unique
- [ ] **T053** : Vérifier que le quota est mis à jour après création
- [ ] **T054** : Tester la création avec un quota atteint (FREE: 1 carte, PRO: 10 cartes) - doit échouer
- [ ] **T055** : Vérifier que l'URL publique est générée (`/c/{slug}`)

### 3.3 Édition de carte
- [ ] **T056** : Accéder à la page d'édition d'une carte (`/cards/{id}/edit`)
- [ ] **T057** : Modifier les informations de la carte
- [ ] **T058** : Vérifier que les modifications sont sauvegardées
- [ ] **T059** : Vérifier que l'URL publique reste la même après modification

### 3.4 Suppression de carte
- [ ] **T060** : Supprimer une carte depuis la page d'édition
- [ ] **T061** : Vérifier que la carte est supprimée (soft delete)
- [ ] **T062** : Vérifier que le quota est libéré après suppression
- [ ] **T063** : Vérifier que l'URL publique retourne 404 après suppression

### 3.5 Carte publique
- [ ] **T064** : Accéder à une carte publique (`/c/{slug}`)
- [ ] **T065** : Vérifier que toutes les informations sont affichées correctement
- [ ] **T066** : Vérifier que les liens sociaux fonctionnent
- [ ] **T067** : Vérifier que le QR code est généré (`/cards/{id}/qr-code`)
- [ ] **T068** : Scanner le QR code et vérifier qu'il redirige vers la carte publique

### 3.6 Quotas par plan
- [ ] **T069** : Créer 1 carte avec un compte FREE (doit réussir)
- [ ] **T070** : Essayer de créer une 2ème carte avec un compte FREE (doit échouer)
- [ ] **T071** : Créer jusqu'à 10 cartes avec un compte PRO (doit réussir)
- [ ] **T072** : Essayer de créer une 11ème carte avec un compte PRO (doit échouer)
- [ ] **T073** : Créer plus de 10 cartes avec un compte ENTERPRISE (doit réussir, illimité)

---

## 4. Tests de Plans et Abonnements

### 4.1 Affichage du plan
- [ ] **T074** : Accéder à la page "Mon Plan" (`/account/my-plan`)
- [ ] **T075** : Vérifier que le plan actuel est affiché (FREE/PRO/ENTERPRISE)
- [ ] **T076** : Vérifier que le quota est affiché correctement
- [ ] **T077** : Vérifier que les options d'upgrade sont proposées

### 4.2 Changement de plan (sans paiement)
- [ ] **T078** : Tester le changement de plan depuis l'interface admin (si disponible)
- [ ] **T079** : Vérifier que le changement de plan met à jour le quota
- [ ] **T080** : Tester le downgrade d'ENTERPRISE vers PRO (doit révoquer l'accès équipe)
- [ ] **T081** : Tester le downgrade de PRO vers FREE (doit vérifier le quota)

---

## 5. Tests Multi-User (Enterprise)

### 5.1 Accès à la gestion d'équipe
- [ ] **T082** : Accéder à `/team` avec un compte FREE (doit rediriger vers upgrade)
- [ ] **T083** : Accéder à `/team` avec un compte PRO (doit rediriger vers upgrade)
- [ ] **T084** : Accéder à `/team` avec un compte ENTERPRISE (doit afficher la page)

### 5.2 Invitation de membres
- [ ] **T085** : En tant que propriétaire Enterprise, inviter un membre avec rôle MEMBER
- [ ] **T086** : En tant que propriétaire Enterprise, inviter un membre avec rôle ADMIN
- [ ] **T087** : Vérifier que l'email d'invitation est envoyé
- [ ] **T088** : Vérifier que l'invitation apparaît dans la liste avec statut "pending"
- [ ] **T089** : Tester l'invitation d'un email déjà invité (doit échouer)
- [ ] **T090** : Tester le rate limiting (10/heure, 50/jour) - doit échouer après limite

### 5.3 Acceptation d'invitation
- [ ] **T091** : Cliquer sur le lien d'invitation dans l'email
- [ ] **T092** : Vérifier que la page d'acceptation s'affiche (`/team/accept/{token}`)
- [ ] **T093** : Accepter l'invitation sans être connecté (doit rediriger vers login)
- [ ] **T094** : Se connecter et accepter l'invitation
- [ ] **T095** : Vérifier que le statut passe à "accepted"
- [ ] **T096** : Vérifier que le membre apparaît dans la liste de l'équipe
- [ ] **T097** : Tester avec un email différent de l'invitation (doit échouer)
- [ ] **T098** : Tester avec un token expiré (doit échouer)
- [ ] **T099** : Tester avec un token déjà utilisé (doit échouer)

### 5.4 Renvoi d'invitation
- [ ] **T100** : Renvoyer une invitation en attente depuis `/team`
- [ ] **T101** : Vérifier qu'un nouveau token est généré (token rotation)
- [ ] **T102** : Vérifier que la date d'expiration est réinitialisée
- [ ] **T103** : Vérifier que l'email est renvoyé

### 5.5 Gestion des rôles
- [ ] **T104** : En tant que propriétaire, changer le rôle d'un membre de MEMBER à ADMIN
- [ ] **T105** : En tant que propriétaire, changer le rôle d'un membre de ADMIN à MEMBER
- [ ] **T106** : Vérifier que les changements sont sauvegardés
- [ ] **T107** : Tester le changement de rôle en tant que membre (doit échouer)
- [ ] **T108** : Tester le changement de rôle du propriétaire (doit échouer)

### 5.6 Suppression de membres
- [ ] **T109** : En tant que propriétaire, supprimer un membre de l'équipe
- [ ] **T110** : Vérifier que le membre est retiré de la liste
- [ ] **T111** : Vérifier que les assignations de cartes sont supprimées (CASCADE)
- [ ] **T112** : Tester la suppression en tant que membre (doit échouer)
- [ ] **T113** : Tester la suppression du propriétaire (doit échouer)

### 5.7 Assignation de cartes
- [ ] **T114** : En tant que propriétaire/ADMIN, accéder à l'édition d'une carte
- [ ] **T115** : Vérifier que la section "Assignations" est visible
- [ ] **T116** : Assigner une carte à un membre MEMBER
- [ ] **T117** : Assigner une carte à plusieurs membres
- [ ] **T118** : Vérifier que les assignations apparaissent dans la liste des cartes
- [ ] **T119** : Retirer une assignation d'une carte
- [ ] **T120** : Tester l'assignation en tant que membre MEMBER (doit échouer)

### 5.8 Accès aux cartes selon les rôles
- [ ] **T121** : En tant que membre MEMBER, accéder à `/cards`
- [ ] **T122** : Vérifier que seules les cartes assignées sont visibles
- [ ] **T123** : En tant que membre ADMIN, accéder à `/cards`
- [ ] **T124** : Vérifier que toutes les cartes du compte sont visibles
- [ ] **T125** : En tant que membre MEMBER, essayer d'accéder à une carte non assignée (doit échouer)
- [ ] **T126** : En tant que membre ADMIN, accéder à toutes les cartes du compte (doit réussir)

### 5.9 Vue d'ensemble de l'équipe
- [ ] **T127** : Vérifier que le nombre de cartes assignées est affiché pour chaque membre
- [ ] **T128** : Vérifier que la dernière activité est affichée
- [ ] **T129** : Vérifier que les statuts d'invitation sont correctement affichés (pending/accepted/declined/expired)

### 5.10 Downgrade Enterprise
- [ ] **T130** : Tester le downgrade d'ENTERPRISE vers PRO
- [ ] **T131** : Vérifier que tous les membres d'équipe ont le statut "revoked"
- [ ] **T132** : Vérifier que les assignations de cartes sont préservées mais inaccessibles

---

## 6. Tests de Branding

### 6.1 Accès au branding
- [ ] **T133** : Accéder à `/branding` avec un compte FREE (doit rediriger vers upgrade)
- [ ] **T134** : Accéder à `/branding` avec un compte PRO (doit afficher la page)
- [ ] **T135** : Accéder à `/branding` avec un compte ENTERPRISE (doit afficher la page)

### 6.2 Configuration des couleurs
- [ ] **T136** : Modifier la couleur primaire
- [ ] **T137** : Modifier la couleur secondaire
- [ ] **T138** : Vérifier que les couleurs sont appliquées sur la carte publique
- [ ] **T139** : Réinitialiser les couleurs par défaut

### 6.3 Gestion du logo
- [ ] **T140** : Uploader un logo
- [ ] **T141** : Changer la position du logo (top-left, top-center, etc.)
- [ ] **T142** : Changer la taille du logo (small, medium, large)
- [ ] **T143** : Vérifier que le logo s'affiche sur la carte publique
- [ ] **T144** : Supprimer le logo

### 6.4 Template personnalisé (Enterprise uniquement)
- [ ] **T145** : Accéder à la section template avec un compte ENTERPRISE
- [ ] **T146** : Modifier le template personnalisé
- [ ] **T147** : Vérifier que le template est appliqué sur la carte publique
- [ ] **T148** : Tester avec un template invalide (doit afficher une erreur)
- [ ] **T149** : Réinitialiser le template par défaut

---

## 7. Tests de Paiement Stripe

### 7.1 Création de session de paiement
- [ ] **T150** : Accéder à la page d'upgrade (`/subscription/manage`)
- [ ] **T151** : Sélectionner le plan PRO
- [ ] **T152** : Vérifier que la session Stripe Checkout est créée
- [ ] **T153** : Vérifier la redirection vers Stripe Checkout

### 7.2 Webhooks Stripe
- [ ] **T154** : Simuler un webhook `checkout.session.completed` pour PRO
- [ ] **T155** : Vérifier que le compte est mis à jour vers PRO
- [ ] **T156** : Simuler un webhook `checkout.session.completed` pour ENTERPRISE
- [ ] **T157** : Vérifier que le compte est mis à jour vers ENTERPRISE
- [ ] **T158** : Simuler un webhook `customer.subscription.updated`
- [ ] **T159** : Simuler un webhook `customer.subscription.deleted` (annulation)
- [ ] **T160** : Vérifier que le compte est rétrogradé après annulation

### 7.3 Gestion des abonnements
- [ ] **T161** : Vérifier que l'abonnement est créé dans la base de données
- [ ] **T162** : Vérifier que le statut de l'abonnement est synchronisé
- [ ] **T163** : Vérifier que les dates de période sont correctes

---

## 8. Tests de Sécurité

### 8.1 Authentification
- [ ] **T164** : Tester l'accès à une page protégée sans être connecté (doit rediriger vers login)
- [ ] **T165** : Tester l'accès avec un token de session expiré
- [ ] **T166** : Tester la protection CSRF sur les formulaires
- [ ] **T167** : Tester l'injection SQL dans les formulaires (doit être bloquée)

### 8.2 Autorisation
- [ ] **T168** : Tester l'accès à `/cards/{id}/edit` d'une carte d'un autre utilisateur (doit échouer)
- [ ] **T169** : Tester l'accès à `/team` sans être propriétaire Enterprise (doit échouer)
- [ ] **T170** : Tester l'accès aux assignations sans être ADMIN (doit échouer)
- [ ] **T171** : Tester la modification d'un membre d'équipe sans être propriétaire (doit échouer)

### 8.3 Validation des données
- [ ] **T172** : Tester la création de carte avec des données invalides (doit échouer)
- [ ] **T173** : Tester l'invitation avec un email invalide (doit échouer)
- [ ] **T174** : Tester l'upload de logo avec un fichier non-image (doit échouer)
- [ ] **T175** : Tester l'upload de logo avec un fichier trop volumineux (doit échouer)

### 8.4 Rate Limiting
- [ ] **T176** : Tester le rate limiting des invitations (10/heure)
- [ ] **T177** : Tester le rate limiting des invitations (50/jour)
- [ ] **T178** : Vérifier que les messages d'erreur appropriés sont affichés

### 8.5 Tokens et sécurité
- [ ] **T179** : Vérifier que les tokens d'invitation sont uniques
- [ ] **T180** : Vérifier que les tokens d'invitation expirent après 7 jours
- [ ] **T181** : Vérifier que les tokens sont invalidés après utilisation
- [ ] **T182** : Vérifier que les tokens sont rotés lors du renvoi d'invitation

---

## 9. Tests de Performance

### 9.1 Requêtes base de données
- [ ] **T183** : Vérifier qu'il n'y a pas de requêtes N+1 sur `/cards`
- [ ] **T184** : Vérifier qu'il n'y a pas de requêtes N+1 sur `/team`
- [ ] **T185** : Vérifier que les requêtes sont optimisées avec des JOINs
- [ ] **T186** : Vérifier que les index sont présents sur les colonnes fréquemment interrogées

### 9.2 Cache
- [ ] **T187** : Vérifier que le cache Symfony fonctionne
- [ ] **T188** : Vérifier que le cache de rate limiting fonctionne
- [ ] **T189** : Tester la purge du cache

### 9.3 Temps de réponse
- [ ] **T190** : Vérifier que la page `/cards` se charge en moins de 500ms
- [ ] **T191** : Vérifier que la page `/team` se charge en moins de 500ms
- [ ] **T192** : Vérifier que la carte publique se charge en moins de 300ms

---

## 10. Tests d'Intégration

### 10.1 Workflow complet utilisateur
- [ ] **T193** : Inscription → Vérification email → Connexion → Création carte → Partage
- [ ] **T194** : Upgrade FREE → PRO → Création de 10 cartes → Upgrade ENTERPRISE → Création illimitée
- [ ] **T195** : ENTERPRISE → Invitation membre → Acceptation → Assignation carte → Accès membre

### 10.2 Workflow équipe Enterprise
- [ ] **T196** : Propriétaire invite ADMIN → ADMIN invite MEMBER → MEMBER reçoit carte assignée
- [ ] **T197** : ADMIN change rôle MEMBER → MEMBER perd accès aux cartes non assignées
- [ ] **T198** : Propriétaire supprime membre → Assignations supprimées → Carte inaccessible

### 10.3 Workflow paiement
- [ ] **T199** : Sélection plan → Stripe Checkout → Paiement → Webhook → Mise à jour compte
- [ ] **T200** : Annulation abonnement → Webhook → Downgrade → Révocation accès équipe

### 10.4 Commandes console
- [ ] **T201** : Exécuter `php bin/console app:team:cleanup-expired-invitations`
- [ ] **T202** : Vérifier que les invitations expirées sont marquées
- [ ] **T203** : Vérifier que la commande peut être planifiée (cron)

---

## 📊 Statistiques de Tests

**Total de tests** : 203  
**Tests complétés** : ___ / 203  
**Pourcentage** : ___%

### Par catégorie :
- Infrastructure : ___ / 13
- Authentification : ___ / 30
- Cartes : ___ / 29
- Plans/Abonnements : ___ / 8
- Multi-User : ___ / 51
- Branding : ___ / 17
- Paiement : ___ / 14
- Sécurité : ___ / 19
- Performance : ___ / 10
- Intégration : ___ / 12

---

## 📝 Notes de Test

**Environnement de test** :  
**Date de début** :  
**Date de fin** :  
**Testeur** :  

### Problèmes rencontrés :

1. 
2. 
3. 

### Améliorations suggérées :

1. 
2. 
3. 

---

## ✅ Validation finale

- [ ] Tous les tests critiques sont passés
- [ ] Aucun bug bloquant n'est présent
- [ ] La documentation est à jour
- [ ] Les performances sont acceptables
- [ ] La sécurité est validée
- [ ] L'application est prête pour la production

**Signature** :  
**Date** :

