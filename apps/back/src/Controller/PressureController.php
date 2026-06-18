<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CyclistProfileRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PressureController
{
    // Base pressures per bike type (front / rear, in bar)
    private const BASE = [
        'route'  => ['front' => 3.2, 'rear' => 3.5],
        'gravel' => ['front' => 2.6, 'rear' => 2.8],
        'vtt'    => ['front' => 1.7, 'rear' => 1.9],
        'vae'    => ['front' => 2.8, 'rear' => 3.0],
    ];

    // WMO weather codes that indicate rain/precipitation
    private const RAIN_CODES = [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99];

    #[Route('/api/pressure', name: 'api_pressure_recommendation', methods: ['GET'])]
    public function recommendation(
        Request $request,
        ?Security $security = null,
        ?CyclistProfileRepository $profileRepo = null,
    ): JsonResponse {
        $bikeType   = 'gravel';
        $riderLevel = 'intermediate';

        if ($security !== null && $profileRepo !== null) {
            /** @var User|null $user */
            $user    = $security->getUser();
            $profile = $user ? $profileRepo->findOneBy(['user' => $user]) : null;
            $bikeType   = $profile?->getBikeType()?->value   ?? 'gravel';
            $riderLevel = $profile?->getRiderLevel()?->value ?? 'intermediate';
        }

        $base = self::BASE[$bikeType] ?? self::BASE['gravel'];

        // Weather params sent by the frontend (Open-Meteo data)
        $temp        = (float) $request->query->get('temp', 20);
        $humidity    = (float) $request->query->get('humidity', 50);
        $windSpeed   = (float) $request->query->get('wind', 0);
        $precip      = (float) $request->query->get('precip', 0);
        $weatherCode = (int)   $request->query->get('code', 0);

        // 'rain' shortcut param (bool 0/1) allows direct override
        $rainParam = $request->query->get('rain');
        $isRain = ($rainParam !== null)
            ? (bool)(int)$rainParam
            : ($precip > 0.1 || in_array($weatherCode, self::RAIN_CODES));

        // Temperature: cold = less pressure (stiffer rubber), hot asphalt = thermal expansion
        $tempAdj = match (true) {
            $temp < 0   => -0.20,
            $temp < 10  => -0.10,
            $temp < 15  => -0.05,
            $temp > 35  => +0.15,
            $temp > 25  => +0.08,
            default     => 0.0,
        };

        // Rain/wet: lower pressure increases contact patch → more grip
        $rainAdj = $isRain ? -round($base['rear'] * 0.10, 2) : ($humidity > 85 ? -0.05 : 0.0);

        // Wind: crosswinds need stability → slight increase
        $windAdj = match (true) {
            $windSpeed > 50 => +0.10,
            $windSpeed > 30 => +0.05,
            default         => 0.0,
        };

        // Rider level: beginners need more forgiving (higher pressure = more predictable),
        // experts prefer lower for grip and comfort
        $levelAdj = match ($riderLevel) {
            'beginner'     => +0.10,
            'advanced'     => -0.05,
            'expert'       => -0.10,
            default        => 0.0,
        };

        $totalAdj = $tempAdj + $rainAdj + $windAdj + $levelAdj;

        $rear  = max(1.2, round(($base['rear']  + $totalAdj) * 10) / 10);
        $front = max(1.0, round(($base['front'] + $totalAdj) * 10) / 10);

        return new JsonResponse([
            'rain'       => $isRain,
            'front_bar'  => $front,
            'rear_bar'   => $rear,
            'bike_type'  => $bikeType,
            'factors'    => [
                'base_rear'  => $base['rear'],
                'temp_adj'   => $tempAdj,
                'rain_adj'   => $rainAdj,
                'wind_adj'   => $windAdj,
                'level_adj'  => $levelAdj,
            ],
        ]);
    }
}
