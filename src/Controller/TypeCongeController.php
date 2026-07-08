<?php

namespace App\Controller;

use App\Entity\TypeConge;
use App\Form\TypeCongeType;
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
        return $this->render('type_conge/index.html.twig', [
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

        return $this->render('type_conge/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/modifier', name: 'admin_type_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeConge $typeConge, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TypeCongeType::class, $typeConge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le type de congé a été modifié.');
            return $this->redirectToRoute('admin_type_conge_index');
        }

        return $this->render('type_conge/edit.html.twig', ['form' => $form, 'typeConge' => $typeConge]);
    }
}