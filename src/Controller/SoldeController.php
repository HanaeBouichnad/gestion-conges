<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\SoldeCongeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SoldeController extends AbstractController
{
    #[Route('/mon-solde', name: 'solde_mes_soldes', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function mesSoldes(SoldeCongeRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('shared/solde/mes_soldes.html.twig', [
            'soldes' => $repository->findParEmploye($user),
        ]);
    }
}
