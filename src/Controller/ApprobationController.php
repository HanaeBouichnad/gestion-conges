<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Form\RefusDemandeType;
use App\Repository\DemandeCongeRepository;
use App\Security\Voter\DemandeCongeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/approbations')]
#[IsGranted('ROLE_RESPONSABLE')]
class ApprobationController extends AbstractController
{
    #[Route('', name: 'approbation_index', methods: ['GET'])]
    public function index(DemandeCongeRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('approbation/index.html.twig', [
            'demandes' => $repository->findAValiderPar($user),
        ]);
    }

    #[Route('/{id}/approuver', name: 'approbation_approuver', methods: ['POST'])]
    public function approuver(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::APPROUVER, $demande);

        if (!$this->isCsrfTokenValid('approuver'.$demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('approbation_index');
        }

        $demande->setStatut(StatutDemande::APPROUVE);
        $demande->setDateModification(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'La demande a été approuvée.');
        return $this->redirectToRoute('approbation_index');
    }

    #[Route('/{id}/refuser', name: 'approbation_refuser', methods: ['GET', 'POST'])]
    public function refuser(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::APPROUVER, $demande);

        $form = $this->createForm(RefusDemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demande->setStatut(StatutDemande::REFUSE);
            $demande->setDateModification(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'La demande a été refusée.');
            return $this->redirectToRoute('approbation_index');
        }

        return $this->render('approbation/refuser.html.twig', [
            'form' => $form,
            'demande' => $demande,
        ]);
    }
}