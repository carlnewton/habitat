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
}
