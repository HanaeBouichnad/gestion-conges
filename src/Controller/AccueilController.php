<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DashboardDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'app_accueil', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function index(DashboardDataProvider $dashboardDataProvider): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('accueil/index.html.twig', $dashboardDataProvider->getData($user));
    }
}