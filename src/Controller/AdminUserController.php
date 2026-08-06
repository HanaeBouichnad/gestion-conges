<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\SoldeCongeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $repository): Response
    {
        $recherche = trim((string) $request->query->get('q', ''));

        $tousLesUtilisateurs = $repository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);

        $utilisateursFiltres = $recherche === ''
            ? $tousLesUtilisateurs
            : array_values(array_filter($tousLesUtilisateurs, function (User $u) use ($recherche) {
                $aiguille = mb_strtolower($recherche);
                return str_contains(mb_strtolower($u->getNomComplet()), $aiguille)
                    || str_contains(mb_strtolower($u->getEmail()), $aiguille);
            }));

        return $this->render('admin/utilisateurs/index.html.twig', [
            'utilisateurs' => $utilisateursFiltres,
            'recherche' => $recherche,
        ]);
    }

    #[Route('/nouveau', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SoldeCongeManager $soldeCongeManager
    ): Response {
        $user = new User();
        $user->setActif(true);

        $form = $this->createForm(UserType::class, $user, ['is_creation' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('rolePrincipal')->getData();
            $user->setRoles([$role]);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));

            $em->persist($user);

            // RG-29/31 : un utilisateur doit disposer de ses soldes dès sa création, comme le font
            // les fixtures. Les comptes admin ne sont pas des employés soumis aux congés (cf. fixtures).
            if ($role !== 'ROLE_ADMIN') {
                $soldeCongeManager->initialiserSoldes($user);
            }

            $em->flush();

            $this->addFlash('success', "L'utilisateur a été créé.");
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/utilisateurs/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/modifier', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $em, SoldeCongeManager $soldeCongeManager): Response
    {
        $etaitActif = $user->isActif();

        $form = $this->createForm(UserType::class, $user, ['is_creation' => false]);
        $form->get('rolePrincipal')->setData($this->rolePrincipal($user));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $form->get('rolePrincipal')->getData();
            $user->setRoles([$role]);

            // Un compte activé (ex. validation d'une inscription libre-service) doit repartir
            // avec ses soldes initialisés, comme n'importe quel utilisateur créé par un admin.
            if (!$etaitActif && $user->isActif() && $role !== 'ROLE_ADMIN') {
                $soldeCongeManager->initialiserSoldes($user);
            }

            $em->flush();

            $this->addFlash('success', "L'utilisateur a été modifié.");
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/utilisateurs/edit.html.twig', ['form' => $form, 'utilisateur' => $user]);
    }

    private function rolePrincipal(User $user): string
    {
        $roles = $user->getRoles();

        return match (true) {
            in_array('ROLE_ADMIN', $roles, true) => 'ROLE_ADMIN',
            in_array('ROLE_RH', $roles, true) => 'ROLE_RH',
            in_array('ROLE_RESPONSABLE', $roles, true) => 'ROLE_RESPONSABLE',
            default => 'ROLE_EMPLOYE',
        };
    }
}
