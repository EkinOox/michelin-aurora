<?php

namespace App\Controller;

use App\Repository\NewsArticleRepository;
use App\Service\CyclingNewsProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NewsController
{
    public function __construct(
        private NewsArticleRepository $newsArticleRepository,
        private CyclingNewsProvider $cyclingNewsProvider,
    ) {
    }

    #[Route('/api/news', name: 'api_news_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $michelin = array_map(static fn ($a) => [
            'id' => (string) $a->getId(),
            'tag' => $a->getTag(),
            'title' => $a->getTitle(),
            'date' => $a->getDate(),
            'read_time' => $a->getReadTime(),
            'image_key' => $a->getImageKey(),
            'body' => $a->getBody(),
            'url' => null,
        ], $this->newsArticleRepository->findAll());

        // Actus cyclisme (DirectVelo, filtrées) en tête, articles Michelin ensuite.
        $articles = array_merge($this->cyclingNewsProvider->getArticles(), $michelin);

        return new JsonResponse($articles);
    }
}
