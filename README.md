# Gestion des Congés

Application web de gestion des demandes de congés, développée avec Symfony.

## Fonctionnalités

- **Authentification** : connexion et inscription publique (validation par un administrateur avant activation du compte)
- **Gestion des rôles** : Employé, Responsable, RH, Administrateur
- **Demandes de congé** : création, modification, envoi, annulation, suivi du statut
  - Brouillon
  - En attente d'approbation
  - Approuvé
  - Refusé
  - Annulé
  - En attente d'annulation
  - Annulé par RH
- **Soldes de congés** : suivi par type (congé annuel, maladie, sans solde, récupération, exceptionnel), avec répartition utilisé / planifié / disponible
- **Circuit d'approbation** : validation des demandes par les responsables, gestion des demandes d'annulation par les RH
- **Calendrier** : vue d'ensemble des congés
- **Administration** : gestion des types de congés, des jours fériés et des utilisateurs

## Stack technique

- **Backend** : Symfony 8.1 (PHP)
- **Frontend** : Twig, CSS (via Symfony AssetMapper — pas de build Node.js requis)
- **Base de données** : PostgreSQL 16
- **Environnement** : Docker Compose pour la base de données (`compose.yaml`) — le serveur d'application PHP se lance séparément (voir plus bas)

## Installation

### Prérequis

- PHP 8.4 ou supérieur
- Composer
- Docker (recommandé, pour la base de données) ou PostgreSQL en local
- [Symfony CLI](https://symfony.com/download) (optionnel, pour `symfony server:start` — sinon le serveur PHP intégré suffit, voir étape 7)

### Étapes

1. Cloner le dépôt :

```bash
git clone https://github.com/Hanae123458/gestion-conges.git
cd gestion-conges
```

2. Installer les dépendances (installe aussi les assets via les scripts Composer automatiques) :

```bash
composer install
```

3. Configurer les variables d'environnement : copier `.env` en `.env.local` et l'adapter (voir la section [Variables d'environnement](#variables-denvironnement) ci-dessous). Au minimum, définir `APP_SECRET`.

4. Lancer la base de données :

```bash
docker compose up -d
```

   Le service PostgreSQL s'appelle `database`. En développement, son port n'est pas fixé à `5432` sur l'hôte (mappage aléatoire défini dans `compose.override.yaml`) : récupérez le port réellement exposé avec :

```bash
docker compose port database 5432
```

   et ajustez `DATABASE_URL` dans `.env.local` en conséquence (ou utilisez une instance PostgreSQL locale sur le port `5432` par défaut).

5. Exécuter les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

6. (Optionnel) Charger les fixtures de données de test — crée un compte de démonstration par rôle (Admin, RH, Responsable, Employé), avec des soldes de congés initialisés, des types de congés et des jours fériés. ⚠️ Cette commande **purge** les données existantes :

```bash
php bin/console doctrine:fixtures:load
```

7. Lancer le serveur :

```bash
# Avec Symfony CLI (si installée)
symfony server:start

# Ou avec le serveur PHP intégré
php -S 127.0.0.1:8000 -t public
```

## Tests

```bash
php bin/phpunit
```

Les tests utilisent la même base de données que celle configurée dans `DATABASE_URL` (adaptée à l'environnement `test` via `.env.test`/`.env.test.local`) ; assurez-vous que les migrations y sont appliquées avant de lancer la suite.

## Variables d'environnement

Définies dans `.env` (valeurs par défaut) et surchargeables dans `.env.local` :

| Variable | Rôle | Valeur par défaut |
|---|---|---|
| `APP_SECRET` | Secret applicatif Symfony (à générer, vide par défaut) | _(vide)_ |
| `DATABASE_URL` | Connexion PostgreSQL | `postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8` |
| `MAILER_DSN` | Transport d'envoi d'e-mails | `null://null` (aucun e-mail réellement envoyé) |
| `MESSENGER_TRANSPORT_DSN` | Transport Messenger (asynchrone) | `doctrine://default?auto_setup=0` |

## Rôles et accès

| Rôle | Accès |
|---|---|
| Employé | Mes soldes, Mes demandes, Calendrier |
| Responsable | + Demandes d'approbation |
| RH | + Demandes d'annulation |
| Administrateur | Tableau de bord admin, Types de congés, Jours fériés, Utilisateurs |

## Auteur

Hanae Bouichnad — Étudiante en 4e année en développement digital et systèmes d'information (EMSI), 
projet réalisé dans le cadre d'un stage chez Webodrop pendant l'été 2026.
