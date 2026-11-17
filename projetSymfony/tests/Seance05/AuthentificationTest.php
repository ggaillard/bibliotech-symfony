<?php

namespace App\Tests\Seance05;

use App\Entity\User;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * SÉANCE 5 — Authentification, rôles, sécurité de la connexion
 *
 * Les rôles : tout compte a ROLE_USER (adhérent) ; ROLE_BIBLIOTHECAIRE ; ROLE_ADMIN
 * (qui hérite de ROLE_BIBLIOTHECAIRE).
 *
 *   php bin/phpunit --group=seance-05
 */
#[Group('seance-05')]
class AuthentificationTest extends TestCase
{
    private const MOT_DE_PASSE = 'motdepasse-solide';

    private function compte(array $roles = []): User
    {
        return $this->creerCompte($roles, uniqid('lecteur-', true).'@bibliotech.test', self::MOT_DE_PASSE);
    }

    private function messageDErreurDeConnexion(): string
    {
        self::assertResponseRedirects('/connexion');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger', 'La page de connexion doit afficher l\'erreur dans un .alert-danger.');

        return trim($this->client->getCrawler()->filter('.alert-danger')->text());
    }

    // ── Mission 5.1 🟢 — Comptes, connexion, déconnexion ──────────

    public function test_m1_l_entite_user_existe_et_un_compte_est_adherent_par_defaut(): void
    {
        self::assertTrue(class_exists(User::class), 'Créez l\'entité User : php bin/console make:user');
        self::assertTrue(is_subclass_of(User::class, UserInterface::class));
        self::assertTrue(is_subclass_of(User::class, PasswordAuthenticatedUserInterface::class));

        $colonnes = $this->colonnes('user');
        foreach (['email', 'roles', 'password', 'nom'] as $colonne) {
            self::assertContains($colonne, $colonnes, "Il manque la colonne « $colonne » dans la table user.");
        }

        self::assertSame(['ROLE_USER'], $this->compte()->getRoles(), 'Un nouveau compte est un adhérent : ROLE_USER seulement.');
    }

    public function test_m1_la_page_de_connexion_s_affiche(): void
    {
        self::assertSame('/connexion', $this->url('app_login'));

        $this->get('/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_password"]');
        self::assertSelectorExists('input[name="_csrf_token"]', 'Le formulaire de connexion doit être protégé par un jeton CSRF.');
    }

    public function test_m1_on_se_connecte_avec_de_bons_identifiants(): void
    {
        $user = $this->compte();

        $this->seConnecterAvec($user->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseRedirects('/');
        self::assertSame($user->getEmail(), $this->utilisateurConnecte()?->getUserIdentifier());
    }

    public function test_m1_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $user = $this->compte();

        $this->seConnecterAvec($user->getEmail(), 'faux');

        self::assertNotEmpty($this->messageDErreurDeConnexion());
        self::assertNull($this->utilisateurConnecte());
    }

    public function test_m1_on_se_deconnecte(): void
    {
        $this->connecter($this->compte());

        $this->get('/deconnexion');

        self::assertResponseRedirects('/');
        $this->get('/');
        self::assertNull($this->utilisateurConnecte());
    }

    // ── Mission 5.2 🟢 — Protéger l'espace de gestion ─────────────

    public function test_m2_un_visiteur_est_renvoye_vers_la_connexion(): void
    {
        $this->get('/gestion/livres');

        self::assertResponseRedirects('/connexion');
    }

    public function test_m2_un_adherent_n_a_pas_acces_a_la_gestion(): void
    {
        $this->connecter($this->compte());

        $this->get('/gestion/livres');

        self::assertResponseStatusCodeSame(403);
    }

    public function test_m2_bibliothecaire_et_admin_ont_acces_a_la_gestion(): void
    {
        $this->connecter($this->compte(['ROLE_BIBLIOTHECAIRE']));
        $this->get('/gestion/livres');
        self::assertResponseIsSuccessful();

        $this->connecter($this->compte(['ROLE_ADMIN']));
        $this->get('/gestion/categories');
        self::assertResponseIsSuccessful('ROLE_ADMIN doit hériter de ROLE_BIBLIOTHECAIRE (role_hierarchy).');
    }

    public function test_m2_les_fixtures_creent_un_compte_par_role(): void
    {
        $this->chargerLesFixtures();
        $comptes = $this->em()->getRepository(User::class);

        $attendus = [
            'admin@bibliotech.test' => 'ROLE_ADMIN',
            'bibliothecaire@bibliotech.test' => 'ROLE_BIBLIOTHECAIRE',
            'adherent@bibliotech.test' => 'ROLE_USER',
        ];
        foreach ($attendus as $email => $role) {
            $compte = $comptes->findOneBy(['email' => $email]);
            self::assertNotNull($compte, "Le compte de démonstration $email n'existe pas.");
            self::assertContains($role, $compte->getRoles(), "$email doit avoir le rôle $role.");
        }
        self::assertSame(['ROLE_USER'], $comptes->findOneBy(['email' => 'adherent@bibliotech.test'])->getRoles());
    }

    // ── Mission 5.3 🟡 — Inscription d'un adhérent ────────────────

    public function test_m3_un_visiteur_peut_s_inscrire_comme_adherent(): void
    {
        self::assertSame('/inscription', $this->url('app_register'));
        $this->get('/inscription');
        self::assertResponseIsSuccessful();

        $this->soumettre('Créer mon compte', [
            'nom' => 'Léa Martin',
            'email' => 'lea@exemple.fr',
            'plainPassword[first]' => 'un-mot-de-passe-long',
            'plainPassword[second]' => 'un-mot-de-passe-long',
        ]);

        self::assertResponseRedirects('/');
        $lea = $this->em()->getRepository(User::class)->findOneBy(['email' => 'lea@exemple.fr']);
        self::assertNotNull($lea);
        self::assertSame(['ROLE_USER'], $lea->getRoles());
        self::assertNotSame('un-mot-de-passe-long', $lea->getPassword(), 'Le mot de passe doit être haché.');
        self::assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($lea, 'un-mot-de-passe-long'));
        self::assertSame('lea@exemple.fr', $this->utilisateurConnecte()?->getUserIdentifier(), "L'adhérent doit être connecté après son inscription.");
    }

    public function test_m3_l_inscription_refuse_un_email_deja_pris_ou_un_mot_de_passe_court(): void
    {
        $existant = $this->compte();
        $comptes = fn () => $this->em()->getRepository(User::class)->count();
        $avant = $comptes();

        $this->get('/inscription');
        $this->soumettre('Créer mon compte', [
            'nom' => 'X',
            'email' => $existant->getEmail(),
            'plainPassword[first]' => 'un-mot-de-passe-long',
            'plainPassword[second]' => 'un-mot-de-passe-long',
        ]);
        self::assertResponseStatusCodeSame(422, 'Un e-mail déjà utilisé doit être refusé.');
        $this->assertChampsEnErreur(['email']);

        $this->get('/inscription');
        $this->soumettre('Créer mon compte', [
            'nom' => 'X',
            'email' => 'nouveau@exemple.fr',
            'plainPassword[first]' => 'court',
            'plainPassword[second]' => 'court',
        ]);
        self::assertResponseStatusCodeSame(422, 'Un mot de passe de moins de 8 caractères doit être refusé.');

        $this->get('/inscription');
        $this->soumettre('Créer mon compte', [
            'nom' => 'X',
            'email' => 'distrait@exemple.fr',
            'plainPassword[first]' => 'un-mot-de-passe-long',
            'plainPassword[second]' => 'un-autre-mot-de-passe',
        ]);
        self::assertResponseStatusCodeSame(422, 'Les deux mots de passe doivent être identiques.');

        self::assertSame($avant, $comptes(), 'Aucun compte ne doit avoir été créé.');
    }

    public function test_m3_on_ne_peut_pas_s_inscrire_admin_en_trichant(): void
    {
        $this->get('/inscription');
        $formulaire = $this->client->getCrawler()->selectButton('Créer mon compte')->form();
        $this->remplir($formulaire, [
            'nom' => 'Pirate',
            'email' => 'pirate@exemple.fr',
            'plainPassword[first]' => 'un-mot-de-passe-long',
            'plainPassword[second]' => 'un-mot-de-passe-long',
        ]);

        // Le pirate ajoute à la main un champ « roles » que le formulaire ne propose pas
        $valeurs = $formulaire->getPhpValues();
        $nomDuFormulaire = array_key_first(array_filter($valeurs, 'is_array'));
        $valeurs[$nomDuFormulaire]['roles'] = ['ROLE_ADMIN'];
        $this->client->request('POST', $formulaire->getUri(), $valeurs);

        $pirate = $this->em()->getRepository(User::class)->findOneBy(['email' => 'pirate@exemple.fr']);
        self::assertTrue(null === $pirate || ['ROLE_USER'] === $pirate->getRoles(), 'Le pirate ne doit pas devenir admin.');
    }

    // ── Mission 5.4 🟡 — Durcir la connexion ──────────────────────

    public function test_m4_le_message_d_erreur_ne_revele_pas_si_le_compte_existe(): void
    {
        $user = $this->compte();

        $this->seConnecterAvec($user->getEmail(), 'faux');
        $mauvaisMotDePasse = $this->messageDErreurDeConnexion();

        $this->seConnecterAvec('inconnu@exemple.fr', 'faux');
        $compteInconnu = $this->messageDErreurDeConnexion();

        self::assertNotEmpty($mauvaisMotDePasse);
        self::assertSame($mauvaisMotDePasse, $compteInconnu,
            'Les deux messages doivent être identiques : sinon un attaquant sait quels e-mails ont un compte.');
    }

    public function test_m4_trop_de_tentatives_de_connexion_sont_bloquees(): void
    {
        // Le compteur de tentatives est gardé en mémoire : on garde la même application d'une requête à l'autre
        $this->client->disableReboot();
        $user = $this->compte();

        for ($i = 1; $i <= 5; ++$i) {
            $this->seConnecterAvec($user->getEmail(), 'faux'.$i);
        }

        // 6e tentative, avec le BON mot de passe : elle doit quand même être refusée
        $this->seConnecterAvec($user->getEmail(), self::MOT_DE_PASSE);

        self::assertResponseRedirects('/connexion', null, 'Après 5 échecs en une minute, la connexion doit être bloquée.');
        self::assertNull($this->utilisateurConnecte());
    }
}
