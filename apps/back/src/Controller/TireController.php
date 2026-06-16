<?php

namespace App\Controller;

use App\Entity\Tire;
use App\Repository\TireRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TireController
{
    public function __construct(private TireRepository $tireRepository)
    {
    }

    #[Route('/api/tires', name: 'api_tires_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tires = array_map([$this, 'serialize'], $this->tireRepository->findAll());

        return new JsonResponse($tires);
    }

    #[Route('/api/tires/{id}', name: 'api_tires_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $tire = $this->tireRepository->find($id);

        if (!$tire) {
            return new JsonResponse(['error' => 'Tire not found'], 404);
        }

        return new JsonResponse($this->serialize($tire));
    }

    private function serialize(Tire $tire): array
    {
        return [
            'id' => (string) $tire->getId(),
            'name' => $tire->getName(),
            'bike_type' => $tire->getBikeType()->value,
            'scores' => $tire->getScores(),
            'price_eur' => $tire->getPriceEur(),
            'avg_km_lifetime' => $tire->getAvgKmLifetime(),
            'image_key' => $tire->getImageKey(),
            'subtitle' => $tire->getSubtitle(),
            'tag' => $tire->getTag(),
            'color_token' => $tire->getColorToken(),
        ];
    }
}
