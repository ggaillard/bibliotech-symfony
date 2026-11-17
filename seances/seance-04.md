# 4️⃣ Séance 4 — Gestion : modifier, supprimer ; les catégories en autonomie

> **Durée** 2 h · **Tests** `php bin/phpunit --group=seance-04` · **Fiche de cours** [Verbes HTTP, CSRF](../cours/04-verbes-http-csrf.md)

## 🎯 Objectifs

- Écrire un formulaire de **modification** pré-rempli, envoyé en **PUT**
- **Supprimer** proprement : verbe DELETE, jeton CSRF, confirmation
- Traduire une **règle de gestion** en code
- Refaire seul, sur une autre table, ce qui a été fait en guidé

## 🎬 La situation

> **De :** Mme Lefèvre
> **Objet :** RE: les nouveautés
>
> Merci, la saisie fonctionne ! Deux remarques : nous faisons des fautes de frappe et nous ne
> pouvons pas les **corriger** ; et nous **retirons** des livres abîmés.
> Il faudrait aussi pouvoir créer la catégorie « Bande dessinée ». Attention : une catégorie qui
> contient encore des livres ne doit **jamais** disparaître.

---

## 1 ▶️ Lancer

Ajoutez un livre depuis `/gestion/livres/new`, puis ouvrez les outils de développement du
navigateur (F12 → Réseau) et renvoyez le formulaire : observez la requête **POST** et son champ
`livre[_token]`.

## 2 🔍 Comprendre

1. Un formulaire HTML ne connaît que GET et POST. Ouvrez `config/packages/framework.yaml` : quelle
   option permet à Symfony de recevoir un **PUT** ou un **DELETE** ? Quel champ caché lit-il ?
2. Retirez `{{ form_end(form) }}` d'une vue et remplacez-le par `</form>` : que se passe-t-il à l'envoi ?
   (Regardez le message sous le formulaire.) Remettez-le.
3. Pourquoi une suppression ne doit-elle **jamais** être un simple lien `<a href="…/supprimer">` ?

## 3 ✏️ Modifier

### Mission 4.1 🟢 — Modifier un livre

**`edit()`** ressemble à `new()`, avec le livre de l'URL et le verbe PUT :
```php
#[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'PUT'])]
public function edit(Request $request, Livre $livre, EntityManagerInterface $em): Response
{
    $form = $this->createForm(LivreType::class, $livre, ['method' => 'PUT']);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();        // le livre est déjà suivi par Doctrine : pas de persist()
        $this->addFlash('success', "« {$livre->getTitre()} » a été modifié.");

        return $this->redirectToRoute('gestion.livres.index');
    }

    return $this->render('gestion/livres/edit.html.twig', ['livre' => $livre, 'form' => $form]);
}
```
Le formulaire est **pré-rempli** tout seul : il lit les valeurs du `$livre` qu'on lui donne.
Avec `'method' => 'PUT'`, `form_start` ajoute `<input type="hidden" name="_method" value="PUT">`.

**La vue `edit.html.twig`** reprend le formulaire d'ajout, avec un bouton **« Mettre à jour »**.

> ✂️ **Ne copiez pas deux fois les mêmes champs.** Déplacez les `form_row` dans
> `templates/gestion/livres/_formulaire.html.twig` et incluez-les dans les deux vues :
> `{{ include('gestion/livres/_formulaire.html.twig', {bouton: 'Mettre à jour'}) }}`
> (dans `_formulaire`, le bouton affiche `{{ bouton }}`). Dans la liste, ajoutez un lien « Modifier ».

**Le piège de l'ISBN… qui n'en est pas un.** Modifiez un livre sans toucher à son ISBN : accepté.
Pourtant `#[UniqueEntity('isbn')]` interdit deux livres avec le même ISBN ! Écrivez dans votre
journal pourquoi Symfony ne confond pas le livre avec… lui-même. (Indice : que compare-t-il quand
il trouve un livre avec cet ISBN en base ?)

✅ `php bin/phpunit --group=seance-04 --filter=m1`

### Mission 4.2 🟢 — Supprimer un livre

Dans chaque ligne du tableau de la liste :
```twig
<form action="{{ path('gestion.livres.delete', {id: livre.id}) }}" method="post" class="d-inline"
      onsubmit="return confirm('Retirer ce livre du catalogue ?')">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="_token" value="{{ csrf_token('supprimer-livre-' ~ livre.id) }}">
    <button class="btn btn-sm btn-outline-danger">Supprimer</button>
</form>
```
Et la méthode :
```php
#[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
public function delete(Request $request, Livre $livre, EntityManagerInterface $em): Response
{
    // Le jeton reçu doit être celui fabriqué pour CE livre, dans CETTE session
    if ($this->isCsrfTokenValid('supprimer-livre-'.$livre->getId(), $request->getPayload()->getString('_token'))) {
        $em->remove($livre);
        $em->flush();
        $this->addFlash('success', "« {$livre->getTitre()} » a été retiré du catalogue.");
    }

    return $this->redirectToRoute('gestion.livres.index');
}
```

✅ `php bin/phpunit --group=seance-04 --filter=m2`

### Mission 4.3 🟡 — Les catégories, en autonomie

**Attendu :** la gestion des catégories sous `/gestion/categories` (routes `gestion.categories.index`,
`.new`, `.edit`, `.delete` — **pas** de fiche `show`) :
- créer et renommer une catégorie : `nom` obligatoire et unique, `couleur` au format `#RRGGBB` ;
  boutons **« Enregistrer »** et **« Mettre à jour »** ;
- le **slug est calculé** à partir du nom : « Bande dessinée » → `bande-dessinee` ;
- supprimer une catégorie **vide** (bouton **« Supprimer »**) : message `success` ;
- **règle de gestion** : une catégorie qui contient des livres n'est pas supprimée — retour à la
  liste avec un message `error`.

> 💡 **Indices.**
> - `php bin/console make:form CategorieType Categorie`. Pour la couleur : `ColorType`.
>   Le slug ne doit **pas** être un champ du formulaire.
> - Les contraintes : `#[UniqueEntity('nom')]`, `#[Assert\NotBlank]`,
>   `#[Assert\Regex('/^#[0-9A-Fa-f]{6}$/')]`.
> - Le slug : injectez `SluggerInterface $slugger`, puis `$slugger->slug($nom)->lower()`.
> - Une catégorie a-t-elle des livres ? `$categorie->getLivres()->isEmpty()`.
> - La base vous protège déjà (clé étrangère) ; mais une erreur SQL n'est pas un message pour la
>   bibliothécaire : c'est au code de vérifier **avant**.
> - `make:crud` existe et écrirait tout cela d'un coup. Pas aujourd'hui : refaites à la main ce que
>   vous venez d'apprendre — vous saurez ensuite lire ce qu'il génère.

✅ `php bin/phpunit --group=seance-04 --filter=m3`

### Mission 4.4 🔴 — Filtrer la liste de gestion

**Attendu :** la liste `/gestion/livres` se filtre par titre/auteur (`?q=`) et par catégorie
(`?categorie=<id>`), et les filtres **restent** quand on change de page.
*(test : `--group=bonus --filter=s4_m4` ; regardez les liens que produit `knp_pagination_render`.)*

## 4 ✅ Valider

```bash
php bin/phpunit --group=seance-04 && php bin/phpunit --group=socle
git add -A && git commit -m "feat: modification, suppression, gestion des catégories" && git push
```

> ✍️ **La séance 5 commence par l'interrogation écrite n° 1** (30 min, sur papier, séances 1 à 4).
> Révisez avec les auto-évaluations des fiches 1 à 4.

---

## 📌 Concepts à connaître

1. **Verbes HTTP** — GET lit, POST crée, PUT modifie, DELETE supprime ; le champ caché `_method` les simule dans un formulaire.
2. **CSRF** — le jeton prouve que le formulaire vient du site, dans la session de l'utilisateur ; sans lui, l'action est refusée.
3. **Formulaire pré-rempli** — le formulaire lit l'entité qu'on lui donne ; `flush()` suffit pour une entité déjà en base.
4. **`UniqueEntity`** — une valeur unique… sauf pour l'enregistrement qu'on modifie lui-même.
5. **Règle de gestion** — vérifiée dans le code avant d'agir, avec un message compréhensible.

## 📓 Journal de fin de séance (portail)

Ce que j'ai fait · ce qui m'a bloqué · ce que j'ai compris aujourd'hui.
