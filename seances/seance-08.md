# 8️⃣ Séance 8 — Qualité, sécurité, mise en ligne

> **Durée** 2 h, dont 30 min d'**interrogation écrite n° 2** · **Tests** `php bin/phpunit --group=seance-08`
> · **Fiche de cours** [Qualité et mise en production](../cours/08-qualite-production.md)

## 🎯 Objectifs

- Mener un **audit de sécurité** simple et corriger une faille réelle
- Écrire **ses propres tests** et faire respecter un **style de code** par la CI
- **Mettre en ligne** l'application avec une configuration de production
- Présenter la réalisation dans son **tableau de synthèse E5**

## 🎬 La situation

> **De :** Mme Lefèvre
> **Objet :** ouverture au public
>
> La direction veut ouvrir BiblioTech aux habitants le mois prochain. Avant, le DSI de la
> communauté de communes demande trois choses : un **audit de sécurité**, la preuve que
> l'application est **testée**, et une **version en ligne** pour la recette.

---

## 1 ▶️ Lancer

```bash
php bin/phpunit                          # toute la suite : où en êtes-vous ?
composer audit                           # des dépendances avec des failles connues ?
vendor/bin/php-cs-fixer check            # le code respecte-t-il le style Symfony ?
php bin/console debug:firewall           # les pare-feux et ce qu'ils protègent
```

## 2 🔍 Comprendre — la grille d'audit

Parcourez l'application **et son code** avec cette grille. Notez chaque constat dans votre journal.

| # | Risque (OWASP) | Ce que vous vérifiez |
|---|---|---|
| A | Injection SQL | aucune requête construite en collant une saisie dans le DQL/SQL : toujours `:parametre` + `setParameter()` |
| B | **XSS** | toute donnée saisie par un utilisateur est affichée avec `{{ }}`, jamais avec le filtre `\|raw` |
| C | CSRF | chaque formulaire qui modifie quelque chose porte un jeton ; le contrôleur le vérifie |
| D | Contrôle d'accès | chaque URL de gestion passe par `access_control` ; l'API d'écriture exige un jeton et un rôle |
| E | Affectation de masse | aucun formulaire ne propose `roles` ; l'API ne recopie pas aveuglément le JSON reçu |
| F | Authentification | mots de passe hachés, message d'erreur neutre, tentatives limitées |
| G | Configuration | aucun secret dans un fichier versionné ; en production `APP_ENV=prod` et un `APP_SECRET` propre au serveur |
| H | Composants vulnérables | `composer audit` ne signale rien |

## 3 ✏️ Modifier

### Mission 8.1 🟡 — Trouver et corriger la faille

**Attendu :** la grille révèle une faille présente **depuis le premier jour**. Une bibliothécaire
malveillante (ou un compte volé) peut saisir dans un livre un texte qui **exécute du JavaScript**
chez tous les visiteurs qui ouvrent la fiche. Trouvez-la, prouvez-la, corrigez-la.

> 💡 **Indices.** Ajoutez un livre dont le résumé est `<script>alert('XSS')</script>` et ouvrez sa
> fiche publique. Puis : `grep -rn '|raw' templates`.
> Rédigez dans votre journal : *où*, *quel risque*, *quelle correction*, *comment le test le prouve*.

✅ `php bin/phpunit --group=seance-08 --filter=m1`

### Mission 8.2 🟢 — Vos propres tests

Les tests fournis ne couvrent pas tout. **Attendu :** au moins **deux** tests à vous dans
`tests/Etudiant/`, sur un comportement que personne n'a encore testé.

```php
<?php

namespace App\Tests\Etudiant;

use App\Tests\TestCase;

class MesTestsTest extends TestCase
{
    public function test_un_adherent_ne_voit_pas_le_lien_gestion(): void
    {
        $this->connecter($this->adherent());

        $this->get('/');

        self::assertSelectorNotExists('a[href="/gestion/livres"]');
    }
}
```
`tests/TestCase.php` vous fournit `get()`, `soumettre()`, `connecter()`, `adherent()`,
`bibliothecaire()`, `recharger()`… et les fabriques `LivreFactory::createOne([...])`. Les
assertions de Symfony (`assertResponseIsSuccessful`, `assertSelectorTextContains`,
`assertResponseRedirects`…) sont listées dans la documentation « Testing ».

Idées : un livre rendu n'est plus « en cours » ; un visiteur ne peut pas ouvrir `/mes-emprunts` ;
la fiche d'un livre emprunté n'affiche pas le bouton « Emprunter » ; l'API ne renvoie pas plus de
20 livres par page. **Un bon test échoue quand le code est faux** : cassez volontairement le code,
vérifiez que votre test passe au rouge.

✅ `php bin/phpunit --group=seance-08 --filter=m2`

### Mission 8.3 🟢 — Le style du code

```bash
vendor/bin/php-cs-fixer check --diff     # liste les écarts
vendor/bin/php-cs-fixer fix              # les corrige
git diff                                 # relisez ce qui a changé
```
Les fichiers écrits par les générateurs `make:` ont souvent quelques écarts : c'est normal.
Le job **Style du code** de l'onglet *Actions* refait cette vérification à chaque push.

### Mission 8.4 🟡 — Mise en ligne

**Attendu :** une URL publique où l'application tourne en **production**, communiquée dans
`PROGRESSION.md`.

Hébergeur proposé : **alwaysdata**, offre gratuite (PHP, MySQL, accès SSH, sans carte bancaire —
vérifiez les conditions au moment de vous inscrire). La démarche vaut pour tout hébergement PHP :

1. Créez une base **MySQL** et un utilisateur dans l'interface de l'hébergeur.
2. En SSH : `git clone` de **votre** dépôt, puis dans `projetSymfony/` :
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Créez `.env.local` — **jamais versionné** (voyez `.gitignore`) — avec les valeurs du serveur :
   ```dotenv
   APP_ENV=prod
   APP_SECRET=une-longue-chaine-aleatoire        # php -r "echo bin2hex(random_bytes(16));"
   DATABASE_URL="mysql://UTILISATEUR:MOTDEPASSE@HOTE:3306/BASE?serverVersion=8.0&charset=utf8mb4"
   ```
   (`serverVersion` : la version du serveur de l'hébergeur, par exemple `8.0` pour MySQL, `10.11.2-MariaDB` pour MariaDB.)
4. ```bash
   composer dump-env prod                              # fige la configuration dans .env.local.php
   php bin/console doctrine:migrations:migrate --no-interaction
   php bin/console cache:clear
   ```
   Pas de `doctrine:fixtures:load` : en production, le paquet n'est même pas installé — et des
   comptes `password` en ligne seraient une faille. Les catégories se créent depuis `/gestion/categories`.
5. Faites pointer le site sur le dossier **`projetSymfony/public`** (et rien d'autre : `.env.local`,
   `config/` et `src/` ne doivent pas être accessibles).
6. Le compte administrateur : fabriquez l'empreinte du mot de passe, puis insérez la ligne
   (phpMyAdmin de l'hébergeur ou `mysql`) :
   ```bash
   php bin/console security:hash-password
   ```
   ```sql
   INSERT INTO user (email, roles, password, nom)
   VALUES ('vous@exemple.fr', '["ROLE_ADMIN"]', '<l''empreinte affichée>', 'Administratrice');
   ```

**Recette** : connexion, ajout d'un livre, emprunt, retour, `/api/livres`. Une URL inconnue doit
afficher votre page 404 — **pas** une trace d'erreur Symfony.

> Pas d'hébergement possible ? Montrez la recette dans votre Codespace, port 8000 rendu **public**
> (clic droit → *Port Visibility*) le temps de la démonstration.

### Mission 8.5 🟢 — Fiche de réalisation E5

Complétez [e5/FICHE-REALISATION.md](../e5/FICHE-REALISATION.md) : elle prépare la ligne
« BiblioTech » de votre **tableau de synthèse** (épreuve E5) et vos captures pour le portfolio.

## 4 ✅ Valider

```bash
php bin/phpunit --group=seance-08 && php bin/phpunit
git add -A && git commit -m "fix: faille XSS sur le résumé ; tests et mise en ligne" && git push
```
Dans l'onglet **Actions** : toutes les séances et le style au vert.

---

## 📌 Concepts à connaître

1. **XSS** — Twig échappe tout ce qui passe par `{{ }}` ; le filtre `|raw` affiche **sans** échapper : jamais sur une saisie utilisateur.
2. **OWASP Top 10** — la liste de référence des risques web ; chaque séance en a traité un.
3. **Test de non-régression** — un test qui échoue quand on réintroduit le défaut qu'il protège.
4. **Intégration continue** — chaque push relance les tests et le style : le rouge se voit tout de suite.
5. **Configuration de production** — `APP_ENV=prod`, secrets dans `.env.local` (non versionné), site pointé sur `public/`, pas de données de démonstration.

## 📓 Journal de fin de séance (portail)

Le bilan de la situation professionnelle : ce que vous savez faire maintenant que vous ne saviez
pas faire il y a huit séances, et ce que vous mettrez en avant à l'oral.
