<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeCongeIndexTest extends WebTestCase
{
    public function testIndexUsesAppLayoutAndCardStyle(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/demandes');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-navbar');
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Mes demandes');
        $this->assertSelectorExists('.lp-page-header');
        $this->assertSelectorNotExists('table.table');
    }
}
