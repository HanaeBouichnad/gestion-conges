<?php

namespace App\Tests\Controller;

use App\Entity\DemandeConge;
use App\Entity\PieceJointe;
use App\Entity\TypeConge;
use App\Enum\StatutDemande;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeCongeShowTest extends WebTestCase
{
    public function testShowUsesAppLayoutWithBadgeAndStyledSections(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $employe = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $approbateur = $userRepository->findOneBy(['email' => 'responsable@example.com']);
        $type = $em->getRepository(TypeConge::class)->findOneBy(['libelle' => 'Congé annuel']);

        $demande = new DemandeConge();
        $demande->setEmploye($employe);
        $demande->setTypeConge($type);
        $demande->setApprobateur($approbateur);
        $demande->setDateDebut(new \DateTimeImmutable('2026-10-05'));
        $demande->setDateFin(new \DateTimeImmutable('2026-10-06'));
        $demande->setStatut(StatutDemande::BROUILLON);
        $em->persist($demande);

        $pieceJointe = new PieceJointe();
        $pieceJointe->setNomFichier('justificatif.pdf');
        $pieceJointe->setCheminFichier('justificatif.pdf');
        $demande->addPieceJointe($pieceJointe);
        $em->persist($pieceJointe);

        $em->flush();
        $demandeId = $demande->getId();

        try {
            $client->loginUser($employe);
            $crawler = $client->request('GET', '/demandes/' . $demandeId);

            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('.lp-navbar');
            $this->assertSelectorTextContains('.lp-page-header-title h1', 'Demande de congé');
            $this->assertSelectorTextContains('.lp-page-header-title .status-badge', 'Brouillon');
            $this->assertSelectorExists('.lp-info-grid');
            $this->assertSelectorTextContains('.lp-info-grid', 'Congé annuel');
            $this->assertSelectorExists('.lp-attachment-item');
            $this->assertSelectorTextContains('.lp-attachment-name', 'justificatif.pdf');
            $this->assertSelectorExists('a.lp-see-more');
            // BROUILLON is editable/sendable but not yet sent, so the "valider" button must show.
            $this->assertSelectorExists('button.lp-btn-success');
            $this->assertSelectorNotExists('dl.row');
        } finally {
            $em->getConnection()->executeStatement('DELETE FROM piece_jointe WHERE demande_conge_id = ?', [$demandeId]);
            $em->getConnection()->executeStatement('DELETE FROM demande_conge WHERE id = ?', [$demandeId]);
        }
    }

    public function testShowWithoutAttachmentsShowsEmptyState(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $employe = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $approbateur = $userRepository->findOneBy(['email' => 'responsable@example.com']);
        $type = $em->getRepository(TypeConge::class)->findOneBy(['libelle' => 'Congé annuel']);

        $demande = new DemandeConge();
        $demande->setEmploye($employe);
        $demande->setTypeConge($type);
        $demande->setApprobateur($approbateur);
        $demande->setDateDebut(new \DateTimeImmutable('2026-10-10'));
        $demande->setDateFin(new \DateTimeImmutable('2026-10-11'));
        $demande->setStatut(StatutDemande::APPROUVE);
        $em->persist($demande);
        $em->flush();
        $demandeId = $demande->getId();

        try {
            $client->loginUser($employe);
            $crawler = $client->request('GET', '/demandes/' . $demandeId);

            $this->assertResponseIsSuccessful();
            $this->assertSelectorTextContains('.lp-empty-text', 'Aucune pièce jointe');
            $this->assertSelectorTextContains('.lp-page-header-title .status-badge', 'Approuvé');
            // APPROUVE can no longer be sent for approval.
            $this->assertSelectorNotExists('button.lp-btn-success');
        } finally {
            $em->getConnection()->executeStatement('DELETE FROM demande_conge WHERE id = ?', [$demandeId]);
        }
    }
}
