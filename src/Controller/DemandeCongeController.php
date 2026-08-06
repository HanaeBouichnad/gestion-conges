<?php

namespace App\Controller;

use App\Entity\DemandeAnnulation;
use App\Entity\DemandeConge;
use App\Entity\User;
use App\Enum\OrigineAnnulation;
use App\Enum\StatutDemande;
use App\Form\DemandeAnnulationType;
use App\Form\DemandeCongeType;
use App\Repository\DemandeAnnulationRepository;
use App\Repository\DemandeCongeRepository;
use App\Repository\SoldeCongeRepository;
use App\Repository\UserRepository;
use App\Security\Voter\DemandeCongeVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\PieceJointe;
use App\Service\PieceJointeUploader;
use App\Service\SoldeCongeManager;

#[Route('/demandes')]
#[IsGranted('ROLE_EMPLOYE')]
class DemandeCongeController extends AbstractController
{
    #[Route('', name: 'demande_conge_index', methods: ['GET'])]
    public function index(DemandeCongeRepository $repository, SoldeCongeRepository $soldeCongeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $tousSoldesNuls = true;
        foreach ($soldeCongeRepository->findParEmploye($user) as $solde) {
            if ($solde->getSolde() > 0) {
                $tousSoldesNuls = false;
                break;
            }
        }

        return $this->render('shared/demandes_conge/index.html.twig', [
            'demandes' => $repository->findMesDemandes($user),
            'formAnnulation' => $this->createForm(DemandeAnnulationType::class)->createView(),
            'tousSoldesNuls' => $tousSoldesNuls,
        ]);
    }

    #[Route('/nouvelle', name: 'demande_conge_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        PieceJointeUploader $uploader,
        SoldeCongeManager $soldeCongeManager
    ): Response {
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
                return $this->render('shared/demandes_conge/new.html.twig', ['form' => $form], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $approbateur = $demande->getEmploye()->getResponsable();
            if (!$approbateur) {
                $this->addFlash('error', "Impossible de créer la demande : aucun responsable hiérarchique n'est déclaré pour cet employé.");
                return $this->render('shared/demandes_conge/new.html.twig', ['form' => $form], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $demande->setApprobateur($approbateur);
            $demande->setStatut(StatutDemande::BROUILLON);

            $fichier = $form->get('pieceJointeFichier')->getData();
            if (!$fichier && $demande->getTypeConge()->isPieceJointeObligatoire()) {
                $this->addFlash('error', sprintf(
                    'Une pièce jointe est obligatoire pour le type de congé "%s".',
                    $demande->getTypeConge()->getLibelle()
                ));
                return $this->render('shared/demandes_conge/new.html.twig', ['form' => $form], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $erreurSolde = $soldeCongeManager->verifierDisponibilite($demande);
            if ($erreurSolde !== null) {
                $this->addFlash('error_solde', $erreurSolde);
                return $this->render('shared/demandes_conge/new.html.twig', ['form' => $form], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $em->persist($demande);
            $em->flush();

            if ($fichier) {
                $this->ajouterPieceJointe($demande, $fichier, $uploader, $em);
            }

            $this->addFlash('success', 'La demande de congé a été créée en brouillon.');
            return $this->redirectToRoute('demande_conge_index');
        }

        $statut = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        return $this->render('shared/demandes_conge/new.html.twig', ['form' => $form], new Response(null, $statut));
    }

    #[Route('/{id}', name: 'demande_conge_show', methods: ['GET'])]
    public function show(DemandeConge $demande, DemandeAnnulationRepository $demandeAnnulationRepository): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::VIEW, $demande);

        return $this->render('shared/demandes_conge/show.html.twig', [
            'demande' => $demande,
            'demandeAnnulation' => $demandeAnnulationRepository->findDernierePourDemande($demande),
            'formAnnulation' => $this->createForm(DemandeAnnulationType::class)->createView(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'demande_conge_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        DemandeConge $demande,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        SoldeCongeManager $soldeCongeManager
    ): Response {
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
                return $this->render('shared/demandes_conge/edit.html.twig', ['form' => $form, 'demande' => $demande], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $erreurSolde = $soldeCongeManager->verifierDisponibilite($demande);
            if ($erreurSolde !== null) {
                $this->addFlash('error', $erreurSolde);
                return $this->render('shared/demandes_conge/edit.html.twig', ['form' => $form, 'demande' => $demande], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $demande->setDateModification(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'La demande de congé a été mise à jour.');
            return $this->redirectToRoute('demande_conge_index');
        }

        $statut = $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;
        return $this->render('shared/demandes_conge/edit.html.twig', ['form' => $form, 'demande' => $demande], new Response(null, $statut));
    }

    #[Route('/{id}/envoyer', name: 'demande_conge_envoyer', methods: ['POST'])]
    public function envoyer(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::ENVOYER, $demande);

        if (!$this->isCsrfTokenValid('envoyer'.$demande->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('demande_conge_index');
        }

        $demande->setStatut(StatutDemande::EN_ATTENTE);
        $demande->setDateModification(new \DateTimeImmutable());
        $em->flush();

        // RG-21 : notification de l'approbateur (mail simple, cf etape mailer si activee plus tard)

        $this->addFlash('success', 'La demande a été envoyée et est en attente d\'approbation.');
        return $this->redirectToRoute('demande_conge_index');
    }

    #[Route('/{id}/annuler', name: 'demande_conge_annuler', methods: ['POST'])]
    public function annuler(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::DELETE, $demande);

        if ($this->isCsrfTokenValid('annuler'.$demande->getId(), $request->request->get('_token'))) {
            $demande->setStatut(StatutDemande::ANNULE);
            $demande->setOrigineAnnulation(OrigineAnnulation::EMPLOYE);
            $demande->setDateModification(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'La demande de congé a été annulée.');
        }

        return $this->redirectToRoute('demande_conge_index');
    }

    #[Route('/{id}/demander-annulation', name: 'demande_conge_demander_annulation', methods: ['POST'])]
    public function demanderAnnulation(Request $request, DemandeConge $demande, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted(DemandeCongeVoter::DEMANDER_ANNULATION, $demande);

        /** @var User $user */
        $user = $this->getUser();

        $demandeAnnulation = new DemandeAnnulation();
        $demandeAnnulation->setDemandeConge($demande);
        $demandeAnnulation->setDemandeur($user);

        $form = $this->createForm(DemandeAnnulationType::class, $demandeAnnulation);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Le motif de la demande d\'annulation est obligatoire.');
            return $this->redirectToRoute('demande_conge_show', ['id' => $demande->getId()]);
        }

        $em->persist($demandeAnnulation);
        $em->flush();

        $this->addFlash('success', 'Votre demande d\'annulation a été envoyée aux RH.');
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
    private function ajouterPieceJointe(DemandeConge $demande, $fichier, PieceJointeUploader $uploader, EntityManagerInterface $em): void
    {
        $nomFichier = $uploader->upload($fichier);

        $pieceJointe = new PieceJointe();
        $pieceJointe->setDemandeConge($demande);
        $pieceJointe->setNomFichier($fichier->getClientOriginalName());
        $pieceJointe->setCheminFichier($nomFichier);

        $em->persist($pieceJointe);
        $em->flush();
    }
}