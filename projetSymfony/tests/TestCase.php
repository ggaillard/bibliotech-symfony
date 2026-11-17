<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * La classe mère de tous les tests de BiblioTech.
 *
 * - Avant la suite, la base de test « bibliotech_test » est recréée en jouant vos migrations.
 * - Chaque test s'exécute dans une transaction annulée à la fin : les tests ne se gênent pas.
 * - $this->client est un navigateur simulé : il envoie des requêtes à l'application
 *   sans passer par Apache, et garde ses cookies (donc la session) d'une requête à l'autre.
 */
abstract class TestCase extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Chaque test « vient » d'une adresse IP différente : les compteurs de tentatives
        // de connexion (séance 5) d'un test ne bloquent pas les tests suivants.
        $this->client = static::createClient([], ['REMOTE_ADDR' => sprintf('10.%d.%d.%d', random_int(0, 255), random_int(0, 255), random_int(1, 254))]);
    }

    // ── Naviguer ───────────────────────────────────────────────────

    /** L'adresse d'une route nommée : $this->url('livres.show', ['id' => 3]) → '/livre/3' */
    protected function url(string $route, array $parametres = []): string
    {
        return static::getContainer()->get('router')->generate($route, $parametres);
    }

    protected function routeExiste(string $route): bool
    {
        return null !== static::getContainer()->get('router')->getRouteCollection()->get($route);
    }

    protected function get(string $url): Crawler
    {
        return $this->client->request('GET', $url);
    }

    /**
     * Remplit et envoie le formulaire de la page courante qui contient le bouton $bouton
     * (son texte), comme le ferait un utilisateur. Les champs sont désignés par leur nom
     * court : 'titre' pour livre[titre], 'plainPassword[first]' pour
     * registration_form[plainPassword][first]. Une case à cocher reçoit true ou false.
     */
    protected function soumettre(string $bouton, array $valeurs = []): Crawler
    {
        $boutons = $this->client->getCrawler()->selectButton($bouton);
        self::assertGreaterThan(0, $boutons->count(), "Aucun bouton « $bouton » sur la page.");

        $formulaire = $boutons->form();
        $this->remplir($formulaire, $valeurs);

        return $this->client->submit($formulaire);
    }

    protected function remplir(Form $formulaire, array $valeurs): void
    {
        // Le test peut envoyer une valeur absente d'une liste déroulante (un auteur inexistant…)
        $formulaire->disableValidation();

        foreach ($valeurs as $champ => $valeur) {
            $nom = $this->nomComplet($formulaire, $champ);
            $element = $formulaire[$nom];

            if ($element instanceof ChoiceFormField && 'checkbox' === $element->getType()) {
                $valeur ? $element->tick() : $element->untick();
            } else {
                $element->setValue((string) $valeur);
            }
        }
    }

    /** 'titre' → 'livre[titre]' (le nom réel dépend du nom du formulaire) */
    protected function nomComplet(Form $formulaire, string $champ): string
    {
        $suffixe = '['.str_replace('[', '][', $champ);
        $suffixe = str_ends_with($suffixe, ']') ? $suffixe : $suffixe.']';

        foreach (array_keys($formulaire->all()) as $nom) {
            if ($nom === $champ || str_ends_with($nom, $suffixe)) {
                return $nom;
            }
        }

        self::fail("Le formulaire n'a pas de champ « $champ ». Champs trouvés : ".implode(', ', array_keys($formulaire->all())));
    }

    /** Les champs signalés en rouge (classe Bootstrap is-invalid) après un envoi refusé */
    protected function assertChampsEnErreur(array $champs): void
    {
        foreach ($champs as $champ) {
            self::assertSelectorExists(
                sprintf('[name$="[%s]"].is-invalid, [name="%s"].is-invalid', $champ, $champ),
                "Le champ « $champ » devrait être signalé en erreur."
            );
        }
    }

    /** Suit la redirection et vérifie le message flash affiché (success → .alert-success, error → .alert-danger) */
    protected function assertMessageFlash(string $type): void
    {
        if ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }

        $classe = 'error' === $type ? '.alert-danger' : '.alert-'.$type;
        self::assertSelectorExists($classe, "Un message « $type » devrait s'afficher ($classe).");
    }

    /** Le jeton CSRF d'un formulaire de la page $url */
    protected function jetonCsrf(string $url, string $selecteur = 'input[name="_token"]'): string
    {
        $champ = $this->get($url)->filter($selecteur);
        self::assertGreaterThan(0, $champ->count(), "Aucun jeton CSRF ($selecteur) sur la page $url.");

        return (string) $champ->first()->attr('value');
    }

    /** Envoie une requête JSON (séance 7), avec un jeton d'API si on en donne un */
    protected function json(string $methode, string $url, ?array $donnees = null, ?string $jeton = null): array
    {
        $entetes = ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'];
        if (null !== $jeton) {
            $entetes['HTTP_AUTHORIZATION'] = 'Bearer '.$jeton;
        }

        $this->client->request($methode, $url, [], [], $entetes, null === $donnees ? null : json_encode($donnees));

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    // ── Base de données ────────────────────────────────────────────

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /** Relit un enregistrement en base (null s'il a été supprimé) — comme $livre->fresh() en Laravel */
    protected function recharger(object $entite): ?object
    {
        $em = $this->em();
        $classe = $em->getClassMetadata($entite::class)->getName();
        $id = $entite->getId();
        $em->clear();

        return $em->find($classe, $id);
    }

    /** Les noms des colonnes d'une table */
    protected function colonnes(string $table): array
    {
        $schema = $this->em()->getConnection()->createSchemaManager();
        self::assertTrue($schema->tablesExist([$table]), "La table « $table » n'existe pas.");

        return array_map(fn ($colonne) => $colonne->getName(), $schema->listTableColumns($table));
    }

    /** php bin/console doctrine:fixtures:load, sur la base de test */
    protected function chargerLesFixtures(): void
    {
        $application = new Application($this->client->getKernel());
        $application->setAutoExit(false);

        $testeur = new ApplicationTester($application);
        $code = $testeur->run(['command' => 'doctrine:fixtures:load', '--append' => true, '--no-interaction' => true]);

        self::assertSame(0, $code, "doctrine:fixtures:load a échoué :\n".$testeur->getDisplay());
        $this->em()->clear();
    }

    // ── Comptes (à partir de la séance 5) ──────────────────────────

    /**
     * Crée un compte. Avant la séance 5, l'entité User n'existe pas encore :
     * bibliothecaire(), adherent() et admin() renvoient alors null, et les tests
     * des séances 3 et 4 restent valables avant ET après l'ajout des comptes.
     */
    protected function creerCompte(array $roles = [], ?string $email = null, string $motDePasse = 'password'): object
    {
        $classe = 'App\Entity\User';
        self::assertTrue(class_exists($classe), "L'entité User n'existe pas encore (séance 5 : php bin/console make:user).");

        $user = new $classe();
        $user->setEmail($email ?? uniqid('compte-', true).'@bibliotech.test');
        $user->setRoles($roles);
        if (method_exists($user, 'setNom')) {
            $user->setNom('Compte de test');
        }
        $user->setPassword($this->hacher($user, $motDePasse));

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function bibliothecaire(): ?object
    {
        return class_exists('App\Entity\User') ? $this->creerCompte(['ROLE_BIBLIOTHECAIRE']) : null;
    }

    protected function adherent(): ?object
    {
        return class_exists('App\Entity\User') ? $this->creerCompte([]) : null;
    }

    protected function admin(): ?object
    {
        return class_exists('App\Entity\User') ? $this->creerCompte(['ROLE_ADMIN']) : null;
    }

    /** Ouvre une session au nom de $user (sans passer par le formulaire). Rien avant la séance 5. */
    protected function connecter(?object $user): void
    {
        if (null !== $user) {
            $this->client->loginUser($user);
        }
    }

    protected function connecterBibliothecaire(): void
    {
        $this->connecter($this->bibliothecaire());
    }

    /** Se connecte par le formulaire de /connexion, comme un utilisateur */
    protected function seConnecterAvec(string $email, string $motDePasse): void
    {
        $champ = $this->get('/connexion')->filter('input[name="_username"]');
        self::assertGreaterThan(0, $champ->count(), 'La page /connexion doit contenir un champ name="_username".');

        $formulaire = $champ->closest('form')->form();
        $formulaire['_username'] = $email;
        $formulaire['_password'] = $motDePasse;
        $this->client->submit($formulaire);
    }

    /** L'utilisateur connecté pendant la dernière requête (null si personne) */
    protected function utilisateurConnecte(): ?object
    {
        return static::getContainer()->get('security.token_storage')->getToken()?->getUser();
    }

    private function hacher(object $user, string $motDePasse): string
    {
        $conteneur = static::getContainer();

        return $conteneur->has('security.user_password_hasher')
            ? $conteneur->get('security.user_password_hasher')->hashPassword($user, $motDePasse)
            : password_hash($motDePasse, \PASSWORD_BCRYPT, ['cost' => 4]);
    }
}
