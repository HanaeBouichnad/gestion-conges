<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DashboardDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(DashboardDataProvider $dashboardDataProvider): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->render('home/index.html.twig');
        }

        return $this->render('home/index.html.twig', $dashboardDataProvider->getData($user));
    }
}
