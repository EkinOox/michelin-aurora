<?php

namespace App\Tests\Unit\Controller;

use App\Controller\PressureController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * La recommandation de pression est un calcul métier pur (facteur pluie +
 * décalage avant). Une erreur ici donne une mauvaise consigne au cycliste :
 * c'est la partie « à risque » la plus simple à isoler.
 */
final class PressureControllerTest extends TestCase
{
    public function testRainLowersPressure(): void
    {
        $controller = new PressureController();

        // gravel defaults (front=2.6, rear=2.8), rain adj = -round(2.8*0.10,2) = -0.28
        // rear = round(2.52*10)/10 = 2.5 ; front = round(2.32*10)/10 = 2.3
        $response = $controller->recommendation(Request::create('/api/pressure', 'GET', ['rain' => '1']));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertTrue($payload['rain']);
        self::assertSame(2.5, $payload['rear_bar']);
        self::assertSame(2.3, $payload['front_bar']);
    }

    public function testDryUsesBasePressure(): void
    {
        $controller = new PressureController();

        // gravel defaults, no rain, temp=20 → no adjustment
        $response = $controller->recommendation(Request::create('/api/pressure', 'GET', ['rain' => '0']));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertFalse($payload['rain']);
        self::assertSame(2.8, $payload['rear_bar']);
        self::assertSame(2.6, $payload['front_bar']);
    }

    public function testFrontIsAlwaysLowerThanRear(): void
    {
        $controller = new PressureController();

        $response = $controller->recommendation(Request::create('/api/pressure', 'GET', ['rain' => '1']));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertLessThan($payload['rear_bar'], $payload['front_bar']);
    }
}
