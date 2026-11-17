<?php

namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Le « repository » rassemble les requêtes sur la table categorie.
 * Il hérite déjà de find($id), findAll(), findBy([...]), findOneBy([...]), count([...]).
 *
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    /**
     * Catégories visibles sur le site, par ordre alphabétique.
     *
     * @return Categorie[]
     */
    public function findActives(): array
    {
        return $this->findBy(['active' => true], ['nom' => 'ASC']);
    }

    public function countActives(): int
    {
        return $this->count(['active' => true]);
    }
}
