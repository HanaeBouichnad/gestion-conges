<?php

namespace App\Tests\Controller;

use App\Entity\DemandeConge;
use App\Entity\TypeConge;
use App\Enum\StatutDemande;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeCongeEditTest extends WebTestCase
{
    public function testFormUsesSameStyleAsNewPage(): void
    {
        $client = static::createClient();
        $demandeId = $this->creerBrouillon();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        try {
            $crawler = $client->request('GET', '/demandes/' . $demandeId . '/modifier');

            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('.lp-page-header h1', 'Modifier la demande de congé');
            $this->assertSelectorExists('.lp-form-card');
            $this->assertSelectorExists('.lp-field-row');
            $this->assertSelectorExists('.lp-file-field');
            $this->assertSelectorTextContains('button.lp-btn-primary', 'Enregistrer');
            $this->assertSelectorNotExists('.container');
        } finally {
            $this->supprimerDemande($demandeId);
        }
    }

    public function testFormSubmissionStillUpdatesDemande(): void
    {
        $client = static::createClient();
        $demandeId = $this->creerBrouillon();

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        try {
            $crawler = $client->request('GET', '/demandes/' . $demandeId . '/modifier');
            $form = $crawler->selectButton('Enregistrer')->form();
            $form['demande_conge[dateDebut]'] = '2026-11-03';
            $form['demande_conge[dateFin]'] = '2026-11-04';

            $client->submit($form);

            $this->assertResponseRedirects('/demandes');

            /** @var EntityManagerInterface $em */
            $em = static::getContainer()->get(EntityManagerInterface::class);
            $em->clear();
            $updated = $em->getRepository(DemandeConge::class)->find($demandeId);
            $this->assertSame('2026-11-03', $updated->getDateDebut()->format('Y-m-d'));
        } finally {
            $this->supprimerDemande($demandeId);
        }
    }

    private function creerBrouillon(): int
    {
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $employe = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $approbateur = $userRepository->findOneBy(['email' => 'responsable@example.com']);
        $type = $em->getRepository(TypeConge::class)->findOneBy(['libelle' => 'Congé annuel']);

        // La modification passe par verifierDisponibilite(), qui compare désormais toujours
        // au solde réel de l'employé : il en faut donc un suffisant pour ce type ici.
        $em->getConnection()->executeStatement(
            'UPDATE solde_conge SET solde = 10 WHERE employe_id = :employeId AND type_conge_id = :typeId',
            ['employeId' => $employe->getId(), 'typeId' => $type->getId()]
        );

        $demande = new DemandeConge();
        $demande->setEmploye($employe);
        $demande->setTypeConge($type);
        $demande->setApprobateur($approbateur);
        $demande->setDateDebut(new \DateTimeImmutable('2026-11-01'));
        $demande->setDateFin(new \DateTimeImmutable('2026-11-02'));
        $demande->setStatut(StatutDemande::BROUILLON);
        $em->persist($demande);
        $em->flush();

        return $demande->getId();
    }

    private function supprimerDemande(int $id): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->executeStatement('DELETE FROM demande_conge WHERE id = ?', [$id]);

        $userRepository = static::getContainer()->get(UserRepository::class);
        $employe = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $type = $em->getRepository(TypeConge::class)->findOneBy(['libelle' => 'Congé annuel']);
        $em->getConnection()->executeStatement(
            'UPDATE solde_conge SET solde = 0 WHERE employe_id = :employeId AND type_conge_id = :typeId',
            ['employeId' => $employe->getId(), 'typeId' => $type->getId()]
        );
    }
}
