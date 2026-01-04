# Intégration Bootstrap 5 - Documentation

## ✅ Résumé

Bootstrap 5 a été intégré dans le projet Hermio et remplace le CSS custom inline. Tous les assets sont compilés via Webpack Encore.

---

## 📦 Packages installés

```json
{
  "bootstrap": "^5.x",
  "@popperjs/core": "^2.x",
  "sass": "^1.x",
  "sass-loader": "^16.x"
}
```

### Installation
```bash
npm install bootstrap@5 @popperjs/core sass-loader sass --save-dev
```

---

## 🏗️ Architecture

### Fichiers créés/modifiés

1. **`/app/assets/styles/bootstrap-custom.scss`** (nouveau)
   - Import de Bootstrap via SASS
   - Styles custom complémentaires

2. **`/app/assets/app.js`** (modifié)
   - Import Bootstrap CSS (SCSS)
   - Import Bootstrap JS
   - Point d'entrée principal

3. **`/app/templates/base.html.twig`** (modifié)
   - Suppression du CSS inline
   - Utilisation des classes Bootstrap 5
   - Navbar responsive Bootstrap
   - Système d'alertes Bootstrap

---

## 🎨 Structure des assets

```
app/assets/
├── app.js                          → Point d'entrée JS
├── home.js                         → Page home
└── styles/
    ├── bootstrap-custom.scss       → Bootstrap + styles custom
    └── home.scss                   → Styles page home
```

### Compilation
```
app/assets/styles/bootstrap-custom.scss
    ↓ (SASS)
app/public/build/app.css (753 KB)
    ↓
Inclus dans base.html.twig via encore_entry_link_tags('app')
```

---

## 🔧 Configuration Bootstrap

### Import SASS (`bootstrap-custom.scss`)
```scss
@use "sass:map";
@import "~bootstrap/scss/bootstrap";

// Styles custom complémentaires
.auth-container { ... }
.profile-card { ... }
```

### Import JavaScript (`app.js`)
```javascript
// Import Bootstrap CSS
import './styles/bootstrap-custom.scss';

// Import Bootstrap JS (avec Popper)
import 'bootstrap';

console.log('Hermio app loaded with Bootstrap 5');
```

---

## 📱 Classes Bootstrap utilisées

### Layout
- `.container` - Container responsive (max-width: 1200px)
- `.bg-light` - Fond clair
- `.bg-dark` - Fond sombre
- `.my-4` - Margin Y (top/bottom)
- `.py-4` - Padding Y
- `.mt-5` - Margin top
- `.ms-auto` - Margin start auto (flex)
- `.mb-0` - Margin bottom 0

### Navbar
- `.navbar` - Navbar principale
- `.navbar-expand-lg` - Responsive à partir de lg
- `.navbar-light` - Thème clair
- `.bg-white` - Fond blanc
- `.shadow-sm` - Ombre légère
- `.navbar-brand` - Logo/Brand
- `.navbar-toggler` - Bouton mobile
- `.navbar-collapse` - Container collapse
- `.navbar-nav` - Liste navigation
- `.nav-item` - Item de navigation
- `.nav-link` - Lien de navigation

### Alerts
- `.alert` - Alerte de base
- `.alert-success` - Alerte succès
- `.alert-danger` - Alerte erreur
- `.alert-info` - Alerte info
- `.alert-dismissible` - Alerte avec bouton fermer
- `.fade` `.show` - Animation
- `.btn-close` - Bouton fermer

### Buttons
- `.btn` - Bouton de base
- `.btn-primary` - Bouton primaire
- `.btn-secondary` - Bouton secondaire
- `.text-white` - Texte blanc

### Text
- `.text-center` - Texte centré
- `.text-white` - Texte blanc

---

## 🎯 Template base.html.twig

### Avant (CSS inline)
```html
<style>
    body { margin: 0; ... }
    .navbar { background-color: #fff; ... }
    .container { max-width: 1200px; ... }
    /* 70 lignes de CSS inline */
</style>
```

### Après (Bootstrap)
```html
{% block stylesheets %}
    {{ encore_entry_link_tags('app') }}
{% endblock %}
```

### Navbar Bootstrap
```html
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a href="..." class="navbar-brand">Hermio</a>
        <button class="navbar-toggler" ... data-bs-toggle="collapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="...">Link</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
```

### Alertes avec fermeture
```html
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Message
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
```

---

## 🔨 Commandes utiles

### Compiler les assets
```bash
# Développement
make npm-dev

# Watch (auto-compilation)
make npm-watch

# Production (minifié)
make npm-build
```

### Vider le cache
```bash
make cc
# ou
php bin/console cache:clear
```

---

## 📊 Taille des fichiers

### Avant (CSS inline)
- base.html.twig : ~70 lignes de CSS
- Pas de JS Bootstrap
- Total : ~5 KB

### Après (Bootstrap compilé)
- app.css : 753 KB (non minifié, dev)
- bootstrap JS : 613 KB (non minifié, dev)
- app.js : 2.73 KB
- Total dev : ~1.35 MB

### Production (minifié)
```bash
make npm-build
```
- app.css : ~200 KB (minifié + gzip : ~25 KB)
- bootstrap JS : ~80 KB (minifié + gzip : ~25 KB)
- Total production : ~50 KB (gzippé)

---

## 🎨 Styles custom complémentaires

Les styles custom sont dans `bootstrap-custom.scss` :

```scss
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 70vh;
}

.auth-card {
    background: white;
    padding: 2.5rem;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    width: 100%;
    max-width: 450px;
}

.profile-card,
.activity-card {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 2rem;
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    transition: transform 0.2s;
    
    &:hover {
        transform: translateY(-5px);
    }
}
```

---

## 📱 Responsive

Bootstrap 5 inclut un système de breakpoints :

- **xs** : < 576px (mobile)
- **sm** : ≥ 576px (mobile paysage)
- **md** : ≥ 768px (tablette)
- **lg** : ≥ 992px (desktop)
- **xl** : ≥ 1200px (desktop large)
- **xxl** : ≥ 1400px (très large)

### Exemple navbar responsive
```html
<nav class="navbar navbar-expand-lg">
    <!-- Visible sur mobile uniquement -->
    <button class="navbar-toggler" data-bs-toggle="collapse">
        <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- Collapse sur mobile, visible sur lg+ -->
    <div class="collapse navbar-collapse">
        ...
    </div>
</nav>
```

---

## 🚀 Fonctionnalités Bootstrap activées

### JavaScript
- ✅ **Collapse** : Menu mobile
- ✅ **Alerts** : Alertes avec bouton fermer
- ✅ **Dropdowns** : Menus déroulants
- ✅ **Modals** : Fenêtres modales (disponibles)
- ✅ **Tooltips** : Info-bulles (disponibles)
- ✅ **Popovers** : Fenêtres contextuelles (disponibles)

### CSS
- ✅ **Grid system** : 12 colonnes responsive
- ✅ **Utilities** : Classes utilitaires (spacing, colors, etc.)
- ✅ **Components** : Navbar, alerts, cards, badges, etc.
- ✅ **Forms** : Formulaires stylisés
- ✅ **Buttons** : Boutons stylisés

---

## 🔍 Vérification

### 1. Vérifier que Bootstrap est chargé
Ouvrir la console du navigateur :
```javascript
// Vérifier Bootstrap JS
console.log(bootstrap);

// Vérifier Popper (dépendance Bootstrap)
console.log(Popper);
```

### 2. Tester le menu responsive
1. Ouvrir http://localhost:8010/
2. Réduire la fenêtre < 992px
3. Le hamburger menu doit apparaître
4. Cliquer dessus pour ouvrir/fermer

### 3. Tester les alertes
1. Déclencher une alerte flash
2. Le bouton "X" doit fermer l'alerte
3. Animation fade doit fonctionner

---

## 📚 Documentation Bootstrap

- [Bootstrap 5 Official](https://getbootstrap.com/)
- [Bootstrap Utilities](https://getbootstrap.com/docs/5.3/utilities/)
- [Bootstrap Components](https://getbootstrap.com/docs/5.3/components/)
- [Bootstrap Grid](https://getbootstrap.com/docs/5.3/layout/grid/)

---

## 🎉 Avantages

### ✅ Avant (CSS inline)
- ❌ CSS non réutilisable
- ❌ Difficile à maintenir
- ❌ Pas de système responsive complet
- ❌ Pas de composants JS

### ✅ Après (Bootstrap 5)
- ✅ Framework complet et éprouvé
- ✅ Système de grille responsive
- ✅ Composants JS interactifs
- ✅ Classes utilitaires puissantes
- ✅ Design cohérent
- ✅ Documentation complète
- ✅ Compilé et optimisé via Webpack

---

## 🔄 Migration des pages

Pour migrer d'autres templates vers Bootstrap :

1. **Supprimer les classes custom**
   ```html
   <!-- Avant -->
   <div class="auth-container">
   
   <!-- Après -->
   <div class="d-flex justify-content-center align-items-center min-vh-70">
   ```

2. **Utiliser les composants Bootstrap**
   ```html
   <!-- Cards -->
   <div class="card">
       <div class="card-body">...</div>
   </div>
   
   <!-- Forms -->
   <div class="mb-3">
       <label class="form-label">...</label>
       <input class="form-control" />
   </div>
   ```

3. **Utiliser les utilities**
   ```html
   <!-- Spacing -->
   <div class="mt-4 mb-3 px-2 py-3">
   
   <!-- Colors -->
   <div class="bg-primary text-white">
   
   <!-- Display -->
   <div class="d-flex justify-content-between">
   ```

---

**Bootstrap 5 est maintenant intégré et opérationnel ! 🎊**

