# Qualité et mise en production

> **Séance 8** · Lecture 9 min · ▶️ [Faire la séance](../seances/seance-08.md)

## L'idée

Une application **en service** doit pouvoir évoluer sans casser. Trois filets :

1. Des **tests automatisés** — chaque comportement important est vérifié par du code ;
   un défaut corrigé reçoit un test qui empêche son retour (**non-régression**).
2. L'**intégration continue** — à chaque push, GitHub Actions rejoue les tests et vérifie le style.
3. Une **configuration de production** — rien de ce qui aide le développeur ne doit aider un attaquant.

**XSS** (*Cross-Site Scripting*) : une donnée saisie contient `<script>` et s'exécute dans le
navigateur des visiteurs (vol de session, redirection…). Twig échappe tout ce qui passe par `{{ }}` ;
le filtre `|raw` affiche **sans** échapper.

## Le code à reconnaître

```php
public function test_le_resume_ne_peut_pas_injecter_de_script(): void
{
    $livre = LivreFactory::createOne(['resume' => '<script>alert(1)</script>']);

    $this->get($this->url('livres.show', ['id' => $livre->getId()]));

    $html = $this->client->getResponse()->getContent();
    self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    self::assertStringContainsString('&lt;script&gt;', $html);
}
```
```dotenv
# .env.local sur le serveur — jamais versionné
APP_ENV=prod                # pas de barre de débogage, pas de page d'erreur détaillée, caches figés
APP_SECRET=…                # propre au serveur
DATABASE_URL="mysql://…"
```
```bash
composer install --no-dev --optimize-autoloader
composer dump-env prod
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console cache:clear
```

## Les pièges

- **`APP_ENV=dev` en production** : la page d'erreur et le *profiler* affichent le code, les requêtes et la configuration.
- **Un secret dans `.env`** : ce fichier est **versionné** (il contient les valeurs par défaut). Les vraies valeurs vont dans `.env.local`.
- **Le site pointé sur la racine du projet** au lieu de `public/` : `.env.local` téléchargeable.
- **Lancer les fixtures en production** : des comptes `password` en ligne.
- **Un test qui ne peut pas échouer** : vérifiez qu'il passe au rouge quand on casse le code.
- **Modifier `.env.local` après `dump-env`** : relancez `composer dump-env prod` et `cache:clear`.

## ✅ Auto-évaluation

1. Quelle différence entre `{{ x }}` et `{{ x|raw }}` ? Quand le second est-il acceptable ?
2. Qu'est-ce qu'un test de non-régression ? Donnez celui de la faille XSS.
3. Citez trois réglages de production et le risque que chacun évite.
4. Que vérifie le job « Style du code » de l'onglet Actions ?
5. Pourquoi le serveur web doit-il pointer sur `projetSymfony/public` ?

<details><summary>Réponses</summary>

1. `{{ x }}` échappe le HTML (affiché comme du texte), `|raw` l'interprète. Acceptable seulement pour un HTML produit par le code, jamais pour une saisie.
2. Un test qui échoue si le défaut corrigé réapparaît : créer un livre avec `<script>` dans le résumé et vérifier que la fiche l'affiche échappé.
3. `APP_ENV=prod` (pas de fuite d'informations par les pages d'erreur et le profiler) ; secrets dans `.env.local` non versionné (pas de mot de passe sur GitHub) ; site pointé sur `public/` (`.env.local` et le code inaccessibles) ; pas de fixtures (pas de comptes de démo).
4. Que le code respecte le style Symfony (`php-cs-fixer check`).
5. Seul `public/` doit être servi : le reste (dont `.env.local`, `vendor/`, `src/`) ne doit pas être accessible depuis le web.
</details>
