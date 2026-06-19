<?php

namespace App\DataFixtures;

use App\Entity\Enum\BikeType;
use App\Entity\Tire;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Uid\Uuid;

class TiresFixture extends Fixture
{
    private const XLSX_PATH = __DIR__ . '/../../data/2W Bicycle Product Catalog v4 - 2026.xlsx';

    private const BIKE_TYPE_MAP = [
        'ROAD'                            => BikeType::Route,
        'ROAD,E-BIKE'                     => BikeType::Route,
        'MTB'                             => BikeType::Vtt,
        'MTB,E-BIKE'                      => BikeType::Vtt,
        'GRAVEL,COMMUTING & TOUR,E-BIKE'  => BikeType::Gravel,
        'GRAVEL,E-BIKE'                   => BikeType::Gravel,
        'COMMUTING & TOUR'                => BikeType::Vae,
        'COMMUTING & TOUR,E-BIKE'         => BikeType::Vae,
        'E-BIKE'                          => BikeType::Vae,
    ];

    private const SKIP_TYPES = ['INNER TUBES', 'KIDS'];

    private const COLOR_TOKEN = [
        'route'  => '#27509B',
        'vtt'    => '#E71D36',
        'gravel' => '#84BD00',
        'vae'    => '#2EC4B6',
    ];

    private const SEGMENT_PRICE = [
        'PREMIUM RACING LINE'      => [95,  140],
        'PREMIUM COMPETITION LINE' => [70,  100],
        'PREMIUM PERFORMANCE LINE' => [45,   80],
        'ACCESS LINE'              => [20,   50],
    ];

    private const SEGMENT_KM = [
        'PREMIUM RACING LINE'      => 4000,
        'PREMIUM COMPETITION LINE' => 5000,
        'PREMIUM PERFORMANCE LINE' => 6000,
        'ACCESS LINE'              => 8000,
    ];

    private const SEGMENT_TAG = [
        'PREMIUM RACING LINE'      => 'Racing',
        'PREMIUM COMPETITION LINE' => 'Competition',
        'PREMIUM PERFORMANCE LINE' => 'Performance',
        'ACCESS LINE'              => 'Access',
    ];

    public function load(ObjectManager $manager): void
    {
        if (!file_exists(self::XLSX_PATH)) {
            echo sprintf(
                "⚠  Fichier XLSX introuvable : %s\n   Placez le catalogue produit dans apps/back/data/\n",
                self::XLSX_PATH,
            );
            return;
        }

        $spreadsheet = IOFactory::load(self::XLSX_PATH);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);
        $headers     = array_shift($rows);
        $idx         = array_flip(array_filter($headers, fn($v) => is_string($v) || is_int($v)));

        $seen = [];
        $now  = new \DateTime();
        $upserted = 0;

        foreach ($rows as $row) {
            $rangeName = $row[$idx['Web Range Name']] ?? null;
            if (!$rangeName) {
                continue;
            }

            // Skip products discontinued before today
            $disc = $row[$idx['Discontinued Date']] ?? null;
            if ($disc instanceof \DateTime && $disc < $now) {
                continue;
            }
            if (is_string($disc) && $disc !== '') {
                try {
                    if (new \DateTime($disc) < $now) {
                        continue;
                    }
                } catch (\Exception) {}
            }

            // One representative entry per range
            if (isset($seen[$rangeName])) {
                continue;
            }
            $seen[$rangeName] = true;

            $cycleWeb = trim((string) ($row[$idx['CYCLE TYPE WEB']] ?? ''));

            if (in_array($cycleWeb, self::SKIP_TYPES, true) || $cycleWeb === '') {
                continue;
            }

            $bikeType = self::BIKE_TYPE_MAP[$cycleWeb] ?? null;
            if ($bikeType === null) {
                continue;
            }

            $segment = (string) ($row[$idx['Segment']] ?? 'PREMIUM PERFORMANCE LINE');
            $weightRaw = $row[$idx['Weight (g)']] ?? null;
            $weight  = is_numeric($weightRaw) ? (int) $weightRaw : null;

            $tire = new Tire();
            $tire->setId(Uuid::v5(Uuid::fromString('6ba7b812-9dad-11d1-80b4-00c04fd430c8'), $rangeName));
            $tire->setName($this->cleanName($rangeName));
            $tire->setBikeType($bikeType);
            $tire->setPriceEur((string) $this->deterministicPrice($segment, $rangeName));
            $tire->setAvgKmLifetime(self::SEGMENT_KM[$segment] ?? 5000);
            $tire->setSubtitle($this->buildSubtitle(
                $row[$idx['Use']] ?? null,
                $row[$idx['Terrain Types']] ?? null,
            ));
            $tire->setTag(self::SEGMENT_TAG[$segment] ?? 'Performance');
            $tire->setColorToken(self::COLOR_TOKEN[$bikeType->value]);
            $tire->setWeightG($weight);
            $tire->setDiameterLabel($this->buildDiameterLabel(
                $cycleWeb,
                $row[$idx['Web Diameter (mm)']] ?? null,
                $row[$idx['Web Diameter (Inch)']] ?? null,
                $row[$idx['Web Width (mm)']] ?? null,
                $row[$idx['Web Width (Inch)']] ?? null,
            ));

            $manager->persist($tire);
            ++$upserted;
        }

        $manager->flush();
        echo sprintf("  ✓ %d pneus chargés depuis le catalogue XLSX\n", $upserted);
    }

    private function cleanName(string $raw): string
    {
        $suffixes = [
            ' RACING LINE', ' COMPETITION LINE', ' PERFORMANCE LINE', ' ACCESS LINE',
        ];
        foreach ($suffixes as $s) {
            $raw = str_replace($s, '', $raw);
        }
        $raw = preg_replace('/^MICHELIN\s+/i', '', $raw) ?? $raw;
        return ucwords(strtolower(trim($raw)));
    }

    private function deterministicPrice(string $segment, string $name): float
    {
        [$lo, $hi] = self::SEGMENT_PRICE[$segment] ?? [30, 60];
        $hash = hexdec(substr(md5($name), 0, 4));
        return round($lo + ($hash / 0xFFFF) * ($hi - $lo), 2);
    }

    private function buildSubtitle(mixed $use, mixed $terrain): ?string
    {
        $parts = [];
        if ($use)     { $parts[] = ucwords(strtolower(explode(',', (string) $use)[0])); }
        if ($terrain) { $parts[] = ucwords(strtolower(explode(',', (string) $terrain)[0])); }
        return $parts ? implode(' · ', $parts) : null;
    }

    private function buildDiameterLabel(
        string $cycleWeb,
        mixed  $diamMm,
        mixed  $diamIn,
        mixed  $widthMm,
        mixed  $widthIn,
    ): ?string {
        $dm = is_numeric($diamMm)  ? (float) $diamMm  : null;
        $di = is_numeric($diamIn)  ? (float) $diamIn  : null;
        $wm = is_numeric($widthMm) ? (float) $widthMm : null;
        $wi = is_numeric($widthIn) ? (float) $widthIn : null;

        if (str_contains($cycleWeb, 'MTB') || str_contains($cycleWeb, 'GRAVEL')) {
            $d = $di ?? ($dm ? round($dm / 25.4) : null);
            $w = $wi ?? ($wm ? round($wm / 25.4, 1) : null);
            if ($d && $w) {
                return sprintf('%d" × %s"', (int) $d, rtrim(rtrim((string) $w, '0'), '.'));
            }
        } else {
            $d = $dm ?? ($di ? round($di * 25.4) : null);
            $w = $wm ?? ($wi ? round($wi * 25.4) : null);
            if ($d && $w) {
                return sprintf('%dC × %dmm', (int) $d, (int) $w);
            }
        }

        return null;
    }
}
