# 📖 Glossaire — sécurité

> Séances 5, 7 et 8. Classé par ordre alphabétique.

**`access_control`** — Dans `security.yaml`, les règles par URL : `{ path: ^/gestion, roles: ROLE_BIBLIOTHECAIRE }`.
La **première** règle qui correspond s'applique.

**`access_token` (authentificateur)** — Lit l'en-tête `Authorization: Bearer <jeton>` et demande au
*token handler* à qui appartient le jeton.

**Affectation de masse** — Recopier sans tri les champs reçus dans une entité. Un formulaire Symfony
n'accepte que les champs qu'il déclare (« Ce formulaire ne doit pas contenir de champs supplémentaires »).

**`app.user`** — Dans Twig, l'utilisateur connecté (ou `null`).

**Authentification / autorisation** — *Qui êtes-vous ?* / *Avez-vous le droit ?*

**CSRF** — Attaque qui fait envoyer une requête par le navigateur connecté de la victime. Parade : un
jeton secret par session dans chaque formulaire (`csrf_token()`, `isCsrfTokenValid()`).

**Empreinte (hash)** — Résultat d'un calcul à sens unique. Les mots de passe (bcrypt/argon2, avec sel)
et les jetons d'API (sha256) ne sont stockés que sous cette forme.

**Énumération de comptes** — Deviner quels e-mails ont un compte grâce à des messages d'erreur
différents. Symfony répond « Identifiants invalides. » dans tous les cas (`hide_user_not_found: true`).

**`form_login`** — Authentificateur du pare-feu : intercepte le POST du formulaire de connexion, charge
l'utilisateur, vérifie le mot de passe, ouvre la session.

**Force brute** — Essayer des milliers de mots de passe. Parade : `login_throttling` (5 essais par minute).

**Hiérarchie des rôles (`role_hierarchy`)** — `ROLE_ADMIN: ROLE_BIBLIOTHECAIRE` : un admin a tous les
droits d'un bibliothécaire.

**`#[IsGranted('ROLE_…')]` / `is_granted()`** — Exige un rôle sur une méthode ou une classe ; teste un
rôle dans Twig.

**Pare-feu (firewall)** — Dans `security.yaml`, la partie du site (par motif d'URL) et la façon dont on
y reconnaît l'utilisateur (`form_login`, `access_token`…). `stateless: true` : pas de session.

**`PasswordHasher` (`UserPasswordHasherInterface`)** — Calcule et vérifie les empreintes de mots de
passe : `hashPassword()`, `isPasswordValid()`. `security:hash-password` fait de même en console.

**Provider** — Charge un utilisateur d'après son identifiant (ici l'e-mail), depuis la base.

**Rôle** — Une chaîne `ROLE_…`. Tout compte a `ROLE_USER` (ajouté par `User::getRoles()`).

**Session** — Mémoire côté serveur associée à un navigateur par un cookie. Contient l'utilisateur
connecté, les messages flash, les jetons CSRF.

**XSS** — Injection de JavaScript dans une page vue par d'autres. Twig échappe `{{ }}` ; `|raw` ne le
fait pas.
