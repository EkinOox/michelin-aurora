<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Récupère l'actualité cyclisme depuis le flux RSS DirectVelo, filtre le bruit
 * (accidents, décès, dopage, nouveautés produit…) pour ne garder que l'intérêt
 * sportif, et mappe chaque item au format attendu par le front (NewsRail).
 *
 * Sans dépendance externe : curl + SimpleXML + cache fichier (TTL 15 min).
 */
class CyclingNewsProvider
{
    private const FEED_URL = 'https://feeds.feedburner.com/ActualitsDirectvelo';
    private const CACHE_TTL = 900;
    private const MAX_ITEMS = 40;

    /** Catégories DirectVelo sans intérêt sportif (rejet direct). */
    private const BLOCK_CATEGORIES = ['carnet noir', 'materiel', 'matériel', 'business', 'technique'];

    /** Mots-clés "événement null" : accidents, santé, dopage, produit. */
    private const BLOCK_KEYWORDS = [
        'accident', 'chute', 'meurt', 'décès', 'deces', 'décédé', 'decede', 'mortel',
        'blessé', 'blesse', 'blessure', 'fracture', 'clavicule', 'hospitalis', 'percut',
        'renvers', 'dopage', 'contrôle positif', 'controle positif', 'suspendu', 'suspension',
        'nouvelle gamme', 'nouveau modèle', 'nouveau modele', 'dévoile le', 'devoile le',
        'lance le', 'test matériel', 'test materiel', 'rappel produit',
    ];

    public function __construct(
        #[Autowire('%kernel.cache_dir%')]
        private string $cacheDir,
    ) {
    }

    /**
     * @return list<array{id:string,tag:string,title:string,date:string,read_time:string,image_key:string,body:string,url:string}>
     */
    public function getArticles(): array
    {
        $cacheFile = $this->cacheDir.'/cycling_news.json';

        if (is_readable($cacheFile) && (time() - (int) @filemtime($cacheFile)) < self::CACHE_TTL) {
            $cached = json_decode((string) @file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $raw = $this->fetchRaw();
        if (null === $raw) {
            // Flux injoignable : on sert le cache même périmé plutôt que rien.
            $cached = is_readable($cacheFile) ? json_decode((string) @file_get_contents($cacheFile), true) : null;

            return is_array($cached) ? $cached : [];
        }

        $articles = $this->parse($raw);
        @file_put_contents($cacheFile, json_encode($articles));

        return $articles;
    }

    /**
     * Décide si un item a un intérêt sportif. Logique pure (testable).
     *
     * @param list<string> $categories
     */
    public function isSporting(string $title, string $description, array $categories): bool
    {
        $haystack = mb_strtolower($title.' '.$description.' '.implode(' ', $categories));

        foreach ($categories as $category) {
            if (in_array(mb_strtolower(trim($category)), self::BLOCK_CATEGORIES, true)) {
                return false;
            }
        }

        foreach (self::BLOCK_KEYWORDS as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return false;
            }
        }

        return true;
    }

    private function fetchRaw(): ?string
    {
        $ch = curl_init(self::FEED_URL);
        if (false === $ch) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_USERAGENT => 'MichelinAurora/1.0 (+https://michelin-aurora.duckdns.org)',
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $code < 200 || $code >= 300) {
            return null;
        }

        return $body;
    }

    /**
     * @return list<array{id:string,tag:string,title:string,date:string,read_time:string,image_key:string,body:string,url:string}>
     */
    private function parse(string $raw): array
    {
        $xml = @simplexml_load_string($raw);
        if (false === $xml || !isset($xml->channel->item)) {
            return [];
        }

        $articles = [];

        foreach ($xml->channel->item as $item) {
            $title = $this->clean((string) $item->title);
            $description = $this->clean((string) $item->description);

            $categories = [];
            foreach ($item->category as $category) {
                $categories[] = (string) $category;
            }

            if ('' === $title || !$this->isSporting($title, $description, $categories)) {
                continue;
            }

            $link = (string) $item->link;
            $enclosureUrl = isset($item->enclosure['url']) ? (string) $item->enclosure['url'] : '';

            $articles[] = [
                'id' => 'dv-'.(((string) $item->guid) ?: md5($link)),
                'tag' => $categories[0] ?? 'Cyclisme',
                'title' => $title,
                'date' => $this->formatDate((string) $item->pubDate),
                'read_time' => $this->readTime($description),
                'image_key' => '' !== $enclosureUrl ? $enclosureUrl : 'peloton',
                'body' => $description,
                'url' => $link,
            ];

            if (count($articles) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $articles;
    }

    private function clean(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /** Formate une date RFC-822 en style "17 JUIN" (cohérent avec les articles seedés). */
    private function formatDate(string $pubDate): string
    {
        try {
            $dt = new \DateTimeImmutable($pubDate);
        } catch (\Exception) {
            return '';
        }

        $months = ['JANV', 'FÉVR', 'MARS', 'AVR', 'MAI', 'JUIN', 'JUIL', 'AOÛT', 'SEPT', 'OCT', 'NOV', 'DÉC'];

        return (int) $dt->format('j').' '.$months[(int) $dt->format('n') - 1];
    }

    private function readTime(string $description): string
    {
        $minutes = max(1, (int) round(mb_strlen($description) / 700));

        return $minutes.' min';
    }
}
