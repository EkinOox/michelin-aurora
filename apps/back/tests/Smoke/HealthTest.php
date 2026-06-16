<?php

namespace App\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test fonctionnel de fumée : vérifie que l'API répond sur /api/health.
 * Sert d'exemple de point de départ pour les tests fonctionnels.
 */
final class HealthTest extends WebTestCase
{
    public function testHealthEndpointReturnsOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertSame('ok', $payload['status']);
        self::assertSame('Michelin Aurora API opérationnelle', $payload['message']);
    }
}
