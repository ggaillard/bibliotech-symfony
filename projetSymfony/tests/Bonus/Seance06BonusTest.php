<?php

namespace App\Tests\Bonus;

use App\Entity\Emprunt;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('bonus')]
class Seance06BonusTest extends TestCase
{
    public function test_s6_m5_la_page_des_retards_liste_les_emprunts_en_retard(): void
    {
        $adherent = $this->adherent();
        foreach (['En retard' => '-2 days', 'Dans les temps' => '+10 days'] as $titre => $retour) {
            $emprunt = (new Emprunt())
                ->setUser($adherent)
                ->setLivre(LivreFactory::new()->indisponible()->create(['titre' => $titre]))
                ->setEmprunteLe(new \DateTimeImmutable('-20 days'))
                ->setRetourPrevuLe(new \DateTimeImmutable($retour));
            $this->em()->persist($emprunt);
        }
        $this->em()->flush();
        $this->connecterBibliothecaire();

        $this->get($this->url('gestion.emprunts.retards'));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'En retard');
        self::assertSelectorTextNotContains('main', 'Dans les temps');
        self::assertSelectorExists('button:contains("Enregistrer le retour")');
    }
}
