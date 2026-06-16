<?php

namespace App\Controller;

use App\Repository\RideRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RideController
{
    public function __construct(private RideRepository $rideRepository)
    {
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
}
