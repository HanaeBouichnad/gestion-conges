<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DashboardDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(Request $request, DashboardDataProvider $dashboardDataProvider): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->render('home/index.html.twig');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('solde_mes_soldes');
        }

        $mois = $request->query->get('mois') ? (int) $request->query->get('mois') : null;
        $annee = $request->query->get('annee') ? (int) $request->query->get('annee') : null;

        return $this->render('home/index.html.twig', $dashboardDataProvider->getData($mois, $annee));
    }
}
