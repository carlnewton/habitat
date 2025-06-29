<?php

namespace App\Repository;

use App\Entity\ModerationLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModerationLog>
 */
class ModerationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModerationLog::class);
    }

    public function findBeforeDateTime(\DateTime $datetime): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.date <= :dateTime')
            ->setParameter('dateTime', $datetime)
            ->getQuery()
            ->getResult()
        ;
    }
}
