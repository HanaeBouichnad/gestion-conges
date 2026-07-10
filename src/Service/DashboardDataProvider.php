<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DemandeCongeRepository;
use App\Repository\JourFerieRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongeRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardDataProvider
{
    public function __construct(
        private readonly DemandeCongeRepository $demandeRepository,
        private readonly SoldeCongeRepository $soldeRepository,
        private readonly JourFerieRepository $jourFerieRepository,
        private readonly TypeCongeRepository $typeCongeRepository,
        private readonly Security $security,
    ) {
    }

    public function getData(User $user): array
    {
        $aApprouver = [];
        if ($this->security->isGranted('ROLE_RESPONSABLE')) {
            $aApprouver = $this->demandeRepository->findAValiderPar($user);
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return [
                'vueAdmin' => true,
                'aApprouver' => $aApprouver,
                'nbJoursFeries' => count($this->jourFerieRepository->findAll()),
                'nbTypesConge' => count($this->typeCongeRepository->findBy(['actif' => true])),
            ];
        }

        $mesDemandes = $this->demandeRepository->findMesDemandes($user);

        $counts = ['brouillon' => 0, 'en_attente' => 0, 'approuve' => 0];
        foreach ($mesDemandes as $demande) {
            if (isset($counts[$demande->getStatut()->value])) {
                $counts[$demande->getStatut()->value]++;
            }
        }

        $mesSoldes = $this->soldeRepository->findParEmploye($user);
        $soldeCongeAnnuel = 0;
        foreach ($mesSoldes as $solde) {
            if ($solde->getTypeConge()->getLibelle() === 'Congé annuel') {
                $soldeCongeAnnuel = $solde->getSolde();
                break;
            }
        }

        return [
            'vueAdmin' => false,
            'mesDemandes' => $mesDemandes,
            'mesDemandesRecentes' => array_slice($mesDemandes, 0, 5),
            'mesSoldes' => $mesSoldes,
            'aApprouver' => $aApprouver,
            'counts' => $counts,
            'soldeCongeAnnuel' => $soldeCongeAnnuel,
            'prochaineAbsence' => $this->demandeRepository->findProchaineAbsence($user),
        ];
    }
}
