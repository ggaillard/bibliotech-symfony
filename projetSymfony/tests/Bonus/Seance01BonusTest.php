<?php

namespace App\Tests\Bonus;

use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Missions 🔴 (autonomes, facultatives) — php bin/phpunit --group=bonus
 */
#[Group('bonus')]
class Seance01BonusTest extends TestCase
{
    public function test_s1_m5_la_page_404_est_personnalisee(): void
    {
        // En mode debug, Symfony affiche sa page d'erreur de développeur.
        // Le visiteur, lui, voit l'application en production : debug désactivé.
        // Sans debug, Symfony ne recompile rien : on lui donne un cache neuf à chaque fois.
        self::ensureKernelShutdown();
        $cache = sys_get_temp_dir().'/bibliotech-sans-debug-'.bin2hex(random_bytes(4));
        $_SERVER['APP_CACHE_DIR'] = $cache;

        try {
            $visiteur = static::createClient(['debug' => false]);
            $visiteur->request('GET', '/une-page-qui-n-existe-pas');

            self::assertResponseStatusCodeSame(404);
            self::assertSelectorTextContains('body', 'Page introuvable');
            self::assertSelectorExists('a[href="/livres"]', 'La page 404 doit proposer un lien vers le catalogue.');
            self::assertSelectorExists('nav.navbar', 'La page 404 doit garder le gabarit du site (base.html.twig).');
        } finally {
            self::ensureKernelShutdown();
            unset($_SERVER['APP_CACHE_DIR']);
            (new Filesystem())->remove($cache);
        }
    }
}
