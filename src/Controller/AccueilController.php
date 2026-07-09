<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DemandeCongeRepository;
use App\Repository\JourFerieRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'app_accueil', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function index(
        DemandeCongeRepository $demandeRepository,
        SoldeCongeRepository $soldeRepository,
        JourFerieRepository $jourFerieRepository,
        TypeCongeRepository $typeCongeRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $aApprouver = [];
        if ($this->isGranted('ROLE_RESPONSABLE')) {
            $aApprouver = $demandeRepository->findAValiderPar($user);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('accueil/index.html.twig', [
                'vueAdmin' => true,
                'aApprouver' => $aApprouver,
                'nbJoursFeries' => count($jourFerieRepository->findAll()),
                'nbTypesConge' => count($typeCongeRepository->findBy(['actif' => true])),
            ]);
        }

        $mesDemandes = $demandeRepository->findMesDemandes($user);

        return $this->render('accueil/index.html.twig', [
            'vueAdmin' => false,
            'mesDemandes' => array_slice($mesDemandes, 0, 5),
            'mesSoldes' => $soldeRepository->findParEmploye($user),
            'aApprouver' => $aApprouver,
        ]);
    }
}