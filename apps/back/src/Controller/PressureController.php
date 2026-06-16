<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PressureController
{
    private const BASE_PRESSURE_BAR = 2.9;
    private const RAIN_FACTOR = 0.9;
    private const FRONT_OFFSET_BAR = 0.2;

    #[Route('/api/pressure', name: 'api_pressure_recommendation', methods: ['GET'])]
    public function recommendation(Request $request): JsonResponse
    {
        $rain = $request->query->getBoolean('rain', true);
        $base = self::BASE_PRESSURE_BAR;

        $rear = $rain ? $base * self::RAIN_FACTOR : $base;
        $front = $rear - self::FRONT_OFFSET_BAR;

        return new JsonResponse([
            'rain' => $rain,
            'front_bar' => round($front, 1),
            'rear_bar' => round($rear, 1),
        ]);
    }
}
