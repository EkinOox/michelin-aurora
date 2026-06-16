<?php

namespace App\Controller;

use App\Entity\Route as RouteEntity;
use App\Repository\RouteRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RouteController
{
    public function __construct(private RouteRepository $routeRepository)
    {
    }

    #[Route('/api/routes', name: 'api_routes_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $routes = array_map([$this, 'serialize'], $this->routeRepository->findAll());

        return new JsonResponse($routes);
    }

    #[Route('/api/routes/{id}', name: 'api_routes_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $route = $this->routeRepository->find($id);

        if (!$route) {
            return new JsonResponse(['error' => 'Route not found'], 404);
        }

        return new JsonResponse($this->serialize($route));
    }

    private function serialize(RouteEntity $route): array
    {
        return [
            'id' => (string) $route->getId(),
            'name' => $route->getName(),
            'bike_type' => $route->getBikeType()->value,
            'difficulty' => $route->getDifficulty()->value,
            'michelin_score' => $route->getMichelinScore(),
            'distance_km' => $route->getDistanceKm(),
            'elevation_gain_m' => $route->getElevationGainM(),
            'surface' => $route->getSurface(),
            'duration_label' => $route->getDurationLabel(),
            'description' => $route->getDescription(),
            'safety_score' => $route->getSafetyScore(),
            'fun_score' => $route->getFunScore(),
            'match_score' => $route->getMatchScore(),
            'tag' => $route->getTag(),
            'tire_id' => $route->getTire() ? (string) $route->getTire()->getId() : null,
        ];
    }
}
