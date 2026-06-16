<?php

namespace App\Controller;

use App\Entity\Enum\TerrainType;
use App\Entity\Ride;
use App\Entity\User;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RideController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RideRepository $rideRepository,
        private Security $security,
    ) {
    }

    #[Route('/api/rides/demo', name: 'api_rides_demo', methods: ['GET'])]
    public function demo(): JsonResponse
    {
        $ride = $this->rideRepository->findOneBy([], ['startedAt' => 'DESC']);

        if (!$ride) {
            return new JsonResponse(['error' => 'No demo ride found, run doctrine:fixtures:load'], 404);
        }

        return new JsonResponse([
            'id' => (string) $ride->getId(),
            'user_id' => (string) $ride->getUser()->getId(),
            'started_at' => $ride->getStartedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/api/rides', name: 'api_rides_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $payload = json_decode($request->getContent(), true) ?? [];

        $ride = new Ride();
        $ride->setUser($user);
        $ride->setDistanceKm(0);
        $ride->setElevationM(0);
        $ride->setTerrainType(TerrainType::tryFrom($payload['terrain_type'] ?? 'mixed') ?? TerrainType::Mixed);
        $ride->setStartedAt(new \DateTimeImmutable());

        $this->entityManager->persist($ride);
        $this->entityManager->flush();

        return new JsonResponse(['id' => (string) $ride->getId()], 201);
    }
}
