# ==============================================================================
# Makefile pour DashMenu
#
# Gère les conteneurs Docker et les commandes de développement courantes.
# Les commandes sont exécutées à l'intérieur du conteneur 'app'.
# ==============================================================================

.PHONY: help up down restart logs shell rebuild install update test cc migrate make-migration yarn-install yarn-dev yarn-watch yarn-watch-stop yarn-build

# --- Aide ---------------------------------------------------------------------
help:
	@echo "Makefile pour DashMenu - Commandes disponibles:"
	@echo ""
	@echo "  Docker:"
	@echo "    \033[36mup\033[0m          - Démarre les conteneurs Docker en arrière-plan."
	@echo "    \033[36mdown\033[0m        - Arrête les conteneurs Docker."
	@echo "    \033[36mrestart\033[0m     - Redémarre les conteneurs Docker."
	@echo "    \033[36mrebuild\033[0m     - Reconstruit et redémarre les conteneurs Docker."
	@echo "    \033[36mlogs\033[0m        - Affiche les logs du conteneur 'app'."
	@echo "    \033[36mshell\033[0m       - Ouvre un shell bash dans le conteneur 'app'."
	@echo ""
	@echo "  Dépendances:"
	@echo "    \033[32minstall\033[0m     - Installe les dépendances, et lance les migrations."
	@echo "    \033[32mupdate\033[0m      - Met à jour les dépendances, compile les assets et lance les migrations."
	@echo ""
	@echo "  Symfony & Doctrine:"
	@echo "    \033[33mcc\033[0m          - Vide le cache de Symfony."
	@echo "    \033[33mmigrate\033[0m     - Exécute les migrations Doctrine."
	@echo "    \033[33mmake-migration\033[0m - Crée une nouvelle migration Doctrine."
	@echo ""
	@echo "  Tests & Qualité:"
	@echo "    \033[35mtest\033[0m        - Lance les tests PHPUnit (avec reset DB auto)."
	@echo "    \033[35mtest-db-reset\033[0m - Réinitialise la base de données de test."
	@echo "    \033[35mtest-db-init\033[0m  - Initialise la base de données de test."
	@echo "    \033[35mtest-db-fixtures\033[0m - Charge les fixtures de test."
	@echo "    \033[35mtest-db-migrate\033[0m - Lance les migrations de test."
	@echo "    \033[35mtest-db-check\033[0m - Vérifie la base de données de test."
	@echo "    \033[35mlint\033[0m        - Lance l'analyse statique avec PHPStan."
	@echo "    \033[35mcs-fix\033[0m      - Corrige le style du code avec PHP-CS-Fixer."
	@echo ""
	@echo "  Frontend (Yarn):"
	@echo "    \033[34myarn-install\033[0m- Installe les dépendances frontend."
	@echo "    \033[34myarn-dev\033[0m    - Compile les assets pour le développement."
	@echo "    \033[34myarn-watch\033[0m  - Compile et surveille les changements des assets."
	@echo "    \033[34myarn-watch-stop\033[0m - Arrête la surveillance des assets."
	@echo "    \033[34myarn-build\033[0m  - Compile les assets pour la production."
	@echo ""

# --- Commandes Docker ---------------------------------------------------------
up:
	@echo "🚀 Démarrage des conteneurs Docker..."
	docker compose up -d

down:
	@echo "🛑 Arrêt des conteneurs Docker..."
	docker compose down

restart: down up

rebuild:
	@echo "🏗️  Reconstruction des conteneurs Docker..."
	docker compose up -d --build

logs:
	@echo "📜 Affichage des logs du conteneur 'app'..."
	docker compose logs -f app

shell:
	@echo "💻 Connexion au conteneur 'app'..."
	docker compose exec app bash

# --- Commandes de Dépendances -------------------------------------------------
install:
	@echo "📦 Installation des dépendances Composer..."
	docker compose exec app composer install
	@echo "🗄️  Exécution des migrations..."
	$(MAKE) migrate
	@echo "🎨 Compilation des assets..."
	$(MAKE) yarn-install
	$(MAKE) yarn-build
	$(MAKE) cc

update:
	@echo "⬆️  Mise à jour des dépendances Composer..."
	docker compose exec app composer update
	@echo "🎨 Compilation des assets..."
	$(MAKE) yarn-build
	@echo "🗄️  Exécution des migrations..."
	$(MAKE) migrate

# --- Commandes Symfony & Doctrine ---------------------------------------------
cc:
	@echo "🧹 Nettoyage du cache Symfony..."
	docker compose exec app php bin/console cache:clear

migrate:
	@echo "🗄️  Exécution des migrations Doctrine..."
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

make-migration:
	@echo "📝 Création d'une nouvelle migration..."
	docker compose exec app php bin/console make:migration

# --- Commandes de Tests & Qualité ---------------------------------------------
test:
	@echo "✅ Préparation de la base de données de test..."
	@$(MAKE) test-db-reset
	@echo "✅ Lancement des tests PHPUnit..."
	docker compose exec app php vendor/bin/phpunit

test-db-reset:
	@echo "🔄 Réinitialisation de la base de données de test..."
	@docker compose exec app bash -c "cd /var/www/symfony && \
		bin/console doctrine:database:drop --env=test --force --if-exists && \
		bin/console doctrine:database:create --env=test && \
		bin/console doctrine:migrations:migrate --env=test --no-interaction && \
		bin/console doctrine:fixtures:load --env=test --no-interaction"

test-db-init:
	@echo "🚀 Initialisation de la base de données de test..."
	@docker compose exec app bash -c "cd /var/www/symfony && \
		bin/console doctrine:database:create --env=test --if-not-exists && \
		bin/console doctrine:migrations:migrate --env=test --no-interaction && \
#		bin/console doctrine:fixtures:load --env=test --no-interaction"

test-db-fixtures:
	@echo "📦 Chargement des fixtures de test..."
	@docker compose exec app bash -c "cd /var/www/symfony && \
		bin/console doctrine:fixtures:load --env=test --no-interaction"

test-db-migrate:
	@echo "🔄 Exécution des migrations de test..."
	@docker compose exec app bash -c "cd /var/www/symfony && \
		bin/console doctrine:migrations:migrate --env=test --no-interaction"

test-db-check:
	@echo "🔍 Vérification de la base de données de test..."
	@docker compose exec app bash -c "cd /var/www/symfony && \
		bin/console doctrine:query:sql 'SELECT DATABASE()' --env=test"

cs-fix:
	@echo "🎨 Correction du style de code avec PHP-CS-Fixer..."
	docker compose exec app vendor/bin/php-cs-fixer fix

lint:
	@echo "🔍 Analyse du code avec PHPStan..."
	docker compose exec app vendor/bin/phpstan analyse src


# --- Commandes Frontend (Yarn) ------------------------------------------------
yarn-install:
	@echo "📦 Installation des dépendances Yarn..."
	docker compose exec app bash -c "cd /var/www/symfony && yarn install"

yarn-dev:
	@echo "🎨 Compilation des assets en mode développement..."
	docker compose exec app bash -c "cd /var/www/symfony && NODE_OPTIONS=--openssl-legacy-provider yarn dev"

yarn-watch:
	@echo "👀 Surveillance des assets..."
	docker compose exec app bash -c "cd /var/www/symfony && NODE_OPTIONS=--openssl-legacy-provider yarn watch"

yarn-watch-stop:
	@echo "🛑 Arrêt de la surveillance des assets..."
	@docker compose exec app bash -c "pkill -f 'yarn watch' || true"
	@echo "✅ Processus yarn watch arrêtés"

yarn-build:
	@echo "📦 Compilation des assets pour la production..."
	docker compose exec app bash -c "cd /var/www/symfony && NODE_OPTIONS=--openssl-legacy-provider yarn build"

# --- Sync des vendors ---------------------------------------------
sync-vendors:
	@echo "🔄 Synchronisation des vendors Composer..."
	docker compose cp app:/var/www/symfony/vendor app/symfony
# Fin du Makefile