# 📖 Glossaire — déploiement

> Séance 8. Classé par ordre alphabétique.

**`APP_ENV`** — L'environnement : `dev` (barre de débogage, erreurs détaillées, recompilation
automatique), `test` (les tests), `prod` (rapide, muet sur ses erreurs).

**`APP_SECRET`** — Clé secrète propre à chaque serveur (signatures, jetons CSRF). Jamais versionnée,
jamais la même qu'en développement.

**Cache** — Symfony compile la configuration, les routes et les vues Twig dans `var/cache/`.
`cache:clear` le reconstruit ; en `prod`, rien n'est recompilé tout seul.

**`composer install --no-dev --optimize-autoloader`** — Installe uniquement les dépendances de
production (pas de PHPUnit, de *maker*, de fixtures), avec un chargement de classes optimisé.

**`composer dump-env prod`** — Fige les variables des fichiers `.env*` dans `.env.local.php` : plus
rapide, et rien à relire à chaque requête.

**`DATABASE_URL`** — L'adresse de la base : `mysql://utilisateur:motdepasse@hote:3306/base?serverVersion=8.0&charset=utf8mb4`.

**`.env` / `.env.local`** — `.env` est **versionné** et ne contient que des valeurs par défaut ;
`.env.local` (ignoré par Git) contient les vraies valeurs de la machine. Les variables d'environnement
du serveur l'emportent sur les deux.

**Intégration continue (CI)** — À chaque push, GitHub Actions installe le projet, lance les tests et
vérifie le style (`.github/workflows/tests.yml`).

**`public/` (racine web)** — Le seul dossier que le serveur web doit servir : `index.php`, `.htaccess`,
les fichiers publics. Le reste du projet ne doit pas être accessible.

**Recette** — La vérification, par le client, que l'application livrée fait ce qui était demandé.

**`.htaccess`** — Règles Apache de `public/` (paquet `symfony/apache-pack`) : toute URL est confiée à `index.php`.

**`security:hash-password`** — Calcule l'empreinte d'un mot de passe, pour créer un compte à la main.

**Secrets (coffre-fort)** — `secrets:set` chiffre les secrets de production dans le dépôt ; une
alternative à `.env.local` pour les équipes.
