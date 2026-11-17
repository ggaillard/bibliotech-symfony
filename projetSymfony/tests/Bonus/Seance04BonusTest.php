<?php

namespace App\Tests\Bonus;

use App\Tests\Factory\CategorieFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('bonus')]
class Seance04BonusTest extends TestCase
{
    public function test_s4_m4_la_liste_de_gestion_se_filtre_et_garde_ses_filtres_en_pagination(): void
    {
        $sf = CategorieFactory::createOne(['slug' => 'sf']);
        LivreFactory::createMany(16, ['categorie' => $sf]);
        LivreFactory::createOne(['titre' => 'Hors filtre']);
        LivreFactory::createOne(['titre' => 'Germinal', 'categorie' => $sf]);
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.livres.index', ['categorie' => $sf->getId()]));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('table', 'Hors filtre');
        self::assertSelectorExists(sprintf('a[href*="categorie=%d"][href*="page=2"]', $sf->getId()),
            'Le lien vers la page 2 doit garder le filtre de catégorie.');

        $this->get($this->url('gestion.livres.index', ['q' => 'Germinal']));
        self::assertSelectorCount(1, 'table tbody tr', 'Le filtre ?q= cherche dans le titre (ou l\'auteur).');
    }
}
