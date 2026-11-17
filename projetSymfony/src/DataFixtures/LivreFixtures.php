<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Livre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Vingt livres répartis dans les catégories créées par CategorieFixtures.
 * Les ISBN sont fictifs.
 */
class LivreFixtures extends Fixture implements DependentFixtureInterface
{
    private const LIVRES = [
        // [titre, auteur, année, pages, catégorie, disponible, résumé]
        ['Les Misérables', 'Victor Hugo', 1862, 1200, 'roman', true, "Un chef-d'œuvre de la littérature française qui suit la vie de Jean Valjean."],
        ['Notre-Dame de Paris', 'Victor Hugo', 1831, 940, 'roman', true, 'Quasimodo, Esmeralda et Frollo autour de la cathédrale.'],
        ['Germinal', 'Émile Zola', 1885, 590, 'roman', false, 'La grève des mineurs du Nord, vue par Étienne Lantier.'],
        ['Madame Bovary', 'Gustave Flaubert', 1857, 480, 'roman', true, "Emma Bovary, l'ennui de province et les illusions romanesques."],
        ["L'Étranger", 'Albert Camus', 1942, 186, 'roman', true, 'Meursault, un homme étranger à sa propre vie.'],
        ['Dune', 'Frank Herbert', 1965, 828, 'science-fiction', true, "Un classique de la science-fiction dans l'univers d'Arrakis."],
        ['Fondation', 'Isaac Asimov', 1951, 416, 'science-fiction', true, "La psychohistoire contre la chute de l'Empire galactique."],
        ['1984', 'George Orwell', 1949, 376, 'science-fiction', false, 'Big Brother vous regarde.'],
        ['Le Seigneur des Anneaux', 'J.R.R. Tolkien', 1954, 1216, 'fantastique', true, "L'épopée fantasy la plus célèbre de tous les temps."],
        ['Le Hobbit', 'J.R.R. Tolkien', 1937, 400, 'fantastique', true, "Bilbo Sacquet part à l'aventure avec treize nains."],
        ["Le Crime de l'Orient-Express", 'Agatha Christie', 1934, 256, 'policier', true, 'Hercule Poirot enquête dans un train bloqué par la neige.'],
        ['Le Chien jaune', 'Georges Simenon', 1931, 192, 'policier', true, 'Le commissaire Maigret à Concarneau.'],
        ['Steve Jobs', 'Walter Isaacson', 2011, 650, 'biographie', true, "La biographie officielle du co-fondateur d'Apple."],
        ['Marie Curie', 'Ève Curie', 1938, 380, 'biographie', true, 'La vie de Marie Curie racontée par sa fille.'],
        ['Histoire de France', 'Pierre Martin', 2024, 195, 'histoire', false, "Un voyage à travers l'histoire de France des origines à nos jours."],
        ['La Révolution française', 'Jean Dupont', 2019, 320, 'histoire', true, 'De 1789 au Consulat, les années qui ont changé la France.'],
        ['Guide Symfony pour développeurs', 'Marie Dubois', 2023, 280, 'informatique', true, 'Maîtriser le framework Symfony avec des exemples pratiques.'],
        ['Clean Code', 'Robert C. Martin', 2008, 464, 'informatique', true, 'Écrire du code lisible, testable et maintenable.'],
        ['Le Petit Prince', 'Antoine de Saint-Exupéry', 1943, 96, 'jeunesse', true, "« On ne voit bien qu'avec le cœur. »"],
        ["Harry Potter à l'école des sorciers", 'J.K. Rowling', 1997, 320, 'jeunesse', true, 'Un jeune sorcier découvre Poudlard.'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::LIVRES as $i => [$titre, $auteur, $annee, $pages, $slug, $disponible, $resume]) {
            $livre = (new Livre())
                ->setTitre($titre)
                ->setAuteur($auteur)
                ->setAnnee($annee)
                ->setNbPages($pages)
                ->setIsbn(sprintf('978-2-1234-%04d-%d', 5678 + $i, $i % 10))
                ->setResume($resume)
                ->setDisponible($disponible)
                ->setCategorie($this->getReference('categorie-'.$slug, Categorie::class));

            $manager->persist($livre);
        }

        $manager->flush();
    }

    /** L'ordre compte : les livres ont besoin des catégories. */
    public function getDependencies(): array
    {
        return [CategorieFixtures::class];
    }
}
