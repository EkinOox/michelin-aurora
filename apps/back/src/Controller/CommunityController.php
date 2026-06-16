<?php

namespace App\Controller;

use App\Repository\CommunityRiderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CommunityController
{
    public function __construct(private CommunityRiderRepository $communityRiderRepository)
    {
    }

    #[Route('/api/community/riders', name: 'api_community_riders', methods: ['GET'])]
    public function riders(): JsonResponse
    {
        $riders = array_map(static fn ($r) => [
            'name' => $r->getName(),
            'initials' => $r->getInitials(),
            'rank' => $r->getRank()->value,
            'km_this_month' => $r->getKmThisMonth(),
            'match_percent' => $r->getMatchPercent(),
            'color_hex' => $r->getColorHex(),
        ], $this->communityRiderRepository->findAll());

        return new JsonResponse($riders);
    }
}
