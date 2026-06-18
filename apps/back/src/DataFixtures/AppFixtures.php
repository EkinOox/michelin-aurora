<?php

namespace App\DataFixtures;

use App\Entity\CommunityRider;
use App\Entity\CyclistProfile;
use App\Entity\Enum\BikeType;
use App\Entity\Enum\Difficulty;
use App\Entity\Enum\EventType;
use App\Entity\Enum\RewardsLevel;
use App\Entity\Enum\RiderLevel;
use App\Entity\Enum\TerrainType;
use App\Entity\Enum\UsageType;
use App\Entity\Event;
use App\Entity\NewsArticle;
use App\Entity\RewardCatalogItem;
use App\Entity\Ride;
use App\Entity\Route;
use App\Entity\Tire;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('demo@michelin-aurora.dev');
        $user->setName('Thomas Garnier');
        $user->setCity('Clermont-Ferrand');
        $user->setTotalPoints(3820);
        $user->setRewardsLevel(RewardsLevel::Performer);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'demo12345'));
        $manager->persist($user);

        $profile = new CyclistProfile();
        $profile->setUser($user);
        $profile->setBikeType(BikeType::Gravel);
        $profile->setRiderLevel(RiderLevel::Expert);
        $profile->setUsageType(UsageType::Sport);
        $profile->setPreferences(['Performance', 'Sécurité']);
        $manager->persist($profile);

        $ride = new Ride();
        $ride->setUser($user);
        $ride->setDistanceKm(0);
        $ride->setElevationM(0);
        $ride->setTerrainType(TerrainType::Mixed);
        $ride->setStartedAt(new \DateTimeImmutable());
        $manager->persist($ride);

        // Pneus et revendeurs sont gérés par TiresFixture et RetailersFixture (depuis XLSX).
        // On crée 3 pneus légers ici uniquement pour que les routes ci-dessous
        // puissent référencer un Tire sans dépendance entre fixtures.
        $tireGravel = new Tire();
        $tireGravel->setName('Power Gravel');
        $tireGravel->setBikeType(BikeType::Gravel);
        $tireGravel->setPriceEur('54.90');
        $tireGravel->setAvgKmLifetime(6500);
        $tireGravel->setColorToken('#84BD00');
        $manager->persist($tireGravel);

        $tireRoad = new Tire();
        $tireRoad->setName('Power Road TLR');
        $tireRoad->setBikeType(BikeType::Route);
        $tireRoad->setPriceEur('62.00');
        $tireRoad->setAvgKmLifetime(5800);
        $tireRoad->setColorToken('#27509B');
        $manager->persist($tireRoad);

        $tireMtb = new Tire();
        $tireMtb->setName('Wild Enduro');
        $tireMtb->setBikeType(BikeType::Vtt);
        $tireMtb->setPriceEur('58.50');
        $tireMtb->setAvgKmLifetime(7200);
        $tireMtb->setColorToken('#E71D36');
        $manager->persist($tireMtb);

        $routes = [
            ['name' => 'Col de la Croix Morand', 'bikeType' => BikeType::Route, 'difficulty' => Difficulty::Expert, 'score' => 9.2, 'km' => 48, 'gain' => 980, 'surface' => 'Bitume', 'dur' => '2h10', 'safety' => 92, 'fun' => 88, 'match' => 96, 'tag' => 'Performance', 'tire' => $tireRoad,
                'desc' => 'Ascension rapide, bitume parfait, descente technique — segment Strava référence.'],
            ['name' => 'Chemins des Volcans', 'bikeType' => BikeType::Gravel, 'difficulty' => Difficulty::Expert, 'score' => 9.6, 'km' => 62, 'gain' => 740, 'surface' => 'Mixte', 'dur' => '3h05', 'safety' => 84, 'fun' => 95, 'match' => 98, 'tag' => 'Aventure', 'tire' => $tireGravel,
                'desc' => 'Chemins blancs, single forestier, mix route/terre. Idéal Power Gravel.'],
            ['name' => 'Single Forêt de Randan', 'bikeType' => BikeType::Vtt, 'difficulty' => Difficulty::Expert, 'score' => 8.7, 'km' => 24, 'gain' => 610, 'surface' => 'Technique', 'dur' => '1h45', 'safety' => 71, 'fun' => 97, 'match' => 74, 'tag' => 'Technique', 'tire' => $tireMtb,
                'desc' => 'Single tracks engagés, descentes techniques, racines & cailloux.'],
            ['name' => 'Boucle Allier Sécurisée', 'bikeType' => BikeType::Vae, 'difficulty' => Difficulty::Moderate, 'score' => 8.9, 'km' => 38, 'gain' => 220, 'surface' => 'Bitume', 'dur' => '1h50', 'safety' => 96, 'fun' => 79, 'match' => 90, 'tag' => 'Sécurisé', 'tire' => $tireGravel,
                'desc' => 'Parcours sécurisé, dénivelé maîtrisé, gestion batterie optimisée.'],
        ];

        foreach ($routes as $r) {
            $route = new Route();
            $route->setName($r['name']);
            $route->setBikeType($r['bikeType']);
            $route->setDifficulty($r['difficulty']);
            $route->setMichelinScore($r['score']);
            $route->setDistanceKm($r['km']);
            $route->setElevationGainM($r['gain']);
            $route->setSurface($r['surface']);
            $route->setDurationLabel($r['dur']);
            $route->setDescription($r['desc']);
            $route->setSafetyScore($r['safety']);
            $route->setFunScore($r['fun']);
            $route->setMatchScore($r['match']);
            $route->setTag($r['tag']);
            $route->setTire($r['tire']);
            $manager->persist($route);
        }

        $events = [
            ['name' => "Bol d'Or Vélo", 'date' => '12 JUIL', 'place' => 'Magny-Cours', 'dist' => '24 h', 'type' => EventType::Endurance, 'km' => 8, 'riders' => 1240, 'img' => 'peloton'],
            ['name' => 'Sunset Gravel', 'date' => '24 JUIN', 'place' => 'Puy-de-Dôme', 'dist' => '85 km', 'type' => EventType::Gravel, 'km' => 22, 'riders' => 480, 'img' => 'sunsetRide'],
            ['name' => 'Cyclo des Volcans', 'date' => '03 AOÛT', 'place' => 'Clermont-Fd', 'dist' => '130 km', 'type' => EventType::Route, 'km' => 5, 'riders' => 920, 'img' => 'duo'],
        ];

        foreach ($events as $e) {
            $event = new Event();
            $event->setName($e['name']);
            $event->setDate($e['date']);
            $event->setPlace($e['place']);
            $event->setDistanceLabel($e['dist']);
            $event->setType($e['type']);
            $event->setKmAway($e['km']);
            $event->setRiders($e['riders']);
            $event->setImageKey($e['img']);
            $manager->persist($event);
        }

        $riders = [
            ['name' => 'Léa M.', 'initials' => 'LM', 'rank' => RewardsLevel::EliteCyclist, 'km' => 312, 'match' => 94, 'color' => '#84BD00'],
            ['name' => 'Karim D.', 'initials' => 'KD', 'rank' => RewardsLevel::Performer, 'km' => 268, 'match' => 91, 'color' => '#27509B'],
            ['name' => 'Sofia R.', 'initials' => 'SR', 'rank' => RewardsLevel::Performer, 'km' => 241, 'match' => 88, 'color' => '#582C83'],
        ];

        foreach ($riders as $r) {
            $rider = new CommunityRider();
            $rider->setName($r['name']);
            $rider->setInitials($r['initials']);
            $rider->setRank($r['rank']);
            $rider->setKmThisMonth($r['km']);
            $rider->setMatchPercent($r['match']);
            $rider->setColorHex($r['color']);
            $manager->persist($rider);
        }

        $articles = [
            ['tag' => 'Innovation', 'title' => 'Power Cup TLR : la nouvelle référence route', 'date' => '10 JUIN', 'read' => '3 min', 'img' => 'bikeWhite',
                'body' => 'Michelin dévoile le Power Cup TLR, un pneu tubeless-ready taillé pour la compétition : gomme bi-densité, carcasse 4x120 TPI et un rendement parmi les meilleurs du marché. Pensé pour les cyclistes exigeants qui veulent vitesse et résistance aux crevaisons.'],
            ['tag' => 'Compétition', 'title' => 'Michelin équipe 3 équipes World Tour 2026', 'date' => '02 JUIN', 'read' => '2 min', 'img' => 'peloton',
                'body' => "Pour la saison 2026, Michelin renforce son engagement dans le cyclisme professionnel en équipant trois équipes World Tour. Un terrain d'essai grandeur nature pour les technologies qui équiperont demain les pneus grand public."],
            ['tag' => 'Durabilité', 'title' => 'Recyclage des pneus vélo : +40% en 2026', 'date' => '24 MAI', 'read' => '4 min', 'img' => 'bikepacking',
                'body' => 'Le programme de retour et recyclage des pneus Michelin progresse de 40% sur un an. Chaque pneu rapporté chez un revendeur agréé génère des Ride Points et contribue à une mobilité plus responsable.'],
        ];

        foreach ($articles as $a) {
            $article = new NewsArticle();
            $article->setTag($a['tag']);
            $article->setTitle($a['title']);
            $article->setDate($a['date']);
            $article->setReadTime($a['read']);
            $article->setImageKey($a['img']);
            $article->setBody($a['body']);
            $manager->persist($article);
        }

        // Revendeurs gérés par RetailersFixture (depuis XLSX).

        $rewardCatalog = [
            ['cost' => 1000, 'title' => '−10 € gamme route', 'sub' => 'Bon Michelin pneus route', 'icon' => 'gift'],
            ['cost' => 2000, 'title' => '−10 % panier', 'sub' => 'Sur toute la boutique', 'icon' => 'cart'],
            ['cost' => 4000, 'title' => 'Montage offert', 'sub' => 'Livraison + pose offerts', 'icon' => 'bike'],
            ['cost' => 8000, 'title' => 'Test prototype', 'sub' => 'Accès essais & événements', 'icon' => 'star'],
        ];

        foreach ($rewardCatalog as $rc) {
            $item = new RewardCatalogItem();
            $item->setCost($rc['cost']);
            $item->setTitle($rc['title']);
            $item->setSub($rc['sub']);
            $item->setIcon($rc['icon']);
            $manager->persist($item);
        }

        $manager->flush();
    }
}
