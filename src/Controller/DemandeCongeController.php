<?php

namespace App\Controller;

use App\Entity\DemandeConge;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Form\DemandeCongeType;
use App\Repository\DemandeCongeRepository;
use App\Repository\UserRepository;
use App\Security\Voter\DemandeCongeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/demandes')]
#[IsGranted('ROLE_EMPLOYE')]
class DemandeCongeController extends AbstractController
{
    #[Route('', name: 'demande_conge_index', methods: ['GET'])]
    public function index(DemandeCongeRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('demande_conge/index.html.twig', [
            'demandes' => $repository->findMesDemandes($user),
        ]);
    }

    #[Route('/nouvelle', name: 'demande_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $demande = new DemandeConge();
        $demande->setEmploye($user);

        $employesPossibles = $this->getEmployesPossibles($user, $userRepository);

        $form = $this->createForm(DemandeCongeType::class, $demande, [
            'employes_possibles' => $employesPossibles,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($demande->getDateFin() < $demande->getDateDebut()) {
                $this->addFlash('error', 'La date de fin doit être postérieure ou égale à la date de début.');
                return $this->render('demande_conge/new.html.twig', ['form' => $form]);
            }

            $approbateur = $demande->getEmploye()->getResponsable();
            if (!$approbateur) {
                $this->addFlash('error', "Impossible de créer la demande : aucun responsable hiérarchique n'est déclaré pour cet employé.");
                return $this->render('demande_conge/new.html.twig', ['form' => $form]);
            }
            $demande->setApprobateur($approbateur);
            $demande->setStatut(StatutDemande::BROUILLON);

            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', 'La demande de congé a été créée en brouillon.');
            return $this->redirectToRoute('demande_conge_index');
        }

        return $this->render('demande_conge/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'demande_conge_show', methods: ['GET'])]
    public function show(DemandeConge $demande): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::VIEW, $demande);

        return $this->render('demande_conge/show.html.twig', ['demande' => $demande]);
    }

    #[Route('/{id}/modifier', name: 'demande_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DemandeConge $demande, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::EDIT, $demande);

        /** @var User $user */
        $user = $this->getUser();
        $employesPossibles = $this->getEmployesPossibles($user, $userRepository);

        $form = $this->createForm(DemandeCongeType::class, $demande, [
            'employes_possibles' => $employesPossibles,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($demande->getDateFin() < $demande->getDateDebut()) {
                $this->addFlash('error', 'La date de fin doit être postérieure ou égale à la date de début.');
                return $this->render('demande_conge/edit.html.twig', ['form' => $form, 'demande' => $demande]);
            }

            $demande->setDateModification(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'La demande de congé a été mise à jour.');
            return $this->redirectToRoute('demande_conge_index');
        }

        return $this->render('demande_conge/edit.html.twig', ['form' => $form, 'demande' => $demande]);
    }

    #[Route('/{id}/supprimer', name: 'demande_conge_delete', methods: ['POST'])]
    public function delete(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::DELETE, $demande);

        if ($this->isCsrfTokenValid('supprimer'.$demande->getId(), $request->request->get('_token'))) {
            $em->remove($demande);
            $em->flush();
            $this->addFlash('success', 'La demande de congé a été supprimée.');
        }

        return $this->redirectToRoute('demande_conge_index');
    }

    /** @return User[] */
    private function getEmployesPossibles(User $user, UserRepository $userRepository): array
    {
        if (in_array('ROLE_RESPONSABLE', $user->getRoles(), true)) {
            $subordonnes = $userRepository->findBy(['responsable' => $user]);
            return array_merge([$user], $subordonnes);
        }

        return [$user];
    }
}