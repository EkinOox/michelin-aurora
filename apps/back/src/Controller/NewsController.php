<?php

namespace App\Controller;

use App\Repository\NewsArticleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NewsController
{
    public function __construct(private NewsArticleRepository $newsArticleRepository)
    {
    }

    #[Route('/api/news', name: 'api_news_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $articles = array_map(static fn ($a) => [
            'id' => (string) $a->getId(),
            'tag' => $a->getTag(),
            'title' => $a->getTitle(),
            'date' => $a->getDate(),
            'read_time' => $a->getReadTime(),
            'image_key' => $a->getImageKey(),
            'body' => $a->getBody(),
        ], $this->newsArticleRepository->findAll());

        return new JsonResponse($articles);
    }
}
