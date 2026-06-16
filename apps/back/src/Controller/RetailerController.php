<?php

namespace App\Controller;

use App\Repository\RetailerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class RetailerController
{
    public function __construct(private RetailerRepository $retailerRepository)
    {
    }

    #[Route('/api/retailers', name: 'api_retailers_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $retailers = array_map(static fn ($r) => [
            'name' => $r->getName(),
            'sub' => $r->getSub(),
            'url' => $r->getUrl(),
        ], $this->retailerRepository->findAll());

        return new JsonResponse($retailers);
    }
}
