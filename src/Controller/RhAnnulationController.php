<?php

namespace App\Controller;

use App\Entity\DemandeAnnulation;
use App\Entity\JournalSolde;
use App\Entity\SoldeConge;
use App\Entity\User;
use App\Enum\OrigineAnnulation;
use App\Enum\StatutDemande;
use App\Enum\StatutDemandeAnnulation;
use App\Repository\DemandeAnnulationRepository;
use App\Repository\SoldeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rh/demandes-annulation')]
#[IsGranted('ROLE_RH')]
class RhAnnulationController extends AbstractController
{
    #[Route('', name: 'rh_annulation_index', methods: ['GET'])]
    public function index(DemandeAnnulationRepository $repository): Response
    {
        return $this->render('rh/demandes_annulation.html.twig', [
            'demandesAnnulation' => $repository->findEnAttente(),
        ]);
    }

    #[Route('/{id}/accepter', name: 'rh_annulation_accepter', methods: ['POST'])]
    public function accepter(
        Request $request,
        DemandeAnnulation $demandeAnnulation,
        EntityManagerInterface $em,
        SoldeCongeRepository $soldeRepository
    ): Response {
        if (!$this->isCsrfTokenValid('accepter'.$demandeAnnulation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('rh_annulation_index');
        }

        if ($demandeAnnulation->getStatut() !== StatutDemandeAnnulation::EN_ATTENTE) {
            $this->addFlash('error', 'Cette demande d\'annulation a déjà été traitée.');
            return $this->redirectToRoute('rh_annulation_index');
        }

        /** @var User $rh */
        $rh = $this->getUser();
        $demande = $demandeAnnulation->getDemandeConge();

        $demande->setStatut(StatutDemande::ANNULE);
        $demande->setOrigineAnnulation(OrigineAnnulation::RH);
        $demande->setDateModification(new \DateTimeImmutable());

        $demandeAnnulation->setStatut(StatutDemandeAnnulation::ACCEPTEE);
        $demandeAnnulation->setTraitePar($rh);
        $demandeAnnulation->setDateTraitement(new \DateTimeImmutable());

        $solde = $soldeRepository->findOneParEmployeEtType($demande->getEmploye(), $demande->getTypeConge());
        if (!$solde) {
            $solde = new SoldeConge();
            $solde->setEmploye($demande->getEmploye());
            $solde->setTypeConge($demande->getTypeConge());
            $solde->setSolde(0.0);
        }

        $nombreJours = $demande->getNombreJours();
        $solde->setSolde($solde->getSolde() + $nombreJours);
        $em->persist($solde);

        $journal = new JournalSolde();
        $journal->setEmploye($demande->getEmploye());
        $journal->setTypeConge($demande->getTypeConge());
        $journal->setAuteur($rh);
        $journal->setNombreJours($nombreJours);
        $journal->setNote(sprintf(
            "Recrédit automatique suite à l'acceptation de la demande d'annulation de la demande de congé #%d (motif : %s)",
            $demande->getId(),
            $demandeAnnulation->getMotif()
        ));
        $em->persist($journal);

        $em->flush();

        $this->addFlash('success', "La demande d'annulation a été acceptée et le solde a été recrédité.");
        return $this->redirectToRoute('rh_annulation_index');
    }

    #[Route('/{id}/refuser', name: 'rh_annulation_refuser', methods: ['POST'])]
    public function refuser(Request $request, DemandeAnnulation $demandeAnnulation, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('refuser'.$demandeAnnulation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('rh_annulation_index');
        }

        if ($demandeAnnulation->getStatut() !== StatutDemandeAnnulation::EN_ATTENTE) {
            $this->addFlash('error', 'Cette demande d\'annulation a déjà été traitée.');
            return $this->redirectToRoute('rh_annulation_index');
        }

        /** @var User $rh */
        $rh = $this->getUser();

        $demandeAnnulation->setStatut(StatutDemandeAnnulation::REFUSEE);
        $demandeAnnulation->setTraitePar($rh);
        $demandeAnnulation->setDateTraitement(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', "La demande d'annulation a été refusée. La demande de congé reste approuvée.");
        return $this->redirectToRoute('rh_annulation_index');
    }
}
