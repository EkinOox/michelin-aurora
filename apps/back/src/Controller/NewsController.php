<?php

namespace App\Controller;

use App\Entity\Enum\BikeType;
use App\Entity\User;
use App\Repository\CyclistProfileRepository;
use App\Repository\NewsArticleRepository;
use App\Service\CyclingNewsProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NewsController
{
    public function __construct(
        private NewsArticleRepository $newsArticleRepository,
        private CyclingNewsProvider $cyclingNewsProvider,
        private CyclistProfileRepository $cyclistProfileRepository,
        private Security $security,
    ) {
    }

    #[Route('/api/news', name: 'api_news_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $michelin = array_map(static fn ($a) => [
            'id' => (string) $a->getId(),
            'cat' => 'michelin',
            'tag' => $a->getTag(),
            'title' => $a->getTitle(),
            'date' => $a->getDate(),
            'read_time' => $a->getReadTime(),
            'image_key' => $a->getImageKey(),
            'body' => $a->getBody(),
            'url' => null,
        ], $this->newsArticleRepository->findAll());

        $articles = array_merge($this->cyclingNewsProvider->getArticles(), $michelin);

        // Personnalisation : on remonte la discipline du cycliste connecté.
        $preferred = $this->preferredCat();
        if (null !== $preferred) {
            usort($articles, static function (array $a, array $b) use ($preferred): int {
                $rank = static fn (array $x): int => 'event' === $x['cat'] ? 0 : ($x['cat'] === $preferred ? 1 : 2);

                return $rank($a) <=> $rank($b);
            });
        }

        return new JsonResponse($articles);
    }

    /** Catégorie large préférée déduite du type de vélo du cycliste connecté. */
    private function preferredCat(): ?string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        $profile = $this->cyclistProfileRepository->findOneBy(['user' => $user]);
        if (null === $profile) {
            return null;
        }

        return match ($profile->getBikeType()) {
            BikeType::Route => 'route',
            BikeType::Vtt, BikeType::Gravel => 'vtt',
            BikeType::Vae => null,
        };
    }
}
