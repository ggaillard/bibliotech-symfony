<?php

namespace App\Tests\Factory;

use App\Entity\Categorie;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Fabrique de catégories pour les tests :
 *   CategorieFactory::createOne()                       une catégorie au hasard
 *   CategorieFactory::createOne(['nom' => 'Roman'])     en imposant des valeurs
 *   CategorieFactory::new()->inactive()->create()       une catégorie masquée
 *
 * @extends PersistentObjectFactory<Categorie>
 */
final class CategorieFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Categorie::class;
    }

    /** Les valeurs par défaut : faker invente des données réalistes. */
    #[\Override]
    protected function defaults(): array|callable
    {
        $nom = ucfirst(self::faker()->unique()->words(2, true));

        return [
            'nom' => $nom,
            'description' => self::faker()->sentence(),
            'slug' => (new AsciiSlugger())->slug($nom)->lower()->toString(),
            'couleur' => strtoupper(self::faker()->hexColor()),
            'icone' => 'fas fa-book',
            'active' => true,
        ];
    }

    /** Catégorie masquée sur le site */
    public function inactive(): static
    {
        return $this->with(['active' => false]);
    }
}
