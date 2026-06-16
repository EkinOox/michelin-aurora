<?php

namespace App\Controller;

use App\Entity\CyclistProfile;
use App\Entity\Enum\BikeType;
use App\Entity\Enum\RiderLevel;
use App\Entity\Enum\UsageType;
use App\Repository\CyclistProfileRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private CyclistProfileRepository $cyclistProfileRepository,
    ) {
    }

    #[Route('/api/profile', name: 'api_profile_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $user = $this->userRepository->findOneBy([]);

        if (!$user) {
            return new JsonResponse(['error' => 'No demo user found, run doctrine:fixtures:load'], 404);
        }

        $profile = $this->cyclistProfileRepository->findOneBy(['user' => $user]);

        return new JsonResponse([
            'id' => (string) $user->getId(),
            'name' => $user->getName(),
            'city' => $user->getCity(),
            'rewards_level' => $user->getRewardsLevel()->value,
            'total_points' => $user->getTotalPoints(),
            'profile' => $profile ? [
                'bike_type' => $profile->getBikeType()->value,
                'rider_level' => $profile->getRiderLevel()->value,
                'usage_type' => $profile->getUsageType()->value,
                'preferences' => $profile->getPreferences(),
            ] : null,
        ]);
    }

    #[Route('/api/profile', name: 'api_profile_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->userRepository->findOneBy([]);

        if (!$user) {
            return new JsonResponse(['error' => 'No demo user found, run doctrine:fixtures:load'], 404);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        $profile = $this->cyclistProfileRepository->findOneBy(['user' => $user]) ?? new CyclistProfile();
        $profile->setUser($user);
        $profile->setBikeType(BikeType::from($payload['bike_type'] ?? 'gravel'));
        $profile->setRiderLevel(RiderLevel::from($payload['rider_level'] ?? 'expert'));
        $profile->setUsageType(UsageType::from($payload['usage_type'] ?? 'sport'));
        $profile->setPreferences($payload['preferences'] ?? null);

        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        return new JsonResponse(['status' => 'updated']);
    }
}
