<?php

namespace App\DataFixtures;

use App\Entity\JourFerie;
use App\Entity\SoldeConge;
use App\Entity\TypeConge;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // --- Types de congés (RG-08/RG-09) ---
        $typeAnnuel = $this->creerType($manager, 'Congé annuel', 'Congé payé accordé chaque année civile.', false);
        $typeMaladie = $this->creerType($manager, 'Congé maladie', 'Congé accordé en cas de maladie, sur présentation d\'un justificatif médical.', true); // pièce jointe obligatoire (réponse #3)
        $typeSansSolde = $this->creerType($manager, 'Congé sans solde', 'Congé non rémunéré, accordé sans limite de solde.', false);
        $typeRecup = $this->creerType($manager, 'Récupération', 'Jours accordés en compensation d\'heures ou de jours supplémentaires travaillés.', false);
        $typeExceptionnel = $this->creerType($manager, 'Congé exceptionnel', 'Congé accordé pour un événement familial ou personnel exceptionnel.', false);

        $feries = [
            ['2026-01-01', 'Nouvel An'],
            ['2026-01-11', 'Manifeste de l’Indépendance'],

            
            ['2026-05-01', 'Fête du Travail'],
            ['2026-07-30', 'Fête du Trône'],
            ['2026-08-14', 'Journée Oued Eddahab'],
            ['2026-08-20', 'Révolution du Roi et du Peuple'],



            
            ['2026-08-21', 'Fête de la Jeunesse'],
            ['2026-11-06', 'Marche Verte'],
            ['2026-11-18', 'Fête de l’Indépendance'],
        ];
        foreach ($feries as [$date, $libelle]) {
            $jf = new JourFerie();
            $jf->setDate(new \DateTimeImmutable($date));
            $jf->setLibelle($libelle);
            $jf->setRecurrent(true);
            $manager->persist($jf);
        }

        // --- Utilisateurs de test (un par rôle, hiérarchie RG-07/RG-14) ---
        $admin = $this->creerUser($manager, 'admin@example.com', 'Admin', 'Test', ['ROLE_ADMIN'], null);
        $rh = $this->creerUser($manager, 'rh@example.com', 'Ressh', 'RH', ['ROLE_RH'], null);
        $responsable = $this->creerUser($manager, 'responsable@example.com', 'Resp', 'Manager', ['ROLE_RESPONSABLE'], $rh);
        $employe = $this->creerUser($manager, 'employe@example.com', 'Emp', 'Test', ['ROLE_EMPLOYE'], $responsable);

        // Lien circulaire : RH approuve Responsable, Responsable approuve RH
        $rh->setResponsable($responsable);

        $manager->flush();

        // --- Soldes initiaux (RG-29/31), gérés manuellement par le RH pour la V1 ---
        foreach ([$employe, $responsable, $rh] as $u) {
            foreach ([$typeAnnuel, $typeMaladie, $typeSansSolde, $typeRecup, $typeExceptionnel] as $type) {
                $solde = new SoldeConge();
                $solde->setEmploye($u);
                $solde->setTypeConge($type);
                $solde->setSolde(0.0);
                $manager->persist($solde);
            }
        }

        $manager->flush();
    }

    private function creerType(ObjectManager $manager, string $libelle, string $description, bool $pjObligatoire): TypeConge
    {
        $type = new TypeConge();
        $type->setLibelle($libelle);
        $type->setDescription($description);
        $type->setPieceJointeObligatoire($pjObligatoire);
        $type->setActif(true);
        $manager->persist($type);
        return $type;
    }

    private function creerUser(ObjectManager $manager, string $email, string $prenom, string $nom, array $roles, ?User $responsable): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setRoles($roles);
        $user->setResponsable($responsable);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);
        return $user;
    }
}