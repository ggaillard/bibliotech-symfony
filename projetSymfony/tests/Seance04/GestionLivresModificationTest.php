<?php

namespace App\Tests\Seance04;

use App\Entity\Categorie;
use App\Tests\Factory\CategorieFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 4 — Modifier, supprimer ; gérer les catégories en autonomie
 *
 *   php bin/phpunit --group=seance-04
 */
#[Group('seance-04')]
class GestionLivresModificationTest extends TestCase
{
    // ── Mission 4.1 🟢 — Modifier un livre ─────────────────────────

    public function test_m1_le_formulaire_de_modification_est_pre_rempli(): void
    {
        $livre = LivreFactory::createOne(['titre' => 'Candide']);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.edit', ['id' => $livre->getId()]));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name$="[titre]"][value="Candide"]', 'Le champ titre doit être pré-rempli.');
        self::assertSelectorExists('input[name="_method"][value="PUT"]', 'Le formulaire de modification doit être envoyé en PUT.');
    }

    public function test_m1_la_modification_est_enregistree(): void
    {
        $livre = LivreFactory::createOne(['titre' => 'Candid']);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.edit', ['id' => $livre->getId()]));
        $this->soumettre('Mettre à jour', ['titre' => 'Candide']);

        self::assertResponseRedirects('/gestion/livres');
        $this->assertMessageFlash('success');
        self::assertSame('Candide', $this->recharger($livre)->getTitre());
    }

    public function test_m1_on_peut_garder_son_propre_isbn_mais_pas_celui_d_un_autre(): void
    {
        $livre = LivreFactory::createOne(['isbn' => '978-0-00-000001-1']);
        $autre = LivreFactory::createOne(['isbn' => '978-0-00-000002-2']);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.edit', ['id' => $livre->getId()]));
        $this->soumettre('Mettre à jour');
        self::assertResponseRedirects('/gestion/livres', null, 'Garder son propre ISBN doit être accepté.');

        $this->get($this->url('gestion.livres.edit', ['id' => $livre->getId()]));
        $this->soumettre('Mettre à jour', ['isbn' => $autre->getIsbn()]);
        self::assertResponseStatusCodeSame(422);
        $this->assertChampsEnErreur(['isbn']);
    }

    // ── Mission 4.2 🟢 — Supprimer un livre ────────────────────────

    public function test_m2_un_livre_peut_etre_supprime(): void
    {
        $livre = LivreFactory::createOne();
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.index'));
        $this->soumettre('Supprimer');

        self::assertResponseRedirects('/gestion/livres');
        $this->assertMessageFlash('success');
        self::assertNull($this->recharger($livre), "Le livre n'a pas été supprimé.");
    }

    public function test_m2_la_liste_propose_un_formulaire_de_suppression_protege(): void
    {
        LivreFactory::createOne();
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.index'));

        self::assertSelectorExists('form input[name="_method"][value="DELETE"]', 'La suppression doit être envoyée en DELETE.');
        self::assertSelectorExists('form input[name="_token"]', 'Le formulaire de suppression doit contenir un jeton CSRF.');
    }

    // ── Mission 4.3 🟡 — Les catégories, en autonomie ─────────────

    public function test_m3_on_peut_creer_une_categorie_et_son_slug_est_calcule(): void
    {
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.categories.new'));
        $this->soumettre('Enregistrer', ['nom' => 'Bande dessinée', 'couleur' => '#FF8800']);

        self::assertResponseRedirects('/gestion/categories');
        $this->assertMessageFlash('success');

        $categorie = $this->em()->getRepository(Categorie::class)->findOneBy(['nom' => 'Bande dessinée']);
        self::assertNotNull($categorie);
        self::assertSame('bande-dessinee', $categorie->getSlug());
    }

    public function test_m3_on_peut_renommer_une_categorie(): void
    {
        $categorie = CategorieFactory::createOne(['nom' => 'Polar']);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.categories.edit', ['id' => $categorie->getId()]));
        $this->soumettre('Mettre à jour', ['nom' => 'Policier', 'couleur' => '#000000']);

        self::assertResponseRedirects('/gestion/categories');
        self::assertSame('Policier', $this->recharger($categorie)->getNom());
    }

    public function test_m3_une_categorie_vide_peut_etre_supprimee(): void
    {
        $categorie = CategorieFactory::createOne();
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.categories.index'));
        $this->soumettre('Supprimer');

        $this->assertMessageFlash('success');
        self::assertNull($this->recharger($categorie));
    }

    public function test_m3_une_categorie_qui_contient_des_livres_ne_peut_pas_etre_supprimee(): void
    {
        $categorie = CategorieFactory::createOne();
        LivreFactory::createOne(['categorie' => $categorie]);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.categories.index'));
        $this->soumettre('Supprimer');

        self::assertResponseRedirects('/gestion/categories');
        $this->assertMessageFlash('error');
        self::assertNotNull($this->recharger($categorie), 'Une catégorie qui contient des livres ne doit pas disparaître.');
    }
}
