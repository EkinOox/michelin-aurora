<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class EventController
{
    public function __construct(private EventRepository $eventRepository)
    {
    }

    #[Route('/api/events', name: 'api_events_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $events = array_map(static fn ($e) => [
            'id' => (string) $e->getId(),
            'name' => $e->getName(),
            'date' => $e->getDate(),
            'place' => $e->getPlace(),
            'distance_label' => $e->getDistanceLabel(),
            'type' => $e->getType()->value,
            'km_away' => $e->getKmAway(),
            'riders' => $e->getRiders(),
            'image_key' => $e->getImageKey(),
        ], $this->eventRepository->findAll());

        return new JsonResponse($events);
    }
}
