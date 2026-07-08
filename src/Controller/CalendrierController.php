<?php

namespace App\Controller;

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
    public function index(Request $request, DemandeCongeRepository $demandeRepository, JourFerieRepository $jourFerieRepository): Response
    {
        $mois = (int) ($request->query->get('mois') ?? date('n'));
        $annee = (int) ($request->query->get('annee') ?? date('Y'));

        $debut = new \DateTimeImmutable(sprintf('%d-%02d-01', $annee, $mois));
        $fin = $debut->modify('last day of this month');

        $demandes = $demandeRepository->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.dateDebut <= :fin AND d.dateFin >= :debut')
            ->setParameter('statut', \App\Enum\StatutDemande::APPROUVE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        $joursFeries = $jourFerieRepository->createQueryBuilder('j')
            ->andWhere('j.date BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        return $this->render('calendrier/index.html.twig', [
            'demandes' => $demandes,
            'joursFeries' => $joursFeries,
            'mois' => $mois,
            'annee' => $annee,
            'moisPrecedent' => $debut->modify('-1 month'),
            'moisSuivant' => $debut->modify('+1 month'),
        ]);
    }
}