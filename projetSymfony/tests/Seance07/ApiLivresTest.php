<?php

namespace App\Tests\Seance07;

use App\Entity\Livre;
use App\Service\GestionJetons;
use App\Tests\Factory\AuteurFactory;
use App\Tests\Factory\CategorieFactory;
use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 7 — Une API REST pour le catalogue
 *
 *   php bin/phpunit --group=seance-07
 */
#[Group('seance-07')]
class ApiLivresTest extends TestCase
{
    /** Un vrai jeton d'API, créé par votre service GestionJetons */
    private function jetonDe(object $user): string
    {
        return static::getContainer()->get(GestionJetons::class)->creer($user, 'tests');
    }

    // ── Mission 7.1 🟢 — GET /api/livres ──────────────────────────

    public function test_m1_la_liste_est_renvoyee_en_json_paginee(): void
    {
        LivreFactory::createMany(3);

        $reponse = $this->json('GET', '/api/livres');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertCount(3, $reponse['data'] ?? []);
        foreach (['id', 'titre', 'auteur', 'categorie', 'annee', 'disponible'] as $cle) {
            self::assertArrayHasKey($cle, $reponse['data'][0], "Chaque livre doit avoir la clé « $cle ».");
        }
        self::assertArrayHasKey('links', $reponse);
        self::assertSame(1, $reponse['meta']['current_page'] ?? null);
        self::assertSame(3, $reponse['meta']['total'] ?? null);
    }

    public function test_m1_la_ressource_ne_divulgue_pas_les_champs_internes(): void
    {
        LivreFactory::createOne();

        $livre = $this->json('GET', '/api/livres')['data'][0];

        foreach (['resume', 'categorie_id', 'categorieId', 'emprunts'] as $interne) {
            self::assertArrayNotHasKey($interne, $livre, "« $interne » ne fait pas partie du contrat de l'API.");
        }
        self::assertIsString($livre['auteur'], 'auteur doit être le nom complet (texte).');
        self::assertIsString($livre['categorie'], 'categorie doit être le nom de la catégorie (texte).');
    }

    // ── Mission 7.2 🟢 — GET /api/livres/{id} ─────────────────────

    public function test_m2_un_livre_est_renvoye_par_son_id(): void
    {
        $livre = LivreFactory::createOne(['titre' => 'Fondation']);

        $reponse = $this->json('GET', '/api/livres/'.$livre->getId());

        self::assertResponseIsSuccessful();
        self::assertSame('Fondation', $reponse['data']['titre'] ?? null);
    }

    public function test_m2_un_livre_inconnu_renvoie_404_en_json(): void
    {
        self::assertSame('/api/livres/7', $this->url('api.livres.show', ['id' => 7]),
            "Nommez les routes de l'API api.livres.* pour ne pas écraser les routes web livres.*");

        $reponse = $this->json('GET', '/api/livres/999999');

        self::assertResponseStatusCodeSame(404);
        self::assertStringContainsString('json', (string) $this->client->getResponse()->headers->get('Content-Type'));
        self::assertSame(404, $reponse['status'] ?? null);
    }

    // ── Mission 7.3 🟡 — Filtres ───────────────────────────────────

    public function test_m3_on_filtre_par_categorie_et_disponibilite(): void
    {
        $sf = CategorieFactory::createOne(['slug' => 'science-fiction']);
        LivreFactory::createOne(['titre' => 'Dune', 'categorie' => $sf]);
        LivreFactory::new()->indisponible()->create(['titre' => '1984', 'categorie' => $sf]);
        LivreFactory::createOne(['titre' => 'Germinal']);

        self::assertCount(2, $this->json('GET', '/api/livres?categorie=science-fiction')['data']);

        $reponse = $this->json('GET', '/api/livres?categorie=science-fiction&disponible=1');
        self::assertCount(1, $reponse['data']);
        self::assertSame('Dune', $reponse['data'][0]['titre']);
    }

    // ── Mission 7.4 🟡 — POST protégé par jeton ───────────────────

    public function test_m4_sans_jeton_la_creation_est_refusee(): void
    {
        $this->json('POST', '/api/livres', ['titre' => 'X']);

        self::assertResponseStatusCodeSame(401);
    }

    public function test_m4_un_adherent_ne_peut_pas_creer_de_livre(): void
    {
        $jeton = $this->jetonDe($this->adherent());

        $this->json('POST', '/api/livres', ['titre' => 'X'], $jeton);

        self::assertResponseStatusCodeSame(403);
    }

    public function test_m4_un_jeton_inconnu_est_refuse(): void
    {
        $this->json('POST', '/api/livres', ['titre' => 'X'], 'un-jeton-invente');

        self::assertResponseStatusCodeSame(401);
    }

    public function test_m4_un_bibliothecaire_cree_un_livre(): void
    {
        $jeton = $this->jetonDe($this->bibliothecaire());

        $reponse = $this->json('POST', '/api/livres', [
            'titre' => 'Vingt mille lieues sous les mers',
            'auteur_id' => AuteurFactory::createOne()->getId(),
            'categorie_id' => CategorieFactory::createOne()->getId(),
            'annee' => 1870,
            'isbn' => '978-2-253-00630-0',
        ], $jeton);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Vingt mille lieues sous les mers', $reponse['data']['titre'] ?? null);
        self::assertNotNull($this->em()->getRepository(Livre::class)->findOneBy(['titre' => 'Vingt mille lieues sous les mers']));
    }

    public function test_m4_une_creation_invalide_renvoie_422(): void
    {
        $jeton = $this->jetonDe($this->bibliothecaire());

        $reponse = $this->json('POST', '/api/livres', ['titre' => ''], $jeton);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('titre', $reponse['errors'] ?? [], 'La réponse doit lister les erreurs par champ : {"errors": {"titre": [...]}}');
        self::assertArrayHasKey('auteur', $reponse['errors'] ?? []);
    }
}
