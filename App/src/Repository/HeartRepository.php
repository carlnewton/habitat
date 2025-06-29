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
}
