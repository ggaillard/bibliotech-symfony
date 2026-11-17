<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Les données de démonstration : les huit catégories de la médiathèque.
 *
 *   php bin/console doctrine:fixtures:load     ⚠️ vide la base avant de la remplir
 */
class CategorieFixtures extends Fixture
{
    public const CATEGORIES = [
        // slug => [nom, description, couleur, icône]
        'roman' => ['Roman', 'Œuvres de fiction narrative en prose', '#EF4444', 'fas fa-book'],
        'science-fiction' => ['Science-Fiction', "Littérature d'anticipation et de science-fiction", '#3B82F6', 'fas fa-rocket'],
        'fantastique' => ['Fantastique', 'Littérature fantastique et fantasy', '#8B5CF6', 'fas fa-magic'],
        'policier' => ['Policier', 'Romans policiers et thrillers', '#1F2937', 'fas fa-search'],
        'biographie' => ['Biographie', 'Biographies et autobiographies', '#F59E0B', 'fas fa-user'],
        'histoire' => ['Histoire', "Livres d'histoire et documentaires historiques", '#10B981', 'fas fa-landmark'],
        'informatique' => ['Informatique', 'Manuels et guides techniques en informatique', '#6366F1', 'fas fa-laptop-code'],
        'jeunesse' => ['Jeunesse', 'Littérature pour enfants et adolescents', '#EC4899', 'fas fa-child'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $slug => [$nom, $description, $couleur, $icone]) {
            $categorie = (new Categorie())
                ->setNom($nom)
                ->setDescription($description)
                ->setSlug($slug)
                ->setCouleur($couleur)
                ->setIcone($icone)
                ->setActive(true);

            $manager->persist($categorie);

            // Une « référence » permet à LivreFixtures de retrouver cette catégorie
            $this->addReference('categorie-'.$slug, $categorie);
        }

        $manager->flush();
    }
}
