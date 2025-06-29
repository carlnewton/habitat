<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByAssocCount(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        // NOTE: There might be a way of better sanitising all of this, but until that time, this method should only
        // be used when the criteria and orderBy parameters are sanitised beforehand. In this instance, in the
        // moderation sorting options.
        $qb = $this->createQueryBuilder('user');

        foreach ($criteria as $key => $value) {
            $qb->andWhere("user.$key = :$key")
                ->setParameter($key, $value)
            ;
        }

        if ($orderBy) {
            $sortKey = key($orderBy);
            $sortValue = $orderBy[$sortKey];
            $qb->select('user, COUNT(a.id) as HIDDEN assocCount')
                ->leftJoin("user.$sortKey", 'a')
                ->groupBy('user.id')
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

    public function findUsersWithComments()
    {
        return $this->createQueryBuilder('u')
            ->join('u.comments', 'c')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findUsersWithPosts()
    {
        return $this->createQueryBuilder('u')
            ->join('u.posts', 'p')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findUsersByRole(string $role)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
           ->where('u.roles LIKE :roles')
           ->setParameter('roles', '%"' . $role . '"%');

        return $qb->getQuery()->getResult();
    }
}
