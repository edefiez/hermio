# Configuration Webpack - Guide

## ✅ Résumé des modifications

Le projet utilise **Webpack Encore** pour compiler les assets JavaScript et CSS.

### Modifications apportées :

1. **Dockerfile app** : Ajout de Node.js 20.x et npm
2. **Makefile** : Remplacement des commandes `yarn-*` par `npm-*` avec le bon chemin `/app`
3. **Configuration** : Les assets sont compilés dans `/app/public/build/`

---

## 📦 Commandes disponibles

### Installer les dépendances
```bash
make npm-install
```

### Compiler les assets (développement)
```bash
make npm-dev
```

### Compiler et surveiller les changements
```bash
make npm-watch
```

### Arrêter la surveillance
```bash
make npm-watch-stop
```

### Compiler pour la production
```bash
make npm-build
```

### Compatibilité (anciennes commandes yarn)
Les commandes `yarn-*` fonctionnent toujours grâce aux alias :
```bash
make yarn-install  # → npm-install
make yarn-dev      # → npm-dev
make yarn-watch    # → npm-watch
make yarn-build    # → npm-build
```

---

## 🔧 Configuration Webpack

Le fichier `webpack.config.js` définit deux points d'entrée :

- **app.js** : Assets principaux de l'application
- **home.js** : Assets spécifiques à la page d'accueil

### Fichiers générés

Les assets compilés se trouvent dans `/app/public/build/` :
```
app.b3ad094e.js
app.b75294ae.css
home.8f9b4035.js
home.43a58833.css
runtime.81003d5f.js
entrypoints.json
manifest.json
```

---

## 🐳 Architecture Docker

### Conteneur `app`
- **Image** : `php:8.4-fpm` + Node.js 20.x
- **Répertoire de travail** : `/app`
- **Outils installés** : PHP, Composer, Node.js, npm

### Volumes
Le dossier `/app` dans le conteneur est monté sur `./app` sur l'hôte, ce qui permet :
- Le hot-reload avec `npm run watch`
- La synchronisation automatique des fichiers compilés

---

## 🚀 Workflow de développement

### 1. Démarrer les conteneurs
```bash
make up
```

### 2. Installer les dépendances
```bash
make install
```

### 3. Lancer la surveillance des assets
```bash
make npm-watch
```

### 4. Développer
Les modifications dans `assets/` sont automatiquement compilées.

---

## 📝 Notes

### Port 33062
Le port configuré dans `docker-compose.override.yml` (33062) est pour la **base de données MySQL**, pas pour webpack.
```yaml
db:
  ports:
    - "33062:3306"
```

### Pourquoi npm et pas yarn ?
Node.js 20.x inclut npm par défaut. Yarn nécessiterait une installation séparée. npm est suffisant pour ce projet.

### Mode production
Pour compiler en mode production (minification, optimisation) :
```bash
make npm-build
```

---

## 🔍 Dépannage

### Les assets ne se compilent pas
1. Vérifier que Node.js est installé :
   ```bash
   docker exec hermio-app-1 node --version
   ```

2. Vérifier les dépendances :
   ```bash
   make npm-install
   ```

3. Nettoyer et recompiler :
   ```bash
   rm -rf app/public/build app/node_modules
   make npm-install
   make npm-dev
   ```

### Reconstruire le conteneur
Si Node.js n'est pas disponible :
```bash
docker compose build --no-cache app
docker compose up -d
```

---

## 📚 Documentation

- [Symfony Webpack Encore](https://symfony.com/doc/current/frontend.html)
- [Webpack Documentation](https://webpack.js.org/)
- [Node.js](https://nodejs.org/)

