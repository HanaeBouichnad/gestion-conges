<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $user->setActif(false);
        $user->setRoles(['ROLE_EMPLOYE']);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoleSouhaite($form->get('rolePrincipal')->getData());
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "Votre compte a été créé. Il doit être validé par un administrateur avant que vous puissiez vous connecter.");
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', ['form' => $form]);
    }
}
