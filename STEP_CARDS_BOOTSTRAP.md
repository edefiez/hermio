# Migration Step Cards vers Bootstrap - Documentation

## ✅ Modification effectuée

Les `step-card` custom ont été remplacés par des **cards Bootstrap** avec le système de grille responsive.

---

## 🔄 Changements

### Template `home/index.html.twig`

#### ❌ Avant (CSS custom)
```html
<div class="steps-grid">
    <div class="step-card">
        <div class="step-number">1</div>
        <h3>Titre</h3>
        <p>Description</p>
    </div>
    <!-- ... -->
</div>
```

#### ✅ Après (Bootstrap 5)
```html
<div class="row g-4">
    <div class="col-12 col-md-4">
        <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="step-number bg-primary text-white rounded-circle...">1</div>
                <h3 class="card-title h5">Titre</h3>
                <p class="card-text text-muted">Description</p>
            </div>
        </div>
    </div>
    <!-- ... -->
</div>
```

---

## 🎨 Classes Bootstrap utilisées

### Système de grille
- **`.row`** - Container de grille
- **`.g-4`** - Gap (gutter) de 1.5rem entre les colonnes
- **`.col-12`** - 12 colonnes (100% largeur) sur mobile
- **`.col-md-4`** - 4 colonnes (33.33%) à partir de md (≥768px)

### Cards
- **`.card`** - Card Bootstrap
- **`.h-100`** - Hauteur 100% (cards de même hauteur)
- **`.border-0`** - Pas de bordure
- **`.shadow-sm`** - Ombre légère

### Card body
- **`.card-body`** - Corps de la card
- **`.card-title`** - Titre de la card
- **`.card-text`** - Texte de la card

### Styles
- **`.text-center`** - Texte centré
- **`.bg-primary`** - Fond couleur primaire
- **`.text-white`** - Texte blanc
- **`.text-muted`** - Texte gris clair
- **`.rounded-circle`** - Cercle parfait
- **`.d-inline-flex`** - Display inline-flex
- **`.align-items-center`** - Alignement vertical
- **`.justify-content-center`** - Alignement horizontal
- **`.mb-3`** - Margin bottom
- **`.h5`** - Taille de titre h5

---

## 📱 Comportement responsive

### Mobile (< 768px)
```
┌─────────────────────┐
│                     │
│    Card 1 (100%)    │
│                     │
├─────────────────────┤
│                     │
│    Card 2 (100%)    │
│                     │
├─────────────────────┤
│                     │
│    Card 3 (100%)    │
│                     │
└─────────────────────┘
```

### Tablette/Desktop (≥ 768px)
```
┌──────┬──────┬──────┐
│      │      │      │
│ Card │ Card │ Card │
│  1   │  2   │  3   │
│(33%) │(33%) │(33%) │
└──────┴──────┴──────┘
```

---

## 🗑️ Code supprimé

### Fichier `home.scss`

Suppression de ~50 lignes de CSS custom :

```scss
// ❌ Supprimé
.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 3rem;
    // ...
}

.step-card {
    text-align: center;
    padding: 2rem;
    background: $white;
    border-radius: 1rem;
    // ...
    
    .step-number {
        // ...
    }
}
```

**Résultat** : `home.css` réduit de 33.5 KB à 30.5 KB 📉

---

## 🎯 Avantages de Bootstrap

### ✅ Avant (CSS custom)
- ❌ Code custom à maintenir
- ❌ Grid CSS non standard
- ❌ Breakpoints personnalisés

### ✅ Après (Bootstrap)
- ✅ Composants standard réutilisables
- ✅ Système de grille éprouvé
- ✅ Breakpoints Bootstrap cohérents
- ✅ Classes utilitaires puissantes
- ✅ Moins de CSS custom
- ✅ Plus facile à maintenir

---

## 🔧 Structure de la card

```html
<div class="col-12 col-md-4">
    <!-- Card container -->
    <div class="card h-100 text-center border-0 shadow-sm">
        
        <!-- Card body -->
        <div class="card-body">
            
            <!-- Step number (circle) -->
            <div class="step-number bg-primary text-white rounded-circle 
                        d-inline-flex align-items-center justify-content-center mb-3" 
                 style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: 700;">
                1
            </div>
            
            <!-- Title -->
            <h3 class="card-title h5">
                {{ 'home.how_it_works.step1.title'|trans }}
            </h3>
            
            <!-- Description -->
            <p class="card-text text-muted">
                {{ 'home.how_it_works.step1.description'|trans }}
            </p>
            
        </div>
    </div>
</div>
```

---

## 📊 Breakpoints Bootstrap

| Breakpoint | Classe | Largeur |
|------------|--------|---------|
| Extra small | (défaut) | < 576px |
| Small | `sm` | ≥ 576px |
| Medium | `md` | ≥ 768px |
| Large | `lg` | ≥ 992px |
| Extra large | `xl` | ≥ 1200px |
| Extra extra large | `xxl` | ≥ 1400px |

### Notre configuration
- **`col-12`** : 100% sur xs, sm (mobile)
- **`col-md-4`** : 33.33% sur md, lg, xl, xxl (tablette/desktop)

---

## 💡 Personnalisation du step-number

Le cercle du numéro utilise du style inline pour la taille :

```html
<div class="... bg-primary text-white rounded-circle ..." 
     style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: 700;">
    1
</div>
```

### Alternative (classe CSS custom)
Si vous préférez, vous pouvez créer une classe dans `bootstrap-custom.scss` :

```scss
.step-number-circle {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
    font-weight: 700;
}
```

Puis utiliser :
```html
<div class="step-number-circle bg-primary text-white rounded-circle ...">1</div>
```

---

## 🧪 Test du résultat

### 1. Accéder à la page
```
http://localhost:8010/
```

### 2. Section "How It Works"
- ✅ 3 cards affichées
- ✅ Cercles numérotés bleus
- ✅ Ombre légère sur les cards
- ✅ Cards de même hauteur

### 3. Tester le responsive
**DevTools** → Mode responsive

#### Mobile (< 768px)
- ✅ Cards empilées verticalement
- ✅ Pleine largeur
- ✅ Espacement entre les cards

#### Desktop (≥ 768px)
- ✅ 3 cards côte à côte
- ✅ Même hauteur
- ✅ Espacement égal

---

## 📝 Fichiers modifiés

1. **`/app/templates/home/index.html.twig`**
   - Remplacé `.steps-grid` par `.row .g-4`
   - Remplacé `.step-card` par `.col-12 .col-md-4` + `.card`
   - Utilisé les classes Bootstrap pour le styling

2. **`/app/assets/styles/home.scss`**
   - Supprimé `.steps-grid` (7 lignes)
   - Supprimé `.step-card` (43 lignes)
   - Économie : ~50 lignes de CSS

---

## 🎨 Autres cards à migrer ?

Vous pouvez appliquer le même pattern aux autres sections :

### Features Section
```html
<div class="row g-4">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <span class="feature-icon">🎴</span>
                <h3 class="card-title">...</h3>
                <p class="card-text">...</p>
            </div>
        </div>
    </div>
</div>
```

### Pricing Section
```html
<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card h-100 border-primary">
            <div class="card-header bg-primary text-white">Plan</div>
            <div class="card-body">...</div>
            <div class="card-footer">...</div>
        </div>
    </div>
</div>
```

---

## ✅ Résultat final

```
✅ Step cards migrées vers Bootstrap
✅ Système de grille col-12 col-md-4
✅ Cards responsive et uniformes
✅ CSS custom supprimé (-50 lignes)
✅ home.css réduit (30.5 KB vs 33.5 KB)
✅ Design cohérent avec Bootstrap
✅ Aucune erreur de compilation
✅ Cache vidé et prêt à tester
```

**Les step cards utilisent maintenant 100% Bootstrap ! 🎉**

