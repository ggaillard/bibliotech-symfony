# 🔁 Correspondance Laravel ↔ Symfony

BiblioTech existe dans les deux frameworks, avec la même médiathèque, les mêmes demandes et les mêmes
tests. Cette table aide qui connaît l'un à lire l'autre. Les idées sont les mêmes ; ce sont surtout
les **endroits** et les **noms** qui changent.

## Le projet

| | Laravel (BiblioTech Laravel) | Symfony (ce dépôt) |
|---|---|---|
| Console | `php artisan …` | `php bin/console …` |
| Générateurs | `php artisan make:model`, `make:controller`… | `php bin/console make:entity`, `make:controller`… |
| Tests | `php artisan test --group=seance-03` | `php bin/phpunit --group=seance-03` |
| Configuration locale | `.env` (non versionné) | `.env` (versionné, valeurs par défaut) + `.env.local` (non versionné) |
| Style du code | Pint (`./vendor/bin/pint`) | PHP CS Fixer (`vendor/bin/php-cs-fixer fix`) |
| Barre de débogage | (Debugbar, en option) | Web Debug Toolbar et *profiler*, inclus |

## Routes, contrôleurs, vues (séances 1, 3, 4)

| Laravel | Symfony |
|---|---|
| `routes/web.php` : `Route::get('/livres', [LivreController::class, 'index'])->name('livres.index')` | attribut sur la méthode : `#[Route('/livres', name: 'livres.index')]` |
| `Route::view('/contact', 'contact')` | `TemplateController` dans `config/routes.yaml` |
| `Route::prefix('gestion')->name('gestion.')->group(…)` | `#[Route('/gestion/livres', name: 'gestion.livres.')]` sur la classe |
| `php artisan route:list` | `php bin/console debug:router` |
| `route('livres.show', $id)` | `path('livres.show', {id: id})` (Twig), `$this->generateUrl(…)` (PHP) |
| `view('livres.index', [...])` | `$this->render('livres/index.html.twig', [...])` |
| Blade : `@extends`, `@section`, `@yield` | Twig : `{% extends %}`, `{% block %}` |
| `{{ $livre->titre }}` · `{!! $x !!}` | `{{ livre.titre }}` · `{{ x\|raw }}` |
| `@foreach … @empty … @endforeach` | `{% for … %} … {% else %} … {% endfor %}` |
| composant `<x-livre-card :livre="$livre" />` | `{{ include('livres/_carte.html.twig', {livre: livre}) }}` |
| `findOrFail($id)` | `find($id) ?? throw $this->createNotFoundException()` |
| *route model binding* `show(Livre $livre)` | liaison d'entité `show(Livre $livre)` (EntityValueResolver) |
| `redirect()->route(…)->with('success', …)` | `$this->addFlash('success', …); return $this->redirectToRoute(…);` |
| `paginate(15)` + `{{ $livres->links() }}` | KnpPaginator : `$paginator->paginate($qb, $page, 15)` + `{{ knp_pagination_render(livres) }}` |

## Base de données (séances 2, 6)

| Laravel (Eloquent) | Symfony (Doctrine) |
|---|---|
| Modèle `app/Models/Livre.php` | Entité `src/Entity/Livre.php` + repository `src/Repository/LivreRepository.php` |
| table `livres` (pluriel) | table `livre` (nom de l'entité) |
| migration écrite à la main (`Schema::create`) | migration **générée** par comparaison (`make:migration`), en SQL, à relire |
| `php artisan migrate` | `php bin/console doctrine:migrations:migrate` |
| `belongsTo` / `hasMany` | `#[ORM\ManyToOne]` / `#[ORM\OneToMany]` |
| `$fillable`, `$casts` | propriétés typées et *setters* ; types Doctrine (`date_immutable`…) |
| *scope* `scopeEnCours()` | méthode de repository `findEnCours()` |
| `with('auteur')` | `leftJoin('l.auteur', 'a')->addSelect('a')` |
| `withCount('livres')` | `COUNT(l.id)` + `GROUP BY` dans le QueryBuilder |
| `Livre::create([...])` | `new Livre()` + setters, `$em->persist()`, `$em->flush()` |
| Seeders, `php artisan db:seed` | Fixtures, `php bin/console doctrine:fixtures:load` |
| Factories (`Livre::factory()->create()`) | Foundry (`LivreFactory::createOne()`) |
| `DB::transaction(fn …)` | `$em->wrapInTransaction(fn …)` |
| `lockForUpdate()` | `$em->refresh($livre, LockMode::PESSIMISTIC_WRITE)` |

## Formulaires et validation (séances 3, 4)

| Laravel | Symfony |
|---|---|
| HTML écrit à la main, `old()`, `@error` | classe `LivreType`, `form_row()` fait label + champ + erreur |
| `$request->validate([...])`, Form Request | contraintes **sur l'entité** : `#[Assert\NotBlank]`, `#[UniqueEntity]` |
| `Rule::unique()->ignore($id)` | inutile : `UniqueEntity` reconnaît l'entité modifiée |
| redirection en cas d'erreur (302) | formulaire réaffiché (422) |
| `@csrf`, `@method('PUT')` | jeton ajouté par `form_end()` ; `['method' => 'PUT']` |
| 419 Page Expired | formulaire refusé (« Le jeton CSRF est invalide ») |

## Sécurité (séances 5, 7)

| Laravel | Symfony |
|---|---|
| `Auth::attempt()` écrit dans un contrôleur | pare-feu `form_login` : aucune vérification à écrire |
| colonne `role` (texte), middleware `role:bibliothecaire,admin` | colonne `roles` (JSON), `role_hierarchy`, `access_control` |
| middleware `auth` | `#[IsGranted('ROLE_USER')]` ou `access_control` |
| `@auth`, `@guest` | `{% if app.user %}`, `is_granted('…')` |
| `throttle:5,1` → 429 | `login_throttling` → retour au formulaire avec un message |
| Sanctum, `HasApiTokens` | authentificateur `access_token` + entité `JetonApi` + `JetonApiHandler` |
| `Sanctum::actingAs()` dans les tests | un vrai jeton créé par `GestionJetons::creer()` |

## Tests

| Laravel | Symfony |
|---|---|
| `$this->get('/')->assertOk()->assertSee('…')` | `$this->get('/'); self::assertResponseIsSuccessful(); self::assertSelectorTextContains('body', '…');` |
| `actingAs($user)` | `$this->client->loginUser($user)` (ici : `$this->connecter($user)`) |
| `assertSessionHas('success')` | suivre la redirection, puis `assertSelectorExists('.alert-success')` |
| `RefreshDatabase` | Foundry `ResetDatabase` (joue vos migrations) + DAMA (transaction par test) |
| `$livre->fresh()` | `$this->recharger($livre)` |
