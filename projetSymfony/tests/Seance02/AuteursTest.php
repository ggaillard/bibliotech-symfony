<?php

namespace App\Tests\Seance02;

use App\Entity\Auteur;
use App\Tests\Factory\AuteurFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 2 — Base de données : de la colonne « auteur » à la table « auteur »
 *
 *   php bin/phpunit --group=seance-02
 */
#[Group('seance-02')]
class AuteursTest extends TestCase
{
    // ── Mission 2.1 🟢 — Entité Auteur et sa table ────────────────

    public function test_m1_la_table_auteur_a_les_bonnes_colonnes(): void
    {
        $colonnes = $this->colonnes('auteur');

        foreach (['id', 'nom', 'prenom', 'nationalite', 'date_naissance', 'biographie'] as $colonne) {
            self::assertContains($colonne, $colonnes, "Il manque la colonne « $colonne » dans la table auteur.");
        }
    }

    public function test_m1_un_auteur_s_enregistre_et_donne_son_nom_complet(): void
    {
        $auteur = (new Auteur())->setPrenom('Victor')->setNom('Hugo')->setNationalite('française');
        $this->em()->persist($auteur);
        $this->em()->flush();

        self::assertSame(1, $this->em()->getRepository(Auteur::class)->count(['nom' => 'Hugo']));
        self::assertSame('Victor Hugo', $auteur->getNomComplet());
    }

    public function test_m1_la_fabrique_d_auteurs_fonctionne(): void
    {
        self::assertInstanceOf(Auteur::class, AuteurFactory::createOne());
    }

    // ── Mission 2.2 🟢 — Relation Livre → Auteur ──────────────────

    public function test_m2_livre_reference_auteur_et_l_ancienne_colonne_a_disparu(): void
    {
        $colonnes = $this->colonnes('livre');

        self::assertContains('auteur_id', $colonnes, 'Il manque livre.auteur_id');
        self::assertNotContains('auteur', $colonnes, "L'ancienne colonne texte livre.auteur doit être supprimée.");
    }

    public function test_m2_un_livre_appartient_a_un_auteur(): void
    {
        $livre = LivreFactory::createOne();

        self::assertInstanceOf(Auteur::class, $livre->getAuteur(), 'LivreFactory doit créer un auteur pour chaque livre.');
    }

    public function test_m2_un_auteur_a_plusieurs_livres(): void
    {
        $tolkien = AuteurFactory::createOne(['prenom' => 'J.R.R.', 'nom' => 'Tolkien']);
        LivreFactory::createMany(2, ['auteur' => $tolkien]);

        self::assertCount(2, $this->recharger($tolkien)->getLivres());
    }

    public function test_m2_la_fiche_livre_affiche_le_nom_complet_de_l_auteur(): void
    {
        $camus = AuteurFactory::createOne(['prenom' => 'Albert', 'nom' => 'Camus']);
        $livre = LivreFactory::createOne(['titre' => "L'Étranger", 'auteur' => $camus]);

        $this->get($this->url('livres.show', ['id' => $livre->getId()]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Albert Camus');
    }

    // ── Mission 2.3 🟡 — Fixtures et recherche par auteur ─────────

    public function test_m3_les_fixtures_relient_les_livres_a_leurs_auteurs(): void
    {
        $this->chargerLesFixtures();

        $depot = $this->em()->getRepository(Auteur::class);
        $hugo = $depot->findOneBy(['nom' => 'Hugo', 'prenom' => 'Victor']);

        self::assertNotNull($hugo, 'Victor Hugo doit exister dans la table auteur après le chargement des fixtures.');
        self::assertCount(2, $hugo->getLivres(), 'Victor Hugo doit avoir 2 livres (Les Misérables, Notre-Dame de Paris).');
        self::assertSame(1, $depot->count(['nom' => 'Hugo']), 'Un même auteur ne doit pas être créé deux fois.');
    }

    public function test_m3_la_recherche_trouve_un_livre_par_le_nom_de_son_auteur(): void
    {
        $zola = AuteurFactory::createOne(['prenom' => 'Émile', 'nom' => 'Zola']);
        LivreFactory::createOne(['titre' => 'Germinal', 'auteur' => $zola]);
        LivreFactory::createOne(['titre' => 'Dune']);

        $this->get($this->url('livres.search', ['q' => 'Zola']));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Germinal');
        self::assertSelectorTextNotContains('body', 'Dune');
    }
}
