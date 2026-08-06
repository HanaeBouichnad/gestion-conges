<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeCongeIndexTest extends WebTestCase
{
    public function testIndexUsesAppLayoutAndTableStyle(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/demandes');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-navbar');
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Mes demandes');
        $this->assertSelectorExists('.lp-page-header');
        $this->assertSelectorNotExists('table.table');
        $this->assertSelectorNotExists('.lp-demande-card');

        $this->assertSelectorExists('.lp-table-card table.lp-table');
        $headers = $crawler->filter('.lp-table thead th')->each(fn ($node) => trim($node->text()));
        $this->assertSame(['Type de congé', 'Période', 'Statut', 'Actions'], $headers);
        $this->assertSelectorExists('.lp-table tbody tr .lp-table-actions .lp-icon-btn[title="Voir"]');
    }
}
