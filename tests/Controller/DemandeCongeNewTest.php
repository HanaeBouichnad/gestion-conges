<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeCongeNewTest extends WebTestCase
{
    public function testFormUsesAppLayoutAndStyledFields(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/demandes/nouvelle');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-navbar');
        $this->assertSelectorTextContains('.lp-page-header h1', 'Nouvelle demande de congé');
        $this->assertSelectorExists('.lp-form-card');
        $this->assertSelectorExists('.lp-field-row');
        $this->assertSelectorExists('.lp-file-field');
        $this->assertSelectorExists('form[enctype="multipart/form-data"]');
        $this->assertSelectorNotExists('.container');
    }

    public function testFormSubmissionStillCreatesDraftDemande(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        // Making two requests with the KernelBrowser resets stateful services
        // (including the DB connection) in between, so a wrapping transaction
        // can't survive across them here — clean up explicitly instead.
        try {
            $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
            $client->loginUser($user);

            $crawler = $client->request('GET', '/demandes/nouvelle');
            $form = $crawler->selectButton('Créer en brouillon')->form();

            $typeId = $crawler->filter('select[name="demande_conge[typeConge]"] option')->first()->attr('value');
            $form['demande_conge[typeConge]']->select($typeId);
            $form['demande_conge[dateDebut]'] = '2026-09-01';
            $form['demande_conge[dateFin]'] = '2026-09-02';

            // La création passe par verifierDisponibilite(), qui compare désormais toujours
            // au solde réel de l'employé : il en faut donc un suffisant pour le type choisi ici.
            $em->getConnection()->executeStatement(
                'UPDATE solde_conge SET solde = 10 WHERE employe_id = :employeId AND type_conge_id = :typeId',
                ['employeId' => $user->getId(), 'typeId' => $typeId]
            );

            $client->submit($form);

            $this->assertResponseRedirects('/demandes');
        } finally {
            $em->getConnection()->executeStatement(
                "DELETE FROM demande_conge WHERE date_debut = '2026-09-01' AND date_fin = '2026-09-02'"
            );
            $em->getConnection()->executeStatement(
                'UPDATE solde_conge SET solde = 0 WHERE employe_id = :employeId AND type_conge_id = :typeId',
                ['employeId' => $user->getId(), 'typeId' => $typeId ?? null]
            );
        }
    }
}
