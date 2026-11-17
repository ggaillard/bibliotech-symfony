# 🧾 Mémo A4 — les commandes de BiblioTech

> Toutes se lancent depuis `projetSymfony/`. `--help` après une commande donne ses options.

## Tests et qualité

| Commande | Effet |
|---|---|
| `php bin/phpunit --group=seance-03` | les tests d'une séance |
| `php bin/phpunit --group=seance-03 --filter=m2` | seulement la mission 3.2 |
| `php bin/phpunit --group=socle` | ce qui marchait au départ (doit rester vert) |
| `php bin/phpunit` | toute la suite |
| `vendor/bin/php-cs-fixer check --diff` · `fix` | vérifier · corriger le style |
| `composer audit` | dépendances avec des failles connues |

## Routes, conteneur, configuration

| Commande | Effet |
|---|---|
| `php bin/console debug:router` | toutes les routes (nom, verbe, URL) |
| `php bin/console debug:router gestion.livres.edit` | le détail d'une route |
| `php bin/console debug:autowiring Paginator` | les services qu'on peut demander en argument |
| `php bin/console debug:config security` | la configuration effective de la sécurité |
| `php bin/console debug:firewall` | les pare-feux |
| `php bin/console cache:clear` | vider le cache |

## Base de données

| Commande | Effet |
|---|---|
| `php bin/console make:entity Auteur` | créer ou compléter une entité |
| `php bin/console make:migration` | écrire la migration (à **relire**) |
| `php bin/console doctrine:migrations:migrate` | jouer les migrations en attente |
| `php bin/console doctrine:migrations:migrate prev` | revenir d'une migration |
| `php bin/console doctrine:migrations:status` | où en est la base |
| `php bin/console doctrine:schema:validate` | entités et base correspondent-elles ? |
| `php bin/console doctrine:fixtures:load` | ⚠️ vider puis charger les données de démo |
| `php bin/console dbal:run-sql "SELECT …"` | exécuter une requête SQL |

## Générateurs

| Commande | Crée |
|---|---|
| `make:controller CategorieController` | un contrôleur et sa vue |
| `make:form LivreType Livre` | une classe de formulaire |
| `make:factory --test Auteur` | une fabrique pour les tests |
| `make:fixtures UtilisateurFixtures` | une classe de données de démo |
| `make:user` · `make:security:form-login` · `make:registration-form` | les comptes, la connexion, l'inscription |
| `make:command app:jeton` | une commande console |

## Git

| Commande | Effet |
|---|---|
| `git status` · `git diff` | ce qui a changé |
| `git add -A && git commit -m "feat: …"` | enregistrer une étape |
| `git push` | envoyer sur GitHub (relance les tests dans Actions) |
| `git pull --rebase` | récupérer les commits faits ailleurs |
