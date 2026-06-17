<?php

namespace App\Tests\Unit\Controller;

use App\Controller\TelemetryController;
use App\Entity\Ride;
use App\Entity\TelemetrySession;
use App\Repository\RideRepository;
use App\Repository\TelemetrySessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Création de télémétrie : point d'écriture exposé qui prend du JSON brut.
 * On vérifie les garde-fous d'entrée et que la session est bien remplie
 * à partir du payload.
 */
final class TelemetryControllerTest extends TestCase
{
    private function makeController(
        ?RideRepository $rideRepository = null,
        ?EntityManagerInterface $entityManager = null,
    ): TelemetryController {
        return new TelemetryController(
            $entityManager ?? $this->createStub(EntityManagerInterface::class),
            $rideRepository ?? $this->createStub(RideRepository::class),
            $this->createStub(TelemetrySessionRepository::class),
        );
    }

    private function request(array $body): Request
    {
        return Request::create('/api/telemetry', 'POST', [], [], [], [], json_encode($body));
    }

    public function testReturns400WhenRideIdMissing(): void
    {
        $controller = $this->makeController();

        $response = $controller->create($this->request(['speed_kmh' => 30]));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testReturns404WhenRideUnknown(): void
    {
        $rideRepository = $this->createStub(RideRepository::class);
        $rideRepository->method('find')->willReturn(null);

        $controller = $this->makeController($rideRepository);

        $response = $controller->create($this->request(['ride_id' => 'does-not-exist']));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testCreatesSessionFromPayload(): void
    {
        $rideRepository = $this->createStub(RideRepository::class);
        $rideRepository->method('find')->willReturn(new Ride());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')
            ->with(self::callback(static function (TelemetrySession $s): bool {
                return 2.4 === $s->getPressureFrontBar()
                    && 2.6 === $s->getPressureRearBar()
                    && 32.5 === $s->getSpeedKmh()
                    && true === $s->isAlertTriggered();
            }));
        $entityManager->expects(self::once())->method('flush');

        $controller = $this->makeController($rideRepository, $entityManager);

        $response = $controller->create($this->request([
            'ride_id' => 'ride-1',
            'pressure_front_bar' => 2.4,
            'pressure_rear_bar' => 2.6,
            'speed_kmh' => 32.5,
            'alert_triggered' => true,
        ]));

        self::assertSame(201, $response->getStatusCode());
    }
}
