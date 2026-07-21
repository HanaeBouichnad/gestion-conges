<?php

namespace App\Controller;

use App\Entity\JournalSolde;
use App\Entity\TypeConge;
use App\Entity\User;
use App\Form\TypeCongeType;
use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/types-conge')]
#[IsGranted('ROLE_ADMIN')]
class TypeCongeController extends AbstractController
{
    #[Route('', name: 'admin_type_conge_index', methods: ['GET'])]
    public function index(TypeCongeRepository $repository): Response
    {
        return $this->render('admin/types_conge/index.html.twig', [
            'types' => $repository->findBy([], ['libelle' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_type_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $type = new TypeConge();
        $form = $this->createForm(TypeCongeType::class, $type);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($type);
            $em->flush();
            $this->addFlash('success', 'Le type de congé a été créé.');
            return $this->redirectToRoute('admin_type_conge_index');
        }

        return $this->render('admin/types_conge/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/modifier', name: 'admin_type_conge_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        TypeConge $typeConge,
        EntityManagerInterface $em,
        SoldeCongeRepository $soldeCongeRepository
    ): Response {
        $ancienSoldeMax = $typeConge->getSoldeMax();

        $form = $this->createForm(TypeCongeType::class, $typeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauSoldeMax = $typeConge->getSoldeMax();

            // Remplace le solde individuel de chaque utilisateur ayant ce type par le nouveau plafond.
            // - Si le nouveau plafond est "illimité" (null), aucun remplacement n'a de sens : on ne touche à rien.
            // - Si l'ancien plafond était "illimité" (null) et le nouveau est défini, on applique quand même
            //   le remplacement (le passage d'illimité à un plafond chiffré doit se répercuter sur les soldes individuels).
            $doitRemplacer = $nouveauSoldeMax !== null
                && ($ancienSoldeMax === null || abs($nouveauSoldeMax - $ancienSoldeMax) > 0.001);

            if ($doitRemplacer) {
                /** @var User $auteur */
                $auteur = $this->getUser();

                foreach ($soldeCongeRepository->findBy(['typeConge' => $typeConge]) as $solde) {
                    $ancienSoldeIndividuel = $solde->getSolde();
                    $solde->setSolde($nouveauSoldeMax);

                    $note = $ancienSoldeMax === null
                        ? sprintf(
                            'Remplacement du solde suite au passage du type "%s" d\'illimité à un plafond de %s j. par l\'administrateur. Solde individuel remplacé : %s j. → %s j.',
                            $typeConge->getLibelle(),
                            $nouveauSoldeMax,
                            $ancienSoldeIndividuel,
                            $nouveauSoldeMax
                        )
                        : sprintf(
                            'Remplacement du solde suite à la modification du plafond du type "%s" par l\'administrateur (%s j. → %s j.). Solde individuel remplacé : %s j. → %s j.',
                            $typeConge->getLibelle(),
                            $ancienSoldeMax,
                            $nouveauSoldeMax,
                            $ancienSoldeIndividuel,
                            $nouveauSoldeMax
                        );

                    $journal = new JournalSolde();
                    $journal->setEmploye($solde->getEmploye());
                    $journal->setTypeConge($typeConge);
                    $journal->setAuteur($auteur);
                    $journal->setNombreJours($nouveauSoldeMax - $ancienSoldeIndividuel);
                    $journal->setNote($note);
                    $em->persist($journal);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Le type de congé a été modifié.');
            return $this->redirectToRoute('admin_type_conge_index');
        }

        return $this->render('admin/types_conge/edit.html.twig', ['form' => $form, 'typeConge' => $typeConge]);
    }
}