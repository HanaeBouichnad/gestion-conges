<?php

namespace App\Repository;

use App\Entity\SoldeConge;
use App\Entity\TypeConge;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SoldeCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoldeConge::class);
    }

    /** @return SoldeConge[] */
    public function findParEmploye(User $employe): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.employe = :employe')
            ->setParameter('employe', $employe)
            ->getQuery()
            ->getResult();
    }

    public function findOneParEmployeEtType(User $employe, TypeConge $type): ?SoldeConge
    {
        return $this->findOneBy(['employe' => $employe, 'typeConge' => $type]);
    }
}