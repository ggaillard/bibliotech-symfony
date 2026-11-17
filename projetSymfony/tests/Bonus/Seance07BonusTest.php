<?php

namespace App\Tests\Bonus;

use App\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('bonus')]
class Seance07BonusTest extends TestCase
{
    public function test_s7_m5_on_obtient_un_jeton_avec_ses_identifiants(): void
    {
        $this->creerCompte(['ROLE_BIBLIOTHECAIRE'], 'acquisitions@bibliotech.test', 'mot-de-passe-du-logiciel');

        $reponse = $this->json('POST', '/api/jetons', [
            'email' => 'acquisitions@bibliotech.test',
            'password' => 'mot-de-passe-du-logiciel',
            'appareil' => 'Logiciel d\'acquisitions',
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertNotEmpty($reponse['jeton'] ?? null);

        // Le jeton obtenu ouvre bien l'écriture
        $this->json('POST', '/api/livres', ['titre' => ''], $reponse['jeton']);
        self::assertResponseStatusCodeSame(422);
    }

    public function test_s7_m5_les_identifiants_faux_donnent_le_meme_message_et_sont_limites(): void
    {
        $this->client->disableReboot();
        $this->creerCompte([], 'lecteur@bibliotech.test', 'bon-mot-de-passe');

        $mauvais = $this->json('POST', '/api/jetons', ['email' => 'lecteur@bibliotech.test', 'password' => 'faux', 'appareil' => 'x']);
        self::assertResponseStatusCodeSame(422);
        $inconnu = $this->json('POST', '/api/jetons', ['email' => 'inconnu@bibliotech.test', 'password' => 'faux', 'appareil' => 'x']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame($mauvais, $inconnu, 'Même réponse pour un e-mail inconnu et un mauvais mot de passe.');

        for ($i = 3; $i <= 5; ++$i) {
            $this->json('POST', '/api/jetons', ['email' => 'lecteur@bibliotech.test', 'password' => 'faux'.$i, 'appareil' => 'x']);
        }
        $this->json('POST', '/api/jetons', ['email' => 'lecteur@bibliotech.test', 'password' => 'bon-mot-de-passe', 'appareil' => 'x']);
        self::assertResponseStatusCodeSame(429, 'Au-delà de 5 essais par minute : 429 Too Many Requests.');
    }
}
