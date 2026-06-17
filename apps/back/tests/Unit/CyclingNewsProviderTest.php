<?php

namespace App\Tests\Unit;

use App\Service\CyclingNewsProvider;
use PHPUnit\Framework\TestCase;

/**
 * Vérifie le filtre anti-bruit du flux DirectVelo (intérêt sportif uniquement).
 * Les cas proviennent d'items réels observés sur le flux.
 */
final class CyclingNewsProviderTest extends TestCase
{
    private CyclingNewsProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new CyclingNewsProvider(sys_get_temp_dir());
    }

    /** @dataProvider sportingCases */
    public function testIsSporting(bool $expected, string $title, string $description, array $categories): void
    {
        self::assertSame($expected, $this->provider->isSporting($title, $description, $categories));
    }

    public static function sportingCases(): array
    {
        return [
            'résultat étape gardé' => [
                true,
                'Tour de Suisse Femmes - Et. 1 : Femke de Vries 1ère',
                'Femke de Vries a remporté la première étape du Tour de Suisse Femmes.',
                ['Résultats'],
            ],
            'présentation course gardée' => [
                true,
                'Le nouveau Faun Tour, Classe 1 féminine en Drôme-Ardèche, se dévoile',
                'Boucles Drôme Ardèche Organisation a présenté la nouvelle épreuve.',
                ['Féminines'],
            ],
            'accident mortel rejeté (carnet noir)' => [
                false,
                'Shane O’Brien meurt dans un accident à l\'entraînement',
                'Le Junior est mort ce mardi, il aurait percuté un camion en stationnement.',
                ['Carnet noir'],
            ],
            'accident rejeté par mot-clé' => [
                false,
                'Un coureur de Visma victime d\'une fracture de la clavicule',
                'La formation n\'est pas épargnée par les blessures.',
                ['Route'],
            ],
            'nouveauté produit rejetée' => [
                false,
                'Michelin dévoile le nouveau modèle Power Cup',
                'Une nouvelle gamme de pneus pour la compétition.',
                ['Matériel'],
            ],
            'dopage rejeté' => [
                false,
                'Un coureur suspendu après un contrôle positif',
                'Le coureur a été contrôlé positif lors du dernier Grand Tour.',
                ['Route'],
            ],
        ];
    }
}
