<?php

namespace App\Tests\Controller;

use App\Repository\SoldeCongeRepository;
use App\Repository\TypeCongeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminUserControllerTest extends WebTestCase
{
    public function testCreatingAnEmployeInitializesHisSoldesLikeTheFixturesDo(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $userRepository = $container->get(UserRepository::class);
        $soldeCongeRepository = $container->get(SoldeCongeRepository::class);
        $typeCongeRepository = $container->get(TypeCongeRepository::class);
        $email = 'exemple.test@example.com';

        try {
            $admin = $userRepository->findOneBy(['email' => 'admin@example.com']);
            $client->loginUser($admin);

            $crawler = $client->request('GET', '/admin/utilisateurs/nouveau');
            $form = $crawler->selectButton('Enregistrer')->form([
                'user[prenom]' => 'Exemple',
                'user[nom]' => 'Test',
                'user[email]' => $email,
                'user[password]' => 'MotDePasse123',
                'user[rolePrincipal]' => 'ROLE_EMPLOYE',
            ]);
            $client->submit($form);

            $this->assertResponseRedirects('/admin/utilisateurs');

            $nouvelUtilisateur = $userRepository->findOneBy(['email' => $email]);
            $this->assertNotNull($nouvelUtilisateur);

            $soldes = $soldeCongeRepository->findParEmploye($nouvelUtilisateur);
            $nombreTypesActifs = count($typeCongeRepository->findBy(['actif' => true]));

            $this->assertGreaterThan(0, $nombreTypesActifs);
            $this->assertCount($nombreTypesActifs, $soldes);

            foreach ($soldes as $solde) {
                $this->assertSame($solde->getTypeConge()->getSoldeMax() ?? 0.0, $solde->getSolde());
            }
        } finally {
            $em->getConnection()->executeStatement(
                'DELETE FROM solde_conge WHERE employe_id = (SELECT id FROM "user" WHERE email = :email)',
                ['email' => $email]
            );
            $em->getConnection()->executeStatement('DELETE FROM "user" WHERE email = :email', ['email' => $email]);
        }
    }

    public function testCreatingAnAdminDoesNotInitializeSoldesLikeTheFixturesDo(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $userRepository = $container->get(UserRepository::class);
        $soldeCongeRepository = $container->get(SoldeCongeRepository::class);
        $email = 'nouvel.admin@example.com';

        try {
            $admin = $userRepository->findOneBy(['email' => 'admin@example.com']);
            $client->loginUser($admin);

            $crawler = $client->request('GET', '/admin/utilisateurs/nouveau');
            $form = $crawler->selectButton('Enregistrer')->form([
                'user[prenom]' => 'Nouvel',
                'user[nom]' => 'Admin',
                'user[email]' => $email,
                'user[password]' => 'MotDePasse123',
                'user[rolePrincipal]' => 'ROLE_ADMIN',
            ]);
            $client->submit($form);

            $this->assertResponseRedirects('/admin/utilisateurs');

            $nouvelUtilisateur = $userRepository->findOneBy(['email' => $email]);
            $this->assertNotNull($nouvelUtilisateur);
            $this->assertCount(0, $soldeCongeRepository->findParEmploye($nouvelUtilisateur));
        } finally {
            $em->getConnection()->executeStatement('DELETE FROM "user" WHERE email = :email', ['email' => $email]);
        }
    }
}
