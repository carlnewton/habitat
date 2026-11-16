<?php

namespace App\Repository;

use App\Entity\PostAttachment;
use App\Entity\User;
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

    public function findOrphanedById(array $ids, User $user): array
    {
        $qb = $this->createQueryBuilder('p');

        return $qb
            ->andWhere('p.post IS NULL')
            ->andWhere($qb->expr()->in('p.id', $ids))
            ->andWhere('p.user = :userId')
            ->setParameter('userId', $user->getId())
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
