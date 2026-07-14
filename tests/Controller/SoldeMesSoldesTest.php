<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SoldeMesSoldesTest extends WebTestCase
{
    public function testIndexUsesAppLayoutAndDashboardStyleWidgets(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/mon-solde');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-navbar');
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Solde');
        $this->assertSelectorTextContains('.lp-page-header h1', 'Mes soldes de congé');
        $this->assertSelectorNotExists('.lp-solde-bar');
        $this->assertSelectorNotExists('.lp-solde-legend');
        $this->assertSelectorExists('.lp-type-card');
        $this->assertSelectorNotExists('table.table');
    }
}
