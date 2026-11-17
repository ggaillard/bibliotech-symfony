# 5️⃣ Séance 5 — Authentification, rôles, sécurité de la connexion

> **Durée** 2 h, dont 30 min d'**interrogation écrite n° 1** · **Tests** `php bin/phpunit --group=seance-05`
> · **Fiche de cours** [Authentification et autorisation](../cours/05-authentification.md)

## 🎯 Objectifs

- Distinguer **authentification** (qui êtes-vous ?) et **autorisation** (avez-vous le droit ?)
- Connecter et déconnecter un utilisateur avec le **pare-feu** de Symfony
- Protéger une partie du site par **rôle**
- Résister à deux attaques classiques : l'**énumération de comptes** et la **force brute**

## 🎬 La situation

> **De :** Mme Lefèvre
> **Objet :** URGENT
>
> Mon neveu a tapé `/gestion/livres` sur son téléphone… et il a supprimé *Dune*.
> Seules les bibliothécaires doivent pouvoir gérer le catalogue. Et les adhérents devront bientôt
> avoir un compte pour emprunter.

Trois profils. Dans Symfony, un profil est un **rôle**, une chaîne qui commence par `ROLE_` :

| Profil | Rôle | Comment |
|---|---|---|
| adhérent | `ROLE_USER` | tout compte l'a, automatiquement |
| bibliothécaire | `ROLE_BIBLIOTHECAIRE` | donné par l'administration |
| admin | `ROLE_ADMIN` | **hérite** des droits du bibliothécaire |

---

## 1 ▶️ Lancer

Ouvrez `/gestion/livres` dans une fenêtre de navigation privée : rien ne vous arrête.
C'est ce que nous corrigeons. Ouvrez `config/packages/security.yaml` : un **pare-feu** (`firewalls.main`)
existe déjà, mais il ne sait encore reconnaître personne.

## 2 🔍 Comprendre

1. Dans `security.yaml`, que dit la section `password_hashers` ? Pourquoi `auto` ?
2. La barre de débogage affiche une icône 👤 : que dit-elle quand personne n'est connecté ?
3. Pourquoi ne faut-il **jamais** proposer le champ `roles` dans un formulaire ?

## 3 ✏️ Modifier

### Mission 5.1 🟢 — Comptes, connexion, déconnexion

**a. L'entité `User`.**
```bash
php bin/console make:user
```
Acceptez les quatre réponses proposées (Entrée à chaque fois) : classe `User`, rangée en base par
Doctrine, identifiée par `email`, mot de passe haché. Le générateur a créé `src/Entity/User.php`
**et** mis à jour `security.yaml` (le *provider* qui charge un utilisateur d'après son e-mail).
Ouvrez `User::getRoles()` : quel rôle chaque compte reçoit-il toujours ?

Ajoutez le nom de l'adhérent, puis créez la table :
```bash
php bin/console make:entity User        # propriété nom, string, 255, not null
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**b. Le formulaire de connexion.**
```bash
php bin/console make:security:form-login
```
| Question | Réponse |
|---|---|
| Choose a name for the controller class | `SecurityController` (Entrée) |
| Do you want to generate a '/logout' URL? | `yes` |
| Do you want to generate PHPUnit tests? | `no` |

Le générateur a écrit `src/Controller/SecurityController.php`, `templates/security/login.html.twig`,
et configuré le pare-feu (`form_login`, `logout`). Adaptez :

1. Dans `SecurityController`, les URL : `/login` → **`/connexion`**, `/logout` → **`/deconnexion`**
   (gardez les noms `app_login` et `app_logout`).
2. Traduisez `security/login.html.twig` : titre « Connexion », libellés « Adresse e-mail » et
   « Mot de passe », bouton **« Se connecter »**, et entourez le tout d'un `<div class="container">`.
   Gardez les noms des champs `_username`, `_password`, `_csrf_token`.
3. Dans `security.yaml`, sous `form_login:` ajoutez `default_target_path: home` ; sous `logout:`,
   `target: home`.
4. Dans le menu (`base.html.twig`), à droite :
   ```twig
   <ul class="navbar-nav me-2">
       {% if app.user %}
           <li class="nav-item"><span class="nav-link">{{ app.user.nom }}</span></li>
           <li class="nav-item"><a class="nav-link" href="{{ path('app_logout') }}">Déconnexion</a></li>
       {% else %}
           <li class="nav-item"><a class="nav-link" href="{{ path('app_login') }}">Connexion</a></li>
       {% endif %}
   </ul>
   ```

Vous n'avez écrit **aucune** vérification de mot de passe : le pare-feu intercepte le POST de
`/connexion`, charge l'utilisateur, compare l'empreinte, ouvre la session.

✅ `php bin/phpunit --group=seance-05 --filter=m1`

### Mission 5.2 🟢 — Protéger l'espace de gestion

Dans `security.yaml`, la hiérarchie des rôles et la règle d'accès :
```yaml
security:
    # …
    role_hierarchy:
        ROLE_BIBLIOTHECAIRE: ROLE_USER
        ROLE_ADMIN: ROLE_BIBLIOTHECAIRE

    access_control:
        - { path: ^/gestion, roles: ROLE_BIBLIOTHECAIRE }
```
Toute URL qui commence par `/gestion` exige `ROLE_BIBLIOTHECAIRE` : un visiteur est renvoyé vers
`/connexion`, un adhérent reçoit **403**, un admin passe (il hérite du rôle).

**Des comptes de démonstration** : créez `src/DataFixtures/UtilisateurFixtures.php` (mot de passe `password`) :
```php
<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UtilisateurFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $comptes = [
            ['admin@bibliotech.test', 'Administratrice', ['ROLE_ADMIN']],
            ['bibliothecaire@bibliotech.test', 'Mme Lefèvre', ['ROLE_BIBLIOTHECAIRE']],
            ['adherent@bibliotech.test', 'Léa Adhérente', []],
        ];

        foreach ($comptes as [$email, $nom, $roles]) {
            $user = (new User())->setEmail($email)->setNom($nom)->setRoles($roles);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
```
```bash
php bin/console doctrine:fixtures:load
```
Testez avec les trois comptes. Le lien « Gestion » du menu ne doit apparaître qu'au personnel :
`{% if is_granted('ROLE_BIBLIOTHECAIRE') %}…{% endif %}`.

✅ `php bin/phpunit --group=seance-05 --filter=m2`

### Mission 5.3 🟡 — Inscription d'un adhérent

**Attendu :** `/inscription` (route `app_register`) crée un compte **adhérent** : nom, e-mail unique,
mot de passe d'au moins 8 caractères **saisi deux fois** ; l'adhérent est connecté et renvoyé à
l'accueil. Le bouton s'appelle **« Créer mon compte »**. Un lien « Inscription » s'ajoute au menu.

```bash
php bin/console make:registration-form
```
| Question | Réponse |
|---|---|
| add a #[UniqueEntity] validation attribute…? | `yes` |
| send an email to verify the user's email address…? | `no` |
| automatically authenticate the user after registration? | `yes` |
| generate PHPUnit tests? | `no` |

Puis adaptez ce qui a été généré.

> 💡 **Indices.**
> - `RegistrationFormType` : ajoutez `nom` ; supprimez `agreeTerms` (du formulaire **et** de la vue) ;
>   transformez `plainPassword` en `RepeatedType` de `PasswordType` (deux champs, `first` et `second`,
>   qui doivent être identiques) ; passez le minimum de 6 à 8 caractères.
> - Traduisez la vue et les messages (celui de `#[UniqueEntity]` est dans `User.php`).
> - Un petit malin ajoute `roles[]=ROLE_ADMIN` à la requête. Essayez de comprendre pourquoi c'est
>   sans effet : quels champs un formulaire Symfony accepte-t-il ? Et que se passerait-il si vous
>   ajoutiez `->add('roles')` à ce formulaire ?

✅ `php bin/phpunit --group=seance-05 --filter=m3`

### Mission 5.4 🟡 — Durcir la connexion

Deux attaques à parer :

1. **Énumération de comptes.** Si « compte inconnu » et « mauvais mot de passe » donnent deux messages
   différents, un attaquant teste des milliers d'e-mails et apprend lesquels sont adhérents.
   **Attendu :** le même message dans les deux cas. Essayez : que répond Symfony ? Cherchez
   `hide_user_not_found` dans la documentation et notez dans votre journal ce qu'il faut **ne pas** faire.
2. **Force brute.** **Attendu :** au-delà de **5 tentatives par minute**, la connexion est refusée,
   même avec le bon mot de passe.

> 💡 **Indice.** Cherchez `login_throttling` dans la documentation de la sécurité. Le pare-feu
> affiche alors « Plusieurs tentatives de connexion ont échoué… » au lieu d'ouvrir la session.

✅ `php bin/phpunit --group=seance-05`

## 4 ✅ Valider

```bash
php bin/phpunit --group=seance-03 && php bin/phpunit --group=seance-04   # toujours verts ?
php bin/phpunit --group=seance-05 && php bin/phpunit --group=socle
git add -A && git commit -m "feat: comptes, rôles, protection de la gestion" && git push
```

> Les tests des séances 3 et 4 se connectent maintenant avec un compte `ROLE_BIBLIOTHECAIRE` : s'ils
> passent au rouge, c'est votre protection qui refuse ce rôle.

---

## 📌 Concepts à connaître

1. **Authentification / autorisation** — *qui êtes-vous* (connexion) puis *avez-vous le droit* (rôle) : redirection vers la connexion, puis 403.
2. **Hachage** — on ne stocke jamais un mot de passe, mais son empreinte (bcrypt ou argon2) ; le pare-feu compare.
3. **Pare-feu et `access_control`** — la sécurité est **déclarée** dans `security.yaml`, au-dessus des contrôleurs.
4. **Rôles et hiérarchie** — `ROLE_ADMIN` hérite de `ROLE_BIBLIOTHECAIRE` ; tout compte a `ROLE_USER`. Un rôle ne vient jamais d'un formulaire.
5. **Énumération et force brute** — message d'erreur identique, nombre de tentatives limité.

## 📓 Journal de fin de séance (portail)

Ce que j'ai fait · ce qui m'a bloqué · ce que j'ai compris aujourd'hui.
