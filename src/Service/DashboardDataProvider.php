<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\StatutDemande;
use App\Repository\DemandeCongeRepository;
use App\Repository\JourFerieRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardDataProvider
{
    public function __construct(
        private readonly DemandeCongeRepository $demandeRepository,
        private readonly SoldeCongeRepository $soldeRepository,
        private readonly JourFerieRepository $jourFerieRepository,
        private readonly TypeCongeRepository $typeCongeRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    public function getData(User $user, ?int $mois = null, ?int $annee = null): array
    {
        $aApprouver = [];
        if ($this->security->isGranted('ROLE_RESPONSABLE')) {
            $aApprouver = $this->demandeRepository->findAValiderPar($user);
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $debutMois = new \DateTimeImmutable('first day of this month');

            return [
                'vueAdmin' => true,
                'aApprouver' => $aApprouver,
                'nbJoursFeries' => count($this->jourFerieRepository->findAll()),
                'nbTypesConge' => count($this->typeCongeRepository->findBy(['actif' => true])),
                'nbDemandesEnAttente' => $this->demandeRepository->countParStatut(StatutDemande::EN_ATTENTE),
                'nbDemandesApprouveesCeMois' => $this->demandeRepository->countParStatutDepuis(StatutDemande::APPROUVE, $debutMois),
                'nbDemandesRefuseesCeMois' => $this->demandeRepository->countParStatutDepuis(StatutDemande::REFUSE, $debutMois),
                'nbEmployesActifs' => $this->userRepository->count([]),
                'prochainsJoursFeries' => $this->jourFerieRepository->findProchains(5),
                'utilisateursApercu' => $this->userRepository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC'], 5),
            ] + $this->getGrilleMois($mois, $annee);
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

    /**
     * Grille du mini calendrier du mois affiché sur le tableau de bord Admin
     * (jour courant + jours fériés uniquement, pas de barres d'absence).
     */
    private function getGrilleMois(?int $mois, ?int $annee): array
    {
        $mois ??= (int) date('n');
        $annee ??= (int) date('Y');

        $debut = new \DateTimeImmutable(sprintf('%d-%02d-01', $annee, $mois));
        $fin = $debut->modify('last day of this month');

        $joursFeries = $this->jourFerieRepository->createQueryBuilder('j')
            ->andWhere('j.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
        $feriesParDate = [];
        foreach ($joursFeries as $jourFerie) {
            $feriesParDate[$jourFerie->getDate()->format('Y-m-d')] = $jourFerie->getLibelle();
        }

        $grilleDebut = $debut->modify('-' . ($debut->format('N') - 1) . ' days');
        $grilleFin = $fin->modify('+' . (7 - $fin->format('N')) . ' days');

        $aujourdhui = new \DateTimeImmutable('today');
        $semaines = [];
        $semaineCourante = [];
        $curseur = $grilleDebut;
        while ($curseur <= $grilleFin) {
            $cle = $curseur->format('Y-m-d');

            $semaineCourante[] = [
                'jour' => (int) $curseur->format('j'),
                'dansMoisCourant' => $curseur >= $debut && $curseur <= $fin,
                'estAujourdhui' => $cle === $aujourdhui->format('Y-m-d'),
                'estFerie' => isset($feriesParDate[$cle]),
                'libelleFerie' => $feriesParDate[$cle] ?? null,
            ];

            if (count($semaineCourante) === 7) {
                $semaines[] = $semaineCourante;
                $semaineCourante = [];
            }

            $curseur = $curseur->modify('+1 day');
        }

        return [
            'mois' => $mois,
            'annee' => $annee,
            'moisPrecedent' => $debut->modify('-1 month'),
            'moisSuivant' => $debut->modify('+1 month'),
            'semaines' => $semaines,
        ];
    }
}
