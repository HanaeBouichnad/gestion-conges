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
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Mes soldes');
        $this->assertSelectorNotExists('.lp-dashboard-header');
        $this->assertSelectorNotExists('.lp-info-banner');
        $this->assertSelectorNotExists('.lp-dashboard-cta');
        $this->assertSelectorNotExists('.lp-dash-card-wide');
        $this->assertSelectorTextContains('.lp-page-header h1', 'Répartition de mon solde');
        $this->assertSelectorExists('.lp-year-banner');
        $this->assertSelectorExists('.lp-type-card');
        $this->assertSelectorExists('.lp-type-card-icon');
        $this->assertSelectorExists('.lp-solde-bar');
        $this->assertSelectorExists('.lp-type-card-btn');
        $this->assertSelectorExists('.lp-donut');
        $this->assertSelectorNotExists('table.table');
    }
}
