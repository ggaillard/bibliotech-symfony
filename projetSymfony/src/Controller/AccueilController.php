<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    /**
     * Page d'accueil : quelques chiffres et trois livres mis en avant.
     *
     * Les repositories arrivent en argument : Symfony les « injecte » tout seul
     * d'après le type déclaré (autowiring).
     */
    #[Route('/', name: 'home')]
    public function index(LivreRepository $livres, CategorieRepository $categories): Response
    {
        return $this->render('accueil/index.html.twig', [
            'stats' => [
                'totalLivres' => $livres->count(),
                'livresDisponibles' => $livres->countDisponibles(),
                'totalCategories' => $categories->countActives(),
            ],
            'livresEnVedette' => $livres->findNouveautes(3),
        ]);
    }
}
