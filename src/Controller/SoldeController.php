<?php

namespace App\Controller;

use App\Entity\JournalSolde;
use App\Entity\SoldeConge;
use App\Entity\TypeConge;
use App\Entity\User;
use App\Form\ModifierSoldeType;
use App\Repository\SoldeCongeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SoldeController extends AbstractController
{
    #[Route('/mon-solde', name: 'solde_mes_soldes', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function mesSoldes(SoldeCongeRepository $repository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('solde/mes_soldes.html.twig', [
            'soldes' => $repository->findParEmploye($user),
        ]);
    }

    #[Route('/rh/soldes', name: 'rh_soldes_index', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('solde/rh_index.html.twig', [
            'employes' => $userRepository->findAll(),
        ]);
    }

    #[Route('/rh/soldes/{id}', name: 'rh_solde_employe', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function soldeEmploye(User $id, SoldeCongeRepository $repository): Response
    {
        return $this->render('solde/rh_employe.html.twig', [
            'employe' => $id,
            'soldes' => $repository->findParEmploye($id),
        ]);
    }

    #[Route('/rh/soldes/{employeId}/{typeId}/modifier', name: 'rh_solde_modifier', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_RH')]
    public function modifier(
        Request $request,
        int $employeId,
        int $typeId,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        SoldeCongeRepository $soldeRepository
    ): Response {
        $employe = $userRepository->find($employeId);
        $type = $em->getRepository(TypeConge::class)->find($typeId);

        if (!$employe || !$type) {
            throw $this->createNotFoundException();
        }

        $solde = $soldeRepository->findOneParEmployeEtType($employe, $type);
        if (!$solde) {
            $solde = new SoldeConge();
            $solde->setEmploye($employe);
            $solde->setTypeConge($type);
            $solde->setSolde(0.0);
        }

        $form = $this->createForm(ModifierSoldeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $auteur */
            $auteur = $this->getUser();
            $nombreJours = (float) $form->get('nombreJours')->getData();
            $note = $form->get('note')->getData();

            $solde->setSolde($solde->getSolde() + $nombreJours);
            $em->persist($solde);

            $journal = new JournalSolde();
            $journal->setEmploye($employe);
            $journal->setTypeConge($type);
            $journal->setAuteur($auteur);
            $journal->setNombreJours($nombreJours);
            $journal->setNote($note);
            $em->persist($journal);

            $em->flush();

            $this->addFlash('success', 'Le solde a été mis à jour.');
            return $this->redirectToRoute('rh_solde_employe', ['id' => $employe->getId()]);
        }

        return $this->render('solde/modifier.html.twig', [
            'form' => $form,
            'employe' => $employe,
            'type' => $type,
            'solde' => $solde,
        ]);
    }
}