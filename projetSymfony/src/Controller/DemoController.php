<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DemoController extends AbstractController
{
    /**
     * Démonstration : paramètre dans l'URL (séance 1).
     * /demo/hello/Lea → $nom = 'Lea' ; /demo/hello → valeur par défaut 'Étudiant'.
     * La valeur par défaut de l'argument PHP rend le paramètre {nom} facultatif.
     */
    #[Route('/demo/hello/{nom}', name: 'demo.hello', methods: ['GET'])]
    public function hello(string $nom = 'Étudiant'): Response
    {
        return $this->render('demo/hello.html.twig', ['nom' => $nom]);
    }
}
