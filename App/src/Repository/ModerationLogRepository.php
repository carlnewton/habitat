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

    //    /**
    //     * @return ModerationLog[] Returns an array of ModerationLog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ModerationLog
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
