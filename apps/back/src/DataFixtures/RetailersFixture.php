<?php

namespace App\DataFixtures;

use App\Entity\Retailer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Uid\Uuid;

class RetailersFixture extends Fixture
{
    private const XLSX_PATH = __DIR__ . '/../../data/Liste Retail MICHELIN.xlsx';

    private const COUNTRY_NAMES = [
        'UK' => ['🇬🇧', 'Royaume-Uni'],
        'DE' => ['🇩🇪', 'Allemagne'],
        'ES' => ['🇪🇸', 'Espagne'],
        'NL' => ['🇳🇱', 'Pays-Bas'],
        'IT' => ['🇮🇹', 'Italie'],
        'PL' => ['🇵🇱', 'Pologne'],
        'BE' => ['🇧🇪', 'Belgique'],
        'FR' => ['🇫🇷', 'France'],
    ];

    public function load(ObjectManager $manager): void
    {
        if (!file_exists(self::XLSX_PATH)) {
            echo sprintf(
                "⚠  Fichier XLSX introuvable : %s\n   Placez la liste revendeurs dans apps/back/data/\n",
                self::XLSX_PATH,
            );
            return;
        }

        $spreadsheet = IOFactory::load(self::XLSX_PATH);
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        array_shift($rows); // skip header

        $ns      = Uuid::fromString('6ba7b812-9dad-11d1-80b4-00c04fd430c8');
        $loaded  = 0;

        foreach ($rows as $row) {
            [, $country, $url] = array_pad($row, 3, null);
            if (!$url || !$country) {
                continue;
            }

            $url = trim((string) $url);
            if (!str_starts_with($url, 'http')) {
                $url = 'https://' . $url;
            }

            $domain  = preg_replace('#https?://(www\.)?#', '', $url);
            $domain  = rtrim((string) $domain, '/');
            $name    = ucfirst(explode('.', $domain)[0]);
            [$flag, $countryName] = self::COUNTRY_NAMES[trim((string) $country)] ?? ['🌍', $country];

            $retailer = new Retailer();
            $retailer->setId(Uuid::v5($ns, $url));
            $retailer->setName($name);
            $retailer->setSub($flag . ' ' . $countryName);
            $retailer->setUrl($url);

            $manager->persist($retailer);
            ++$loaded;
        }

        $manager->flush();
        echo sprintf("  ✓ %d revendeurs chargés depuis le fichier XLSX\n", $loaded);
    }
}
