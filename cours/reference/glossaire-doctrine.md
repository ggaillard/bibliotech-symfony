# 📖 Glossaire — Doctrine et base de données

> Séances 2 et 6. Classé par ordre alphabétique.

**Collection** — Ce que contient le côté « plusieurs » d'une relation (`$auteur->getLivres()`) : on la
parcourt avec `foreach`, `count()`, `isEmpty()`. Chargée à la première utilisation.

**DQL** — Le langage de requête de Doctrine. Il ressemble au SQL mais parle d'**entités** et de
**propriétés** : `SELECT l FROM App\Entity\Livre l WHERE l.disponible = true`.

**`EntityManagerInterface` (`$em`)** — Le chef d'orchestre : `persist()` (« à enregistrer »),
`remove()` (« à supprimer »), `flush()` (envoie tout en base), `refresh()` (relit), `wrapInTransaction()`.

**Entité** — Une classe de `src/Entity/` décrite par des attributs `#[ORM\…]` ; une instance = une ligne.

**Fabrique (Foundry)** — Dans les tests, crée des données réalistes : `LivreFactory::createOne(['titre' => 'Dune'])`,
`createMany(3)`, `new()->indisponible()->create()`.

**Fixtures** — Données de démonstration (`src/DataFixtures/`), chargées par
`doctrine:fixtures:load`, qui **vide** d'abord les tables. Jamais en production.

**`flush()`** — Envoie à la base, en une fois, tout ce qui a été `persist()`, modifié ou `remove()`.
Sans `flush()`, rien n'est écrit.

**Hydratation** — Doctrine transforme les lignes SQL en objets PHP (et l'inverse).

**`inversedBy` / `mappedBy`** — Relient les deux côtés d'une relation. Le côté `ManyToOne`
(`inversedBy`) porte la clé étrangère et « possède » la relation.

**`JoinColumn`** — Décrit la colonne de clé étrangère : `nullable`, `onDelete: 'SET NULL'` ou `'CASCADE'`.

**`leftJoin` + `addSelect`** — Charge une relation dans la même requête : la parade au problème N + 1.

**`LockMode::PESSIMISTIC_WRITE`** — Verrouille une ligne (`SELECT … FOR UPDATE`) jusqu'à la fin de la transaction.

**`ManyToOne` / `OneToMany` / `ManyToMany`** — Les types de relation. Une association porteuse de
données (EMPRUNTER) devient une entité à deux `ManyToOne`.

**Migration** — Fichier `migrations/Version….php` : du SQL dans `up()` (avancer) et `down()`
(revenir). Générée par `make:migration`, **relue**, jouée par `doctrine:migrations:migrate`.
La table `doctrine_migration_versions` retient ce qui a été joué.

**N + 1 (problème)** — 1 requête pour la liste + 1 par élément pour sa relation. La barre de débogage
les compte.

**Paramètre (`:terme`)** — Une valeur passée à part (`setParameter('terme', …)`) : la saisie n'est jamais
collée dans la requête, l'injection SQL est impossible.

**QueryBuilder** — Construit une requête DQL morceau par morceau : `createQueryBuilder('l')->andWhere(…)->orderBy(…)`.
Rien n'est exécuté avant `getQuery()->getResult()`.

**Repository** — La classe des requêtes d'une entité (`src/Repository/`). Elle hérite de `find()`,
`findAll()`, `findBy()`, `findOneBy()`, `count()`.

**Reprise de données** — Faire évoluer une base en service sans perte : ajouter, transférer, supprimer.

**`schema:validate`** — Vérifie que les entités sont correctes et que la base leur correspond.

**Transaction** — Un groupe d'écritures en tout ou rien : `wrapInTransaction()` valide (COMMIT) à la
fin, ou annule tout (ROLLBACK) si une exception est levée.

**Types Doctrine** — `string` (VARCHAR), `text`, `integer`, `smallint`, `boolean`, `date_immutable`,
`datetime_immutable`, `json`… `_immutable` : un `DateTimeImmutable` qu'on ne modifie pas par erreur.
