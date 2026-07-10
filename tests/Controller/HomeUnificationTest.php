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
        $this->assertSelectorExists('.lp-role-link');
        $this->assertSelectorNotExists('.lp-dashboard');
    }

    public function testHomeConnectedEmployeShowsDashboard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.lp-hero');
        $this->assertSelectorNotExists('.lp-role-link');
        $this->assertSelectorTextNotContains('.lp-nav-links', 'À propos');
        $this->assertSelectorTextNotContains('.lp-nav-links', 'Fonctionnalités');
        $this->assertSelectorExists('.lp-nav-link-active');
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Tableau de bord');
        $this->assertSelectorExists('.lp-dashboard');
        $this->assertSelectorExists('.lp-info-banner');
        $this->assertSelectorExists('.lp-dash-card');
        $this->assertCount(4, $crawler->filter('.lp-dash-card:not(.lp-dash-card-wide)'));
        $this->assertSelectorTextContains('.lp-dashboard-header h1', 'Bonjour ' . $user->getPrenom());
        $this->assertSelectorTextContains('.lp-dashboard-subtitle', (string) (int) date('j'));
        $this->assertSelectorTextContains('.lp-user-menu-toggle', $user->getNomComplet());
        $this->assertSelectorExists('a[href="' . $client->getContainer()->get('router')->generate('demande_conge_index') . '"]');
        $this->assertSelectorExists('a[href="' . $client->getContainer()->get('router')->generate('demande_conge_new') . '"]');
        $this->assertSelectorExists('.lp-dashboard-hero-image img[src$="images/calendrier.png"]');
    }

    public function testHomeConnectedResponsableShowsApprovalCard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'responsable@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-dash-card-wide');
        $this->assertSelectorTextContains('.lp-dash-card-wide h3', 'À approuver');
    }

    public function testHomeConnectedAdminShowsAdminDashboard(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.lp-kpi-label', 'Jours fériés configurés');
    }

    public function testLoginPageStillRendersSharedNavbar(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-navbar');
        $this->assertSelectorExists('.lp-role-link');
    }

    public function testLegacyAccueilRouteStillWorksWhenConnected(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/accueil');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bonjour');
    }
}
