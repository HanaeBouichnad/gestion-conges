<?php

namespace App\Repository;

use App\Entity\DemandeAnnulation;
use App\Entity\DemandeConge;
use App\Enum\StatutDemandeAnnulation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeAnnulationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeAnnulation::class);
    }

    /** @return DemandeAnnulation[] */
    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', StatutDemandeAnnulation::EN_ATTENTE)
            ->orderBy('a.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countEnAttente(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', StatutDemandeAnnulation::EN_ATTENTE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneEnAttentePour(DemandeConge $demande): ?DemandeAnnulation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.demandeConge = :demande')
            ->andWhere('a.statut = :statut')
            ->setParameter('demande', $demande)
            ->setParameter('statut', StatutDemandeAnnulation::EN_ATTENTE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDernierePourDemande(DemandeConge $demande): ?DemandeAnnulation
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.demandeConge = :demande')
            ->setParameter('demande', $demande)
            ->orderBy('a.dateCreation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
