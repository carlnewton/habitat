<?php

namespace App\Repository;

use App\Entity\PostAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostAttachment>
 *
 * @method PostAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostAttachment[]    findAll()
 * @method PostAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostAttachment::class);
    }

    public function findOrphanedById(array $ids): array
    {
        $qb = $this->createQueryBuilder('p');

        return $qb
            ->andWhere('p.post IS NULL')
            ->andWhere($qb->expr()->in('p.id', $ids))
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    //    public function findOneBySomeField($value): ?PostAttachment
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
