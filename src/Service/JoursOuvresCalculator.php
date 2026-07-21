<?php

namespace App\Service;

use App\Entity\DemandeConge;
use App\Enum\PeriodeJournee;
use App\Repository\JourFerieRepository;

class JoursOuvresCalculator
{
    public function __construct(private readonly JourFerieRepository $jourFerieRepository)
    {
    }

    public function calculer(DemandeConge $demande): float
    {
        $debut = $demande->getDateDebut();
        $fin = $demande->getDateFin();

        if ($demande->getPeriode() !== PeriodeJournee::JOURNEE_COMPLETE && $debut == $fin) {
            return $this->estJourOuvre($debut) ? 0.5 : 0.0;
        }

        $datesFeriees = $this->datesFerieesEntre($debut, $fin);

        $jours = 0;
        $curseur = $debut;
        while ($curseur <= $fin) {
            if (!$this->estWeekend($curseur) && !isset($datesFeriees[$curseur->format('Y-m-d')])) {
                $jours++;
            }
            $curseur = $curseur->modify('+1 day');
        }

        return (float) $jours;
    }

    private function estJourOuvre(\DateTimeImmutable $date): bool
    {
        return !$this->estWeekend($date) && !isset($this->datesFerieesEntre($date, $date)[$date->format('Y-m-d')]);
    }

    private function estWeekend(\DateTimeImmutable $date): bool
    {
        return (int) $date->format('N') >= 6;
    }

    /** @return array<string, true> */
    private function datesFerieesEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        $joursFeries = $this->jourFerieRepository->createQueryBuilder('j')
            ->andWhere('j.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        $dates = [];
        foreach ($joursFeries as $jourFerie) {
            $dates[$jourFerie->getDate()->format('Y-m-d')] = true;
        }

        return $dates;
    }
}
