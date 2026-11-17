<?php

namespace App\Tests\Bonus;

use App\Tests\Factory\AuteurFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('bonus')]
class Seance02BonusTest extends TestCase
{
    public function test_s2_m4_la_page_d_un_auteur_liste_ses_livres(): void
    {
        $asimov = AuteurFactory::createOne(['prenom' => 'Isaac', 'nom' => 'Asimov']);
        $livre = LivreFactory::createOne(['titre' => 'Fondation', 'auteur' => $asimov]);

        $this->get($this->url('auteurs.show', ['id' => $asimov->getId()]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Isaac Asimov');
        self::assertSelectorTextContains('body', 'Fondation');

        $this->get($this->url('livres.show', ['id' => $livre->getId()]));
        self::assertSelectorExists(sprintf('a[href="%s"]', $this->url('auteurs.show', ['id' => $asimov->getId()])),
            "Sur la fiche du livre, le nom de l'auteur doit être un lien vers sa page.");
    }
}
