<?php

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    public function findBetween(User $a, User $b): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.userFrom = :a AND f.userTo = :b) OR (f.userFrom = :b AND f.userTo = :a)')
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return Friendship[] */
    public function findAllForUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.userFrom = :u OR f.userTo = :u')
            ->setParameter('u', $user)
            ->getQuery()
            ->getResult();
    }
}
