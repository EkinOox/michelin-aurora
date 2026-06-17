<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ProfileController;
use App\Entity\CyclistProfile;
use App\Entity\Enum\BikeType;
use App\Entity\Enum\RiderLevel;
use App\Entity\Enum\UsageType;
use App\Entity\User;
use App\Repository\CyclistProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mise à jour du profil : les champs enum viennent du payload client.
 * On vérifie qu'une valeur invalide retombe sur un défaut (et ne fait pas
 * planter en 500), et qu'une valeur valide est bien appliquée.
 */
final class ProfileControllerTest extends TestCase
{
    private function makeController(EntityManagerInterface $entityManager): ProfileController
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new User());

        $repository = $this->createStub(CyclistProfileRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        return new ProfileController($entityManager, $security, $repository);
    }

    private function request(array $body): Request
    {
        return Request::create('/api/profile', 'PUT', [], [], [], [], json_encode($body));
    }

    public function testInvalidEnumFallsBackToDefaults(): void
    {
        $saved = null;
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function ($profile) use (&$saved): void {
            $saved = $profile;
        });

        $controller = $this->makeController($entityManager);

        $response = $controller->update($this->request([
            'bike_type' => 'moto',      // invalide
            'rider_level' => 'wizard',  // invalide
            'usage_type' => 'parade',   // invalide
        ]));

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(CyclistProfile::class, $saved);
        self::assertSame(BikeType::Gravel, $saved->getBikeType());
        self::assertSame(RiderLevel::Expert, $saved->getRiderLevel());
        self::assertSame(UsageType::Sport, $saved->getUsageType());
    }

    public function testValidValuesAreApplied(): void
    {
        $saved = null;
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function ($profile) use (&$saved): void {
            $saved = $profile;
        });

        $controller = $this->makeController($entityManager);

        $response = $controller->update($this->request([
            'bike_type' => 'vtt',
            'rider_level' => 'beginner',
            'usage_type' => 'commute',
        ]));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(BikeType::Vtt, $saved->getBikeType());
        self::assertSame(RiderLevel::Beginner, $saved->getRiderLevel());
        self::assertSame(UsageType::Commute, $saved->getUsageType());
    }
}
