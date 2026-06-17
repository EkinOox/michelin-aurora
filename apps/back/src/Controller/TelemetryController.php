<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\TelemetrySession;
use App\Repository\RideRepository;
use App\Repository\TelemetrySessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class TelemetryController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RideRepository $rideRepository,
        private TelemetrySessionRepository $telemetrySessionRepository,
    ) {
    }

    #[Route('/api/telemetry', name: 'api_telemetry_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || !isset($payload['ride_id'])) {
            return new JsonResponse(['error' => 'ride_id is required'], 400);
        }

        $ride = $this->rideRepository->find($payload['ride_id']);

        if (!$ride) {
            return new JsonResponse(['error' => 'Unknown ride_id'], 404);
        }

        $session = new TelemetrySession();
        $session->setRide($ride);
        $session->setPressureFrontBar((float) ($payload['pressure_front_bar'] ?? 0));
        $session->setPressureRearBar((float) ($payload['pressure_rear_bar'] ?? 0));
        $session->setSpeedKmh((float) ($payload['speed_kmh'] ?? 0));
        $alertTriggered = (bool) ($payload['alert_triggered'] ?? false);
        $session->setAlertTriggered($alertTriggered);

        $this->entityManager->persist($session);

        if ($alertTriggered) {
            $pressure = (float) ($payload['pressure_rear_bar'] ?? $payload['pressure_front_bar'] ?? 0);
            $notif = new Notification();
            $notif->setUser($ride->getUser());
            $notif->setType('tire_alert');
            $notif->setTitle('Alerte pression pneu');
            $notif->setBody(sprintf(
                'Chute de pression détectée (%.1f bar) pendant ta sortie. Vérifie tes pneus.',
                $pressure
            ));
            $notif->setData([
                'ride_id'       => $ride->getId()->toString(),
                'pressure_bar'  => $pressure,
                'session_at'    => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]);
            $this->entityManager->persist($notif);
        }

        $this->entityManager->flush();

        return new JsonResponse(['status' => 'created'], 201);
    }

    #[Route('/api/telemetry/stream', name: 'api_telemetry_stream', methods: ['GET'])]
    public function stream(Request $request): StreamedResponse
    {
        $rideId = $request->query->get('ride_id');

        $response = new StreamedResponse(function () use ($rideId) {
            $lastRecordedAt = new \DateTimeImmutable('-1 second');

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $qb = $this->telemetrySessionRepository->createQueryBuilder('t')
                    ->andWhere('t.recordedAt > :since')
                    ->setParameter('since', $lastRecordedAt)
                    ->orderBy('t.recordedAt', 'ASC');

                if ($rideId) {
                    $qb->andWhere('t.ride = :rideId')->setParameter('rideId', $rideId);
                }

                $sessions = $qb->getQuery()->getResult();

                foreach ($sessions as $session) {
                    $lastRecordedAt = $session->getRecordedAt();

                    $data = [
                        'pressure_front_bar' => $session->getPressureFrontBar(),
                        'pressure_rear_bar' => $session->getPressureRearBar(),
                        'speed_kmh' => $session->getSpeedKmh(),
                        'alert_triggered' => $session->isAlertTriggered(),
                        'recorded_at' => $session->getRecordedAt()->format(\DateTimeInterface::ATOM),
                    ];

                    echo 'data: '.json_encode($data)."\n\n";
                }

                echo ": ping\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                $this->entityManager->clear();

                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}
