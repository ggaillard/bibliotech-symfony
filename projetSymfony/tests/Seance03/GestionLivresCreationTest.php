<?php

namespace App\Tests\Seance03;

use App\Entity\Livre;
use App\Form\LivreType;
use App\Tests\Factory\AuteurFactory;
use App\Tests\Factory\CategorieFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 3 — Espace de gestion : lister, consulter, ajouter un livre
 *
 * Toutes les URL commencent par /gestion/livres et les routes s'appellent
 * gestion.livres.index, gestion.livres.new, gestion.livres.show, …
 *
 *   php bin/phpunit --group=seance-03
 */
#[Group('seance-03')]
class GestionLivresCreationTest extends TestCase
{
    private function donneesValides(array $surcharge = []): array
    {
        return array_merge([
            'titre' => 'Le Rouge et le Noir',
            'auteur' => AuteurFactory::createOne()->getId(),
            'categorie' => CategorieFactory::createOne()->getId(),
            'annee' => 1830,
            'nbPages' => 576,
            'isbn' => '978-2-07-041311-9',
            'resume' => "Julien Sorel veut s'élever.",
            'disponible' => true,
        ], $surcharge);
    }

    // ── Mission 3.1 🟢 — Contrôleur de gestion et liste paginée ───

    public function test_m1_la_liste_de_gestion_affiche_les_livres(): void
    {
        LivreFactory::createOne(['titre' => 'Germinal']);
        $this->connecterBibliothecaire();

        self::assertSame('/gestion/livres', $this->url('gestion.livres.index'));
        $this->get('/gestion/livres');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Germinal');
    }

    public function test_m1_la_liste_est_paginee_par_15(): void
    {
        LivreFactory::createMany(20);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.index'));
        self::assertSelectorCount(15, 'table tbody tr', 'La première page doit montrer 15 livres.');
        self::assertSelectorExists('a[href*="page=2"]', 'Il manque le lien vers la page 2.');

        $this->get($this->url('gestion.livres.index', ['page' => 2]));
        self::assertSelectorCount(5, 'table tbody tr', 'La page 2 doit montrer les 5 livres restants.');
    }

    // ── Mission 3.2 🟢 — Formulaire d'ajout et validation ─────────

    public function test_m2_le_formulaire_d_ajout_s_affiche_avec_ses_champs(): void
    {
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.new'));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name$="[titre]"]');
        self::assertSelectorExists('select[name$="[auteur]"]');
        self::assertSelectorExists('select[name$="[categorie]"]');
        self::assertSelectorExists('input[name$="[_token]"]', 'Le formulaire doit contenir son jeton CSRF.');
    }

    public function test_m2_un_livre_valide_est_enregistre_avec_un_message(): void
    {
        $donnees = $this->donneesValides();
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.new'));
        $this->soumettre('Enregistrer', $donnees);

        self::assertResponseRedirects('/gestion/livres');
        $this->assertMessageFlash('success');

        $livre = $this->em()->getRepository(Livre::class)->findOneBy(['titre' => 'Le Rouge et le Noir']);
        self::assertNotNull($livre, "Le livre n'a pas été enregistré.");
        self::assertSame('978-2-07-041311-9', $livre->getIsbn());
    }

    public function test_m2_les_donnees_invalides_sont_refusees(): void
    {
        $donnees = $this->donneesValides(['titre' => '', 'annee' => 3000, 'auteur' => 999999]);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.new'));
        $this->soumettre('Enregistrer', $donnees);

        self::assertResponseStatusCodeSame(422, 'Un formulaire refusé est réaffiché avec le code 422.');
        $this->assertChampsEnErreur(['titre', 'annee', 'auteur']);
        self::assertSame(0, $this->em()->getRepository(Livre::class)->count());
    }

    public function test_m2_les_regles_de_validation_sont_portees_par_l_entite(): void
    {
        $erreurs = static::getContainer()->get('validator')->validate(new Livre());
        $champs = array_map(fn ($erreur) => $erreur->getPropertyPath(), iterator_to_array($erreurs));

        foreach (['titre', 'auteur'] as $champ) {
            self::assertContains($champ, $champs, "Un livre vide doit être refusé : ajoutez une contrainte sur « $champ » dans l'entité Livre.");
        }
    }

    public function test_m2_l_isbn_doit_etre_unique(): void
    {
        LivreFactory::createOne(['isbn' => '978-2-07-041311-9']);
        $donnees = $this->donneesValides();
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.new'));
        $this->soumettre('Enregistrer', $donnees);

        self::assertResponseStatusCodeSame(422);
        $this->assertChampsEnErreur(['isbn']);
    }

    // ── Mission 3.3 🟡 — Fiche de gestion et classe de formulaire ─

    public function test_m3_la_fiche_de_gestion_charge_le_livre_de_l_url(): void
    {
        $livre = LivreFactory::createOne(['titre' => 'Bel-Ami']);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.show', ['id' => $livre->getId()]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Bel-Ami');
        self::assertSelectorTextContains('body', $livre->getAuteur()->getNomComplet());

        $this->get('/gestion/livres/999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function test_m3_le_formulaire_est_une_classe_livre_type(): void
    {
        self::assertTrue(
            class_exists(LivreType::class),
            'Créez la classe App\Form\LivreType (php bin/console make:form LivreType Livre).'
        );

        $formulaire = static::getContainer()->get('form.factory')->create(LivreType::class);
        foreach (['titre', 'auteur', 'categorie', 'annee', 'isbn'] as $champ) {
            self::assertTrue($formulaire->has($champ), "LivreType doit contenir le champ « $champ ».");
        }
    }
}
