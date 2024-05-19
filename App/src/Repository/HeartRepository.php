<?php

namespace App\Repository;

use App\Entity\Heart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Heart>
 *
 * @method Heart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Heart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Heart[]    findAll()
 * @method Heart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HeartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Heart::class);
    }

    //    /**
    //     * @return Heart[] Returns an array of Heart objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Heart
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
