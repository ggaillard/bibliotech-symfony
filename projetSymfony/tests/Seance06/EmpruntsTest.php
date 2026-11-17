<?php

namespace App\Tests\Seance06;

use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 6 — Les emprunts : une association qui porte des données,
 * et des règles de gestion
 *
 *   php bin/phpunit --group=seance-06
 */
#[Group('seance-06')]
class EmpruntsTest extends TestCase
{
    /** Ouvre la fiche du livre et clique sur « Emprunter », comme un adhérent */
    private function emprunter(Livre $livre): void
    {
        $this->get($this->url('livres.show', ['id' => $livre->getId()]));
        $this->soumettre('Emprunter');
    }

    private function nouvelEmprunt(object $user, Livre $livre, ?string $rendu = null): Emprunt
    {
        $emprunt = (new Emprunt())
            ->setUser($user)
            ->setLivre($livre)
            ->setEmprunteLe(new \DateTimeImmutable('-5 days'))
            ->setRetourPrevuLe(new \DateTimeImmutable('+16 days'))
            ->setRenduLe(null === $rendu ? null : new \DateTimeImmutable($rendu));

        $this->em()->persist($emprunt);
        $this->em()->flush();

        return $emprunt;
    }

    private function emprunts(): array
    {
        $this->em()->clear();

        return $this->em()->getRepository(Emprunt::class)->findAll();
    }

    // ── Mission 6.1 🟢 — Entité, table, relations ─────────────────

    public function test_m1_la_table_emprunt_a_les_bonnes_colonnes(): void
    {
        $colonnes = $this->colonnes('emprunt');

        foreach (['id', 'user_id', 'livre_id', 'emprunte_le', 'retour_prevu_le', 'rendu_le'] as $colonne) {
            self::assertContains($colonne, $colonnes, "Il manque la colonne « $colonne » dans la table emprunt.");
        }
    }

    public function test_m1_les_relations_fonctionnent_dans_les_deux_sens(): void
    {
        $adherent = $this->adherent();
        $livre = LivreFactory::createOne();
        $emprunt = $this->nouvelEmprunt($adherent, $livre);

        $emprunt = $this->recharger($emprunt);
        self::assertSame($adherent->getId(), $emprunt->getUser()->getId());
        self::assertSame($livre->getId(), $emprunt->getLivre()->getId());
        self::assertCount(1, $this->recharger($adherent)->getEmprunts());
        self::assertCount(1, $this->recharger($livre)->getEmprunts());
    }

    public function test_m1_les_emprunts_en_cours_ignorent_les_emprunts_rendus(): void
    {
        $adherent = $this->adherent();
        $this->nouvelEmprunt($adherent, LivreFactory::createOne());
        $this->nouvelEmprunt($adherent, LivreFactory::createOne(), 'today');

        self::assertCount(1, $this->em()->getRepository(Emprunt::class)->findEnCours());
    }

    // ── Mission 6.2 🟢 — Emprunter un livre ───────────────────────

    public function test_m2_un_adherent_connecte_emprunte_un_livre_disponible(): void
    {
        $adherent = $this->adherent();
        $livre = LivreFactory::createOne();
        $this->connecter($adherent);

        $this->emprunter($livre);

        $this->assertMessageFlash('success');
        $emprunts = $this->emprunts();
        self::assertCount(1, $emprunts);
        self::assertSame($adherent->getId(), $emprunts[0]->getUser()->getId());
        self::assertSame((new \DateTimeImmutable('+21 days'))->format('Y-m-d'), $emprunts[0]->getRetourPrevuLe()->format('Y-m-d'));
        self::assertFalse($this->recharger($livre)->isDisponible(), "Le livre emprunté n'est plus disponible.");
    }

    public function test_m2_un_visiteur_doit_se_connecter_pour_emprunter(): void
    {
        $livre = LivreFactory::createOne();

        $this->client->request('POST', $this->url('livres.emprunter', ['id' => $livre->getId()]));

        self::assertResponseRedirects('/connexion');
        self::assertCount(0, $this->emprunts());
    }

    // ── Mission 6.3 🟡 — Règles de gestion ────────────────────────

    public function test_m3_on_ne_peut_pas_emprunter_un_livre_deja_emprunte(): void
    {
        $dejaEmprunte = LivreFactory::new()->indisponible()->create();
        $autre = LivreFactory::createOne();
        $this->connecter($this->adherent());

        // On contourne l'interface (bouton absent) : la règle doit être vérifiée par le serveur
        $jeton = $this->jetonCsrf($this->url('livres.show', ['id' => $autre->getId()]));
        $this->client->request('POST', $this->url('livres.emprunter', ['id' => $dejaEmprunte->getId()]), ['_token' => $jeton]);

        $this->assertMessageFlash('error');
        self::assertCount(0, $this->emprunts());
    }

    public function test_m3_un_adherent_ne_peut_pas_avoir_plus_de_trois_emprunts_en_cours(): void
    {
        $livres = LivreFactory::createMany(4);
        $this->connecter($this->adherent());

        foreach (array_slice($livres, 0, 3) as $livre) {
            $this->emprunter($livre);
        }
        $this->emprunter($livres[3]);

        $this->assertMessageFlash('error');
        self::assertCount(3, $this->emprunts());
        self::assertTrue($this->recharger($livres[3])->isDisponible());
    }

    // ── Mission 6.4 🟡 — Mes emprunts, retour d'un livre ──────────

    public function test_m4_mes_emprunts_n_affiche_que_les_miens(): void
    {
        $moi = $this->adherent();
        $voisin = $this->adherent();

        $this->connecter($voisin);
        $this->emprunter(LivreFactory::createOne(['titre' => 'Livre du voisin']));
        $this->connecter($moi);
        $this->emprunter(LivreFactory::createOne(['titre' => 'Mon livre']));

        self::assertSame('/mes-emprunts', $this->url('emprunts.mes'));
        $this->get($this->url('emprunts.mes'));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Mon livre');
        self::assertSelectorTextNotContains('main', 'Livre du voisin');
    }

    public function test_m4_le_bibliothecaire_enregistre_le_retour(): void
    {
        $livre = LivreFactory::createOne();
        $this->connecter($this->adherent());
        $this->emprunter($livre);

        $this->connecter($this->bibliothecaire());
        $this->get($this->url('gestion.emprunts.index'));
        $this->soumettre('Enregistrer le retour');

        $this->assertMessageFlash('success');
        self::assertNotNull($this->emprunts()[0]->getRenduLe());
        self::assertTrue($this->recharger($livre)->isDisponible());
    }

    public function test_m4_un_adherent_ne_peut_pas_enregistrer_un_retour(): void
    {
        $adherent = $this->adherent();
        $emprunt = $this->nouvelEmprunt($adherent, LivreFactory::createOne());
        $this->connecter($adherent);

        $this->client->request('POST', $this->url('gestion.emprunts.retour', ['id' => $emprunt->getId()]));

        self::assertResponseStatusCodeSame(403);
    }
}
