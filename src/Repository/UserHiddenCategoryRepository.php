<?php

namespace App\Repository;

use App\Entity\UserHiddenCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserHiddenCategory>
 */
class UserHiddenCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserHiddenCategory::class);
    }
}
