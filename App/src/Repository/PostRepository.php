<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\UserHiddenCategory;
use App\Utilities\LatLong;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 *
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findByAssocCount(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        // NOTE: There might be a way of better sanitising all of this, but until that time, this method should only
        // be used when the criteria and orderBy parameters are sanitised beforehand. In this instance, in the
        // moderation sorting options.
        $qb = $this->createQueryBuilder('post');

        foreach ($criteria as $key => $value) {
            $qb->andWhere("post.$key = :$key")
                ->setParameter($key, $value)
            ;
        }

        if ($orderBy) {
            $sortKey = key($orderBy);
            $sortValue = $orderBy[$sortKey];
            $qb->select('post, COUNT(a.id) as HIDDEN assocCount')
                ->leftJoin("post.$sortKey", 'a')
                ->groupBy('post.id')
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

    public function findByDistance(array $criteria, LatLong $latLong, ?int $limit = null, ?int $offset = null, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('post');

        foreach ($criteria as $key => $value) {
            $qb->andWhere("post.$key = :$key")
                ->setParameter($key, $value)
            ;
        }

        $qb->andWhere('post.latitude IS NOT NULL');
        $qb->andWhere('post.longitude IS NOT NULL');
        if (!is_null($userId)) {
            $qb->leftJoin('post.category', 'c')
                ->leftJoin(UserHiddenCategory::class, 'uhc', 'WITH', 'uhc.category = c.id AND uhc.user = :userId')
                ->andWhere('uhc.id IS NULL')
                ->setParameter('userId', $userId)
            ;
        }

        $qb->addSelect('DEGREES(ACOS((SIN(RADIANS(:latitude)) * SIN(RADIANS(post.latitude))) + (COS(RADIANS(:latitude)) * COS(RADIANS(post.latitude)) * COS(RADIANS(:longitude - post.longitude))))) * :radius AS distanceMiles')
            ->setParameter('latitude', $latLong->latitude)
            ->setParameter('longitude', $latLong->longitude)
            ->setParameter('radius', 60 * 1.1515)
            ->addOrderBy('distanceMiles', 'ASC')
        ;

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        $results = $qb->getQuery()->getResult();

        $entities = [];
        foreach ($results as $result) {
            $entity = $result[0];
            $entity->setDistanceMiles($result['distanceMiles']);
            $entities[] = $entity;
        }

        return $entities;
    }

    public function findByHiddenCategories(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null, ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('post');

        foreach ($criteria as $key => $value) {
            $qb->andWhere("post.$key = :$key")
                ->setParameter($key, $value)
            ;
        }

        if (!is_null($userId)) {
            $qb->leftJoin('post.category', 'c')
               ->leftJoin(UserHiddenCategory::class, 'uhc', 'WITH', 'uhc.category = c.id AND uhc.user = :userId')
               ->andWhere('uhc.id IS NULL')
               ->setParameter('userId', $userId)
            ;
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->orderBy('post.' . $field, $direction);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
