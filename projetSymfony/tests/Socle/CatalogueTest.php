<?php

namespace App\Tests\Socle;

use App\Entity\Categorie;
use App\Entity\Livre;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SOCLE — ce qui marche dès le premier jour et doit RESTER vert
 * à chaque séance. Si un de ces tests passe au rouge, vous avez cassé
 * quelque chose qui fonctionnait : c'est une régression.
 *
 *   php bin/phpunit --group=socle
 */
#[Group('socle')]
class CatalogueTest extends TestCase
{
    public function test_la_page_d_accueil_s_affiche(): void
    {
        $this->get('/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'BiblioTech');
    }

    public function test_le_catalogue_liste_les_livres(): void
    {
        LivreFactory::createOne(['titre' => 'Germinal']);

        self::assertSame('/livres', $this->url('livres.index'), 'La route livres.index a été écrasée.');

        $this->get($this->url('livres.index'));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Germinal');
    }

    public function test_la_fiche_d_un_livre_s_affiche(): void
    {
        $livre = LivreFactory::createOne(['titre' => 'Le Petit Prince']);

        self::assertSame('/livre/'.$livre->getId(), $this->url('livres.show', ['id' => $livre->getId()]), 'La route livres.show a été écrasée.');

        $this->get($this->url('livres.show', ['id' => $livre->getId()]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Le Petit Prince');
    }

    public function test_un_livre_inexistant_renvoie_404(): void
    {
        $this->get('/livre/99999');

        self::assertResponseStatusCodeSame(404);
    }

    public function test_la_recherche_trouve_un_livre_par_son_titre(): void
    {
        LivreFactory::createOne(['titre' => 'Dune']);
        LivreFactory::createOne(['titre' => 'Fondation']);

        $this->get($this->url('livres.search', ['q' => 'Dune']));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Dune');
        self::assertSelectorTextNotContains('body', 'Fondation');
    }

    public function test_la_demo_des_parametres_de_route_fonctionne(): void
    {
        $this->get('/demo/hello/Lea');
        self::assertSelectorTextContains('body', 'Bonjour Lea');

        $this->get('/demo/hello');
        self::assertSelectorTextContains('body', 'Bonjour Étudiant');
    }

    public function test_la_page_a_propos_s_affiche(): void
    {
        $this->get($this->url('about'));

        self::assertResponseIsSuccessful();
    }

    public function test_les_fixtures_remplissent_la_base(): void
    {
        $this->chargerLesFixtures();

        self::assertGreaterThanOrEqual(8, $this->em()->getRepository(Categorie::class)->count());
        self::assertGreaterThanOrEqual(20, $this->em()->getRepository(Livre::class)->count());
    }
}
