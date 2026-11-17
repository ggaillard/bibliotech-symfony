<?php

namespace App\Repository;

use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Les requêtes sur la table livre.
 *
 * @extends ServiceEntityRepository<Livre>
 */
class LivreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livre::class);
    }

    /**
     * Le point de départ des listes de livres : chaque livre avec sa catégorie,
     * par ordre alphabétique. Un QueryBuilder se complète ensuite (->andWhere(…)).
     *
     * leftJoin + addSelect charge la catégorie dans la MÊME requête SQL :
     * sans cela, Doctrine ferait une requête de plus par livre affiché (problème « N + 1 »).
     */
    public function creerRequeteCatalogue(): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.categorie', 'c')
            ->addSelect('c')
            ->orderBy('l.titre', 'ASC');
    }

    /**
     * @return Livre[]
     */
    public function findCatalogue(): array
    {
        return $this->creerRequeteCatalogue()->getQuery()->getResult();
    }

    public function countDisponibles(): int
    {
        return $this->count(['disponible' => true]);
    }

    /**
     * Les derniers livres disponibles ajoutés au catalogue.
     *
     * @return Livre[]
     */
    public function findNouveautes(int $nombre = 3): array
    {
        return $this->creerRequeteCatalogue()
            ->andWhere('l.disponible = true')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults($nombre)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par titre ou auteur : /recherche?q=hugo
     *
     * La condition OR est écrite d'un seul bloc : Doctrine l'entoure de parenthèses
     * dès qu'on ajoute un andWhere(), ce qui produit
     *   WHERE (titre LIKE ? OR auteur LIKE ?) AND …
     * Sans parenthèses, le OR « avalerait » les autres conditions.
     *
     * :terme est un PARAMÈTRE : la saisie n'est jamais collée dans le SQL (injection SQL impossible).
     *
     * @return Livre[]
     */
    public function rechercher(string $terme): array
    {
        return $this->creerRequeteCatalogue()
            ->andWhere('l.titre LIKE :terme OR l.auteur LIKE :terme')
            ->setParameter('terme', '%'.$terme.'%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Quelques autres livres de la même catégorie.
     *
     * @return Livre[]
     */
    public function findMemeCategorie(Livre $livre, int $nombre = 3): array
    {
        if (null === $livre->getCategorie()) {
            return [];
        }

        return $this->creerRequeteCatalogue()
            ->andWhere('l.categorie = :categorie')
            ->andWhere('l.id != :id')
            ->setParameter('categorie', $livre->getCategorie())
            ->setParameter('id', $livre->getId())
            ->setMaxResults($nombre)
            ->getQuery()
            ->getResult();
    }
}
