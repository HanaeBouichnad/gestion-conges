<?php

namespace App\Repository;

use App\Entity\DemandeConge;
use App\Entity\User;
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
}