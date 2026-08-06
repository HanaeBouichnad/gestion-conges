<?php

namespace App\Service;

use App\Entity\TypeConge;
use App\Entity\User;
use App\Repository\DemandeCongeRepository;
use App\Repository\SoldeCongeRepository;

class SoldeStatsProvider
{
    public function __construct(
        private readonly SoldeCongeRepository $soldeCongeRepository,
        private readonly DemandeCongeRepository $demandeCongeRepository,
        private readonly JoursOuvresCalculator $joursOuvresCalculator,
    ) {
    }

    public function calculer(User $employe): array
    {
        $annee = (int) date('Y');
        $today = new \DateTimeImmutable('today');
        $dateDebutAnnee = new \DateTimeImmutable("{$annee}-01-01");
        $dateFinAnnee = new \DateTimeImmutable("{$annee}-12-31");

        $utiliseParType = [];
        $planifieParType = [];
        foreach ($this->demandeCongeRepository->findApprouveesDeLAnnee($employe, $annee) as $demande) {
            $typeId = $demande->getTypeConge()->getId();
            $jours = $this->joursOuvresCalculator->calculer($demande);

            if ($demande->getDateDebut() <= $today) {
                $utiliseParType[$typeId] = ($utiliseParType[$typeId] ?? 0.0) + $jours;
            } else {
                $planifieParType[$typeId] = ($planifieParType[$typeId] ?? 0.0) + $jours;
            }
        }

        $parType = [];
        $globalDisponible = 0.0;
        $globalUtilise = 0.0;
        $globalPlanifie = 0.0;
        $globalTotal = 0.0;

        foreach ($this->soldeCongeRepository->findParEmploye($employe) as $solde) {
            $type = $solde->getTypeConge();
            $typeId = $type->getId();

            $disponible = $solde->getSolde();
            $utilise = $utiliseParType[$typeId] ?? 0.0;
            $planifie = $planifieParType[$typeId] ?? 0.0;
            $total = $type->getSoldeMax() ?? ($disponible + $utilise + $planifie);

            $parType[] = [
                'typeConge' => $type,
                'typeKey' => $this->typeKey($type),
                'disponible' => $disponible,
                'utilise' => $utilise,
                'planifie' => $planifie,
                'total' => $total,
                'pctUtilise' => $this->pourcentage($utilise, $total),
                'pctPlanifie' => $this->pourcentage($planifie, $total),
                'pctDisponible' => $this->pourcentage($disponible, $total),
            ];

            $globalDisponible += $disponible;
            $globalUtilise += $utilise;
            $globalPlanifie += $planifie;
            $globalTotal += $total;
        }

        return [
            'annee' => $annee,
            'dateDebut' => $dateDebutAnnee,
            'dateFin' => $dateFinAnnee,
            'parType' => $parType,
            'global' => [
                'disponible' => $globalDisponible,
                'utilise' => $globalUtilise,
                'planifie' => $globalPlanifie,
                'total' => $globalTotal,
                'pctUtilise' => $this->pourcentage($globalUtilise, $globalTotal),
                'pctPlanifie' => $this->pourcentage($globalPlanifie, $globalTotal),
                'pctDisponible' => $this->pourcentage($globalDisponible, $globalTotal),
            ],
        ];
    }

    private function pourcentage(float $valeur, float $total): float
    {
        return $total > 0 ? round($valeur / $total * 100, 1) : 0.0;
    }

    private function typeKey(TypeConge $type): string
    {
        $libelleLower = strtolower($type->getLibelle());

        return match (true) {
            str_contains($libelleLower, 'annuel') => 'annuel',
            str_contains($libelleLower, 'maladie') => 'maladie',
            str_contains($libelleLower, 'récup') || str_contains($libelleLower, 'recup') => 'recuperation',
            str_contains($libelleLower, 'exceptionnel') => 'exceptionnel',
            str_contains($libelleLower, 'sans solde') => 'sans-solde',
            default => 'defaut',
        };
    }
}
