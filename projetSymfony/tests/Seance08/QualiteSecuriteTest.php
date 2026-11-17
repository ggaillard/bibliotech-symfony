<?php

namespace App\Tests\Seance08;

use App\Tests\Factory\LivreFactory;
use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * SÉANCE 8 — Qualité, sécurité, mise en production
 *
 *   php bin/phpunit --group=seance-08
 */
#[Group('seance-08')]
class QualiteSecuriteTest extends TestCase
{
    // ── Mission 8.1 🟡 — Audit de sécurité : la faille XSS ────────

    public function test_m1_le_resume_d_un_livre_ne_peut_pas_injecter_de_script(): void
    {
        $livre = LivreFactory::createOne(['resume' => '<script>alert("piraté")</script>']);

        $this->get($this->url('livres.show', ['id' => $livre->getId()]));
        $html = (string) $this->client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('<script>alert("piraté")</script>', $html, 'Le script est inséré tel quel dans la page : faille XSS.');
        self::assertStringContainsString('&lt;script&gt;', $html, 'Le résumé doit être affiché comme du texte (échappé).');
    }

    // ── Mission 8.2 🟢 — Vos propres tests ─────────────────────────

    public function test_m2_vous_avez_ecrit_au_moins_deux_tests(): void
    {
        $dossier = static::getContainer()->getParameter('kernel.project_dir').'/tests/Etudiant';
        self::assertDirectoryExists($dossier, 'Créez le dossier tests/Etudiant et écrivez-y vos tests.');

        $nombre = 0;
        foreach (glob($dossier.'/*Test.php') as $fichier) {
            $nombre += preg_match_all('/public function test_?\w+\s*\(/', file_get_contents($fichier));
        }

        self::assertGreaterThanOrEqual(2, $nombre, 'Il faut au moins deux méthodes test… dans tests/Etudiant.');
    }
}
