<?php

namespace App\Controller;

use App\Entity\JourFerie;
use App\Form\JourFerieType;
use App\Repository\JourFerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/jours-feries')]
#[IsGranted('ROLE_ADMIN')]
class JourFerieController extends AbstractController
{
    #[Route('', name: 'admin_jour_ferie_index', methods: ['GET'])]
    public function index(JourFerieRepository $repository): Response
    {
        return $this->render('jour_ferie/index.html.twig', [
            'joursFeries' => $repository->findBy([], ['date' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_jour_ferie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $jourFerie = new JourFerie();
        $form = $this->createForm(JourFerieType::class, $jourFerie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($jourFerie);
            $em->flush();
            $this->addFlash('success', 'Le jour férié a été ajouté.');
            return $this->redirectToRoute('admin_jour_ferie_index');
        }

        return $this->render('jour_ferie/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/modifier', name: 'admin_jour_ferie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JourFerie $jourFerie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(JourFerieType::class, $jourFerie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le jour férié a été modifié.');
            return $this->redirectToRoute('admin_jour_ferie_index');
        }

        return $this->render('jour_ferie/edit.html.twig', ['form' => $form, 'jourFerie' => $jourFerie]);
    }

    #[Route('/{id}/supprimer', name: 'admin_jour_ferie_delete', methods: ['POST'])]
    public function delete(Request $request, JourFerie $jourFerie, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('supprimer'.$jourFerie->getId(), $request->request->get('_token'))) {
            $em->remove($jourFerie);
            $em->flush();
            $this->addFlash('success', 'Le jour férié a été supprimé.');
        }

        return $this->redirectToRoute('admin_jour_ferie_index');
    }
}