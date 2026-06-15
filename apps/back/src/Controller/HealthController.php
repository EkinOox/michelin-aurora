<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Michelin Aurora API opérationnelle',
            'version' => '1.0.0',
            'status'  => 'ok',
        ]);
    }
}
