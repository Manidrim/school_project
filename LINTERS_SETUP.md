# Configuration des Linters

Ce document détaille les linters ajoutés au projet et les commandes disponibles.

## 🔧 Linters installés

### Backend (PHP)

#### ✅ PHP-CS-Fixer (existant)
- **Localisation** : `api/.php-cs-fixer.dist.php`
- **Description** : Correction automatique du style de code PHP selon les standards Symfony
- **Configuration** : Standards Symfony

#### ✅ PHPStan (ajouté)
- **Localisation** : `api/phpstan.neon`
- **Description** : Analyse statique du code PHP (niveau 8)
- **Extensions** : 
  - `phpstan/phpstan-doctrine` : Support Doctrine
  - `phpstan/phpstan-symfony` : Support Symfony
- **Configuration** : Analyse des dossiers `src` et `tests`

#### ✅ PHPMD (ajouté)
- **Localisation** : `api/phpmd.xml`
- **Description** : Détection de code mess (complexité, violations, code mort)
- **Règles** : CleanCode, CodeSize, Controversial, Design, Naming, UnusedCode

### Frontend (React/Next.js)

#### ✅ ESLint (amélioré)
- **Localisation** : `pwa/.eslintrc.json`
- **Description** : Linter JavaScript/TypeScript
- **Configuration améliorée** :
  - `next/core-web-vitals`
  - `eslint:recommended` 
  - `@typescript-eslint/recommended`
- **Règles ajoutées** :
  - `no-unused-vars`: error
  - `no-console`: warn
  - `prefer-const`: error
  - `no-var`: error
  - `eqeqeq`: error
  - `curly`: error
  - `@typescript-eslint/no-explicit-any`: warn
  - `@typescript-eslint/no-unused-vars`: error
  - `@typescript-eslint/explicit-function-return-type`: warn

### Docker

#### ✅ Hadolint (existant, amélioré)
- **Description** : Linter pour Dockerfiles
- **Configuration** : Scan récursif de tous les Dockerfiles

## 📋 Commandes Make disponibles

### Commandes de linting

```bash
# Linters PHP
make lint-php-cs      # Correction automatique du style PHP
make lint-phpstan     # Analyse statique PHP
make lint-phpmd       # Détection de code mess PHP
make lint-php         # Tous les linters PHP

# Linters Frontend
make lint-eslint      # ESLint (vérification)
make lint-eslint-fix  # ESLint (correction automatique)
make lint-frontend    # Tous les linters frontend

# Linters Docker
make lint-hadolint    # Hadolint sur Dockerfiles
make lint-docker      # Tous les linters Docker

# Commandes globales
make lint             # Tous les linters
make fix              # Correction automatique + vérification couverture 100%
make fix-php          # Correction automatique PHP
make fix-frontend     # Correction automatique Frontend

# Commandes de tests et couverture
make test             # Tests PHPUnit
make test-coverage    # Tests PHPUnit avec rapport de couverture
make test-coverage-check # Tests PHPUnit + vérification 100% couverture
make test-frontend    # Tests frontend (Jest)
make test-frontend-coverage # Tests frontend avec rapport de couverture
make test-frontend-coverage-check # Tests frontend + vérification 100% couverture
make coverage-check   # Vérification 100% couverture pour tout le code
```

### Autres commandes utiles

```bash
# Docker
make build            # Construire les images Docker
make start            # Démarrer le projet
make stop             # Arrêter le projet
make restart          # Redémarrer le projet
make logs             # Voir les logs
make install          # Installer toutes les dépendances
make install-api      # Installer les dépendances API
make install-pwa      # Installer les dépendances PWA

# Symfony
make console          # Console Symfony
make test             # Tests PHPUnit
make test-coverage    # Tests avec couverture
make cache-clear      # Vider le cache
make migrations       # Migrations de base de données
make database-create  # Créer la base de test
make schema-validate  # Valider le schéma Doctrine
```

## 🚀 Pipelines GitHub Actions

### Workflows créés

#### 1. `lint-php.yml`
- **Déclenchement** : Push/PR sur fichiers PHP
- **Jobs** :
  - `php-cs-fixer` : Vérification du style de code
  - `phpstan` : Analyse statique
  - `phpmd` : Détection de code mess

#### 2. `lint-frontend.yml`
- **Déclenchement** : Push/PR sur fichiers TS/JS
- **Jobs** :
  - `eslint` : Linting JavaScript/TypeScript
  - `typescript` : Vérification TypeScript

#### 3. `lint-docker.yml`
- **Déclenchement** : Push/PR sur Dockerfiles
- **Jobs** :
  - `hadolint` : Linting Dockerfiles
  - `docker-compose-validate` : Validation des fichiers compose

#### 4. `ci.yml` (existant, modifié)
- **Modification** : Lint rapide lors des tests principaux + vérification couverture 100%
- **Amélioration** : Échec uniquement sur les erreurs critiques

#### 5. `coverage.yml` (nouveau)
- **Déclenchement** : Push/PR sur toutes branches
- **Job** : `coverage-check` - Vérification obligatoire de 100% de couverture
- **Upload** : Reports automatiques vers Codecov

## 📦 Dépendances ajoutées

### Backend (`api/composer.json`)
```json
{
  "require-dev": {
    "phpmd/phpmd": "^2.15",
    "phpstan/phpstan": "^1.10",
    "phpstan/phpstan-doctrine": "^1.3",
    "phpstan/phpstan-symfony": "^1.3"
  }
}
```

### Frontend (`pwa/package.json`)
```json
{
  "devDependencies": {
    "@typescript-eslint/eslint-plugin": "^6.21.0",
    "@typescript-eslint/parser": "^6.21.0"
  }
}
```

## 🛠️ Installation et utilisation

### 1. Installation des dépendances
```bash
make install
```

### 2. Lancement des linters
```bash
# Vérification complète
make lint

# Correction automatique
make fix
```

### 3. Linters spécifiques
```bash
# PHP uniquement
make lint-php
make fix-php

# Frontend uniquement  
make lint-frontend
make fix-frontend

# Docker uniquement
make lint-docker
```

## 📝 Configuration personnalisée

### PHPStan
- Modifier `api/phpstan.neon` pour ajuster le niveau d'analyse (1-8)
- Ajouter des exclusions dans `excludePaths`

### PHPMD
- Modifier `api/phpmd.xml` pour personnaliser les règles
- Exclure des règles spécifiques avec `<exclude name="..."/>`

### ESLint
- Modifier `pwa/.eslintrc.json` pour ajuster les règles
- Ajouter des règles dans la section `rules`

## ✅ Résumé des ajouts

### Nouveaux fichiers créés :
- `api/phpstan.neon` - Configuration PHPStan
- `api/phpmd.xml` - Configuration PHPMD  
- `api/tests/object-manager.php` - Helper PHPStan pour Doctrine
- `api/coverage-check.php` - Script de vérification couverture 100% PHP
- `make/lint.mk` - Commandes de linting et tests
- `.github/workflows/lint-php.yml` - Pipeline PHP + couverture
- `.github/workflows/lint-frontend.yml` - Pipeline Frontend + couverture
- `.github/workflows/lint-docker.yml` - Pipeline Docker
- `.github/workflows/coverage.yml` - Pipeline dédiée couverture 100%

### Fichiers modifiés :
- `api/composer.json` - Ajout des dépendances PHPStan et PHPMD
- `api/phpunit.xml.dist` - Configuration rapports de couverture
- `pwa/package.json` - Ajout dépendances TypeScript ESLint + scripts couverture
- `pwa/.eslintrc.json` - Configuration ESLint améliorée
- `pwa/jest.config.js` - Configuration Jest avec exigence 100% couverture
- `Makefile` - Inclusion des commandes de linting et couverture
- `make/docker.mk` - Amélioration des commandes Docker
- `make/symfony.mk` - Réorganisation des commandes (tests déplacés)
- `.github/workflows/ci.yml` - Ajout vérification couverture 100%

### Commandes principales ajoutées :
```bash
make fix              # Correction automatique + vérification 100% couverture
make coverage-check   # Vérification exclusive de la couverture à 100%
make test-coverage-check      # Tests PHP avec vérification 100% couverture
make test-frontend-coverage-check # Tests frontend avec vérification 100% couverture
```

## ⚠️ Exigence de couverture à 100%

### Configuration stricte
- **PHP** : PHPUnit + script personnalisé de vérification (`coverage-check.php`)
- **Frontend** : Jest avec `coverageThreshold` à 100% pour toutes les métriques
- **Pipeline CI** : Échec automatique si couverture < 100%

### Métriques surveillées
- **Lignes** : 100%
- **Fonctions/Méthodes** : 100% 
- **Branches** : 100%
- **Statements** : 100%