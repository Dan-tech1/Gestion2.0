# TaskManager - Gestionnaire de Projets et Tâches

Application web de gestion de projets et tâches développée avec Symfony, permettant la collaboration d'équipes avec gestion des rôles et permissions.

## Technologies Utilisées

- **Backend** : Symfony 7.4.2, PHP 8.2.12, Doctrine ORM, MySQL
- **Frontend** : Twig, Tailwind CSS, JavaScript ES6+, Stimulus
- **Sécurité** : Symfony Security, Argon2i
- **Outils** : Composer, Symfony CLI, PHPUnit, Doctrine Migrations

## Prérequis

- PHP 8.2+ (avec extensions : pdo_mysql, mbstring, xml, zip, intl)
- MySQL 5.7+ ou MariaDB 10.3+
- Composer
- Symfony CLI (recommandé)
- Node.js & npm (pour les assets)

## Installation

1. **Cloner le projet** :
   ```bash
   git clone <repository-url>
   cd taskmanager
   ```

2. **Installer les dépendances** :
   ```bash
   composer install
   npm install
   ```

3. **Configurer l'environnement** :
   - Copier `.env.example` vers `.env`
   - Configurer `DATABASE_URL`, `APP_SECRET`

4. **Configurer la base de données** :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

5. **Compiler les assets** :
   ```bash
   npm run build
   ```

6. **Démarrer le serveur** :
   ```bash
   symfony serve
   ```
   Accessible sur `http://localhost:8000`
   ou symfony server:start

## Utilisation

- **Inscription/Connexion** : Via `/register` et `/login`
- **Dashboard** : Gestion des workspaces et projets
- **Rôles** : Admin, Project Manager, User
- **Fonctionnalités** : Créer workspaces, projects, tâches, issues ; uploader des images

## Fonctionnalités Essentielles

- Gestion des utilisateurs avec rôles
- Workspaces et projects organisés
- Assignation de tâches et suivi des statuts
- Signalement et résolution d'issues
- Upload d'images par projet
- Interface responsive avec Tailwind CSS

## Tests

```bash
php bin/phpunit
```

## Déploiement

- Configurer `APP_ENV=prod`
- Optimiser le cache : `php bin/console cache:clear --env=prod`
- Compiler les assets : `npm run build`

## Structure du Projet

- `src/` : Code source (Controller, Entity, Form, Repository, Security)
- `templates/` : Templates Twig
- `assets/` : JavaScript/CSS
- `config/` : Configuration Symfony
- `migrations/` : Migrations Doctrine
- `tests/` : Tests unitaires
