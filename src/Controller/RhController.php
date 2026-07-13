<?php

namespace App\Controller;

use App\Repository\DemandeCongeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\TypeCongeRepository;

#[Route('/rh')]
#[IsGranted('ROLE_RH')]
class RhController extends AbstractController
{
    #[Route('/demandes-approuvees', name: 'rh_demandes_approuvees', methods: ['GET'])]
    public function demandesApprouvees(DemandeCongeRepository $repository): Response
    {
        return $this->render('rh/demandes_approuvees.html.twig', [
            'demandes' => $repository->findApprouvees(),
        ]);
    }

    #[Route('/demandes', name: 'rh_demandes_toutes', methods: ['GET'])]
    public function toutesLesDemandes(Request $request, DemandeCongeRepository $repository, TypeCongeRepository $typeCongeRepository): Response
    {
        $statut = $request->query->get('statut');
        $typeCongeId = $request->query->get('type') ? (int) $request->query->get('type') : null;

        return $this->render('rh/demandes_toutes.html.twig', [
            'demandes' => $repository->findToutesAvecFiltres($statut, $typeCongeId),
            'types' => $typeCongeRepository->findAll(),
            'statutSelectionne' => $statut,
            'typeSelectionne' => $typeCongeId,
        ]);
    }
}