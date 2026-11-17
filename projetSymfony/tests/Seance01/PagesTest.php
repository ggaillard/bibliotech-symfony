<?php

namespace App\Tests\Seance01;

use App\Tests\Factory\CategorieFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 1 — Routes, contrôleurs, vues
 *
 *   php bin/phpunit --group=seance-01
 */
#[Group('seance-01')]
class PagesTest extends TestCase
{
    // ── Mission 1.2 🟢 — Page Contact ─────────────────────────────

    public function test_m2_la_page_contact_existe_et_porte_le_nom_contact(): void
    {
        self::assertTrue($this->routeExiste('contact'), "Il n'y a pas de route nommée « contact ».");
        self::assertSame('/contact', $this->url('contact'));

        $this->get($this->url('contact'));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Horaires');
    }

    public function test_m2_le_menu_contient_un_lien_vers_contact(): void
    {
        $this->get('/');

        self::assertSelectorExists('nav a[href="/contact"]', 'Le menu doit contenir un lien vers /contact.');
    }

    // ── Mission 1.3 🟡 — Liste des catégories ─────────────────────

    public function test_m3_la_page_categories_liste_les_categories_actives(): void
    {
        CategorieFactory::createOne(['nom' => 'Roman']);
        CategorieFactory::new()->inactive()->create(['nom' => 'Archives']);

        self::assertSame('/categories', $this->url('categories.index'));
        $this->get($this->url('categories.index'));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Roman');
        self::assertSelectorTextNotContains('body', 'Archives');
    }

    public function test_m3_chaque_categorie_affiche_son_nombre_de_livres(): void
    {
        $policier = CategorieFactory::createOne(['nom' => 'Policier']);
        LivreFactory::createMany(2, ['categorie' => $policier]);

        $this->get($this->url('categories.index'));

        self::assertSelectorTextContains('body', '2 livre(s)');
    }

    // ── Mission 1.4 🟡 — Livres d'une catégorie ───────────────────

    public function test_m4_la_page_d_une_categorie_liste_ses_livres_seulement(): void
    {
        $sf = CategorieFactory::createOne(['nom' => 'Science-fiction', 'slug' => 'science-fiction']);
        $jeunesse = CategorieFactory::createOne(['nom' => 'Jeunesse', 'slug' => 'jeunesse']);
        LivreFactory::createOne(['titre' => 'Fondation', 'categorie' => $sf]);
        LivreFactory::createOne(['titre' => 'Le Petit Prince', 'categorie' => $jeunesse]);

        self::assertSame('/categories/science-fiction', $this->url('categories.show', ['slug' => 'science-fiction']));
        $this->get($this->url('categories.show', ['slug' => 'science-fiction']));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Science-fiction');
        self::assertSelectorTextContains('body', 'Fondation');
        self::assertSelectorTextNotContains('body', 'Le Petit Prince');
    }

    public function test_m4_un_slug_inconnu_renvoie_404(): void
    {
        self::assertTrue($this->routeExiste('categories.show'), "La route categories.show n'existe pas.");

        $this->get('/categories/n-existe-pas');

        self::assertResponseStatusCodeSame(404);
    }
}
