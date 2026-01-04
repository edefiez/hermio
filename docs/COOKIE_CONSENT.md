# Gestion du Consentement aux Cookies RGPD

Ce document décrit l'intégration et l'administration du gestionnaire de consentement aux cookies conforme au RGPD sur le site Hermio.

## Vue d'ensemble

Le site Hermio utilise le [Silktide Consent Manager](https://github.com/silktide/consent-manager) pour gérer le consentement aux cookies conformément au RGPD. Ce gestionnaire est gratuit, léger et entièrement personnalisable.

## Fonctionnalités

- ✅ **Conforme au RGPD** : Respecte les exigences du Règlement Général sur la Protection des Données
- 🎨 **Personnalisable** : Design adapté à la charte graphique du site
- 🌍 **Multilingue** : Interface en français
- ♿ **Accessible** : Navigation au clavier, focus trap et labels ARIA
- 📊 **Granulaire** : Gestion par catégories (Essentiels, Analytiques, Marketing)
- 🔄 **Révocable** : Les utilisateurs peuvent modifier leurs préférences à tout moment

## Architecture

### Fichiers principaux

```
app/
├── assets/
│   ├── consent-config.js                    # Configuration du gestionnaire
│   ├── silktide-consent-manager.js          # Bibliothèque JavaScript
│   └── styles/
│       ├── silktide-consent-manager.css     # Styles du gestionnaire
│       └── app.css                          # Styles de l'application
├── templates/
│   └── base.html.twig                       # Template de base (intégration)
└── public/
    └── build/                               # Assets compilés (générés)
```

### Intégration dans l'application

1. **JavaScript** : Le fichier `app/assets/app.js` importe :
   - La bibliothèque Silktide Consent Manager
   - Les styles du gestionnaire
   - La configuration personnalisée (`consent-config.js`)

2. **Template** : Le fichier `base.html.twig` charge les assets compilés via Webpack Encore :
   - `{{ encore_entry_link_tags('app') }}` pour les CSS
   - `{{ encore_entry_script_tags('app') }}` pour les JS

3. **Configuration** : Le fichier `consent-config.js` initialise le gestionnaire au chargement du DOM

## Configuration

### Types de consentement

Le gestionnaire gère trois catégories de cookies :

#### 1. Cookies Essentiels (obligatoires)
- **ID** : `essential`
- **Description** : Nécessaires au fonctionnement du site
- **État** : Toujours activés (requis)
- **Exemples** : Cookies de session, authentification, sécurité

#### 2. Cookies Analytiques (optionnels)
- **ID** : `analytics`
- **Description** : Analyse de l'utilisation du site
- **État par défaut** : Désactivés
- **Intégration** : Google Tag Manager / Analytics (gtag: 'analytics_storage')
- **Exemples** : Google Analytics, statistiques de visite

#### 3. Cookies Marketing (optionnels)
- **ID** : `marketing`
- **Description** : Publicité personnalisée
- **État par défaut** : Désactivés
- **Intégration** : Google Tag Manager (gtag: ['ad_storage', 'ad_user_data', 'ad_personalization'])
- **Exemples** : Publicités ciblées, remarketing

### Personnalisation des textes

Tous les textes sont en français et peuvent être modifiés dans `consent-config.js` :

```javascript
text: {
    prompt: {
        description: '<p>Nous utilisons des cookies...</p>',
        acceptAllButtonText: 'Tout accepter',
        rejectNonEssentialButtonText: 'Refuser les cookies non essentiels',
        preferencesButtonText: 'Préférences',
    },
    preferences: {
        title: 'Personnalisez vos préférences',
        description: '<p>Choisissez les cookies...</p>',
        saveButtonText: 'Enregistrer et fermer',
    },
}
```

### Personnalisation de l'apparence

#### Position du bandeau initial
```javascript
prompt: {
    position: 'bottomRight' // Options: 'center', 'bottomLeft', 'bottomCenter', 'bottomRight'
}
```

#### Position de l'icône cookie
```javascript
icon: {
    position: 'bottomLeft' // Options: 'bottomLeft', 'bottomRight'
}
```

#### Personnalisation CSS
Les styles peuvent être modifiés dans le fichier `assets/styles/silktide-consent-manager.css`

## Administration

### Modifier les préférences par défaut

Pour changer l'état par défaut d'une catégorie de cookies, modifiez le fichier `consent-config.js` :

```javascript
{
    id: 'analytics',
    label: 'Analytiques',
    defaultValue: true, // false par défaut, changez en true pour activer
}
```

### Ajouter une nouvelle catégorie

Pour ajouter une nouvelle catégorie de cookies :

1. Ouvrez `consent-config.js`
2. Ajoutez un nouvel objet dans le tableau `consentTypes` :

```javascript
{
    id: 'preferences',
    label: 'Préférences',
    description: 'Ces cookies mémorisent vos préférences sur le site.',
    defaultValue: false,
    onAccept: function() {
        console.log('Cookies de préférences acceptés');
        // Ajoutez votre code ici
    },
    onReject: function() {
        console.log('Cookies de préférences refusés');
        // Ajoutez votre code ici
    },
}
```

### Intégrer des scripts tiers

Pour charger automatiquement des scripts lorsque l'utilisateur accepte une catégorie :

```javascript
{
    id: 'analytics',
    label: 'Analytiques',
    scripts: [
        {
            src: 'https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID',
            async: true
        }
    ],
}
```

### Callbacks personnalisés

Utilisez les callbacks `onAccept` et `onReject` pour exécuter du code personnalisé :

```javascript
{
    id: 'marketing',
    onAccept: function() {
        // Charger les pixels de tracking
        loadMarketingPixels();
    },
    onReject: function() {
        // Supprimer les cookies marketing existants
        clearMarketingCookies();
    },
}
```

## Développement

### Prérequis

- Node.js et npm/yarn
- PHP 8.4+
- Symfony 8.0

### Installation des dépendances

```bash
# Dépendances JavaScript
cd app
npm install
# ou
yarn install
```

### Compilation des assets

```bash
# Développement (avec watch)
make yarn-watch

# Développement (simple)
make yarn-dev

# Production
make yarn-build
```

### Développement avec Docker

Si vous utilisez Docker :

```bash
# Démarrer les conteneurs
make up

# Compiler les assets
make yarn-build

# Voir les logs
make logs
```

## Tests de compatibilité

### Navigateurs testés

Le gestionnaire de consentement a été testé sur les navigateurs suivants :

- ✅ **Chrome** (dernière version)
- ✅ **Firefox** (dernière version)
- ✅ **Safari** (dernière version)
- ✅ **Edge** (dernière version)
- ✅ **Mobile Safari** (iOS)
- ✅ **Chrome Mobile** (Android)

### Tests d'accessibilité

- ✅ Navigation au clavier (Tab, Enter, Escape)
- ✅ Lecteurs d'écran (NVDA, JAWS, VoiceOver)
- ✅ Contraste des couleurs (WCAG AA)
- ✅ Focus visible

### Tests fonctionnels

Pour tester le gestionnaire de consentement :

1. **Test du bandeau initial** :
   - Ouvrez le site en navigation privée
   - Vérifiez que le bandeau de consentement apparaît
   - Testez les boutons "Tout accepter", "Refuser" et "Préférences"

2. **Test des préférences** :
   - Cliquez sur "Préférences"
   - Activez/désactivez chaque catégorie
   - Vérifiez que les choix sont enregistrés (LocalStorage)

3. **Test de persistance** :
   - Acceptez les cookies
   - Fermez le navigateur
   - Rouvrez le site
   - Vérifiez que le bandeau ne s'affiche plus

4. **Test de modification** :
   - Cliquez sur l'icône cookie en bas de page
   - Modifiez vos préférences
   - Vérifiez que les changements sont appliqués

5. **Test de révocation** :
   - Ouvrez les outils de développement (F12)
   - Console > Application > Local Storage
   - Supprimez les entrées liées au consentement
   - Rechargez la page
   - Vérifiez que le bandeau réapparaît

## Conformité RGPD

### Points de conformité

Le gestionnaire respecte les exigences du RGPD :

- ✅ **Consentement explicite** : L'utilisateur doit accepter activement
- ✅ **Granularité** : Choix par catégorie de cookies
- ✅ **Information claire** : Description de chaque catégorie
- ✅ **Révocable** : Possibilité de changer d'avis à tout moment
- ✅ **Pas de mur de cookies** : L'accès au site reste possible
- ✅ **Cookies essentiels uniquement** : Par défaut, seuls les cookies nécessaires

### Recommandations

1. **Politique de confidentialité** : Créez une page dédiée expliquant votre utilisation des cookies
2. **Mentions légales** : Ajoutez un lien vers votre politique de cookies
3. **Registre des traitements** : Documentez les cookies utilisés et leur finalité
4. **DPO** : Nommez un délégué à la protection des données si nécessaire

## Maintenance

### Mise à jour du gestionnaire

Pour mettre à jour la bibliothèque Silktide Consent Manager :

1. Téléchargez la dernière version depuis le [dépôt GitHub](https://github.com/silktide/consent-manager)
2. Remplacez les fichiers :
   - `app/assets/silktide-consent-manager.js`
   - `app/assets/styles/silktide-consent-manager.css`
3. Recompilez les assets : `make yarn-build`
4. Testez le fonctionnement

### Résolution de problèmes

#### Le bandeau ne s'affiche pas

1. Vérifiez que les assets sont compilés :
   ```bash
   make yarn-build
   ```

2. Vérifiez la console JavaScript (F12) pour les erreurs

3. Vérifiez que le template base.html.twig inclut :
   ```twig
   {{ encore_entry_script_tags('app') }}
   {{ encore_entry_link_tags('app') }}
   ```

#### Les préférences ne sont pas enregistrées

1. Vérifiez que le LocalStorage est activé dans le navigateur
2. Vérifiez que le domaine ne bloque pas les cookies
3. Testez en navigation privée

#### Conflit de styles

Si les styles du gestionnaire entrent en conflit avec votre CSS :

1. Modifiez `silktide-consent-manager.css`
2. Utilisez des sélecteurs plus spécifiques
3. Recompilez les assets

## Support

### Ressources

- [Documentation officielle Silktide](https://silktide.com/consent-manager/)
- [Dépôt GitHub](https://github.com/silktide/consent-manager)
- [Configurateur en ligne](https://silktide.com/consent-manager/install/)

### Questions fréquentes

**Q : Comment supprimer le lien de crédit "Get this consent manager for free" ?**

R : Dans `consent-config.js`, modifiez la section `text.preferences.creditLinkText` avec une chaîne vide ou votre propre texte.

**Q : Puis-je utiliser ce gestionnaire avec Google Analytics ?**

R : Oui, utilisez l'option `gtag` dans la configuration des cookies analytiques.

**Q : Comment tester le comportement sans cookies ?**

R : Utilisez le mode navigation privée de votre navigateur ou supprimez le LocalStorage via les outils de développement.

**Q : Le gestionnaire fonctionne-t-il avec Symfony Encore ?**

R : Oui, c'est exactement ce qui est configuré dans cette intégration.

## Changelog

### Version 1.0.0 (2026-01-04)

- ✅ Intégration initiale du Silktide Consent Manager v2.0
- ✅ Configuration en français
- ✅ Support des cookies essentiels, analytiques et marketing
- ✅ Intégration avec Webpack Encore
- ✅ Documentation complète
- ✅ Tests de compatibilité multi-navigateurs
