<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findByAssocCount(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        // NOTE: There might be a way of better sanitising all of this, but until that time, this method should only
        // be used when the criteria and orderBy parameters are sanitised beforehand. In this instance, in the
        // admin category sorting options.
        $qb = $this->createQueryBuilder('category');

        foreach ($criteria as $key => $value) {
            $qb->andWhere("category.$key = :$key")
                ->setParameter($key, $value)
            ;
        }

        if ($orderBy) {
            $sortKey = key($orderBy);
            $sortValue = $orderBy[$sortKey];
            $qb->select('category, COUNT(a.id) as HIDDEN assocCount')
                ->leftJoin("category.$sortKey", 'a')
                ->groupBy('category.id')
                ->orderBy('assocCount', strtoupper($sortValue))
            ;
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    public function findCategoriesWithPosts()
    {
        return $this->createQueryBuilder('c')
            ->join('c.posts', 'p')
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
