# 📚 Modèles de vues Twig

Ce dossier contient des **modèles de démarrage** pour gagner du temps pendant les séances 3 et 4.
Ils apportent la structure HTML (Bootstrap) ; vous apportez la logique.

| Fichier | Pour la vue… | Séance |
|---|---|---|
| `index.html.twig` | liste en tableau, avec pagination | 3 |
| `show.html.twig` | fiche d'un enregistrement | 3 |
| `new.html.twig` | formulaire d'ajout | 3 |
| `edit.html.twig` | formulaire de modification + suppression | 4 |

## Utilisation

```bash
mkdir -p templates/gestion/livres
cp templates/_modeles/index.html.twig templates/gestion/livres/index.html.twig
```

Puis remplacez les commentaires `{# TODO … #}`.

## Les formulaires Symfony dans Twig

Le formulaire est construit en PHP (dans le contrôleur ou une classe `…Type`) ; la vue se contente
de l'afficher. Le thème Bootstrap 5 est déjà configuré (`config/packages/twig.yaml`) :

```twig
{{ form_start(form) }}       {# <form method="post" …> #}
    {{ form_row(form.titre) }}   {# label + champ + message d'erreur #}
    {{ form_rest(form) }}        {# les champs restants, dont le jeton CSRF caché #}
    <button class="btn btn-primary">Enregistrer</button>
{{ form_end(form) }}         {# </form> #}
```

`form_row` affiche le message d'erreur sous le champ et l'entoure de rouge (`is-invalid`) :
pas besoin de l'écrire vous-même.

> 💡 Pendant les missions 🟢, utilisez les modèles ; pendant les missions 🔴, essayez sans.
