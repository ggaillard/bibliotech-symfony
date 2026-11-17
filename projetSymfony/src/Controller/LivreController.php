<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Le catalogue public.
 *
 * Chaque attribut #[Route] associe une URL (et un verbe HTTP) à une méthode.
 * name: '…' donne un nom à la route : dans les vues on écrit
 * path('livres.index') plutôt que '/livres' — si l'URL change, rien ne casse.
 */
class LivreController extends AbstractController
{
    /**
     * Catalogue public : tous les livres, avec leur catégorie.
     */
    #[Route('/livres', name: 'livres.index', methods: ['GET'])]
    public function index(LivreRepository $livres, CategorieRepository $categories): Response
    {
        $catalogue = $livres->findCatalogue();

        return $this->render('livres/index.html.twig', [
            'livres' => $catalogue,
            'stats' => [
                'totalLivres' => count($catalogue),
                'livresDisponibles' => count(array_filter($catalogue, fn ($livre) => $livre->isDisponible())),
                'totalCategories' => $categories->countActives(),
            ],
        ]);
    }

    /**
     * Fiche d'un livre : /livre/3
     * requirements : {id} doit être un nombre. find() renvoie null si l'id n'existe pas :
     * on lève alors une exception « 404 Not Found ».
     */
    #[Route('/livre/{id}', name: 'livres.show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, LivreRepository $livres): Response
    {
        $livre = $livres->find($id);

        if (null === $livre) {
            throw $this->createNotFoundException("Le livre n°$id n'existe pas.");
        }

        return $this->render('livres/show.html.twig', [
            'livre' => $livre,
            'livresAssocies' => $livres->findMemeCategorie($livre, 3),
        ]);
    }

    /**
     * Recherche par titre ou auteur : /recherche?q=hugo
     * $request->query contient les paramètres de l'URL (après le « ? »).
     */
    #[Route('/recherche', name: 'livres.search', methods: ['GET'])]
    public function search(Request $request, LivreRepository $livres): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        $resultats = '' === $query ? $livres->findCatalogue() : $livres->rechercher($query);

        return $this->render('livres/search.html.twig', [
            'livres' => $resultats,
            'query' => $query,
            'total' => count($resultats),
        ]);
    }
}
