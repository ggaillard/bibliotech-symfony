<?php

namespace App\Tests\Factory;

use App\Entity\Livre;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Fabrique de livres pour les tests :
 *   LivreFactory::createOne(['titre' => 'Germinal'])
 *   LivreFactory::createMany(3)
 *   LivreFactory::new()->indisponible()->create()
 *
 * @extends PersistentObjectFactory<Livre>
 */
final class LivreFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Livre::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'titre' => rtrim(self::faker()->unique()->sentence(3), '.'),
            'auteur' => self::faker()->name(),
            'annee' => self::faker()->numberBetween(1850, 2025),
            'nbPages' => self::faker()->numberBetween(80, 900),
            'isbn' => self::faker()->unique()->isbn13(),
            'resume' => self::faker()->paragraph(),
            'disponible' => true,
            // Chaque livre reçoit sa propre catégorie, créée par la fabrique de catégories
            'categorie' => CategorieFactory::new(),
        ];
    }

    /** Livre déjà emprunté */
    public function indisponible(): static
    {
        return $this->with(['disponible' => false]);
    }
}
