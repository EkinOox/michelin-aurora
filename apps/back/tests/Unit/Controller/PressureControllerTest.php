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

        // base = 2.9 ; pluie : arrière = 2.9 * 0.9 = 2.61 -> 2.6 ; avant = 2.41 -> 2.4
        $response = $controller->recommendation(Request::create('/api/pressure', 'GET', ['rain' => '1']));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertTrue($payload['rain']);
        self::assertSame(2.6, $payload['rear_bar']);
        self::assertSame(2.4, $payload['front_bar']);
    }

    public function testDryUsesBasePressure(): void
    {
        $controller = new PressureController();

        // sec : arrière = 2.9 ; avant = 2.9 - 0.2 = 2.7
        $response = $controller->recommendation(Request::create('/api/pressure', 'GET', ['rain' => '0']));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertFalse($payload['rain']);
        self::assertSame(2.9, $payload['rear_bar']);
        self::assertSame(2.7, $payload['front_bar']);
    }

    public function testFrontIsAlwaysLowerThanRear(): void
    {
        $controller = new PressureController();

        $response = $controller->recommendation(Request::create('/api/pressure', 'GET', ['rain' => '1']));

        $payload = json_decode((string) $response->getContent(), true);
        self::assertLessThan($payload['rear_bar'], $payload['front_bar']);
    }
}
