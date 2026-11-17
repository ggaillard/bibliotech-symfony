# 7️⃣ Séance 7 — Une API REST pour le catalogue

> **Durée** 2 h · **Tests** `php bin/phpunit --group=seance-07` · **Fiche de cours** [API REST](../cours/07-api-rest.md)

## 🎯 Objectifs

- Exposer des données en **JSON** avec les bons **codes HTTP**
- Définir un **contrat** de sortie, indépendant de la table
- Protéger une écriture par **jeton** et par rôle

## 🎬 La situation

> **De :** M. Benali, service numérique de la communauté de communes
> **Objet :** catalogue dans l'application mobile
>
> Notre application mobile « Ma ville » affichera le catalogue des trois médiathèques du territoire.
> Il nous faut une **API** : la liste des livres (filtrable par catégorie et disponibilité) et la fiche
> d'un livre. Plus tard, notre logiciel d'acquisitions **créera** les livres commandés : il aura un
> compte bibliothécaire.

| Verbe | URL | Accès | Réponse |
|---|---|---|---|
| GET | `/api/livres?categorie=roman&disponible=1` | public | 200, liste paginée |
| GET | `/api/livres/{id}` | public | 200, ou 404 |
| POST | `/api/livres` | jeton **bibliothécaire** | 201 ; 401 sans jeton ; 403 adhérent ; 422 données invalides |

---

## 1 ▶️ Lancer

Les requêtes se testent depuis le terminal du Codespace (Apache écoute sur le port 80 du conteneur) :
```bash
curl -s http://localhost/livres | head -c 300     # le site renvoie du HTML…
```
… et l'application mobile veut des **données**, en JSON.

## 2 🔍 Comprendre

1. Pourquoi ne pas renvoyer simplement toutes les colonnes de la table `livre` ? Citez deux informations
   qui ne regardent pas l'application mobile.
2. Quelle différence entre **401** et **403** ?
3. Une route d'API nommée `livres.index`… alors qu'une route web porte déjà ce nom. Que se passe-t-il ?
4. Le site garde l'utilisateur connecté grâce à une **session** (un cookie). Pourquoi une application
   mobile ou un logiciel d'acquisitions ne fonctionne-t-il pas ainsi ?

## 3 ✏️ Modifier

### Mission 7.1 🟢 — `GET /api/livres`

**Le contrat** : les champs qu'on choisit d'exposer. `src/Api/LivreRessource.php` :
```php
<?php

namespace App\Api;

use App\Entity\Livre;

final readonly class LivreRessource
{
    public function __construct(
        public int $id,
        public string $titre,
        public ?string $auteur,
        public ?string $categorie,
        public ?int $annee,
        public ?string $isbn,
        public bool $disponible,
    ) {
    }

    public static function depuis(Livre $livre): self
    {
        return new self(
            id: $livre->getId(),
            titre: $livre->getTitre(),
            auteur: $livre->getAuteur()?->getNomComplet(),
            categorie: $livre->getCategorie()?->getNom(),
            annee: $livre->getAnnee(),
            isbn: $livre->getIsbn(),
            disponible: $livre->isDisponible(),
        );
    }
}
```

**Le contrôleur** : `php bin/console make:controller Api\\LivreController` (supprimez la vue
générée : une API n'en a pas besoin), puis
```php
#[Route('/api/livres', name: 'api.livres.', format: 'json')]
class LivreController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, LivreRepository $livres, PaginatorInterface $paginator): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pagination = $paginator->paginate($livres->creerRequeteCatalogue(), $page, 20);
        $derniere = max(1, (int) ceil($pagination->getTotalItemCount() / 20));
        $lien = fn (int $numero) => $this->generateUrl('api.livres.index',
            [...$request->query->all(), 'page' => $numero], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json([
            'data' => array_map(LivreRessource::depuis(...), [...$pagination->getItems()]),
            'links' => [
                'first' => $lien(1),
                'last' => $lien($derniere),
                'prev' => $page > 1 ? $lien($page - 1) : null,
                'next' => $page < $derniere ? $lien($page + 1) : null,
            ],
            'meta' => [
                'current_page' => $page,
                'per_page' => 20,
                'total' => $pagination->getTotalItemCount(),
                'last_page' => $derniere,
            ],
        ]);
    }
}
```
⚠️ Les noms **commencent par `api.`** : sinon ils écraseraient les routes web `livres.*` et le
catalogue du site renverrait du JSON. Le groupe `socle` vous préviendrait.

```bash
curl -s http://localhost/api/livres
```

✅ `php bin/phpunit --group=seance-07 --filter=m1`

### Mission 7.2 🟢 — `GET /api/livres/{id}`

Une méthode `show(Livre $livre)` qui renvoie `$this->json(['data' => LivreRessource::depuis($livre)])`,
route `show`, `requirements: ['id' => '\d+']`. Essayez `curl -i http://localhost/api/livres/99999` :
la liaison d'entité produit un **404**… en JSON, grâce à `format: 'json'` sur la classe.

✅ `php bin/phpunit --group=seance-07 --filter=m2`

### Mission 7.3 🟡 — Filtres

**Attendu :** `?categorie=<slug>` ne garde que les livres de cette catégorie ; `?disponible=1`
seulement les disponibles ; les deux se combinent.

> 💡 **Indice.** `creerRequeteCatalogue()` renvoie un QueryBuilder **pas encore exécuté** : ajoutez-lui
> des `->andWhere(…)` seulement si le paramètre est présent (`$request->query->has('categorie')`).
> La catégorie est déjà jointe sous l'alias `c` : `c.slug = :slug`. Pour lire `1`/`0`/`true` :
> `$request->query->getBoolean('disponible')`.

✅ `php bin/phpunit --group=seance-07 --filter=m3`

### Mission 7.4 🟡 — Créer un livre avec un jeton

Une API n'a pas de session : chaque requête **porte** son identité dans un en-tête
`Authorization: Bearer <jeton>`. Comme un mot de passe, le jeton n'est stocké que sous forme
d'**empreinte**.

**a. L'entité des jetons** : `php bin/console make:entity JetonApi`
| Propriété | Type | Détail |
|---|---|---|
| `user` | `relation` → `User`, `ManyToOne` | nullable `no` ; propriété ajoutée dans `User` : `no` |
| `appareil` | `string` | `100`, not null |
| `empreinte` | `string` | `64`, not null |
| `creeLe` | `datetime_immutable` | not null |

Ajoutez `unique: true` à la colonne `empreinte` et `onDelete: 'CASCADE'` au `JoinColumn`, puis
`make:migration` et `doctrine:migrations:migrate`.

**b. Le service des jetons** `src/Service/GestionJetons.php` :
```php
class GestionJetons
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JetonApiRepository $jetons,
    ) {
    }

    /** Crée un jeton et renvoie sa valeur en clair : elle n'est montrée qu'une fois */
    public function creer(User $user, string $appareil): string
    {
        $valeur = bin2hex(random_bytes(32));

        $jeton = (new JetonApi())
            ->setUser($user)
            ->setAppareil($appareil)
            ->setEmpreinte(hash('sha256', $valeur))
            ->setCreeLe(new \DateTimeImmutable());
        $this->em->persist($jeton);
        $this->em->flush();

        return $valeur;
    }

    public function proprietaire(string $valeur): ?User
    {
        return $this->jetons->findOneBy(['empreinte' => hash('sha256', $valeur)])?->getUser();
    }
}
```

**c. Qui est derrière ce jeton ?** `src/Security/JetonApiHandler.php` :
```php
class JetonApiHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly GestionJetons $jetons)
    {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->jetons->proprietaire($accessToken)
            ?? throw new BadCredentialsException('Jeton invalide.');

        return new UserBadge($user->getUserIdentifier());
    }
}
```

**d. Un pare-feu pour l'API**, dans `security.yaml`, **avant** `main` (le premier qui correspond gagne) :
```yaml
    firewalls:
        dev: …
        api:
            pattern: ^/api
            stateless: true                 # pas de session
            provider: app_user_provider
            access_token:
                token_handler: App\Security\JetonApiHandler
        main: …
```

**e. L'écriture.** **Attendu :** `POST /api/livres` (route `api.livres.store`) :
- sans jeton → **401** ; jeton inconnu → **401** ; jeton d'un adhérent → **403** ;
- jeton d'un bibliothécaire et données valides → **201** et le livre en JSON (`{"data": {…}}`) ;
- données invalides → **422** avec les erreurs par champ : `{"message": …, "errors": {"titre": ["…"], "auteur": ["…"]}}`.

Le corps de la requête contient `titre`, `auteur_id`, `categorie_id`, `annee`, `nb_pages`, `isbn`,
`resume`, `disponible`.

> 💡 **Indices.**
> - `#[IsGranted('ROLE_BIBLIOTHECAIRE')]` sur la méthode : sans jeton, le pare-feu répond 401 ;
>   avec le jeton d'un adhérent, 403.
> - `$request->toArray()` donne le corps JSON en tableau. Construisez un `new Livre()` avec ses
>   setters ; `auteur_id` et `categorie_id` deviennent des entités avec `$auteurs->find(…)`.
> - La validation ? Vous l'avez déjà : les contraintes de l'entité `Livre`. Injectez
>   `ValidatorInterface $validator` : `$validator->validate($livre)` renvoie la liste des violations
>   (`getPropertyPath()`, `getMessage()`).
> - Le code 201 : `$this->json([...], 201)`.

**Essayez à la main.** Une commande pour créer un jeton (`src/Command/CreerJetonCommand.php`) :
```php
#[AsCommand(name: 'app:jeton', description: "Crée un jeton d'API pour un compte")]
class CreerJetonCommand
{
    public function __construct(private readonly UserRepository $users, private readonly GestionJetons $jetons)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Argument] string $email): int
    {
        $user = $this->users->findOneBy(['email' => $email]);
        if (null === $user) {
            $io->error("Aucun compte $email.");

            return Command::FAILURE;
        }
        $io->writeln($this->jetons->creer($user, 'terminal'));

        return Command::SUCCESS;
    }
}
```
```bash
php bin/console app:jeton bibliothecaire@bibliotech.test
curl -i -X POST http://localhost/api/livres -H "Content-Type: application/json" \
     -H "Authorization: Bearer <votre jeton>" -d '{"titre": ""}'
```

✅ `php bin/phpunit --group=seance-07`

### Mission 7.5 🔴 — Obtenir un jeton

**Attendu :** `POST /api/jetons` avec `email`, `password` et `appareil` renvoie `{"jeton": "…"}`
(201), ou une erreur 422 si les identifiants sont faux — la **même** réponse que l'on se trompe
d'e-mail ou de mot de passe — et **429** au-delà de 5 essais par minute.
*(test : `--group=bonus --filter=s7_m5` ; cherchez « Rate Limiter » dans la documentation.)*

## 4 ✅ Valider

```bash
php bin/phpunit --group=seance-07 && php bin/phpunit --group=socle
git add -A && git commit -m "feat: API REST du catalogue" && git push
```

> ✍️ **La séance 8 commence par l'interrogation écrite n° 2** (30 min, séances 5 à 7).

---

## 📌 Concepts à connaître

1. **REST** — des ressources (`/livres`), des verbes (GET, POST…), des codes de retour qui ont un sens.
2. **Codes HTTP** — 200 lu, 201 créé, 401 non authentifié, 403 interdit, 404 introuvable, 422 invalide, 429 trop de requêtes.
3. **Contrat** — `LivreRessource` choisit les champs exposés : la table peut évoluer sans casser l'application mobile.
4. **Jeton** — une API ne garde pas de session : chaque requête porte `Authorization: Bearer …` ; on ne stocke que l'empreinte.
5. **Pagination** — une API ne renvoie jamais « tout » : `data`, `links`, `meta`.

## 📓 Journal de fin de séance (portail)

Ce que j'ai fait · ce qui m'a bloqué · ce que j'ai compris aujourd'hui.
