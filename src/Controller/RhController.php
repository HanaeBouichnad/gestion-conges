<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Enum\StatutDemande;
use App\Repository\DemandeCongeRepository;
use App\Security\Voter\DemandeCongeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    #[Route('/{id}/annuler', name: 'rh_demande_annuler', methods: ['POST'])]
    public function annuler(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::ANNULER, $demande);

        if (!$this->isCsrfTokenValid('annuler'.$demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('rh_demandes_approuvees');
        }

        $demande->setStatut(StatutDemande::ANNULE);
        $demande->setDateModification(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'La demande a été annulée.');
        return $this->redirectToRoute('rh_demandes_approuvees');
    }
}