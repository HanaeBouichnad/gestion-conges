<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeUnificationTest extends WebTestCase
{
    public function testHomeAnonymousShowsMarketingHero(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-hero');
        $this->assertSelectorExists('.lp-navbar .lp-btn-primary');
        $this->assertSelectorNotExists('.lp-dashboard');
    }

    public function testHomeConnectedEmployeRedirectsToMesSoldes(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/');
        $this->assertResponseRedirects('/mon-solde');

        $client->followRedirect();

        $this->assertSelectorTextNotContains('.lp-nav-links', 'À propos');
        $this->assertSelectorTextNotContains('.lp-nav-links', 'Fonctionnalités');
        $this->assertSelectorTextNotContains('.lp-nav-links', 'Tableau de bord');
        $this->assertSelectorExists('.lp-nav-link-active');
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Mes soldes');
        $this->assertSelectorNotExists('.lp-info-banner');
        $this->assertSelectorNotExists('.lp-dashboard-header');
        $this->assertSelectorTextContains('.lp-user-menu-toggle', $user->getNomComplet());
        $this->assertSelectorNotExists('.lp-dash-card-wide');
    }

    public function testHomeConnectedResponsableShowsApprovalCard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'responsable@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/');
        $this->assertResponseRedirects('/mon-solde');

        $client->followRedirect();

        $this->assertSelectorExists('.lp-dash-card-wide');
        $this->assertSelectorTextContains('.lp-dash-card-wide h3', 'À approuver');
    }

    public function testHomeConnectedRhShowsApprovalAndCancellationCards(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'rh@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/');
        $this->assertResponseRedirects('/mon-solde');

        $client->followRedirect();

        $cards = $client->getCrawler()->filter('.lp-dash-card-wide');
        $this->assertCount(2, $cards);
        $this->assertStringContainsString('À approuver', $cards->eq(0)->text());
        $this->assertStringContainsString('À annuler', $cards->eq(1)->text());
        $this->assertSelectorExists('a[href="' . $client->getContainer()->get('router')->generate('rh_annulation_index') . '"].lp-dash-card-wide');
    }

    public function testHomeConnectedAdminShowsAdminDashboard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin@example.com']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.lp-dashboard-header h1', 'Bonjour Admin,');
        $this->assertSelectorExists('.lp-dashboard-date-pill');
        $statCards = $crawler->filter('.lp-stat-card');
        $this->assertCount(4, $statCards);
        $this->assertStringContainsString('Demandes en attente', $statCards->eq(0)->text());
        $this->assertStringContainsString('Employés actifs', $statCards->eq(3)->text());
        $this->assertSelectorTextContains('.lp-quicklink', 'Types de congés');
        $this->assertSelectorExists('.lp-cal-day-today');
    }

    public function testLoginPageStillRendersSharedNavbar(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-navbar');
        $this->assertSelectorExists('.lp-navbar .lp-btn-primary');
    }
}
