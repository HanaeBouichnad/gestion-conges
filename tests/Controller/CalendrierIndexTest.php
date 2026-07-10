<?php

namespace App\Tests\Controller;

use App\Entity\DemandeConge;
use App\Entity\TypeConge;
use App\Enum\StatutDemande;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalendrierIndexTest extends WebTestCase
{
    public function testIndexRendersVisualGrid(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/calendrier');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.lp-nav-link-active', 'Calendrier');
        $this->assertSelectorTextContains('.lp-page-header h1', 'Calendrier');
        $this->assertSelectorTextContains('.lp-page-subtitle', 'Consultez vos congés');
        $this->assertSelectorExists('.lp-cal-week');
        $this->assertSelectorExists('.lp-cal-legend');
        $this->assertCount(5, $crawler->filter('.lp-legend-item'));
        $this->assertSelectorTextContains('.lp-legend-item:last-child', 'Jour férié');
        $this->assertSelectorNotExists('ul.list-group');
        $this->assertSelectorNotExists('.lp-calendar-legend');

        // Grid must be made of full weeks of 7 days (28, 35 or 42 cells).
        $cellCount = $crawler->filter('.lp-cal-day')->count();
        $this->assertContains($cellCount, [28, 35, 42]);
        $this->assertSame(0, $cellCount % 7);

        // Exactly one day should carry the "today" marker.
        $this->assertCount(1, $crawler->filter('.lp-cal-day-today'));
    }

    public function testMonthNavigationKeepsGridStructure(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        $client->request('GET', '/calendrier?mois=2&annee=2026');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.lp-month-label', 'février 2026');
    }

    public function testSixWeekMonthScrollsInsideWeeksContainer(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'employe@example.com']);
        $client->loginUser($user);

        // August 2026 spans 6 calendar weeks (42 cells).
        $crawler = $client->request('GET', '/calendrier?mois=8&annee=2026');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.lp-cal-weeks');
        $this->assertCount(6, $crawler->filter('.lp-cal-weeks > .lp-cal-week'));
        $this->assertCount(42, $crawler->filter('.lp-cal-day'));

        // Weekday header and legend must live outside the scrollable weeks wrapper,
        // so they stay visible while only the weeks scroll internally.
        $this->assertCount(0, $crawler->filter('.lp-cal-weeks .lp-calendar-weekdays'));
        $this->assertCount(0, $crawler->filter('.lp-cal-weeks .lp-cal-legend'));
    }

    public function testOverlappingAbsencesRenderAsStackedBars(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        // Wrap in a transaction that is rolled back at the end so this fixture
        // data never actually persists beyond the test (no DAMA test bundle here).
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $employe = $userRepository->findOneBy(['email' => 'employe@example.com']);
            $approbateur = $userRepository->findOneBy(['email' => 'responsable@example.com']);
            $typeAnnuel = $em->getRepository(TypeConge::class)->findOneBy(['libelle' => 'Congé annuel']);
            $typeMaladie = $em->getRepository(TypeConge::class)->findOneBy(['libelle' => 'Congé maladie']);

            $approuvee = new DemandeConge();
            $approuvee->setEmploye($employe);
            $approuvee->setTypeConge($typeAnnuel);
            $approuvee->setApprobateur($approbateur);
            $approuvee->setDateDebut(new \DateTimeImmutable('2026-04-06'));
            $approuvee->setDateFin(new \DateTimeImmutable('2026-04-08'));
            $approuvee->setStatut(StatutDemande::APPROUVE);
            $em->persist($approuvee);

            $enAttente = new DemandeConge();
            $enAttente->setEmploye($employe);
            $enAttente->setTypeConge($typeMaladie);
            $enAttente->setApprobateur($approbateur);
            $enAttente->setDateDebut(new \DateTimeImmutable('2026-04-07'));
            $enAttente->setDateFin(new \DateTimeImmutable('2026-04-09'));
            $enAttente->setStatut(StatutDemande::EN_ATTENTE);
            $em->persist($enAttente);

            $em->flush();

            $client->loginUser($employe);
            $crawler = $client->request('GET', '/calendrier?mois=4&annee=2026');

            $this->assertResponseIsSuccessful();
            $bars = $crawler->filter('.lp-cal-bar');
            $this->assertCount(2, $bars);
            $this->assertSelectorExists('.lp-cal-bar-approuve');
            $this->assertSelectorExists('.lp-cal-bar-en_attente');

            // Overlapping dates (6-8 and 7-9 April) must be assigned to different lanes (grid-row).
            $rows = $bars->each(fn ($node) => preg_match('/grid-row:\s*(\d+)/', $node->attr('style'), $m) ? $m[1] : null);
            $this->assertNotSame($rows[0], $rows[1]);
        } finally {
            $connection->rollBack();
        }
    }
}
