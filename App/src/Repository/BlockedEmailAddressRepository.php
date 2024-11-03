<?php

namespace App\Repository;

use App\Entity\BlockedEmailAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BlockedEmailAddress>
 *
 * @method BlockedEmailAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockedEmailAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockedEmailAddress[]    findAll()
 * @method BlockedEmailAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlockedEmailAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlockedEmailAddress::class);
    }

    //    /**
    //     * @return BlockedEmailAddress[] Returns an array of BlockedEmailAddress objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BlockedEmailAddress
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
