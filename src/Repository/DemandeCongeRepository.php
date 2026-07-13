<?php

namespace App\Repository;

use App\Entity\DemandeConge;
use App\Entity\User;
use App\Enum\StatutDemande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeConge::class);
    }

    /** @return DemandeConge[] */
    public function findMesDemandes(User $employe): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return DemandeConge[] */
    public function findAValiderPar(User $approbateur): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.approbateur = :approbateur')
            ->andWhere('d.statut = :statut')
            ->setParameter('approbateur', $approbateur)
            ->setParameter('statut', StatutDemande::EN_ATTENTE)
            ->orderBy('d.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return DemandeConge[] */
    public function findMesAbsencesDuMois(User $employe, \DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.employe = :employe')
            ->andWhere('d.statut IN (:statuts)')
            ->andWhere('d.dateDebut <= :fin AND d.dateFin >= :debut')
            ->setParameter('employe', $employe)
            ->setParameter('statuts', [
                StatutDemande::BROUILLON,
                StatutDemande::EN_ATTENTE,
                StatutDemande::APPROUVE,
                StatutDemande::REFUSE,
            ])
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('d.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findProchaineAbsence(User $employe): ?DemandeConge
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.employe = :employe')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.dateDebut >= :today')
            ->setParameter('employe', $employe)
            ->setParameter('statut', StatutDemande::APPROUVE)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('d.dateDebut', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return DemandeConge[] */
    public function findApprouvees(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->setParameter('statut', StatutDemande::APPROUVE)
            ->orderBy('d.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return DemandeConge[] */
    public function findToutesAvecFiltres(?string $statut = null, ?int $typeCongeId = null): array
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.dateCreation', 'DESC');

        if ($statut) {
            $qb->andWhere('d.statut = :statut')->setParameter('statut', $statut);
        }
        if ($typeCongeId) {
            $qb->andWhere('d.typeConge = :type')->setParameter('type', $typeCongeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function countParStatut(StatutDemande $statut): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countParStatutDepuis(StatutDemande $statut, \DateTimeImmutable $depuis): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.dateModification >= :depuis')
            ->setParameter('statut', $statut)
            ->setParameter('depuis', $depuis)
            ->getQuery()
            ->getSingleScalarResult();
    }
}