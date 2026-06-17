<?php

namespace App\Repository;

use App\Entity\Ride;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ride>
 */
class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

    public function getTotalKmForUser(User $user): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.distanceKm), 0) AS total_km')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    public function getTotalRidesForUser(User $user): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function getKmThisMonthForUser(User $user): float
    {
        $start = new \DateTimeImmutable('first day of this month 00:00:00');
        $end   = new \DateTimeImmutable('first day of next month 00:00:00');

        $result = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.distanceKm), 0) AS km_month')
            ->where('r.user = :user')
            ->andWhere('r.startedAt >= :start')
            ->andWhere('r.startedAt < :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }
}
