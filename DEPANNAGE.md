# 🚑 Dépannage — classé par ce que vous voyez à l'écran

> Cherchez (Ctrl+F) un morceau du message d'erreur. Toujours commencer par **lire le titre** de la
> page d'erreur Symfony : il dit presque toujours où regarder. La barre de débogage (en bas de page)
> donne aussi le contrôleur, la route et les requêtes SQL.
>
> Une erreur absente de cette page ? Ouvrez une *issue* avec le message complet : elle y sera ajoutée.

---

## Au démarrage du Codespace

### Le port 8000 affiche « Forbidden », « Not Found » ou une page Apache
Le projet n'est pas encore prêt (le script d'installation tourne encore) ou il a échoué.
Relancez-le, il ne casse rien (il ne recharge pas les données si la base contient déjà des livres) :
```bash
bash /workspace/.devcontainer/post-create.sh
```

### `SQLSTATE[HY000] [2002] Connection refused` · `getaddrinfo for mysql failed`
Le serveur MySQL démarre plus lentement que PHP. Attendez 30 secondes et recommencez.
Vérifiez dans `projetSymfony/.env` : `DATABASE_URL=…@mysql:3306/…` (le **nom du conteneur**, pas `127.0.0.1`).

### `Unable to write in the "…/var/cache/dev" directory` · `Permission denied` dans `var/`
Apache n'a pas le droit d'écrire dans `var/`. Depuis `projetSymfony/` :
```bash
chown -R www-data:www-data var && chmod -R ug+rwX var
```

### Une modification n'apparaît pas (configuration, traduction…)
```bash
php bin/console cache:clear
```

### Le Codespace s'est arrêté (inactivité)
Rouvrez-le depuis GitHub (**Code → Codespaces**). Vos fichiers et votre base sont conservés
tant que le Codespace n'est pas supprimé.

---

## Dans le navigateur

### `Invalid CSRF token` · `Jeton CSRF invalide` · le formulaire se réaffiche sans rien faire
Le jeton du formulaire manque ou ne correspond pas. Un formulaire Symfony (`form_start` … `form_end`)
l'ajoute tout seul ; un formulaire écrit à la main a besoin de
`<input type="hidden" name="_token" value="{{ csrf_token('…') }}">`, et le contrôleur doit vérifier
**le même identifiant** : `isCsrfTokenValid('…', …)`.

### `Unable to generate a URL for the named route "gestion.livres.index" as such route does not exist.`
Le nom de route n'existe pas (faute de frappe, ou route pas encore créée).
```bash
php bin/console debug:router                   # toutes les routes et leurs noms
php bin/console debug:router | grep gestion
php bin/console debug:router gestion.livres.index   # le détail d'une route
```

### `No route found for "GET http://…/gestion/livres/new"` alors que la route existe
Vérifiez `methods: [...]` dans l'attribut `#[Route]`, et l'ordre : `/{id}` doit avoir
`requirements: ['id' => '\d+']`, sinon il « attrape » le mot `new`.

### `No route found for "POST …": Method Not Allowed (Allow: DELETE)`
Le formulaire n'envoie pas le champ caché `<input type="hidden" name="_method" value="DELETE">`
(ou `PUT`). Symfony ne le lit que parce que `http_method_override: true` est dans `config/packages/framework.yaml`.

### `Attempted to load class "EntityType" from namespace "App\Controller\Gestion"`
Il manque une ligne `use …;` en haut du fichier : PHP cherche la classe dans le dossier courant.
Placez le curseur sur le nom de la classe, `Ctrl+.` (ou clic droit → *Import class*), ou copiez le
`use` depuis le guide.

### `Variable "livres" does not exist.`
La vue utilise une variable que le contrôleur ne lui a pas transmise :
`return $this->render('…', ['livres' => $livres]);`

### `Impossible to access an attribute ("nomComplet") on a null variable.`
Une relation est vide (un livre sans auteur). Écrivez `{{ livre.auteur.nomComplet ?? 'Auteur inconnu' }}`
ou testez avant : `{% if livre.auteur %}…{% endif %}`.

### `Unable to find template "livres/_carte.html.twig"`
Le chemin est relatif au dossier `templates/`. Vérifiez le nom du fichier et son dossier.

### `"App\Entity\Livre" object not found by "…EntityValueResolver"` (page 404)
La liaison automatique `show(Livre $livre)` n'a pas trouvé de livre avec l'`{id}` de l'URL.
C'est le comportement voulu pour un id inexistant.

### `403 Forbidden` · `Access Denied.` (à partir de la séance 5)
Vous êtes connecté avec un compte adhérent. Comptes de démonstration (mot de passe `password`) :
`admin@bibliotech.test` · `bibliothecaire@bibliotech.test` · `adherent@bibliotech.test`.
Le rôle de l'utilisateur connecté est visible dans la barre de débogage (icône 👤).

### `Plusieurs tentatives de connexion ont échoué, veuillez réessayer dans 1 minute.`
Cinq tentatives par minute, c'est voulu (séance 5). Attendez une minute.

### `Ce formulaire ne doit pas contenir de champs supplémentaires.`
Le navigateur a envoyé un champ que le formulaire ne déclare pas. C'est une protection de Symfony
(séance 5) ; si le champ est légitime, ajoutez-le dans la classe `…Type`.

---

## Dans la base de données

### `SQLSTATE[42S02]: Base table or view not found: 1146 Table 'bibliotech.auteur' doesn't exist`
La migration n'a pas été jouée : `php bin/console doctrine:migrations:migrate`.

### `make:migration` répond `No database changes were detected`
Doctrine ne voit aucune différence entre vos entités et la base : la migration a déjà été jouée,
ou l'entité n'a pas été enregistrée. `php bin/console doctrine:migrations:status` montre où vous en êtes.

### `SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'auteur_id'`
Une migration a échoué à mi-chemin (séance 2). MySQL n'annule pas ce qui a été fait avant
l'erreur : la colonne existe déjà, la relance bute dessus. Supprimez la colonne dans phpMyAdmin,
corrigez la migration, relancez. Ou repartez d'une base propre (voir plus bas).

### `SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row`
Vous supprimez une ligne encore référencée par une clé étrangère (une catégorie qui a des livres).
C'est la base qui vous protège : traitez le cas dans le contrôleur (séance 4).

### `SQLSTATE[23000]: … 1062 Duplicate entry '…' for key 'UNIQ_…'`
Deux livres avec le même ISBN. Ajoutez `#[UniqueEntity('isbn')]` sur l'entité (séance 3).

### `doctrine:schema:validate` signale que la base n'est pas synchronisée
Une entité a changé sans migration : `php bin/console make:migration`, relisez-la, puis `doctrine:migrations:migrate`.

### Je veux repartir d'une base propre
```bash
php bin/console doctrine:database:drop --force      # ⚠️ efface toutes les données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n
```
`doctrine:fixtures:load` seul **vide** les tables puis les remplit avec les données de démonstration.

---

## Dans les tests

### `Error: Class "App\Entity\Auteur" not found` · `Unable to generate a URL for the named route "categories.index"`
La mission correspondante n'est pas encore faite : le test **décrit** ce que vous devez construire.
C'est normal au début de chaque séance.

### `Failed asserting that '/categorie' is identical to '/categories'`
La route existe mais pas avec la bonne URL ou le bon nom. Relisez l'énoncé de la mission.

### `Aucun bouton « Enregistrer » sur la page.`
Les tests remplissent les formulaires comme un utilisateur : ils cherchent le bouton par son **texte**.
Utilisez exactement le texte demandé dans le guide (`Enregistrer`, `Mettre à jour`, `Supprimer`…).

### `Le formulaire n'a pas de champ « auteur ».`
Le champ n'existe pas dans votre formulaire, ou il porte un autre nom (`auteur_id` au lieu de `auteur`).

### `Failed asserting that the Response status code is 422`
Quand un formulaire est refusé, le contrôleur doit **réafficher** la page avec le formulaire
(`return $this->render('…', ['form' => $form])`) : Symfony met alors le code 422 tout seul.

### `Migration DoctrineMigrations\Version… failed during Execution` au lancement des tests
Les tests recréent leur base en jouant **vos** migrations : une migration qui ne fonctionne pas
fait échouer tous les tests. Lisez l'erreur SQL qui suit, corrigez la migration.

### Un test du groupe `socle` est devenu rouge
Vous avez cassé quelque chose qui marchait : c'est une **régression**. Annulez votre dernière
modification (`git diff` pour la voir) et cherchez ce qui a changé.

### Tous les tests de gestion passent en 302 ou 403 au lieu de 200 (séance 5)
C'est la protection qui fonctionne. Les tests se connectent avec un compte `ROLE_BIBLIOTHECAIRE` :
vérifiez votre `access_control` et votre `role_hierarchy` dans `config/packages/security.yaml`.

### Le test de la page 404 reste rouge alors que la page s'affiche (séance 1, bonus)
La page personnalisée n'est montrée que **sans** le mode debug. Pour la voir pendant le
développement : `/_error/404`.

---

## Git et GitHub

### `! [rejected] main -> main (fetch first)`
Le dépôt GitHub contient des commits que vous n'avez pas (modification faite sur le site) :
```bash
git pull --rebase && git push
```

### La croix rouge dans l'onglet Actions
Cliquez dessus, puis sur l'étape en rouge : le message est le même que dans votre terminal.
