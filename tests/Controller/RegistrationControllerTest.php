<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterCreatesInactiveEmployeAccountPendingValidation(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $email = 'nouvel.utilisateur@example.com';

        try {
            $crawler = $client->request('GET', '/inscription');
            $this->assertResponseIsSuccessful();

            $form = $crawler->selectButton("S'inscrire")->form([
                'registration_form[prenom]' => 'Nouvel',
                'registration_form[nom]' => 'Utilisateur',
                'registration_form[email]' => $email,
                'registration_form[rolePrincipal]' => 'ROLE_RESPONSABLE',
                'registration_form[plainPassword][first]' => 'MotDePasse123',
                'registration_form[plainPassword][second]' => 'MotDePasse123',
            ]);
            $client->submit($form);

            $this->assertResponseRedirects('/login');
            $client->followRedirect();
            $this->assertSelectorTextContains('.lp-alert-success', 'validé');

            $userRepository = $container->get(UserRepository::class);
            $user = $userRepository->findOneBy(['email' => $email]);

            $this->assertNotNull($user);
            $this->assertFalse($user->isActif());
            $this->assertContains('ROLE_EMPLOYE', $user->getRoles());
            $this->assertNotContains('ROLE_RESPONSABLE', $user->getRoles());
            $this->assertSame('ROLE_RESPONSABLE', $user->getRoleSouhaite());
        } finally {
            $em->getConnection()->executeStatement('DELETE FROM "user" WHERE email = :email', ['email' => $email]);
        }
    }

    public function testMismatchedPasswordsAreRejected(): void
    {
        $client = static::createClient();
        $email = 'mismatch@example.com';

        $crawler = $client->request('GET', '/inscription');
        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_form[prenom]' => 'Test',
            'registration_form[nom]' => 'Mismatch',
            'registration_form[email]' => $email,
            'registration_form[rolePrincipal]' => 'ROLE_EMPLOYE',
            'registration_form[plainPassword][first]' => 'MotDePasse123',
            'registration_form[plainPassword][second]' => 'AutreMotDePasse',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $this->assertNull($userRepository->findOneBy(['email' => $email]));
    }

    public function testInactiveAccountCannotLogIn(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $email = 'en.attente@example.com';

        try {
            $crawler = $client->request('GET', '/inscription');
            $form = $crawler->selectButton("S'inscrire")->form([
                'registration_form[prenom]' => 'En',
                'registration_form[nom]' => 'Attente',
                'registration_form[email]' => $email,
                'registration_form[rolePrincipal]' => 'ROLE_EMPLOYE',
                'registration_form[plainPassword][first]' => 'MotDePasse123',
                'registration_form[plainPassword][second]' => 'MotDePasse123',
            ]);
            $client->submit($form);

            $loginCrawler = $client->followRedirect();
            $loginForm = $loginCrawler->filter('form[action="/login"]')->form();
            $loginForm['_username'] = $email;
            $loginForm['_password'] = 'MotDePasse123';
            $client->submit($loginForm);

            $this->assertResponseRedirects('/login');
            $client->followRedirect();
            $this->assertSelectorTextContains('.alert-danger', 'attente de validation');
        } finally {
            $em->getConnection()->executeStatement('DELETE FROM "user" WHERE email = :email', ['email' => $email]);
        }
    }

    public function testAdminCanActivateARegisteredAccountAndItCanThenLogIn(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $userRepository = $container->get(UserRepository::class);
        $email = 'a.valider@example.com';

        try {
            $crawler = $client->request('GET', '/inscription');
            $form = $crawler->selectButton("S'inscrire")->form([
                'registration_form[prenom]' => 'A',
                'registration_form[nom]' => 'Valider',
                'registration_form[email]' => $email,
                'registration_form[rolePrincipal]' => 'ROLE_RESPONSABLE',
                'registration_form[plainPassword][first]' => 'MotDePasse123',
                'registration_form[plainPassword][second]' => 'MotDePasse123',
            ]);
            $client->submit($form);

            $nouvelUtilisateur = $userRepository->findOneBy(['email' => $email]);
            $this->assertNotNull($nouvelUtilisateur);

            $admin = $userRepository->findOneBy(['email' => 'admin@example.com']);
            $client->loginUser($admin);

            $editCrawler = $client->request('GET', '/admin/utilisateurs/' . $nouvelUtilisateur->getId() . '/modifier');
            $this->assertSelectorTextContains('.lp-validation-banner strong', 'en attente de validation');
            $this->assertSelectorTextContains('.lp-validation-banner .lp-role-badge-responsable', 'Responsable');

            $editForm = $editCrawler->selectButton('Enregistrer')->form();
            $editForm['user[rolePrincipal]'] = 'ROLE_RESPONSABLE';
            $editForm['user[actif]'] = true;
            $client->submit($editForm);

            $this->assertResponseRedirects('/admin/utilisateurs');

            $em->clear();
            $utilisateurActive = $userRepository->findOneBy(['email' => $email]);
            $this->assertTrue($utilisateurActive->isActif());
            $this->assertContains('ROLE_RESPONSABLE', $utilisateurActive->getRoles());

            // L'activation doit initialiser les soldes de l'utilisateur, comme pour un utilisateur
            // créé directement par un admin (sinon "Mes soldes" reste vide).
            $soldeCongeRepository = $container->get(\App\Repository\SoldeCongeRepository::class);
            $typeCongeRepository = $container->get(\App\Repository\TypeCongeRepository::class);
            $soldesActives = $soldeCongeRepository->findParEmploye($utilisateurActive);
            $nombreTypesActifs = count($typeCongeRepository->findBy(['actif' => true]));
            $this->assertCount($nombreTypesActifs, $soldesActives);
            $this->assertGreaterThan(0, $nombreTypesActifs);

            $client->restart();
            $loginCrawler = $client->request('GET', '/login');
            $loginForm = $loginCrawler->filter('form[action="/login"]')->form();
            $loginForm['_username'] = $email;
            $loginForm['_password'] = 'MotDePasse123';
            $client->submit($loginForm);

            $this->assertResponseRedirects('/');
        } finally {
            $em->getConnection()->executeStatement(
                'DELETE FROM solde_conge WHERE employe_id = (SELECT id FROM "user" WHERE email = :email)',
                ['email' => $email]
            );
            $em->getConnection()->executeStatement('DELETE FROM "user" WHERE email = :email', ['email' => $email]);
        }
    }
}
