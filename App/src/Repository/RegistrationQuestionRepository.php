<?php

namespace App\Repository;

use App\Entity\RegistrationQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegistrationQuestion>
 */
class RegistrationQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistrationQuestion::class);
    }

    public function getOneRandom(): ?RegistrationQuestion
    {
        return $this->createQueryBuilder('r')
            ->orderBy('RAND()')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
