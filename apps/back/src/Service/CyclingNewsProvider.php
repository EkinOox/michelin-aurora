<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Récupère l'actualité cyclisme depuis plusieurs flux RSS (DirectVelo pour la
 * route, Vojo pour le VTT/gravel/DH/enduro, calendrier velowire pour les
 * événements à venir), filtre le bruit (accidents, décès, dopage, nouveautés
 * produit…) pour ne garder que l'intérêt sportif, dédoublonne, fusionne et trie
 * par date, puis mappe au format attendu par le front (NewsRail).
 *
 * Chaque item porte une catégorie large `cat` (route / vtt / event) qui sert de
 * filtre côté front — volontairement peu nombreuses pour rester lisibles.
 *
 * Sans dépendance externe : curl (multi = parallèle) + SimpleXML + cache fichier.
 */
class CyclingNewsProvider
{
    private const CACHE_TTL = 900;
    private const MAX_ITEMS = 44;

    /**
     * Sources RSS. Pour chaque source :
     *  - cat        : catégorie large pour le filtre front (route / vtt / event) ;
     *  - keep       : si non vide, whitelist de catégories d'item à garder ;
     *  - tag        : libellé détaillé forcé (sinon = 1re catégorie de l'item) ;
     *  - image      : clé/URL d'image par défaut quand l'item n'en fournit pas ;
     *  - limit      : plafond d'items par source (diversité des disciplines) ;
     *  - reencode   : true si flux en double-encodage UTF-8 à corriger (velowire) ;
     *  - fetchImage : true pour aller chercher l'og:image de l'article (flux sans image).
     *
     * @var list<array{id:string,url:string,cat:string,keep:list<string>,tag:?string,image:string,limit:int,reencode:bool,fetchImage:bool}>
     */
    private const SOURCES = [
        ['id' => 'dv', 'url' => 'https://feeds.feedburner.com/ActualitsDirectvelo', 'cat' => 'route', 'keep' => [], 'tag' => null, 'image' => 'peloton', 'limit' => 24, 'reencode' => false, 'fetchImage' => false],
        ['id' => 'vojo', 'url' => 'https://www.vojomag.com/feed/', 'cat' => 'vtt', 'keep' => ['sport'], 'tag' => 'VTT', 'image' => 'gravelBike', 'limit' => 12, 'reencode' => false, 'fetchImage' => true],
        ['id' => 'vw', 'url' => 'http://www.velowire.com/calendar/rss.php?l=fr', 'cat' => 'event', 'keep' => [], 'tag' => 'À venir', 'image' => 'sunsetRide', 'limit' => 8, 'reencode' => true, 'fetchImage' => false],
    ];

    /** Catégories sans intérêt sportif (rejet direct). */
    private const BLOCK_CATEGORIES = ['carnet noir', 'materiel', 'matériel', 'business', 'technique', 'tech', 'nature'];

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
     * @return list<array{id:string,cat:string,tag:string,title:string,date:string,read_time:?string,image_key:string,body:string,url:string}>
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

        return $this->refresh($cacheFile);
    }

    /** Force la récupération des flux et la réécriture du cache (utilisé par le warm-up cron). */
    public function refresh(?string $cacheFile = null): array
    {
        $cacheFile ??= $this->cacheDir.'/cycling_news.json';

        // 1) Récupération des flux en parallèle.
        $feeds = $this->fetchMany(array_column(self::SOURCES, 'url', 'id'));

        $articles = [];
        $anyOk = false;
        foreach (self::SOURCES as $source) {
            $raw = $feeds[$source['id']] ?? null;
            if (null === $raw) {
                continue;
            }
            $anyOk = true;
            $articles = array_merge($articles, array_slice($this->parse($raw, $source), 0, $source['limit']));
        }

        if (!$anyOk) {
            // Tous les flux injoignables : on sert le cache même périmé plutôt que rien.
            $cached = is_readable($cacheFile) ? json_decode((string) @file_get_contents($cacheFile), true) : null;

            return is_array($cached) ? $cached : [];
        }

        // 2) Images manquantes : og:image des articles concernés, en parallèle.
        $this->hydrateImages($articles);

        // 3) Dédoublonnage par titre normalisé (garde la 1re occurrence).
        $articles = $this->deduplicate($articles);

        // 4) Tri par date décroissante (événements à venir en tête) + plafond + nettoyage.
        usort($articles, static fn ($a, $b) => $b['_ts'] <=> $a['_ts']);
        $articles = array_slice($articles, 0, self::MAX_ITEMS);
        $articles = array_map(static function (array $a): array {
            unset($a['_ts'], $a['_needImg']);

            return $a;
        }, $articles);

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

    /**
     * Récupère plusieurs URLs en parallèle (curl_multi).
     *
     * @param array<string,string> $urls
     *
     * @return array<string,?string>
     */
    private function fetchMany(array $urls): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($urls as $key => $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_USERAGENT => 'MichelinAurora/1.0 (+https://michelin-aurora.duckdns.org)',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && CURLM_OK === $status);

        $out = [];
        foreach ($handles as $key => $ch) {
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $body = curl_multi_getcontent($ch);
            $out[$key] = (is_string($body) && $code >= 200 && $code < 300) ? $body : null;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $out;
    }

    /**
     * Complète l'image des items marqués `_needImg` via l'og:image de leur page.
     *
     * @param list<array<string,mixed>> $articles
     */
    private function hydrateImages(array &$articles): void
    {
        $targets = [];
        foreach ($articles as $i => $a) {
            if (!empty($a['_needImg'])) {
                $targets[$i] = $a['url'];
            }
        }
        if (!$targets) {
            return;
        }

        $pages = $this->fetchMany($targets);
        foreach ($targets as $i => $url) {
            $img = isset($pages[$i]) && null !== $pages[$i] ? $this->extractOgImage($pages[$i]) : null;
            if (null !== $img) {
                $articles[$i]['image_key'] = $img;
            }
        }
    }

    private function extractOgImage(string $html): ?string
    {
        if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
            $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return str_starts_with($url, 'http') ? $url : null;
        }

        return null;
    }

    /**
     * Supprime les doublons par titre normalisé (accents/casse/ponctuation ignorés).
     *
     * @param list<array<string,mixed>> $articles
     *
     * @return list<array<string,mixed>>
     */
    private function deduplicate(array $articles): array
    {
        $seen = [];
        $out = [];
        foreach ($articles as $a) {
            $key = preg_replace('/[^a-z0-9]+/', '', $this->stripAccents(mb_strtolower((string) $a['title'])));
            if ('' === $key || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $a;
        }

        return $out;
    }

    private function stripAccents(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return false !== $converted ? $converted : $value;
    }

    /**
     * @param array{id:string,url:string,cat:string,keep:list<string>,tag:?string,image:string,limit:int,reencode:bool,fetchImage:bool} $source
     *
     * @return list<array{id:string,cat:string,tag:string,title:string,date:string,read_time:?string,image_key:string,body:string,url:string,_ts:int,_needImg:bool}>
     */
    private function parse(string $raw, array $source): array
    {
        $xml = @simplexml_load_string($raw);
        if (false === $xml || !isset($xml->channel->item)) {
            return [];
        }

        $articles = [];

        foreach ($xml->channel->item as $item) {
            $title = $this->clean((string) $item->title, $source['reencode']);
            $description = $this->clean((string) $item->description, $source['reencode']);

            $categories = [];
            foreach ($item->category as $category) {
                $categories[] = (string) $category;
            }

            if (!empty($source['keep'])) {
                $lower = array_map(static fn ($c) => mb_strtolower(trim($c)), $categories);
                if (empty(array_intersect($lower, $source['keep']))) {
                    continue;
                }
            }

            if ('' === $title || !$this->isSporting($title, $description, $categories)) {
                continue;
            }

            // Pour les événements, on remplace le HTML du calendrier par la plage de dates.
            if ('event' === $source['cat']
                && preg_match('/(du\s+\d{1,2}\s+\p{L}+\s+\d{4}\s+au\s+\d{1,2}\s+\p{L}+\s+\d{4}|le\s+\d{1,2}\s+\p{L}+\s+\d{4})/ui', $description, $m)) {
                $description = ucfirst($m[1]);
            }

            $link = (string) $item->link;
            $enclosureUrl = isset($item->enclosure['url']) ? (string) $item->enclosure['url'] : '';

            $articles[] = [
                'id' => $source['id'].'-'.(((string) $item->guid) ?: md5($link)),
                'cat' => $source['cat'],
                'tag' => $source['tag'] ?? ($categories[0] ?? 'Cyclisme'),
                'title' => $title,
                'date' => $this->formatDate((string) $item->pubDate),
                'read_time' => null, // teaser RSS seulement : pas de temps de lecture fiable.
                'image_key' => '' !== $enclosureUrl ? $enclosureUrl : $source['image'],
                'body' => $description,
                'url' => $link,
                '_ts' => $this->parseDate((string) $item->pubDate)?->getTimestamp() ?? 0,
                '_needImg' => '' === $enclosureUrl && $source['fetchImage'],
            ];
        }

        return $articles;
    }

    private function clean(string $value, bool $fixDoubleUtf8 = false): string
    {
        // Certains flux (velowire) sont en double-encodage UTF-8 : on l'inverse.
        if ($fixDoubleUtf8) {
            $reverted = @mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            if (is_string($reverted) && mb_check_encoding($reverted, 'UTF-8')) {
                $value = $reverted;
            }
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /** Parse une date RFC-822, en tolérant le format velowire "00h00" (→ "00:00"). */
    private function parseDate(string $pubDate): ?\DateTimeImmutable
    {
        $clean = preg_replace('/(\d{1,2})h(\d{2})/', '$1:$2', trim($pubDate)) ?? $pubDate;

        try {
            return new \DateTimeImmutable($clean);
        } catch (\Exception) {
            return null;
        }
    }

    /** Formate une date en style "17 JUIN" (cohérent avec les articles seedés). */
    private function formatDate(string $pubDate): string
    {
        $dt = $this->parseDate($pubDate);
        if (null === $dt) {
            return '';
        }

        $months = ['JANV', 'FÉVR', 'MARS', 'AVR', 'MAI', 'JUIN', 'JUIL', 'AOÛT', 'SEPT', 'OCT', 'NOV', 'DÉC'];

        return (int) $dt->format('j').' '.$months[(int) $dt->format('n') - 1];
    }
}
