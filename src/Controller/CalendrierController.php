<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Repository\DemandeCongeRepository;
use App\Repository\JourFerieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/calendrier')]
#[IsGranted('ROLE_EMPLOYE')]
class CalendrierController extends AbstractController
{
    #[Route('', name: 'calendrier_index', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeCongeRepository $demandeRepository,
        JourFerieRepository $jourFerieRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $mois = (int) ($request->query->get('mois') ?? date('n'));
        $annee = (int) ($request->query->get('annee') ?? date('Y'));

        $debut = new \DateTimeImmutable(sprintf('%d-%02d-01', $annee, $mois));
        $fin = $debut->modify('last day of this month');

        // RG : le calendrier n'affiche que les congés approuvés et les jours fériés.
        $mesAbsences = $demandeRepository->findMesAbsencesDuMois($user, $debut, $fin, [StatutDemande::APPROUVE]);

        $joursFeries = $jourFerieRepository->createQueryBuilder('j')
            ->andWhere('j.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
        $feriesParDate = [];
        foreach ($joursFeries as $jourFerie) {
            $feriesParDate[$jourFerie->getDate()->format('Y-m-d')] = $jourFerie->getLibelle();
        }

        // Grille : du lundi de la semaine du 1er au dimanche de la semaine du dernier jour.
        $grilleDebut = $debut->modify('-' . ($debut->format('N') - 1) . ' days');
        $grilleFin = $fin->modify('+' . (7 - $fin->format('N')) . ' days');

        $aujourdhui = new \DateTimeImmutable('today');
        $semaines = [];
        $semaineCourante = [];
        $curseur = $grilleDebut;
        while ($curseur <= $grilleFin) {
            $cle = $curseur->format('Y-m-d');

            $semaineCourante[] = [
                'date' => $curseur,
                'jour' => (int) $curseur->format('j'),
                'dansMoisCourant' => $curseur >= $debut && $curseur <= $fin,
                'estAujourdhui' => $curseur->format('Y-m-d') === $aujourdhui->format('Y-m-d'),
                'estFerie' => isset($feriesParDate[$cle]),
                'libelleFerie' => $feriesParDate[$cle] ?? null,
            ];

            if (count($semaineCourante) === 7) {
                $semaines[] = $semaineCourante;
                $semaineCourante = [];
            }

            $curseur = $curseur->modify('+1 day');
        }

        $barresParSemaine = [];
        foreach ($semaines as $indexSemaine => $semaine) {
            $barresParSemaine[$indexSemaine] = $this->segmentsDeLaSemaine(
                $mesAbsences,
                $semaine[0]['date'],
                $semaine[6]['date']
            );
        }

        return $this->render('shared/calendrier/index.html.twig', [
            'mois' => $mois,
            'annee' => $annee,
            'moisPrecedent' => $debut->modify('-1 month'),
            'moisSuivant' => $debut->modify('+1 month'),
            'semaines' => $semaines,
            'barresParSemaine' => $barresParSemaine,
        ]);
    }

    /**
     * Calcule, pour une semaine donnée (7 jours), les segments de barre à afficher :
     * intersection de chaque demande avec la semaine (colonnes de grille), et attribution
     * d'une "voie" (lane) par recouvrement glouton pour empiler proprement les barres qui
     * se chevauchent dans le temps.
     *
     * @param DemandeConge[] $absences
     * @return array<int, array{colStart:int, colEnd:int, lane:int, typeLibelle:string, statutValue:string, statutLabel:string}>
     */
    private function segmentsDeLaSemaine(array $absences, \DateTimeImmutable $semaineDebut, \DateTimeImmutable $semaineFin): array
    {
        $segments = [];
        foreach ($absences as $demande) {
            if ($demande->getDateFin() < $semaineDebut || $demande->getDateDebut() > $semaineFin) {
                continue;
            }

            $segDebut = max($demande->getDateDebut(), $semaineDebut);
            $segFin = min($demande->getDateFin(), $semaineFin);

            $offsetDebut = (int) $semaineDebut->diff($segDebut)->days;
            $offsetFin = (int) $semaineDebut->diff($segFin)->days;

            $segments[] = [
                'colStart' => $offsetDebut + 1,
                'colEnd' => $offsetFin + 2,
                'lane' => 0,
                'typeLibelle' => $demande->getTypeConge()->getLibelle(),
                'statutValue' => $demande->getStatut()->value,
                'statutLabel' => $demande->getStatut()->label(),
            ];
        }

        usort($segments, static fn ($a, $b) => $a['colStart'] <=> $b['colStart']);

        $finDeVoie = [];
        foreach ($segments as &$segment) {
            $voieTrouvee = null;
            foreach ($finDeVoie as $voie => $finLigne) {
                if ($finLigne <= $segment['colStart']) {
                    $voieTrouvee = $voie;
                    break;
                }
            }
            $voieTrouvee ??= count($finDeVoie);
            $segment['lane'] = $voieTrouvee;
            $finDeVoie[$voieTrouvee] = $segment['colEnd'];
        }
        unset($segment);

        return $segments;
    }
}