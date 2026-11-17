# 📖 Glossaire — contrôleurs, routes et Twig

> Séances 1, 3 et 4. Classé par ordre alphabétique.

**`AbstractController`** — La classe mère des contrôleurs. Elle apporte les raccourcis : `render()`,
`redirectToRoute()`, `addFlash()`, `createForm()`, `createNotFoundException()`, `getUser()`,
`isCsrfTokenValid()`, `json()`.

**Attribut PHP** — Une annotation entre `#[ ]` lue par Symfony : `#[Route(…)]`, `#[IsGranted(…)]`,
`#[ORM\Column]`, `#[Assert\NotBlank]`. Elle décrit, elle n'exécute rien.

**`block` (Twig)** — Une zone nommée d'un gabarit. Le parent la déclare (`{% block body %}{% endblock %}`),
l'enfant la remplit. `{{ parent() }}` reprend le contenu du parent.

**Contrôleur** — Une classe de `src/Controller/` dont chaque méthode publique routée reçoit une
requête et renvoie une `Response`.

**`EntityValueResolver` (liaison d'entité)** — Quand une méthode demande `Livre $livre` et que la route
a un `{id}`, Symfony charge le livre ou renvoie 404.

**`extends` (Twig)** — Première ligne d'une page : `{% extends 'base.html.twig' %}`. La page hérite du
gabarit et ne remplit que ses blocs.

**Filtre (Twig)** — Transforme une valeur : `{{ titre|upper }}`, `{{ date|date('d/m/Y') }}`,
`{{ livres|length }}`, `{{ texte|raw }}` (⚠️ sans échappement).

**Flash (message)** — Un message stocké en session pour **une seule** page :
`$this->addFlash('success', '…')`, lu par `app.flashes('success')` dans `base.html.twig`.

**`include` (Twig)** — Insère un morceau de page : `{{ include('livres/_carte.html.twig', {livre: livre}) }}`.
Le `_` au début du nom signale un morceau, pas une page.

**Injection de dépendances (autowiring)** — Symfony fournit tout seul les objets demandés en argument
d'après leur **type** : `LivreRepository $livres`, `EntityManagerInterface $em`, `Request $request`.

**`methods`** — Les verbes acceptés par une route : `methods: ['GET', 'POST']`. Un autre verbe : 405.

**`name` (route)** — Le nom d'une route, utilisé par `path()` et `redirectToRoute()` : il ne change
pas quand l'URL change.

**Paramètre de route** — Un segment variable : `/categories/{slug}`. Il arrive en argument de la
méthode, du même nom. `requirements: ['id' => '\d+']` le restreint (ici, des chiffres).

**`path()` / `url()`** — Fabriquent une adresse à partir d'un nom de route : `path('livres.show', {id: 3})`
→ `/livre/3` ; `url()` donne l'adresse complète (`https://…`).

**Profiler / barre de débogage** — En développement, la barre en bas de page et sa page détaillée :
route, contrôleur, requêtes SQL, formulaire, sécurité, temps. Absents en production.

**`render()`** — `return $this->render('dossier/vue.html.twig', ['cle' => $valeur]);` : la vue reçoit
les variables du tableau.

**`Request`** — La requête HTTP : `$request->query` (après le `?`), `$request->getPayload()` (corps d'un POST),
`$request->toArray()` (corps JSON).

**`Response`** — Ce que renvoie un contrôleur : une page (`render`), une redirection, du JSON (`json`).

**Route** — L'association URL + verbe → méthode de contrôleur. Déclarée par `#[Route]` ou dans `config/routes.yaml`.

**`TemplateController`** — Contrôleur fourni par Symfony qui affiche un gabarit sans logique.
Équivalent de `Route::view()` en Laravel.

**Twig** — Le langage des vues. `{{ … }}` affiche (en échappant), `{% … %}` agit (`if`, `for`, `set`),
`{# … #}` commente. `livre.titre` appelle `getTitre()`.

## Formulaires

**`createForm(LivreType::class, $livre, ['method' => 'PUT'])`** — Fabrique un formulaire lié à une entité.

**`EntityType`** — Liste déroulante remplie depuis une table ; transforme l'id choisi en entité.

**`form_start` / `form_row` / `form_rest` / `form_end`** — Dessinent le formulaire : début, un champ
complet (label + widget + erreur), les champs oubliés, la fin (avec le jeton CSRF).

**`handleRequest()`** — Lit la requête ; si le formulaire a été envoyé, remplit l'entité et la valide.

**`isSubmitted()` / `isValid()`** — Le formulaire a-t-il été envoyé ? Respecte-t-il les contraintes ?

**Thème de formulaire** — `bootstrap_5_layout.html.twig` (dans `config/packages/twig.yaml`) : classes
Bootstrap, erreurs en rouge (`is-invalid`).

**`Type` (classe de formulaire)** — `src/Form/LivreType.php` : la liste des champs, réutilisable.
