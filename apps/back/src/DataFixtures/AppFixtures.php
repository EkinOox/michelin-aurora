<?php

namespace App\DataFixtures;

use App\Entity\Enum\TerrainType;
use App\Entity\Ride;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('demo@michelin-aurora.dev');
        $manager->persist($user);

        $ride = new Ride();
        $ride->setUser($user);
        $ride->setDistanceKm(0);
        $ride->setElevationM(0);
        $ride->setTerrainType(TerrainType::Mixed);
        $ride->setStartedAt(new \DateTimeImmutable());
        $manager->persist($ride);

        $manager->flush();
    }
}
