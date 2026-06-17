<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Repository\CyclistProfileRepository;
use App\Repository\FriendshipRepository;
use App\Repository\RideRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CommunityController
{
    private const AVATAR_COLORS = [
        '#27509B', '#84BD00', '#FCE500', '#D62828',
        '#582C83', '#F77F00', '#2EC4B6', '#E71D36',
    ];

    public function __construct(
        private UserRepository $userRepository,
        private CyclistProfileRepository $cyclistProfileRepository,
        private FriendshipRepository $friendshipRepository,
        private RideRepository $rideRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    #[Route('/api/community/stats', name: 'api_community_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $allUsers = $this->userRepository->findAll();
        $total = count($allUsers);
        $myPoints = $me->getTotalPoints();
        $myProfile = $this->cyclistProfileRepository->findOneBy(['user' => $me]);
        $myBikeType = $myProfile?->getBikeType()->value;

        $totalPoints = 0;
        $totalKm = 0.0;
        $rank = 1;
        $sameDiscipline = 0;

        foreach ($allUsers as $u) {
            $pts = $u->getTotalPoints();
            $totalPoints += $pts;
            $totalKm += $this->rideRepository->getTotalKmForUser($u);
            if ($u->getId()->toString() !== $me->getId()->toString() && $pts > $myPoints) {
                $rank++;
            }
            $p = $this->cyclistProfileRepository->findOneBy(['user' => $u]);
            if ($p && $myBikeType && $p->getBikeType()->value === $myBikeType && $u->getId()->toString() !== $me->getId()->toString()) {
                $sameDiscipline++;
            }
        }

        $myTotalKm = $this->rideRepository->getTotalKmForUser($me);
        $myRidesCount = $this->rideRepository->getTotalRidesForUser($me);
        $avgKm = $total > 0 ? round($totalKm / $total, 1) : 0.0;

        return new JsonResponse([
            'total_riders' => $total,
            'my_rank' => $rank,
            'my_points' => $myPoints,
            'avg_points' => $total > 0 ? (int) round($totalPoints / $total) : 0,
            'same_discipline_count' => $sameDiscipline,
            'my_bike_type' => $myBikeType,
            'my_total_km' => $myTotalKm,
            'my_rides_count' => $myRidesCount,
            'avg_km' => $avgKm,
        ]);
    }

    #[Route('/api/community/users', name: 'api_community_users', methods: ['GET'])]
    public function users(): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $allUsers = $this->userRepository->findAll();
        $myFriendships = $this->friendshipRepository->findAllForUser($me);

        $statusMap = [];
        foreach ($myFriendships as $f) {
            $otherId = $f->getUserFrom()->getId()->toString() === $me->getId()->toString()
                ? $f->getUserTo()->getId()->toString()
                : $f->getUserFrom()->getId()->toString();

            if ($f->getStatus() === 'accepted') {
                $statusMap[$otherId] = 'friends';
            } elseif ($f->getUserFrom()->getId()->toString() === $me->getId()->toString()) {
                $statusMap[$otherId] = 'pending_sent';
            } else {
                $statusMap[$otherId] = 'pending_received';
            }
        }

        $result = [];
        foreach ($allUsers as $user) {
            if ($user->getId()->toString() === $me->getId()->toString()) {
                continue;
            }

            $profile = $this->cyclistProfileRepository->findOneBy(['user' => $user]);
            $uid = $user->getId()->toString();

            $result[] = [
                'id' => $uid,
                'name' => $user->getName(),
                'city' => $user->getCity(),
                'rewards_level' => $user->getRewardsLevel()->value,
                'total_points' => $user->getTotalPoints(),
                'color_hex' => self::AVATAR_COLORS[crc32($uid) % count(self::AVATAR_COLORS)],
                'bike_photo_url' => $profile?->getBikePhotoUrl(),
                'profile' => $profile ? [
                    'bike_type' => $profile->getBikeType()->value,
                    'rider_level' => $profile->getRiderLevel()->value,
                    'usage_type' => $profile->getUsageType()->value,
                ] : null,
                'friendship_status' => $statusMap[$uid] ?? 'none',
                'total_km' => $this->rideRepository->getTotalKmForUser($user),
                'rides_count' => $this->rideRepository->getTotalRidesForUser($user),
            ];
        }

        return new JsonResponse($result);
    }

    #[Route('/api/community/users/{id}', name: 'api_community_user_show', methods: ['GET'])]
    public function userShow(string $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $profile = $this->cyclistProfileRepository->findOneBy(['user' => $user]);
        $friendship = $this->friendshipRepository->findBetween($me, $user);

        $status = 'none';
        if ($friendship) {
            if ($friendship->getStatus() === 'accepted') {
                $status = 'friends';
            } elseif ($friendship->getUserFrom()->getId()->toString() === $me->getId()->toString()) {
                $status = 'pending_sent';
            } else {
                $status = 'pending_received';
            }
        }

        $uid = $user->getId()->toString();

        return new JsonResponse([
            'id' => $uid,
            'name' => $user->getName(),
            'city' => $user->getCity(),
            'rewards_level' => $user->getRewardsLevel()->value,
            'total_points' => $user->getTotalPoints(),
            'color_hex' => self::AVATAR_COLORS[crc32($uid) % count(self::AVATAR_COLORS)],
            'bike_photo_url' => $profile?->getBikePhotoUrl(),
            'profile' => $profile ? [
                'bike_type' => $profile->getBikeType()->value,
                'rider_level' => $profile->getRiderLevel()->value,
                'usage_type' => $profile->getUsageType()->value,
            ] : null,
            'friendship_status' => $status,
            'total_km' => $this->rideRepository->getTotalKmForUser($user),
            'rides_count' => $this->rideRepository->getTotalRidesForUser($user),
            'km_this_month' => $this->rideRepository->getKmThisMonthForUser($user),
        ]);
    }

    #[Route('/api/community/friends/{id}', name: 'api_community_friend_toggle', methods: ['POST'])]
    public function friendToggle(string $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $other = $this->userRepository->find($id);
        if (!$other || $other->getId()->toString() === $me->getId()->toString()) {
            return new JsonResponse(['error' => 'Invalid user'], 400);
        }

        $existing = $this->friendshipRepository->findBetween($me, $other);

        if (!$existing) {
            $f = new Friendship();
            $f->setUserFrom($me)->setUserTo($other)->setStatus('pending');
            $this->entityManager->persist($f);
            $this->entityManager->flush();
            return new JsonResponse(['friendship_status' => 'pending_sent']);
        }

        if ($existing->getStatus() === 'pending' && $existing->getUserTo()->getId()->toString() === $me->getId()->toString()) {
            $existing->setStatus('accepted');
            $this->entityManager->flush();
            return new JsonResponse(['friendship_status' => 'friends']);
        }

        return new JsonResponse(['friendship_status' => $existing->getStatus() === 'accepted' ? 'friends' : 'pending_sent']);
    }

    #[Route('/api/community/friends/{id}', name: 'api_community_friend_remove', methods: ['DELETE'])]
    public function friendRemove(string $id): JsonResponse
    {
        /** @var \App\Entity\User $me */
        $me = $this->security->getUser();

        $other = $this->userRepository->find($id);
        if (!$other) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $existing = $this->friendshipRepository->findBetween($me, $other);
        if ($existing) {
            $this->entityManager->remove($existing);
            $this->entityManager->flush();
        }

        return new JsonResponse(['friendship_status' => 'none']);
    }
}
