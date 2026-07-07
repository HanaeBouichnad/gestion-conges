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
}