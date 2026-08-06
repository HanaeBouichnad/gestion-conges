<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DemandeAnnulationRepository;
use App\Repository\DemandeCongeRepository;
use App\Service\SoldeStatsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SoldeController extends AbstractController
{
    #[Route('/mon-solde', name: 'solde_mes_soldes', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function mesSoldes(
        SoldeStatsProvider $soldeStatsProvider,
        DemandeCongeRepository $demandeCongeRepository,
        DemandeAnnulationRepository $demandeAnnulationRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $stats = $soldeStatsProvider->calculer($user);

        $aApprouver = [];
        if ($this->isGranted('ROLE_RESPONSABLE')) {
            $aApprouver = $demandeCongeRepository->findAValiderPar($user);
        }

        $aAnnuler = 0;
        if ($this->isGranted('ROLE_RH')) {
            $aAnnuler = $demandeAnnulationRepository->countEnAttente();
        }

        return $this->render('shared/solde/mes_soldes.html.twig', [
            'stats' => $stats,
            'aApprouver' => $aApprouver,
            'aAnnuler' => $aAnnuler,
        ]);
    }
}
